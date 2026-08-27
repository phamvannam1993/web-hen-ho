/* Tương tác frontend: modal thông báo, lấy mã pass, hiện số điện thoại. */console.log('🔴 DEBUG: app.js đã được load - Phiên bản mới nhất!');
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
        el.addEventListener('click', function (e) { if (e.target === el) { close(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
        return el;
    }

    /**
     * showModal({title, message, type, code})           -> thông báo, một nút
     * showModal({..., onConfirm: fn, confirmText, danger}) -> hỏi xác nhận, hai nút
     */
    function showModal(opts) {
        var el = ensureModal();
        var type = opts.type || 'info';
        var icons = { success: '✓', error: '!', info: 'i' };

        el.querySelector('.modal-icon').textContent = icons[type] || 'i';
        el.querySelector('.modal-icon').className = 'modal-icon ' + type;
        el.querySelector('.modal-title').textContent = opts.title || '';

        var body = el.querySelector('.modal-body');
        body.textContent = '';
        var p = document.createElement('p');
        p.textContent = opts.message || '';
        body.appendChild(p);
        if (opts.code) {
            var codeBox = document.createElement('div');
            codeBox.className = 'modal-code';
            codeBox.textContent = opts.code;
            body.appendChild(codeBox);
        }

        // Dựng lại vùng nút để bỏ trình xử lý của lần mở trước
        var actions = el.querySelector('.modal-actions');
        actions.textContent = '';

        if (typeof opts.onConfirm === 'function') {
            var cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'btn btn-ghost';
            cancel.textContent = 'Huỷ';
            cancel.addEventListener('click', function () { el.classList.remove('open'); });

            var ok = document.createElement('button');
            ok.type = 'button';
            ok.className = 'btn ' + (opts.danger ? 'btn-danger' : 'btn-primary');
            ok.textContent = opts.confirmText || 'Đồng ý';
            ok.addEventListener('click', function () {
                el.classList.remove('open');
                opts.onConfirm();
            });

            actions.appendChild(cancel);
            actions.appendChild(ok);
        } else {
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-primary modal-ok';
            close.textContent = 'Đã hiểu';
            close.addEventListener('click', function () { el.classList.remove('open'); });
            actions.appendChild(close);
        }

        el.classList.add('open');
    }

    window.appModal = showModal;

    /* Nút có data-confirm: hỏi bằng modal thay cho hộp thoại của trình duyệt */
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) { return; }

        e.preventDefault();
        showModal({
            type: 'error',
            title: 'Xác nhận',
            message: trigger.getAttribute('data-confirm'),
            danger: trigger.hasAttribute('data-confirm-danger'),
            confirmText: trigger.hasAttribute('data-confirm-danger') ? 'Xoá' : 'Đồng ý',
            onConfirm: function () {
                if (trigger.tagName === 'A') {
                    window.location.href = trigger.href;
                    return;
                }
                var form = trigger.form || trigger.closest('form');
                if (form) {
                    trigger.removeAttribute('data-confirm');
                    trigger.click();
                }
            }
        });
    });

    /* ------------------------- Menu mobile: ngăn kéo ------------------------- */

    (function () {
        var toggle  = document.getElementById('nav-toggle');
        var drawer  = document.getElementById('nav-drawer');
        var overlay = document.getElementById('nav-overlay');
        var closeBtn = document.getElementById('drawer-close');
        if (!toggle || !drawer || !overlay) { return; }

        var MOBILE = 900;   // khớp với ngưỡng @media trong CSS

        function isMobile() { return window.innerWidth <= MOBILE; }

        function openDrawer() {
            drawer.classList.add('open');
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            overlay.hidden = false;
            // đợi một khung hình để hiệu ứng mờ dần chạy được
            requestAnimationFrame(function () { overlay.classList.add('show'); });
            document.body.classList.add('drawer-open');
            // nâng header lên trên các lớp nổi khác (bong bóng chat…)
            var hdr = document.querySelector('.site-header');
            if (hdr) { hdr.classList.add('drawer-active'); }
        }

        function closeDrawer() {
            drawer.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            overlay.classList.remove('show');
            document.body.classList.remove('drawer-open');
            var hdrOff = document.querySelector('.site-header');
            if (hdrOff) { hdrOff.classList.remove('drawer-active'); }
            // ẩn hẳn sau khi hiệu ứng kết thúc để không chắn thao tác
            setTimeout(function () {
                if (!drawer.classList.contains('open')) { overlay.hidden = true; }
            }, 280);
            // thu gọn mọi danh mục con đang mở
            document.querySelectorAll('.has-mega.open').forEach(function (li) {
                li.classList.remove('open');
            });
        }

        toggle.addEventListener('click', function () {
            if (drawer.classList.contains('open')) { closeDrawer(); } else { openDrawer(); }
        });

        overlay.addEventListener('click', closeDrawer);
        if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('open')) { closeDrawer(); }
        });

        // Bấm vào một mục menu thường thì đóng ngăn kéo rồi mới chuyển trang
        drawer.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile() && !link.parentNode.classList.contains('has-mega')) {
                    closeDrawer();
                }
            });
        });

        // Mục có danh mục con: bấm để xổ ra thay vì chuyển trang ngay
        document.querySelectorAll('.has-mega > a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (!isMobile()) { return; }
                e.preventDefault();
                var li = link.parentNode;
                // mở mục này thì đóng các mục khác cho gọn
                document.querySelectorAll('.has-mega.open').forEach(function (other) {
                    if (other !== li) { other.classList.remove('open'); }
                });
                li.classList.toggle('open');
            });
        });

        // Xoay ngang hoặc phóng to cửa sổ qua ngưỡng desktop thì trả về trạng thái thường
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (!isMobile() && drawer.classList.contains('open')) { closeDrawer(); }
            }, 150);
        });
    })();

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

    /* --- Thích thành viên --- */
    document.querySelectorAll('[data-like-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            post(base + 'ajax/like', { type: 'user', id: btn.getAttribute('data-like-user') })
                .then(function (res) {
                    if (!res.ok) {
                        return showModal({ type: 'error', title: 'Không thực hiện được', message: res.message });
                    }
                    var lbl = btn.querySelector('.js-like-text');
                    if (lbl) { lbl.textContent = res.liked ? 'Đã thích' : 'Thích'; }
                    else { btn.textContent = res.liked ? 'Đã thích' : 'Thích'; }
                    btn.classList.toggle('is-liked', !!res.liked);
                    // Thích / bỏ thích không cần báo gì thêm, trạng thái nút đã đủ rõ.
                    // Riêng ghép đôi thì vẫn báo vì đó là việc đáng chú ý.
                    if (res.matched) {
                        showModal({
                            type: 'success', title: 'Ghép đôi thành công!',
                            message: 'Hai bạn đã thích nhau. Vào mục Tin nhắn để bắt đầu trò chuyện.'
                        });
                    }
                });
        });
    });

    /* --- Trang Khám phá: chồng thẻ vuốt --- */
    var stage = document.getElementById('sw-stage');
    var deck  = document.getElementById('sw-deck');

    if (stage) {
        var laKhach = stage.getAttribute('data-guest') === '1';

        /* Bộ lọc nhanh */
        var hopLoc = document.getElementById('sw-filter');
        function moLoc()  { if (hopLoc) { hopLoc.hidden = false; } }
        function dongLoc() { if (hopLoc) { hopLoc.hidden = true; } }
        var nutLoc = document.getElementById('sw-filter-btn');
        if (nutLoc) { nutLoc.addEventListener('click', moLoc); }
        var nutLoc2 = document.getElementById('sw-open-filter');
        if (nutLoc2) { nutLoc2.addEventListener('click', moLoc); }
        if (hopLoc) {
            hopLoc.addEventListener('click', function (e) {
                if (e.target === hopLoc || e.target.closest('[data-close-filter]')) { dongLoc(); }
            });
        }

        /* Hộp chúc mừng ghép đôi */
        var hopMatch = document.getElementById('sw-match');
        if (hopMatch) {
            hopMatch.addEventListener('click', function (e) {
                if (e.target.closest('[data-close-match]')) { hopMatch.hidden = true; }
            });
        }
        function khoeMatch(res) {
            if (!hopMatch || !res.partner) { return; }
            document.getElementById('sw-match-me').src  = res.me_avatar || '';
            document.getElementById('sw-match-you').src = res.partner.avatar || '';
            document.getElementById('sw-match-name').textContent = res.partner.name || '';
            hopMatch.hidden = false;
        }

        /* Xé đôi thẻ khi bỏ qua: dựng hai nửa từ chính ảnh của thẻ đó */
        function xeThe(card) {
            var img = card.querySelector('.sw-photo img');
            if (!img) { return; }
            var r = card.getBoundingClientRect();

            var lop = document.createElement('div');
            lop.className = 'sw-tear';
            lop.style.left   = r.left + 'px';
            lop.style.top    = r.top + 'px';
            lop.style.width  = r.width + 'px';
            lop.style.height = r.height + 'px';
            lop.innerHTML = '<div class="sw-tear-half tren"></div><div class="sw-tear-half duoi"></div>';

            var nen = 'url("' + img.currentSrc + '")';
            lop.querySelectorAll('.sw-tear-half').forEach(function (n) { n.style.backgroundImage = nen; });

            document.body.appendChild(lop);
            setTimeout(function () { lop.remove(); }, 700);
        }

        /* Trái tim bung ra giữa màn hình khi bấm thích */
        function timBung() {
            var el = document.createElement('div');
            el.className = 'sw-burst';
            el.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>';
            var r = deck ? deck.getBoundingClientRect() : { left: 0, top: 0, width: window.innerWidth, height: window.innerHeight };
            el.style.left = (r.left + r.width / 2 - 21) + 'px';
            el.style.top  = (r.top + r.height / 2 - 21) + 'px';
            document.body.appendChild(el);
            setTimeout(function () { el.remove(); }, 900);
        }

        /* Khách bấm thích: mời đăng nhập thay vì gọi máy chủ */
        function moiDangNhap() {
            showModal({
                type: 'info',
                title: 'Cần đăng nhập',
                message: 'Đăng nhập hoặc tạo tài khoản để gửi lượt thích và nhắn tin.',
                confirmText: 'Đăng nhập',
                onConfirm: function () { window.location.href = stage.getAttribute('data-login'); }
            });
        }

        if (deck) {

            var NGUONG = 110;
            var dangBan = false;
            var keo = null;

            var demEl    = document.getElementById('sw-count');
            var trongEl  = document.getElementById('sw-empty');
            var dieuKhien = document.getElementById('sw-controls');

            /* Thẻ đang xem là thẻ nằm ngay giữa khung cuộn */
            function theTrenCung() {
                var cac = deck.querySelectorAll('.sw-card');
                var giua = deck.getBoundingClientRect().top + deck.clientHeight / 2;
                for (var i = 0; i < cac.length; i++) {
                    var r = cac[i].getBoundingClientRect();
                    if (r.top <= giua && r.bottom >= giua) { return cac[i]; }
                }
                return cac[cac.length - 1] || null;
            }

            /* Sau khi bỏ một thẻ, đưa khung về đúng thẻ kế tiếp */
            function veTheKeTiep(sau) {
                var ke = sau && sau.nextElementSibling ? sau.nextElementSibling : deck.querySelector('.sw-card');
                if (ke) { deck.scrollTop = ke.offsetTop; }
            }

            function datViTri(card, dx, dy) {
                card.style.transform = 'translate(' + dx + 'px,' + dy + 'px) rotate(' + (dx / 18) + 'deg)';
                var muc  = Math.min(Math.abs(dx) / NGUONG, 1);
                var like = card.querySelector('.sw-stamp-like');
                var nope = card.querySelector('.sw-stamp-nope');
                if (like) { like.style.opacity = dx > 0 ? muc : 0; }
                if (nope) { nope.style.opacity = dx < 0 ? muc : 0; }
            }

            function traVe(card) {
                card.classList.remove('is-dragging');
                card.classList.add('is-animating');
                card.style.transform = '';
                var like = card.querySelector('.sw-stamp-like');
                var nope = card.querySelector('.sw-stamp-nope');
                if (like) { like.style.opacity = 0; }
                if (nope) { nope.style.opacity = 0; }
                setTimeout(function () { card.classList.remove('is-animating'); }, 340);
            }

            function doiDem(delta) {
                if (!demEl) { return; }
                var n = parseInt(demEl.textContent.replace(/\D/g, ''), 10) || 0;
                demEl.textContent = Math.max(0, n + delta).toLocaleString('vi-VN');
            }

            function kiemTraHet() {
                if (deck.querySelector('.sw-card')) { return; }
                if (trongEl)   { trongEl.hidden = false; }
                if (dieuKhien) { dieuKhien.hidden = true; }
                deck.hidden = true;
            }

            function chon(card, huong) {
                if (!card || dangBan) { return; }
                if (laKhach) { return moiDangNhap(); }
                dangBan = true;

                var id = card.getAttribute('data-user');
                var xa = (huong === 'right' ? 1 : -1) * (window.innerWidth + 340);

                if (huong === 'right') {
                    timBung();
                    card.classList.remove('is-dragging');
                    card.classList.add('is-animating', 'is-gone');
                    card.style.transform = 'translate(' + xa + 'px,-40px) rotate(' + (xa / 26) + 'deg)';
                } else {
                    // Bỏ qua: thẻ biến mất ngay, thay bằng hai nửa bị xé trượt đi
                    xeThe(card);
                    card.style.visibility = 'hidden';
                }

                var url = base + (huong === 'right' ? 'kham-pha/thich/' : 'kham-pha/bo-qua/') + id;
                post(url, {}).then(function (res) {
                    if (!res.ok) {
                        dangBan = false;
                        card.classList.remove('is-gone');
                        traVe(card);
                        if (res.need_login) { return moiDangNhap(); }
                        return showModal({ type: 'error', title: 'Không thực hiện được', message: res.message });
                    }
                    if (res.matched) { khoeMatch(res); }
                }).catch(function () {});

                setTimeout(function () {
                    var ke = card.nextElementSibling;
                    card.remove();
                    if (ke) { deck.scrollTop = ke.offsetTop; }
                    doiDem(-1);
                    dangBan = false;
                    kiemTraHet();
                }, huong === 'right' ? 340 : 200);
            }

            /* ---- Kéo bằng chuột / ngón tay ---- */
            deck.addEventListener('pointerdown', function (e) {
                if (e.target.closest('a, button, .sw-album, .sw-detail')) { return; }
                var card = theTrenCung();
                if (!card || dangBan) { return; }
                keo = { card: card, x0: e.clientX, y0: e.clientY, id: e.pointerId, ngang: null };
            });

            deck.addEventListener('pointermove', function (e) {
                if (!keo || e.pointerId !== keo.id) { return; }
                var dx = e.clientX - keo.x0, dy = e.clientY - keo.y0;

                // Quyết định một lần: kéo ngang là chọn, kéo dọc là cuộn xem chi tiết
                if (keo.ngang === null) {
                    if (Math.abs(dx) < 8 && Math.abs(dy) < 8) { return; }
                    keo.ngang = Math.abs(dx) > Math.abs(dy);
                    if (keo.ngang) {
                        keo.card.classList.add('is-dragging');
                        keo.card.setPointerCapture(e.pointerId);
                    } else {
                        keo = null;   // nhường cho vùng cuộn bên trong thẻ
                        return;
                    }
                }
                datViTri(keo.card, dx, dy * 0.3);
            });

            function thaTay(e) {
                if (!keo || (e && e.pointerId !== keo.id)) { return; }
                var card = keo.card, ngang = keo.ngang;
                var dx = e ? e.clientX - keo.x0 : 0;
                keo = null;
                if (!ngang) { return; }
                if (dx > NGUONG)       { chon(card, 'right'); }
                else if (dx < -NGUONG) { chon(card, 'left'); }
                else                   { traVe(card); }
            }
            deck.addEventListener('pointerup', thaTay);
            deck.addEventListener('pointercancel', thaTay);

            /* ---- Mở / đóng phần chi tiết ---- */
            deck.addEventListener('click', function (e) {
                if (e.target.closest('[data-scroll-more]')) {
                    e.target.closest('.sw-card').classList.add('is-open');
                    return;
                }
                if (e.target.closest('[data-close-detail]')) {
                    var card = e.target.closest('.sw-card');
                    card.classList.remove('is-open');
                    card.querySelector('.sw-detail').scrollTop = 0;
                }
            });

            /* ---- Nút điều khiển ---- */
            if (dieuKhien) {
                dieuKhien.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-swipe]');
                    if (!btn) { return; }
                    var huong = btn.getAttribute('data-swipe');
                    var card  = theTrenCung();

                    if (huong === 'info') {
                        if (!card) { return; }
                        card.classList.toggle('is-open');
                        return;
                    }

                    if (huong === 'undo') {
                        if (laKhach) { return moiDangNhap(); }
                        post(base + 'kham-pha/xem-lai', {}).then(function (res) {
                            if (!res.ok) {
                                return showModal({ type: 'info', title: 'Chưa có gì để xem lại', message: res.message });
                            }
                            deck.hidden = false;
                            if (trongEl)   { trongEl.hidden = true; }
                            if (dieuKhien) { dieuKhien.hidden = false; }
                            deck.insertAdjacentHTML('beforeend', res.html);
                            doiDem(1);
                        });
                        return;
                    }

                    chon(card, huong);
                });
            }

            /* ---- Phím tắt ---- */
            document.addEventListener('keydown', function (e) {
                if (e.target.matches('input, textarea, select')) { return; }
                if (hopMatch && !hopMatch.hidden) { return; }
                var card = theTrenCung();
                if (e.key === 'ArrowRight') { chon(card, 'right'); }
                if (e.key === 'ArrowLeft')  { chon(card, 'left'); }
                if (e.key === 'ArrowUp' && card) {
                    e.preventDefault();
                    card.classList.add('is-open');
                }
                if (e.key === 'ArrowDown' && card) {
                    e.preventDefault();
                    if (card.classList.contains('is-open')) { card.classList.remove('is-open'); }
                    else { veTheKeTiep(card); }
                }
            });

            kiemTraHet();
        }
    }

    /* --- Thẻ hồ sơ: nút Bỏ qua / Thích (trang chủ, tìm kiếm, khu vực) --- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-card-action]');
        if (!btn) { return; }

        var card = btn.closest('.pcard');
        var id = card.getAttribute('data-user');
        var action = btn.getAttribute('data-card-action');
        btn.disabled = true;

        var url = action === 'like'
            ? base + 'kham-pha/thich/' + id
            : base + 'kham-pha/bo-qua/' + id;

        post(url, {}).then(function (res) {
            if (!res.ok) {
                btn.disabled = false;
                return showModal({ type: 'error', title: 'Không thực hiện được', message: res.message });
            }

            if (action === 'pass') {
                // Bỏ qua thì ẩn hẳn thẻ khỏi danh sách
                card.classList.add('card-gone');
                setTimeout(function () { card.remove(); }, 280);
                return;
            }

            var lbl = btn.querySelector('.js-like-text');
            if (lbl) { lbl.textContent = res.liked ? 'Đã thích' : 'Thích'; }
            btn.classList.toggle('is-liked', !!res.liked);
            btn.disabled = false;
            if (res.matched) {
                showModal({
                    type: 'success', title: 'Ghép đôi thành công!',
                    message: 'Hai bạn đã thích nhau. Vào mục Tin nhắn để bắt đầu trò chuyện.'
                });
            }
        });
    });

    /* --- Nút chọn dạng viên thuốc (sở thích, lối sống): bật/tắt trạng thái --- */
    document.querySelectorAll('.pick input').forEach(function (input) {
        input.addEventListener('change', function () {
            var box = input.closest('.pick');
            if (input.type === 'radio' && input.name) {
                document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (other) {
                    if (other.closest('.pick')) { other.closest('.pick').classList.remove('on'); }
                });
            }
            box.classList.toggle('on', input.checked);
        });
    });

    /* --- Trang Hẹn hò: mở rộng / thu gọn đoạn giới thiệu --- */
    var seoToggle = document.getElementById('seo-toggle');
    if (seoToggle) {
        var seoText = document.getElementById('seo-text');
        seoToggle.addEventListener('click', function () {
            var open = seoText.classList.toggle('open');
            seoToggle.textContent = open ? 'Thu gọn' : 'Xem thêm';
            seoToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* --- Bộ lọc tìm kiếm: nút mở/đóng trên màn hình hẹp --- */
    var filterToggle = document.getElementById('filter-toggle');
    if (filterToggle) {
        var layout = document.querySelector('.search-layout');
        // Mở sẵn nếu người dùng vừa lọc xong, để họ thấy điều kiện đang đặt
        if (location.search.replace(/[?&](view|sort|page)=[^&]*/g, '').replace(/^[?&]/, '')) {
            layout.classList.add('filters-open');
            filterToggle.setAttribute('aria-expanded', 'true');
        }
        filterToggle.addEventListener('click', function () {
            var open = layout.classList.toggle('filters-open');
            filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                layout.querySelector('.filter-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    /* --- Bộ lọc tìm kiếm: gập/mở từng nhóm --- */
    document.querySelectorAll('[data-toggle-group]').forEach(function (head) {
        head.addEventListener('click', function () {
            var body = head.nextElementSibling;
            var closed = head.classList.toggle('is-closed');
            if (body) { body.hidden = closed; }
        });
    });

    /* --- Báo cáo thành viên: modal chọn lý do --- */

    var REPORT_REASONS = [
        ['lua_dao',      'Lừa đảo, xin tiền'],
        ['noi_dung_xau', 'Ảnh hoặc nội dung phản cảm'],
        ['mao_danh',     'Mạo danh người khác'],
        ['spam',         'Spam, quảng cáo'],
        ['khac',         'Lý do khác']
    ];

    function openReportModal(userId) {
        var el = ensureModal();

        el.querySelector('.modal-icon').textContent = '!';
        el.querySelector('.modal-icon').className = 'modal-icon error';
        el.querySelector('.modal-title').textContent = 'Báo cáo thành viên';

        var body = el.querySelector('.modal-body');
        body.textContent = '';

        var intro = document.createElement('p');
        intro.textContent = 'Chọn lý do báo cáo. Ban quản trị sẽ xem xét và xử lý.';
        body.appendChild(intro);

        var list = document.createElement('div');
        list.className = 'report-reasons';
        REPORT_REASONS.forEach(function (item, i) {
            var label = document.createElement('label');
            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'report_reason';
            radio.value = item[0];
            if (i === 0) { radio.checked = true; }
            var text = document.createElement('span');
            text.textContent = item[1];
            label.appendChild(radio);
            label.appendChild(text);
            list.appendChild(label);
        });
        body.appendChild(list);

        var note = document.createElement('textarea');
        note.className = 'report-note';
        note.rows = 3;
        note.placeholder = 'Mô tả thêm (không bắt buộc)…';
        body.appendChild(note);

        var actions = el.querySelector('.modal-actions');
        actions.textContent = '';

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-ghost';
        cancel.textContent = 'Huỷ';
        cancel.addEventListener('click', function () { el.classList.remove('open'); });

        var send = document.createElement('button');
        send.type = 'button';
        send.className = 'btn btn-danger';
        send.textContent = 'Gửi báo cáo';
        send.addEventListener('click', function () {
            var chosen = list.querySelector('input:checked');
            send.disabled = true;
            send.textContent = 'Đang gửi…';

            post(base + 'ajax/report', {
                target_type: 'user',
                target_id: userId,
                reason: chosen ? chosen.value : 'khac',
                note: note.value
            }).then(function (res) {
                el.classList.remove('open');
                showModal({
                    type: res.ok ? 'success' : 'error',
                    title: res.ok ? 'Đã gửi báo cáo' : 'Không gửi được',
                    message: res.message
                });
            }).catch(function () {
                el.classList.remove('open');
                showModal({ type: 'error', title: 'Lỗi kết nối', message: 'Vui lòng thử lại sau.' });
            });
        });

        actions.appendChild(cancel);
        actions.appendChild(send);
        el.classList.add('open');
    }

    document.querySelectorAll('[data-report-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openReportModal(btn.getAttribute('data-report-user'));
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
            post(base + 'profile/' + slug + '/lay-pass', {}).then(function (res) {
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
            post(base + 'profile/' + slug + '/mo-lien-he', {
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
