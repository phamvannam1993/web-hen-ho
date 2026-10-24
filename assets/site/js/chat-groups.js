(function () {
    'use strict';
    var nativePopover = typeof HTMLElement.prototype.showPopover === 'function';
    var fallbackPopover = null;
    function popoverOpen(panel) {
        return nativePopover ? panel.matches(':popover-open') : panel.classList.contains('cg-popover-open');
    }
    function closePopover(panel) {
        if (nativePopover) { if (popoverOpen(panel)) panel.hidePopover(); return; }
        panel.classList.remove('cg-popover-open');
        if (panel.cgAnchor) { panel.cgAnchor.replaceWith(panel); panel.cgAnchor = null; }
        if (panel.cgTrigger) panel.cgTrigger.setAttribute('aria-expanded', 'false');
        if (fallbackPopover === panel) fallbackPopover = null;
    }
    function showPopover(panel, trigger, alignRight) {
        if (popoverOpen(panel)) { closePopover(panel); return; }
        if (nativePopover) panel.showPopover();
        else {
            if (fallbackPopover) closePopover(fallbackPopover);
            panel.cgAnchor = document.createComment('chat menu');
            panel.before(panel.cgAnchor);
            document.body.append(panel);
            panel.classList.add('cg-popover-open');
            panel.cgTrigger = trigger; fallbackPopover = panel;
        }
        trigger.setAttribute('aria-expanded', 'true');
        var viewport = window.visualViewport;
        var left = viewport ? viewport.offsetLeft : 0, top = viewport ? viewport.offsetTop : 0;
        var width = viewport ? viewport.width : window.innerWidth, height = viewport ? viewport.height : window.innerHeight;
        panel.style.maxWidth = Math.max(0, width - 16) + 'px';
        panel.style.maxHeight = Math.max(0, height - 16) + 'px';
        var bounds = trigger.getBoundingClientRect();
        panel.style.left = Math.max(left + 8, Math.min(alignRight ? bounds.right - panel.offsetWidth : bounds.left, left + width - panel.offsetWidth - 8)) + 'px';
        var y = bounds.bottom + 6;
        if (y + panel.offsetHeight > top + height - 8) y = bounds.top - panel.offsetHeight - 6;
        panel.style.top = Math.max(top + 8, Math.min(y, top + height - panel.offsetHeight - 8)) + 'px';
    }
    document.addEventListener('click', function (event) {
        if (fallbackPopover && !fallbackPopover.contains(event.target) && !fallbackPopover.cgTrigger.contains(event.target)) closePopover(fallbackPopover);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && fallbackPopover) { var trigger = fallbackPopover.cgTrigger; closePopover(fallbackPopover); trigger.focus(); }
    });
    function closeFallbackPopover() { if (fallbackPopover) closePopover(fallbackPopover); }
    window.addEventListener('resize', closeFallbackPopover);
    document.addEventListener('scroll', function (event) {
        if (fallbackPopover && !fallbackPopover.contains(event.target)) closeFallbackPopover();
    }, true);
    function node(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }
    function outsideDialog(dialog, event) {
        var bounds = dialog.getBoundingClientRect();
        return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
    }
    var backdropDialog = null;
    document.addEventListener('pointerdown', function (event) {
        var target = event.target;
        backdropDialog = target instanceof HTMLDialogElement && target.open && target.closest('.cg-chat') && outsideDialog(target, event) ? target : null;
    });
    document.addEventListener('pointercancel', function () { backdropDialog = null; });
    document.addEventListener('click', function (event) {
        var dialog = backdropDialog; backdropDialog = null;
        if (!dialog || event.target !== dialog || !dialog.open || !outsideDialog(dialog, event)) return;
        // A modal backdrop targets its own dialog, so a click dismisses only that layer.
        event.preventDefault(); event.stopPropagation();
        if (dialog.dispatchEvent(new Event('cancel', {cancelable:true}))) dialog.close();
    });
    var bubbleColors = ['#ff99bd', '#6ee7a8', '#80c7ff', '#c4a0ff', '#ffb568', '#5cdddd', '#f4df59', '#ff9b86'];
    function bubbleColor(id) {
        // Mix the message ID so its random-looking color stays stable on reload.
        var hash = Number(id) | 0;
        hash = Math.imul(hash ^ (hash >>> 16), 0x45d9f3b);
        hash = Math.imul(hash ^ (hash >>> 16), 0x45d9f3b);
        return bubbleColors[((hash ^ (hash >>> 16)) >>> 0) % bubbleColors.length];
    }
    function key() { var a = new Uint8Array(16); crypto.getRandomValues(a); return Array.from(a, function (x) { return x.toString(16).padStart(2, '0'); }).join(''); }
    var page = document.querySelector('.cg-page');
    if (page) {
        function fitPage() {
            var viewport = window.visualViewport;
            var viewportTop = viewport ? viewport.offsetTop : 0;
            var viewportBottom = viewportTop + (viewport ? viewport.height : window.innerHeight);
            var content = page.closest('.site-page-content');
            var top = Math.max(viewportTop, content ? content.getBoundingClientRect().top : 0);
            var nav = document.querySelector('.site-mobile-nav');
            var navHeight = nav && nav.getClientRects().length ? nav.offsetHeight : 0;
            page.style.setProperty('--cg-page-top', top + 'px');
            page.style.setProperty('--cg-page-height', Math.max(0, viewportBottom - top - navHeight) + 'px');
        }
        fitPage();
        window.addEventListener('resize', fitPage);
        window.addEventListener('load', fitPage);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', fitPage);
            window.visualViewport.addEventListener('scroll', fitPage);
        }
        if (window.ResizeObserver) {
            var layoutObserver = new ResizeObserver(fitPage);
            document.querySelectorAll('.site-header,.topbar,.nudge-bar').forEach(function (element) { layoutObserver.observe(element); });
        }
    }
    function Chat(root) {
        var self = this; this.root = root; this.api = root.dataset.api; this.csrf = root.dataset.csrf;
        this.feed = root.querySelector('[data-cg-feed]'); this.status = root.querySelector('[data-cg-status]');
        this.messages = new Map(); this.last = 0; this.first = 0; this.reply = null; this.pending = null; this.busy = false; this.polling = false; this.destroyed = false;
        this.group = null; this.identity = null; this.hasOlder = true;
        this.form = root.querySelector('[data-cg-form]'); this.loading = root.querySelector('[data-cg-loading]');
        this.scrollTop = this.feed.scrollTop;
        this.jump = root.querySelector('[data-cg-jump]');
        this.jump.onclick = function () { self.feed.scrollTop = self.feed.scrollHeight; self.jump.hidden = true; self.read(); };
        this.feed.addEventListener('scroll', function () {
            var top = self.feed.scrollTop, upward = top < self.scrollTop;
            self.scrollTop = top;
            if (self.bottom()) { self.jump.hidden = true; self.read(); }
            if (upward && top < 80 && self.first && self.hasOlder) {
                if (self.polling) self.pendingOlder = true;
                else self.load(true);
            }
        });
        if (this.form) {
            this.form.onsubmit = function (e) { e.preventDefault(); self.send(); };
            this.form.content.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); self.send(); } });
            this.resizeComposer = function () {
                var input = self.form.content, atBottom = self.bottom();
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight + 2, 122) + 'px';
                input.style.overflowY = input.scrollHeight > 120 ? 'auto' : 'hidden';
                if (atBottom) self.feed.scrollTop = self.feed.scrollHeight;
            };
            this.form.content.addEventListener('input', this.resizeComposer);
            this.setupEmojiPicker();
            root.querySelector('[data-cg-cancel-reply]').onclick = function () { self.reply = null; root.querySelector('[data-cg-reply]').hidden = true; };
        }
        var nickname = root.querySelector('[data-cg-nickname]');
        this.nicknameDialog = root.querySelector('[data-cg-nickname-dialog]');
        if (nickname) {
            var editName = root.querySelector('[data-cg-edit-name]');
            this.editNickname = function () {
                if (self.nicknameDialog.open) return;
                nickname.nickname.value = self.identity ? self.identity.name : '';
                root.querySelector('[data-cg-nickname-error]').textContent = '';
                self.nicknameDialog.showModal(); nickname.nickname.focus(); nickname.nickname.select();
            };
            editName.onclick = this.editNickname;
            self.nicknameDialog.addEventListener('cancel', function (event) { event.preventDefault(); });
            nickname.onsubmit = function (e) {
                e.preventDefault();
                var submit = nickname.querySelector('button[type=submit]');
                if (submit.disabled) return;
                submit.disabled = true;
                root.querySelector('[data-cg-nickname-error]').textContent = '';
                self.request('nickname', {nickname: nickname.nickname.value}).then(function () { return self.open(); }).then(function () {
                    self.nicknameDialog.close(); self.load();
                }).catch(function (e) { root.querySelector('[data-cg-nickname-error]').textContent = e.message; })
                    .finally(function () { submit.disabled = false; });
            };
        }
        var file = root.querySelector('[data-cg-file]'), attach = root.querySelector('[data-cg-attach]');
        this.uploadAuth = root.querySelector('[data-cg-upload-auth]');
        if (this.uploadAuth) root.querySelector('[data-cg-upload-auth-close]').onclick = function () { self.uploadAuth.close(); };
        if (attach) attach.onclick = function () {
            if (self.uploadAuth) { if (!self.uploadAuth.open) self.uploadAuth.showModal(); return; }
            if (self.busy || !file) return;
            if (!self.identity) { self.status.textContent = 'Đang kết nối, vui lòng thử lại.'; return; }
            if (self.identity.needs_nickname) { self.editNickname(); return; }
            file.click();
        };
        this.attachmentPreview = node('div', 'cg-attachment-preview'); this.attachmentPreview.hidden = true;
        if (this.form) {
            var composerControls = node('div', 'cg-composer-controls');
            while (this.form.firstChild) composerControls.append(this.form.firstChild);
            this.form.append(this.attachmentPreview, composerControls);
        }
        if (file) file.onchange = function () {
            if (!file.files[0] || self.busy) return;
            var selected = file.files[0];
            if (!/^image\/(jpeg|png|webp)$/.test(selected.type) || selected.size > 5242880) { self.status.textContent = 'Chọn ảnh JPEG, PNG hoặc WebP tối đa 5 MB.'; file.value = ''; return; }
            self.clearAttachment(); self.selectedFile = selected; self.attachmentUrl = URL.createObjectURL(selected);
            var previewImage = node('img'); previewImage.src = self.attachmentUrl; previewImage.alt = 'Ảnh đính kèm';
            var remove = node('button', 'cg-attachment-remove', '×'); remove.type = 'button'; remove.setAttribute('aria-label', 'Bỏ ảnh đính kèm');
            remove.onclick = function () { if (!self.busy) { self.clearAttachment(); self.form.content.focus(); } };
            self.attachmentPreview.append(previewImage, remove); self.attachmentPreview.hidden = false;
            self.form.content.required = false; self.form.content.focus(); self.status.textContent = '';
        };
        var groupMenuToggle = root.querySelector('[data-cg-group-menu-toggle]');
        this.groupMenu = root.querySelector('[data-cg-group-menu]');
        this.groupMenu.id = 'cg-group-menu-' + key();
        groupMenuToggle.setAttribute('aria-controls', this.groupMenu.id);
        this.groupMenu.addEventListener('toggle', function () { groupMenuToggle.setAttribute('aria-expanded', popoverOpen(self.groupMenu) ? 'true' : 'false'); });
        groupMenuToggle.onclick = function () {
            showPopover(self.groupMenu, groupMenuToggle, true);
        };
        this.groupMenu.addEventListener('click', function (event) { if (event.target.closest('button')) closePopover(self.groupMenu); }, true);
        // The fallback popover moves the menu outside root while it is open.
        this.notificationsButton = this.groupMenu.querySelector('[data-cg-notifications]');
        this.notificationsButton.onclick = function () { self.act('notifications', {enabled: self.group && self.group.notifications ? '0' : '1'}); };
        root.querySelector('[data-cg-share]').onclick = function () {
            var url = self.group ? self.group.url : location.href;
            if (navigator.share) navigator.share({title: self.group && self.group.name, url: url}).catch(function () {});
            else if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { self.status.textContent = 'Đã sao chép liên kết nhóm.'; }).catch(function () { self.status.textContent = url; });
            else self.status.textContent = url;
        };
        this.visibility = function () { if (!document.hidden && self.active()) { self.open().then(function () { self.presence(); self.load(); }); } else self.presence(); };
        document.addEventListener('visibilitychange', this.visibility); window.addEventListener('focus', this.visibility); window.addEventListener('blur', this.visibility);
        this.onlineDialog = root.querySelector('[data-cg-online-dialog]');
        this.rulesDialog = root.querySelector('[data-cg-rules-dialog]');
        root.querySelector('[data-cg-online]').onclick = function () {
            if (!self.onlineDialog.open) self.onlineDialog.showModal();
            self.people();
        };
        root.querySelector('[data-cg-rules-open]').onclick = function () { if (!self.rulesDialog.open) self.rulesDialog.showModal(); };
        root.querySelectorAll('[data-cg-close-info]').forEach(function (button) { button.onclick = function () { button.closest('dialog').close(); }; });
        this.open().then(function () { self.presence(); self.load(); }).catch(function (e) { self.status.textContent = e.message; self.load(); });
        this.timer = setInterval(function () { if (self.active()) { self.load(); if (!self.heart || Date.now() - self.heart > 15000) self.presence(); } }, 4000);
    }
    Chat.prototype.setupEmojiPicker = function () {
        var self = this, input = this.form.content, trigger = this.root.querySelector('[data-cg-emoji]');
        var panel = node('div', 'cg-composer-emojis'); panel.hidden = true;
        panel.id = 'cg-emojis-' + key(); panel.setAttribute('role', 'region'); panel.setAttribute('aria-label', 'Chọn biểu tượng cảm xúc');
        trigger.setAttribute('aria-controls', panel.id); trigger.setAttribute('aria-expanded', 'false');
        var header = node('div', 'cg-composer-emojis-header'), tabs = node('div', 'cg-composer-emoji-tabs');
        var close = node('button', '', '×'); close.type = 'button'; close.setAttribute('aria-label', 'Đóng bảng emoji');
        header.append(tabs, close);
        var grid = node('div', 'cg-composer-emoji-grid'); panel.append(header, grid); this.form.before(panel);
        var start = 0, end = 0;
        function remember() { start = input.selectionStart; end = input.selectionEnd; }
        this.closeEmojiPicker = function () { panel.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
        function populate(group) {
            grid.replaceChildren();
            group.items.split(' ').forEach(function (emoji) {
                var button = node('button', '', emoji); button.type = 'button'; button.setAttribute('aria-label', 'Chèn ' + emoji);
                button.onclick = function () {
                    if (input.disabled || input.readOnly) return;
                    var value = input.value.slice(0, start) + emoji + input.value.slice(end);
                    if (input.maxLength > 0 && value.length > input.maxLength) return;
                    input.value = value; start += emoji.length; end = start;
                    input.setSelectionRange(start, end);
                    input.dispatchEvent(new Event('input', {bubbles:true}));
                };
                grid.append(button);
            });
        }
        [
            {name:'Cảm xúc', items:'😀 😃 😄 😁 😆 😅 😂 🤣 😊 🙂 🙃 😉 😍 🥰 😘 😋 😎 🤩 🥳 😏 😌 🤗 🤔 🫣 🥺 😢 😭 😮 😲 😴 😤 😡'},
            {name:'Yêu thương', items:'❤️ 🩷 🧡 💛 💚 💙 💜 🤍 💕 💞 💓 💖 💘 💝 💌 💋 🌹 🌷 💐 🫶 🤝 👏 👍 🙌 🙏 👋 ✌️ 💪'},
            {name:'Cuộc sống', items:'🔥 ✨ ⭐ 🌟 🎉 🎊 🎁 🎂 ☕ 🍵 🧋 🍰 🍜 🍕 🎵 🎶 📷 🎬 🐶 🐱 🌻 🌈 ☀️ 🌙 🏖️ ✈️ 💯 ✅'}
        ].forEach(function (group, index) {
            var tab = node('button', '', group.name); tab.type = 'button'; tab.setAttribute('aria-pressed', index === 0 ? 'true' : 'false');
            tab.onclick = function () { tabs.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b === tab ? 'true' : 'false'); }); populate(group); };
            tabs.append(tab); if (index === 0) populate(group);
        });
        trigger.onclick = function () {
            if (!panel.hidden) { self.closeEmojiPicker(); return; }
            remember(); input.blur(); panel.hidden = false; trigger.setAttribute('aria-expanded', 'true');
        };
        close.onclick = this.closeEmojiPicker;
        input.addEventListener('focus', this.closeEmojiPicker);
        panel.addEventListener('keydown', function (event) { if (event.key === 'Escape') { self.closeEmojiPicker(); trigger.focus(); } });
        this.emojiOutsideClick = function (event) { if (!panel.contains(event.target) && !trigger.contains(event.target)) self.closeEmojiPicker(); };
        document.addEventListener('click', this.emojiOutsideClick);
        this.emojiPanel = panel;
    };
    Chat.prototype.active = function () { return !this.destroyed && !document.hidden && document.hasFocus() && this.root.isConnected; };
    Chat.prototype.request = function (action, data, query) {
        var options = {credentials: 'same-origin', headers: {}};
        if (data) {
            options.method = 'POST'; options.headers['X-Chat-CSRF'] = this.csrf;
            if (data instanceof FormData) options.body = data;
            else { options.body = new URLSearchParams(); Object.keys(data).forEach(function (k) { options.body.set(k, data[k]); }); }
        }
        return fetch(this.api + '/' + action + (query ? '?' + new URLSearchParams(query) : ''), options).then(function (r) {
            return r.json().catch(function () { throw new Error('Chưa tải được dữ liệu. Hãy tải lại trang và thử lại.'); }).then(function (v) { if (!r.ok || !v.ok) { var e = new Error(v.message || 'Chưa thực hiện được.'); e.code = r.status; throw e; } return v; });
        });
    };
    Chat.prototype.open = function () {
        var self = this;
        return this.request('open', {}).then(function (r) {
            self.identity = r.identity;
            var nickname = self.root.querySelector('[data-cg-nickname]');
            if (nickname && !nickname.nickname.value) nickname.nickname.value = r.identity.name;
            if (nickname && r.identity.needs_nickname && !self.nicknamePrompted) { self.nicknamePrompted = true; self.editNickname(); }
            if (self.form) { self.form.content.disabled = !!r.identity.needs_nickname; self.form.querySelector('button[type=submit]').disabled = !!r.identity.needs_nickname; }
            self.connect(r.ws_url, r.ticket);
            if (!self.root.dataset.canSend || self.root.dataset.canSend === '0') return;
            var identityRow = self.root.querySelector('[data-cg-identity]');
            identityRow.hidden = false;
            var identityText = identityRow.querySelector('[data-cg-identity-name]');
            identityText.replaceChildren(document.createTextNode('Bạn đang chat với tên '), node('strong', 'cg-current-nickname', r.identity.name), document.createTextNode('.'));
        });
    };
    Chat.prototype.connect = function (url, ticket) {
        var self = this; if (!url || !ticket || this.destroyed) return;
        if (this.socket) { this.socket.onclose = null; this.socket.close(); }
        try { this.socket = new WebSocket(url.replace(/\/ws\/?$/, '/ws/groups') + '?ticket=' + encodeURIComponent(ticket)); } catch (e) { return; }
        this.socket.onmessage = function (e) { try { if (JSON.parse(e.data).t === 'group.changed') self.load(); } catch (_) {} };
        this.socket.onclose = function () { if (!self.destroyed) self.reconnect = setTimeout(function () { if (self.active()) self.open().catch(function () {}); }, 10000); };
    };
    Chat.prototype.bottom = function () { return this.feed.scrollHeight - this.feed.scrollTop - this.feed.clientHeight < 65; };
    Chat.prototype.read = function () {
        if (!this.active() || !this.bottom() || !this.last || this.readAt === this.last || !this.identity) return;
        var self = this, id = this.last;
        this.request('read', {message_id: id}).then(function () { self.readAt = id; refreshSaved(); }).catch(function () {});
    };
    Chat.prototype.presence = function () {
        if (!this.identity || this.destroyed) return; this.heart = Date.now();
        this.request('presence', {active: this.active() ? '1' : '0'}).catch(function () {});
    };
    Chat.prototype.people = function () {
        var self = this;
        this.request('online').then(function (r) {
            var target = self.root.querySelector('[data-cg-people]'); target.replaceChildren();
            r.people.forEach(function (p) { var item = node(p.url ? 'a' : 'span', 'cg-person'); if (p.url) item.href = p.url; var img = node('img'); img.src = p.avatar; img.alt = ''; item.append(img, node('span', '', p.name)); target.append(item); });
            if (!r.people.length) target.textContent = 'Chưa có ai đang trò chuyện.';
        }).catch(function () {});
    };
    Chat.prototype.load = function (older) {
        if (this.polling || this.destroyed || (older && (!this.hasOlder || !this.first))) return Promise.resolve();
        this.polling = true; var self = this, atBottom = this.bottom(), height = this.feed.scrollHeight, top = this.feed.scrollTop;
        this.loading.hidden = !(older || !this.last);
        this.feed.setAttribute('aria-busy', 'true');
        var query = older ? {before_id: this.first} : {after_id: this.last};
        query.known_ids = Array.from(this.messages.keys()).slice(-200).join(',');
        query.events_cursor = this.eventsCursor || 0;
        return this.request('messages', null, query).then(function (r) {
            if (self.destroyed) return;
            if (self.group && self.group.mode !== r.group.mode) { window.location.reload(); return; }
            self.group = r.group;
            var groupAvatar = self.root.querySelector('[data-cg-group-avatar]');
            if (groupAvatar && groupAvatar.getAttribute('src') !== r.group.avatar) groupAvatar.src = r.group.avatar;
            self.eventsCursor = r.events_cursor;
            (r.removed || []).forEach(function (id) { var old = self.feed.querySelector('[data-message="' + id + '"]'); if (old) old.remove(); self.messages.delete(Number(id)); });
            (r.sender_updates || []).forEach(function (update) {
                var message = self.messages.get(Number(update.message_id));
                if (!message || JSON.stringify(message.sender) === JSON.stringify(update.sender)) return;
                message.sender = update.sender;
                var row = self.feed.querySelector('[data-message="' + message.id + '"]');
                if (row) row.replaceWith(self.render(message));
            });
            (r.senders || []).forEach(function (sender) { self.messages.forEach(function (m) {
                if (m.sender.id !== sender.id) return;
                var profileAccessChanged = m.sender.can_open_profile !== sender.can_open_profile;
                m.sender = sender;
                var row = self.feed.querySelector('[data-message="' + m.id + '"]');
                if (row && profileAccessChanged) { row.replaceWith(self.render(m)); return; }
                if (row) { row.querySelector('.cg-meta > :first-child').textContent = sender.name; row.querySelector('.cg-avatar').src = sender.avatar; row.classList.toggle('is-vip', !!sender.is_vip); }
            }); });
            var changed = false;
            r.messages.forEach(function (m) { if (self.messages.has(m.id)) return; self.messages.set(m.id, m); changed = true; var row = self.render(m); if (older) self.feed.insertBefore(row, self.feed.querySelector('[data-message="' + self.first + '"]')); else self.feed.append(row); });
            (r.reactions || []).forEach(function (reaction) { var m = self.messages.get(reaction.id); if (m) { m.reactions = reaction.items; var row = self.feed.querySelector('[data-message="' + m.id + '"]'); if (row) self.renderReactions(row.querySelector('[data-cg-reactions]'), m); } });
            // Keep DOM ordered when HTTP acknowledgement races with a realtime catch-up.
            if (changed) Array.from(self.feed.children).sort(function (a, b) { return Number(a.dataset.message) - Number(b.dataset.message); }).forEach(function (n) { self.feed.append(n); });
            var ids = Array.from(self.messages.keys()); self.last = ids.length ? Math.max.apply(null, ids) : 0; self.first = ids.length ? Math.min.apply(null, ids) : 0;
            self.refreshQuotes();
            if (older || !query.after_id) self.hasOlder = r.messages.length === 30;
            if (older) { self.feed.scrollTop = top + self.feed.scrollHeight - height; }
            else if (atBottom) self.feed.scrollTop = self.feed.scrollHeight;
            else if (changed) self.jump.hidden = false;
            self.root.querySelector('[data-cg-online]').textContent = r.group.online + ' người đang online';
            self.notificationsButton.textContent = r.group.notifications ? 'Tắt thông báo' : 'Bật thông báo';
            var pin = self.root.querySelector('[data-cg-pin]'); pin.hidden = !r.group.pinned; pin.textContent = r.group.pinned ? '📌 ' + (r.group.pinned.type === 'image' ? 'Hình ảnh' : r.group.pinned.content) : '';
            if (self.form) { self.form.querySelector('button[type=submit]').disabled = self.busy || !!(self.identity && self.identity.needs_nickname) || r.group.status !== 'active'; self.form.content.disabled = !!(self.identity && self.identity.needs_nickname) || r.group.status !== 'active'; }
            self.read();
            if (self.onlineDialog.open) self.people();
        }).catch(function (e) {
            self.status.textContent = e.message;
            if (e.code === 401 || e.code === 403 || e.code === 404) { self.feed.replaceChildren(); self.messages.clear(); var pin = self.root.querySelector('[data-cg-pin]'); pin.hidden = true; pin.textContent = ''; self.root.querySelector('[data-cg-people]').replaceChildren(); if (self.socket) self.socket.close(); }
        }).finally(function () {
            self.polling = false;
            self.loading.hidden = true;
            self.feed.setAttribute('aria-busy', 'false');
            self.scrollTop = self.feed.scrollTop;
            if (self.pendingOlder) {
                self.pendingOlder = false;
                if (self.feed.scrollTop < 80) self.load(true);
            }
        });
    };
    Chat.prototype.showAnonymous = function (sender) {
        if (!sender || sender.can_open_profile !== true || !sender.id) return;
        var id = sender.id;
        var self = this;
        this.request('profile', null, {person: id}).then(function (r) {
            if (self.destroyed) return;
            var dialog = node('dialog', 'cg-anonymous-profile'), p = r.profile;
            var profileAvatar = node('img', 'cg-profile-avatar'); profileAvatar.src = p.avatar; profileAvatar.alt = '';
            var profileHeading = node('h3', '', p.name); profileHeading.id = 'cg-profile-heading-' + self.root.dataset.groupId;
            dialog.setAttribute('aria-labelledby', profileHeading.id);
            var profileStats = node('div', 'cg-profile-stats');
            profileStats.append(node('p', '', 'Tham gia nhóm: ' + p.joined_at), node('p', '', '🔥 Chuỗi online: ' + p.streak + ' ngày'));
            dialog.append(profileAvatar, node('span', 'cg-profile-label', 'THÀNH VIÊN NHÓM'), profileHeading, profileStats);
            var badges = node('div', 'achievement-badges');
            (p.badges || []).forEach(function (b) { var badge = node('span', 'achievement-badge', b.icon + ' ' + b.name); badge.title = b.description; badges.append(badge); });
            dialog.append(badges);
            var close = node('button', 'cg-profile-close', 'Đóng'); close.type = 'button'; close.onclick = function () { dialog.close(); };
            dialog.append(close); dialog.addEventListener('close', function () { dialog.remove(); });
            self.root.append(dialog); dialog.showModal(); close.focus();
        }).catch(function (e) { self.status.textContent = e.message; });
    };
    Chat.prototype.refreshQuotes = function () {
        var self = this;
        this.feed.querySelectorAll('[data-quote]').forEach(function (q) { var parent = self.messages.get(Number(q.dataset.quote)); q.textContent = parent ? (parent.type === 'image' ? 'Trả lời hình ảnh' : parent.content.slice(0, 120)) : 'Tin được trả lời không còn hiển thị'; });
        if (this.reply && !this.messages.has(this.reply.id)) { this.reply = null; this.root.querySelector('[data-cg-reply]').hidden = true; }
    };
    Chat.prototype.render = function (m) {
        var self = this, row = node('article', 'cg-message' + (m.mine ? ' is-mine' : '') + (m.sender.is_vip ? ' is-vip' : '')); row.dataset.message = m.id;
        var avatar = node('img', 'cg-avatar'); avatar.src = m.sender.avatar; avatar.alt = '';
        if (m.sender.guest && m.sender.can_open_profile === true && m.sender.id) {
            var profileButton = node('button', 'cg-anonymous-avatar'); profileButton.type = 'button'; profileButton.setAttribute('aria-label', 'Xem thông tin ẩn danh của ' + m.sender.name);
            profileButton.append(avatar);
            profileButton.onclick = function () { self.showAnonymous(m.sender); };
            avatar = profileButton;
        } else if (!m.sender.guest && m.sender.url) {
            var profileLink = node('a', 'cg-avatar-link');
            profileLink.href = m.sender.url;
            profileLink.setAttribute('aria-label', 'Xem hồ sơ của ' + m.sender.name);
            profileLink.append(avatar);
            avatar = profileLink;
        }
        var avatarFrame = node('span', 'cg-avatar-frame'), vipBadge = node('span', 'cg-avatar-vip', '♛ VIP');
        vipBadge.setAttribute('aria-label', 'Thành viên VIP');
        avatarFrame.append(avatar, vipBadge); avatar = avatarFrame;
        var bubble = node('div', 'cg-bubble'), meta = node('div', 'cg-meta'), name = node(m.sender.url ? 'a' : 'strong', '', m.sender.name);
        bubble.style.setProperty('--cg-bubble-bg', bubbleColor(m.id));
        if (m.sender.url) name.href = m.sender.url;
        meta.append(name, node('time', '', m.created_at.slice(11, 16)));
        var body = node('div', 'cg-message-body'); body.append(meta, bubble);
        if (m.reply_to_id) { var quote = node('span', 'cg-quote'); quote.dataset.quote = m.reply_to_id; bubble.append(quote); }
        if (m.type === 'image') {
            var img = node('img', 'cg-image'); img.src = m.content; img.alt = 'Ảnh trong nhóm'; img.loading = 'lazy';
            var imageButton = node('button', 'cg-image-open'); imageButton.type = 'button'; imageButton.setAttribute('aria-label', 'Xem ảnh lớn'); imageButton.append(img);
            imageButton.onclick = function () {
                var viewer = node('dialog', 'cg-image-viewer'); viewer.setAttribute('aria-label', 'Xem ảnh lớn');
                var fullImage = node('img'); fullImage.src = m.content; fullImage.alt = m.caption || 'Ảnh trong nhóm';
                var closeImage = node('button', 'cg-image-viewer-close', '×'); closeImage.type = 'button'; closeImage.setAttribute('aria-label', 'Đóng ảnh');
                closeImage.onclick = function () { viewer.close(); };
                viewer.append(fullImage, closeImage);
                if (m.caption) viewer.append(node('p', 'cg-image-caption', m.caption));
                viewer.addEventListener('close', function () { viewer.remove(); if (imageButton.isConnected) imageButton.focus(); });
                self.root.append(viewer); viewer.showModal(); closeImage.focus();
            };
            bubble.append(imageButton);
        }
        else bubble.append(node('p', 'cg-text', m.content));
        if (m.type === 'image' && m.caption) bubble.append(node('p', 'cg-text', m.caption));
        var actions = node('div', 'cg-message-actions');
        var menu = node('div', 'cg-message-menu'); menu.setAttribute('popover', 'auto'); menu.setAttribute('aria-label', 'Thao tác tin nhắn');
        var picker = node('div', 'cg-menu-reactions'); picker.setAttribute('role', 'group'); picker.setAttribute('aria-label', 'Chọn cảm xúc');
        function show(popover, trigger) {
            showPopover(popover, trigger);
        }
        function iconButton(label, path, fn) {
            var b = node('button', 'cg-action-icon'); b.type = 'button'; b.title = label; b.setAttribute('aria-label', label);
            b.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>';
            b.onclick = function () { fn(b); }; actions.append(b); return b;
        }
        var more = iconButton('Thêm thao tác', '<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>', function (b) { show(menu, b); });
        more.setAttribute('aria-haspopup', 'true');
        more.setAttribute('aria-expanded', 'false');
        if (this.form) {
            ['❤️','👍','😂','😮','😢','😡'].forEach(function (emoji) { var b = node('button', '', emoji); b.type = 'button'; b.setAttribute('aria-label', 'Thả ' + emoji); b.onclick = function () { closePopover(menu); self.act('react', {message_id:m.id, emoji:emoji}); }; picker.append(b); });
            menu.append(picker);
            button('Trả lời', function () { self.reply = m; var r = self.root.querySelector('[data-cg-reply]'); r.hidden = false; r.querySelector('span').textContent = 'Trả lời ' + m.sender.name; self.form.content.focus(); });
        }
        function button(label, fn) {
            var b = node('button', 'cg-menu-item'); b.type = 'button';
            var paths = {
                'Trả lời': '<path d="m9 5-7 7 7 7v-5c6 0 10 1 13 5-1-8-5-11-13-11z"/>',
                'Chặn': '<circle cx="12" cy="12" r="8"/><path d="m6 6 12 12"/>',
                'Báo cáo': '<path d="M5 21V4m0 0c5-4 9 4 14 0v10c-5 4-9-4-14 0"/>',
                'Ghim': '<path d="m9 3 6 0-1 6 4 4v2H6v-2l4-4-1-6ZM12 15v6"/>',
                'Bỏ ghim': '<path d="m9 3 6 0-1 6 4 4v2H9M12 15v6M3 3l18 18"/>',
                'Xóa': '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
                'Cấm gửi 1 giờ': '<path d="M3 3l18 18M7 4h12a2 2 0 0 1 2 2v9M5 5a2 2 0 0 0-2 1v11l4-3h7"/><circle cx="17" cy="17" r="5"/><path d="M17 14v3l2 1"/>',
                'Gỡ cấm gửi': '<path d="M12 4H5a2 2 0 0 0-2 2v13l4-3h12a2 2 0 0 0 2-2v-3M14 5l3 3 5-6M7 9h5M7 12h9"/>',
                'Cấm khỏi nhóm': '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 10-6"/><circle cx="18" cy="17" r="5"/><path d="m14.5 13.5 7 7"/>',
                'Gỡ cấm nhóm': '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a7 7 0 0 1 10-6M15 17l3 3 5-7"/>',
                'Đặt lại nickname': '<path d="M13 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M7 10h4M7 14h2M14 15l-3 1 1-3L20 5l2 2-8 8Z"/>'
            };
            var path = paths[label] || '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>';
            var icon = node('span', 'cg-menu-icon');
            icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>';
            b.append(icon, node('span', '', label));
            if (label === 'Xóa' || label === 'Chặn' || label === 'Cấm khỏi nhóm') b.classList.add('is-danger');
            b.onclick = function () { closePopover(menu); fn(); }; menu.append(b);
        }
        if (!m.mine && m.sender.id) button('Chặn', function () { if (confirm('Ẩn tin nhắn của người này khỏi bạn?')) self.act('block', {target: m.sender.id}); });
        if (!m.mine) button('Báo cáo', function () { var reason = prompt('Lý do báo cáo (spam, quấy rối, lừa đảo, lộ thông tin hoặc khác):'); if (reason) self.act('report', {message_id: m.id, reason: reason}); });
        if (this.group && this.group.moderator) {
            [['Ghim','pin',m.id],['Bỏ ghim','unpin',m.id],['Xóa','delete',m.id]].forEach(function (a) { button(a[0], function () { self.moderate(a[1], a[2]); }); });
            if (m.sender.id) [['Cấm gửi 1 giờ','mute'],['Gỡ cấm gửi','unmute'],['Cấm khỏi nhóm','ban'],['Gỡ cấm nhóm','unban']].forEach(function (a) { button(a[0], function () { self.moderate(a[1], m.sender.id); }); });
            if (m.sender.guest && m.sender.id) button('Đặt lại nickname', function () { self.moderate('reset-name', m.sender.id); });
        }
        if (!menu.children.length) more.hidden = true;
        var reactions = node('div', 'cg-reactions'); reactions.dataset.cgReactions = '';
        this.renderReactions(reactions, m); bubble.append(reactions);
        row.append(avatar, body, actions, menu); return row;
    };
    Chat.prototype.renderReactions = function (target, m) {
        var self = this; target.replaceChildren();
        (m.reactions || []).forEach(function (r) {
            var b = node('button', r.mine ? 'is-mine' : '', r.emoji + (r.count > 1 ? ' ' + r.count : '')); b.type = 'button';
            b.title = (r.mine ? 'Gỡ cảm xúc ' : 'Thả ') + r.emoji;
            b.setAttribute('aria-label', r.emoji + ': ' + r.count + ' lượt' + (r.mine ? ', bấm để gỡ cảm xúc của bạn' : ''));
            b.setAttribute('aria-pressed', r.mine ? 'true' : 'false');
            b.disabled = !self.form;
            b.onclick = function () { self.act('react', {message_id:m.id, emoji:r.emoji}); }; target.append(b);
        });
        target.hidden = !target.children.length;
    };
    Chat.prototype.moderate = function (action, target) { var reason = prompt('Lý do xử lý:'); if (reason) this.act('moderate', {action: action, target: target, reason: reason}); };
    Chat.prototype.flash = function (message) {
        if (!this.toast) {
            this.toast = node('div', 'cg-toast');
            this.toast.setAttribute('role', 'status');
            document.body.append(this.toast);
        }
        clearTimeout(this.toastTimer);
        this.toast.textContent = message;
        this.toast.hidden = false;
        var self = this;
        this.toastTimer = setTimeout(function () { self.toast.hidden = true; }, 2500);
    };
    Chat.prototype.act = function (action, data) { var self = this; return this.request(action, data || {}).then(function () {
        self.status.textContent = action === 'react' || action === 'notifications' ? '' : 'Đã cập nhật.';
        if (action === 'notifications') {
            var enabled = data.enabled === '1';
            if (self.group) self.group.notifications = enabled;
            self.notificationsButton.textContent = enabled ? 'Tắt thông báo' : 'Bật thông báo';
            self.flash(enabled ? 'Đã bật thông báo' : 'Đã tắt thông báo');
            refreshSidebar();
        }
        if (action === 'block') { self.messages.clear(); self.feed.replaceChildren(); self.last = 0; self.first = 0; self.reply = null; }
        self.load(); refreshSaved();
    }).catch(function (e) { self.status.textContent = e.message; }); };
    Chat.prototype.clearAttachment = function () {
        if (this.attachmentUrl) URL.revokeObjectURL(this.attachmentUrl);
        this.attachmentUrl = null; this.selectedFile = null;
        if (this.attachmentPreview) { this.attachmentPreview.replaceChildren(); this.attachmentPreview.hidden = true; }
        var input = this.root.querySelector('[data-cg-file]'); if (input) input.value = '';
        if (this.form) this.form.content.required = true;
    };
    Chat.prototype.send = function () {
        if (this.busy || !this.form || !this.identity) return;
        var file = this.selectedFile;
        var self = this, content = this.form.content.value.trim(), signature = file ? file.name + ':' + file.size + ':' + file.lastModified + ':' + content : content;
        if (!file && !content) return;
        if (!this.pending || this.pending.signature !== signature || this.pending.reply !== (this.reply && this.reply.id)) this.pending = {id: key(), signature: signature, reply: this.reply && this.reply.id};
        var data = {content: content, client_message_id: this.pending.id, reply_to_id: this.pending.reply || ''};
        if (file) { var form = new FormData(); Object.keys(data).forEach(function (k) { form.append(k, data[k]); }); form.append('image', file); data = form; }
        this.busy = true; this.form.querySelector('button[type=submit]').disabled = true; this.form.content.disabled = true;
        this.request(file ? 'image' : 'send', data).then(function () {
            if (file) self.clearAttachment();
        }).then(function () {
            self.form.content.value = ''; self.resizeComposer(); self.clearAttachment();
            self.pending = null; self.reply = null; self.root.querySelector('[data-cg-reply]').hidden = true;
            self.status.textContent = ''; self.feed.scrollTop = self.feed.scrollHeight; self.load(); refreshSaved();
        }).catch(function (e) { self.status.textContent = e.message + ' Nội dung được giữ lại để bạn gửi lại.'; })
            .finally(function () { self.busy = false; self.form.content.disabled = false; self.form.querySelector('button[type=submit]').disabled = false; if (!self.destroyed) self.form.content.focus(); });
    };
    Chat.prototype.destroy = function () {
        if (this.destroyed) return;
        clearTimeout(this.toastTimer);
        if (this.toast) this.toast.remove();
        this.clearAttachment();
        if (fallbackPopover && this.root.contains(fallbackPopover.cgAnchor)) closePopover(fallbackPopover);
        if (this.groupMenu) closePopover(this.groupMenu);
        if (this.emojiOutsideClick) document.removeEventListener('click', this.emojiOutsideClick);
        if (this.form && this.closeEmojiPicker) this.form.content.removeEventListener('focus', this.closeEmojiPicker);
        if (this.emojiPanel) this.emojiPanel.remove();
        if (this.nicknameDialog && this.nicknameDialog.open) this.nicknameDialog.close();
        if (this.uploadAuth && this.uploadAuth.open) this.uploadAuth.close();
        [this.onlineDialog, this.rulesDialog].forEach(function (dialog) { if (dialog && dialog.open) dialog.close(); });
        this.request('presence', {active: '0'}).catch(function () {}); this.destroyed = true;
        clearInterval(this.timer); clearTimeout(this.reconnect);
        document.removeEventListener('visibilitychange', this.visibility); window.removeEventListener('focus', this.visibility); window.removeEventListener('blur', this.visibility);
        if (this.socket) { this.socket.onclose = null; this.socket.close(); }
    };
    function mount(root) { if (root && !root.groupChat) root.groupChat = new Chat(root); return root && root.groupChat; }
    document.querySelectorAll('[data-group-chat]').forEach(mount);
    var inbox = document.querySelector('.tk-ms');
    if (inbox) {
        var privatePane = inbox.querySelector('.tk-ms-pane');
        var inboxGroupPane = node('div', 'tk-ms-pane tk-ms-group-pane');
        inboxGroupPane.hidden = true; inbox.append(inboxGroupPane);
        var inboxRequest = 0, privateWasOpen = inbox.classList.contains('is-open');
        function closeInboxGroup() {
            inboxRequest++;
            var root = inboxGroupPane.querySelector('[data-group-chat]');
            if (root && root.groupChat) root.groupChat.destroy();
            inboxGroupPane.replaceChildren(); inboxGroupPane.hidden = true;
            privatePane.hidden = false;
            inbox.classList.toggle('is-open', privateWasOpen);
            inbox.querySelectorAll('[data-inbox-group]').forEach(function (link) { link.classList.remove('is-active'); });
            inbox.dispatchEvent(new CustomEvent('inbox:change'));
        }
        inbox.querySelectorAll('[data-inbox-group]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault(); closeInboxGroup();
                var request = ++inboxRequest;
                privatePane.hidden = true; inboxGroupPane.hidden = false;
                inbox.classList.add('is-open'); link.classList.add('is-active');
                var back = node('button', 'cg-widget-back', '←');
                back.type = 'button'; back.setAttribute('aria-label', 'Về danh sách tin nhắn');
                back.onclick = closeInboxGroup;
                var content = node('div', 'cg-widget-content', 'Đang tải nhóm…');
                inboxGroupPane.append(back, content);
                inbox.dispatchEvent(new CustomEvent('inbox:change'));
                fetch(link.dataset.panelUrl, {credentials:'same-origin'}).then(function (response) {
                    if (!response.ok) throw new Error('Chưa mở được nhóm. Vui lòng thử lại.');
                    return response.text();
                }).then(function (html) {
                    if (request !== inboxRequest) return;
                    content.innerHTML = html;
                    var pageBack = content.querySelector('.cg-groups-back');
                    if (pageBack) pageBack.replaceWith(back);
                    mount(content.querySelector('[data-group-chat]'));
                }).catch(function (error) { if (request === inboxRequest) content.textContent = error.message; });
            });
        });
    }
    var saved = document.querySelector('[data-cg-saved]'), groupPanel = null;
    var widget = document.getElementById('chat-widget');
    function close() {
        if (!groupPanel) return;
        var root = groupPanel.querySelector('[data-group-chat]');
        if (root && root.groupChat) root.groupChat.destroy();
        groupPanel.remove(); groupPanel = null;
        refreshSaved();
    }
    if (widget) widget.addEventListener('cw:close-group', close);
    function openPanel(id) {
        var main = document.getElementById('cw-main');
        if (!saved || !widget || !main) return;
        close();
        widget.dispatchEvent(new CustomEvent('cw:open-group', {detail: {id: id}}));
        groupPanel = node('div', 'cg-widget-panel');
        var back = node('button', 'cg-widget-back');
        back.type = 'button';
        back.setAttribute('aria-label', 'Về danh sách nhóm'); back.title = 'Về danh sách nhóm';
        back.innerHTML = '<svg class="cg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
        back.onclick = function () { widget.dispatchEvent(new CustomEvent('cw:group-back')); };
        var content = node('div', 'cg-widget-content', 'Đang tải nhóm…');
        groupPanel.append(back, content); main.append(groupPanel);
        var current = groupPanel;
        fetch(saved.dataset.panelBase + '/' + id, {credentials:'same-origin'}).then(function (r) {
            if (!r.ok) throw new Error('Chưa mở được nhóm. Hãy tải lại hoặc thử nhóm khác.');
            return r.text();
        }).then(function (html) {
            if (groupPanel !== current) return;
            content.innerHTML = html;
            var header = content.querySelector('.cg-header-who');
            if (header) {
                var pageBack = header.querySelector('.cg-groups-back');
                if (pageBack) pageBack.replaceWith(back); else header.prepend(back);
            }
            mount(content.querySelector('[data-group-chat]'));
        }).catch(function (e) { if (groupPanel === current) content.textContent = e.message; });
    }
    function refreshSaved() {
        if (!saved || document.hidden) return;
        fetch(saved.dataset.cgSaved, {credentials:'same-origin'}).then(function (r) { return r.json(); }).then(function (r) {
            if (!r.ok) return; saved.replaceChildren();
            var groups = r.groups;
            if (widget) widget.dispatchEvent(new CustomEvent('cw:group-unread', {detail: groups.reduce(function (total, g) { return total + (Number(g.unread) || 0); }, 0)}));
            groups.forEach(function (g) {
                var a = node('a', 'cw-row cg-sticky-row' + (g.unread ? ' is-unread' : ''));
                a.dataset.cwGroup = g.id;
                a.classList.toggle('is-on', !!widget && widget.dataset.activeGroup === String(g.id));
                a.href = g.url;
                var avatar = node('span', 'cw-row-avatar');
                var image = node('img'); image.src = g.avatar; image.alt = ''; avatar.append(image);
                if (g.online) avatar.append(node('i', 'cw-dot'));
                var text = node('span', 'cw-row-text');
                var meta = node('span', 'cg-sticky-meta');
                meta.append(node('span', 'cg-sticky-tag', g.mode === 'guest' ? 'Khách ẩn danh' : 'Nhóm cộng đồng'));
                if (!g.notifications) { var muted = node('span', 'cg-sticky-muted', 'Đã tắt thông báo'); muted.title = 'Đã tắt thông báo'; meta.append(muted); }
                var top = node('span', 'cw-row-top'), name = node('b'); name.append(node('span', 'cw-row-name', g.name)); top.append(name);
                var preview = g.last ? (g.last_sender ? g.last_sender + ': ' : '') + g.last : 'Chưa có tin nhắn. Bắt đầu trò chuyện!';
                text.append(meta, top, node('span', 'cw-row-last', preview));
                a.append(avatar, text);
                if (g.unread) { var badge = node('i', 'cw-row-unread', g.unread > 99 ? '99+' : String(g.unread)); badge.setAttribute('aria-label', g.unread + ' tin chưa đọc'); a.append(badge); }
                a.onclick = function (e) { e.preventDefault(); openPanel(g.id); }; saved.append(a);
            });
            if (!groups.length) saved.append(node('p', 'cg-sticky-empty', 'Chưa có nhóm chat phù hợp.'));

        }).catch(function () {});
    }
    var sidebar = document.querySelector('[data-cg-sidebar]');
    function refreshSidebar() {
        if (!sidebar || document.hidden) return;
        fetch(sidebar.dataset.url, {credentials: 'same-origin'}).then(function (response) { return response.json(); }).then(function (result) {
            if (!result.ok) return;
            sidebar.replaceChildren();
            result.groups.forEach(function (group) {
                var link = node('a'), avatar = node('img', 'cg-sidebar-avatar');
                link.href = group.url;
                if (String(group.id) === sidebar.dataset.currentGroup) {
                    link.classList.add('is-active'); link.setAttribute('aria-current', 'page');
                }
                avatar.src = group.avatar; avatar.alt = ''; avatar.width = 32; avatar.height = 32;
                link.append(avatar, node('span', '', group.name + (group.unread ? ' (' + group.unread + ')' : '')));
                if (!group.notifications) {
                    var muted = node('span', 'cg-sidebar-muted');
                    muted.title = 'Đã tắt thông báo';
                    muted.setAttribute('aria-label', 'Đã tắt thông báo');
                    muted.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 4.2A6 6 0 0 1 18 10v4l2 3M6 6a6 6 0 0 0-1 4v4l-2 3h14M10 21h4"/></svg>';
                    link.append(muted);
                }
                sidebar.append(link);
            });
        }).catch(function () {});
    }
    if (sidebar) {
        refreshSidebar(); setInterval(refreshSidebar, 5000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshSidebar(); });
    }
    if (saved) {
        refreshSaved(); setInterval(refreshSaved, 5000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshSaved(); });
    }
    window.addEventListener('pagehide', function () { document.querySelectorAll('[data-group-chat]').forEach(function (root) { if (root.groupChat) root.groupChat.destroy(); }); });
}());
