(function () {
    'use strict';
    var dialog = document.getElementById('profile-preview'); if (!dialog) return;
    var base = dialog.dataset.base.replace(/\/?$/, '/'), body = dialog.querySelector('[data-preview-body]');
    var status = dialog.querySelector('[data-preview-status]'), buy = dialog.querySelector('[data-preview-buy]');
    var current = null, sequence = 0, busy = false;
    function node(tag, text, cls) { var n = document.createElement(tag); if (text !== undefined) n.textContent = text; if (cls) n.className = cls; return n; }
    function close() { if (busy) return; sequence++; dialog.close(); }
    dialog.querySelector('[data-preview-close]').onclick = close;
    dialog.querySelector('[data-preview-cancel]').onclick = close;
    dialog.addEventListener('cancel', function (event) { if (busy) event.preventDefault(); else sequence++; });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) { var b = dialog.getBoundingClientRect(); if (event.clientX < b.left || event.clientX > b.right || event.clientY < b.top || event.clientY > b.bottom) close(); } });
    async function json(response) { var data = await response.json(); if (!response.ok || !data.ok) { var error = new Error(data.message || 'Chưa tải được hồ sơ.'); error.code = response.status; throw error; } return data; }
    async function load(slug, message) {
        if (dialog.dataset.selfId !== '0' && (String(slug) === dialog.dataset.selfId || (dialog.dataset.selfSlug && String(slug) === dialog.dataset.selfSlug))) {
            location.assign(base + 'user/' + encodeURIComponent(dialog.dataset.selfSlug || dialog.dataset.selfId));
            return;
        }
        var turn = ++sequence; current = null; buy.disabled = true; body.replaceChildren(); status.textContent = 'Đang tải…';
        if (!dialog.open) dialog.showModal();
        try {
            var data = await fetch(base + 'user/' + encodeURIComponent(slug) + '/preview', {credentials:'same-origin',cache:'no-store'}).then(json);
            if (turn !== sequence || !dialog.open) return;
            current = data; var p = data.preview, image = node('img', undefined, 'profile-preview-avatar'); image.src = p.avatar; image.alt = '';
            var title = node('h2', p.name); title.id = 'profile-preview-title';
            var header = node('div', undefined, 'profile-preview-person'), identity = node('div');
            identity.append(title,node('p',[p.age ? p.age + ' tuổi' : '',p.province].filter(Boolean).join(' · '),'profile-preview-meta'));
            if (p.job) identity.append(node('p',p.job,'profile-preview-job'));
            header.append(image,identity); body.append(header);
            if (p.bio) body.append(node('p',p.bio,'profile-preview-bio'));
            var entitlement = p.purchased ? '✓ Đã mở · Xem lại miễn phí' : p.cost ? 'Mở hồ sơ chi tiết · ' + p.cost + ' xu' : 'Hồ sơ chi tiết miễn phí';
            body.append(node('span',entitlement,'profile-preview-entitlement'));
            if (p.logged_in) {
                var wallet = node('section',undefined,'profile-preview-wallet'); wallet.setAttribute('aria-label','Số dư và phí mở hồ sơ');
                var summary = node('div',undefined,'profile-preview-wallet-summary');
                summary.append(node('span','Số dư hiện tại'),node('strong',(p.unlimited_coins ? '∞' : Number(p.balance).toLocaleString('vi-VN')) + ' xu'));
                wallet.append(summary);
                var rows = node('dl',undefined,'profile-preview-wallet-rows');
                [['Xu đủ điều kiện thanh toán',p.transferable_balance],['Phí mở hồ sơ',p.cost],['Số dư sau khi mở',p.balance - p.cost]].forEach(function (item) {
                    var row = node('div');
                    row.append(node('dt',item[0]),node('dd',p.unlimited_coins && item[0] !== 'Phí mở hồ sơ' ? '∞ xu' : item[1] < 0 ? 'Chưa đủ xu' : Number(item[1]).toLocaleString('vi-VN') + ' xu'));
                    rows.append(row);
                });
                wallet.append(rows); body.append(wallet);
            }
            if (p.cost) body.append(node('p','Chỉ thanh toán một lần. Chủ hồ sơ nhận đủ ' + p.cost + ' xu, bạn xem lại miễn phí.','profile-preview-note'));
            if (p.contact_vip_required) body.append(node('p','Thông tin liên hệ chỉ dành cho VIP, độc lập với phí mở hồ sơ.','profile-preview-vip-note'));
            status.textContent = message || '';
            buy.textContent = !p.logged_in ? 'Đăng nhập để xem' : p.cost ? 'Xem hồ sơ chi tiết · ' + p.cost + ' xu' : 'Xem hồ sơ chi tiết';
            buy.disabled = false;
            if (p.logged_in && p.cost && p.transferable_balance < p.cost) {
                buy.disabled = true; status.textContent = 'Bạn cần ' + p.cost + ' xu đủ điều kiện chuyển. Xu thưởng khóa không được dùng. ';
                var topup = node('a','Nạp xu'); topup.href = base + 'tai-khoan/nap-xu'; status.append(topup);
            }
        } catch (error) { if (turn === sequence) status.textContent = error.message; }
    }
    buy.onclick = async function () {
        if (!current || busy) return;
        var p = current.preview;
        if (!p.logged_in) { location.assign(base + 'dang-nhap?next=' + encodeURIComponent('user/' + p.slug)); return; }
        busy = true; buy.disabled = true; status.textContent = 'Đang xác nhận…';
        try {
            var response = await fetch(base+'user/'+encodeURIComponent(p.slug)+'/purchase',{method:'POST',credentials:'same-origin',body:new URLSearchParams({csrf:current.csrf,version:p.version})}).then(json);
            location.assign(response.url);
        } catch (error) {
            if (error.code === 409) await load(p.slug,'Điều kiện đã thay đổi. Vui lòng xem lại và xác nhận lần nữa.');
            else status.textContent = error.message;
        } finally { busy = false; if (current) buy.disabled = !!(current.preview.logged_in && current.preview.cost && current.preview.transferable_balance < current.preview.cost); }
    };
    function cardSlug(card) {
        if (card.dataset.profilePreviewCard) return card.dataset.profilePreviewCard;
        var link = card.querySelector('a[href*="/profile/"],a[href*="/user/"]');
        var match = link && link.pathname.match(/\/(?:profile|user)\/([^/]+)\/?$/);
        return match ? decodeURIComponent(match[1]) : null;
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-profile-preview]');
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
        if (button) { event.preventDefault(); load(button.dataset.profilePreview); return; }
        var card = event.target.closest('[data-profile-preview-card],.pcard,.ccard,.hm-sug,.sw-card');
        if (card && !event.target.closest('button,input,textarea,select,[role=button],[data-chat-with],[data-card-action]')) {
            var slug = cardSlug(card);
            if (slug) { event.preventDefault(); event.stopPropagation(); load(slug); return; }
        }
        var anchor = event.target.closest('a[href]');
        if (!anchor || anchor.target === '_blank' || anchor.origin !== location.origin) return;
        var root = new URL(base, location.href).pathname.replace(/\/$/, '');
        var path = anchor.pathname.slice(root.length), match = path.match(/^\/(?:profile|user|thanh-vien)\/([^/]+)\/?$/);
        if (match) { event.preventDefault(); event.stopPropagation(); load(decodeURIComponent(match[1])); }
    }, true);
    document.addEventListener('keydown', function (event) {
        var card = event.target;
        if (!card.matches('[data-profile-preview-card]') || (event.key !== 'Enter' && event.key !== ' ')) return;
        event.preventDefault(); var slug = cardSlug(card); if (slug) load(slug);
    });
}());
