(function () {
    'use strict';
    var panel = document.getElementById('tk-ms-inbox-panel');
    if (!panel) return;
    var side = panel.closest('.tk-ms-side'), tabs = Array.from(side.querySelectorAll('[data-inbox-tab]'));
    var search = side.querySelector('#tk-ms-search'), privateSection = panel.querySelector('.tk-ms-private');
    var none = panel.querySelector('#tk-ms-nores'), empty = panel.querySelector('[data-private-empty]');
    var active = 'priority';
    function filter() {
        var query = search.value.trim().toLocaleLowerCase('vi'), visible = 0;
        panel.querySelectorAll('li[data-name]').forEach(function (row) {
            var group = !!row.querySelector('[data-inbox-group]');
            var matches = (active === 'priority' || group) && row.dataset.name.toLocaleLowerCase('vi').includes(query);
            row.hidden = !matches;
            if (matches) visible++;
        });
        privateSection.hidden = active !== 'priority';
        var showEmpty = active === 'priority' && !query && !panel.querySelector('li[data-name]') && empty;
        if (empty) empty.hidden = !showEmpty;
        none.hidden = visible > 0 || !!showEmpty;
        var message = query ? 'Không tìm thấy cuộc trò chuyện nào.'
            : active === 'clubs' ? 'Chưa có nhóm chat ẩn danh.' : 'Chưa có cuộc trò chuyện nào.';
        if (none.textContent !== message) none.textContent = message;
    }
    function select(tab, focus) {
        active = tab.dataset.inboxTab;
        tabs.forEach(function (item) {
            var selected = item === tab;
            item.setAttribute('aria-selected',String(selected)); item.tabIndex = selected ? 0 : -1;
        });
        panel.setAttribute('aria-labelledby',tab.id);
        filter(); panel.scrollTop = 0;
        if (focus) tab.focus();
    }
    tabs.forEach(function (tab,index) {
        tab.addEventListener('click',function () { select(tab,false); });
        tab.addEventListener('keydown',function (event) {
            var next;
            if (event.key === 'ArrowRight') next = (index+1)%tabs.length;
            if (event.key === 'ArrowLeft') next = (index+tabs.length-1)%tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length-1;
            if (next !== undefined) { event.preventDefault(); select(tabs[next],true); }
        });
    });
    search.addEventListener('input',filter);
    panel.querySelectorAll('ul').forEach(function (list) {
        new MutationObserver(filter).observe(list,{childList:true});
    });
    filter();
})();
