(function () {
    'use strict';
    var root = document.querySelector('[data-public-profile]');
    if (!root) return;
    var tabs = Array.from(root.querySelectorAll('[data-profile-tab]'));
    function selectTab(tab) {
        tabs.forEach(function (item) {
            var selected = item === tab;
            item.setAttribute('aria-selected', String(selected));
            item.tabIndex = selected ? 0 : -1;
            document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
        });
    }
    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { selectTab(tab); });
        tab.addEventListener('keydown', function (event) {
            var next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault(); selectTab(tabs[next]); tabs[next].focus();
        });
    });
    root.querySelector('[data-profile-share]').addEventListener('click', async function () {
        var status = root.querySelector('[data-profile-share-status]');
        try {
            await navigator.clipboard.writeText(window.location.href);
            status.textContent = 'Đã sao chép liên kết hồ sơ.';
        } catch (error) {
            status.textContent = 'Bạn có thể sao chép liên kết trên thanh địa chỉ trình duyệt.';
        }
    });
}());
