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

document.querySelectorAll('select[data-searchable], select[data-custom-select], .tk-pf select, .filter-panel select').forEach(function (select, index) {
    if (select.multiple || select.size > 1) { return; }
    var searchable = select.hasAttribute('data-searchable');
    var options = Array.prototype.slice.call(select.options);
    var rong    = options[0] && options[0].value === '' ? options[0].textContent : '-- Chọn --';

    var box = document.createElement('div');
    box.className = 'ss';

    var nut = document.createElement('button');
    nut.type = 'button';
    nut.className = 'ss-toggle';
    nut.setAttribute('aria-haspopup', 'listbox');
    nut.setAttribute('aria-expanded', 'false');
    nut.disabled = select.disabled;
    if (select.hasAttribute('aria-label')) { nut.setAttribute('aria-label', select.getAttribute('aria-label')); }
    select.tabIndex = -1;
    var label = select.labels && select.labels[0];
    if (label) {
        if (!label.id) { label.id = 'ss-label-' + index; }
        nut.setAttribute('aria-labelledby', label.id);
        label.addEventListener('click', function (e) { e.preventDefault(); nut.focus(); });
    }

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
    ds.id = 'ss-list-' + index;
    nut.setAttribute('aria-controls', ds.id);
    if (label) { ds.setAttribute('aria-labelledby', label.id); }

    if (searchable) { panel.appendChild(oLoc); }
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
        nut.disabled = select.disabled;
    }

    function veDanhSach(tuKhoa) {
        var key = boDauTiengViet(tuKhoa || '');
        ds.textContent = '';
        var hien = 0;

        options.forEach(function (op) {
            if (searchable && op.value === '' && !select.hasAttribute('data-allow-empty')) { return; }
            if (key && boDauTiengViet(op.textContent).indexOf(key) === -1) { return; }
            hien++;

            var muc = document.createElement('button');
            muc.type = 'button';
            muc.className = 'ss-item' + (op.value === select.value ? ' is-on' : '');
            muc.textContent = op.textContent;
            muc.setAttribute('role', 'option');
            muc.setAttribute('aria-selected', String(op.value === select.value));
            muc.disabled = op.disabled || (op.parentNode.tagName === 'OPTGROUP' && op.parentNode.disabled);
            muc.addEventListener('click', function () {
                select.value = op.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                veNhan();
                dong();
                nut.focus();
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
        document.dispatchEvent(new Event('ss-close'));
        panel.hidden = false;
        nut.setAttribute('aria-expanded', 'true');
        oLoc.value = '';
        veDanhSach('');
        if (searchable) { oLoc.focus(); }
        else {
            var selected = ds.querySelector('.ss-item.is-on:not(:disabled)') || ds.querySelector('.ss-item:not(:disabled)');
            if (selected) { selected.focus(); }
        }
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
    document.addEventListener('ss-close', dong);
    select.addEventListener('change', veNhan);
    select.addEventListener('invalid', function () { nut.focus(); });
    if (select.form) {
        select.form.addEventListener('reset', function () { setTimeout(function () { veNhan(); dong(); }, 0); });
    }
    box.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) { dong(); nut.focus(); }
        if (e.key === 'Tab' && !panel.hidden) { nut.focus(); dong(); }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Home' || e.key === 'End') {
            e.preventDefault();
            if (panel.hidden) { mo(); return; }
            var items = Array.prototype.slice.call(ds.querySelectorAll('.ss-item:not(:disabled)'));
            var active = items.indexOf(document.activeElement);
            var next = e.key === 'Home' ? 0 : e.key === 'End' ? items.length - 1
                : e.key === 'ArrowDown' ? Math.min(active + 1, items.length - 1) : Math.max(active - 1, 0);
            if (items[next]) { items[next].focus(); }
        }
        // Enter trong ô lọc: chọn luôn kết quả đầu tiên cho nhanh
        if (e.key === 'Enter' && e.target === oLoc) {
            e.preventDefault();
            var dau = ds.querySelector('.ss-item:not(:disabled)');
            if (dau) { dau.click(); }
        }
    });

    veNhan();
});
})();
