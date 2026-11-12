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
    var lockedHeader = null, headerSpacer = null;
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
            var header = document.querySelector('.site-header');
            if (header && getComputedStyle(header).position === 'sticky') {
                var bounds = header.getBoundingClientRect();
                if (bounds.top <= 0 && bounds.bottom > 0) {
                    lockedHeader = header;
                    headerSpacer = document.createElement('div');
                    headerSpacer.style.height = header.offsetHeight + 'px';
                    headerSpacer.setAttribute('aria-hidden', 'true');
                    header.before(headerSpacer);
                    header.style.setProperty('--cw-header-top', bounds.top + 'px');
                    header.classList.add('cw-locked-header');
                }
            }
            document.body.style.setProperty('--cw-scroll-top', -savedScrollY + 'px');
            document.documentElement.classList.add('cw-page-locked');
            pageLocked = true;
        } else if (!open && pageLocked) {
            document.documentElement.classList.remove('cw-page-locked');
            document.documentElement.style.removeProperty('--cw-keyboard-inset');
            document.body.style.removeProperty('--cw-scroll-top');
            if (lockedHeader) {
                lockedHeader.classList.remove('cw-locked-header');
                lockedHeader.style.removeProperty('--cw-header-top');
                headerSpacer.remove();
                lockedHeader = null; headerSpacer = null;
            }
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
        var area = event.target.closest('.message-history-results, .cw-body, .cw-side-list, .cw-online-panel, .cw-emoji-list, .cg-feed, .cg-info, .cg-composer-emoji-grid, .cg-composer textarea');
        var delta = event.touches[0].clientY - previousTouchY;
        previousTouchY = event.touches[0].clientY;
        if (!area || !panel.contains(area) || area.scrollHeight <= area.clientHeight ||
            (delta > 0 && area.scrollTop <= 0) ||
            (delta < 0 && area.scrollTop + area.clientHeight >= area.scrollHeight - 1)) {
            if (event.cancelable) event.preventDefault();
        }
    }, { passive: false });
    fitAboveNav();
    var focusFitTimer;
    function fitAfterFocus() {
        fitAboveNav();
        clearTimeout(focusFitTimer);
        focusFitTimer = setTimeout(fitAboveNav, 350);
    }
    panel.addEventListener('focusin', fitAfterFocus);
    panel.addEventListener('focusout', fitAfterFocus);
    window.addEventListener('orientationchange', fitAfterFocus);
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
    var historySearch = document.getElementById('cw-history-search');
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
    var deletedThrough = Object.create(null), chatVersion = 0, listVersion = 0;

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

    var directUnread = 0, groupUnread = 0;
    root.addEventListener('cw:group-unread', function (event) {
        groupUnread = Number(event.detail) || 0;
        setBadge(directUnread);
    });
    function setBadge(n) {
        directUnread = Number(n) || 0;
        n = directUnread + groupUnread;
        if (!badge) { return; }
        badge.textContent = n > 99 ? '99+' : n;
        badge.hidden = n <= 0;
        badge.setAttribute('aria-label', n + ' tin chưa đọc');
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
            if (it.notifications_enabled === false || it.calls_notifications_enabled === false) {
                var muted = document.createElement('span'); muted.className = 'cw-inbox-muted';
                muted.title = 'Đã tắt thông báo'; muted.setAttribute('aria-label','Đã tắt thông báo');
                muted.textContent = '🔕'; b.appendChild(muted);
            }
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
            var holder = document.createElement('div'); holder.className = 'cw-conversation-row';
            holder.dataset.conversationRow = '';
            var more = document.createElement('button'); more.type = 'button'; more.className = 'cw-conversation-more';
            more.textContent = '⋯'; more.dataset.cwConversationMore = '';
            more.setAttribute('aria-label', 'Tùy chọn cuộc trò chuyện với ' + it.name);
            more.setAttribute('aria-haspopup','true'); more.setAttribute('aria-expanded','false');
            conversationActions(more,it); holder.append(row,more); listEl.appendChild(holder);
        });
    }

    function loadList() {
        if (isGuest || !listEl) { return Promise.resolve(); }
        var currentList = ++listVersion;
        return api('ajax/hoi-thoai').then(function (res) {
            if (!res.ok || currentList !== listVersion) { return; }
            duLieuList = res.items || [];
            if (dang_mo && dang_mo.kind === 'chat') {
                var peer = duLieuList.find(function (item) { return Number(item.id) === Number(dang_mo.id); });
                if (peer) setPeerActivity(peer);
            }
            setBadge(res.unread || 0);
            veList();

            // Dòng đang mở được tô sáng để biết mình đang ở đâu
            danhDauDangMo();
        }).catch(function () { /* mất mạng thì để nguyên danh sách cũ */ });
    }

    function danhDauDangMo() {
        var rows = sideEl ? sideEl.querySelectorAll('.cw-row') : [];
        Array.prototype.forEach.call(rows, function (r) { r.classList.remove('is-on'); });

        if (root.dataset.activeGroup) {
            var groupRow = sideEl.querySelector('[data-cw-group="' + root.dataset.activeGroup + '"]');
            if (groupRow) groupRow.classList.add('is-on');
            return;
        }

        if (!dang_mo) { return; }
        if (dang_mo.kind === 'room') {
            if (rowRoom) rowRoom.classList.add('is-on');
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
            if (previousMeta && !previousMeta.querySelector('.cw-seen')) previousMeta.hidden = true;
        }
        nguoiCuoi = khoa;

        var wrap = document.createElement('div');
        wrap.dataset.messageId = m.id;
        wrap.dataset.messageType = m.type;
        wrap.className = 'cw-msg' + (m.mine ? ' mine' : '') + (noiTiep ? ' is-cont' : '');
        if (m.recalled) wrap.classList.add('is-recalled');
        wrap.dataset.recalled = m.recalled ? '1' : '0';

        // Avatar chỉ vẽ ở tin đầu nhóm, và chỉ với tin của người khác
        if (!m.mine) {
            var peerAvatar = m.avatar || (dang_mo && dang_mo.kind === 'chat' ? dang_mo.avatar : '');
            if ((!noiTiep || (dang_mo && dang_mo.kind === 'chat')) && peerAvatar) {
                var av = document.createElement('img');
                av.className = 'cw-msg-avatar';
                av.src = peerAvatar; av.alt = '';
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
            bodyEl.querySelectorAll('.cw-seen').forEach(function (label) { label.parentNode.classList.remove('has-seen'); label.remove(); });
            meta.classList.add('has-seen');
            var seen = document.createElement('i');
            seen.className = 'cw-seen';
            seen.textContent = 'Đã xem';
            meta.appendChild(seen);
        }
        col.appendChild(meta);

        wrap.appendChild(col);
        if (window.MessageReply && dang_mo && dang_mo.kind === 'chat' && !m.recalled) {
            window.MessageReply.render(wrap,m.reply,bodyEl);
            var replyButton = document.createElement('button'); replyButton.type = 'button';
            replyButton.className = 'pm-widget-reply'; replyButton.textContent = 'Trả lời';
            replyButton.onclick = function () { window.MessageReply.select(formEl,wrap); };
            col.append(replyButton);
        }
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

    function updateSeen(id) {
        bodyEl.querySelectorAll('.cw-seen').forEach(function (label) { label.parentNode.classList.remove('has-seen'); label.remove(); });
        var message = bodyEl.querySelector('.cw-msg.mine[data-message-id="' + Number(id) + '"]');
        if (!message) return;
        var meta = message.querySelector('.cw-msg-time');
        meta.hidden = false;
        meta.classList.add('has-seen');
        var label = document.createElement('i'); label.className = 'cw-seen'; label.textContent = 'Đã xem'; meta.appendChild(label);
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
        root.dispatchEvent(new CustomEvent('cw:close-group'));
        if (idleEl) { idleEl.hidden = true; }
        convoEl.hidden = false;
        root.classList.add('is-chat');   // màn hẹp: trượt sang khung chat
        jump.reset();
    }

    /** Mở phòng chat chung. */
    function moPhong() {
        delete bodyEl.dataset.pinUrl; delete bodyEl.dataset.recallUrl; delete bodyEl.dataset.conversation;
        var conversationMore = document.getElementById('cw-conversation-more');
        if (conversationMore) conversationMore.hidden = true;
        root.dispatchEvent(new CustomEvent('cw:conversation-change'));
        if (window.MessageReply) window.MessageReply.clear(formEl);
        chatVersion++; bodyEl.dataset.deletedThrough = '0';
        if (historySearch) { historySearch.hidden = true; delete historySearch.dataset.messageHistory; }
        ['cw-voice', 'cw-video'].forEach(function (id) {
            var callButton = document.getElementById(id);
            if (callButton) {
                callButton.hidden = true;
                delete callButton.dataset.voiceCall;
            }
        });
        document.getElementById('cw-avatar-link').hidden = true;
        document.getElementById('cw-avatar-link').removeAttribute('href');
        document.getElementById('cw-profile-link').removeAttribute('href');
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
    function conversationActions(button, info) {
        if (!button) return;
        var endpoint = base + 'ajax/tin-nhan/' + Number(info.id);
        button.dataset.conversationId = String(info.id);
        button.dataset.blockUrl = endpoint + '/chan'; button.dataset.deleteUrl = endpoint + '/xoa-hoi-thoai';
        button.dataset.notificationUrl = endpoint + '/thong-bao';
        button.dataset.blockedByMe = info.blocked_by_me ? '1' : '0';
        button.dataset.notificationsEnabled = info.notifications_enabled === false ? '0' : '1';
        button.dataset.notificationsMuted = info.notifications_enabled === false || info.calls_notifications_enabled === false ? '1' : '0';
        button.dataset.peerId = String(info.user_id || ''); button.dataset.peerName = info.name || '';
        button.dataset.profileUrl = info.profile_url || ''; button.hidden = false;
    }
    function moChat(info) {
        if (window.MessageReply) window.MessageReply.clear(formEl);
        chatVersion++; bodyEl.dataset.deletedThrough = String(deletedThrough[info.id] || 0);
        if (historySearch) {
            historySearch.dataset.messageHistory = base + 'ajax/tin-nhan/' + Number(info.id) + '/tim-kiem';
            historySearch.hidden = false;
        }
        ['cw-voice', 'cw-video'].forEach(function (id) {
            var callButton = document.getElementById(id);
            if (callButton) {
                callButton.dataset.voiceCall = String(info.id);
                callButton.hidden = false;
            }
        });
        var profileUrl = info.profile_url || '';
        var avatarLink = document.getElementById('cw-avatar-link');
        var profileLink = document.getElementById('cw-profile-link');
        avatarLink.hidden = false;
        if (profileUrl) {
            avatarLink.href = profileUrl;
            profileLink.href = profileUrl;
        } else {
            avatarLink.removeAttribute('href');
            profileLink.removeAttribute('href');
        }
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
        setPeerActivity(info);
        if (receiver) { receiver.value = info.user_id || ''; }
        if (inputEl) { inputEl.placeholder = 'Vui lòng nhập tin nhắn'; }

        moKhung();
        bodyEl.dataset.conversation = String(info.id);
        bodyEl.dataset.pinUrl = base + 'ajax/tin-nhan/' + Number(info.id) + '/ghim';
        bodyEl.dataset.recallUrl = base + 'ajax/tin-nhan/' + Number(info.id) + '/thu-hoi';
        conversationActions(document.getElementById('cw-conversation-more'),info);
        root.dispatchEvent(new CustomEvent('cw:conversation-change'));
        danhDauDangMo();
        bodyEl.textContent = '';

        batDauTai();
        if (inputEl) { inputEl.focus(); }
    }

    function setPeerActivity(info) {
        statusEl.textContent = info.online ? 'Đang online' : (info.activity || 'Chưa có thông tin hoạt động');
        statusEl.classList.toggle('is-online', !!info.online);
    }

    /** Quay lại danh sách (màn hẹp). */
    function veDanhSach() {
        if (window.MessageReply) window.MessageReply.clear(formEl);
        chatVersion++;
        root.dispatchEvent(new CustomEvent('cw:close-group'));
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
            if (rtLive() && lastId > 0 && arguments[0] !== true) { return; }
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

        if (rtLive() && lastId > 0 && arguments[0] !== true) { return; }
        var hoi_thoai = dang_mo.id;   // ghim lại để đối chiếu khi câu trả lời về
        var currentChat = chatVersion;
        return api('ajax/tin-nhan/' + hoi_thoai + '?after=' + lastId).then(function (res) {
            if (!res.ok || !dang_mo || dang_mo.kind !== 'chat') { return; }
            if (Number(dang_mo.id) !== Number(hoi_thoai) || currentChat !== chatVersion) { return; }
            var cutoff = Math.max(deletedThrough[hoi_thoai] || 0, Number(bodyEl.dataset.deletedThrough || 0));
            if (Number(res.deleted_through_message_id || 0) < cutoff) return;
            if (window.MessageRecall) window.MessageRecall.clearBefore(bodyEl,res.deleted_through_message_id);
            cutoff = Number(bodyEl.dataset.deletedThrough || 0);
            res.messages = (res.messages || []).filter(function (m) { return Number(m.id) > cutoff; });
            setPeerActivity(res);

            if (lastId === 0) {
                veLai(res.messages);
            } else {
                res.messages.forEach(function (m) { themTin(m); });
            }
            (res.messages || []).forEach(function (m) {
                if (m.id > lastId) { lastId = m.id; }
            });
            lastId = Math.max(lastId,cutoff);

            if (res.messages && res.messages.length) { loadList(); }
            updateSeen(res.latest_seen_id);

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
            var replySnapshot = window.MessageReply && window.MessageReply.capture(formEl);
            var sendingVersion = chatVersion;
            var imageSnapshot = fileEl && fileEl.files[0];
            var url  = dang_mo.kind === 'room' ? 'ajax/phong-chat/gui' : 'ajax/send-message';

            sending = true;
            inputEl.value = '';
            // Tải ảnh lên mất vài giây, phải cho thấy là máy đang làm việc chứ
            // không để người dùng bấm đi bấm lại vì tưởng hỏng.
            formEl.classList.add('is-sending');

            api(url, { method: 'POST', body: data }).then(function (res) {
                formEl.classList.remove('is-sending');
                if (sendingVersion !== chatVersion) return;
                if (!res.ok) {
                    if (res.image_sent) {
                        if (fileEl && fileEl.files[0] === imageSnapshot) fileEl.value = '';
                        tai(true); loadList();
                    }
                    if (!inputEl.value) inputEl.value = text;
                    if (window.appNeed && window.appNeed(res)) { return; }
                    if (window.appModal) {
                        window.appModal({ type: 'error', title: 'Không gửi được', message: res.message });
                    }
                    return;
                }
                if (window.MessageReply) window.MessageReply.sent(formEl,replySnapshot);
                if (fileEl && fileEl.files[0] === imageSnapshot) fileEl.value = '';
                tai();
                loadList();
                loadRoomSummary();
            }).catch(function () {
                formEl.classList.remove('is-sending');
                if (sendingVersion !== chatVersion) return;
                if (!inputEl.value) inputEl.value = text;
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
            root.dispatchEvent(new CustomEvent('cw:close-group'));
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
        // A modal above the chat handles its own close button and backdrop.
        // Keep the underlying conversation open until the modal is dismissed.
        if (document.querySelector('dialog[open]')) return;
        // Fallback chat menus are moved to document.body.
        var menu = e.target.closest('.cg-popover-open');
        if (menu && menu.cgTrigger && root.contains(menu.cgTrigger)) return;
        if (e.target.closest('.pm-message-menu, .conversation-delete-menu, .ig-row-menu')) return;

        if (!panel.hidden && !root.contains(e.target)) { dongChat(); }
    });

    if (rowRoom) { rowRoom.addEventListener('click', moPhong); }

    root.addEventListener('cw:close-group', function () {
        delete root.dataset.activeGroup;
        danhDauDangMo();
    });
    root.addEventListener('cw:open-group', function (event) {
        root.dataset.activeGroup = String(event.detail.id);
        closeOnline(false);
        clearInterval(timer);
        dang_mo = null;
        convoEl.hidden = true;
        if (idleEl) idleEl.hidden = true;
        root.classList.add('is-chat');
        danhDauDangMo();
    });
    root.addEventListener('cw:group-back', veDanhSach);

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

    bodyEl.addEventListener('conversation-history-cleared',function (event) {
        if (!dang_mo || dang_mo.kind !== 'chat') return;
        deletedThrough[dang_mo.id] = Math.max(deletedThrough[dang_mo.id] || 0,event.detail.cutoff);
        renderedIds.forEach(function (id) { if (Number(id) <= event.detail.cutoff) renderedIds.delete(id); });
        convoEl.querySelectorAll('.message-history-dialog[open]').forEach(function (dialog) { dialog.close(); });
    });
    document.addEventListener('private-conversation-deleted',function (event) {
        var id = Number(event.detail.id), cutoff = Number(event.detail.deleted_through_message_id || 0);
        deletedThrough[id] = Math.max(deletedThrough[id] || 0,cutoff);
        listVersion++;
        duLieuList = duLieuList.filter(function (item) { return Number(item.id) !== id; });
        veList();
        if (dang_mo && dang_mo.kind === 'chat' && Number(dang_mo.id) === id) {
            convoEl.querySelectorAll('.message-history-dialog[open]').forEach(function (dialog) { dialog.close(); });
            bodyEl.textContent = ''; bodyEl.dataset.deletedThrough = String(cutoff);
            renderedIds.clear(); lastId = 0; ngayCuoi = null; nguoiCuoi = null;
            veDanhSach();
        } else loadList();
    });

    /* ------------------------- Realtime ------------------------- */

    if (RT) {
        RT.on('chat.notification-preference',function (msg) {
            document.dispatchEvent(new CustomEvent('private-conversation-notifications',{
                detail:Object.assign({},msg,{id:msg.conversationId})}));
        });
        RT.on('chat.deleted',function (msg) {
            document.dispatchEvent(new CustomEvent('private-conversation-deleted',{detail:{id:msg.conversationId,
                deleted_through_message_id:msg.deleted_through_message_id}}));
        });
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

        RT.on('chat.seen', function (msg) {
            if (dang_mo && dang_mo.kind === 'chat' && Number(dang_mo.id) === Number(msg.conversationId)) tai(true);
        });

        RT.on('chat.message', function (msg) {
            if (Number(msg.message.id) <= (deletedThrough[msg.conversationId] || 0)) return;
            if (dang_mo && dang_mo.kind === 'chat' && Number(dang_mo.id) === Number(msg.conversationId)) {
                themTin(msg.message);
                if (msg.message.id > lastId) { lastId = msg.message.id; }
                RT.send({ t: 'chat.read', conversationId: dang_mo.id });
            }
            loadList();
        });
    }

    if (window.MessageReply) window.MessageReply.mount(bodyEl,formEl,function () {
        return dang_mo && dang_mo.kind === 'chat' ? String(dang_mo.id) : null;
    });
    if (window.MessageRecall) {
        window.MessageRecall.watch(bodyEl, function () {
            return !panel.hidden && dang_mo && dang_mo.kind === 'chat'
                ? base + 'ajax/tin-nhan/' + Number(dang_mo.id) + '/thu-hoi' : null;
        }, loadList);
    }

    /* Số chưa đọc và tóm tắt phòng chung lấy ngay khi vào trang */
    loadRoomSummary();
    if (!isGuest) {
        loadList();
        document.addEventListener('dating-contact-added', loadList);
        document.addEventListener('private-message-recalled', loadList);
        document.addEventListener('private-conversation-notifications',function (event) {
            listVersion++;
            duLieuList.forEach(function (item) {
                if (Number(item.id) === Number(event.detail.id)) {
                    item.notifications_enabled = event.detail.notifications_enabled;
                    if(typeof event.detail.calls_notifications_enabled==='boolean') item.calls_notifications_enabled=event.detail.calls_notifications_enabled;
                }
            });
            veList();
        });
    }
})();
