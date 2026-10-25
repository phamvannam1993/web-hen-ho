(function () {
    'use strict';
    var form = document.querySelector('[data-frame-shop]');
    var dialog = document.getElementById('frame-purchase-dialog');
    if (!form || !dialog) return;
    var library = document.getElementById('profile-frame-library');
    var opener = document.querySelector('[data-frame-shop-open]');
    var preview = document.getElementById('frame-preview-dialog');
    var scrollLock = null;
    function syncScrollLock() {
        if (library.open || dialog.open || preview.open) {
            if (scrollLock) return;
            scrollLock = {x: window.scrollX, y: window.scrollY, styles: {}};
            ['position', 'top', 'left', 'width', 'overflow'].forEach(function (property) {
                scrollLock.styles[property] = document.body.style[property];
            });
            document.body.style.position = 'fixed';
            document.body.style.top = -scrollLock.y + 'px';
            document.body.style.left = -scrollLock.x + 'px';
            document.body.style.width = '100%';
            document.body.style.overflow = 'hidden';
        } else if (scrollLock) {
            var saved = scrollLock; scrollLock = null;
            Object.keys(saved.styles).forEach(function (property) { document.body.style[property] = saved.styles[property]; });
            window.scrollTo({left: saved.x, top: saved.y, behavior: 'instant'});
        }
    }
    function openLibrary() { if (!library.open) library.showModal(); syncScrollLock(); }
    library.addEventListener('close', syncScrollLock);
    dialog.addEventListener('close', syncScrollLock);
    preview.addEventListener('close', syncScrollLock);
    preview.querySelector('[data-frame-preview-close]').onclick = function () { preview.close(); };
    form.addEventListener('click', function (event) {
        var button = event.target.closest('[data-frame-preview]');
        if (!button) return;
        var frame = button.dataset.framePreview;
        preview.className = 'cg-anonymous-profile frame-demo-dialog' + (frame === 'none' ? ' cg-profile-unframed' : ' cg-profile-theme-aurora' + (frame === 'aurora' ? '' : ' cg-profile-theme-' + frame));
        preview.querySelector('[data-frame-demo-title]').textContent = button.dataset.frameName;
        preview.showModal();
        preview.scrollTop = 0;
        syncScrollLock();
        preview.querySelector('[data-frame-preview-close]').focus();
    });
    preview.addEventListener('click', function (event) {
        if (event.target !== preview) return;
        var rect = preview.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) preview.close();
    });
    opener.onclick = openLibrary;
    library.querySelector('[data-frame-shop-close]').onclick = function () { library.close(); };
    library.addEventListener('click', function (event) {
        if (event.target !== library) return;
        var rect = library.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) library.close();
    });
    if (location.hash === '#kho-khung') openLibrary();
    window.addEventListener('hashchange', function () { if (location.hash === '#kho-khung') openLibrary(); });
    var pending = null, approved = false, submitting = false;
    var confirm = dialog.querySelector('[data-frame-confirm-buy]');
    var unlimited = form.dataset.unlimited === '1';
    var balance = Number(form.dataset.balance);
    function money(value) { return Number(value).toLocaleString('vi-VN') + ' xu'; }
    function fill(selector, text) { dialog.querySelector(selector).textContent = text; }
    form.addEventListener('submit', function (event) {
        if (submitting) { event.preventDefault(); return; }
        var button = event.submitter;
        if (!button || button.name !== 'buy_profile_frame') return;
        if (approved && button === pending) { submitting = true; return; }
        event.preventDefault();
        pending = button;
        var price = Number(button.dataset.framePrice), enough = unlimited || balance >= price;
        fill('[data-frame-confirm-name]', button.dataset.frameName);
        fill('[data-frame-confirm-balance]', unlimited ? '∞ xu' : money(balance));
        fill('[data-frame-confirm-price]', money(price));
        fill('[data-frame-confirm-after]', unlimited ? '∞ xu' : enough ? money(balance - price) : 'Chưa đủ xu');
        fill('[data-frame-confirm-note]', unlimited ? 'Xu vô hạn đang bật: mua khung không trừ số dư.' : enough ? 'Mua một lần, sử dụng lâu dài.' : 'Bạn chưa đủ xu để mua khung này.');
        dialog.querySelector('[data-frame-confirm-topup]').hidden = enough;
        confirm.disabled = !enough;
        form.elements.frame_purchase_confirmed.value = '0';
        dialog.showModal();
        syncScrollLock();
        dialog.querySelector('[data-frame-confirm-cancel]').focus();
    });
    dialog.querySelector('[data-frame-confirm-cancel]').onclick = function () { dialog.close(); };
    dialog.addEventListener('close', function () { if (!submitting) { pending = null; approved = false; } });
    dialog.addEventListener('click', function (event) {
        if (event.target !== dialog) return;
        var rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
    confirm.onclick = function () {
        if (!pending || submitting || confirm.disabled) return;
        approved = true;
        form.elements.frame_purchase_confirmed.value = '1';
        form.requestSubmit(pending);
        if (submitting) confirm.disabled = true;
    };
}());
