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

- Nút “Loa / tai nghe” chọn đầu ra cho tiếng cuộc gọi (cả thoại và video), dùng `selectAudioOutput`/`setSinkId` khi trình duyệt hỗ trợ. Nếu không có API, hướng dẫn chọn đầu ra qua hệ thống; web không giả lập bật loa ngoài bằng việc tăng âm lượng.
- Tắt camera sẽ ngừng gửi track video và dừng camera trên máy; bật lại xin mở camera rồi `replaceTrack`, không tạo cuộc gọi mới. Micro tiếp tục hoạt động. Nếu mở lại thất bại, cuộc gọi vẫn giữ âm thanh và hiển thị lỗi cụ thể.

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
- Web mới gửi `trickle:true` trong `voice.invite` và `voice.accept`. Node chỉ bật chế độ nhanh khi cả hai bên hỗ trợ, báo lại qua `voice.accepted.trickle`.
- Với chế độ nhanh, gửi SDP ngay khi có relay đầu tiên, không chờ toàn bộ giao diện mạng. Các relay còn lại được chuyển tiếp bằng `voice.ice {callId,candidate}`; phía nhận xếp hàng nếu SDP chưa áp dụng xong. Chỉ chấp nhận relay, tối đa 128 gói mỗi phía/cuộc gọi.
- Nếu một phía là client cũ hoặc Node cũ không xác nhận `trickle:true`, giữ luồng non-trickle: chờ gathering hoàn tất tối đa 15 giây mỗi bên.
- Sau khi xác định người tham gia, Node kiểm tra quyền hai phía song song cho bước nhận/offer/answer và kiểm tra định kỳ; không bỏ kiểm tra chặn, trạng thái tài khoản hoặc quyền gọi.
- Web lấy sẵn một bộ ICE candidate sau khi xác thực quyền gọi, song song với việc mở micro/camera; audio và video dùng chung transport (`max-bundle`).
- Bên gọi chuẩn bị SDP/relay ngay lúc đổ chuông, chỉ gửi offer khi người nhận đồng ý; tái sử dụng kết quả đã chuẩn bị để giảm thời gian chờ sau khi nhận. Không tự mở micro/camera của người nhận trước khi họ bấm nhận cuộc gọi.
- Chưa ghi âm/ghi hình, lưu lịch sử, gọi nhóm hay nhận cuộc gọi khi đóng web/ứng dụng.
- Trình duyệt có thể chặn phát âm thanh tự động; dùng nút Bật âm thanh.
- Âm báo gọi nằm trong `assets/site/audio/`: `goi-den.mp3` (chuông đến), `goi-di.mp3` (chuông đi) và `end-call.mp3` (kết thúc). Đẩy cả ba file cùng `voice-calls.js` khi triển khai.
- Chuông lặp trong lúc chờ nhận, dừng khi nhận/hủy/kết thúc. Trình duyệt có thể cần người dùng tương tác với trang trước khi cho phép phát chuông đến.
- Một tiến trình Node quản lý cuộc gọi; chưa hỗ trợ nhiều instance độc lập.
- Restart Node sẽ kết thúc các cuộc gọi đang diễn ra.
- Nếu bật CSRF sau này, cần cấu hình riêng endpoint nội bộ dùng shared secret.

## Kiểm tra trước khi bật

### Khi cuộc gọi tự ngắt sau vài phút

- Mã không đặt giới hạn 5 phút. Token realtime lấy khi tải trang có hạn 1 giờ; nếu trang đã mở lâu, cuộc gọi mới có thể chỉ còn vài phút trước khi token hết hạn. Tải lại cả hai trang rồi thử để loại trừ trường hợp này.
- Node ghi một dòng `[voice]` khi kết thúc: `reason`, `phase`, `elapsedSeconds`, `callId`. Trình duyệt ghi `[voice] call ended` cùng trạng thái WebRTC/ICE. Log không chứa token, TURN secret hoặc SDP.
- `expired`: hết token hoặc đạt giới hạn cuộc gọi; `permission`: kiểm tra quyền định kỳ không thành công; `connection`: WebSocket đóng hoặc tiến trình Node dừng.
- Nếu Node ghi `ended`, xem thêm log trình duyệt: có thể người dùng chủ động kết thúc hoặc WebRTC bị lỗi rồi trình duyệt gửi yêu cầu kết thúc. `webrtc-failed`/`webrtc-disconnected` khác với `websocket-closed`.
- Khi cần chẩn đoán production, cập nhật `websoket/src/voice.js`, `websoket/src/server.js`, `assets/site/js/voice-calls.js`, rồi restart tiến trình realtime. Lấy log Node và coturn ở cùng thời điểm xảy ra lỗi; không tự tăng TURN permission lifetime hoặc bỏ kiểm tra quyền.
- Kiểm thử thời hạn bằng đồng hồ giả: `node tests/voice_duration.js` (vượt 6 phút, thu hồi quyền, giới hạn 50 phút, token cũ chỉ còn 5 phút). Đây không thay thế kiểm thử cuộc gọi thật trên mạng production.

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

Kiểm tra tối ưu kết nối: `node tests/voice_trickle.js`, `node tests/voice_ice.js`; kiểm thử trình duyệt `tests/voice_controls.html` mô phỏng relay đến sớm nhưng gathering hoàn tất muộn để xác nhận kết nối bắt đầu trước khi hết chờ.

Triển khai tối ưu này cần cập nhật **cả** `assets/site/js/voice-calls.js` và `websoket/src/voice.js`, rồi restart tiến trình realtime. Chỉ cập nhật JS trên web sẽ vẫn dùng luồng cũ nếu Node chưa hỗ trợ thương lượng trickle.

Đo trên trình duyệt thật bằng dòng Console `[voice] connected`: `trickle:true` xác nhận hai bên/Node đã bật luồng nhanh; `afterAcceptedMs` là thời gian từ khi nhận xác nhận của Node đến khi WebRTC kết nối, `gatherMs` là thời gian chuẩn bị SDP/relay cục bộ. Không suy ra tốc độ production chỉ từ kiểm thử mô phỏng.

Tài liệu này không tự thay đổi server, firewall hoặc triển khai production.
