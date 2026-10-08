/**
 * Khung chat nổi hiển thị trên mọi trang.
 *
 * Luồng: thẻ dọc ở mép phải -> mở ra thấy DANH SÁCH hội thoại (phòng chat
 * chung nằm ngay đầu danh sách như một mục bình thường) -> chọn một mục mới
 * vào khung trò chuyện.
 *
 * Màn rộng: hai cột, danh sách bên trái và nội dung bên phải.
 * Màn hẹp: danh sách chiếm hết khung, chọn mục thì trượt sang khung chat và
 * có nút quay lại.
 */
(function () {
    'use strict';

    var root = document.getElementById('chat-widget');
    if (!root) { return; }

    var base    = root.getAttribute('data-base');
    var isGuest = root.getAttribute('data-guest') === '1';
    // Chưa xác thực email: xem phòng chat được, nhưng chưa gửi tin được
    var canXacThuc = root.getAttribute('data-need-verify') === '1';

    /* Icon chia nhóm để bảng chọn có tab, mỗi tab một biểu tượng đại diện */
    var EMOJI_GROUPS = [
        { icon: '🙂', name: 'Cảm xúc', items: [
            '😀','😃','😄','😁','😆','😊','🙂','😉','😍','🥰','😘','😗','😙','😚','😋','😜',
            '🤪','🤗','🤔','🤭','😐','😑','😶','🙄','😏','😥','😮','😯','😴','😌','😔','😪',
            '🤤','😭','😢','😤','😠','😡','🥺','😳','🥵','🥶','😱','😨','😰','🤩','🥳','😎'] },
        { icon: '👍', name: 'Cử chỉ', items: [
            '👋','🤚','✋','👌','🤌','✌️','🤞','🤟','🤘','👈','👉','👆','👇','👍','👎','✊',
            '👊','🤝','🙏','💪','👏','🙌','🤲','💅','👀','👁️','👄','💋'] },
        { icon: '❤️', name: 'Tình yêu', items: [
            '❤️','🧡','💛','💚','💙','💜','🖤','🤍','💔','❣️','💕','💞','💓','💗','💖','💘',
            '💝','💐','🌹','🌷','🌸','💌','💍','💑','💏','👩‍❤️‍👨','🥂','🍫','🎁','🌙','✨','⭐'] },
        { icon: '🎉', name: 'Khác', items: [
            '🔥','💯','🎉','🎊','☕','🍺','🍻','🍰','🍜','🍕','🚗','✈️','🏖️','🌴','🎵','🎸',
            '⚽','🏀','🎬','📷','📱','💤','☀️','🌧️','❄️','🐶','🐱','🌻','🍀','🎯','🕐','✅'] }
    ];

    var bubble = document.getElementById('cw-bubble');
    var badge  = document.getElementById('cw-badge');
    var panel  = document.getElementById('cw-panel');

    // Reserve the visible bottom navigation instead of covering it.
    var bottomNav = document.querySelector('.site-mobile-nav');
    var visualViewport = window.visualViewport;
    var savedScrollY = 0;
    var pageLocked = false;
    function fitAboveNav() {
        var height = bottomNav && bottomNav.getClientRects().length ? bottomNav.offsetHeight : 0;
        var top = visualViewport ? visualViewport.offsetTop : 0;
        var viewportHeight = visualViewport ? visualViewport.height : window.innerHeight;
        panel.style.setProperty('--cw-nav-height', height + 'px');
        panel.style.setProperty('--cw-viewport-top', top + 'px');
        panel.style.setProperty('--cw-panel-height', Math.max(0, viewportHeight - height) + 'px');
        if (pageLocked) {
            document.documentElement.style.setProperty('--cw-keyboard-inset', Math.max(0, window.innerHeight - top - viewportHeight) + 'px');
        }
    }
    function syncPageLock() {
        var open = !panel.hidden;
        if (open && !pageLocked) {
            savedScrollY = window.scrollY;
            document.body.style.setProperty('--cw-scroll-top', -savedScrollY + 'px');
            document.documentElement.classList.add('cw-page-locked');
            pageLocked = true;
        } else if (!open && pageLocked) {
            document.documentElement.classList.remove('cw-page-locked');
            document.documentElement.style.removeProperty('--cw-keyboard-inset');
            document.body.style.removeProperty('--cw-scroll-top');
            pageLocked = false;
            window.scrollTo(0, savedScrollY);
        }
        fitAboveNav();
    }
    new MutationObserver(syncPageLock).observe(panel, { attributes: true, attributeFilter: ['hidden'] });
    var previousTouchY = 0;
    document.addEventListener('touchstart', function (event) {
        if (event.touches.length === 1) previousTouchY = event.touches[0].clientY;
    }, { passive: true });
    document.addEventListener('touchmove', function (event) {
        if (!pageLocked || event.touches.length !== 1) return;
        var area = event.target.closest('.cw-body, .cw-side-list, .cw-online-panel, .cw-emoji-list');
        var delta = event.touches[0].clientY - previousTouchY;
        previousTouchY = event.touches[0].clientY;
        if (!area || !panel.contains(area) || area.scrollHeight <= area.clientHeight ||
            (delta > 0 && area.scrollTop <= 0) ||
            (delta < 0 && area.scrollTop + area.clientHeight >= area.scrollHeight - 1)) {
            if (event.cancelable) event.preventDefault();
        }
    }, { passive: false });
    fitAboveNav();
    window.addEventListener('resize', fitAboveNav);
    window.addEventListener('load', fitAboveNav);
    if (visualViewport) {
        visualViewport.addEventListener('resize', fitAboveNav);
        visualViewport.addEventListener('scroll', fitAboveNav);
    }
    if (bottomNav && window.ResizeObserver) {
        new ResizeObserver(fitAboveNav).observe(bottomNav);
    }

    var sideEl   = document.getElementById('cw-side');
    var listEl   = document.getElementById('cw-list');
    var searchEl = document.getElementById('cw-search');
    var idleEl   = document.getElementById('cw-idle');
    var convoEl  = document.getElementById('cw-convo');

    var bodyEl   = document.getElementById('cw-body');
    var formEl   = document.getElementById('cw-form');
    var inputEl  = document.getElementById('cw-input');
    var fileEl   = document.getElementById('cw-file');
    var receiver = document.getElementById('cw-receiver');

    var avatarEl = document.getElementById('cw-avatar');
    var avatarRm = document.getElementById('cw-avatar-room');
    var nameEl   = document.getElementById('cw-name');
    var statusEl = document.getElementById('cw-status');

    var roomLastEl = document.getElementById('cw-room-last');
    var roomTimeEl = document.getElementById('cw-room-time');
    var rowRoom    = document.getElementById('cw-row-room');

    var onlinePanel = document.getElementById('cw-online-panel');
    var onlineList = document.getElementById('cw-online-list');
    var onlineToggle = document.getElementById('cw-online-toggle');
    var onlineToggleCount = document.getElementById('cw-online-toggle-count');
    var onlineClose = document.getElementById('cw-online-close');
    var onlineGeneration = 0;
    var onlineLoading = false;
    var onlineUpdated = 0;
    var mobileOnline = window.matchMedia('(max-width: 760px)');

    function closeOnline(restoreFocus) {
        if (!onlinePanel) return;
        onlinePanel.classList.remove('is-open');
        onlinePanel.setAttribute('role', 'complementary');
        if (onlineToggle) {
            onlineToggle.setAttribute('aria-expanded', 'false');
            if (restoreFocus) onlineToggle.focus();
        }
    }

    function showOnlineRoom(show) {
        if (!onlinePanel) return;
        onlinePanel.hidden = !show;
        if (onlineToggle) onlineToggle.hidden = !show;
        if (statusEl) statusEl.hidden = show;
        closeOnline(false);
        onlineGeneration++;
        onlineLoading = false;
        onlineUpdated = 0;
        if (show) loadOnline();
    }

    function loadOnline(force) {
        if (!onlinePanel || panel.hidden || !dang_mo || dang_mo.kind !== 'room'
            || document.hidden || onlineLoading
            || !onlinePanel.classList.contains('is-open')
            || (!force && Date.now() - onlineUpdated < 30000)) return;
        onlineLoading = true;
        var generation = onlineGeneration;
        var members = [];
        function page(after) {
            return api('ajax/online-members?after=' + after).then(function (res) {
                if (generation !== onlineGeneration) return;
                if (!res.ok || !Array.isArray(res.members)) throw new Error('Online list unavailable');
                members = members.concat(res.members);
                if (res.next && res.next > after) return page(res.next);
                var fragment = document.createDocumentFragment();
                members.forEach(function (member) {
                    var link = document.createElement('a');
                    link.className = 'cw-online-member';
                    link.href = member.url;
                    var avatar = document.createElement('img');
                    avatar.src = member.avatar;
                    avatar.alt = '';
                    avatar.width = 32;
                    avatar.height = 32;
                    avatar.loading = 'lazy';
                    var name = document.createElement('span');
                    name.textContent = member.name + (member.mine ? ' (Bạn)' : '');
                    link.appendChild(avatar);
                    link.appendChild(name);
                    fragment.appendChild(link);
                });
                if (!members.length) {
                    var empty = document.createElement('p');
                    empty.textContent = 'Chưa có thành viên online.';
                    fragment.appendChild(empty);
                }
                onlineList.replaceChildren(fragment);
                onlineUpdated = Date.now();
            });
        }
        if (!onlineList.childNodes.length) onlineList.textContent = 'Đang tải…';
        page(0).catch(function () {
            if (generation === onlineGeneration && !onlineUpdated) onlineList.textContent = 'Chưa tải được danh sách. Vui lòng thử lại.';
        }).finally(function () {
            if (generation === onlineGeneration) onlineLoading = false;
        });
    }

    if (onlineToggle && onlinePanel) {
        onlineToggle.addEventListener('click', function () {
            var open = !onlinePanel.classList.contains('is-open');
            onlinePanel.classList.toggle('is-open', open);
            onlineToggle.setAttribute('aria-expanded', String(open));
            onlinePanel.setAttribute('role', open ? 'dialog' : 'complementary');
            if (open) { loadOnline(true); onlineClose.focus(); }
        });
        onlineClose.addEventListener('click', function () { closeOnline(true); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && onlinePanel.classList.contains('is-open')) {
                event.preventDefault(); closeOnline(true);
            }
        });
        document.addEventListener('pointerdown', function (event) {
            if (!onlinePanel.contains(event.target) && !onlineToggle.contains(event.target)) closeOnline(false);
        });
        mobileOnline.addEventListener('change', function () { closeOnline(false); loadOnline(true); });
    }

    /** Hội thoại đang mở: {kind:'room'} hoặc {kind:'chat', id, user_id, name, ...} */
    var dang_mo = null;
    var lastId  = 0;     // id tin cuối đã vẽ của hội thoại đang mở
    var timer   = null;  // chu kỳ tải tin mới
    var listTimer = null;
    var sending = false;
    var renderedIds = new Set();

    var RT = window.Realtime || null;
    function rtLive() { return !!(RT && RT.connected); }

    /* ------------------------- Tiện ích ------------------------- */

    function api(url, options) {
        return fetch(base + url, Object.assign({
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }, options || {})).then(function (r) { return r.json(); });
    }

    function atBottom() {
        return bodyEl.scrollHeight - bodyEl.scrollTop - bodyEl.clientHeight < 60;
    }
    function scrollDown() { bodyEl.scrollTop = bodyEl.scrollHeight; }

    function setBadge(n) {
        if (!badge) { return; }
        badge.textContent = n > 99 ? '99+' : n;
        badge.hidden = n <= 0;
    }

    /** Nút "N tin nhắn mới" ở góc dưới khung tin. */
    var jump = (function () {
        var btn = document.getElementById('cw-jump');
        var so  = 0;
        if (!btn) { return { them: function () {}, reset: function () {} }; }

        var text = btn.querySelector('.cw-jump-text');
        function ve() {
            btn.hidden = so === 0;
            if (text) { text.textContent = so + ' Tin nhắn mới'; }
        }
        function reset() { so = 0; ve(); }

        btn.addEventListener('click', function () { scrollDown(); reset(); });
        bodyEl.addEventListener('scroll', function () { if (atBottom()) { reset(); } });

        return { them: function () { so++; ve(); }, reset: reset };
    })();

    /* ------------------------- Danh sách hội thoại ------------------------- */

    var duLieuList = [];   // giữ lại để lọc theo ô tìm kiếm mà không gọi lại máy chủ

    function boDau(s) {
        return s.normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
    }

    function veList() {
        if (!listEl) { return; }

        var key = boDau((searchEl && searchEl.value || '').trim());
        var hien = duLieuList.filter(function (it) {
            return !key || boDau(it.name).indexOf(key) !== -1;
        });

        listEl.textContent = '';

        if (!hien.length) {
            var p = document.createElement('p');
            p.className = 'cw-empty';
            p.textContent = key
                ? 'Không tìm thấy ai tên như vậy.'
                : 'Chưa có cuộc trò chuyện nào. Mở một hồ sơ và nhắn tin để làm quen.';
            listEl.appendChild(p);
            return;
        }

        hien.forEach(function (it) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'cw-row' + (it.unread > 0 ? ' is-unread' : '');

            var av = document.createElement('span');
            av.className = 'cw-row-avatar';
            var img = document.createElement('img');
            img.src = it.avatar; img.alt = '';
            av.appendChild(img);
            if (it.online) {
                var dot = document.createElement('i');
                dot.className = 'cw-dot';
                av.appendChild(dot);
            }

            var text = document.createElement('span');
            text.className = 'cw-row-text';

            var top = document.createElement('span');
            top.className = 'cw-row-top';
            var b = document.createElement('b');
            var ten = document.createElement('span');
            ten.className = 'cw-row-name';
            ten.textContent = it.name;
            b.appendChild(ten);
            var t = document.createElement('small');
            t.textContent = it.time || '';
            top.appendChild(b);
            top.appendChild(t);

            var last = document.createElement('span');
            last.className = 'cw-row-last';
            last.textContent = it.last || '';

            text.appendChild(top);
            text.appendChild(last);

            row.appendChild(av);
            row.appendChild(text);

            if (it.unread > 0) {
                var n = document.createElement('i');
                n.className = 'cw-row-unread';
                n.textContent = it.unread > 99 ? '99+' : it.unread;
                row.appendChild(n);
            }

            row.dataset.conv = it.id;
            row.addEventListener('click', function () { moChat(it); });
            listEl.appendChild(row);
        });
    }

    function loadList() {
        if (isGuest || !listEl) { return Promise.resolve(); }
        return api('ajax/hoi-thoai').then(function (res) {
            if (!res.ok) { return; }
            duLieuList = res.items || [];
            setBadge(res.unread || 0);
            veList();

            // Dòng đang mở được tô sáng để biết mình đang ở đâu
            danhDauDangMo();
        }).catch(function () { /* mất mạng thì để nguyên danh sách cũ */ });
    }

    function danhDauDangMo() {
        var rows = sideEl ? sideEl.querySelectorAll('.cw-row') : [];
        Array.prototype.forEach.call(rows, function (r) { r.classList.remove('is-on'); });

        if (!dang_mo) { return; }
        if (dang_mo.kind === 'room') {
            rowRoom.classList.add('is-on');
            return;
        }
        // Tra theo id gắn trên từng dòng, không theo thứ tự: danh sách hiển thị
        // có thể đang bị ô tìm kiếm lọc bớt nên thứ tự không còn khớp dữ liệu.
        var row = listEl.querySelector('[data-conv="' + dang_mo.id + '"]');
        if (row) { row.classList.add('is-on'); }
    }

    /** Tóm tắt phòng chung cho dòng đầu danh sách + số online trên thẻ dọc. */
    function loadRoomSummary() {
        loadOnline();
        return api('ajax/phong-chat?only=online').then(function (res) {
            if (!res || !res.ok) { return; }
            setOnline(res.online);
            if (roomLastEl) { roomLastEl.textContent = res.last || ''; }
            if (roomTimeEl) { roomTimeEl.textContent = res.time || ''; }
        }).catch(function () {});
    }

    var tabCount  = document.getElementById('cw-tab-count');
    var tabOnline = document.getElementById('cw-tab-online');
    function setOnline(n) {
        if (onlineToggleCount) {
            var onlineLabel = Math.max(0, Number(n) || 0).toLocaleString('vi-VN') + ' Người đang Online';
            onlineToggleCount.textContent = onlineLabel;
            onlineToggle.setAttribute('aria-label', onlineLabel + '. Bấm để xem danh sách người đang online');
        }
        if (statusEl && dang_mo && dang_mo.kind === 'room') {
            statusEl.textContent = n + ' Người đang Online';
        }
        if (tabCount) { tabCount.textContent = Number(n).toLocaleString('vi-VN'); }
        if (tabOnline) { tabOnline.hidden = false; }
    }

    /* ------------------------- Vẽ tin nhắn ------------------------- */

    var ngayCuoi = null;   // ngày của tin vừa vẽ, để biết khi nào cần chèn vạch ngày
    var nguoiCuoi = null;  // người gửi tin vừa vẽ, để gộp tin liên tiếp

    function chiLaIcon(s) {
        return /^[\p{Extended_Pictographic}️\s]{1,8}$/u.test(String(s).trim());
    }

    /** Tô màu phần "@Tên ai đó" ở đầu câu, dùng textContent nên không chèn được HTML. */
    function veNoiDungCoNhac(p, content) {
        var m = /^(@[^\s@]+(?:\s[^\s@]+){0,3})(\s+)([\s\S]*)$/.exec(content);
        if (!m) { p.textContent = content; return; }

        var at = document.createElement('span');
        at.className = 'cw-at';
        at.textContent = m[1];
        p.appendChild(at);
        p.appendChild(document.createTextNode(m[2] + m[3]));
    }

    function vachNgay(nhan) {
        var d = document.createElement('div');
        d.className = 'cw-day';
        var s = document.createElement('span');
        s.textContent = nhan;
        d.appendChild(s);
        return d;
    }

    /**
     * Vẽ một tin. Tin liên tiếp của cùng một người được gộp: chỉ tin đầu nhóm
     * mới có avatar và tên, các tin sau thụt vào cho gọn.
     */
    function renderMessage(m) {
        // Sang ngày mới thì chèn vạch ngăn
        if (m.day && m.day !== ngayCuoi) {
            bodyEl.appendChild(vachNgay(m.day));
            ngayCuoi = m.day;
            nguoiCuoi = null;
        }

        var khoa = m.mine ? 'me' : ('u' + (m.user_id || m.sender_id || m.name || ''));
        var noiTiep = khoa === nguoiCuoi;
        if (noiTiep) {
            var previous = bodyEl.lastElementChild;
            var previousMeta = previous && previous.querySelector('.cw-msg-time');
            if (previousMeta) previousMeta.hidden = true;
        }
        nguoiCuoi = khoa;

        var wrap = document.createElement('div');
        wrap.className = 'cw-msg' + (m.mine ? ' mine' : '') + (noiTiep ? ' is-cont' : '');

        // Avatar chỉ vẽ ở tin đầu nhóm, và chỉ với tin của người khác
        if (!m.mine) {
            if (!noiTiep && m.avatar) {
                var av = document.createElement('img');
                av.className = 'cw-msg-avatar';
                av.src = m.avatar; av.alt = '';
                wrap.appendChild(av);
            } else {
                var chen = document.createElement('span');
                chen.className = 'cw-msg-avatar is-blank';
                wrap.appendChild(chen);
            }
        }

        var col = document.createElement('div');
        col.className = 'cw-msg-col';

        if (!m.mine && !noiTiep && m.name) {
            var who = m.slug ? document.createElement('a') : document.createElement('span');
            who.className = 'cw-msg-name';
            if (m.slug) { who.href = base + 'profile/' + m.slug; }
            who.textContent = m.name;
            col.appendChild(who);
        }

        if (m.type === 'image') {
            var a = document.createElement('a');
            a.href = m.content; a.target = '_blank'; a.rel = 'noopener';
            var img = document.createElement('img');
            img.src = m.content; img.alt = 'Ảnh'; img.className = 'cw-msg-image';
            img.loading = 'lazy';
            // Ảnh vào khung làm nội dung dài thêm, cuộn lại cho khỏi hụt đáy
            img.onload = function () { if (atBottom()) { scrollDown(); } };
            // Ảnh hỏng (file bị xoá, sai đường dẫn) thì trình duyệt vẽ biểu
            // tượng ảnh vỡ to đùng tràn cả khung — thay bằng một dòng gọn.
            img.onerror = function () {
                var hong = document.createElement('span');
                hong.className = 'cw-msg-broken';
                hong.textContent = 'Ảnh không tải được';
                a.replaceWith(hong);
            };
            a.appendChild(img);
            col.appendChild(a);
        } else {
            var p = document.createElement('p');
            if (chiLaIcon(m.content)) {
                p.className = 'cw-emoji-only';
                p.textContent = m.content;
            } else {
                veNoiDungCoNhac(p, m.content);
            }
            col.appendChild(p);
        }

        var meta = document.createElement('small');
        meta.className = 'cw-msg-time';
        meta.textContent = m.time || '';
        if (m.mine && m.seen) {
            meta.classList.add('has-seen');
            var seen = document.createElement('i');
            seen.className = 'cw-seen';
            seen.textContent = 'Đã xem';
            meta.appendChild(seen);
        }
        col.appendChild(meta);

        wrap.appendChild(col);
        return wrap;
    }

    function veLai(messages) {
        bodyEl.textContent = '';
        renderedIds.clear();
        ngayCuoi = null;
        nguoiCuoi = null;

        if (!messages || !messages.length) {
            var trong = document.createElement('p');
            trong.className = 'cw-empty';
            trong.textContent = dang_mo && dang_mo.kind === 'room'
                ? 'Chưa có ai nhắn gì. Bạn mở lời trước nhé!'
                : 'Chưa có tin nhắn nào. Gửi lời chào đi!';
            bodyEl.appendChild(trong);
            return;
        }

        messages.forEach(function (m) {
            var id = String(m.id);
            if (renderedIds.has(id)) { return; }
            renderedIds.add(id);
            bodyEl.appendChild(renderMessage(m));
        });
        scrollDown();
    }

    function themTin(m) {
        var id = String(m.id);
        if (renderedIds.has(id)) { return; }
        renderedIds.add(id);
        var trong = bodyEl.querySelector('.cw-empty');
        if (trong) { trong.remove(); }

        var stick = atBottom();
        bodyEl.appendChild(renderMessage(m));
        if (stick) { scrollDown(); }
        else if (!m.mine) { jump.them(); }
    }

    /* ------------------------- Mở hội thoại ------------------------- */

    function moKhung() {
        if (idleEl) { idleEl.hidden = true; }
        convoEl.hidden = false;
        root.classList.add('is-chat');   // màn hẹp: trượt sang khung chat
        jump.reset();
    }

    /** Mở phòng chat chung. */
    function moPhong() {
        renderedIds.clear();
        dang_mo = { kind: 'room' };
        showOnlineRoom(true);
        lastId = 0;
        clearInterval(timer);

        if (avatarEl) { avatarEl.hidden = true; }
        if (avatarRm) { avatarRm.hidden = false; }
        nameEl.textContent = 'Nhóm Hẹn Hò 4 Phương';
        statusEl.textContent = 'Đang tải…';
        if (receiver) { receiver.value = ''; }
        if (inputEl) {
            inputEl.placeholder = isGuest ? 'Đăng nhập để trò chuyện…' : 'Nhắn cho cả phòng…';
        }

        moKhung();
        danhDauDangMo();
        bodyEl.textContent = '';

        batDauTai();
        if (inputEl && !isGuest) { inputEl.focus(); }
    }

    /** Mở một hội thoại riêng. */
    function moChat(info) {
        showOnlineRoom(false);
        renderedIds.clear();
        dang_mo = Object.assign({ kind: 'chat' }, info);
        lastId = 0;
        clearInterval(timer);

        if (avatarRm) { avatarRm.hidden = true; }
        if (avatarEl) {
            avatarEl.hidden = false;
            avatarEl.src = info.avatar || '';
        }
        nameEl.textContent = info.name || '';
        statusEl.textContent = info.online ? 'Đang online' : '';
        if (receiver) { receiver.value = info.user_id || ''; }
        if (inputEl) { inputEl.placeholder = 'Vui lòng nhập tin nhắn'; }

        moKhung();
        danhDauDangMo();
        bodyEl.textContent = '';

        batDauTai();
        if (inputEl) { inputEl.focus(); }
    }

    /** Quay lại danh sách (màn hẹp). */
    function veDanhSach() {
        closeOnline(false);
        root.classList.remove('is-chat');
        dang_mo = null;
        clearInterval(timer);
        convoEl.hidden = true;
        if (idleEl) { idleEl.hidden = false; }
        danhDauDangMo();
        loadList();
    }

    /* ------------------------- Tải tin ------------------------- */

    /** Tải ngay một lần rồi bật lại vòng lặp (nếu không có WebSocket). */
    function batDauTai() {
        clearInterval(timer);
        tai();
        if (!rtLive()) { timer = setInterval(tai, 4000); }
    }

    function tai() {
        if (!dang_mo) { return; }

        if (dang_mo.kind === 'room') {
            if (rtLive() && lastId > 0) { return; }
            return api('ajax/phong-chat?after=' + lastId).then(function (res) {
                // Đổi hội thoại rất nhanh thì câu trả lời của hội thoại cũ có
                // thể về sau — bỏ qua, không thì tin của người này lọt sang
                // khung của người kia.
                if (!res.ok || !dang_mo || dang_mo.kind !== 'room') { return; }
                if (typeof res.online !== 'undefined') { setOnline(res.online); }

                if (lastId === 0) {
                    veLai(res.messages);
                } else {
                    res.messages.forEach(function (m) { themTin(m); });
                }
                (res.messages || []).forEach(function (m) {
                    if (m.id > lastId) { lastId = m.id; }
                });
            }).catch(function () {});
        }

        if (rtLive() && lastId > 0) { return; }
        var hoi_thoai = dang_mo.id;   // ghim lại để đối chiếu khi câu trả lời về
        return api('ajax/tin-nhan/' + hoi_thoai + '?after=' + lastId).then(function (res) {
            if (!res.ok || !dang_mo || dang_mo.kind !== 'chat') { return; }
            if (Number(dang_mo.id) !== Number(hoi_thoai)) { return; }

            if (lastId === 0) {
                veLai(res.messages);
            } else {
                res.messages.forEach(function (m) { themTin(m); });
            }
            (res.messages || []).forEach(function (m) {
                if (m.id > lastId) { lastId = m.id; }
            });

            if (res.messages && res.messages.length) { loadList(); }
            if (res.seen) {
                bodyEl.querySelectorAll('.cw-msg.mine .cw-msg-time:not([hidden])').forEach(function (meta) {
                    if (meta.querySelector('.cw-seen')) return;
                    meta.classList.add('has-seen');
                    var label = document.createElement('i');
                    label.className = 'cw-seen';
                    label.textContent = 'Đã xem';
                    meta.appendChild(label);
                });
            }
        }).catch(function () {});
    }

    /* ------------------------- Gửi tin ------------------------- */

    if (formEl) {
        var sendButton = formEl.querySelector('.cw-send');
        if (sendButton) {
            // Keep the input focused during the tap; refocusing after an async
            // request cannot reliably reopen the keyboard on iOS.
            function keepKeyboard(event) {
                if (document.activeElement === inputEl) event.preventDefault();
            }
            sendButton.addEventListener('pointerdown', keepKeyboard);
            sendButton.addEventListener('mousedown', keepKeyboard);
            if (!window.PointerEvent) {
                sendButton.addEventListener('touchstart', function (event) {
                    if (document.activeElement !== inputEl) return;
                    event.preventDefault();
                    formEl.dispatchEvent(new Event('submit', { cancelable: true }));
                }, { passive: false });
            }
        }
        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!dang_mo || sending) { return; }

            var text = (inputEl.value || '').trim();
            var coAnh = fileEl && fileEl.files && fileEl.files.length;
            if (!text && !coAnh) { return; }

            if (canXacThuc) {
                if (window.appNeed) {
                    window.appNeed({
                        need: 'verify', url: base + 'xac-thuc',
                        message: 'Xác thực email (bấm link trong thư chúng tôi đã gửi) để bắt đầu nhắn tin.'
                    });
                }
                return;
            }

            var data = new FormData(formEl);
            var url  = dang_mo.kind === 'room' ? 'ajax/phong-chat/gui' : 'ajax/send-message';

            sending = true;
            inputEl.value = '';
            // Tải ảnh lên mất vài giây, phải cho thấy là máy đang làm việc chứ
            // không để người dùng bấm đi bấm lại vì tưởng hỏng.
            formEl.classList.add('is-sending');

            api(url, { method: 'POST', body: data }).then(function (res) {
                formEl.classList.remove('is-sending');
                if (fileEl) { fileEl.value = ''; }
                if (!res.ok) {
                    if (window.appNeed && window.appNeed(res)) { return; }
                    if (window.appModal) {
                        window.appModal({ type: 'error', title: 'Không gửi được', message: res.message });
                    }
                    return;
                }
                tai();
                loadList();
                loadRoomSummary();
            }).catch(function () {
                formEl.classList.remove('is-sending');
                if (fileEl) { fileEl.value = ''; }
                if (window.appModal) {
                    window.appModal({
                        type: 'error', title: 'Không gửi được',
                        message: 'Mất kết nối khi đang gửi. Bạn thử lại nhé.'
                    });
                }
            }).finally(function () { sending = false; });
        });
    }

    if (bodyEl) {
        bodyEl.addEventListener('click', function (event) {
            if (!event.target.closest('a, button') && document.activeElement === inputEl) {
                inputEl.blur();
            }
        });
    }

    /* Chọn ảnh xong là gửi luôn, không phải bấm thêm nút */
    if (fileEl) {
        fileEl.addEventListener('change', function () {
            if (fileEl.files && fileEl.files.length) {
                formEl.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
    }

    /* ------------------------- Bảng icon ------------------------- */

    function setupEmoji(btn, panelEl, input) {
        if (!btn || !panelEl || !input) { return; }

        var tabs = panelEl.querySelector('.cw-emoji-tabs');
        var list = panelEl.querySelector('.cw-emoji-list');
        if (!tabs || !list) { return; }

        function chen(ch) {
            var a = input.selectionStart == null ? input.value.length : input.selectionStart;
            var b = input.selectionEnd == null ? input.value.length : input.selectionEnd;
            input.value = input.value.slice(0, a) + ch + input.value.slice(b);
            input.selectionStart = input.selectionEnd = a + ch.length;
        }

        function veNhom(i) {
            list.textContent = '';
            EMOJI_GROUPS[i].items.forEach(function (ch) {
                var it = document.createElement('button');
                it.type = 'button';
                it.className = 'cw-emoji-item';
                it.textContent = ch;
                it.addEventListener('click', function () { chen(ch); });
                list.appendChild(it);
            });
            Array.prototype.forEach.call(tabs.children, function (t, k) {
                t.classList.toggle('is-on', k === i);
            });
            list.scrollTop = 0;
        }

        EMOJI_GROUPS.forEach(function (g, i) {
            var t = document.createElement('button');
            t.type = 'button';
            t.className = 'cw-emoji-tab';
            t.textContent = g.icon;
            t.title = g.name;
            t.addEventListener('click', function () { veNhom(i); });
            tabs.appendChild(t);
        });
        veNhom(0);

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            panelEl.hidden = !panelEl.hidden;
            if (!panelEl.hidden) { input.blur(); }
        });
        panelEl.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    var emojiPanel = document.getElementById('cw-emoji-panel');
    setupEmoji(document.getElementById('cw-emoji-btn'), emojiPanel, inputEl);

    /* ------------------------- Đóng / mở ------------------------- */

    bubble.addEventListener('click', function () {
        var moRa = panel.hidden;
        panel.hidden = !moRa;
        root.classList.toggle('open', moRa);
        bubble.setAttribute('aria-expanded', String(moRa));

        if (moRa) {
            // Mở ra là thấy danh sách trước, không nhảy thẳng vào phòng chung
            loadRoomSummary();
            loadList();
            clearInterval(listTimer);
            listTimer = setInterval(function () {
                loadList();
                loadRoomSummary();
            }, 15000);
            // Mở lại phải bật lại vòng lặp, không thì hội thoại đang mở
            // đứng im vì chỉ gọi tai() đúng một lần.
            if (dang_mo) { batDauTai(); }
        } else {
            clearInterval(timer);
            clearInterval(listTimer);
        }
    });

    function dongChat() {
            showOnlineRoom(false);
            panel.hidden = true;
            bubble.setAttribute('aria-expanded', 'false');
            root.classList.remove('open', 'is-chat');
            clearInterval(timer);
            clearInterval(listTimer);
            dang_mo = null;
            convoEl.hidden = true;
            if (idleEl) { idleEl.hidden = false; }
    }

    root.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', dongChat);
    });

    document.addEventListener('pointerdown', function (e) {
        if (!panel.hidden && !root.contains(e.target)) { dongChat(); }
    });

    if (rowRoom) { rowRoom.addEventListener('click', moPhong); }

    var backBtn = document.getElementById('cw-back');
    if (backBtn) { backBtn.addEventListener('click', veDanhSach); }

    if (searchEl) {
        searchEl.addEventListener('input', function () { veList(); danhDauDangMo(); });
    }

    document.addEventListener('click', function () {
        if (emojiPanel) { emojiPanel.hidden = true; }
    });

    /* Nút "Nhắn tin" ở trang cá nhân mở thẳng khung chat thay vì chuyển trang */
    document.querySelectorAll('[data-chat-with]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            api('ajax/mo-chat/' + btn.getAttribute('data-chat-with')).then(function (res) {
                if (res.ok) {
                    panel.hidden = false;
                    root.classList.add('open');
                    bubble.setAttribute('aria-expanded', 'true');
                    loadList();
                    return moChat(res);
                }
                if (window.appModal) {
                    window.appModal({
                        type: 'error',
                        title: 'Không mở được trò chuyện',
                        message: res.message
                    });
                }
            });
        });
    });

    /* ------------------------- Realtime ------------------------- */

    if (RT) {
        RT.on('room.message', function (msg) {
            if (!dang_mo || dang_mo.kind !== 'room') { loadRoomSummary(); return; }
            themTin(msg.message);
            if (msg.message.id > lastId) { lastId = msg.message.id; }
        });

        RT.on('room.history', function (msg) {
            if (!dang_mo || dang_mo.kind !== 'room') { return; }
            veLai(msg.messages);
            (msg.messages || []).forEach(function (m) {
                if (m.id > lastId) { lastId = m.id; }
            });
            jump.reset();
        });

        RT.on('room.presence', function (msg) { setOnline(msg.online); });

        RT.on('chat.message', function (msg) {
            if (dang_mo && dang_mo.kind === 'chat' && Number(dang_mo.id) === Number(msg.conversationId)) {
                themTin(msg.message);
                if (msg.message.id > lastId) { lastId = msg.message.id; }
                RT.send({ t: 'chat.read', conversationId: dang_mo.id });
            }
            loadList();
        });
    }

    /* Số chưa đọc và tóm tắt phòng chung lấy ngay khi vào trang */
    loadRoomSummary();
    if (!isGuest) {
        loadList();
        document.addEventListener('dating-contact-added', loadList);
    }
})();
