(function () {
    'use strict';
    var enabled = document.currentScript && document.currentScript.dataset.callsEnabled === '1';
    var selector = '#chat-body .chat-msg[data-message-type="system"], #cw-body .cw-msg[data-message-type="system"]';
    var phone = '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5l2.9.7a2 2 0 0 1 1.7 2z"/>';
    var video = '<rect x="2" y="5" width="14" height="14" rx="3"/><path d="m16 10 6-4v12l-6-4"/>';
    function node(tag, cls, text) {
        var el = document.createElement(tag); el.className = cls;
        if (text !== undefined) el.textContent = text;
        return el;
    }
    function icon(path) {
        var el = node('span', 'call-message-symbol');
        el.setAttribute('aria-hidden','true');
        el.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + path + '</svg>';
        return el;
    }
    function availability(button) {
        var supported = window.isSecureContext && window.RTCPeerConnection
            && navigator.mediaDevices && navigator.mediaDevices.getUserMedia
            && window.crypto && window.crypto.randomUUID && window.HTMLDialogElement;
        button.disabled = !enabled || !supported || !window.Realtime || !window.Realtime.connected;
        button.title = !enabled ? 'Chức năng gọi hiện chưa bật'
            : !supported ? 'Cần HTTPS hoặc localhost và trình duyệt hỗ trợ gọi'
            : !window.Realtime || !window.Realtime.connected ? 'Chưa kết nối máy chủ gọi'
            : button.dataset.video === '1' ? 'Gọi lại bằng video' : 'Gọi lại bằng thoại';
    }
    function decorate(message) {
        if (message.classList.contains('is-call-message') || message.dataset.recalled === '1') return;
        var original = message.querySelector('p');
        if (!original) return;
        var match = /^Cuộc gọi (thoại|video)(?: · (\d+) phút (\d+) giây| (nhỡ|đã bị từ chối|không thành công · Máy bận|không kết nối được))$/.exec(original.textContent.trim());
        if (!match) return;
        var feed = message.closest('#chat-body, #cw-body');
        var cid = feed && Number(feed.dataset.conversation);
        if (!Number.isSafeInteger(cid) || cid <= 0) return;
        var stick = feed.scrollHeight - feed.scrollTop - feed.clientHeight < 80;
        var isVideo = match[1] === 'video', missed = match[4] === 'nhỡ', completed = match[2] !== undefined;
        var mine = message.classList.contains('mine');
        var label = 'Cuộc gọi ' + match[1];
        var title = missed ? (mine ? label + ' nhỡ' : 'Đã nhỡ cuộc gọi ' + match[1]) : label;
        var detail;
        if (completed) {
            var minutes = Number(match[2]), seconds = Number(match[3]);
            detail = minutes ? minutes + ' phút' + (seconds ? ' ' + seconds + ' giây' : '') : seconds + ' giây';
        } else {
            detail = missed ? (mine ? 'Không có người trả lời' : 'Bạn chưa nhận cuộc gọi')
                : match[4] === 'đã bị từ chối' ? 'Cuộc gọi bị từ chối'
                : match[4] === 'không thành công · Máy bận' ? 'Máy bận' : 'Không kết nối được';
        }
        var card = node('div', 'call-message-card' + (completed ? '' : ' is-unanswered'));
        var header = node('div', 'call-message-heading'), text = node('div', 'call-message-text');
        text.append(node('strong', 'call-message-title', title), node('span', 'call-message-detail', detail));
        var mark = icon(isVideo ? video : phone);
        if (!completed) mark.appendChild(node('span', 'call-message-cross', '×'));
        header.append(mark, text);
        var callback = node('button', 'call-message-callback', 'Gọi lại'); callback.type = 'button';
        callback.dataset.voiceCall = String(cid); callback.dataset.video = isVideo ? '1' : '0';
        callback.setAttribute('aria-label', isVideo ? 'Gọi lại bằng video' : 'Gọi lại bằng thoại');
        availability(callback);
        card.append(header, callback);
        // Keep the immutable stored text for copy/reply; only its presentation changes.
        original.classList.add('call-message-original'); original.hidden = true;
        message.classList.add('is-call-message'); original.after(card);
        if (stick) feed.scrollTop = feed.scrollHeight;
    }
    function scan(root) {
        if (root.nodeType !== 1 && root.nodeType !== 9) return;
        if (root.matches && root.matches(selector)) decorate(root);
        root.querySelectorAll(selector).forEach(decorate);
    }
    scan(document);
    new MutationObserver(function (records) {
        records.forEach(function (record) { record.addedNodes.forEach(scan); });
    }).observe(document.body, {childList:true,subtree:true});
    if (window.Realtime) ['open','close'].forEach(function (event) {
        window.Realtime.on(event, function () { document.querySelectorAll('.call-message-callback').forEach(availability); });
    });
})();
