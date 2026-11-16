(function () {
    'use strict';
    var panel = document.getElementById('tk-ms-inbox-panel');
    if (!panel) return;
    var side = panel.closest('.tk-ms-side'), tabs = Array.from(side.querySelectorAll('[data-inbox-tab]'));
    var search = side.querySelector('#tk-ms-search'), privateSection = panel.querySelector('.tk-ms-private');
    var none = panel.querySelector('#tk-ms-nores'), empty = panel.querySelector('[data-private-empty]');
    var searchBack = side.querySelector('[data-inbox-search-back]');
    if (searchBack) {
        function syncSearchFocus() {
            var focused = document.activeElement === search || document.activeElement === searchBack;
            searchBack.closest('.tk-ms-side__h').classList.toggle('is-search-focused', focused);
            searchBack.setAttribute('aria-hidden', focused ? 'false' : 'true');
            searchBack.tabIndex = focused ? 0 : -1;
        }
        search.addEventListener('focus', syncSearchFocus);
        search.addEventListener('blur', function () { setTimeout(syncSearchFocus, 0); });
        searchBack.addEventListener('focus', syncSearchFocus);
        searchBack.addEventListener('blur', function () { setTimeout(syncSearchFocus, 0); });
        searchBack.addEventListener('pointerdown', function (event) { event.preventDefault(); });
        searchBack.onclick = function () { search.blur(); searchBack.blur(); syncSearchFocus(); };
        syncSearchFocus();
        searchBack.hidden = false;
    }
    var active = 'priority';
    function timestamp(row) {
        return Date.parse((row.dataset.lastAt || '').replace(' ', 'T')) || 0;
    }
    function filter() {
        var list = panel.querySelector('#tk-ms-list');
        if (list) {
            var rows = Array.from(list.querySelectorAll('li[data-name]'));
            var sorted = rows.slice().sort(function (a, b) { return timestamp(b) - timestamp(a); });
            if (sorted.some(function (row, index) { return row !== rows[index]; })) sorted.forEach(function (row) { list.append(row); });
        }
        var query = search.value.trim().toLocaleLowerCase('vi'), visible = 0;
        panel.querySelectorAll('li[data-name]').forEach(function (row) {
            var group = !!row.querySelector('[data-inbox-group]');
            var matches = (active === 'priority' || group) && row.dataset.name.toLocaleLowerCase('vi').includes(query);
            row.hidden = !matches;
            if (matches) visible++;
        });
        privateSection.hidden = false;
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
    var root = panel.closest('.tk-ms'), refreshing = false;
    function refreshLatest() {
        if (document.hidden || refreshing) return;
        refreshing = true;
        var tasks = [
            [root.dataset.inboxConversationsUrl, 'items', 'data-conversation-id', 'last'],
            [root.dataset.inboxGroupsUrl, 'groups', 'data-inbox-group', 'last']
        ].filter(function (source) { return !!source[0]; }).map(function (source) {
            return fetch(source[0], {credentials:'same-origin', cache:'no-store'}).then(function (response) {
                if (!response.ok) throw new Error('Chưa tải được danh sách.');
                return response.json();
            }).then(function (data) {
                if (!data.ok) return;
                (data[source[1]] || []).forEach(function (item) {
                    var link = panel.querySelector('a[' + source[2] + '="' + Number(item.id) + '"]');
                    if (!link) return;
                    var row = link.closest('li'), lastAt = item.last_at || '';
                    if (row.dataset.lastAt !== lastAt) row.dataset.lastAt = lastAt;
                    var preview = link.querySelector('.tk-ms-item__l');
                    var text = source[1] === 'groups' ? (item.last ? (item.last_sender ? item.last_sender + ': ' : '') + item.last : 'Chưa có tin nhắn') : item[source[3]];
                    if (preview && typeof text === 'string' && preview.textContent !== text) preview.textContent = text;
                    var time = link.querySelector('.tk-ms-preview-time');
                    if (time && typeof item.time === 'string') {
                        time.textContent = item.time ? '· ' + item.time : '';
                        time.hidden = !item.last_at; time.title = item.last_at || '';
                    }
                });
                filter();
            }).catch(function () {});
        });
        Promise.all(tasks).then(function () { refreshing = false; });
    }
    setInterval(refreshLatest, 12000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshLatest(); });
    panel.querySelectorAll('ul').forEach(function (list) {
        new MutationObserver(filter).observe(list,{childList:true,subtree:true,attributes:true,attributeFilter:['data-last-at','data-name']});
    });
    filter();
})();
