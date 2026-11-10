(function () {
    'use strict';
    var root = document.querySelector('.tk-ms[data-private-groups-api]');
    if (!root) return;
    var base = root.dataset.privateGroupsApi.replace(/\/$/, '');
    var inboxUrl = root.dataset.inboxUrl, uid = Number(root.dataset.privateUser);
    var list = root.querySelector('[data-ig-list]'), listStatus = root.querySelector('[data-ig-list-status]');
    var moreGroups = root.querySelector('[data-ig-list-more]');
    var token = '', groupCursor = 0, listing = false, listingPromise = null, loadedPages = 1, dialog = null;
    var chat = root.querySelector('[data-private-group]'), pane = chat && chat.closest('.tk-ms-pane');
    var gid = chat ? Number(chat.dataset.privateGroup) : 0;
    var feed = chat && chat.querySelector('[data-ig-feed]');
    var chatStatus = chat && chat.querySelector('[data-ig-status]');
    var sendForm = chat && chat.querySelector('[data-ig-send]');
    var olderButton = chat && chat.querySelector('[data-ig-older]');
    var first = 0, last = 0, historyBusy = false, loaded = false, sending = false, pendingSend = null;
    var ids = new Set(), readBusy = false, readThrough = 0;
    function node(tag, cls, text) {
        var el = document.createElement(tag);
        if (cls) el.className = cls;
        if (text !== undefined) el.textContent = text;
        return el;
    }
    function groupUrl(id) { return inboxUrl + '?nhom=' + encodeURIComponent(id); }
    function request(path, values, query) {
        var url = new URL(base + path, location.href);
        Object.keys(query || {}).forEach(function (key) { if (query[key] !== undefined && query[key] !== null) url.searchParams.set(key, query[key]); });
        var options = {credentials: 'same-origin', cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest'}};
        if (values) {
            var data = new FormData(); data.set('action_token', token);
            Object.keys(values).forEach(function (key) {
                if (Array.isArray(values[key])) values[key].forEach(function (value) { data.append(key + '[]', value); });
                else data.set(key, values[key]);
            });
            options.method = 'POST'; options.body = data;
        }
        return fetch(url.href, options).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) { var error = new Error(data.message || 'Chưa thực hiện được. Vui lòng thử lại.'); error.status = response.status; throw error; }
                return data;
            });
        });
    }
    function active() { return !!chat && chat.isConnected && !pane.hidden && !document.hidden; }
    function atBottom() { return feed.scrollHeight - feed.scrollTop - feed.clientHeight < 70; }
    function groupRow(group) {
        var item = node('li'); item.dataset.name = group.name.toLocaleLowerCase('vi');
        var link = node('a'); link.href = groupUrl(group.id);
        var text = node('span'); text.append(node('strong', '', group.name), node('small', '', group.last_content || (group.member_count + ' thành viên')));
        link.append(text);
        if (group.unread) link.append(node('span', 'ig-count', group.unread > 99 ? '99+' : group.unread));
        if (Number(group.id) === gid) link.setAttribute('aria-current', 'page');
        item.append(link); return item;
    }
    function loadGroups(more) {
        if (listing) return listingPromise;
        if (document.hidden) return Promise.resolve();
        listing = true; moreGroups.disabled = true;
        var groups = [], pages = 0, requestedPages = more ? 1 : loadedPages;
        function page(before) {
            return request('', null, {before: before}).then(function (data) {
                token = data.action_token; groups = groups.concat(data.groups); pages++;
                if (data.has_more && pages < requestedPages) return page(data.next_before);
                return data;
            });
        }
        listingPromise = page(more ? groupCursor : 0).then(function (data) {
            if (!more) list.replaceChildren();
            groups.forEach(function (group) { list.append(groupRow(group)); });
            loadedPages = more ? loadedPages + 1 : pages;
            groupCursor = data.next_before || 0; moreGroups.hidden = !data.has_more;
            listStatus.textContent = '';
            listStatus.hidden = true;
            if (sendForm && loaded) sendForm.querySelectorAll('textarea,button').forEach(function (el) { el.disabled = sending; });
        }).catch(function (error) { listStatus.textContent = error.message; listStatus.hidden = false; })
        .then(function () { listing = false; moreGroups.disabled = false; });
        return listingPromise;
    }
    moreGroups.onclick = function () { loadGroups(true); };
    function openDialog(title, trigger) {
        if (dialog && dialog.open) dialog.close();
        var el = node('dialog', 'ig-dialog');
        if (typeof el.showModal !== 'function') { window.alert('Vui lòng cập nhật trình duyệt để sử dụng nhóm inbox.'); return null; }
        var header = node('header'), heading = node('h2', '', title), close = node('button', '', 'Đóng');
        heading.id = 'ig-dialog-title'; el.setAttribute('aria-labelledby', heading.id);
        close.type = 'button'; close.onclick = function () { el.close(); };
        header.append(heading, close);
        var body = node('div', 'ig-dialog-body'), status = node('p', 'ig-dialog-status'); status.setAttribute('role', 'status');
        el.append(header, status, body); root.append(el); dialog = el;
        var viewport = window.visualViewport, timer;
        function fit() { el.style.top = ((viewport ? viewport.offsetTop : 0) + 12) + 'px'; el.style.height = Math.max(0, Math.min(600, (viewport ? viewport.height : innerHeight) - 24)) + 'px'; }
        el.addEventListener('close', function () {
            clearTimeout(timer); window.removeEventListener('resize', fit);
            if (viewport) { viewport.removeEventListener('resize', fit); viewport.removeEventListener('scroll', fit); }
            el.remove(); if (trigger && trigger.isConnected) trigger.focus({preventScroll: true});
        });
        window.addEventListener('resize', fit);
        if (viewport) { viewport.addEventListener('resize', fit); viewport.addEventListener('scroll', fit); }
        fit(); el.showModal(); timer = setTimeout(fit, 350);
        return {el: el, body: body, status: status, current: function () { return el.isConnected && el.open; }};
    }
    function membersDialog(group, trigger) {
        var box = openDialog('Thành viên nhóm', trigger); if (!box) return;
        group.members.forEach(function (user) { var row = node('div', 'ig-person'); var img = node('img'); img.src = user.avatar; img.alt = ''; row.append(img, node('span', '', user.name + ' · ID ' + user.id)); box.body.append(row); });
    }
    function peopleDialog(targetGroup, initial, trigger) {
        var box = openDialog(targetGroup ? 'Thêm người vào nhóm' : 'Tạo nhóm mới', trigger); if (!box) return;
        var selected = new Map(); (initial || []).forEach(function (user) { selected.set(Number(user.id), user); });
        var form = node('form'), name = node('input'); name.type = 'text'; name.maxLength = 120;
        name.placeholder = 'Tên nhóm, ví dụ: 3 anh chị em'; name.setAttribute('aria-label', 'Tên nhóm');
        if (!targetGroup) { name.required = true; name.minLength = 2; form.append(name); }
        var selectedLabel = node('p'), selectedList = node('div');
        var searchForm = node('form'), query = node('input'); query.type = 'search'; query.minLength = 2; query.maxLength = 100; query.required = true;
        query.placeholder = 'Tìm theo tên hoặc biệt danh…'; query.setAttribute('aria-label', 'Tìm thành viên');
        var find = node('button', '', 'Tìm người'); find.type = 'submit'; searchForm.append(query, find);
        var results = node('div'), more = node('button', '', 'Xem thêm người'); more.type = 'button'; more.hidden = true;
        var save = node('button', '', targetGroup ? 'Thêm vào nhóm' : 'Tạo nhóm'); save.type = 'submit'; form.append(save);
        box.body.append(selectedLabel, selectedList, searchForm, results, more, form);
        var version = 0, cursor = 0, term = '', busy = false, saving = false;
        function summary() {
            selectedLabel.textContent = 'Đã chọn ' + selected.size + ' người (không cần kết bạn).'; selectedList.replaceChildren();
            selected.forEach(function (user) {
                var remove = node('button', '', user.name + ' ×'); remove.type = 'button'; remove.setAttribute('aria-label', 'Bỏ chọn ' + user.name);
                remove.onclick = function () { if (saving) return; selected.delete(Number(user.id)); summary(); results.querySelectorAll('input').forEach(function (input) { input.checked = selected.has(Number(input.value)); }); }; selectedList.append(remove);
            }); save.disabled = saving || !selected.size || !token;
        }
        function search(append) {
            if (append && busy) return;
            var current = ++version; if (!append) { term = query.value.trim(); cursor = 0; results.replaceChildren(); more.hidden = true; }
            if (term.length < 2) return;
            busy = true; more.disabled = true; box.status.textContent = 'Đang tìm…';
            request('/tim-nguoi', null, {q: term, after: append ? cursor : 0, group_id: targetGroup || undefined}).then(function (data) {
                if (!box.current() || current !== version) return;
                data.users.forEach(function (user) {
                    var row = node('label', 'ig-person'), input = node('input'); input.type = 'checkbox'; input.value = user.id; input.checked = selected.has(Number(user.id));
                    var image = node('img'); image.src = user.avatar; image.alt = '';
                    input.onchange = function () { if (input.checked) selected.set(Number(user.id), user); else selected.delete(Number(user.id)); summary(); };
                    row.append(input, image, node('span', '', user.name + ' · ID ' + user.id)); results.append(row);
                }); cursor = data.next_after || 0; more.hidden = !data.has_more;
                box.status.textContent = results.children.length ? 'Chọn đúng người bằng tên, ảnh và ID.' : 'Không tìm thấy người phù hợp.';
            }).catch(function (error) { if (box.current() && current === version) box.status.textContent = error.message; })
            .then(function () { if (current === version) { busy = false; more.disabled = false; } });
        }
        searchForm.onsubmit = function (event) { event.preventDefault(); search(false); };
        more.onclick = function () { search(true); };
        form.onsubmit = function (event) {
            event.preventDefault(); if (saving || !selected.size || !token) return;
            saving = true; summary(); box.status.textContent = 'Đang lưu nhóm…';
            var values = {user_ids: Array.from(selected.keys())}; if (!targetGroup) values.name = name.value.trim();
            request(targetGroup ? '/' + targetGroup + '/them' : '/tao', values).then(function (data) {
                loadGroups(false);
                if (!box.current()) return;
                if (targetGroup) { box.status.textContent = 'Đã thêm thành viên.'; if (gid === Number(targetGroup)) loadDetail(); box.el.close(); }
                else location.assign(groupUrl(data.group.id));
            }).catch(function (error) { if (box.current()) box.status.textContent = error.message; })
            .then(function () { saving = false; if (box.current()) summary(); });
        };
        summary();
        if (!token) loadGroups(false).then(function () { if (box.current()) summary(); });
    }
    function groupsDialog(peerId, peerName, common, trigger) {
        var box = openDialog(common ? 'Nhóm chung' : 'Thêm vào nhóm', trigger); if (!box) return;
        if (!common) {
            var create = node('button', 'ig-choice', 'Tạo nhóm mới với ' + peerName);
            create.onclick = function () { peopleDialog(null, [{id: peerId, name: peerName}], trigger); }; box.body.append(create);
        }
        var choices = node('div'), more = node('button', '', 'Xem thêm nhóm'); more.hidden = true; more.type = 'button'; box.body.append(choices, more);
        var cursor = 0, busy = false;
        function load(append) {
            if (busy) return; busy = true; more.disabled = true; box.status.textContent = 'Đang tải nhóm…';
            request(common ? '/chung/' + peerId : '', null, {before: append ? cursor : 0}).then(function (data) {
                if (!box.current()) return; if (data.action_token) token = data.action_token;
                data.groups.forEach(function (group) {
                    var choice = node(common ? 'a' : 'button', 'ig-choice', group.name + ' · ' + group.member_count + ' thành viên');
                    if (common) choice.href = groupUrl(group.id);
                    else { choice.type = 'button'; choice.onclick = function () {
                        if (busy) return; busy = true; choices.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                        request('/' + group.id + '/them', {user_ids: [peerId]}).then(function () { loadGroups(false); if (box.current()) location.assign(groupUrl(group.id)); })
                        .catch(function (error) { if (box.current()) box.status.textContent = error.message; })
                        .then(function () { busy = false; choices.querySelectorAll('button').forEach(function (b) { b.disabled = false; }); });
                    }; } choices.append(choice);
                }); cursor = data.next_before || 0; more.hidden = !data.has_more;
                box.status.textContent = choices.children.length ? '' : (common ? 'Hai bạn chưa có nhóm chung.' : 'Chưa có nhóm. Bạn có thể tạo nhóm mới.');
            }).catch(function (error) { if (box.current()) box.status.textContent = error.message; })
            .then(function () { busy = false; more.disabled = false; });
        }
        more.onclick = function () { load(true); }; load(false);
    }
    root.addEventListener('click', function (event) {
        var create = event.target.closest('[data-ig-create]');
        var add = event.target.closest('[data-ig-add-peer]'), common = event.target.closest('[data-ig-common]');
        if (create) peopleDialog(null, [], create);
        if (add) groupsDialog(Number(add.dataset.igAddPeer), add.dataset.igPeerName, false, add);
        if (common) groupsDialog(Number(common.dataset.igCommon), '', true, common);
        if (create || add || common) { var details = event.target.closest('details'); if (details) details.open = false; }
    });
    function appendMessages(messages, older) {
        var fragment = document.createDocumentFragment();
        messages.forEach(function (message) {
            if (ids.has(message.id)) return; ids.add(message.id);
            var item = node('article', 'ig-message' + (message.mine ? ' mine' : '')); item.dataset.messageId = message.id;
            item.append(node('small', '', message.mine ? 'Bạn' : message.sender_name), node('p', '', message.content), node('small', '', message.created_at));
            fragment.append(item); first = first ? Math.min(first, message.id) : message.id;
        });
        if (older) feed.prepend(fragment); else feed.append(fragment);
        Array.from(feed.children).sort(function (a, b) { return Number(a.dataset.messageId) - Number(b.dataset.messageId); }).forEach(function (item) { feed.append(item); });
    }
    function acknowledge() {
        if (!active() || !loaded || !token || !last || readBusy || last <= readThrough || !atBottom()) return;
        readBusy = true; var through = last;
        request('/' + gid + '/da-doc', {message_id: through}).then(function () { readThrough = Math.max(readThrough, through); })
        .catch(function () {}).then(function () { readBusy = false; });
    }
    function loadMessages(older) {
        if (!active() || historyBusy) return Promise.resolve();
        historyBusy = true; olderButton.disabled = true;
        var wasBottom = !loaded || atBottom(), height = feed.scrollHeight, top = feed.scrollTop;
        return request('/' + gid + '/tin-nhan', null, older ? {before: first} : (loaded ? {after: last} : {})).then(function (data) {
            if (!active()) return;
            appendMessages(data.messages, older);
            if (!older) data.messages.forEach(function (message) { last = Math.max(last, message.id); });
            if (older || !loaded) olderButton.hidden = !data.has_more;
            loaded = true;
            if (older) feed.scrollTop = top + feed.scrollHeight - height;
            else if (wasBottom) feed.scrollTop = feed.scrollHeight;
            chatStatus.textContent = ''; sendForm.querySelectorAll('textarea,button').forEach(function (el) { el.disabled = sending || !token; }); acknowledge();
        }).catch(function (error) {
            chatStatus.textContent = error.message;
            if (error.status === 403) { loaded = false; sendForm.querySelectorAll('textarea,button').forEach(function (el) { el.disabled = true; }); feed.replaceChildren(); ids.clear(); first = 0; last = 0; readThrough = 0; }
        }).then(function () { historyBusy = false; olderButton.disabled = false; });
    }
    function loadDetail() {
        return request('/' + gid + '/chi-tiet').then(function (data) {
            if (!chat.isConnected) return;
            chat.querySelector('[data-ig-name]').textContent = data.group.name;
            chat.querySelector('[data-ig-count]').textContent = data.group.members.length + ' thành viên';
            chat.querySelector('[data-ig-members]').onclick = function () { membersDialog(data.group, this); };
        }).catch(function (error) { chatStatus.textContent = error.message; });
    }
    if (chat) {
        chat.querySelector('[data-ig-add]').onclick = function () { peopleDialog(gid, [], this); };
        olderButton.onclick = function () { loadMessages(true); };
        feed.addEventListener('scroll', acknowledge, {passive: true});
        sendForm.onsubmit = function (event) {
            event.preventDefault(); var text = sendForm.elements.content.value.trim();
            if (sending || !token || !loaded || !text) return;
            if (!pendingSend || pendingSend.text !== text) {
                var bytes = new Uint8Array(16); crypto.getRandomValues(bytes);
                pendingSend = {text: text, key: Array.from(bytes).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('')};
            }
            sending = true; sendForm.querySelector('button').disabled = true;
            request('/' + gid + '/gui', {content: text, client_message_id: pendingSend.key}).then(function (data) {
                appendMessages([data.message], false);
                if (sendForm.elements.content.value.trim() === text) sendForm.elements.content.value = '';
                pendingSend = null; feed.scrollTop = feed.scrollHeight; chatStatus.textContent = ''; acknowledge(); loadGroups(false);
            }).catch(function (error) { chatStatus.textContent = error.message + ' Nội dung vẫn được giữ để thử lại.'; })
            .then(function () { sending = false; sendForm.querySelector('button').disabled = !loaded || !token; });
        };
        loadDetail();
    }
    function refresh() { if (!document.hidden) loadGroups(false); }
    loadGroups(false).then(function () { if (chat) loadMessages(false); });
    setInterval(function () { if (active()) loadMessages(false); }, 4000);
    setInterval(function () { if (!dialog || !dialog.open) refresh(); }, 12000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(); if (chat) loadMessages(false); } });
    if (pane) new MutationObserver(function () { if (!pane.hidden) { loadMessages(false); loadDetail(); } }).observe(pane, {attributes: true, attributeFilter: ['hidden']});
})();
