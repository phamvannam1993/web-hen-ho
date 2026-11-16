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
        input.setAttribute('enterkeyhint', 'search');
        field.append(input); form.append(back, field);
        var status = node('p', 'message-history-status', 'Nhập từ 2 ký tự để tìm trong hội thoại này.');
        status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        var results = node('div', 'message-history-results');
        results.setAttribute('role', 'region'); results.setAttribute('aria-label', 'Kết quả tìm kiếm'); results.tabIndex = 0;
        var more = node('button', 'message-history-more', 'Xem thêm'); more.type = 'button'; more.hidden = true;
        var navigation = node('div', 'message-history-navigation');
        var count = node('button', 'message-history-count', 'Kết quả'); count.type = 'button'; count.setAttribute('aria-label','Xem danh sách kết quả');
        var older = node('button', 'message-history-previous', '↑'), newer = node('button', 'message-history-next', '↓');
        older.type = newer.type = 'button'; older.setAttribute('aria-label','Kết quả cũ hơn'); newer.setAttribute('aria-label','Kết quả mới hơn');
        navigation.append(count,older,newer); navigation.hidden = true;
        var context = node('div', 'message-history-results message-history-context'); context.hidden = true;
        context.setAttribute('role','region'); context.setAttribute('aria-label','Đoạn hội thoại chứa tin nhắn tìm thấy'); context.tabIndex = 0;
        panel.append(form, navigation, status, results, context, more);
        var query = '', cursor = 0, generation = 0, pending = null, busy = false, closed = false, timer;
        var matches = [], selected = -1, hasMore = false, jumpVersion = 0, jumpPending = null, jumping = false;
        function controls() {
            navigation.hidden = !matches.length;
            count.textContent = (selected >= 0 ? (selected + 1) + ' / ' : '') + matches.length + (hasMore ? '+' : '') + ' kết quả';
            older.disabled = busy || jumping || (!hasMore && selected >= matches.length - 1);
            newer.disabled = busy || jumping || selected <= 0;
            more.hidden = !hasMore || !cursor || !context.hidden;
        }
        function cancelJump() { jumpVersion++; if (jumpPending) jumpPending.abort(); jumping = false; }
        function showResults() { cancelJump(); context.hidden = true; results.hidden = false; controls(); }
        count.onclick = showResults;
        function jump(index) {
            var message = matches[index]; if (!message || closed) return;
            cancelJump(); var version = jumpVersion, revision = generation;
            selected = index; jumping = true; context.replaceChildren(); context.hidden = false; results.hidden = true;
            status.textContent = 'Đang mở vị trí tin nhắn…'; controls();
            var url = new URL(endpoint, location.href); url.searchParams.set('around',message.id);
            jumpPending = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            var options = {credentials:'same-origin',cache:'no-store',headers:{'X-Requested-With':'XMLHttpRequest'}};
            if (jumpPending) options.signal = jumpPending.signal;
            fetch(url.href,options).then(function (response) { return response.json().then(function (data) {
                if (!response.ok || !data.ok) throw new Error(data.message || 'Không mở được vị trí tin nhắn.'); return data;
            }); }).then(function (data) {
                if (closed || revision !== generation || version !== jumpVersion) return;
                var target;
                (data.messages || []).forEach(function (entry) {
                    var row = node('article','message-history-context-message' + (entry.mine ? ' is-mine' : ''));
                    row.dataset.messageId = entry.id;
                    row.append(node('small','',(entry.mine ? 'Bạn' : (entry.sender ? entry.sender.name : 'Đối phương')) + ' · ' + entry.created_at));
                    if (entry.type === 'image') {
                        var image = node('img'), link = node('a'); image.src = entry.content; image.alt = 'Ảnh trong hội thoại';
                        link.href = entry.content; link.setAttribute('data-chat-image',''); link.append(image); row.append(link);
                        if (entry.caption) row.append(node('p','',entry.caption));
                    } else row.append(node('p','',entry.content));
                    if (Number(entry.id) === Number(message.id)) { row.classList.add('is-target'); target = row; }
                    context.append(row);
                });
                if (!target) throw new Error('Tin nhắn không còn khả dụng.');
                context.scrollTop = Math.max(0,target.offsetTop - (context.clientHeight - target.offsetHeight) / 2);
                status.textContent = 'Tin nhắn được tìm thấy đã được đánh dấu. Dùng ↑ / ↓ để chuyển kết quả.';
                results.querySelectorAll('[data-result-id]').forEach(function (row) { row.setAttribute('aria-pressed',String(Number(row.dataset.resultId) === Number(message.id))); });
            }).catch(function (error) {
                if (!closed && revision === generation && version === jumpVersion) { context.replaceChildren(); status.textContent = error.message; }
            }).then(function () { if (!closed && version === jumpVersion) { jumping = false; controls(); } });
        }
        older.onclick = function () { if (selected + 1 < matches.length) jump(selected + 1); else if (hasMore) search(true,true); };
        newer.onclick = function () { if (selected > 0) jump(selected - 1); };
        var observer, widget = host.closest('#chat-widget'), inbox = host.closest('.tk-ms');
        var positions = Array.from(host.querySelectorAll('.tk-ms-body, .cw-body, .cg-feed')).map(function (feed) { return {feed:feed, top:feed.scrollTop}; });
        function cleanup(restoreFocus) {
            if (closed) return;
            closed = true; generation++; clearTimeout(timer);
            cancelJump();
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
        function search(append, advance) {
            if (closed || (append && busy)) return;
            clearTimeout(timer);
            var token = ++generation;
            cancelJump();
            if (pending) pending.abort();
            if (!append) {
                query = input.value.trim(); cursor = 0; results.replaceChildren(); more.hidden = true;
                matches = []; selected = -1; hasMore = false; context.replaceChildren(); context.hidden = true; results.hidden = false; controls();
                if (query.length < 2) { busy = false; status.textContent = 'Nhập từ 2 ký tự để tìm trong hội thoại này.'; return; }
            }
            var url = new URL(endpoint, window.location.href); url.searchParams.set('q', query);
            if (append && cursor) url.searchParams.set(cursorName, cursor);
            pending = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            busy = true; more.disabled = true; status.textContent = 'Đang tìm tin nhắn…';
            controls();
            var options = {credentials:'same-origin', cache:'no-store', headers:{'X-Requested-With':'XMLHttpRequest'}};
            if (pending) options.signal = pending.signal;
            fetch(url.href, options).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) throw new Error(data.message || 'Không tìm được tin nhắn.');
                    return data;
                });
            }).then(function (data) {
                if (closed || token !== generation || !panel.isConnected) return;
                var firstNew = matches.length;
                (data.messages || []).forEach(function (message) {
                    if (matches.some(function (entry) { return Number(entry.id) === Number(message.id); })) return;
                    var index = matches.length; matches.push(message);
                    var item = node('article');
                    item.dataset.resultId = message.id; item.setAttribute('role','button'); item.setAttribute('aria-pressed','false'); item.tabIndex = 0;
                    item.onclick = function () { jump(index); };
                    item.onkeydown = function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); jump(index); } };
                    var sender = message.mine ? 'Bạn' : (message.sender ? message.sender.name : 'Đối phương');
                    item.append(node('small', '', sender + ' · ' + message.created_at));
                    item.append(node('p', '', message.type === 'image' ? (message.caption || '[Hình ảnh]') : message.content));
                    results.append(item);
                });
                cursor = data.next_before || 0; hasMore = !!data.has_more && !!cursor;
                if (append && matches.length === firstNew) hasMore = false;
                status.textContent = results.children.length ? 'Đã tìm thấy ' + results.children.length + ' tin nhắn.' : 'Không tìm thấy tin nhắn phù hợp.';
                controls();
                if (!append && matches.length) jump(0);
                else if (advance && matches.length > firstNew) jump(firstNew);
            }).catch(function (error) {
                if (!closed && token === generation && panel.isConnected) status.textContent = error.message || 'Lỗi kết nối. Vui lòng thử lại.';
            }).then(function () { if (!closed && token === generation) { busy = false; more.disabled = false; controls(); } });
        }
        form.onsubmit = function (event) { event.preventDefault(); search(false); };
        input.addEventListener('input', function () {
            generation++; if (pending) pending.abort(); clearTimeout(timer); busy = false;
            cancelJump(); matches = []; selected = -1; hasMore = false; context.replaceChildren(); context.hidden = true; results.hidden = false; controls();
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
