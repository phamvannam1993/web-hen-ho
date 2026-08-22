/* Tương tác frontend: modal thông báo, lấy mã pass, hiện số điện thoại. */
(function () {
    'use strict';

    var base = document.querySelector('base') ? document.querySelector('base').href : '/';

    /* ------------------------- Modal dùng chung ------------------------- */

    function ensureModal() {
        var el = document.getElementById('app-modal');
        if (el) { return el; }

        el = document.createElement('div');
        el.id = 'app-modal';
        el.className = 'modal-overlay';
        el.innerHTML =
            '<div class="modal-box" role="dialog" aria-modal="true">' +
                '<button class="modal-close" type="button" aria-label="Đóng">&times;</button>' +
                '<div class="modal-icon"></div>' +
                '<h3 class="modal-title"></h3>' +
                '<div class="modal-body"></div>' +
                '<div class="modal-actions"><button class="btn btn-primary modal-ok" type="button">Đã hiểu</button></div>' +
            '</div>';
        document.body.appendChild(el);

        function close() { el.classList.remove('open'); }
        el.querySelector('.modal-close').addEventListener('click', close);
        el.querySelector('.modal-ok').addEventListener('click', close);
        el.addEventListener('click', function (e) { if (e.target === el) { close(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
        return el;
    }

    /** showModal({title, message, type: 'success'|'error'|'info', code: 'ABC123'}) */
    function showModal(opts) {
        var el = ensureModal();
        var type = opts.type || 'info';
        var icons = { success: '✓', error: '!', info: 'i' };

        el.querySelector('.modal-icon').textContent = icons[type] || 'i';
        el.querySelector('.modal-icon').className = 'modal-icon ' + type;
        el.querySelector('.modal-title').textContent = opts.title || '';
        el.querySelector('.modal-body').innerHTML = opts.code
            ? '<p>' + (opts.message || '') + '</p><div class="modal-code">' + opts.code + '</div>'
            : '<p>' + (opts.message || '') + '</p>';
        el.classList.add('open');
    }

    window.appModal = showModal;

    /* ------------------------- Menu mobile ------------------------- */

    // Trên màn hình hẹp, bấm vào mục cha để mở/đóng danh mục con thay vì hover.
    // Không chặn điều hướng ở desktop vì ở đó submenu mở bằng hover.
    document.querySelectorAll('.has-mega > a').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (window.innerWidth > 720) { return; }
            e.preventDefault();
            var li = link.parentNode;
            document.querySelectorAll('.has-mega.open').forEach(function (other) {
                if (other !== li) { other.classList.remove('open'); }
            });
            li.classList.toggle('open');
        });
    });

    /* ------------------------- Xem trước ảnh khi chọn file ------------------------- */

    document.querySelectorAll('input[type=file][accept*="image"]').forEach(function (input) {
        // ảnh đại diện có sẵn thẻ img cạnh bên thì cập nhật trực tiếp thẻ đó
        var target = input.getAttribute('data-preview')
            ? document.querySelector(input.getAttribute('data-preview'))
            : (input.closest('.profile-avatar') ? input.closest('.profile-avatar').querySelector('img') : null);

        // với các input còn lại, tạo khung xem trước ngay dưới ô chọn file
        var box = null;
        if (!target) {
            box = document.createElement('div');
            box.className = 'file-preview';

            // input đính kèm ảnh của bình luận nằm ẩn trong nhãn -> đặt khung preview
            // ngay dưới thanh công cụ để không phá bố cục
            var bar = input.closest('.comment-bar');
            if (bar) {
                bar.parentNode.insertBefore(box, bar.nextSibling);
            } else {
                input.parentNode.insertBefore(box, input.nextSibling);
            }
        }

        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files || []);
            if (box) { box.innerHTML = ''; }
            if (!files.length) { return; }

            files.forEach(function (file, i) {
                if (!/^image\//.test(file.type)) { return; }
                var url = URL.createObjectURL(file);

                if (target && i === 0) {
                    target.src = url;
                    return;
                }
                if (box) {
                    var img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.onload = function () { URL.revokeObjectURL(url); };
                    box.appendChild(img);
                }
            });
        });
    });

    /* ------------------------- Gọi API ------------------------- */

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data || {})
        }).then(function (r) { return r.json(); });
    }

    /* --- Lấy mã bảo mật cho form đăng ký / đăng nhập --- */
    document.querySelectorAll('[data-get-code]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            post(link.href, {}).then(function (res) {
                if (!res.ok) {
                    return showModal({ type: 'error', title: 'Không lấy được mã', message: res.message || 'Vui lòng thử lại.' });
                }
                var input = link.closest('form').querySelector('[name=access_code]');
                if (input) { input.value = res.code; }
                showModal({
                    type: 'success',
                    title: 'Mã bảo mật của bạn',
                    message: 'Mã đã được điền sẵn vào ô bên dưới, hiệu lực trong 30 phút.',
                    code: res.code
                });
            }).catch(function () { window.location.href = link.href; });
        });
    });

    /* --- Trang Khám phá: thích / bỏ qua từng hồ sơ --- */
    var grid = document.getElementById('swipe-grid');
    if (grid) {
        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-action]');
            if (!btn) { return; }

            var card = btn.closest('.swipe-card');
            var id = card.getAttribute('data-user');
            var action = btn.getAttribute('data-action');
            var url = base + 'kham-pha/' + (action === 'like' ? 'thich/' : 'bo-qua/') + id;

            btn.disabled = true;
            post(url, {}).then(function (res) {
                if (!res.ok) {
                    btn.disabled = false;
                    return showModal({ type: 'error', title: 'Không thực hiện được', message: res.message });
                }

                // Bay thẻ ra khỏi lưới rồi mới gỡ, đồng thời nạp hồ sơ kế tiếp
                card.classList.add(action === 'like' ? 'card-liked' : 'card-passed');
                setTimeout(function () {
                    card.remove();
                    if (res.next) { grid.insertAdjacentHTML('beforeend', res.next); }
                    if (!grid.querySelector('.swipe-card')) { window.location.reload(); }
                }, 280);

                if (res.matched) {
                    showModal({
                        type: 'success', title: 'Ghép đôi thành công!',
                        message: 'Hai bạn đã thích nhau. Vào mục Tin nhắn để bắt đầu trò chuyện.'
                    });
                }
            });
        });
    }

    /* --- Thích thành viên --- */
    document.querySelectorAll('[data-like-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            post(base + 'ajax/like', { type: 'user', id: btn.getAttribute('data-like-user') })
                .then(function (res) {
                    if (!res.ok) {
                        return showModal({ type: 'error', title: 'Không thực hiện được', message: res.message });
                    }
                    btn.textContent = res.liked ? '♥ Đã thích' : '♥ Thích';
                    btn.classList.toggle('btn-primary', !res.liked);
                    btn.classList.toggle('btn-ghost', res.liked);
                    showModal({
                        type: res.matched ? 'success' : 'info',
                        title: res.matched ? 'Ghép đôi thành công!' : 'Đã cập nhật',
                        message: res.message
                    });
                });
        });
    });

    /* --- Báo cáo thành viên --- */
    document.querySelectorAll('[data-report-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var note = window.prompt('Mô tả ngắn lý do bạn báo cáo thành viên này:');
            if (note === null) { return; }
            post(base + 'ajax/report', {
                target_type: 'user', target_id: btn.getAttribute('data-report-user'),
                reason: 'khac', note: note
            }).then(function (res) {
                showModal({ type: res.ok ? 'success' : 'error', title: res.ok ? 'Đã gửi báo cáo' : 'Lỗi', message: res.message });
            });
        });
    });

    /* --- Trả lời bình luận kiểu Facebook: mở form ngay dưới bình luận --- */
    document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('reply-form-' + btn.getAttribute('data-reply-to'));
            if (!form) { return; }
            document.querySelectorAll('.reply-form').forEach(function (f) {
                if (f !== form) { f.hidden = true; }
            });
            form.hidden = !form.hidden;
            if (!form.hidden) { form.querySelector('textarea').focus(); }
        });
    });

    document.querySelectorAll('[data-cancel-reply]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('.reply-form').hidden = true; });
    });

    /* --- Lấy pass và hiện số điện thoại ở trang cá nhân thành viên --- */
    var memberContact = document.querySelector('.contact-row[data-member]');
    if (memberContact) {
        var slug = memberContact.getAttribute('data-member');

        memberContact.querySelector('[data-action=get-pass]').addEventListener('click', function () {
            post(base + 'thanh-vien/' + slug + '/lay-pass', {}).then(function (res) {
                if (!res.ok) {
                    return showModal({ type: 'error', title: 'Không lấy được pass', message: res.message });
                }
                memberContact.querySelector('[data-field=pass]').value = res.code;
                showModal({
                    type: 'success', title: 'Pass xem số điện thoại', code: res.code,
                    message: res.cost ? 'Đã trừ ' + res.cost + ' xu.' : 'Bạn là thành viên VIP nên không mất xu.'
                });
            });
        });

        memberContact.querySelector('[data-action=reveal]').addEventListener('click', function () {
            post(base + 'thanh-vien/' + slug + '/mo-lien-he', {
                code: memberContact.querySelector('[data-field=pass]').value
            }).then(function (res) {
                if (!res.ok) {
                    return showModal({ type: 'error', title: 'Mã không hợp lệ', message: res.message });
                }
                memberContact.querySelector('[data-field=result]').textContent = res.contact;
                showModal({ type: 'success', title: 'Đã mở liên hệ', message: res.contact });
            });
        });
    }

    /* --- Lấy pass và hiện số điện thoại ở trang chi tiết tin --- */
    var contact = document.querySelector('.contact-row[data-post]');
    if (contact) {
        var postId = contact.getAttribute('data-post');

        var getBtn = contact.querySelector('[data-action=get-pass]');
        if (getBtn) {
            getBtn.addEventListener('click', function () {
                post(base + 'tin-dang/lay-pass/' + postId, {}).then(function (res) {
                    if (!res.ok) {
                        return showModal({ type: 'error', title: 'Không lấy được pass', message: res.message });
                    }
                    contact.querySelector('[data-field=pass]').value = res.code;
                    showModal({
                        type: 'success',
                        title: 'Pass xem số điện thoại',
                        message: res.cost ? 'Đã trừ ' + res.cost + ' xu khỏi tài khoản của bạn.' : 'Bạn là thành viên VIP nên không mất xu.',
                        code: res.code
                    });
                });
            });
        }

        var okBtn = contact.querySelector('[data-action=reveal]');
        if (okBtn) {
            okBtn.addEventListener('click', function () {
                var code = contact.querySelector('[data-field=pass]').value;
                post(base + 'tin-dang/mo-lien-he/' + postId, { code: code }).then(function (res) {
                    if (!res.ok) {
                        return showModal({ type: 'error', title: 'Mã không hợp lệ', message: res.message });
                    }
                    contact.querySelector('[data-field=result]').textContent = res.contact;
                    showModal({ type: 'success', title: 'Đã mở liên hệ', message: 'Thông tin liên hệ: ' + res.contact });
                });
            });
        }
    }
})();

/* ------------------------- Bộ icon cho khung chat ------------------------- */
(function () {
    'use strict';

    var input = document.getElementById('chat-input');
    var panel = document.getElementById('emoji-panel');
    var btn   = document.getElementById('emoji-btn');
    var list  = document.getElementById('emoji-list');
    if (!input || !panel || !btn || !list) { return; }

    var GROUPS = {
        'cam-xuc': ['😀','😃','😄','😁','😆','😊','🙂','😉','😍','🥰','😘','😗','😙','😚','😋','😜',
                    '🤪','🤗','🤔','🤭','😐','😑','😶','🙄','😏','😥','😮','😯','😴','😌','😔','😪',
                    '🤤','😭','😢','😤','😠','😡','🥺','😳','🥵','🥶','😱','😨','😰','🤩','🥳','😎'],
        'cu-chi':  ['👋','🤚','✋','👌','🤌','✌️','🤞','🤟','🤘','👈','👉','👆','👇','👍','👎','✊',
                    '👊','🤝','🙏','💪','👏','🙌','🤲','💅','👀','👁️','👄','💋'],
        'tinh-yeu':['❤️','🧡','💛','💚','💙','💜','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘',
                    '💝','💐','🌹','🌷','🌸','💌','💍','💑','💏','👩‍❤️‍👨','🥂','🍫','🎁','🌙','✨','⭐'],
        'khac':    ['🔥','💯','🎉','🎊','☕','🍺','🍻','🍰','🍜','🍕','🚗','✈️','🏖️','🌴','🎵','🎸',
                    '⚽','🏀','🎬','📷','📱','💤','☀️','🌧️','❄️','🐶','🐱','🌻','🍀','🎯','🕐','✅']
    };

    /** Vẽ danh sách icon của một nhóm. */
    function renderGroup(name) {
        list.innerHTML = '';
        (GROUPS[name] || []).forEach(function (ch) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'emoji-item';
            b.textContent = ch;
            list.appendChild(b);
        });
    }
    renderGroup('cam-xuc');

    /* Đổi nhóm icon */
    panel.querySelector('.emoji-tabs').addEventListener('click', function (e) {
        var tab = e.target.closest('[data-group]');
        if (!tab) { return; }
        panel.querySelectorAll('.emoji-tabs button').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        renderGroup(tab.getAttribute('data-group'));
    });

    /* Chèn icon vào đúng vị trí con trỏ, không ghi đè nội dung đang gõ */
    list.addEventListener('click', function (e) {
        var item = e.target.closest('.emoji-item');
        if (!item) { return; }
        var start = input.selectionStart || input.value.length;
        var end   = input.selectionEnd || input.value.length;
        input.value = input.value.slice(0, start) + item.textContent + input.value.slice(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + item.textContent.length;
    });

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.hidden = !panel.hidden;
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { panel.hidden = true; });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { panel.hidden = true; }
    });

    /* Gửi ảnh: chọn xong là gửi luôn, khỏi bấm thêm nút Gửi */
    var attach = document.querySelector('.chat-attach input[type=file]');
    if (attach) {
        attach.addEventListener('change', function () {
            if (attach.files && attach.files.length) { attach.closest('form').submit(); }
        });
    }

    /* Luôn cuộn xuống tin nhắn mới nhất khi mở hội thoại */
    var body = document.getElementById('chat-body');
    if (body) { body.scrollTop = body.scrollHeight; }
})();

/* ------------------------- Chat: gửi & nhận không tải lại trang ------------------------- */
(function () {
    'use strict';

    var body = document.getElementById('chat-body');
    var form = document.querySelector('.chat-form');
    if (!body || !form) { return; }

    // suy ra đường dẫn gốc từ action của form (site_url) để chạy đúng cả khi
    // site nằm trong thư mục con, không giả định luôn là "/"
    var base = form.action.replace(/ajax\/send-message.*$/, '');
    var convId = body.getAttribute('data-conversation');
    if (!convId) { return; }

    var lastId = parseInt(body.getAttribute('data-last-id') || '0', 10);
    var input  = document.getElementById('chat-input');
    var seenEl = document.getElementById('chat-seen');

    function atBottom() {
        return body.scrollHeight - body.scrollTop - body.clientHeight < 60;
    }
    function scrollDown() { body.scrollTop = body.scrollHeight; }

    /** Dựng một bong bóng tin nhắn từ dữ liệu JSON. */
    function renderMessage(m) {
        var div = document.createElement('div');
        div.className = 'chat-msg' + (m.mine ? ' mine' : '') + (m.type === 'image' ? ' is-image' : '');

        if (m.type === 'image') {
            var a = document.createElement('a');
            a.href = m.content; a.target = '_blank';
            var img = document.createElement('img');
            img.src = m.content; img.alt = 'Ảnh'; img.loading = 'lazy';
            img.onload = function () { if (atBottom()) { scrollDown(); } };
            a.appendChild(img); div.appendChild(a);
        } else {
            var p = document.createElement('p');
            // tin nhắn chỉ gồm icon thì phóng to
            if (/^[\p{Extended_Pictographic}️\s]{1,8}$/u.test(m.content.trim())) {
                p.className = 'emoji-only';
            }
            p.textContent = m.content;
            div.appendChild(p);
        }

        var t = document.createElement('small');
        t.textContent = m.time;
        div.appendChild(t);
        return div;
    }

    /** Hỏi máy chủ xem có tin nào mới hơn lastId không. */
    function poll() {
        fetch(base + 'ajax/tin-nhan/' + convId + '?after=' + lastId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) { return; }
            var stick = atBottom();

            res.messages.forEach(function (m) {
                body.appendChild(renderMessage(m));
                if (m.id > lastId) { lastId = m.id; }
            });

            if (seenEl) { seenEl.textContent = res.seen ? 'Đã xem' : ''; }
            if (res.messages.length && stick) { scrollDown(); }
        })
        .catch(function () { /* mất mạng tạm thời thì bỏ qua, lần sau thử lại */ });
    }

    setInterval(poll, 4000);
    scrollDown();

    /* Gửi tin nhắn chữ bằng AJAX; gửi ảnh vẫn để form submit như cũ */
    form.addEventListener('submit', function (e) {
        var file = form.querySelector('input[type=file]');
        if (file && file.files && file.files.length) { return; }

        e.preventDefault();
        var text = (input.value || '').trim();
        if (!text) { return; }

        input.value = '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.ok) {
                input.value = text;
                if (window.appModal) {
                    window.appModal({ type: 'error', title: 'Không gửi được', message: res.message });
                }
                return;
            }
            poll();
        })
        .catch(function () { form.submit(); });
    });
})();
