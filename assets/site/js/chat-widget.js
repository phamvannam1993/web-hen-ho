/**
 * Khung chat nổi hiển thị trên mọi trang.
 *
 * Luồng: bong bóng sát mép phải -> bấm xổ ra danh sách hội thoại
 * -> chọn một người -> khung trò chuyện. Tin nhắn mới tự tải về theo chu kỳ.
 */
(function () {
    'use strict';

    var root = document.getElementById('chat-widget');
    if (!root) { return; }

    var base   = root.getAttribute('data-base');
    // Khách chưa đăng nhập chỉ xem được phòng chat chung, không có tin nhắn riêng
    var isGuest = root.getAttribute('data-guest') === '1';
    var EMOJI = ['😀','😄','😁','😊','🙂','😉','😍','🥰','😘','😋','😜','🤗','🤔','😐','🙄','😏',
                 '😢','😭','😤','😡','🥺','😳','😱','🤩','🥳','😎','👋','👌','✌️','👍','🙏','💪',
                 '👏','🙌','❤️','💕','💖','💘','💔','🌹','💐','💍','🔥','💯','🎉','☕','🍺','🎁'];
    var bubble = document.getElementById('cw-bubble');
    var badge  = document.getElementById('cw-badge');
    var panel  = document.getElementById('cw-panel');

    var roomView = document.getElementById('cw-room-view');
    var listView = document.getElementById('cw-list-view');
    var chatView = document.getElementById('cw-chat-view');
    var listEl   = document.getElementById('cw-list');

    var bodyEl   = document.getElementById('cw-body');
    var formEl   = document.getElementById('cw-form');
    var inputEl  = document.getElementById('cw-input');
    var fileEl   = document.getElementById('cw-file');
    var receiver = document.getElementById('cw-receiver');

    var current = null;   // hội thoại đang mở
    var lastId  = 0;      // id tin nhắn cuối đã hiển thị
    var timer   = null;   // chu kỳ tải tin mới của hội thoại đang mở

    /* Lớp realtime (nếu có). Khi WebSocket sống thì tắt polling để đỡ tải máy chủ. */
    var RT = window.Realtime || null;
    function rtLive() { return !!(RT && RT.connected); }

    /* ------------------------- Tiện ích ------------------------- */

    function api(url, options) {
        return fetch(base + url, Object.assign({
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }, options || {})).then(function (r) { return r.json(); });
    }

    function atBottom() {
        return bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight < 60;
    }
    function scrollDown() { bodyEl.scrollTop = bodyEl.scrollHeight; }

    function setBadge(n) {
        if (n > 0) {
            badge.textContent = n > 99 ? '99+' : n;
            badge.hidden = false;
        } else {
            badge.hidden = true;
        }
    }

    /* ------------------------- Danh sách hội thoại ------------------------- */

    function loadList() {
        return api('ajax/hoi-thoai').then(function (res) {
            if (!res.ok) { return; }
            setBadge(res.unread);

            if (!res.items.length) {
                listEl.innerHTML = '<p class="cw-empty">Chưa có cuộc trò chuyện nào.<br>'
                    + 'Hãy vào <a href="' + base + 'kham-pha">Khám phá</a> để kết nối.</p>';
                return;
            }

            listEl.innerHTML = '';
            res.items.forEach(function (it) {
                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'cw-item';
                row.innerHTML =
                    '<span class="cw-item-avatar">'
                  +   '<img src="' + it.avatar + '" alt="">'
                  +   (it.online ? '<i class="cw-dot"></i>' : '')
                  + '</span>'
                  + '<span class="cw-item-text">'
                  +   '<b></b><small></small>'
                  + '</span>'
                  + (it.unread ? '<span class="cw-item-unread">' + it.unread + '</span>' : '');
                // gán bằng textContent để tránh chèn HTML từ dữ liệu người dùng
                row.querySelector('b').textContent = it.name;
                row.querySelector('small').textContent = it.last;

                row.addEventListener('click', function () { openChat(it); });
                listEl.appendChild(row);
            });
        });
    }

    /* ------------------------- Khung trò chuyện ------------------------- */

    function renderMessage(m) {
        var div = document.createElement('div');
        div.className = 'cw-msg' + (m.mine ? ' mine' : '') + (m.type === 'image' ? ' is-image' : '');

        if (m.type === 'image') {
            var a = document.createElement('a');
            a.href = m.content;
            a.target = '_blank';
            var img = document.createElement('img');
            img.src = m.content;
            img.alt = 'Ảnh';
            img.onload = function () { if (atBottom()) { scrollDown(); } };
            a.appendChild(img);
            div.appendChild(a);
        } else {
            var p = document.createElement('p');
            if (/^[\p{Extended_Pictographic}️\s]{1,8}$/u.test(m.content.trim())) {
                p.className = 'cw-emoji-only';
            }
            p.textContent = m.content;
            div.appendChild(p);
        }

        var t = document.createElement('small');
        t.textContent = m.time;
        div.appendChild(t);
        return div;
    }

    function poll() {
        if (!current || rtLive()) { return; }
        api('ajax/tin-nhan/' + current.id + '?after=' + lastId).then(function (res) {
            if (!res.ok) { return; }
            var stick = atBottom();
            res.messages.forEach(function (m) {
                bodyEl.appendChild(renderMessage(m));
                if (m.id > lastId) { lastId = m.id; }
            });
            if (res.messages.length && stick) { scrollDown(); }
        }).catch(function () { /* mất mạng thì bỏ qua, chu kỳ sau thử lại */ });
    }

    function openChat(info) {
        stopRoom();
        current = info;
        lastId = 0;
        bodyEl.innerHTML = '';
        receiver.value = info.user_id;

        document.getElementById('cw-avatar').src = info.avatar;
        document.getElementById('cw-name').textContent = info.name;
        document.getElementById('cw-status').textContent = info.online ? 'Đang online' : 'Ngoại tuyến';

        roomView.hidden = true;
        listView.hidden = true;
        chatView.hidden = false;
        panel.hidden = false;
        root.classList.add('open');

        poll();
        clearInterval(timer);
        timer = setInterval(poll, 4000);
        inputEl.focus();
    }

    function backToList() {
        clearInterval(timer);
        current = null;
        chatView.hidden = true;
        listView.hidden = false;
        loadList();
    }

    /* ------------------------- Gửi tin ------------------------- */

    if (formEl) {
    formEl.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (inputEl.value || '').trim();
        var hasFile = fileEl.files && fileEl.files.length;
        if (!text && !hasFile) { return; }

        if (!hasFile && rtLive() && current && current.id) {
            inputEl.value = '';
            RT.send({ t: 'chat.send', conversationId: current.id, content: text });
            return;
        }

        var data = new FormData(formEl);
        inputEl.value = '';
        fileEl.value = '';

        api('ajax/send-message', { method: 'POST', body: data }).then(function (res) {
            if (!res.ok) {
                inputEl.value = text;
                if (window.appModal) {
                    window.appModal({ type: 'error', title: 'Không gửi được', message: res.message });
                }
                return;
            }
            if (res.warning && window.appModal) {
                window.appModal({ type: 'error', title: 'Ảnh không gửi được', message: res.warning });
            }
            poll();
        });
    });

    // chọn ảnh xong gửi luôn
    fileEl.addEventListener('change', function () {
        if (fileEl.files && fileEl.files.length) {
            formEl.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });
    }

    /* ------------------------- Bảng icon ------------------------- */


    var emojiBtn = document.getElementById('cw-emoji-btn');
    var emojiPanel = document.getElementById('cw-emoji-panel');

    if (emojiBtn && emojiPanel && inputEl) {
    EMOJI.forEach(function (ch) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'cw-emoji-item';
        b.textContent = ch;
        b.addEventListener('click', function () {
            var s = inputEl.selectionStart || inputEl.value.length;
            var e2 = inputEl.selectionEnd || inputEl.value.length;
            inputEl.value = inputEl.value.slice(0, s) + ch + inputEl.value.slice(e2);
            inputEl.focus();
            inputEl.selectionStart = inputEl.selectionEnd = s + ch.length;
        });
        emojiPanel.appendChild(b);
    });

    emojiBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        emojiPanel.hidden = !emojiPanel.hidden;
    });
    emojiPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    /* ------------------------- Phòng chat chung ------------------------- */

    var roomBody  = document.getElementById('cw-room-body');
    var roomForm  = document.getElementById('cw-room-form');   // khách: không có
    var roomInput = document.getElementById('cw-room-input');
    var roomFile  = document.getElementById('cw-room-file');
    var roomOnline = document.getElementById('cw-room-online');
    var roomLastId = 0;
    var roomTimer  = null;

    function roomAtBottom() {
        return roomBody.scrollHeight - roomBody.scrollTop - roomBody.clientHeight < 60;
    }

    /** Tin phòng chung có kèm avatar và tên người gửi để phân biệt nhiều người. */
    function renderRoomMessage(m) {
        var wrap = document.createElement('div');
        wrap.className = 'cw-room-msg' + (m.mine ? ' mine' : '');

        if (!m.mine) {
            var av = document.createElement('img');
            av.className = 'cw-room-avatar';
            av.src = m.avatar;
            av.alt = '';
            wrap.appendChild(av);
        }

        var col = document.createElement('div');
        col.className = 'cw-room-col';

        if (!m.mine) {
            var who = document.createElement('a');
            who.className = 'cw-room-name';
            who.href = base + 'profile/' + m.slug;
            who.textContent = m.name;
            col.appendChild(who);
        }

        if (m.type === 'image') {
            var a = document.createElement('a');
            a.href = m.content; a.target = '_blank';
            var img = document.createElement('img');
            img.src = m.content; img.alt = 'Ảnh'; img.className = 'cw-room-image';
            img.onload = function () { if (roomAtBottom()) { roomBody.scrollTop = roomBody.scrollHeight; } };
            a.appendChild(img); col.appendChild(a);
        } else {
            var p = document.createElement('p');
            if (/^[\p{Extended_Pictographic}️\s]{1,8}$/u.test(m.content.trim())) {
                p.className = 'cw-emoji-only';
            }
            p.textContent = m.content;
            col.appendChild(p);
        }

        var t = document.createElement('small');
        t.textContent = m.time;
        col.appendChild(t);
        wrap.appendChild(col);
        return wrap;
    }

    function pollRoom() {
        if (rtLive()) { return; }
        api('ajax/phong-chat?after=' + roomLastId).then(function (res) {
            if (!res.ok) { return; }
            var stick = roomAtBottom() || roomLastId === 0;

            res.messages.forEach(function (m) {
                roomBody.appendChild(renderRoomMessage(m));
                if (m.id > roomLastId) { roomLastId = m.id; }
            });

            roomOnline.textContent = res.online + ' người đang online';
            if (res.messages.length && stick) { roomBody.scrollTop = roomBody.scrollHeight; }
        }).catch(function () { /* bỏ qua, chu kỳ sau thử lại */ });
    }

    function startRoom() {
        pollRoom();
        clearInterval(roomTimer);
        roomTimer = setInterval(pollRoom, 4000);
    }
    function stopRoom() { clearInterval(roomTimer); }

    if (roomForm) {
    roomForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (roomInput.value || '').trim();
        var hasFile = roomFile.files && roomFile.files.length;
        if (!text && !hasFile) { return; }

        // Tin chữ đi qua WebSocket cho tức thời; có ảnh thì vẫn phải qua HTTP để tải file
        if (!hasFile && rtLive()) {
            roomInput.value = '';
            RT.send({ t: 'room.send', content: text });
            return;
        }

        var data = new FormData(roomForm);
        roomInput.value = '';
        roomFile.value = '';

        api('ajax/phong-chat/gui', { method: 'POST', body: data }).then(function (res) {
            if (!res.ok) {
                roomInput.value = text;
                if (window.appModal) {
                    window.appModal({ type: 'error', title: 'Không gửi được', message: res.message });
                }
                return;
            }
            if (res.warning && window.appModal) {
                window.appModal({ type: 'error', title: 'Ảnh không gửi được', message: res.warning });
            }
            pollRoom();
        });
    });

    roomFile.addEventListener('change', function () {
        if (roomFile.files && roomFile.files.length) {
            roomForm.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });
    }

    /* Bảng icon riêng cho phòng chung */
    var roomEmojiBtn = document.getElementById('cw-room-emoji-btn');
    var roomEmojiPanel = document.getElementById('cw-room-emoji-panel');
    if (roomEmojiBtn && roomEmojiPanel) {
    EMOJI.forEach(function (ch) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'cw-emoji-item';
        b.textContent = ch;
        b.addEventListener('click', function () {
            var s2 = roomInput.selectionStart || roomInput.value.length;
            var e2 = roomInput.selectionEnd || roomInput.value.length;
            roomInput.value = roomInput.value.slice(0, s2) + ch + roomInput.value.slice(e2);
            roomInput.focus();
            roomInput.selectionStart = roomInput.selectionEnd = s2 + ch.length;
        });
        roomEmojiPanel.appendChild(b);
    });
    roomEmojiBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        roomEmojiPanel.hidden = !roomEmojiPanel.hidden;
    });
    roomEmojiPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    /* Chuyển qua lại giữa phòng chung và danh sách chat riêng */
    function showRoom() {
        listView.hidden = true; chatView.hidden = true; roomView.hidden = false;
        clearInterval(timer); current = null;
        startRoom();
    }
    function showList() {
        stopRoom();
        roomView.hidden = true; chatView.hidden = true; listView.hidden = false;
        loadList();
    }
    var toListBtn = document.getElementById('cw-to-list');
    var toRoomBtn = document.getElementById('cw-to-room');
    if (toListBtn) { toListBtn.addEventListener('click', showList); }
    if (toRoomBtn) { toRoomBtn.addEventListener('click', showRoom); }

    /* Khách bấm vào ô nhập hoặc nút gửi: mời đăng nhập thay vì không phản hồi gì */
    if (isGuest) {
        var guestForm = document.getElementById('cw-room-form');
        if (guestForm) {
            guestForm.addEventListener('click', function () {
                if (window.appModal) {
                    window.appModal({
                        type: 'info',
                        title: 'Cần đăng nhập',
                        message: 'Bạn cần đăng nhập để tham gia trò chuyện. Việc xem thì hoàn toàn tự do.',
                        confirmText: 'Đăng nhập',
                        onConfirm: function () { window.location.href = base + 'dang-nhap'; }
                    });
                }
            });
        }
    }

    /* ------------------------- Đóng / mở ------------------------- */

    bubble.addEventListener('click', function () {
        var opening = panel.hidden;
        panel.hidden = !opening;
        root.classList.toggle('open', opening);
        if (opening) {
            // mở lên là vào thẳng phòng chat chung
            if (roomView.hidden && listView.hidden && chatView.hidden) { showRoom(); }
            else if (!roomView.hidden) { startRoom(); }
        } else {
            stopRoom();
        }
    });

    root.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () {
            panel.hidden = true;
            root.classList.remove('open');
            clearInterval(timer);
            stopRoom();
            current = null;
            chatView.hidden = true;
            listView.hidden = true;
            roomView.hidden = false;
        });
    });

    var backBtn = document.getElementById('cw-back');
    if (backBtn) { backBtn.addEventListener('click', backToList); }
    // Khách chưa đăng nhập không có khung chat riêng nên các phần tử này vắng mặt
    document.addEventListener('click', function () {
        if (emojiPanel) { emojiPanel.hidden = true; }
    });

    /* Nút "Nhắn tin" ở trang cá nhân mở thẳng khung chat thay vì chuyển trang */
    document.querySelectorAll('[data-chat-with]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            api('ajax/mo-chat/' + btn.getAttribute('data-chat-with')).then(function (res) {
                if (res.ok) { openChat(res); }
            });
        });
    });


    /* ------------------------- Gắn với máy chủ realtime ------------------------- */

    if (RT) {
        // Nhận tin mới của phòng chung
        RT.on('room.message', function (msg) {
            if (roomView.hidden) { return; }
            var stick = roomAtBottom();
            roomBody.appendChild(renderRoomMessage(msg.message));
            if (msg.message.id > roomLastId) { roomLastId = msg.message.id; }
            if (stick) { roomBody.scrollTop = roomBody.scrollHeight; }
        });

        // Lịch sử phòng chung khi vừa vào
        RT.on('room.history', function (msg) {
            roomBody.innerHTML = '';
            roomLastId = 0;
            msg.messages.forEach(function (m) {
                roomBody.appendChild(renderRoomMessage(m));
                if (m.id > roomLastId) { roomLastId = m.id; }
            });
            roomBody.scrollTop = roomBody.scrollHeight;
        });

        // Số người online cập nhật tức thời
        RT.on('room.presence', function (msg) {
            roomOnline.textContent = msg.online + ' người đang online';
        });

        // Tin nhắn riêng đến
        RT.on('chat.message', function (msg) {
            if (current && Number(current.id) === Number(msg.conversationId)) {
                var stick = atBottom();
                bodyEl.appendChild(renderMessage(msg.message));
                if (msg.message.id > lastId) { lastId = msg.message.id; }
                if (stick) { scrollDown(); }
                RT.send({ t: 'chat.read', conversationId: current.id });
            } else if (!msg.message.mine) {
                // đang ở màn khác: cập nhật huy hiệu chưa đọc
                loadList();
            }
        });

        // Lịch sử khi mở hội thoại riêng bằng WebSocket
        RT.on('chat.opened', function (msg) {
            bodyEl.innerHTML = '';
            lastId = 0;
            msg.messages.forEach(function (m) {
                bodyEl.appendChild(renderMessage(m));
                if (m.id > lastId) { lastId = m.id; }
            });
            scrollDown();
        });

        // Kết nối lại thì lấy bù phần đã lỡ
        RT.on('open', function () {
            if (!roomView.hidden) { RT.send({ t: 'room.join' }); }
            if (current && current.id) { RT.send({ t: 'chat.load', conversationId: current.id, after: lastId }); }
        });

        RT.on('chat.history', function (msg) {
            if (!current || Number(current.id) !== Number(msg.conversationId)) { return; }
            var stick = atBottom();
            msg.messages.forEach(function (m) {
                bodyEl.appendChild(renderMessage(m));
                if (m.id > lastId) { lastId = m.id; }
            });
            if (stick) { scrollDown(); }
        });

        // Máy chủ báo lỗi (vượt giới hạn, bị chặn…)
        RT.on('error', function (msg) {
            if (window.appModal) {
                window.appModal({ type: 'error', title: 'Không gửi được', message: msg.message });
            }
        });

        // Vào/rời phòng chung theo màn hình đang mở
        var _showRoom = showRoom;
        showRoom = function () {
            _showRoom();
            RT.send({ t: 'room.join' });
        };
        var _showList = showList;
        showList = function () {
            RT.send({ t: 'room.leave' });
            _showList();
        };
        var _openChat = openChat;
        openChat = function (info) {
            _openChat(info);
            RT.send({ t: 'room.leave' });
            if (info && info.id) { RT.send({ t: 'chat.load', conversationId: info.id, after: 0 }); }
        };
    }

    /* Đếm tin chưa đọc định kỳ ngay cả khi chưa mở khung chat */
    if (!isGuest) {
        loadList();
        setInterval(function () { if (!current) { loadList(); } }, 20000);
    }
})();
