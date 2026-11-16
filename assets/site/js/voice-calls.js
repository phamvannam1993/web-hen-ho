(function () {
    'use strict';
    var root = document.getElementById('chat-widget');
    var rt = window.Realtime;
    if (!root || root.dataset.guest === '1' || !rt) return;
    var supported = window.isSecureContext && window.RTCPeerConnection
        && navigator.mediaDevices && navigator.mediaDevices.getUserMedia
        && window.crypto && window.crypto.randomUUID
        && window.HTMLDialogElement;
    var base = root.dataset.base.replace(/\/?$/, '/');
    var current = null, incomingGeneration = 0, pendingIncoming = null;
    var dialog = document.createElement('dialog');
    dialog.className = 'voice-dialog';
    dialog.setAttribute('aria-labelledby', 'voice-title');
    var phoneIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 5.2 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.6a2 2 0 0 1-.5 2.1L9 10.7a16 16 0 0 0 4.3 4.3l1.3-1.3a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2z"/></svg>';
    var micIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3M8 22h8"/><path class="voice-mic-slash" d="m3 3 18 18"/></svg>';
    dialog.innerHTML = '<div class="voice-heading"><span class="voice-brand">Saigon Cupid</span><h2 id="voice-title">Gọi thoại</h2></div>'
        + '<div class="voice-avatar" aria-hidden="true"><span>SC</span></div>'
        + '<p class="voice-name"></p>'
        + '<p class="voice-status" role="status" aria-live="polite"></p>'
        + '<p class="voice-duration" aria-label="Thời lượng cuộc gọi" aria-live="off" hidden>00:00</p>'
        + '<div class="voice-actions">'
        + '<button type="button" data-accept hidden>' + phoneIcon + '<span>Nhận cuộc gọi</span></button>'
        + '<button type="button" data-mute aria-pressed="false" hidden>' + micIcon + '<span>Tắt mic</span></button>'
        + '<button type="button" data-end>' + phoneIcon + '<span>Đóng</span></button></div>'
        + '<audio autoplay playsinline></audio>'
        + '<button type="button" data-play hidden>Bật âm thanh</button>';
    document.body.appendChild(dialog);
    var name = dialog.querySelector('.voice-name');
    var status = dialog.querySelector('.voice-status');
    var accept = dialog.querySelector('[data-accept]');
    var mute = dialog.querySelector('[data-mute]');
    var end = dialog.querySelector('[data-end]');
    var play = dialog.querySelector('[data-play]');
    var audio = dialog.querySelector('audio');
    var duration = dialog.querySelector('.voice-duration');
    function label(button, text) { button.querySelector('span').textContent = text; }
    function peer(value) {
        name.textContent = value || 'Cuộc gọi thoại';
        var words = name.textContent.trim().split(/\s+/);
        dialog.querySelector('.voice-avatar span').textContent = words.slice(-2).map(function (word) { return Array.from(word)[0] || ''; }).join('').toLocaleUpperCase('vi');
    }
    function phase(value, text) { dialog.dataset.state = value; status.textContent = text; }

    var reasons = {
        busy: 'Người dùng đang bận hoặc bạn gọi quá nhanh.',
        offline: 'Người dùng hiện chưa kết nối.',
        permission: 'Hiện không thể gọi người dùng này.',
        unanswered: 'Không có người trả lời.',
        expired: 'Cuộc gọi hết thời gian hoặc phiên đăng nhập đã hết hạn.',
        connection: 'Không thể duy trì kết nối thoại.',
        ended: 'Cuộc gọi đã kết thúc.',
    };

    function refresh() {
        document.querySelectorAll('[data-voice-call]').forEach(function (b) {
            b.disabled = !supported || !rt.connected;
            b.title = !supported ? 'Cần HTTPS hoặc localhost và trình duyệt hỗ trợ gọi thoại'
                : (!rt.connected ? 'Chưa kết nối máy chủ gọi thoại' : 'Gọi thoại');
        });
    }
    function send(s, type, extra) {
        if (!rt.sendDirect(Object.assign({
            t: type, callId: s.id,
        }, extra || {}))) throw new Error('Mất kết nối máy chủ.');
    }
    function finish(s, text, notify) {
        if (current !== s) return;
        current = null;
        if (notify) {
            try { send(s, 'voice.end'); } catch (e) {}
        }
        clearTimeout(s.timer);
        clearTimeout(s.disconnect);
        clearInterval(s.clock);
        if (s.cancelGather) s.cancelGather();
        if (s.pc) {
            s.pc.onconnectionstatechange = null;
            s.pc.ontrack = null;
            s.pc.close();
        }
        if (s.stream) s.stream.getTracks().forEach(function (t) { t.stop(); });
        audio.srcObject = null;
        accept.hidden = mute.hidden = play.hidden = true;
        label(end, 'Đóng');
        phase('ended', text);
        dialog.querySelector('#voice-title').textContent = 'Cuộc gọi kết thúc';
        refresh();
    }
    function fail(s, error) {
        finish(s, error.name === 'NotAllowedError'
            ? 'Hãy cho phép dùng microphone để gọi thoại.'
            : (error.message || reasons.connection), true);
    }
    function show(s, text) {
        current = s;
        peer(s.name);
        phase(s.incoming ? 'incoming' : 'preparing', text);
        dialog.querySelector('#voice-title').textContent = s.incoming ? 'Cuộc gọi đến' : 'Gọi thoại';
        duration.hidden = true; duration.textContent = '00:00';
        accept.hidden = !s.incoming;
        mute.hidden = play.hidden = true;
        label(mute, 'Tắt mic');
        mute.setAttribute('aria-pressed', 'false');
        label(end, s.incoming ? 'Từ chối' : 'Huỷ gọi');
        if (!dialog.open) dialog.showModal();
        s.timer = setTimeout(function () {
            finish(s, reasons.unanswered, true);
        }, 45000);
    }
    async function prepare(s) {
        var abort = new AbortController();
        var timer = setTimeout(function () { abort.abort(); }, 10000);
        var response;
        try {
            response = await fetch(base + 'ajax/voice-config/' + s.cid, {
                credentials: 'same-origin', cache: 'no-store',
                signal: abort.signal,
            });
        } finally {
            clearTimeout(timer);
        }
        if (!response.ok) throw new Error('Chưa thể gọi: hãy kiểm tra quyền gọi và cấu hình TURN.');
        var data = await response.json();
        if (current !== s) return false;
        if (!data.ok || !data.rtc || !data.rtc.enabled
            || data.rtc.iceTransportPolicy !== 'relay'
            || !data.rtc.iceServers || !data.rtc.iceServers.length
            || data.rtc.expiresAt * 1000 < Date.now() + 60000) {
            throw new Error('Cấu hình gọi thoại chưa sẵn sàng.');
        }
        s.name = data.peer.name;
        peer(s.name);
        var stream = await navigator.mediaDevices.getUserMedia({
            audio: { echoCancellation: true, noiseSuppression: true },
            video: false,
        });
        if (current !== s) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            return false;
        }
        s.stream = stream;
        s.pc = new RTCPeerConnection({
            iceServers: data.rtc.iceServers, iceTransportPolicy: 'relay',
        });
        stream.getTracks().forEach(function (t) { s.pc.addTrack(t, stream); });
        s.pc.ontrack = function (event) {
            if (current !== s) return;
            audio.srcObject = event.streams[0] || new MediaStream([event.track]);
            audio.play().catch(function () {
                if (current === s) play.hidden = false;
            });
        };
        s.pc.onconnectionstatechange = function () {
            if (current !== s) return;
            var value = s.pc.connectionState;
            if (value === 'connected') {
                clearTimeout(s.timer);
                clearTimeout(s.disconnect);
                s.disconnect = null;
                label(end, 'Kết thúc');
                duration.hidden = false;
                if (!s.clock) {
                    s.startedAt = Date.now();
                    s.clock = setInterval(function () {
                        var seconds = Math.floor((Date.now() - s.startedAt) / 1000);
                        duration.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':'
                            + String(seconds % 60).padStart(2, '0');
                    }, 1000);
                }
                phase('connected', 'Đang trong cuộc gọi');
            } else if (value === 'failed' || value === 'closed') {
                finish(s, reasons.connection, true);
            } else if (value === 'disconnected' && !s.disconnect) {
                phase('connecting', 'Kết nối gián đoạn, đang thử lại…');
                s.disconnect = setTimeout(function () {
                    finish(s, reasons.connection, true);
                }, 5000);
            }
        };
        mute.hidden = false;
        return true;
    }
    async function description(s, type) {
        var pc = s.pc;
        var value = type === 'offer' ? await pc.createOffer() : await pc.createAnswer();
        if (current !== s) return;
        await pc.setLocalDescription(value);
        await new Promise(function (resolve, reject) {
            var timer;
            function done(error) {
                clearTimeout(timer);
                pc.removeEventListener('icegatheringstatechange', changed);
                s.cancelGather = null;
                if (error) reject(error); else resolve();
            }
            function changed() {
                if (pc.iceGatheringState === 'complete') done();
            }
            s.cancelGather = function () { done(new Error('Cuộc gọi đã hủy.')); };
            pc.addEventListener('icegatheringstatechange', changed);
            timer = setTimeout(function () {
                done(new Error('Không kết nối được TURN. Hãy thử lại.'));
            }, 15000);
            changed();
        });
        if (current !== s) return;
        var sdp = pc.localDescription.sdp;
        if (!/^a=candidate:.* typ relay(?: |$)/m.test(sdp)) {
            throw new Error('TURN chưa cung cấp được kết nối thoại.');
        }
        send(s, 'voice.' + type, { sdp: sdp });
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-voice-call]');
        if (!button || !supported || !rt.connected || !button.dataset.voiceCall) return;
        if (current) {
            if (!dialog.open) dialog.showModal();
            return;
        }
        var s = {
            id: crypto.randomUUID(), cid: Number(button.dataset.voiceCall),
            incoming: false,
        };
        show(s, 'Đang chuẩn bị microphone…');
        prepare(s).then(function (ready) {
            if (!ready || current !== s) return;
            phase('ringing', 'Đang gọi…');
            send(s, 'voice.invite', { conversationId: s.cid });
        }).catch(function (error) { fail(s, error); });
    });
    accept.onclick = function () {
        var s = current;
        if (!s || !s.incoming || s.accepting) return;
        s.accepting = true;
        accept.hidden = true;
        label(end, 'Huỷ gọi');
        phase('preparing', 'Đang chuẩn bị microphone…');
        prepare(s).then(function (ready) {
            if (ready && current === s) send(s, 'voice.accept');
        }).catch(function (error) { fail(s, error); });
    };
    end.onclick = function () {
        if (current) finish(current, reasons.ended, true);
        else dialog.close();
    };
    dialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        end.click();
    });
    mute.onclick = function () {
        if (!current || !current.stream) return;
        current.stream.getAudioTracks().forEach(function (t) { t.enabled = !t.enabled; });
        var muted = !current.stream.getAudioTracks()[0].enabled;
        label(mute, muted ? 'Bật mic' : 'Tắt mic');
        mute.setAttribute('aria-pressed', muted ? 'true' : 'false');
    };
    play.onclick = function () {
        audio.play().then(function () { play.hidden = true; }).catch(function () {});
    };
    rt.on('voice.incoming', async function (m) {
        if (!supported || current) return;
        var generation=++incomingGeneration; pendingIncoming=m.callId;
        try {
            var response=await fetch(base+'ajax/tin-nhan/'+Number(m.conversationId)+'/thong-bao',{credentials:'same-origin',cache:'no-store'});
            if (!response.ok) return;
            var state=await response.json();
            if (!state.ok || state.calls_notifications_enabled === false || current || generation !== incomingGeneration) return;
        } catch (_) { return; }
        if (generation !== incomingGeneration) return;
        pendingIncoming=null;
        show({
            id: m.callId, cid: m.conversationId,
            name: m.name, incoming: true,
        }, 'Cuộc gọi thoại đến');
    });
    rt.on('voice.ringing', function (m) {
        if (current && current.id === m.callId) phase('ringing', 'Đang đổ chuông…');
    });
    rt.on('voice.accepted', function (m) {
        var s = current;
        if (!s || s.id !== m.callId || s.accepted) return;
        s.accepted = true;
        clearTimeout(s.timer);
        s.timer = setTimeout(function () {
            finish(s, reasons.connection, true);
        }, 60000);
        phase('connecting', 'Đang kết nối thoại…');
        if (!s.incoming) description(s, 'offer').catch(function (e) { fail(s, e); });
    });
    ['offer', 'answer'].forEach(function (type) {
        rt.on('voice.' + type, function (m) {
            if (pendingIncoming === m.callId) { incomingGeneration++; pendingIncoming=null; }
            var s = current;
            if (!s || s.id !== m.callId || !s.accepted || !s.pc
                || s.remoteSet || (type === 'offer') !== s.incoming) return;
            s.remoteSet = true;
            s.pc.setRemoteDescription({ type: type, sdp: m.sdp })
                .then(function () {
                    if (current === s && type === 'offer') return description(s, 'answer');
                }).catch(function (e) { fail(s, e); });
        });
    });
    ['ended', 'error', 'dismiss'].forEach(function (type) {
        rt.on('voice.' + type, function (m) {
            if (current && current.id === m.callId) {
                finish(current, type === 'dismiss'
                    ? 'Cuộc gọi được nhận ở tab khác.'
                    : (reasons[m.reason] || reasons.ended), false);
            }
        });
    });
    rt.on('open', refresh);
    rt.on('close', function () {
        incomingGeneration++; pendingIncoming=null;
        if (current) finish(current, reasons.connection, false);
        refresh();
    });
    window.addEventListener('pagehide', function () {
        incomingGeneration++; pendingIncoming=null;
        if (current) finish(current, reasons.ended, true);
    });
    refresh();
})();
