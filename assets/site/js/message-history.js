(function () {
    'use strict';
    function node(tag, cls, text) {
        var item = document.createElement(tag);
        if (cls) item.className = cls;
        if (text !== undefined) item.textContent = text;
        return item;
    }
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var menu = document.querySelector('.tk-ms-more[open]');
        if (menu) { menu.open = false; menu.querySelector('summary').focus(); }
    });
    document.addEventListener('click', function (event) {
        document.querySelectorAll('.tk-ms-more[open]').forEach(function (menu) {
            if (!menu.contains(event.target)) menu.open = false;
        });
        var trigger = event.target.closest('[data-message-history]');
        if (!trigger || !trigger.dataset.messageHistory) return;
        event.preventDefault();
        var host = trigger.closest('.cg-chat, .cw-convo, .tk-ms-pane');
        if (!host) return;
        var existing = host.querySelector('.message-history-panel');
        if (existing) { existing.querySelector('input').focus(); return; }
        var menu = trigger.closest('.tk-ms-more');
        var returnFocus = menu ? menu.querySelector('summary') : (trigger.closest('.cg-group-menu') ? host.querySelector('[data-cg-group-menu-toggle]') : trigger);
        if (menu) menu.open = false;
        var endpoint = trigger.dataset.messageHistory;
        var cursorName = trigger.dataset.messageHistoryCursor || 'before';
        var panel = node('section', 'message-history-panel');
        panel.setAttribute('aria-label', 'Tìm tin nhắn trong hội thoại');
        var form = node('form', 'message-history-toolbar');
        var back = node('button', 'message-history-back'); back.type = 'button';
        back.setAttribute('aria-label', 'Quay lại hội thoại');
        back.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
        var field = node('div', 'message-history-field');
        field.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>';
        var input = node('input'); input.type = 'search'; input.required = true;
        input.minLength = 2; input.maxLength = 100; input.autocomplete = 'off';
        input.placeholder = 'Tìm tin nhắn'; input.setAttribute('aria-label', 'Từ khóa tìm tin nhắn');
        var submit = node('button', 'message-history-submit'); submit.type = 'submit';
        submit.setAttribute('aria-label', 'Tìm tin nhắn'); submit.title = 'Tìm tin nhắn';
        submit.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>';
        field.append(input); form.append(back, field, submit);
        var status = node('p', 'message-history-status', 'Nhập từ 2 ký tự để tìm trong hội thoại này.');
        status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        var results = node('div', 'message-history-results');
        results.setAttribute('role', 'region'); results.setAttribute('aria-label', 'Kết quả tìm kiếm'); results.tabIndex = 0;
        var more = node('button', 'message-history-more', 'Xem thêm'); more.type = 'button'; more.hidden = true;
        panel.append(form, status, results, more);
        var query = '', cursor = 0, generation = 0, pending = null, busy = false, closed = false, timer;
        var observer, widget = host.closest('#chat-widget'), inbox = host.closest('.tk-ms');
        var positions = Array.from(host.querySelectorAll('.tk-ms-body, .cw-body, .cg-feed')).map(function (feed) { return {feed:feed, top:feed.scrollTop}; });
        function cleanup(restoreFocus) {
            if (closed) return;
            closed = true; generation++; clearTimeout(timer);
            if (pending) pending.abort();
            if (observer) observer.disconnect();
            if (widget) widget.removeEventListener('cw:conversation-change', switched);
            if (inbox) inbox.removeEventListener('inbox:change', switched);
            host.classList.remove('has-message-history'); panel.remove();
            positions.forEach(function (position) { if (position.feed.isConnected) position.feed.scrollTop = position.top; });
            if (restoreFocus && returnFocus && returnFocus.isConnected && host.isConnected) returnFocus.focus({preventScroll:true});
        }
        function switched() { cleanup(false); }
        panel.closeHistory = function () { cleanup(false); };
        back.onclick = function () { cleanup(true); };
        panel.addEventListener('keydown', function (event) { if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); cleanup(true); } });
        function search(append) {
            if (closed || (append && busy)) return;
            clearTimeout(timer);
            var token = ++generation;
            if (pending) pending.abort();
            if (!append) {
                query = input.value.trim(); cursor = 0; results.replaceChildren(); more.hidden = true;
                if (query.length < 2) { busy = false; status.textContent = 'Nhập từ 2 ký tự để tìm trong hội thoại này.'; return; }
            }
            var url = new URL(endpoint, window.location.href); url.searchParams.set('q', query);
            if (append && cursor) url.searchParams.set(cursorName, cursor);
            pending = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            busy = true; more.disabled = true; status.textContent = 'Đang tìm tin nhắn…';
            var options = {credentials:'same-origin', cache:'no-store', headers:{'X-Requested-With':'XMLHttpRequest'}};
            if (pending) options.signal = pending.signal;
            fetch(url.href, options).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) throw new Error(data.message || 'Không tìm được tin nhắn.');
                    return data;
                });
            }).then(function (data) {
                if (closed || token !== generation || !panel.isConnected) return;
                (data.messages || []).forEach(function (message) {
                    var item = node('article');
                    var sender = message.mine ? 'Bạn' : (message.sender ? message.sender.name : 'Đối phương');
                    item.append(node('small', '', sender + ' · ' + message.created_at));
                    item.append(node('p', '', message.type === 'image' ? (message.caption || '[Hình ảnh]') : message.content));
                    results.append(item);
                });
                cursor = data.next_before || 0; more.hidden = !data.has_more || !cursor;
                status.textContent = results.children.length ? 'Đã tìm thấy ' + results.children.length + ' tin nhắn.' : 'Không tìm thấy tin nhắn phù hợp.';
            }).catch(function (error) {
                if (!closed && token === generation && panel.isConnected) status.textContent = error.message || 'Lỗi kết nối. Vui lòng thử lại.';
            }).then(function () { if (!closed && token === generation) { busy = false; more.disabled = false; } });
        }
        form.onsubmit = function (event) { event.preventDefault(); search(false); };
        input.addEventListener('input', function () {
            generation++; if (pending) pending.abort(); clearTimeout(timer); busy = false;
            results.replaceChildren(); more.hidden = true;
            status.textContent = input.value.trim().length < 2 ? 'Nhập từ 2 ký tự để tìm trong hội thoại này.' : 'Đang tìm tin nhắn…';
            timer = setTimeout(function () { search(false); }, 300);
        });
        more.onclick = function () { search(true); };
        host.classList.add('has-message-history'); host.append(panel);
        if (widget) widget.addEventListener('cw:conversation-change', switched);
        if (inbox) inbox.addEventListener('inbox:change', switched);
        observer = new MutationObserver(function () { if (!panel.isConnected || host.closest('[hidden]')) cleanup(false); });
        observer.observe(document.body, {childList:true, subtree:true, attributes:true, attributeFilter:['hidden']});
        input.focus();
    });
})();
