(function () {
    'use strict';
    function mount(root) {
    if (!root) return;
    var menu = document.createElement('div'); menu.className = 'conversation-delete-menu';
    menu.setAttribute('popover', 'auto'); menu.setAttribute('aria-label', 'Thao tác cuộc trò chuyện');
    var remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Xóa cuộc trò chuyện';
    var icon = document.createElement('span'); icon.className = 'conversation-delete-icon'; icon.setAttribute('aria-hidden','true');
    icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg>';
    remove.prepend(icon);
    remove.dataset.action = 'delete';
    var notifications = document.createElement('button'); notifications.type = 'button'; notifications.dataset.action = 'notifications';
    var notificationIcon = document.createElement('span'); notificationIcon.className = 'conversation-delete-icon'; notificationIcon.setAttribute('aria-hidden','true');
    notificationIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>';
    var notificationLabel = document.createElement('span'); notificationLabel.textContent = 'Tắt thông báo';
    notifications.append(notificationIcon,notificationLabel);
    var block = document.createElement('button'); block.type = 'button'; block.dataset.action = 'block';
    var blockIcon = document.createElement('span'); blockIcon.className = 'conversation-delete-icon'; blockIcon.setAttribute('aria-hidden','true');
    blockIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="m6 6 12 12"/></svg>';
    var blockLabel = document.createElement('span'); blockLabel.textContent = 'Chặn người này'; block.append(blockIcon,blockLabel);
    menu.append(notifications,block,remove); root.append(menu);
    var profile, addToGroup, commonGroups;
    if (root.id === 'chat-widget') {
        profile = document.createElement('a'); profile.textContent = 'Xem hồ sơ';
        addToGroup = document.createElement('button'); addToGroup.type = 'button'; addToGroup.textContent = 'Thêm vào nhóm';
        commonGroups = document.createElement('button'); commonGroups.type = 'button'; commonGroups.textContent = 'Xem nhóm chung';
        menu.prepend(profile,addToGroup,commonGroups);
        [profile,addToGroup,commonGroups].forEach(function (item) { item.addEventListener('click', function () { close(false); }); });
    }
    var nativePopover = typeof menu.showPopover === 'function', selected = null;
    var generation = 0, states = new Map(), tokens = new Map(), expiryTimers=new Map();
    function label(button) {
        if (profile) {
            profile.href = button.dataset.profileUrl || '#'; profile.hidden = !button.dataset.profileUrl;
            addToGroup.dataset.igAddPeer = button.dataset.peerId || '';
            addToGroup.dataset.igPeerName = button.dataset.peerName || '';
            commonGroups.dataset.igCommon = button.dataset.peerId || '';
            addToGroup.hidden = commonGroups.hidden = !button.dataset.peerId;
        }
        blockLabel.textContent = button.dataset.blockedByMe === '1' ? 'Bỏ chặn người này' : 'Chặn người này';
        block.disabled = !button.dataset.blockUrl || button.disabled;
        var enabled = button.dataset.notificationsMuted !== '1' && button.dataset.notificationsEnabled !== '0';
        notificationLabel.textContent = enabled ? 'Tắt thông báo' : 'Bật thông báo';
        notifications.disabled = !button.dataset.notificationUrl || button.dataset.notificationBusy === '1';
    }
    function applyState(data) {
        var id = String(data.id), enabled = data.notifications_enabled === true;
        var muted=typeof data.muted==='boolean' ? data.muted : !enabled || data.calls_notifications_enabled===false;
        clearTimeout(expiryTimers.get(id));
        root.querySelectorAll('.tk-ms-conversation-more, [data-cw-conversation-more]').forEach(function (button) {
            if (button.dataset.conversationId !== id) return;
            button.dataset.notificationsEnabled = enabled ? '1' : '0';
            button.dataset.notificationsMuted=muted ? '1' : '0';
            var indicator = (button.closest('li, [data-conversation-row]') || button.parentElement).querySelector('.inbox-muted-indicator');
            if (indicator) indicator.hidden = !muted;
            if (selected === button) label(button);
            var deadlines=[data.message_muted_until,data.calls_muted_until].map(Number).filter(function (n) {return n>Number(data.server_time || 0);});
            if(deadlines.length && button.dataset.notificationUrl) {
                var delay=Math.max(1000,(Math.min.apply(null,deadlines)-Number(data.server_time))*1000+250);
                expiryTimers.set(id,setTimeout(function () {
                    if(!button.isConnected)return;
                    request(button.dataset.notificationUrl).then(function (fresh) {
                        applyState(fresh);document.dispatchEvent(new CustomEvent('private-conversation-notifications',{detail:fresh}));
                    }).catch(function () {});
                },Math.min(delay,2147483647)));
            }
        });
    }
    function request(url,options) {
        return fetch(url,Object.assign({credentials:'same-origin',cache:'no-store'},options)).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || !data.ok) throw new Error(data.message || 'Không cập nhật được thông báo.');
                return data;
            });
        });
    }
    function failure(error) {
        if (window.appModal) window.appModal({type:'error',title:'Thông báo inbox',message:error.message});
        else window.alert(error.message);
    }
    function opened() { return nativePopover ? menu.matches(':popover-open') : menu.classList.contains('is-open'); }
    function close(focus) {
        if (nativePopover) { if (opened()) menu.hidePopover(); }
        else menu.classList.remove('is-open');
        if (selected) {
            selected.setAttribute('aria-expanded','false');
            if (focus && selected.isConnected) selected.focus({preventScroll:true});
        }
    }
    menu.addEventListener('toggle', function () { if (selected) selected.setAttribute('aria-expanded', opened() ? 'true' : 'false'); });
    var pointerToggle = null;
    root.addEventListener('pointerdown', function (event) {
        var button = event.target.closest('.tk-ms-conversation-more, [data-cw-conversation-more]');
        pointerToggle = button && selected === button && opened() ? button : null;
    }, true);
    root.addEventListener('pointercancel', function () { pointerToggle = null; });
    root.addEventListener('keydown', function () { pointerToggle = null; });
    root.addEventListener('click', function (event) {
        var button = event.target.closest('.tk-ms-conversation-more, [data-cw-conversation-more]');
        if (!button || button.disabled) return;
        event.preventDefault(); event.stopPropagation();
        var shouldClose = pointerToggle === button || (selected === button && opened());
        pointerToggle = null;
        if (shouldClose) { generation++; close(true); return; }
        close(false); selected = button; label(button);
        var current = ++generation;
        states.delete(button.dataset.conversationId);
        if (button.dataset.notificationUrl) {
            notifications.disabled = true;
            request(button.dataset.notificationUrl).then(function (data) {
                if (current !== generation || selected !== button || !button.isConnected) return;
                states.set(button.dataset.conversationId,data); tokens.set(button.dataset.conversationId,data.action_token);
                applyState(data); notifications.disabled = !data.notifications_available || button.dataset.notificationBusy === '1';
            }).catch(function (error) { if (current === generation && opened()) { notifications.disabled = true; failure(error); } });
        }
        if (nativePopover) menu.showPopover(); else menu.classList.add('is-open');
        selected.setAttribute('aria-expanded','true');
        var bounds = button.getBoundingClientRect(), viewport = window.visualViewport;
        var left = viewport ? viewport.offsetLeft : 0, top = viewport ? viewport.offsetTop : 0;
        var width = viewport ? viewport.width : innerWidth, height = viewport ? viewport.height : innerHeight;
        menu.style.left = Math.max(left+8, Math.min(bounds.left,left+width-menu.offsetWidth-8))+'px';
        var y = bounds.bottom+4;
        if (y+menu.offsetHeight > top+height-8) y = bounds.top-menu.offsetHeight-4;
        menu.style.top = Math.max(top+8,y)+'px';
    });
    function deleted(event) {
        var id = String(event.detail.id), cutoff = Number(event.detail.deleted_through_message_id || 0);
        root.querySelectorAll('.tk-ms-conversation-more, [data-cw-conversation-more]').forEach(function (button) {
            if (button.dataset.conversationId === id) { var row = button.closest('li, [data-conversation-row]'); if (row) row.remove(); }
        });
        if (selected && !selected.isConnected) close(false);
        if (!root.querySelector('.tk-ms-conversation-more, [data-cw-conversation-more]')) {
            var empty = document.getElementById('tk-ms-nores');
            if (empty) { empty.textContent = 'Chưa có cuộc trò chuyện nào.'; empty.hidden = false; }
        }
        var feed = root.querySelector('#chat-body'), pane = root.querySelector('.tk-ms-pane');
        if (feed && root.dataset.currentConversation === id) {
            if (window.MessageRecall) window.MessageRecall.clearBefore(feed,cutoff);
            if (!pane.hidden) window.location.assign(root.dataset.inboxUrl);
        }
    }
    document.addEventListener('private-conversation-deleted', deleted);
    document.addEventListener('private-conversation-notifications',function (event) {
        applyState(event.detail);
        states.delete(String(event.detail.id));
        if (selected && selected.dataset.conversationId === String(event.detail.id)) {
            generation++; close(false);
        }
    });
    notifications.onclick = async function () {
        var button = selected;
        if (!button || !button.isConnected || notifications.disabled || button.dataset.notificationBusy === '1') return;
        var id = button.dataset.conversationId, state = states.get(id);
        if (!state || !state.notifications_available) return;
        var muted=typeof state.muted==='boolean' ? state.muted : !state.notifications_enabled || state.calls_notifications_enabled===false;
        var choice={scope:'both',duration:'0'};
        close(true);
        if(!muted) {
            if(!window.InboxMuteDialog)return;
            choice=await window.InboxMuteDialog.choose(button);
            if(!choice || !button.isConnected)return;
        }
        button.dataset.notificationBusy = '1'; notifications.disabled = true;
        var body = new FormData(); body.set('action_token',tokens.get(id));
        body.set('notifications_enabled',muted ? '1' : '0');
        body.set('mute_scope',choice.scope);body.set('mute_duration',choice.duration);
        request(button.dataset.notificationUrl,{method:'POST',body:body}).then(function (data) {
            states.set(id,data); applyState(data); if (selected === button) close(true);
            document.dispatchEvent(new CustomEvent('private-conversation-notifications',{detail:data}));
        }).catch(failure).finally(function () {
            button.dataset.notificationBusy = '0'; if (selected === button) label(button);
        });
    };
    function refreshMuted() {
        if(document.hidden)return;
        root.querySelectorAll('.tk-ms-conversation-more[data-notifications-muted="1"], [data-cw-conversation-more][data-notifications-muted="1"]').forEach(function (button) {
            if(!button.dataset.notificationUrl || button.dataset.notificationBusy==='1')return;
            var version=generation;
            request(button.dataset.notificationUrl).then(function (state) {
                if(version===generation && button.isConnected && button.dataset.notificationBusy!=='1')applyState(state);
            }).catch(function () {});
        });
    }
    document.addEventListener('visibilitychange',refreshMuted);
    refreshMuted();
    block.onclick = function () {
        var button = selected;
        if (!button || !button.isConnected || button.disabled || !button.dataset.blockUrl) return;
        var unblocking = button.dataset.blockedByMe === '1';
        close(true);
        function perform() {
            if (!button.isConnected || button.disabled) return;
            button.disabled = true;
            var form = document.createElement('form'); form.method = 'post';
            form.setAttribute('action', button.dataset.blockUrl); form.hidden = true;
            [['action_token',root.dataset.conversationToken],['action',unblocking ? 'unblock' : 'block']].forEach(function (field) {
                var input = document.createElement('input'); input.type = 'hidden'; input.name = field[0]; input.value = field[1]; form.append(input);
            });
            document.body.append(form);
            HTMLFormElement.prototype.submit.call(form);
        }
        var message = unblocking ? 'Bỏ chặn người này ở phía bạn?' : 'Chặn người này? Hai bạn sẽ không thể nhắn tin và xem hồ sơ của nhau.';
        if (window.appModal) window.appModal({title:unblocking ? 'Bỏ chặn người này?' : 'Chặn người này?',message:message,confirmText:unblocking ? 'Bỏ chặn' : 'Chặn',onConfirm:perform});
        else if (window.confirm(message)) perform();
    };
    remove.onclick = function () {
        var button = selected;
        if (!button || !button.isConnected || button.disabled) return;
        close(true);
        function perform() {
            if (!button.isConnected || button.disabled) return;
            button.disabled = true;
            var body = new FormData(); body.set('action_token',root.dataset.conversationToken);
            fetch(button.dataset.deleteUrl,{method:'POST',body:body,credentials:'same-origin',cache:'no-store'})
                .then(function (response) { return response.json().then(function (data) {
                    if (!response.ok || !data.ok) throw new Error(data.message || 'Không xóa được cuộc trò chuyện.');
                    return data;
                }); }).then(function (data) {
                    document.dispatchEvent(new CustomEvent('private-conversation-deleted',{detail:data}));
                }).catch(function (error) {
                    button.disabled = false;
                    if (window.appModal) window.appModal({type:'error',title:'Không xóa được',message:error.message});
                    else window.alert(error.message);
                });
        }
        var message = 'Xóa lịch sử cuộc trò chuyện phía bạn? Người kia vẫn giữ lịch sử. Khi có tin nhắn mới, cuộc trò chuyện sẽ hiện lại.';
        if (window.appModal) window.appModal({title:'Xóa cuộc trò chuyện?',message:message,danger:true,confirmText:'Xóa',onConfirm:perform});
        else if (window.confirm(message)) perform();
    };
    document.addEventListener('click',function (event) { if (!menu.contains(event.target) && selected && !selected.contains(event.target)) close(false); });
    document.addEventListener('keydown',function (event) { if (event.key === 'Escape' && opened()) { event.preventDefault(); close(true); } });
    root.addEventListener('scroll',function () { close(false); },true);
    root.addEventListener('cw:conversation-change',function () { generation++; close(false); selected = null; });
    root.addEventListener('cw:close-group',function () { generation++; close(false); });
    window.addEventListener('resize',function () { close(false); });
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize',function () { close(false); });
        window.visualViewport.addEventListener('scroll',function () { close(false); });
    }
    }
    document.querySelectorAll('.tk-ms[data-conversation-token], #chat-widget[data-conversation-token]').forEach(function (root) { if (root.dataset.conversationToken) mount(root); });
})();
