(function () {
    'use strict';
    function node(tag, cls, text) { var n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; }
    function key() { var a = new Uint8Array(16); crypto.getRandomValues(a); return Array.from(a, function (x) { return x.toString(16).padStart(2, '0'); }).join(''); }
    var page = document.querySelector('.cg-page');
    if (page) {
        function fitPage() {
            page.style.setProperty('--cg-page-top', Math.max(0, page.getBoundingClientRect().top + window.scrollY) + 'px');
        }
        fitPage();
        window.addEventListener('resize', fitPage);
        window.addEventListener('load', fitPage);
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
            root.querySelector('[data-cg-emoji]').onclick = function () { self.form.content.value += ' 😊'; self.form.content.focus(); };
            root.querySelector('[data-cg-cancel-reply]').onclick = function () { self.reply = null; root.querySelector('[data-cg-reply]').hidden = true; };
        }
        var file = root.querySelector('[data-cg-file]'); if (file) file.onchange = function () { if (file.files[0]) self.send(file.files[0]); };
        root.querySelector('[data-cg-follow]').onclick = function () { self.act(self.group && self.group.following ? 'unfollow' : 'follow'); };
        root.querySelector('[data-cg-notifications]').onclick = function () { self.act('notifications', {enabled: self.group && self.group.notifications ? '0' : '1'}); };
        root.querySelector('[data-cg-share]').onclick = function () {
            var url = self.group ? self.group.url : location.href;
            if (navigator.share) navigator.share({title: self.group && self.group.name, url: url}).catch(function () {});
            else if (navigator.clipboard) navigator.clipboard.writeText(url).then(function () { self.status.textContent = 'Đã sao chép liên kết nhóm.'; }).catch(function () { self.status.textContent = url; });
            else self.status.textContent = url;
        };
        this.visibility = function () { if (!document.hidden && self.active()) { self.open().then(function () { self.presence(); self.load(); }); } else self.presence(); };
        document.addEventListener('visibilitychange', this.visibility); window.addEventListener('focus', this.visibility); window.addEventListener('blur', this.visibility);
        root.querySelector('.cg-info').addEventListener('toggle', function (e) { if (e.target.open) self.people(); });
        this.open().then(function () { self.presence(); self.load(); }).catch(function (e) { self.status.textContent = e.message; self.load(); });
        this.timer = setInterval(function () { if (self.active()) { self.load(); if (!self.heart || Date.now() - self.heart > 15000) self.presence(); } }, 4000);
    }
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
            self.connect(r.ws_url, r.ticket);
            if (!self.root.dataset.canSend || self.root.dataset.canSend === '0') return;
            self.status.textContent = 'Bạn đang chat với tên ' + r.identity.name + '.';
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
        this.request('read', {message_id: id}).then(function () { self.readAt = id; }).catch(function () {});
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
            self.group = r.group;
            self.eventsCursor = r.events_cursor;
            (r.removed || []).forEach(function (id) { var old = self.feed.querySelector('[data-message="' + id + '"]'); if (old) old.remove(); self.messages.delete(Number(id)); });
            (r.senders || []).forEach(function (sender) { self.messages.forEach(function (m) {
                if (m.sender.id !== sender.id) return; m.sender = sender;
                var row = self.feed.querySelector('[data-message="' + m.id + '"]');
                if (row) { row.querySelector('.cg-meta > :first-child').textContent = sender.name; row.querySelector('.cg-avatar').src = sender.avatar; }
            }); });
            var changed = false;
            r.messages.forEach(function (m) { if (self.messages.has(m.id)) return; self.messages.set(m.id, m); changed = true; var row = self.render(m); if (older) self.feed.insertBefore(row, self.feed.querySelector('[data-message="' + self.first + '"]')); else self.feed.append(row); });
            // Keep DOM ordered when HTTP acknowledgement races with a realtime catch-up.
            if (changed) Array.from(self.feed.children).sort(function (a, b) { return Number(a.dataset.message) - Number(b.dataset.message); }).forEach(function (n) { self.feed.append(n); });
            var ids = Array.from(self.messages.keys()); self.last = ids.length ? Math.max.apply(null, ids) : 0; self.first = ids.length ? Math.min.apply(null, ids) : 0;
            self.refreshQuotes();
            if (older || !query.after_id) self.hasOlder = r.messages.length === 30;
            if (older) { self.feed.scrollTop = top + self.feed.scrollHeight - height; }
            else if (atBottom) self.feed.scrollTop = self.feed.scrollHeight;
            else if (changed) self.jump.hidden = false;
            self.root.querySelector('[data-cg-online]').textContent = r.group.online + ' đang online';
            self.root.querySelector('[data-cg-follow]').textContent = r.group.following ? 'Bỏ theo dõi' : 'Theo dõi';
            self.root.querySelector('[data-cg-notifications]').textContent = r.group.notifications ? 'Tắt thông báo' : 'Bật thông báo';
            var pin = self.root.querySelector('[data-cg-pin]'); pin.hidden = !r.group.pinned; pin.textContent = r.group.pinned ? '📌 ' + (r.group.pinned.type === 'image' ? 'Hình ảnh' : r.group.pinned.content) : '';
            if (self.form) { self.form.querySelector('button[type=submit]').disabled = self.busy || r.group.status !== 'active'; self.form.content.disabled = r.group.status !== 'active'; }
            self.read();
            if (self.root.querySelector('.cg-info').open) self.people();
        }).catch(function (e) {
            self.status.textContent = e.message;
            if (e.code === 403 || e.code === 404) { self.feed.replaceChildren(); self.messages.clear(); var pin = self.root.querySelector('[data-cg-pin]'); pin.hidden = true; pin.textContent = ''; self.root.querySelector('[data-cg-people]').replaceChildren(); if (self.socket) self.socket.close(); }
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
    Chat.prototype.refreshQuotes = function () {
        var self = this;
        this.feed.querySelectorAll('[data-quote]').forEach(function (q) { var parent = self.messages.get(Number(q.dataset.quote)); q.textContent = parent ? (parent.type === 'image' ? 'Trả lời hình ảnh' : parent.content.slice(0, 120)) : 'Tin được trả lời không còn hiển thị'; });
        if (this.reply && !this.messages.has(this.reply.id)) { this.reply = null; this.root.querySelector('[data-cg-reply]').hidden = true; }
    };
    Chat.prototype.render = function (m) {
        var self = this, row = node('article', 'cg-message' + (m.mine ? ' is-mine' : '')); row.dataset.message = m.id;
        var avatar = node('img', 'cg-avatar'); avatar.src = m.sender.avatar; avatar.alt = '';
        var bubble = node('div', 'cg-bubble'), meta = node('div', 'cg-meta'), name = node(m.sender.url ? 'a' : 'strong', '', m.sender.name);
        if (m.sender.url) name.href = m.sender.url;
        meta.append(name, node('time', '', m.created_at.slice(11, 16))); bubble.append(meta);
        if (m.reply_to_id) { var quote = node('span', 'cg-quote'); quote.dataset.quote = m.reply_to_id; bubble.append(quote); }
        if (m.type === 'image') { var img = node('img', 'cg-image'); img.src = m.content; img.alt = 'Ảnh trong nhóm'; img.loading = 'lazy'; bubble.append(img); }
        else bubble.append(node('p', 'cg-text', m.content));
        var actions = node('div', 'cg-message-actions');
        function button(label, fn) { var b = node('button', '', label); b.type = 'button'; b.onclick = fn; actions.append(b); }
        if (this.form) button('Trả lời', function () { self.reply = m; var r = self.root.querySelector('[data-cg-reply]'); r.hidden = false; r.querySelector('span').textContent = 'Trả lời ' + m.sender.name; self.form.content.focus(); });
        if (!m.mine && m.sender.id) button('Chặn', function () { if (confirm('Ẩn tin nhắn của người này khỏi bạn?')) self.act('block', {target: m.sender.id}); });
        if (!m.mine) button('Báo cáo', function () { var reason = prompt('Lý do báo cáo (spam, quấy rối, lừa đảo, lộ thông tin hoặc khác):'); if (reason) self.act('report', {message_id: m.id, reason: reason}); });
        if (this.group && this.group.moderator) {
            [['Ghim','pin',m.id],['Bỏ ghim','unpin',m.id],['Xóa','delete',m.id]].forEach(function (a) { button(a[0], function () { self.moderate(a[1], a[2]); }); });
            if (m.sender.id) [['Cấm gửi 1 giờ','mute'],['Gỡ cấm gửi','unmute'],['Cấm khỏi nhóm','ban'],['Gỡ cấm nhóm','unban']].forEach(function (a) { button(a[0], function () { self.moderate(a[1], m.sender.id); }); });
            if (m.sender.guest && m.sender.id) button('Đặt lại nickname', function () { self.moderate('reset-name', m.sender.id); });
        }
        bubble.append(actions); row.append(avatar, bubble); return row;
    };
    Chat.prototype.moderate = function (action, target) { var reason = prompt('Lý do xử lý:'); if (reason) this.act('moderate', {action: action, target: target, reason: reason}); };
    Chat.prototype.act = function (action, data) { var self = this; return this.request(action, data || {}).then(function () {
        self.status.textContent = 'Đã cập nhật.';
        if (action === 'block') { self.messages.clear(); self.feed.replaceChildren(); self.last = 0; self.first = 0; self.reply = null; }
        self.load(); refreshSaved();
    }).catch(function (e) { self.status.textContent = e.message; }); };
    Chat.prototype.send = function (file) {
        if (this.busy || !this.form || !this.identity) return;
        var self = this, content = this.form.content.value.trim(), signature = file ? file.name + ':' + file.size + ':' + file.lastModified : content;
        if (!file && !content) return;
        if (!this.pending || this.pending.signature !== signature || this.pending.reply !== (this.reply && this.reply.id)) this.pending = {id: key(), signature: signature, reply: this.reply && this.reply.id};
        var data = {content: content, client_message_id: this.pending.id, reply_to_id: this.pending.reply || ''};
        if (file) { var form = new FormData(); Object.keys(data).forEach(function (k) { form.append(k, data[k]); }); form.append('image', file); data = form; }
        this.busy = true; this.form.querySelector('button[type=submit]').disabled = true;
        this.request(file ? 'image' : 'send', data).then(function () {
            if (!file) self.form.content.value = '';
            var input = self.root.querySelector('[data-cg-file]'); if (input) input.value = '';
            self.pending = null; self.reply = null; self.root.querySelector('[data-cg-reply]').hidden = true;
            self.status.textContent = ''; self.feed.scrollTop = self.feed.scrollHeight; self.load(); refreshSaved();
        }).catch(function (e) { self.status.textContent = e.message + ' Nội dung được giữ lại để bạn gửi lại.'; })
            .finally(function () { self.busy = false; self.form.querySelector('button[type=submit]').disabled = false; });
    };
    Chat.prototype.destroy = function () {
        if (this.destroyed) return;
        this.request('presence', {active: '0'}).catch(function () {}); this.destroyed = true;
        clearInterval(this.timer); clearTimeout(this.reconnect);
        document.removeEventListener('visibilitychange', this.visibility); window.removeEventListener('focus', this.visibility); window.removeEventListener('blur', this.visibility);
        if (this.socket) { this.socket.onclose = null; this.socket.close(); }
    };
    function mount(root) { if (root && !root.groupChat) root.groupChat = new Chat(root); return root && root.groupChat; }
    document.querySelectorAll('[data-group-chat]').forEach(mount);
    document.querySelectorAll('[data-cg-card-follow]').forEach(function (button) {
        button.onclick = function () {
            if (button.disabled) return;
            var following = button.dataset.following === '1';
            var status = button.closest('.cg-group-card').querySelector('[data-cg-card-status]');
            button.disabled = true; status.textContent = '';
            fetch(button.dataset.cgCardFollow + (following ? '/unfollow' : '/follow'), {
                method: 'POST', credentials: 'same-origin',
                headers: {'X-Chat-CSRF': button.dataset.csrf}, body: new URLSearchParams()
            }).then(function (r) {
                return r.json().then(function (v) { if (!r.ok || !v.ok) throw new Error(v.message || 'Chưa theo dõi được nhóm.'); });
            }).then(function () {
                button.dataset.following = following ? '0' : '1';
                button.setAttribute('aria-pressed', following ? 'false' : 'true');
                button.textContent = following ? 'Theo dõi' : 'Đã theo dõi'; status.textContent = ''; refreshSaved();
            }).catch(function (e) { status.textContent = e.message; })
                .finally(function () { button.disabled = false; });
        };
    });
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
        widget.dispatchEvent(new CustomEvent('cw:open-group'));
        groupPanel = node('div', 'cg-widget-panel');
        var back = node('button', 'cg-widget-back', '‹ Về danh sách');
        back.type = 'button';
        back.onclick = function () { widget.dispatchEvent(new CustomEvent('cw:group-back')); };
        var content = node('div', 'cg-widget-content', 'Đang tải nhóm…');
        groupPanel.append(back, content); main.append(groupPanel);
        var current = groupPanel;
        fetch(saved.dataset.panelBase + '/' + id, {credentials:'same-origin'}).then(function (r) {
            if (!r.ok) throw new Error('Chưa mở được nhóm. Hãy tải lại hoặc thử nhóm khác.');
            return r.text();
        }).then(function (html) {
            if (groupPanel !== current) return;
            content.innerHTML = html; mount(content.querySelector('[data-group-chat]'));
        }).catch(function (e) { if (groupPanel === current) content.textContent = e.message; });
    }
    function refreshSaved() {
        if (!saved || document.hidden) return;
        fetch(saved.dataset.cgSaved, {credentials:'same-origin'}).then(function (r) { return r.json(); }).then(function (r) {
            if (!r.ok) return; saved.replaceChildren();
            var groups = r.groups;
            groups.forEach(function (g) {
                var a = node('a', 'cw-row cg-sticky-row' + (g.unread ? ' is-unread' : ''));
                a.href = g.url;
                var avatar = node('span', 'cw-row-avatar');
                var image = node('img'); image.src = g.avatar; image.alt = ''; avatar.append(image);
                if (g.online) avatar.append(node('i', 'cw-dot'));
                var text = node('span', 'cw-row-text');
                var meta = node('span', 'cg-sticky-meta');
                meta.append(node('span', 'cg-sticky-tag', g.mode === 'guest' ? 'Khách ẩn danh' : 'Nhóm cộng đồng'));
                if (!g.notifications) { var muted = node('span', 'cg-sticky-muted', 'Đã tắt thông báo'); muted.title = 'Đã tắt thông báo'; meta.append(muted); }
                var top = node('span', 'cw-row-top'), name = node('b'); name.append(node('span', 'cw-row-name', g.name)); top.append(name);
                text.append(meta, top, node('span', 'cw-row-last', g.last || 'Chưa có tin nhắn. Bắt đầu trò chuyện!'));
                a.append(avatar, text);
                if (g.unread) { var badge = node('i', 'cw-row-unread', g.unread > 99 ? '99+' : String(g.unread)); badge.setAttribute('aria-label', g.unread + ' tin chưa đọc'); a.append(badge); }
                a.onclick = function (e) { e.preventDefault(); openPanel(g.id); }; saved.append(a);
            });
            if (!groups.length) saved.append(node('p', 'cg-sticky-empty', 'Theo dõi nhóm để tìm lại nhanh tại đây.'));
            var sidebar = document.querySelector('[data-cg-sidebar]');
            if (sidebar) { sidebar.replaceChildren(); r.groups.filter(function (g) { return g.id !== 1; }).forEach(function (g) { var a = node('a', '', g.name + (g.unread ? ' (' + g.unread + ')' : '')); a.href = g.url; sidebar.append(a); }); }
        }).catch(function () {});
    }
    if (saved) {
        refreshSaved(); setInterval(refreshSaved, 15000);
    }
    window.addEventListener('pagehide', function () { document.querySelectorAll('[data-group-chat]').forEach(function (root) { if (root.groupChat) root.groupChat.destroy(); }); });
}());
