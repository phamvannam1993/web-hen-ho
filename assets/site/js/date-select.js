/* Chọn ngày sinh theo ngày / tháng / năm; giữ input gốc để gửi và kiểm tra form. */
(function () {
    'use strict';

    document.querySelectorAll('input[type="date"][name="birthday"]').forEach(function (input, index) {
        var box = document.createElement('div');
        box.className = 'date-select';
        box.setAttribute('role', 'group');
        var label = input.labels && input.labels[0];
        if (label) {
            if (!label.id) { label.id = 'date-label-' + index; }
            box.setAttribute('aria-labelledby', label.id);
        }
        var min = input.min || '1900-01-01';
        var today = new Date();
        var max = input.max || today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        var parts = [];
        ['Ngày', 'Tháng', 'Năm'].forEach(function (name, part) {
            var select = document.createElement('select');
            select.setAttribute('aria-label', name + ' sinh');
            select.setAttribute('data-custom-select', '');
            select.disabled = input.disabled || input.readOnly;
            if (part === 2) {
                select.setAttribute('data-searchable', '');
                select.setAttribute('data-search-placeholder', 'Tìm năm sinh...');
            }
            select.add(new Option(name, ''));
            var start = part === 2 ? Number(max.slice(0, 4)) : 1;
            var end = part === 2 ? Number(min.slice(0, 4)) : part === 0 ? 31 : 12;
            for (var n = start; part === 2 ? n >= end : n <= end; n += part === 2 ? -1 : 1) {
                select.add(new Option(String(n), String(n)));
            }
            box.appendChild(select);
            parts.push(select);
        });
        input.parentNode.insertBefore(box, input);
        box.appendChild(input);
        input.classList.add('date-select-native');
        input.tabIndex = -1;

        function refresh() {
            var values = input.value.split('-');
            var days = values[1] ? new Date(Number(values[0]) || 2000, Number(values[1]), 0).getDate() : 31;
            Array.prototype.forEach.call(parts[0].options, function (option) {
                option.disabled = Number(option.value) > days;
            });
            [values[2], values[1], values[0]].forEach(function (value, part) {
                parts[part].value = value ? String(Number(value)) : '';
                parts[part].dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
        var syncing = false;
        function syncFromInput() {
            syncing = true;
            refresh();
            syncing = false;
        }
        function update() {
            var day = Number(parts[0].value);
            var month = Number(parts[1].value);
            var year = Number(parts[2].value);
            var days = month ? new Date(year || 2000, month, 0).getDate() : 31;
            if (day > days) {
                parts[0].value = String(days);
                day = days;
            }
            Array.prototype.forEach.call(parts[0].options, function (option) {
                option.disabled = Number(option.value) > days;
            });
            var value = day && month && year ? year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0') : '';
            input.value = value;
            input.setCustomValidity(value && (value < min || value > max) ? 'Vui lòng chọn ngày sinh từ ' + min.split('-').reverse().join('/') + ' đến ' + max.split('-').reverse().join('/') + '.' : '');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            parts[0].dispatchEvent(new Event('change', { bubbles: true }));
        }
        syncFromInput();
        parts.forEach(function (select) {
            select.addEventListener('change', function () {
                if (syncing) { return; }
                syncing = true;
                update();
                syncing = false;
            });
        });
        input.addEventListener('invalid', function () {
            var toggle = box.querySelector('.ss-toggle');
            if (toggle) { toggle.focus(); } else { parts[0].focus(); }
        });
        if (label) {
            label.addEventListener('click', function (event) {
                event.preventDefault();
                var toggle = box.querySelector('.ss-toggle');
                if (toggle) { toggle.focus(); } else { parts[0].focus(); }
            });
        }
        if (input.form) {
            input.form.addEventListener('reset', function () {
                setTimeout(function () { input.setCustomValidity(''); syncFromInput(); }, 0);
            });
        }
    });
})();
