(function () {
    'use strict';
    var panel = document.getElementById('dating-filter-panel');
    var toggle = document.getElementById('dating-filter-toggle');
    if (panel && toggle) {
        panel.classList.add('is-collapsible');
        toggle.hidden = false;
        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(expanded));
            panel.classList.toggle('is-expanded', expanded);
        });
    }
    var button = document.getElementById('dating-location');
    var status = document.getElementById('dating-location-status');
    if (!button) return;
    button.addEventListener('click', function () {
        if (!navigator.geolocation || !window.isSecureContext) {
            status.textContent = 'Trình duyệt cần HTTPS và hỗ trợ vị trí. Bạn có thể chọn tỉnh/thành để ước tính.';
            return;
        }
        button.disabled = true;
        status.textContent = 'Đang xin quyền truy cập vị trí…';
        navigator.geolocation.getCurrentPosition(function (position) {
            var body = new URLSearchParams();
            body.set('lat', position.coords.latitude);
            body.set('lng', position.coords.longitude);
            body.set('location_token', button.dataset.token);
            body.set(button.dataset.csrfName, button.dataset.csrfValue);
            fetch(button.dataset.url, {method: 'POST', credentials: 'same-origin', body: body})
                .then(function (response) {
                    if (!response.ok) throw new Error('save');
                    return response.json();
                }).then(function (result) {
                    if (!result.ok) throw new Error('save');
                    var form = document.getElementById('dating-distance-fields');
                    if (form && form.requestSubmit) {
                        form.requestSubmit();
                        button.disabled = false;
                        return;
                    }
                    var url = new URL(window.location.href);
                    url.pathname = url.pathname.replace(/\/trang\/\d+\/?$/, '');
                    window.location.replace(url.toString());
                }).catch(function () {
                    button.disabled = false;
                    status.textContent = 'Chưa lưu được vị trí. Hãy tải lại trang và thử lại; bạn vẫn có thể ước tính theo tỉnh/thành.';
                });
        }, function (error) {
            button.disabled = false;
            status.textContent = error.code === 1
                ? 'Bạn chưa cho phép truy cập vị trí. Chọn tỉnh/thành để ước tính khoảng cách.'
                : 'Chưa lấy được vị trí. Bạn có thể thử lại hoặc chọn tỉnh/thành.';
        }, {enableHighAccuracy: true, timeout: 15000, maximumAge: 300000});
    });
}());
