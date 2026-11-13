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
    function createCallSounds(soundBase) {
        if (!window.Audio) return {play:function () {},stop:function () {},unlock:function () {}};
        var sounds = {}, active = null, revision = 0, unlocked = false;
        var files = {incoming:'goi-den.mp3',outgoing:'goi-di.mp3',ended:'end-call.mp3'};
        Object.keys(files).forEach(function (kind) {
            var sound = new window.Audio(soundBase + files[kind]);
            sound.preload = 'auto'; sound.loop = kind !== 'ended'; sound.volume = .65;
            sound.onended = function () { if (active === kind) active = null; };
            sounds[kind] = sound;
        });
        function stop() {
            revision++; active = null;
            Object.keys(sounds).forEach(function (kind) {
                sounds[kind].pause();
                try { sounds[kind].currentTime = 0; } catch (_) {}
            });
        }
        function attempt(kind) {
            var version = revision;
            try {
                sounds[kind].muted = false;
                var result = sounds[kind].play();
                if (result && result.catch) result.catch(function () {
                    // Chỉ thử lại chuông đang đổ ở lần tương tác tiếp theo.
                    if (revision === version && kind === 'ended') active = null;
                });
            } catch (_) { if (kind === 'ended') active = null; }
        }
        function play(kind) {
            stop();
            if (!sounds[kind]) return;
            active = kind; attempt(kind);
        }
        function unlock() {
            if (active) { if (sounds[active].paused) attempt(active); return; }
            if (unlocked) return;
            unlocked = true;
            var version = revision;
            Object.keys(sounds).forEach(function (kind) {
                var sound = sounds[kind]; sound.muted = true;
                try {
                    Promise.resolve(sound.play()).then(function () {
                        // Không để lần mở khóa đến muộn dừng chuông cuộc gọi mới.
                        if (revision === version && !active) {
                            sound.pause();
                            try { sound.currentTime = 0; } catch (_) {}
                        }
                        sound.muted = false;
                    }).catch(function () { sound.muted = false; unlocked = false; });
                } catch (_) { sound.muted = false; unlocked = false; }
            });
        }
        return {play:play,stop:stop,unlock:unlock};
    }
    var callSounds = createCallSounds(document.currentScript && document.currentScript.src
        ? new URL('../audio/', document.currentScript.src).href : base + 'assets/site/audio/');
    document.addEventListener('pointerdown', callSounds.unlock, {passive:true});
    document.addEventListener('keydown', callSounds.unlock);
    var current = null, incomingGeneration = 0, pendingIncoming = null;
    var dialog = document.createElement('dialog');
    dialog.className = 'voice-dialog';
    dialog.setAttribute('aria-labelledby', 'voice-title');
    dialog.innerHTML = '<h2 id="voice-title">Gọi thoại</h2>'
        // Gọi video: hình người kia chiếm khung, hình mình ở góc (tắt tiếng để không vọng)
        + '<div class="voice-video" hidden><video class="voice-remote" autoplay playsinline></video>'
        + '<video class="voice-local" autoplay playsinline muted></video></div>'
        + '<p class="voice-name"></p>'
        + '<p class="voice-status" role="status"></p>'
        + '<div class="voice-actions">'
        + '<button type="button" data-accept hidden>Nhận cuộc gọi</button>'
        + '<button type="button" data-mute hidden>Tắt mic</button>'
        + '<button type="button" data-output hidden aria-expanded="false" aria-controls="voice-output-panel">Loa / tai nghe</button>'
        + '<button type="button" data-camera hidden>Tắt camera</button>'
        + '<button type="button" data-flip hidden>Đổi camera</button>'
        + '<button type="button" data-end>Đóng</button></div>'
        + '<audio autoplay playsinline></audio>'
        + '<button type="button" data-play hidden>Bật âm thanh</button>';
    var outputPanel = document.createElement('div'); outputPanel.className = 'voice-output';
    outputPanel.id = 'voice-output-panel'; outputPanel.hidden = true;
    var outputLabel = document.createElement('label'); outputLabel.htmlFor = 'voice-output-select'; outputLabel.textContent = 'Chọn loa hoặc tai nghe';
    var outputSelect = document.createElement('select'); outputSelect.id = 'voice-output-select';
    outputPanel.append(outputLabel,outputSelect); dialog.appendChild(outputPanel);
    var feedback = document.createElement('p'); feedback.className = 'voice-feedback'; feedback.setAttribute('role','status'); feedback.hidden = true;
    dialog.appendChild(feedback);
    document.body.appendChild(dialog);
    var name = dialog.querySelector('.voice-name');
    var status = dialog.querySelector('.voice-status');
    var accept = dialog.querySelector('[data-accept]');
    var mute = dialog.querySelector('[data-mute]');
    var end = dialog.querySelector('[data-end]');
    var play = dialog.querySelector('[data-play]');
    var audio = dialog.querySelector('audio');
    var title = dialog.querySelector('#voice-title');
    var videoBox = dialog.querySelector('.voice-video');
    var remoteVideo = dialog.querySelector('.voice-remote');
    var localVideo = dialog.querySelector('.voice-local');
    var camera = dialog.querySelector('[data-camera]');
    var flip = dialog.querySelector('[data-flip]');
    var output = dialog.querySelector('[data-output]');
    var outputTask = Promise.resolve();
    var controlIcons = {
        accept: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
        end: '<path d="M4 16v-4m16 4v-4M2 12a16 16 0 0 1 20 0l-2 5-5-2v-3a13 13 0 0 0-6 0v3l-5 2z"/>',
        close: '<path d="m6 6 12 12M6 18 18 6"/>',
        mic: '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3m-4 0h8"/>',
        micOff: '<path d="m3 3 18 18M9 9v3a3 3 0 0 0 5.1 2.1M15 9V5a3 3 0 0 0-5.1-2.1M5 10v2a7 7 0 0 0 12 4.9M19 10v2M12 19v3m-4 0h8"/>',
        camera: '<rect x="3" y="5" width="12" height="14" rx="2"/><path d="m15 10 6-4v12l-6-4"/>',
        cameraOff: '<path d="m3 3 18 18M10 5h3a2 2 0 0 1 2 2v3l6-4v12M15 15v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7"/>',
        flip: '<path d="M3 9a9 9 0 0 1 15-5l3 3M21 3v4h-4M21 15a9 9 0 0 1-15 5l-3-3M3 21v-4h4"/>',
        sound: '<path d="m11 5-6 4H2v6h3l6 4zM15 8a6 6 0 0 1 0 8M18 5a10 10 0 0 1 0 14"/>',
    };
    function setControl(button, label, icon, pressed) {
        button.title = label;
        button.setAttribute('aria-label', label);
        button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            + controlIcons[icon] + '</svg>';
        var text = document.createElement('span'); text.className = 'voice-control-label'; text.textContent = label;
        button.appendChild(text);
        if (typeof pressed === 'boolean') button.setAttribute('aria-pressed', String(pressed));
    }
    setControl(accept, 'Nhận cuộc gọi', 'accept');
    setControl(mute, 'Tắt mic', 'mic', false);
    setControl(camera, 'Tắt camera', 'camera', false);
    setControl(flip, 'Đổi camera', 'flip');
    setControl(end, 'Đóng', 'close');
    setControl(play, 'Bật âm thanh', 'sound');
    setControl(output, 'Loa / tai nghe', 'sound');
    function inform(text) { feedback.textContent = text; feedback.hidden = !text; }
    function routeOutput(s, deviceId) {
        var player = s.video ? remoteVideo : audio;
        var change = outputTask.then(function () {
            if (current !== s) return false;
            return player.setSinkId(deviceId).then(function () { return current === s; });
        });
        // Tuần tự hóa để thao tác từ cuộc gọi cũ không đổi loa của cuộc gọi mới.
        outputTask = change.catch(function () {});
        return change;
    }
    function outputError(error) {
        if (error.name === 'NotAllowedError') return 'Chưa được phép đổi thiết bị phát âm thanh. Hãy cho phép chọn loa trong cài đặt trình duyệt.';
        if (error.name === 'NotFoundError') return 'Không tìm thấy loa hoặc tai nghe đã chọn. Hãy kết nối lại thiết bị rồi thử lại.';
        return 'Không đổi được loa hoặc tai nghe. Âm thanh vẫn dùng thiết bị hiện tại; hãy kiểm tra cài đặt âm thanh của máy.';
    }
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
                : (!rt.connected ? 'Chưa kết nối máy chủ gọi thoại'
                : (b.dataset.video === '1' ? 'Gọi video' : 'Gọi thoại'));
        });
    }
    function send(s, type, extra) {
        if (!rt.sendDirect(Object.assign({
            t: type, callId: s.id,
        }, extra || {}))) throw new Error('Mất kết nối máy chủ.');
    }
    function finish(s, text, notify, silent, endReason) {
        if (current !== s) return;
        console.info('[voice] call ended', {
            callId:s.id, reason:endReason || (notify ? 'local-end' : 'remote-end'),
            elapsedSeconds:s.startedAt ? Math.floor((Date.now()-s.startedAt)/1000) : null,
            connectionState:s.pc ? s.pc.connectionState : null,
            iceConnectionState:s.pc ? s.pc.iceConnectionState : null,
        });
        current = null;
        callSounds.stop();
        if (!silent) callSounds.play('ended');
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
        remoteVideo.srcObject = localVideo.srcObject = null;
        videoBox.hidden = true;
        dialog.classList.remove('is-video');
        accept.hidden = mute.hidden = play.hidden = camera.hidden = flip.hidden = true;
        output.hidden = outputPanel.hidden = true; output.setAttribute('aria-expanded','false'); inform('');
        setControl(end, 'Đóng', 'close');
        status.textContent = text;
        refresh();
    }
    function mediaErrorMessage(error, device) {
        if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
            return 'Chưa được phép sử dụng ' + device + '. Hãy mở quyền của trang web cạnh thanh địa chỉ, cho phép dùng '
                + device + ' rồi gọi lại. Nếu vẫn bị chặn, hãy kiểm tra quyền riêng tư trên thiết bị.';
        }
        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            return 'Không tìm thấy ' + device + '. Hãy kết nối hoặc bật ' + device
                + ' trên thiết bị rồi gọi lại.' + (device === 'camera' ? ' Bạn vẫn có thể gọi thoại.' : '');
        }
        if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            return 'Không mở được ' + device + '. Thiết bị có thể đang được ứng dụng khác sử dụng hoặc gặp lỗi. '
                + 'Hãy đóng ứng dụng đang dùng ' + device + ' và kiểm tra thiết bị rồi gọi lại.';
        }
        if (error.name === 'OverconstrainedError') {
            return device === 'camera'
                ? 'Camera hiện tại không đáp ứng chế độ quay được yêu cầu. Hãy chọn camera khác trong cài đặt trình duyệt rồi gọi lại.'
                : 'Micro hiện tại không đáp ứng chế độ thu âm được yêu cầu. Hãy chọn micro khác trong cài đặt trình duyệt rồi gọi lại.';
        }
        if (error.name === 'AbortError') {
            return 'Việc mở ' + device + ' bị gián đoạn. Hãy kiểm tra kết nối thiết bị rồi gọi lại.';
        }
        return null;
    }
    async function callMedia(video) {
        var stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: true }, video: false,
            });
        } catch (error) {
            throw new Error(mediaErrorMessage(error, 'micro') || 'Không thể mở micro. Hãy kiểm tra thiết bị rồi gọi lại.');
        }
        if (!video) return stream;
        try {
            // Mở riêng từng thiết bị để biết chính xác camera hay micro gặp lỗi.
            var cameraStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            });
            cameraStream.getVideoTracks().forEach(function (track) { stream.addTrack(track); });
        } catch (error) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            throw new Error(mediaErrorMessage(error, 'camera') || 'Không thể mở camera. Hãy kiểm tra thiết bị rồi gọi lại.');
        }
        return stream;
    }
    function fail(s, error) {
        finish(s, error.message || reasons.connection, true);
    }
    function show(s, text) {
        callSounds.stop();
        current = s;
        output.hidden = outputPanel.hidden = true;
        output.disabled = camera.disabled = flip.disabled = outputSelect.disabled = false;
        output.setAttribute('aria-expanded','false'); inform('');
        setControl(output, 'Loa / tai nghe', 'sound');
        var player = s.video ? remoteVideo : audio;
        if (typeof player.setSinkId === 'function') routeOutput(s,'').catch(function () {});
        title.textContent = s.video ? 'Gọi video' : 'Gọi thoại';
        dialog.classList.toggle('is-video', !!s.video);
        name.textContent = s.name || (s.video ? 'Cuộc gọi video' : 'Cuộc gọi thoại');
        status.textContent = text;
        accept.hidden = !s.incoming;
        mute.hidden = play.hidden = true;
        setControl(mute, 'Tắt mic', 'mic', false);
        setControl(end, s.incoming ? 'Từ chối' : 'Hủy gọi', 'end');
        if (!dialog.open) dialog.showModal();
        if (s.incoming) callSounds.play('incoming');
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
        // Lấy sẵn relay trong lúc người dùng cấp quyền/mở thiết bị.
        s.pc = new RTCPeerConnection({
            iceServers: data.rtc.iceServers, iceTransportPolicy: 'relay',
            iceCandidatePoolSize: 1, bundlePolicy: 'max-bundle',
        });
        var stream = await callMedia(s.video);
        if (current !== s) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            return false;
        }
        s.stream = stream;
        if (s.video) {
            s.facing = 'user';
            s.cameraEnabled = true;
            localVideo.srcObject = stream;
            videoBox.hidden = false;
            camera.hidden = false;
            setControl(camera, 'Tắt camera', 'camera', false);
            localVideo.classList.remove('is-off');
            // Nút đổi camera chỉ khi máy có từ hai camera (điện thoại)
            navigator.mediaDevices.enumerateDevices().then(function (list) {
                if (current === s) {
                    s.cameraCount = list.filter(function (d) { return d.kind === 'videoinput'; }).length;
                    flip.hidden = !s.cameraEnabled || s.cameraCount < 2;
                }
            }).catch(function () {});
        }
        stream.getTracks().forEach(function (t) {
            var sender = s.pc.addTrack(t, stream);
            if (t.kind === 'video') s.videoSender = sender;
        });
        s.pc.ontrack = function (event) {
            if (current !== s) return;
            var remote = event.streams[0] || new MediaStream([event.track]);
            // Gọi video: thẻ video phát cả tiếng; thoại: thẻ audio như cũ
            var player = s.video ? remoteVideo : audio;
            if (player.srcObject !== remote) player.srcObject = remote;
            player.play().catch(function () {
                if (current === s) play.hidden = false;
            });
        };
        s.pc.onconnectionstatechange = function () {
            if (current !== s) return;
            var value = s.pc.connectionState;
            if (value === 'connected') {
                callSounds.stop();
                clearTimeout(s.timer);
                clearTimeout(s.disconnect);
                s.disconnect = null;
                setControl(end, 'Kết thúc', 'end');
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
                finish(s, reasons.connection, true, false, 'webrtc-' + value);
            } else if (value === 'disconnected' && !s.disconnect) {
                s.disconnect = setTimeout(function () {
                    finish(s, reasons.connection, true, false, 'webrtc-disconnected');
                }, 5000);
            }
        };
        mute.hidden = false;
        output.hidden = false;
        return true;
    }
    async function gatherDescription(s, type) {
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
                // Một giao diện mạng có thể còn chờ dù giao diện khác đã lấy được relay.
                var hasRelay = pc.localDescription
                    && /^a=candidate:.* typ relay(?: |$)/m.test(pc.localDescription.sdp);
                done(hasRelay ? null : new Error('Không lấy được kết nối TURN. Hãy kiểm tra máy chủ TURN và mạng.'));
            }, 15000);
            changed();
        });
        if (current !== s) return;
        var sdp = pc.localDescription.sdp;
        if (!/^a=candidate:.* typ relay(?: |$)/m.test(sdp)) {
            throw new Error('TURN chưa cung cấp được kết nối ' + (s.video ? 'video.' : 'thoại.'));
        }
        return sdp;
    }
    function warmOffer(s) {
        // Chuẩn bị khi đổ chuông; chỉ gửi SDP sau khi bên kia nhận cuộc gọi.
        if (!s.offerReady) {
            s.offerReady = gatherDescription(s, 'offer');
            s.offerReady.catch(function (error) { if (current === s) fail(s, error); });
        }
        return s.offerReady;
    }
    async function description(s, type) {
        var sdp = await (type === 'offer' ? warmOffer(s) : gatherDescription(s, type));
        if (current !== s || !sdp) return;
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
            incoming: false, video: button.dataset.video === '1',
        };
        show(s, s.video ? 'Đang chuẩn bị camera và microphone…' : 'Đang chuẩn bị microphone…');
        prepare(s).then(function (ready) {
            if (!ready || current !== s) return;
            status.textContent = 'Đang gọi…';
            warmOffer(s);
            send(s, 'voice.invite', { conversationId: s.cid, video: !!s.video });
            callSounds.play('outgoing');
        }).catch(function (error) { fail(s, error); });
    });
    accept.onclick = function () {
        var s = current;
        if (!s || !s.incoming || s.accepting) return;
        s.accepting = true;
        callSounds.stop();
        accept.hidden = true;
        setControl(end, 'Hủy gọi', 'end');
        status.textContent = s.video ? 'Đang chuẩn bị camera và microphone…' : 'Đang chuẩn bị microphone…';
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
        var enabled = current.stream.getAudioTracks()[0].enabled;
        setControl(mute, enabled ? 'Tắt mic' : 'Bật mic', enabled ? 'mic' : 'micOff', !enabled);
    };
    play.onclick = function () {
        (current && current.video ? remoteVideo : audio).play()
            .then(function () { play.hidden = true; }).catch(function () {});
    };
    output.onclick = async function () {
        var s = current;
        if (!s || !s.stream || s.outputBusy) return;
        inform('');
        if (!outputPanel.hidden) {
            outputPanel.hidden = true; output.setAttribute('aria-expanded','false'); return;
        }
        var player = s.video ? remoteVideo : audio;
        if (typeof player.setSinkId !== 'function') {
            inform('Trình duyệt này không hỗ trợ chuyển loa ngoài trong trang web. Hãy chọn loa ngoài, loa thoại hoặc tai nghe trong bảng điều khiển âm thanh của thiết bị.');
            return;
        }
        s.outputBusy = true; output.disabled = true;
        try {
            if (typeof navigator.mediaDevices.selectAudioOutput === 'function') {
                var selected = await navigator.mediaDevices.selectAudioOutput();
                if (current !== s) return;
                if (await routeOutput(s,selected.deviceId)) {
                    setControl(output,'Đầu ra: ' + (selected.label || 'Thiết bị đã chọn'),'sound');
                    inform('Đang phát qua: ' + (selected.label || 'thiết bị đã chọn') + '.');
                }
            } else {
                var devices = await navigator.mediaDevices.enumerateDevices();
                if (current !== s) return;
                outputSelect.replaceChildren();
                var initial = document.createElement('option'); initial.value = ''; initial.textContent = 'Theo cài đặt âm thanh của thiết bị'; outputSelect.append(initial);
                devices.filter(function (d) { return d.kind === 'audiooutput' && d.deviceId; }).forEach(function (device, index) {
                    var option = document.createElement('option'); option.value = device.deviceId;
                    option.textContent = device.label || 'Thiết bị âm thanh ' + (index + 1); outputSelect.append(option);
                });
                if (outputSelect.options.length === 1) {
                    inform('Trình duyệt chưa cung cấp danh sách loa hoặc tai nghe. Hãy chọn đầu ra trong cài đặt âm thanh của thiết bị.');
                    return;
                }
                outputSelect.value = player.sinkId || '';
                outputPanel.hidden = false; output.setAttribute('aria-expanded','true'); outputSelect.focus();
            }
        } catch (error) { if (current === s) inform(outputError(error)); }
        finally { s.outputBusy = false; if (current === s) output.disabled = false; }
    };
    outputSelect.onchange = async function () {
        var s = current;
        if (!s || s.outputBusy) return;
        s.outputBusy = true; outputSelect.disabled = output.disabled = true; inform('');
        try {
            var selected = outputSelect.options[outputSelect.selectedIndex];
            if (await routeOutput(s,outputSelect.value)) {
                setControl(output,'Đầu ra: ' + selected.textContent,'sound');
                inform('Đang phát qua: ' + selected.textContent + '.');
                outputPanel.hidden = true; output.setAttribute('aria-expanded','false'); output.focus();
            }
        } catch (error) { if (current === s) inform(outputError(error)); }
        finally { s.outputBusy = false; if (current === s) outputSelect.disabled = output.disabled = false; }
    };
    async function changeCamera(s, enabled) {
        var sender = s.videoSender;
        if (!sender) throw new Error('Camera chưa sẵn sàng. Hãy thử lại sau khi cuộc gọi kết nối.');
        var fresh = null, adopted = false;
        try {
            if (enabled) {
                fresh = await navigator.mediaDevices.getUserMedia({audio:false,
                    video:{facingMode:s.facing || 'user',width:{ideal:640},height:{ideal:480}}});
                if (current !== s) return false;
                await sender.replaceTrack(fresh.getVideoTracks()[0]);
            } else await sender.replaceTrack(null);
            if (current !== s) return false;
            s.stream.getVideoTracks().forEach(function (track) { s.stream.removeTrack(track); track.stop(); });
            if (fresh) fresh.getVideoTracks().forEach(function (track) { s.stream.addTrack(track); });
            adopted = true; s.cameraEnabled = enabled;
            return true;
        } finally {
            if (fresh && !adopted) fresh.getTracks().forEach(function (track) { track.stop(); });
        }
    }
    camera.onclick = async function () {
        var s = current;
        if (!s || !s.video || !s.stream || s.cameraBusy || s.flipping) return;
        s.cameraBusy = true; camera.disabled = flip.disabled = true; inform('');
        try {
            if (await changeCamera(s,!s.cameraEnabled)) {
                var on = s.cameraEnabled;
                setControl(camera,on ? 'Tắt camera' : 'Bật camera',on ? 'camera' : 'cameraOff',!on);
                localVideo.srcObject = s.stream; localVideo.classList.toggle('is-off',!on);
                flip.hidden = !on || (s.cameraCount || 0) < 2;
                inform(on ? 'Đã bật camera.' : 'Đã tắt camera. Bạn vẫn có thể nói chuyện.');
            }
        } catch (error) {
            if (current === s) inform(mediaErrorMessage(error,'camera') || error.message);
        } finally { s.cameraBusy = false; if (current === s) camera.disabled = flip.disabled = false; }
    };
    /** Đổi camera trước/sau: lấy track mới rồi thay trên kết nối, không cần thương lượng lại. */
    flip.onclick = async function () {
        var s = current;
        if (!s || !s.stream || !s.pc || s.flipping || s.cameraBusy || !s.cameraEnabled) return;
        s.flipping = true;
        var fresh, adopted = false;
        try {
            var facing = s.facing === 'user' ? 'environment' : 'user';
            fresh = await navigator.mediaDevices.getUserMedia({ video: { facingMode: facing, width: { ideal: 640 }, height: { ideal: 480 } } });
            var track = fresh.getVideoTracks()[0];
            if (current !== s) { track.stop(); return; }
            var old = s.stream.getVideoTracks()[0];
            track.enabled = !old || old.enabled;
            var sender = s.videoSender;
            if (!sender) throw new Error('Camera chưa sẵn sàng.');
            await sender.replaceTrack(track);
            if (current !== s) return;
            if (old) { s.stream.removeTrack(old); old.stop(); }
            s.stream.addTrack(track);
            adopted = true;
            s.facing = facing;
            localVideo.srcObject = s.stream;
        } catch (e) {
            if (current === s) inform(mediaErrorMessage(e, 'camera')
                || 'Không đổi được camera. Hãy kiểm tra camera còn lại rồi thử lại.');
        } finally {
            if (fresh && !adopted) fresh.getTracks().forEach(function (t) { t.stop(); });
            s.flipping = false;
        }
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
            name: m.name, incoming: true, video: m.video === true,
        }, m.video === true ? 'Cuộc gọi video đến' : 'Cuộc gọi thoại đến');
    });
    rt.on('voice.ringing', function (m) {
        if (current && current.id === m.callId) status.textContent = 'Đang đổ chuông…';
    });
    rt.on('voice.accepted', function (m) {
        var s = current;
        if (!s || s.id !== m.callId || s.accepted) return;
        s.accepted = true;
        callSounds.stop();
        clearTimeout(s.timer);
        s.timer = setTimeout(function () {
            finish(s, reasons.connection, true, false, 'setup-timeout');
        }, 60000);
        status.textContent = s.video ? 'Đang kết nối video…' : 'Đang kết nối thoại…';
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
                    : (reasons[m.reason] || reasons.ended), false, type === 'dismiss', 'server-' + (m.reason || type));
            }
        });
    });
    rt.on('open', refresh);
    rt.on('close', function () {
        incomingGeneration++; pendingIncoming=null;
        if (current) finish(current, 'Mất kết nối với máy chủ chat. Hãy kiểm tra mạng rồi gọi lại.', false, false, 'websocket-closed');
        refresh();
    });
    window.addEventListener('pagehide', function () {
        incomingGeneration++; pendingIncoming=null;
        callSounds.stop();
        if (current) finish(current, reasons.ended, true, true, 'page-hidden');
    });
    refresh();
})();
