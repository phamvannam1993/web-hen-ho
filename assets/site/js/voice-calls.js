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
    var current = null;
    var dialog = document.createElement('dialog');
    dialog.className = 'voice-dialog';
    dialog.setAttribute('aria-labelledby', 'voice-title');
    dialog.innerHTML = '<h2 id="voice-title">Gọi thoại</h2>'
        + '<p class="voice-name"></p>'
        + '<p class="voice-status" role="status"></p>'
        + '<div class="voice-actions">'
        + '<button type="button" data-accept hidden>Nhận cuộc gọi</button>'
        + '<button type="button" data-mute hidden>Tắt mic</button>'
        + '<button type="button" data-end>Đóng</button></div>'
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
            b.title = supported ? 'Gọi thoại' : 'Cần HTTPS và trình duyệt hỗ trợ gọi thoại';
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
        end.textContent = 'Đóng';
        status.textContent = text;
        refresh();
    }
    function fail(s, error) {
        finish(s, error.name === 'NotAllowedError'
            ? 'Hãy cho phép dùng microphone để gọi thoại.'
            : (error.message || reasons.connection), true);
    }
    function show(s, text) {
        current = s;
        name.textContent = s.name || 'Cuộc gọi thoại';
        status.textContent = text;
        accept.hidden = !s.incoming;
        mute.hidden = play.hidden = true;
        mute.textContent = 'Tắt mic';
        end.textContent = s.incoming ? 'Từ chối' : 'Hủy gọi';
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
        name.textContent = s.name;
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
                end.textContent = 'Kết thúc';
                if (!s.clock) {
                    s.startedAt = Date.now();
                    s.clock = setInterval(function () {
                        var seconds = Math.floor((Date.now() - s.startedAt) / 1000);
                        status.textContent = 'Đang gọi · '
                            + Math.floor(seconds / 60) + ':'
                            + String(seconds % 60).padStart(2, '0');
                    }, 1000);
                }
                status.textContent = 'Đã kết nối';
            } else if (value === 'failed' || value === 'closed') {
                finish(s, reasons.connection, true);
            } else if (value === 'disconnected' && !s.disconnect) {
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
            status.textContent = 'Đang gọi…';
            send(s, 'voice.invite', { conversationId: s.cid });
        }).catch(function (error) { fail(s, error); });
    });
    accept.onclick = function () {
        var s = current;
        if (!s || !s.incoming || s.accepting) return;
        s.accepting = true;
        accept.hidden = true;
        end.textContent = 'Hủy gọi';
        status.textContent = 'Đang chuẩn bị microphone…';
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
        mute.textContent = current.stream.getAudioTracks()[0].enabled ? 'Tắt mic' : 'Bật mic';
    };
    play.onclick = function () {
        audio.play().then(function () { play.hidden = true; }).catch(function () {});
    };
    rt.on('voice.incoming', function (m) {
        if (!supported || current) return;
        show({
            id: m.callId, cid: m.conversationId,
            name: m.name, incoming: true,
        }, 'Cuộc gọi thoại đến');
    });
    rt.on('voice.ringing', function (m) {
        if (current && current.id === m.callId) status.textContent = 'Đang đổ chuông…';
    });
    rt.on('voice.accepted', function (m) {
        var s = current;
        if (!s || s.id !== m.callId || s.accepted) return;
        s.accepted = true;
        clearTimeout(s.timer);
        s.timer = setTimeout(function () {
            finish(s, reasons.connection, true);
        }, 60000);
        status.textContent = 'Đang kết nối thoại…';
        if (!s.incoming) description(s, 'offer').catch(function (e) { fail(s, e); });
    });
    ['offer', 'answer'].forEach(function (type) {
        rt.on('voice.' + type, function (m) {
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
        if (current) finish(current, reasons.connection, false);
        refresh();
    });
    window.addEventListener('pagehide', function () {
        if (current) finish(current, reasons.ended, true);
    });
    refresh();
})();
