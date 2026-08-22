/* Tiện ích khu quản trị: xem trước ảnh trước khi lưu. */
(function () {
    'use strict';

    document.querySelectorAll('input[type=file][accept*="image"]').forEach(function (input) {
        var box = document.createElement('div');
        box.className = 'file-preview';
        input.parentNode.insertBefore(box, input.nextSibling);

        input.addEventListener('change', function () {
            box.innerHTML = '';
            Array.prototype.slice.call(input.files || []).forEach(function (file) {
                if (!/^image\//.test(file.type)) { return; }
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.onload = function () { URL.revokeObjectURL(img.src); };
                box.appendChild(img);
            });
        });
    });
})();
