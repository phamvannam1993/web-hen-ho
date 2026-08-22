/**
 * Lớp kết nối WebSocket thời gian thực cho khung chat.
 *
 * Thiết kế: file này chỉ lo phần truyền tin. Giao diện (chat-widget.js)
 * đăng ký lắng nghe qua Realtime.on(loại, hàm) và gửi đi bằng Realtime.send().
 *
 * Nếu WebSocket không dùng được (chưa cấu hình, mất mạng, máy chủ tắt),
 * cờ Realtime.available = false và widget tự quay về cách hỏi định kỳ (polling)
 * qua các endpoint AJAX của PHP, nên chat vẫn hoạt động.
 */
(function () {
    'use strict';

    var root = document.getElementById('chat-widget');
    if (!root) { return; }

    var wsUrl = root.getAttribute('data-ws-url');
    var token = root.getAttribute('data-ws-token');

    var Realtime = {
        available: false,     // đã kết nối được lần nào chưa
        connected: false,     // hiện đang mở kết nối
        listeners: {},
        socket: null,
        queue: [],            // tin gửi khi đang mất kết nối, nối lại sẽ đẩy đi
        retry: 0,
        maxRetry: 6,

        on: function (type, fn) {
            (this.listeners[type] = this.listeners[type] || []).push(fn);
            return this;
        },

        emit: function (type, data) {
            (this.listeners[type] || []).forEach(function (fn) {
                try { fn(data); } catch (e) { console.error('[realtime]', type, e); }
            });
        },

        send: function (payload) {
            if (this.connected && this.socket && this.socket.readyState === 1) {
                this.socket.send(JSON.stringify(payload));
                return true;
            }
            // Giữ lại tối đa 20 gói để không phình bộ nhớ khi mất mạng lâu
            if (this.queue.length < 20) { this.queue.push(payload); }
            return false;
        },

        connect: function () {
            if (!wsUrl || !token) { return; }

            var self = this;
            var socket;
            try {
                socket = new WebSocket(wsUrl + '?token=' + encodeURIComponent(token));
            } catch (e) {
                return this.scheduleReconnect();
            }
            this.socket = socket;

            socket.onopen = function () {
                self.available = true;
                self.connected = true;
                self.retry = 0;
                self.emit('open');

                // đẩy nốt những gì đã xếp hàng lúc mất kết nối
                var pending = self.queue.splice(0, self.queue.length);
                pending.forEach(function (p) { self.send(p); });
            };

            socket.onmessage = function (ev) {
                var msg;
                try { msg = JSON.parse(ev.data); } catch (e) { return; }
                if (msg && msg.t) {
                    self.emit(msg.t, msg);
                }
            };

            socket.onclose = function () {
                self.connected = false;
                self.emit('close');
                self.scheduleReconnect();
            };

            socket.onerror = function () {
                // onclose sẽ chạy ngay sau đó và lo việc kết nối lại
                self.connected = false;
            };
        },

        /** Nối lại với khoảng chờ tăng dần: 1s, 2s, 4s… tối đa 30s. */
        scheduleReconnect: function () {
            var self = this;
            if (this.retry >= this.maxRetry) {
                this.emit('giveup');
                return;
            }
            var delay = Math.min(1000 * Math.pow(2, this.retry), 30000);
            this.retry++;
            setTimeout(function () { self.connect(); }, delay);
        },

        close: function () {
            this.maxRetry = 0;
            if (this.socket) { this.socket.close(); }
        }
    };

    // Giữ kết nối sống qua proxy hay ngắt phiên nhàn rỗi
    setInterval(function () {
        if (Realtime.connected) { Realtime.send({ t: 'ping' }); }
    }, 25000);

    // Tab hiện lại mà kết nối đã rớt thì thử nối ngay, không chờ hết chu kỳ
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !Realtime.connected && Realtime.retry < Realtime.maxRetry) {
            Realtime.retry = 0;
            Realtime.connect();
        }
    });

    window.Realtime = Realtime;
    Realtime.connect();
})();
