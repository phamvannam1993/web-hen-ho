(function () {
    'use strict';
    var feed = document.getElementById('chat-body');
    if (!feed || !feed.dataset.pinUrl) return;
    var pane = feed.closest('.tk-ms-pane');
    var endpoint = feed.dataset.pinUrl;
    var nativePopover = typeof HTMLElement.prototype.showPopover === 'function';
    var token = '', pinned = null, available = false, reading = false, writing = false, version = 0;
    var selected = null, trigger = null, copyValue = '';
    function node(tag, cls, text) {
        var item = document.createElement(tag);
        if (cls) item.className = cls;
        if (text !== undefined) item.textContent = text;
        return item;
    }
    var paths = {
        reply: '<path d="m9 5-7 7 7 7v-5c6 0 10 1 13 5-1-8-5-11-13-11z"/>',
        pin: '<path d="m16 3 5 5-4 1-3 6-2 2-5-5 2-2 6-3zM7 17l-4 4"/>',
        unpin: '<path d="m3 3 18 18M16 3l5 5-4 1-2 4M9 9l-2 3 5 5 2-2M7 17l-4 4"/>',
        copy: '<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        recall: '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>'
    };
    function action(button, label, icon) {
        button.textContent = '';
        var mark = node('span', 'pm-action-icon'); mark.setAttribute('aria-hidden','true');
        mark.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'+paths[icon]+'</svg>';
        button.append(mark,node('span','pm-action-label',label));
    }
    var strip = node('div', 'pm-pin-strip'); strip.hidden = true;
    var stripText = node('span');
    var unpin = node('button', '', 'Bỏ ghim'); unpin.type = 'button';
    strip.append(stripText, unpin); feed.before(strip);
    var status = node('p', 'pm-action-status'); status.hidden = true;
    status.setAttribute('role', 'status'); feed.before(status);
    var menu = node('div', 'pm-message-menu'); menu.setAttribute('popover', 'auto');
    menu.setAttribute('aria-label', 'Thao tác tin nhắn');
    var pin = node('button', '', 'Ghim'); pin.type = 'button';
    var copy = node('button', '', 'Sao chép'); copy.type = 'button';
    var recall = node('button', '', 'Thu hồi'); recall.type = 'button'; recall.hidden = true;
    var reply = node('button', '', 'Trả lời'); reply.type = 'button';
    action(reply,'Trả lời','reply'); action(pin,'Ghim','pin');
    action(copy,'Sao chép','copy'); action(recall,'Thu hồi','recall');
    recall.classList.add('pm-action-danger');
    var form = pane.querySelector('.chat-form');
    if (window.MessageReply) window.MessageReply.mount(feed,form);
    reply.disabled = !form;
    menu.append(reply, pin, copy, recall); pane.append(menu);
    function notify(text) { status.textContent = text; status.hidden = !text; }
    function opened() { return nativePopover ? menu.matches(':popover-open') : menu.classList.contains('is-open'); }
    function closeMenu(focus) {
        if (nativePopover) { if (opened()) menu.hidePopover(); }
        else menu.classList.remove('is-open');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
            if (focus && trigger.isConnected) trigger.focus({preventScroll: true});
        }
    }
    menu.addEventListener('toggle', function () { if (trigger) trigger.setAttribute('aria-expanded', opened() ? 'true' : 'false'); });
    function messageText(message) {
        var text = message.querySelector('p');
        if (text) return text.textContent;
        var image = message.querySelector('a[href]');
        return image ? image.href : '';
    }
    function showMenu(button, message) {
        if (message.dataset.recalled === '1') return;
        if (trigger === button && opened()) { closeMenu(false); return; }
        closeMenu(false); trigger = button; selected = message;
        copyValue = messageText(message);
        var isPinned = pinned && String(pinned.id) === message.dataset.messageId;
        action(pin,isPinned ? 'Bỏ ghim' : 'Ghim',isPinned ? 'unpin' : 'pin');
        pin.disabled = !available || !token || writing;
        copy.disabled = !copyValue;
        recall.hidden = !message.classList.contains('mine');
        recall.disabled = writing || !feed.dataset.recallUrl;
        reply.disabled = !form || writing;
        if (nativePopover) menu.showPopover(); else menu.classList.add('is-open');
        button.setAttribute('aria-expanded', 'true');
        var viewport = window.visualViewport;
        var left = viewport ? viewport.offsetLeft : 0, top = viewport ? viewport.offsetTop : 0;
        var width = viewport ? viewport.width : window.innerWidth, height = viewport ? viewport.height : window.innerHeight;
        menu.style.maxHeight = Math.max(0, height - 16) + 'px';
        var bounds = button.getBoundingClientRect();
        menu.style.left = Math.max(left + 8, Math.min(bounds.left, left + width - menu.offsetWidth - 8)) + 'px';
        var y = bounds.bottom + 6;
        if (y + menu.offsetHeight > top + height - 8) y = bounds.top - menu.offsetHeight - 6;
        menu.style.top = Math.max(top + 8, y) + 'px';
    }
    function decorate(message) {
            if (!message.matches || !message.matches('.chat-msg[data-message-id]') || message.dataset.recalled === '1' || message.querySelector('.pm-message-more')) return;
            var button = node('button', 'pm-message-more', '⋯'); button.type = 'button';
            button.setAttribute('aria-label', 'Thao tác tin nhắn'); button.setAttribute('title', 'Thao tác tin nhắn');
            button.setAttribute('aria-haspopup', 'true'); button.setAttribute('aria-expanded', 'false');
            var wasOpen = false;
            button.addEventListener('pointerdown', function () { wasOpen = trigger === button && opened(); });
            button.addEventListener('pointercancel', function () { wasOpen = false; });
            button.addEventListener('keydown', function () { wasOpen = false; });
            button.onclick = function () {
                if (wasOpen) { wasOpen = false; closeMenu(false); return; }
                showMenu(button, message);
            };
            message.append(button);
    }
    function applyState(data) {
        available = data.pins_available === true;
        pinned = available ? data.pinned : null;
        strip.hidden = !pinned;
        stripText.textContent = pinned ? 'Đã ghim: ' + (pinned.type === 'image' ? '[Hình ảnh]' : pinned.content) : '';
        unpin.disabled = writing;
        if (selected) {
            var isPinned = pinned && String(pinned.id) === selected.dataset.messageId;
            action(pin,isPinned ? 'Bỏ ghim' : 'Ghim',isPinned ? 'unpin' : 'pin');
            pin.disabled = !available || !token || writing;
        }
    }
    function json(url, options) {
        return fetch(url, options).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) throw new Error(data.message || 'Không thực hiện được thao tác.');
                return data;
            });
        });
    }
    function refresh() {
        if (reading || writing || document.hidden || pane.hidden || !feed.isConnected) return;
        reading = true; var current = version;
        json(endpoint, {credentials: 'same-origin'}).then(function (data) {
            if (current !== version || !feed.isConnected) return;
            token = data.action_token || ''; applyState(data);
            if (!available) notify('Ghim tin nhắn chưa sẵn sàng trên hệ thống.');
        }).catch(function (error) { if (current === version) notify(error.message); })
        .then(function () { reading = false; if (current !== version) refresh(); });
    }
    function save(messageId, action) {
        if (writing || !token || !available) return;
        writing = true; version++; closeMenu(true); notify('');
        pin.disabled = true; unpin.disabled = true;
        var current = version;
        var body = new FormData(); body.set('action_token', token);
        body.set('message_id', String(messageId)); body.set('action', action);
        json(endpoint, {method: 'POST', credentials: 'same-origin', body: body}).then(function (data) {
            if (current === version) { applyState(data); notify(''); }
        }).catch(function (error) { notify(error.message); })
        .then(function () { writing = false; applyState({pins_available: available, pinned: pinned}); refresh(); });
    }
    pin.onclick = function () {
        if (!selected || !selected.isConnected) return;
        var id = selected.dataset.messageId;
        save(id, pinned && String(pinned.id) === id ? 'unpin' : 'pin');
    };
    unpin.onclick = function () { if (pinned) save(pinned.id, 'unpin'); };
    reply.onclick = function () {
        if (!selected || writing || !window.MessageReply) return;
        closeMenu(false); window.MessageReply.select(form,selected);
    };
    recall.onclick = function () {
        if (writing || !selected || !selected.isConnected || !selected.classList.contains('mine')
            || selected.dataset.recalled === '1' || !window.MessageRecall) return;
        var message = selected;
        writing = true; version++; closeMenu(true); notify('');
        recall.disabled = true; pin.disabled = true; unpin.disabled = true;
        window.MessageRecall.recall(feed.dataset.recallUrl, message.dataset.messageId).then(function () {
            window.MessageRecall.apply(message);
        }).catch(function (error) { notify(error.message); })
        .then(function () { writing = false; recall.disabled = false; refresh(); });
    };
    feed.addEventListener('message-recalled', function (event) {
        version++;
        document.querySelectorAll('.tk-ms-item[data-conversation-id][data-last-message-id]').forEach(function (item) {
            if (item.dataset.conversationId === feed.dataset.conversation && item.dataset.lastMessageId === event.detail.id) {
                var preview = item.querySelector('.tk-ms-item__l');
                if (preview) preview.textContent = 'Tin nhắn đã được thu hồi';
            }
        });
        if (selected === event.target) {
            closeMenu(false); selected = null; trigger = null; copyValue = '';
        }
        if (pinned && String(pinned.id) === event.detail.id) {
            applyState({pins_available: available, pinned: null});
        }
    });
    var refreshRecalls = window.MessageRecall && window.MessageRecall.watch(feed, function () {
        return pane.hidden ? null : feed.dataset.recallUrl;
    });
    feed.addEventListener('conversation-history-cleared',function (event) {
        version++; closeMenu(false); selected = null; trigger = null; copyValue = '';
        applyState({pins_available:available,pinned:null});
        pane.querySelectorAll('.message-history-dialog[open]').forEach(function (dialog) { dialog.close(); });
        document.querySelectorAll('.tk-ms-item[data-conversation-id][data-last-message-id]').forEach(function (item) {
            if (item.dataset.conversationId === feed.dataset.conversation && Number(item.dataset.lastMessageId) <= event.detail.cutoff) item.closest('li').remove();
        });
    });
    copy.onclick = function () {
        var text = copyValue; closeMenu(true);
        function fallback() {
            var area = node('textarea'); area.value = text;
            area.className = 'pm-copy-area'; pane.append(area); area.select();
            var copied = false;
            try { copied = document.execCommand('copy'); } catch (_) {}
            area.remove(); if (trigger && trigger.isConnected) trigger.focus({preventScroll: true});
            notify(copied ? '' : 'Chưa sao chép được. Hãy chọn nội dung tin nhắn để sao chép.');
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { notify(''); }, fallback);
        } else fallback();
    };
    document.addEventListener('click', function (event) {
        if (opened() && !menu.contains(event.target) && trigger && !trigger.contains(event.target)) closeMenu(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && opened()) { event.preventDefault(); closeMenu(true); }
    });
    feed.addEventListener('scroll', function () { closeMenu(false); }, {passive: true});
    window.addEventListener('resize', function () { closeMenu(false); });
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', function () { closeMenu(false); });
        window.visualViewport.addEventListener('scroll', function () { closeMenu(false); });
    }
    new MutationObserver(function (changes) {
        changes.forEach(function (change) { change.addedNodes.forEach(decorate); });
    }).observe(feed, {childList: true});
    new MutationObserver(function () {
        if (pane.hidden) closeMenu(false);
        else { refresh(); if (refreshRecalls) refreshRecalls(); }
    })
        .observe(pane, {attributes: true, attributeFilter: ['hidden']});
    document.addEventListener('visibilitychange', refresh);
    feed.querySelectorAll('.chat-msg[data-message-id]').forEach(decorate);
    refresh(); setInterval(refresh, 4000);
})();
