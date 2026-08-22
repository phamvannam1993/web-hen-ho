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

/**
 * Gán nhãn cột cho từng ô của bảng, để trên mobile CSS hiển thị được
 * "Tên cột: giá trị" khi bảng chuyển sang dạng thẻ.
 * Làm bằng JS để không phải sửa data-label thủ công ở hàng chục view.
 */
(function () {
    'use strict';

    document.querySelectorAll('.table').forEach(function (table) {
        var heads = table.querySelectorAll('thead th');
        if (!heads.length) { return; }

        var labels = Array.prototype.map.call(heads, function (th) {
            return th.textContent.trim();
        });

        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.querySelectorAll('td').forEach(function (cell, i) {
                var label = labels[i] || '';
                // Ô chứa ảnh, ô chọn hoặc nút thao tác thì không cần nhãn
                if (cell.querySelector('img, input[type=checkbox]') ||
                    cell.classList.contains('actions')) {
                    label = '';
                }
                cell.setAttribute('data-label', label);
            });
        });
    });
})();

/**
 * Hộp thoại xác nhận dùng chung, thay cho confirm() mặc định của trình duyệt.
 *
 * Cách dùng: thêm data-confirm="Nội dung hỏi" vào thẻ <a> hoặc <button>.
 * Thêm data-confirm-danger để hiện biểu tượng đỏ cho hành động xoá.
 */
(function () {
    'use strict';

    var box = null;

    function build() {
        if (box) { return box; }

        box = document.createElement('div');
        box.className = 'adm-modal';
        box.innerHTML =
            '<div class="adm-modal-box" role="dialog" aria-modal="true">' +
                '<div class="adm-modal-icon">!</div>' +
                '<h3 class="adm-modal-title">Xác nhận</h3>' +
                '<p class="adm-modal-text"></p>' +
                '<div class="adm-modal-actions">' +
                    '<button type="button" class="btn btn-light" data-cancel>Huỷ</button>' +
                    '<button type="button" class="btn btn-primary" data-ok>Đồng ý</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(box);

        box.querySelector('[data-cancel]').addEventListener('click', close);
        box.addEventListener('click', function (e) { if (e.target === box) { close(); } });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); }
        });
        return box;
    }

    function close() {
        if (box) { box.classList.remove('open'); }
    }

    function ask(message, danger, onOk) {
        var el = build();
        el.querySelector('.adm-modal-text').textContent = message;
        el.querySelector('.adm-modal-icon').className = 'adm-modal-icon' + (danger ? ' danger' : '');
        el.querySelector('.adm-modal-icon').textContent = danger ? '⌫' : '!';

        var okBtn = el.querySelector('[data-ok]');
        // thay nút để bỏ mọi trình xử lý của lần mở trước
        var fresh = okBtn.cloneNode(true);
        fresh.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
        okBtn.parentNode.replaceChild(fresh, okBtn);
        fresh.addEventListener('click', function () {
            close();
            onOk();
        });

        el.classList.add('open');
        fresh.focus();
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-confirm]');
        if (!trigger) { return; }

        e.preventDefault();
        var danger = trigger.hasAttribute('data-confirm-danger');

        ask(trigger.getAttribute('data-confirm'), danger, function () {
            if (trigger.tagName === 'A') {
                window.location.href = trigger.href;
                return;
            }
            // nút trong biểu mẫu: gỡ thuộc tính rồi bấm lại để gửi bình thường
            var form = trigger.form || trigger.closest('form');
            if (form) {
                trigger.removeAttribute('data-confirm');
                trigger.click();
            }
        });
    });
})();
