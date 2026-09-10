/**
 * Khay thông báo xổ xuống từ chuông trên thanh đầu trang.
 *
 * Danh sách tải bằng AJAX lúc mở lần đầu, mở lại thì tải lại cho mới.
 * Mở khay là đánh dấu đã đọc luôn, giống cách các mạng xã hội vẫn làm.
 */
(function () {
    'use strict';

    var wrap = document.getElementById('hd-noti');
    if (!wrap) { return; }   // khách chưa đăng nhập thì không có chuông

    // Trang không có thẻ <base>, nên lấy đường dẫn gốc do PHP gắn sẵn:
    // như vậy chạy đúng cả khi site đặt trong thư mục con.
    var base   = wrap.getAttribute('data-base') || '/';
    var toggle = document.getElementById('noti-toggle');
    var panel  = document.getElementById('noti-panel');
    var list   = document.getElementById('noti-list');
    var badge  = document.getElementById('noti-badge');

    /* Biểu tượng theo loại thông báo, khớp với cột type trong bảng */
    var ICONS = {
        like:          '<path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/>',
        match:         '<path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/>',
        message:       '<path d="M4 5.5h16v11H9.5L5.5 20v-3.5H4z"/>',
        post_approved: '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 12.2l2.4 2.4 4.6-5"/>',
        system:        '<path d="M18 16.5V11a6 6 0 1 0-12 0v5.5L4.5 18.5h15z"/><path d="M10 21.2a2.2 2.2 0 0 0 4 0"/>'
    };

    function setBadge(n) {
        if (!badge) { return; }
        badge.textContent = n > 99 ? '99+' : n;
        badge.hidden = n <= 0;
    }

    function veDanhSach(items) {
        list.textContent = '';

        if (!items.length) {
            var trong = document.createElement('p');
            trong.className = 'noti-empty';
            trong.textContent = 'Chưa có thông báo nào.';
            list.appendChild(trong);
            return;
        }

        items.forEach(function (n) {
            // Có đường dẫn thì cả dòng là một liên kết, không thì là một khối tĩnh
            var row = document.createElement(n.url ? 'a' : 'div');
            row.className = 'noti-item' + (n.unread ? ' is-unread' : '');
            if (n.url) {
                // Bản ghi cũ có thể lưu đường dẫn tương đối; ghép với base để
                // không bị hiểu lệch khi đang đứng ở trang lồng nhiều cấp.
                row.href = /^(https?:)?\/\//.test(n.url) || n.url.charAt(0) === '/'
                    ? n.url : base + n.url.replace(/^\.?\//, '');
            }

            var ic = document.createElement('span');
            ic.className = 'noti-item-ic is-' + (n.type || 'system');
            ic.innerHTML = '<svg viewBox="0 0 24 24">' + (ICONS[n.type] || ICONS.system) + '</svg>';

            var text = document.createElement('span');
            text.className = 'noti-item-text';

            var b = document.createElement('b');
            b.textContent = n.title || '';
            text.appendChild(b);

            if (n.body) {
                var p = document.createElement('p');
                p.textContent = n.body;
                text.appendChild(p);
            }

            var t = document.createElement('small');
            t.textContent = n.time || '';
            text.appendChild(t);

            row.appendChild(ic);
            row.appendChild(text);
            if (n.unread) {
                var dot = document.createElement('i');
                dot.className = 'noti-item-dot';
                row.appendChild(dot);
            }
            list.appendChild(row);
        });
    }

    /** Hiện một dòng chữ giữa khay thay cho danh sách. */
    function baoLoi(chu) {
        list.textContent = '';
        var p = document.createElement('p');
        p.className = 'noti-empty';
        p.textContent = chu;
        list.appendChild(p);
    }

    function tai() {
        list.textContent = '';
        var dangTai = document.createElement('p');
        dangTai.className = 'noti-empty';
        dangTai.textContent = 'Đang tải…';
        list.appendChild(dangTai);

        return fetch(base + 'ajax/thong-bao', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                // Máy chủ báo hỏng (hết phiên đăng nhập chẳng hạn) thì phải nói
                // ra, chứ để nguyên chữ "Đang tải…" là treo luôn ở đó.
                if (!res.ok) {
                    return baoLoi(res.message || 'Không tải được thông báo.');
                }
                veDanhSach(res.items);

                // Mở khay coi như đã xem: xoá huy hiệu và ghi nhận ở máy chủ
                if (res.unread > 0) { danhDauDaDoc(); }
                else { setBadge(0); }
            })
            .catch(function () {
                baoLoi('Không tải được thông báo. Thử lại sau nhé.');
            });
    }

    function danhDauDaDoc() {
        setBadge(0);
        fetch(base + 'ajax/thong-bao/doc-het', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).catch(function () { /* hỏng mạng thì thôi, lần sau mở lại vẫn đúng */ });
    }

    function mo() {
        panel.hidden = false;
        wrap.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        tai();   // luôn tải lại để không hiện danh sách cũ
    }
    function dong() {
        panel.hidden = true;
        wrap.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.hidden) { mo(); } else { dong(); }
    });

    document.getElementById('noti-close').addEventListener('click', dong);
    document.getElementById('noti-readall').addEventListener('click', function () {
        danhDauDaDoc();
        list.querySelectorAll('.noti-item.is-unread').forEach(function (row) {
            row.classList.remove('is-unread');
            var dot = row.querySelector('.noti-item-dot');
            if (dot) { dot.remove(); }
        });
    });

    // Bấm ra ngoài hoặc nhấn Esc thì đóng
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', dong);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { dong(); toggle.focus(); }
    });
})();
