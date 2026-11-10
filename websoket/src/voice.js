'use strict';

function createVoice({ config, clients, send }) {
  const calls = new Map();
  const busy = new Map();
  const cooldown = new Map();
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

  function end(c, reason) {
    if (calls.get(c.id) !== c) return;
    calls.delete(c.id);
    for (const uid of [c.uid, c.peer]) {
      if (busy.get(uid) === c) busy.delete(uid);
    }
    const event = { t: 'voice.ended', callId: c.id, reason };
    if (open(c.a)) send(c.a, event);
    if (c.peer && !c.suppressed) peers(c.peer, event);
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
    const peer = await authorize(c.uid, c.cid);
    if (!live(c) || peer.id === c.uid
      || (c.peer && c.peer !== peer.id)) throw new Error('permission');
    const caller = await authorize(peer.id, c.cid);
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
        end(c, 'ended');
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
        send(ws, { t: 'voice.ringing', callId: c.id });
        if (!c.suppressed) peers(c.peer, {
          t: 'voice.incoming', callId: c.id,
          conversationId: c.cid, name: identities.caller.name,
        });
      } catch {
        end(c, 'permission');
      }
      return;
    }

    if (!c || !live(c)) return;
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
        c.until = Date.now() + 60000;
        c.checkAt = Date.now() + 30000;
        send(c.a, { t: 'voice.accepted', callId: c.id });
        send(c.b, { t: 'voice.accepted', callId: c.id });
      } catch {
        end(c, 'permission');
      }
      return;
    }

    const offer = m.t === 'voice.offer';
    const answer = m.t === 'voice.answer';
    if (!offer && !answer) return;
    if (c.state !== 'connecting'
      || (offer ? c.a !== ws || c.stage !== 0
        : c.b !== ws || c.stage !== 1)) return;
    if (typeof m.sdp !== 'string' || m.sdp.length > 12000
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
        t: m.t, callId: c.id, sdp: m.sdp,
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
    },
  };
}

module.exports = { createVoice };
