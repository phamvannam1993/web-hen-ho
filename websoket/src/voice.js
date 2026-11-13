'use strict';

function createVoice({ config, clients, send, log = () => {} }) {
  const calls = new Map();
  const busy = new Map();
  const cooldown = new Map();
  const saving = new Set();
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
  const open = ws => ws && ws.readyState === 1;
  const fresh = ws => open(ws) && Number.isFinite(ws.authExpiresAt)
    && ws.authExpiresAt > Date.now();

  function peers(uid, event, except) {
    for (const ws of clients.get(uid) || []) {
      if (ws !== except && open(ws)
        && (event.t !== 'voice.incoming' || fresh(ws))) send(ws, event);
    }
  }

  function live(c) {
    return calls.get(c.id) === c && fresh(c.a)
      && (!c.b || fresh(c.b)) && c.expires > Date.now();
  }

  async function saveResult(data) {
    // Same callId for every attempt: PHP's unique key guarantees one message.
    for (let attempt = 0; attempt < 5; attempt++) {
      try {
        const response = await fetch(config.appUrl + '/internal/voice/completed', {
          method:'POST', redirect:'error', signal:AbortSignal.timeout(5000),
          headers:{'Content-Type':'application/json','X-Realtime-Secret':config.secret},
          body:JSON.stringify(data),
        });
        if (response.ok && (await response.json()).ok) return;
        if ([400,403,409,422].includes(response.status)) break;
      } catch { /* A temporary HTTP failure must not lose the result immediately. */ }
      if (attempt < 4) await new Promise(resolve => setTimeout(resolve, 1000 * (attempt + 1)));
    }
    log({event:'voice.history_failed',callId:data.callId});
  }

  function end(c, reason, declined = false) {
    if (calls.get(c.id) !== c) return;
    log({event:'voice.ended',callId:c.id,reason,phase:c.state,
      elapsedSeconds:Math.max(0,Math.floor((Date.now()-c.startedAt)/1000))});
    calls.delete(c.id);
    for (const uid of [c.uid, c.peer]) {
      if (busy.get(uid) === c) busy.delete(uid);
    }
    const event = { t: 'voice.ended', callId: c.id, reason };
    if (open(c.a)) send(c.a, event);
    if (c.peer && !c.suppressed) peers(c.peer, event);
    // No history for an invite that failed participant authorization.
    if (c.peer) {
      const connected = c.connectedAt !== undefined;
      const outcome = connected ? 'completed' : declined ? 'declined'
        : reason === 'busy' ? 'busy' : !c.b ? 'missed' : 'failed';
      const job = saveResult({callId:c.id,conversationId:c.cid,uid:c.uid,peerId:c.peer,
        video:c.video,outcome,durationSeconds:connected
          ? Math.min(3000,Math.max(0,Math.floor((Date.now()-c.connectedAt)/1000))) : 0});
      saving.add(job);
      job.finally(() => saving.delete(job));
    }
  }

  async function authorize(uid, conversationId) {
    const response = await fetch(config.appUrl + '/internal/voice/authorize', {
      method: 'POST',
      redirect: 'error',
      signal: AbortSignal.timeout(3000),
      headers: {
        'Content-Type': 'application/json',
        'X-Realtime-Secret': config.secret,
      },
      body: JSON.stringify({ uid, conversationId }),
    });
    if (!response.ok) throw new Error('permission');
    const data = await response.json();
    if (!data.ok || !Number.isSafeInteger(data.peer?.id)
      || data.peer.id <= 0) throw new Error('permission');
    return { ...data.peer, notificationsEnabled: data.calls_notifications_enabled !== false };
  }

  async function pair(c) {
    let peer, caller;
    if (c.peer) {
      [peer, caller] = await Promise.all([authorize(c.uid, c.cid), authorize(c.peer, c.cid)]);
    } else {
      peer = await authorize(c.uid, c.cid);
      if (!live(c) || peer.id === c.uid) throw new Error('permission');
      caller = await authorize(peer.id, c.cid);
    }
    if (!live(c) || peer.id === c.uid
      || (c.peer && c.peer !== peer.id)) throw new Error('permission');
    if (!live(c) || caller.id !== c.uid) throw new Error('permission');
    return { peer, caller };
  }

  async function message(ws, m) {
    if (typeof m.callId !== 'string' || !uuid.test(m.callId)) return;
    const fail = reason => send(ws, {
      t: 'voice.error', callId: m.callId, reason,
    });
    let c = calls.get(m.callId);
    if (m.t === 'voice.end') {
      if (c && (c.a === ws || c.b === ws
        || (!c.suppressed && !c.b && c.state === 'ringing' && c.peer === ws.uid))) {
        end(c, 'ended', !c.b && ws.uid === c.peer);
      }
      return;
    }
    if (!fresh(ws)) return fail('expired');

    if (m.t === 'voice.invite') {
      const now = Date.now();
      if (!Number.isSafeInteger(m.conversationId)
        || m.conversationId <= 0) return fail('permission');
      if (c || busy.has(ws.uid) || calls.size >= 200
        || (cooldown.get(ws.uid) || 0) > now
        || cooldown.size >= 10000) return fail('busy');
      cooldown.set(ws.uid, now + 10000);
      c = {
        id: m.callId, cid: m.conversationId, uid: ws.uid,
        startedAt: now,
        // Gọi video: cùng luồng báo hiệu, chỉ khác SDP có thêm track camera
        video: m.video === true || m.video === 1 || m.video === '1' || m.video === 'true',
        callerTrickle: m.trickle === true, trickle: false, iceA: 0, iceB: 0,
        a: ws, b: null, peer: 0, state: 'pending', stage: 0,
        until: now + 35000,
        expires: Math.min(ws.authExpiresAt, now + 50 * 60000),
        checkAt: now + 30000, checking: false,
      };
      calls.set(c.id, c);
      busy.set(c.uid, c);
      try {
        const identities = await pair(c);
        if (!live(c)) return end(c, 'expired');
        c.peer = identities.peer.id;
        if (busy.has(c.peer)) return end(c, 'busy');
        if (![...(clients.get(c.peer) || [])].some(fresh)) {
          return end(c, 'offline');
        }
        c.suppressed = !identities.caller.notificationsEnabled;
        if (!c.suppressed) busy.set(c.peer, c);
        c.state = 'ringing';
        c.until = Date.now() + 35000;
        send(ws, { t: 'voice.ringing', callId: c.id, video: c.video });
        if (!c.suppressed) peers(c.peer, {
          t: 'voice.incoming', callId: c.id,
          conversationId: c.cid, name: identities.caller.name, video: c.video,
        });
      } catch {
        end(c, 'permission');
      }
      return;
    }

    if (!c || !live(c)) return;
    if (m.t === 'voice.connected') {
      if ((ws === c.a || ws === c.b) && c.b
        && (c.stage === 2 || c.stage === 'answer-pending')
        && c.connectedAt === undefined) c.connectedAt = Date.now();
      return;
    }
    if (m.t === 'voice.accept') {
      if (c.suppressed || c.state !== 'ringing' || c.b || ws.uid !== c.peer) return;
      c.b = ws;
      c.state = 'authorizing';
      c.expires = Math.min(c.expires, ws.authExpiresAt);
      peers(c.peer, { t: 'voice.dismiss', callId: c.id }, ws);
      try {
        await pair(c);
        if (!live(c) || c.state !== 'authorizing') return;
        c.state = 'connecting';
        c.trickle = c.callerTrickle && m.trickle === true;
        c.until = Date.now() + 60000;
        c.checkAt = Date.now() + 30000;
        send(c.a, { t: 'voice.accepted', callId: c.id, video: c.video, trickle: c.trickle });
        send(c.b, { t: 'voice.accepted', callId: c.id, video: c.video, trickle: c.trickle });
      } catch {
        end(c, 'permission');
      }
      return;
    }

    if (m.t === 'voice.ice') {
      if (!c.trickle || (c.state !== 'connecting' && c.state !== 'active')) return;
      const fromCaller = ws === c.a;
      if (!fromCaller && ws !== c.b) return;
      if (fromCaller ? c.stage === 0 : c.stage !== 'answer-pending' && c.stage !== 2) return;
      const count = fromCaller ? 'iceA' : 'iceB';
      if (c[count] >= 128) return;
      let candidate = null;
      if (m.candidate !== null) {
        const value = m.candidate;
        if (!value || typeof value.candidate !== 'string' || value.candidate.length > 2048
          || !/^candidate:[^\r\n]+ typ relay(?: |$)[^\r\n]*$/.test(value.candidate)
          || !(value.sdpMid == null || typeof value.sdpMid === 'string' && value.sdpMid.length <= 64)
          || !(value.sdpMLineIndex == null || Number.isInteger(value.sdpMLineIndex) && value.sdpMLineIndex >= 0 && value.sdpMLineIndex <= 16)
          || value.sdpMid == null && value.sdpMLineIndex == null) return;
        candidate = {candidate:value.candidate,sdpMid:value.sdpMid ?? null,sdpMLineIndex:value.sdpMLineIndex ?? null};
      }
      c[count]++;
      send(fromCaller ? c.b : c.a, {t:'voice.ice',callId:c.id,candidate});
      return;
    }

    const offer = m.t === 'voice.offer';
    const answer = m.t === 'voice.answer';
    if (!offer && !answer) return;
    if (c.state !== 'connecting'
      || (offer ? c.a !== ws || c.stage !== 0
        : c.b !== ws || c.stage !== 1)) return;
    // SDP có video dài hơn nhiều (nhiều codec + relay candidate) so với chỉ âm thanh
    if (typeof m.sdp !== 'string' || m.sdp.length > (c.video ? 40000 : 12000)
      || !m.sdp.startsWith('v=0')
      || !/^a=candidate:.* typ relay(?: |$)/m.test(m.sdp)) {
      return end(c, 'connection');
    }
    c.stage = offer ? 'offer-pending' : 'answer-pending';
    try {
      await pair(c);
      if (!live(c) || c.state !== 'connecting') return;
      c.stage = offer ? 1 : 2;
      if (answer) {
        c.state = 'active';
        c.until = c.expires;
      }
      send(offer ? c.b : c.a, {
        t: m.t, callId: c.id, sdp: m.sdp, video: c.video,
      });
    } catch {
      end(c, 'permission');
    }
  }

  function close(ws) {
    const c = busy.get(ws.uid);
    if (c && (c.a === ws || c.b === ws)) end(c, 'connection');
  }

  const timer = setInterval(() => {
    const now = Date.now();
    for (const [uid, until] of cooldown) {
      if (until <= now) cooldown.delete(uid);
    }
    for (const c of calls.values()) {
      if (!live(c) || c.until <= now) {
        end(c, c.state === 'ringing' ? 'unanswered' : 'expired');
        continue;
      }
      if (c.state === 'active' && !c.checking && c.checkAt <= now) {
        c.checking = true;
        pair(c).catch(() => end(c, 'permission')).finally(() => {
          c.checking = false;
          c.checkAt = Date.now() + 30000;
        });
      }
    }
  }, 5000);
  timer.unref();

  return {
    message, close,
    shutdown() {
      clearInterval(timer);
      for (const c of calls.values()) end(c, 'connection');
      cooldown.clear();
      return Promise.allSettled([...saving]);
    },
  };
}

module.exports = { createVoice };
