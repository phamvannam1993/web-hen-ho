(function () {
    'use strict';
    var states = new Map(), recalledByFeed = new WeakMap();
    function mount(feed, form, conversation) {
        if (!form || states.has(form)) return;
        var bar = document.createElement('div'); bar.className = 'pm-reply-draft'; bar.hidden = true;
        var label = document.createElement('div'), name = document.createElement('strong'), text = document.createElement('span');
        label.append(name,text);
        var cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = '×';
        cancel.setAttribute('aria-label','Hủy trả lời'); bar.append(label,cancel); form.before(bar);
        var field = document.createElement('input'); field.type = 'hidden'; field.name = 'reply_to_id'; form.append(field);
        states.set(form,{feed:feed,bar:bar,name:name,text:text,field:field,revision:0,
            conversation:conversation || function () { return feed.dataset.conversation; }});
        cancel.onclick = function () { clear(form); };
    }
    function clear(form) {
        var state = states.get(form); if (!state) return;
        state.revision++; state.field.value = ''; state.bar.hidden = true; state.name.textContent = ''; state.text.textContent = '';
    }
    function select(form, message) {
        var state = states.get(form);
        if (!state || !state.conversation() || message.dataset.recalled === '1'
            || Number(message.dataset.messageId) <= Number(state.feed.dataset.deletedThrough || 0)) return;
        state.revision++; state.field.value = message.dataset.messageId; state.bar.hidden = false;
        state.name.textContent = 'Trả lời ' + (message.classList.contains('mine') ? 'bạn' : 'đối phương');
        var content = message.querySelector('p');
        state.text.textContent = message.dataset.messageType === 'image' ? '[Hình ảnh]' : (content ? content.textContent.slice(0,120) : '[Hình ảnh]');
        var input = form.querySelector('[name="content"]'); if (input) input.focus();
    }
    function capture(form) {
        var state = states.get(form);
        return state ? {revision:state.revision,conversation:state.conversation()} : null;
    }
    function sent(form, snapshot) {
        var state = states.get(form);
        if (state && snapshot && state.revision === snapshot.revision && state.conversation() === snapshot.conversation) clear(form);
    }
    function render(message, reply, feed) {
        if (!reply) return;
        var hidden = feed && Number(reply.id) <= Number(feed.dataset.deletedThrough || 0);
        var recalled = feed && recalledByFeed.get(feed);
        if (hidden) reply = Object.assign({},reply,{available:false,content:'Tin nhắn không còn hiển thị'});
        else if (recalled && recalled.has(String(reply.id))) reply = Object.assign({},reply,{recalled:true,content:'Tin nhắn đã được thu hồi'});
        var quote = document.createElement('button'); quote.type = 'button'; quote.className = 'pm-reply-quote';
        quote.dataset.replyToId = String(reply.id); quote.disabled = !reply.available;
        var name = document.createElement('strong'); name.textContent = reply.available ? (reply.mine ? 'Bạn' : 'Đối phương') : 'Tin nhắn';
        var text = document.createElement('span'); text.textContent = reply.content;
        quote.append(name,text);
        var content = message.querySelector('.cw-msg-col') || message;
        content.insertBefore(quote,content.querySelector('p, a:not(.cw-msg-name), small'));
    }
    function invalidate(feed, ids, cutoff) {
        var recalled = new Set((ids || []).map(String)), changed = false;
        var remembered = recalledByFeed.get(feed) || new Set();
        recalled.forEach(function (id) { remembered.add(id); }); recalledByFeed.set(feed,remembered);
        feed.querySelectorAll('[data-reply-to-id]').forEach(function (quote) {
            var id = quote.dataset.replyToId, hidden = Number(id) <= Number(cutoff || 0);
            if (!hidden && !recalled.has(id)) return;
            var text = hidden ? 'Tin nhắn không còn hiển thị' : 'Tin nhắn đã được thu hồi';
            var span = quote.querySelector('span');
            if (span && span.textContent !== text) { span.textContent = text; changed = true; }
            if (hidden) { quote.disabled = true; quote.querySelector('strong').textContent = 'Tin nhắn'; }
        });
        states.forEach(function (state,form) {
            if (state.feed === feed && state.field.value
                && (recalled.has(state.field.value) || Number(state.field.value) <= Number(cutoff || 0))) clear(form);
        });
        return changed;
    }
    function pendingIds(feed) {
        var ids = [];
        states.forEach(function (state) { if (state.feed === feed && state.field.value) ids.push(state.field.value); });
        return ids;
    }
    document.addEventListener('click',function (event) {
        var quote = event.target.closest('.pm-reply-quote');
        if (!quote || quote.disabled) return;
        var message = quote.closest('.chat-msg, .cw-msg'), feed = message && message.parentElement;
        var id = quote.dataset.replyToId;
        if (!feed || !/^[1-9]\d*$/.test(id)) return;
        var target = feed.querySelector('[data-message-id="'+id+'"]');
        if (target) {
            target.scrollIntoView({behavior:'smooth',block:'center'}); target.classList.add('pm-reply-target');
            setTimeout(function () { target.classList.remove('pm-reply-target'); },1600);
        }
    });
    window.MessageReply = {mount:mount,select:select,clear:clear,capture:capture,sent:sent,render:render,invalidate:invalidate,pendingIds:pendingIds};
})();
