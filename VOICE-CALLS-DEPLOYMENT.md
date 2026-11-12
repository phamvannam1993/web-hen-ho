# Gọi thoại và gọi video 1–1

## Điều kiện

- Web HTTPS, microphone (và camera khi gọi video) được cấp quyền, WebSocket WSS hoạt động.
- Node >=18; PHP và Node dùng cùng REALTIME_SECRET.
- REALTIME_APP_URL trỏ đúng ứng dụng PHP để Node gọi endpoint nội bộ.
- TURN hỗ trợ credential tạm thời theo shared secret, ví dụ coturn
  với use-auth-secret và static-auth-secret.
- Không đưa shared secret vào JavaScript, Git hoặc log.

## Biến môi trường PHP

VOICE_ENABLED=1
VOICE_TURN_URL=turn:turn.example.com:3478?transport=udp
VOICE_TURN_SECRET=<shared secret của TURN>

Có thể dùng turns:turn.example.com:5349?transport=tcp nếu TURN hỗ trợ TLS.
URL này là endpoint TURN, không phải đường dẫn HTTPS.
Chỉ bật tính năng sau khi cấu hình TURN và kiểm tra hai mạng khác nhau.
Mở listener và dải relay port phù hợp trong firewall theo cấu hình TURN.
Giới hạn bandwidth/allocation của TURN để kiểm soát tài nguyên.

## Gọi video

- Dùng chung luồng báo hiệu `voice.*`; lời mời có thêm `video: true`, Node chuyển cờ này
  trong `voice.ringing`, `voice.incoming`, `voice.accepted`. SDP có video được tới 40000 ký tự
  (thoại giữ 12000).
- Nút "Gọi video" (`data-voice-call` + `data-video="1"`) cạnh nút gọi thoại; trong cuộc gọi có
  Tắt/Bật camera và Đổi camera (khi máy có từ hai camera, thay track không cần thương lượng lại).
- Video đi qua TURN như âm thanh → tốn băng thông relay hơn nhiều (khoảng 0,5–1,5 Mbit/s mỗi chiều
  ở 640×480); xem lại giới hạn bandwidth/allocation của TURN trước khi bật.
- Ứng dụng di động gọi được với web: `GET /api/conversations/{id}/call-config` cấp TURN tạm bằng token,
  cùng luật quyền gọi (`Voice::peer`) với web.

## Hành vi

- Chỉ gọi từ hội thoại riêng; kiểm quyền cả hai người.
- Mỗi user tối đa một cuộc gọi, cooldown 10 giây giữa các lời mời.
- Tab nhận đầu tiên được giữ cuộc gọi; các tab còn lại đóng lời mời.
- Âm thanh chỉ qua TURN; credential hạn 1 giờ.
- Cuộc gọi tối đa 50 phút hoặc đến khi token realtime hết hạn.
- Hết token: tải lại trang để có token mới.
- Đổ chuông tối đa 35 giây; thiết lập kết nối tối đa 60 giây.
- Quyền gọi được kiểm tra lại khi thiết lập và định kỳ khoảng 30 giây.
- Mất WebSocket hoặc rời trang sẽ kết thúc cuộc gọi.
- Non-trickle ICE: chờ gathering hoàn tất tối đa 15 giây mỗi bên.
- Chưa ghi âm/ghi hình, lưu lịch sử, gọi nhóm hay nhận cuộc gọi khi đóng web/ứng dụng.
- Trình duyệt có thể chặn phát âm thanh tự động; dùng nút Bật âm thanh.
- Một tiến trình Node quản lý cuộc gọi; chưa hỗ trợ nhiều instance độc lập.
- Restart Node sẽ kết thúc các cuộc gọi đang diễn ra.
- Nếu bật CSRF sau này, cần cấu hình riêng endpoint nội bộ dùng shared secret.

## Kiểm tra trước khi bật

1. Hai tài khoản trên hai thiết bị/mạng khác nhau: gọi, nhận, nghe hai chiều.
2. Thử tắt/bật mic, từ chối, hủy trước khi nhận và hết thời gian đổ chuông.
3. Thử chặn liên hệ, tài khoản khóa, chưa xác thực và giới hạn VIP.
4. Mở nhiều tab; chỉ tab nhận đầu tiên giữ microphone/cuộc gọi.
5. Hủy lúc đang xin quyền microphone; sau khi cấp quyền mic phải được dừng.
6. Ngắt mạng, đóng tab, hết token; đối phương phải nhận trạng thái kết thúc.
7. Sai TURN secret/port: báo lỗi và giải phóng microphone.
8. DevTools WebRTC: đường truyền dùng relay, không kết nối trực tiếp.
9. Gọi video hai chiều web ↔ web và web ↔ ứng dụng; tắt/bật camera, đổi camera trên điện thoại.

Kiểm tra tự động phần báo hiệu: `node tests/voice_mute.js` và `node tests/voice_video.js`.

Tài liệu này không tự thay đổi server, firewall hoặc triển khai production.
