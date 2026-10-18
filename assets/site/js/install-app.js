(function () {
    'use strict';
    var script = document.currentScript;
    var deferred = null;
    var standalone = window.matchMedia('(display-mode: standalone)');
    var dialog = document.getElementById('install-dialog');
    var buttons = document.querySelectorAll('[data-install-app]');
    if (!dialog) return;
    var confirm = dialog.querySelector('[data-install-confirm]');
    var feedback = dialog.querySelector('[data-install-feedback]');
    function installed() { return standalone.matches || navigator.standalone === true; }
    function update() {
        buttons.forEach(function (button) { button.hidden = installed(); });
        confirm.hidden = !deferred || installed();
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
    window.addEventListener('appinstalled', function () { deferred = null; buttons.forEach(function (b) { b.hidden = true; }); dialog.close(); });
    standalone.addEventListener('change', update);
    buttons.forEach(function (button) { button.onclick = function () {
        feedback.textContent = ''; instructions(); if (!dialog.open) dialog.showModal();
    }; });
    dialog.querySelector('[data-install-close]').onclick = function () { dialog.close(); };
    confirm.onclick = async function () {
        if (!deferred) return;
        var prompt = deferred; deferred = null; confirm.disabled = true;
        try {
            await prompt.prompt();
            var result = await prompt.userChoice;
            if (result.outcome === 'accepted') dialog.close();
            else feedback.textContent = 'Bạn có thể thêm vào màn hình chính sau bất cứ lúc nào.';
        } catch (_) { feedback.textContent = 'Bạn có thể thêm bằng menu trình duyệt theo hướng dẫn bên trên.'; }
        finally { confirm.disabled = false; instructions(); }
    };
    update();
    if ('serviceWorker' in navigator && window.isSecureContext && script && script.dataset.worker) {
        navigator.serviceWorker.register(script.dataset.worker, {updateViaCache:'none'}).catch(function () {});
    }
}());
