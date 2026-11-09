(function () {
    'use strict';
    var serial = 0;
    function node(tag, cls, text) {
        var item = document.createElement(tag);
        if (cls) item.className = cls;
        if (text !== undefined) item.textContent = text;
        return item;
    }
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-message-history]');
        if (!trigger || !trigger.dataset.messageHistory) return;
        event.preventDefault();
        if (document.querySelector('.message-history-dialog[open]')) return;
        var dialog = node('dialog', 'message-history-dialog');
        if (typeof dialog.showModal !== 'function') {
            window.alert('Vui lòng cập nhật trình duyệt để dùng chức năng tìm tin nhắn.');
            return;
        }
        var host = trigger.closest('.cg-chat, .cw-convo, .tk-ms-pane');
        if (!host) return;
        var endpoint = trigger.dataset.messageHistory;
        var cursorName = trigger.dataset.messageHistoryCursor || 'before';
        var heading = node('h2', '', 'Tìm tin nhắn cũ');
        heading.id = 'message-history-title-' + (++serial);
        dialog.setAttribute('aria-labelledby', heading.id);
        var header = node('header');
        var close = node('button', '', 'Đóng'); close.type = 'button';
        close.onclick = function () { dialog.close(); };
        header.append(heading, close);
        var form = node('form');
        var input = node('input'); input.type = 'search'; input.required = true;
        input.minLength = 2; input.maxLength = 100; input.autofocus = true;
        input.placeholder = 'Nhập từ khóa trong tin nhắn…';
        input.setAttribute('aria-label', 'Từ khóa tìm tin nhắn');
        var submit = node('button'); submit.type = 'submit';
        submit.setAttribute('aria-label', 'Tìm tin nhắn');
        submit.title = 'Tìm tin nhắn';
        submit.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" style="display:block"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.34-4.34"/></svg>';
        form.append(input, submit);
        var status = node('p', 'message-history-status', 'Nhập từ 2 ký tự để tìm trong hội thoại này.');
        status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        var results = node('div', 'message-history-results');
        results.setAttribute('role', 'region'); results.setAttribute('aria-label', 'Kết quả tìm kiếm');
        results.tabIndex = 0;
        var more = node('button', 'message-history-more', 'Xem thêm');
        more.type = 'button'; more.hidden = true;
        dialog.append(header, form, status, results, more);
        var query = '', cursor = 0, generation = 0, pending = null, busy = false, closed = false;
        var viewport = window.visualViewport, timer, observer;
        function cleanup() {
            if (closed) return;
            closed = true; generation++;
            if (pending) pending.abort();
            clearTimeout(timer);
            window.removeEventListener('resize', fit);
            if (viewport) {
                viewport.removeEventListener('resize', fit);
                viewport.removeEventListener('scroll', fit);
            }
            if (observer) observer.disconnect();
            dialog.remove();
            if (trigger.isConnected && host.isConnected) trigger.focus({preventScroll: true});
        }
        function fit() {
            if (!dialog.isConnected) { cleanup(); return; }
            var height = viewport ? viewport.height : window.innerHeight;
            dialog.style.top = ((viewport ? viewport.offsetTop : 0) + 12) + 'px';
            dialog.style.height = Math.max(0, Math.min(560, height - 24)) + 'px';
        }
        function search(append) {
            if (append && busy) return;
            var token = ++generation;
            if (pending) pending.abort();
            if (!append) {
                query = input.value.trim(); cursor = 0;
                results.textContent = ''; more.hidden = true;
                if (query.length < 2) {
                    busy = false; status.textContent = 'Vui lòng nhập ít nhất 2 ký tự.'; return;
                }
            }
            var url = new URL(endpoint, window.location.href);
            url.searchParams.set('q', query);
            if (append && cursor) url.searchParams.set(cursorName, cursor);
            pending = typeof window.AbortController === 'function' ? new window.AbortController() : null;
            busy = true; more.disabled = true; status.textContent = 'Đang tìm tin nhắn…';
            var options = {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}};
            if (pending) options.signal = pending.signal;
            fetch(url.href, options).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) throw new Error(data.message || 'Không tìm được tin nhắn.');
                    return data;
                });
            }).then(function (data) {
                if (closed || token !== generation || !dialog.isConnected) return;
                (data.messages || []).forEach(function (message) {
                    var item = node('article');
                    var sender = message.mine ? 'Bạn' : (message.sender ? message.sender.name : 'Đối phương');
                    item.append(node('small', '', sender + ' · ' + message.created_at));
                    item.append(node('p', '', message.type === 'image' ? (message.caption || '[Hình ảnh]') : message.content));
                    results.append(item);
                });
                cursor = data.next_before || 0;
                more.hidden = !data.has_more || !cursor;
                status.textContent = results.children.length ? 'Đã tìm thấy ' + results.children.length + ' tin nhắn.' : 'Không tìm thấy tin nhắn phù hợp.';
            }).catch(function (error) {
                if (!closed && token === generation && dialog.isConnected) status.textContent = error.message || 'Lỗi kết nối. Vui lòng thử lại.';
            }).then(function () {
                if (!closed && token === generation) { busy = false; more.disabled = false; }
            });
        }
        form.onsubmit = function (event) { event.preventDefault(); search(false); };
        more.onclick = function () { search(true); };
        dialog.addEventListener('close', cleanup);
        host.append(dialog);
        observer = new MutationObserver(function () { if (!dialog.isConnected) cleanup(); });
        observer.observe(document.body, {childList: true, subtree: true});
        window.addEventListener('resize', fit);
        if (viewport) {
            viewport.addEventListener('resize', fit);
            viewport.addEventListener('scroll', fit);
        }
        fit(); dialog.showModal();
        timer = setTimeout(fit, 350);
    });
})();
