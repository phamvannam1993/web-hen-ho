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
    menu.append(pin, copy); pane.append(menu);
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
        if (trigger === button && opened()) { closeMenu(false); return; }
        closeMenu(false); trigger = button; selected = message;
        copyValue = messageText(message);
        pin.textContent = pinned && String(pinned.id) === message.dataset.messageId ? 'Bỏ ghim' : 'Ghim';
        pin.disabled = !available || !token || writing;
        copy.disabled = !copyValue;
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
            if (!message.matches || !message.matches('.chat-msg[data-message-id]') || message.querySelector('.pm-message-more')) return;
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
            pin.textContent = pinned && String(pinned.id) === selected.dataset.messageId ? 'Bỏ ghim' : 'Ghim';
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
        var body = new FormData(); body.set('action_token', token);
        body.set('message_id', String(messageId)); body.set('action', action);
        json(endpoint, {method: 'POST', credentials: 'same-origin', body: body}).then(function (data) {
            applyState(data); notify(action === 'pin' ? 'Đã ghim tin nhắn cho cả hai người.' : 'Đã bỏ ghim tin nhắn.');
        }).catch(function (error) { notify(error.message); })
        .then(function () { writing = false; applyState({pins_available: available, pinned: pinned}); refresh(); });
    }
    pin.onclick = function () {
        if (!selected || !selected.isConnected) return;
        var id = selected.dataset.messageId;
        save(id, pinned && String(pinned.id) === id ? 'unpin' : 'pin');
    };
    unpin.onclick = function () { if (pinned) save(pinned.id, 'unpin'); };
    copy.onclick = function () {
        var text = copyValue; closeMenu(true);
        function fallback() {
            var area = node('textarea'); area.value = text;
            area.className = 'pm-copy-area'; pane.append(area); area.select();
            var copied = false;
            try { copied = document.execCommand('copy'); } catch (_) {}
            area.remove(); if (trigger && trigger.isConnected) trigger.focus({preventScroll: true});
            notify(copied ? 'Đã sao chép.' : 'Chưa sao chép được. Hãy chọn nội dung tin nhắn để sao chép.');
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { notify('Đã sao chép.'); }, fallback);
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
    new MutationObserver(function () { if (pane.hidden) closeMenu(false); else refresh(); })
        .observe(pane, {attributes: true, attributeFilter: ['hidden']});
    document.addEventListener('visibilitychange', refresh);
    feed.querySelectorAll('.chat-msg[data-message-id]').forEach(decorate);
    refresh(); setInterval(refresh, 4000);
})();
