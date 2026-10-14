# Nhóm chat và khách chat ẩn danh

## Phạm vi đã chốt

Theo điều chỉnh của chủ dự án ngày 14/10/2026: khách chưa đăng nhập được dùng nickname/avatar ẩn danh trong phòng cho phép khách; thành viên đã đăng nhập dùng tên, avatar và liên kết hồ sơ công khai hiện tại. Tin khách cũ không được gán lại cho tài khoản sau đăng nhập.

Đợt này triển khai nhóm chat và khách chat ẩn danh. Chat riêng, ví xu, VIP và streak tiếp tục dùng luồng cũ. Chat riêng ẩn danh, mốc chia sẻ hồ sơ, cấp độ, avatar theo cấp và quà/chuyển xu trong DOCX được hoãn theo lựa chọn của người dùng.

## Có trong bản này

- `/nhom`: tìm kiếm tên/mô tả, lọc chủ đề, khu vực và chế độ, phân trang. `/nhom/{slug}` có landing render server, metadata/canonical và trạng thái đóng/noindex. Nội dung chat tải qua API không có trong HTML SEO.
- Admin `/admin/chat_groups`: tạo/sửa nhóm, avatar PNG/WebP, nội quy, ưu tiên, chủ đề/khu vực, trạng thái, điều hành được phân công, báo cáo và nhật ký. Slug chỉ đặt khi tạo; không đổi chế độ sau khi có tin. Nhóm mặc định cố định ID 1 và chế độ công khai.
- Nhóm công khai cho khách đọc, thành viên đủ điều kiện gửi. Nhóm cho khách ẩn danh cho khách gửi chữ/emoji sau xác nhận tuổi/nội quy. Thành viên vẫn phải đạt điều kiện tuổi/xác thực hiện hành.
- Nickname khách cấp tự động, ID công khai ngẫu nhiên theo phòng, giữ qua các lần mở bằng cookie HttpOnly 30 ngày. Avatar khách dùng biểu tượng có sẵn trên máy chủ. Không cho khách tự nhập nickname, tải avatar hoặc gửi ảnh.
- Theo dõi/bỏ theo dõi, tự lưu khi gửi đầu tiên thành công, sticky và sidebar desktop, unread không tính tin mình/tin xóa, read marker chỉ tăng, tắt thông báo không ẩn nhóm hoặc xóa unread. Nhóm mặc định không lặp trong danh sách đã lưu.
- Tải tin mới, tải lịch sử khi cuộn lên, trả lời cùng nhóm, một tin ghim, nút tin mới, emoji, retry có client key. Ảnh thành viên được kiểm tra và re-encode WebP bỏ metadata; tải qua endpoint có kiểm tra quyền.
- Chặn theo actor nội bộ, báo cáo chống bản ghi trùng, ghim/bỏ ghim/xóa mềm, cấm gửi một giờ/gỡ cấm, cấm khỏi nhóm/gỡ cấm, đặt lại nickname khách. Điều hành chỉ thao tác nhóm được phân công. Hành động kiểm duyệt có actor, mục tiêu, lý do và thời gian.
- WebSocket riêng hỗ trợ khách: ticket có scope/expiry, xác thực PHP lúc kết nối và trước sự kiện; kiểm tra lại mỗi 15 giây. Chỉ phát tín hiệu thay đổi, nội dung được tải lại qua API có quyền. Outbox lưu cùng transaction với tin và kiểm duyệt; Node lấy mỗi 2 giây, lỗi publish giữ lại để thử sau; polling 4 giây là dự phòng.
- API chat chung cũ chỉ đọc phòng ID 1, kiểm tra cấm/quyền; broadcast legacy lọc quyền người nhận. Tin mặc định gửi từ giao diện mới được phát cho client cũ qua outbox. Chat riêng hiện tại giữ nguyên.

## Triển khai

1. Sao lưu database và triển khai code PHP với `CHAT_GROUPS_ENABLED=0`. Đối chiếu `room_messages.user_id` là BIGINT UNSIGNED và FK hiện hành trước khi chạy; migration này dành cho schema dự án đã nghiên cứu.
2. Xem trước: `php database/migrate_chat_groups.php --dry-run`. Sau đó chạy **trên database đích đã được phê duyệt**: `php database/migrate_chat_groups.php`.
3. Migration bổ sung bảng/cột/index, cho `room_messages.user_id` nhận NULL cho khách, gán mặc định `room_id=1` cho lịch sử cũ và seed hai nhóm: `hen-ho-4-phuong`, `phong-chat-an-danh`. Không đổi ID/nội dung/thời gian tin cũ, không tự follow hoặc tạo unread cho tài khoản cũ. Có khóa migration, version và chạy lại an toàn.
4. PHP cần mysqli, mbstring, curl và GD có WebP. Cho PHP ghi `writable/chat-media` (ảnh riêng), `uploads/group-icons` (avatar nhóm), session và log. Có `APP_KEY` riêng của môi trường.
5. Máy Node: `cd websoket`, `npm ci --omit=dev`. Cấu hình `REALTIME_SECRET` trùng PHP; `REALTIME_APP_URL` là URL PHP mà Node truy cập được; `REALTIME_HTTP_URL` phía PHP là URL Node truy cập được. PHP và Node không cần cùng máy.
6. Reverse proxy chuyển cả `/ws` và `/ws/groups` tới Node, giữ upgrade headers. Nếu cấu hình hiện tại dùng `location /ws`, nó đã bao phủ `/ws/groups`; nếu dùng exact-match thì phải thêm location mới. Chặn đường tĩnh `/writable/` trên nginx; Apache dùng `.htaccess` trong repository. Không mở `/publish` cho truy cập công khai; callback `/internal/chat-groups/*` luôn yêu cầu secret, nên giới hạn thêm IP Node ở proxy nếu môi trường hỗ trợ.
7. Đặt `CHAT_GROUPS_ENABLED=1` trong cấu hình PHP rồi restart/reload PHP khi môi trường cần. Restart dịch vụ Node hiện tại để nạp `src/groups.js`. Node tự chạy worker outbox, không cần thêm cron để phát tin.
8. Mở admin tạo một nhóm thử. Kiểm tra bằng hai trình duyệt: khách chat nickname; thành viên hiện hồ sơ thật; read-only/cấm/đóng có hiệu lực; kiểm tra WS, ảnh và truy cập trực tiếp `/writable/chat-media/...` bị từ chối. Kiểm tra phòng mặc định/client cũ và chat riêng.

Đã chạy migration trên database kiểm thử riêng tại máy làm việc; chưa chạy trên database đang vận hành và chưa tự bật feature flag môi trường thật.

## Route/API

Web API dùng session/cookie cùng site. POST bắt buộc `X-Chat-CSRF` lấy từ panel, kể cả khi cấu hình CSRF toàn site đang tắt. Server lấy actor từ session/guest cookie, không nhận user ID để giả danh.

| Route | Chức năng |
| --- | --- |
| GET `/nhom`, `/nhom/{slug}` | Khám phá / landing |
| GET `/ajax/nhom-panel/{id}` | Cùng component chat cho sticky |
| GET `/ajax/nhom/0/saved` | Nhóm đã lưu của actor hiện tại |
| GET `/ajax/nhom/{id}/messages` | Cursor `before_id`/`after_id`, kiểm tra tin đã xóa và cập nhật sender |
| GET `/ajax/nhom/{id}/online` | Người đang mở phòng, heartbeat 45 giây, không trùng user nhiều tab |
| POST `/ajax/nhom/{id}/open` | Cấp/lấy identity, ticket WS; không tự follow |
| POST `/ajax/nhom/{id}/send` | `content`, `client_message_id`, `reply_to_id`, `guest_consent` |
| POST `/ajax/nhom/{id}/image` | Ảnh thành viên khi admin bật, tối đa 5 MB / 16 megapixel |
| POST `/ajax/nhom/{id}/{follow,unfollow,hide,notifications,read,presence,block,report}` | Trạng thái cá nhân / báo cáo |
| POST `/ajax/nhom/{id}/moderate` | `action`, `target` (message ID hoặc public identity ID), `reason` |
| GET `/nhom-media/{message_id}` | Ảnh có kiểm tra quyền, không cache công khai |
| POST `/internal/chat-groups/{authorize,flush,legacy}` | Node↔PHP, yêu cầu `X-Realtime-Secret` |

Nhóm chỉ đọc chặn gửi cả điều hành. Nhóm đóng chặn lịch sử/subscription người dùng; admin/điều hành được phân công vẫn xem phục vụ kiểm duyệt. Ban nhóm chặn cả đọc/gửi/media. Danh tính khách không có user_id, email, slug hồ sơ hoặc đường avatar thật trong payload.

## Kiểm thử

`php tests/chat_groups_http.php` tạo database riêng tên `sc_chat_test_<ngẫu nhiên>`, chạy migration hai lần, kiểm thử model InnoDB, hai tiến trình gửi đồng thời, HTTP/cookie/CSRF/media và WebSocket thật. Không dùng DB ứng dụng. Mặc định MySQL local root không mật khẩu; dùng `CHAT_TEST_DB_HOST`, `CHAT_TEST_DB_USER`, `CHAT_TEST_DB_PASS` nếu khác. Database kiểm thử được giữ lại để tra cứu, không tự xóa database.

Đã kiểm tra: giữ lịch sử cũ; cách ly nhóm; guest/member identity; retry và xung đột key; follow/unfollow/unread; read chỉ tăng; mutes; reply khác nhóm; ghim/xóa; ban/block; ảnh tắt mặc định, ảnh hợp lệ và thu hồi quyền ảnh; CSRF; ticket giả; thu hồi subscription; closed/noindex/404. Đã chạy hồi quy `chat_online_members`, `message_without_match`, `message_email_privacy`, `notification_actors`, `seo_metadata`.

Test `message_without_match` được bổ sung mock Realtime còn thiếu để chạy được với luồng chat hiện tại; không thay đổi nghiệp vụ chat riêng.

Chưa xác minh bằng browser thật các khác biệt bàn phím iOS/Android và cuộn ảnh trên thiết bị. CSS có bố cục mobile một cột, safe-area và vùng cuộn riêng; cần nghiệm thu thiết bị trước khi mở rộng truy cập. Chưa kiểm thử tải lớn hoặc nhiều Node instance; presence và fanout hiện dành cho một Node instance như hạ tầng cũ.

## Vận hành và tắt tính năng

- `CHAT_GROUPS_ENABLED=0` tắt route, link, panel và subscription nhóm mới, giữ dữ liệu. Không DROP bảng để rollback. Giữ code phân nhóm ở API cũ để không đọc nhầm tin mới sau rollback.
- Nickname khách phụ thuộc cookie, không phải bằng chứng danh tính. Xóa cookie/đổi trình duyệt có thể tạo khách mới; rate limit bổ sung theo IP (30 tin/phút) và actor khách (8 tin/phút). Thành viên 15 tin/phút. Không khẳng định chặn tuyệt đối người cố tình quay lại.
- Online là actor đang giữ heartbeat gần đây, timeout 45 giây; nhiều tab cùng actor tính một người. Một tab rời không xóa heartbeat của tab khác. Khách ở trình duyệt khác được tính là actor khác.
- Ảnh dùng endpoint kiểm quyền; nhóm cho khách là phòng đọc công khai nên người bị cấm vẫn có thể xóa cookie và xem như khách mới. Đây là giới hạn của yêu cầu cho khách tham gia, khác với phòng chỉ cho tài khoản.
- Giữ nguyên bản gốc tin xóa trong DB phục vụ xử lý; API/preview/pin/media thường không trả nội dung đó. Nhật ký kiểm duyệt và báo cáo xem ở admin. Không hard delete nhóm hoặc tài nguyên đang được tham chiếu.
- Outbox chỉ chứa ID nhóm/tin. Có thể dọn các bản ghi đã gửi cũ và rate buckets hết hạn theo chính sách vận hành; không xóa lịch sử chat, báo cáo hoặc audit trong thao tác dọn này.
