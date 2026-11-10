(function () {
    'use strict';
    var text = 'Tin nhắn đã được thu hồi';
    function json(url, options) {
        return fetch(url, Object.assign({credentials: 'same-origin', cache: 'no-store'}, options)).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) throw new Error(data.message || 'Không thực hiện được thao tác thu hồi.');
                return data;
            });
        });
    }
    function apply(message) {
        if (!message || message.dataset.recalled === '1') return false;
        message.dataset.recalled = '1';
        message.classList.remove('is-image');
        message.classList.add('is-recalled');
        var content = message.querySelector('.cw-msg-col') || message;
        Array.from(content.children).forEach(function (child) {
            if (!child.matches('small, .cw-msg-name, .chat-peer-avatar')) child.remove();
        });
        var label = document.createElement('p'); label.textContent = text;
        content.insertBefore(label, content.querySelector('small'));
        message.dispatchEvent(new CustomEvent('message-recalled', {bubbles: true, detail: {id: message.dataset.messageId}}));
        return true;
    }
    function clearBefore(feed, value) {
        var cutoff = Math.max(Number(feed.dataset.deletedThrough || 0), Number(value || 0));
        if (!Number.isFinite(cutoff) || cutoff < 0) return false;
        feed.dataset.deletedThrough = String(cutoff);
        if (window.MessageReply) window.MessageReply.invalidate(feed,[],cutoff);
        var removed = false;
        feed.querySelectorAll('[data-message-id]').forEach(function (message) {
            if (Number(message.dataset.messageId) <= cutoff) { message.remove(); removed = true; }
        });
        if (removed) feed.dispatchEvent(new CustomEvent('conversation-history-cleared', {bubbles:true,detail:{cutoff:cutoff}}));
        return removed;
    }
    function watch(feed, getEndpoint, onChange) {
        var reading = false;
        function applyIds(ids) {
            var changed = false;
            if (window.MessageReply && window.MessageReply.invalidate(feed,ids,Number(feed.dataset.deletedThrough || 0))) changed = true;
            (ids || []).forEach(function (id) {
                id = String(id);
                if (!/^[1-9]\d*$/.test(id)) return;
                var message = feed.querySelector('[data-message-id="' + id + '"]');
                if (apply(message)) changed = true;
            });
            if (changed && onChange) onChange();
        }
        async function refresh() {
            var endpoint = getEndpoint();
            if (!endpoint || reading || document.hidden || !feed.isConnected || !feed.getClientRects().length) return;
            var ids = Array.from(feed.querySelectorAll('[data-message-id]'))
                .filter(function (message) { return message.dataset.recalled !== '1'; })
                .map(function (message) { return message.dataset.messageId; })
                .filter(function (id) { return /^[1-9]\d*$/.test(id); });
            feed.querySelectorAll('[data-reply-to-id]').forEach(function (quote) { ids.push(quote.dataset.replyToId); });
            if (window.MessageReply) ids = ids.concat(window.MessageReply.pendingIds(feed));
            ids = Array.from(new Set(ids)).filter(function (id) { return /^[1-9]\d*$/.test(id); });
            reading = true;
            try {
                for (var offset = 0; offset < Math.max(1, ids.length); offset += 100) {
                    if (getEndpoint() !== endpoint) break;
                    var data = await json(endpoint + '?ids=' + ids.slice(offset, offset + 100).join(','));
                    if (getEndpoint() !== endpoint) break;
                    if (clearBefore(feed, data.deleted_through_message_id) && onChange) onChange();
                    applyIds(data.recalled_ids);
                }
            } catch (_) {
                // Thử lại ở chu kỳ sau khi kết nối trở lại.
            } finally { reading = false; }
        }
        document.addEventListener('private-message-recalled', function (event) {
            if (getEndpoint() === event.detail.endpoint) applyIds([event.detail.id]);
        });
        document.addEventListener('visibilitychange', refresh);
        new MutationObserver(refresh).observe(feed, {childList: true});
        setInterval(refresh, 4000);
        refresh();
        return refresh;
    }
    function recall(endpoint, id) {
        return json(endpoint + '?ids=' + encodeURIComponent(id)).then(function (state) {
            var body = new FormData();
            body.set('action_token', state.action_token);
            body.set('message_id', String(id));
            return json(endpoint, {method: 'POST', body: body});
        }).then(function (result) {
            document.dispatchEvent(new CustomEvent('private-message-recalled', {detail: {endpoint: endpoint, id: result.id}}));
            return result;
        });
    }
    window.MessageRecall = {apply: apply, watch: watch, recall: recall, clearBefore:clearBefore};
})();
