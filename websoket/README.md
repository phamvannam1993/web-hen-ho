# Máy chủ chat thời gian thực

Nhóm chat mới dùng `/ws/groups?ticket=...` riêng, hỗ trợ khách và chỉ phát `group.changed`. Node xác thực ticket/quyền qua PHP, lấy outbox mỗi 2 giây và kiểm tra lại subscription mỗi 15 giây. Cấu hình và triển khai: xem `../CHAT-GROUPS-DEPLOYMENT.md`. Luồng `/ws?token=...` cho chat riêng/client cũ vẫn được giữ.

Đẩy tin nhắn tức thời tới web (`assets/site/js/realtime.js`) và ứng dụng di động
(`app-hen-ho`). Không đụng cơ sở dữ liệu: PHP vẫn lưu tin và kiểm tra quyền, máy
chủ này chỉ chuyển sự kiện tới đúng người.

```
PHP ── POST /publish (X-Realtime-Secret) ──► Node ── WebSocket /ws ──► web / app
web ── chat.read qua WebSocket ──► Node ── POST /api/realtime/read ──► PHP
```

Node tắt thì web và app tự quay về hỏi tin mới mỗi 4 giây — chat không hỏng,
chỉ chậm hơn.

## Chạy

```bash
cd websoket
npm install
npm start            # cổng 3001
```

Cấu hình đọc theo thứ tự: biến môi trường → `websoket/.env` → `.env` của web.

| Biến | Ý nghĩa |
|---|---|
| `REALTIME_SECRET` | **Bắt buộc**, phải trùng với `.env` của web |
| `REALTIME_PORT` | Cổng lắng nghe, mặc định 3001 |
| `REALTIME_HOST` | Mặc định `0.0.0.0`; trên server sau nginx nên đặt `127.0.0.1` |
| `REALTIME_APP_URL` | Địa chỉ PHP để báo "đã đọc", mặc định lấy `APP_URL` |

Bên `.env` của web:

```
REALTIME_SECRET=...                       # giống hệt bên trên
REALTIME_WS_URL=wss://saigoncupid.com/ws  # địa chỉ trình duyệt / app kết nối
REALTIME_HTTP_URL=http://127.0.0.1:3001   # PHP gọi thẳng Node, không qua proxy
```

## Sự kiện

| Chiều | `t` | Dữ liệu |
|---|---|---|
| xuống | `chat.message` | `conversationId`, `message` (cùng dạng `/api/conversations/{id}`) |
| xuống | `chat.seen` | `conversationId` — đối phương vừa đọc tin của bạn |
| xuống | `chat.typing` | `conversationId`, `from` — đối phương đang nhập |
| xuống | `room.message` | `message` — tin mới phòng chung |
| xuống | `room.presence` | `online` — số người đang kết nối |
| lên | `ping` | → `pong` |
| lên | `chat.typing` | `conversationId`, `to` (máy chủ tự gắn `from` theo token) |
| lên | `chat.read` | `conversationId` |

Token là `base64url({uid, exp}).base64url(HMAC-SHA256)` do
`application/libraries/Realtime.php` cấp, sống 1 giờ.

## Trên máy chủ thật

Chạy nền bằng pm2 (hoặc systemd):

```bash
npm i -g pm2
cd /var/www/web-hen-ho/websoket && npm install --omit=dev
REALTIME_HOST=127.0.0.1 pm2 start src/server.js --name saigoncupid-realtime
pm2 save && pm2 startup
```

nginx chuyển `/ws` sang Node (giữ kết nối WebSocket):

```nginx
location /ws {
    proxy_pass http://127.0.0.1:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 3600s;
}
```

Không mở `/publish` ra ngoài: chỉ PHP gọi qua `REALTIME_HTTP_URL` nội bộ (đã có
secret chặn, nhưng không lộ ra vẫn hơn).
