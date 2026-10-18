(function () {
    'use strict';
    var script = document.currentScript;
    var deferred = null;
    var installing = false;
    var standalone = window.matchMedia('(display-mode: standalone)');
    var dialog = document.getElementById('install-dialog');
    var buttons = document.querySelectorAll('[data-install-app]');
    var banner = document.querySelector('[data-install-banner]');
    var bannerClose = document.querySelector('[data-install-banner-close]');
    var bannerDismissed = false;
    try { bannerDismissed = sessionStorage.getItem('sgcp-install-banner-dismissed') === '1'; } catch (_) {}
    if (!dialog) return;
    var confirm = dialog.querySelector('[data-install-confirm]');
    var feedback = dialog.querySelector('[data-install-feedback]');
    function installed() { return standalone.matches || navigator.standalone === true; }
    function update() {
        buttons.forEach(function (button) { button.hidden = installed(); });
        confirm.hidden = !deferred || installed();
        if (banner) banner.hidden = installed() || bannerDismissed;
    }
    function instructions() {
        var ua = navigator.userAgent;
        var ios = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var safari = /Safari/.test(ua) && !/Chrome|Chromium|Edg|Android/.test(ua);
        var steps;
        if (deferred) steps = ['Bấm “Thêm ngay” bên dưới.', 'Xác nhận cài đặt trong hộp thoại của trình duyệt.'];
        else if (ios) steps = ['Mở website bằng Safari và bấm nút Chia sẻ.', 'Chọn “Thêm vào Màn hình chính”. Nếu có, bật “Mở dưới dạng ứng dụng web”.', 'Bấm “Thêm” để lưu biểu tượng Saigon Cupid.'];
        else if (safari && /Mac/.test(ua)) steps = ['Trong Safari, mở menu Tệp hoặc nút Chia sẻ.', 'Chọn “Thêm vào Dock” và xác nhận “Thêm”.'];
        else if (/Android/.test(ua)) steps = ['Mở website bằng Chrome hoặc Edge.', 'Bấm menu ⋮, chọn “Thêm vào màn hình chính” hoặc “Cài đặt ứng dụng”.', 'Xác nhận để thêm biểu tượng.'];
        else steps = ['Mở website bằng Chrome hoặc Edge.', 'Bấm biểu tượng cài đặt trên thanh địa chỉ, hoặc mở menu trình duyệt và tìm “Cài đặt ứng dụng” / “Cài đặt trang này dưới dạng ứng dụng”.', 'Nếu không có tùy chọn này, dùng mục “Tạo lối tắt” của trình duyệt.'];
        var list = dialog.querySelector('[data-install-steps]');
        list.replaceChildren();
        steps.forEach(function (text) { var li = document.createElement('li'); li.textContent = text; list.append(li); });
        update();
    }
    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault(); deferred = event;
        if (dialog.open) instructions(); else update();
    });
    window.addEventListener('appinstalled', function () { deferred = null; buttons.forEach(function (b) { b.hidden = true; }); if (banner) banner.hidden = true; dialog.close(); });
    standalone.addEventListener('change', update);
    function showGuide() {
        instructions();
        if (!dialog.open) dialog.showModal();
    }
    buttons.forEach(function (button) { button.onclick = function () {
        if (installed() || installing) return;
        feedback.textContent = '';
        if (deferred && !button.hasAttribute('data-install-guide')) return installNow();
        showGuide();
    }; });
    if (bannerClose) bannerClose.onclick = function () {
        bannerDismissed = true;
        try { sessionStorage.setItem('sgcp-install-banner-dismissed', '1'); } catch (_) {}
        update();
        window.dispatchEvent(new Event('resize'));
    };
    dialog.querySelector('[data-install-close]').onclick = function () { dialog.close(); };
    async function installNow() {
        if (!deferred || installing) return;
        var prompt = deferred; deferred = null; installing = true; confirm.disabled = true;
        try {
            await prompt.prompt();
            await prompt.userChoice;
            if (dialog.open) dialog.close();
        } catch (_) {
            feedback.textContent = 'Bạn có thể thêm bằng menu trình duyệt theo hướng dẫn bên trên.';
            showGuide();
        }
        finally { installing = false; confirm.disabled = false; update(); }
    }
    confirm.onclick = installNow;
    update();
    if ('serviceWorker' in navigator && window.isSecureContext && script && script.dataset.worker) {
        navigator.serviceWorker.register(script.dataset.worker, {updateViaCache:'none'}).catch(function () {});
    }
}());
