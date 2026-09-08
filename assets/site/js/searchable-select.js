/**
 * Ô chọn có tìm kiếm — dùng cho danh sách dài (nghề nghiệp…).
 *
 * Dùng chung cho cả trang ngoài và khu quản trị nên để riêng một file.
 * Cách dùng: <select data-searchable data-search-placeholder="Tìm nghề..."> …
 */
(function () {
    'use strict';

/* Thẻ select gốc vẫn giữ nguyên giá trị để form gửi đi như thường,
   chỉ ẩn đi và thay bằng ô bấm + ô lọc. */
function boDauTiengViet(s) {
    return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
}

document.querySelectorAll('select[data-searchable]').forEach(function (select) {
    var options = Array.prototype.slice.call(select.options);
    var rong    = options[0] && options[0].value === '' ? options[0].textContent : '-- Chọn --';

    var box = document.createElement('div');
    box.className = 'ss';

    var nut = document.createElement('button');
    nut.type = 'button';
    nut.className = 'ss-toggle';
    nut.setAttribute('aria-haspopup', 'listbox');
    nut.setAttribute('aria-expanded', 'false');

    var panel = document.createElement('div');
    panel.className = 'ss-panel';
    panel.hidden = true;

    var oLoc = document.createElement('input');
    oLoc.type = 'text';
    oLoc.className = 'ss-search';
    oLoc.placeholder = select.getAttribute('data-search-placeholder') || 'Tìm...';
    oLoc.autocomplete = 'off';

    var ds = document.createElement('div');
    ds.className = 'ss-list';
    ds.setAttribute('role', 'listbox');

    panel.appendChild(oLoc);
    panel.appendChild(ds);
    select.parentNode.insertBefore(box, select);
    box.appendChild(select);
    box.appendChild(nut);
    box.appendChild(panel);
    select.classList.add('ss-native');

    function veNhan() {
        var op = select.options[select.selectedIndex];
        var co = op && op.value !== '';
        nut.textContent = co ? op.textContent : rong;
        nut.classList.toggle('is-empty', !co);
    }

    function veDanhSach(tuKhoa) {
        var key = boDauTiengViet(tuKhoa || '');
        ds.textContent = '';
        var hien = 0;

        options.forEach(function (op) {
            if (op.value === '') { return; }
            if (key && boDauTiengViet(op.textContent).indexOf(key) === -1) { return; }
            hien++;

            var muc = document.createElement('button');
            muc.type = 'button';
            muc.className = 'ss-item' + (op.value === select.value ? ' is-on' : '');
            muc.textContent = op.textContent;
            muc.setAttribute('role', 'option');
            muc.addEventListener('click', function () {
                select.value = op.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                veNhan();
                dong();
            });
            ds.appendChild(muc);
        });

        if (!hien) {
            var trong = document.createElement('p');
            trong.className = 'ss-empty';
            trong.textContent = 'Không tìm thấy mục nào phù hợp.';
            ds.appendChild(trong);
        }
    }

    function mo() {
        panel.hidden = false;
        nut.setAttribute('aria-expanded', 'true');
        oLoc.value = '';
        veDanhSach('');
        oLoc.focus();
    }
    function dong() {
        panel.hidden = true;
        nut.setAttribute('aria-expanded', 'false');
    }

    nut.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.hidden) { mo(); } else { dong(); }
    });
    oLoc.addEventListener('input', function () { veDanhSach(oLoc.value); });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', dong);
    box.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { dong(); nut.focus(); }
        // Enter trong ô lọc: chọn luôn kết quả đầu tiên cho nhanh
        if (e.key === 'Enter' && e.target === oLoc) {
            e.preventDefault();
            var dau = ds.querySelector('.ss-item');
            if (dau) { dau.click(); }
        }
    });

    veNhan();
});
})();
