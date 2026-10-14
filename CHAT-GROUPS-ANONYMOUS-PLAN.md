# Phương án bổ sung nhóm chat và chat ẩn danh

> Cập nhật triển khai 14/10/2026: chủ dự án đổi quy tắc thành **khách chưa đăng nhập chat ẩn danh, thành viên dùng hồ sơ thật**, và chọn **chỉ làm nhóm chat/khách ẩn danh trước, giữ chat riêng và ví hiện tại**. Các phần account anonymous, chia sẻ hồ sơ theo mốc, cấp độ/xu/quà bên dưới là nghiên cứu ban đầu, không phải phạm vi đã triển khai. Xem `CHAT-GROUPS-DEPLOYMENT.md` cho chức năng thực tế, migration, cấu hình và kiểm thử.

Ngày nghiên cứu: 14/10/2026. Nguồn yêu cầu: `D:\Downloads\Tinh-nang-Nhom-chat.docx`, bản “Nhóm chat và kết nối ẩn danh”, 36 mục. Đây là kết quả đọc tài liệu và đối chiếu mã nguồn, chưa phải tính năng đã triển khai. Các mốc và hạn mức trong tài liệu được xem là đề xuất cấu hình sản phẩm.

## 1. Kết luận

Tích hợp được vào ứng dụng PHP/CodeIgniter và Node WebSocket hiện tại. Nên mở rộng phòng chung thành nền nhiều nhóm, dùng chung dịch vụ quyền, lưu tin, kiểm duyệt và realtime; danh tính công khai/ẩn danh là thuộc tính bất biến của nhóm sau khi có tin.

Chat riêng ẩn danh cần ngữ cảnh riêng, lời mời được chấp nhận và serializer riêng. Không đưa dữ liệu ẩn danh vào API chat thường khi các endpoint cũ còn trả hồ sơ thật. Không xây thêm hệ thống tài khoản hoặc ví xu khả dụng song song.

Phạm vi tài liệu gồm bốn phần: nhiều nhóm; danh tính/avatar ẩn danh; kết nối riêng và chia sẻ hồ sơ; cấp độ/xu khóa/quà. Không phải chỉ thêm hai trang giao diện.

## 2. Những gì đã xác minh trong repository

| Thành phần | Hiện trạng | Hệ quả khi bổ sung |
| --- | --- | --- |
| `database/schema.sql` – `room_messages` | Một phòng chung, chưa có `room_id`, reply, client key hoặc identity | Thêm ngữ cảnh nhóm và index trước khi tạo nhóm mới |
| `application/controllers/Ajax.php` – `room_messages`, `room_send` | Đọc/ghi phòng chung, trả tên, avatar, slug và user ID thật; preview cũng có tên thật | Endpoint cũ phải giới hạn nhóm mặc định; không đọc chung toàn bộ bảng sau migration |
| `application/controllers/api/Chat.php` – `room`, `room_send` | API thứ hai cho cùng phòng, khách đọc được, serializer có hồ sơ thật | Cùng gọi dịch vụ quyền/serializer với web, tránh sửa một đường bỏ sót đường còn lại |
| `application/libraries/Realtime.php` – `tin_phong`, `phong_moi` | Payload chứa hồ sơ thật; phát `room.message` tới `all` | Chỉ dùng cho nhóm công khai mặc định cũ; sự kiện nhóm mới phải phân kênh và lọc theo người xem |
| `websoket/src/server.js` | Có thật; quản lý kết nối theo uid, phát toàn cục hoặc từng user; chưa có subscription nhóm | Thêm subscribe/unsubscribe được PHP xác thực, thu hồi quyền, presence từng nhóm |
| Online | Node đếm user kết nối toàn hệ thống; API phòng đếm tài khoản hoạt động gần đây | Chưa phải số người đang mở từng nhóm |
| `assets/site/js/chat-widget.js` | Sticky có một phòng chung và chat riêng; danh sách online dùng endpoint chung | Thêm group ID vào state/cache/listener; online phải theo nhóm |
| `conversations` | UNIQUE cặp `user_low_id,user_high_id` | Không thể lưu thêm chat ẩn danh cùng cặp mà giữ nguyên ràng buộc và truy vấn |
| `M_interaction::conversation_with`, `conversations` | Tìm hội thoại theo cặp, danh sách join hồ sơ thật, có tự tạo hội thoại từ lượt thích | Cần cách ly ngữ cảnh ẩn danh khỏi truy vấn cũ |
| `Chat::messages` | GET lịch sử đồng thời gọi `mark_read` | Với luồng mới, tách đọc lịch sử và ghi mốc đã đọc thực tế |
| Chat thường | `message_permission` kiểm tra chặn/quyền nhận tin; code và test cho phép nhắn khi chưa match | Khi chuyển sang hồ sơ thật, dùng hành vi đang chạy; không lấy comment cũ “chỉ khi đã ghép đôi” làm luật |
| Ví | `users.coin_balance`, `coin_transactions`, `M_user::adjust_coin`, `M_billing::coin_history` | Tái sử dụng ví khả dụng, bổ sung giao dịch và ledger cần thiết |
| `M_user::adjust_coin` | Có transaction nhưng đọc số dư rồi ghi lại, chưa khóa hàng trong hàm này | Chưa đủ cho chuyển xu đồng thời; cần thống nhất cơ chế khóa ở mọi đường ghi ví liên quan |
| Realtime publish | Gọi HTTP lúc shutdown, chưa có outbox bền vững trong đoạn đã đọc | Thêm outbox để retry; DB commit thành công không trở thành lỗi gửi chỉ vì Node lỗi |
| Migration | Có `database/update.php` và các script migrate riêng | Tạo migration có version, chạy lại an toàn; không dùng schema tạo mới có DROP để nâng cấp |

Đây là đối chiếu file trong workspace, chưa xác minh schema DB đang chạy, phiên bản Node triển khai, tải thực tế hay cấu hình proxy trên production.

## 3. Hành vi sản phẩm đề xuất

- `/nhom`: tìm kiếm, lọc chủ đề/khu vực/công khai/ẩn danh; thẻ có online thực, hoạt động mới nhất và nút mở. `/nhom/{slug}` là landing riêng, chia sẻ được; nhóm đóng giữ URL và thông báo.
- Công khai: khách đọc, tài khoản đủ điều kiện gửi. Ẩn danh: khách chỉ xem giới thiệu, đăng nhập mới đọc; gửi vẫn kiểm tra điều kiện xác thực/tuổi/hồ sơ hiện hành ở server. Không có bước tham gia bắt buộc.
- Theo dõi hoặc gửi đầu tiên thành công mới lưu vào sticky. Đọc hoặc gửi lỗi không lưu. Bỏ theo dõi ẩn nhóm; chỉ mở lại URL không tự làm hiện. Tắt thông báo vẫn giữ unread.
- Sticky giữ nhóm mặc định, nhóm đã lưu, chat thường, chat riêng ẩn danh và Khám phá nhóm. Hai loại chat riêng có nhãn phân biệt; trang đầy đủ dùng cùng API với sticky.
- Ẩn danh được cấp nickname/avatar ổn định theo tài khoản/phòng, public ID ngẫu nhiên độc lập. Không dùng tên/ảnh hồ sơ hoặc ID tài khoản làm seed, URL hay mã public.
- Thẻ ẩn danh chỉ có nickname, avatar, huy hiệu cấp, mời trò chuyện, chặn và báo cáo. Mặc định tắt gửi ảnh trong nhóm ẩn danh.
- Chat riêng cần lời mời được chấp nhận. Mặc định 5 lời mời/ngày, hết hạn 48 giờ, từ chối có cooldown 7 ngày; một cặp chỉ có một lời mời/kết nối đang chờ hoặc hoạt động dù gặp ở nhiều nhóm.
- 15 phút trò chuyện tích cực: mở quà/chuyển xu. 30 phút + cấp 2: yêu cầu giới thiệu/sở thích/tỉnh thành. 60 phút + cấp 3: yêu cầu tên hiển thị/ảnh/URL hồ sơ công khai. Mọi chia sẻ theo từng trường, một chiều, có đồng ý và thu hồi.
- Phút tính trên server theo bucket 60 giây: cả hai đang mở chat, heartbeat trong 45 giây, mỗi bên có tin hợp lệ trong 120 giây. Nhiều tab không nhân phút; nền/spam/quà không tính.
- Xu khóa tách khỏi xu khả dụng. Chi phí nâng cấp từng bước 100/300/600; thưởng tối đa 35/ngày theo `Asia/Ho_Chi_Minh`. Quà chỉ trừ bên gửi; chuyển xu có debit/credit cân bằng. Cấp/quà không tự mở dữ liệu thật.

“Ghép đôi ẩn danh” trong tài liệu là lời mời từ danh tính trong nhóm, chưa phải ghép cặp ngẫu nhiên toàn hệ thống. Nếu muốn nút ghép ngẫu nhiên, cần bổ sung quy tắc hàng chờ, lọc, timeout và chống ghép lại; không tự suy diễn phần này.

## 4. Thiết kế kỹ thuật đề xuất

### Nhóm và quyền

Thêm `chat_groups`, `chat_group_users`, report/log và room-scoped messages. Dịch vụ dùng chung kiểm tra `can_view`, `can_send`, `can_subscribe`, `can_moderate`; cả Ajax, API, media và Node callback cùng dựa vào dịch vụ này. `readonly` chặn gửi kể cả điều hành; `closed` chặn lịch sử/subscription người dùng; ban khỏi nhóm chặn cả đọc.

`room_messages` bổ sung `room_id`, `reply_to_id`, `client_message_id`, `sender_identity_id`, `deleted_by`. Dedupe bằng khóa client theo người gửi và ngữ cảnh; reply/pin phải cùng nhóm. Read marker chỉ tăng, không tính tin mình/tin xóa; follow lấy mốc hiện tại. Tin xóa phải biến mất khỏi preview/reply/pin và thông báo chưa gửi.

### Chat riêng ẩn danh

Đề xuất bảng `anonymous_connections`, `anonymous_participants`, `anonymous_messages` riêng cho bản đầu. Lý do: khóa cặp và truy vấn legacy của `conversations` đang gắn với hồ sơ thật. Tái sử dụng dịch vụ xử lý nội dung, file, retry, outbox và kiểm duyệt; không nhân đôi tài khoản/ví. Quyết định cuối cần đối chiếu schema thực trước migration.

Dùng bảng giữ chỗ cặp chuẩn hóa `anonymous_pair_slots` với UNIQUE(low_user,high_user), khóa trong transaction để bảo đảm một pending/active connection mỗi cặp; giữ lịch sử lời mời/kết nối riêng khi kết thúc. Không chỉ kiểm tra “chưa có” rồi INSERT vì có race. Cooldown và chặn kiểm tra lại khi nhận lời mời.

Identity và participant có public ID độc lập, không dùng ID phòng để liên kết các phòng với nhau. Snapshot nickname/avatar khi chấp nhận. API không nhận `recipient_user_id` từ client cho luồng này. Chặn ánh xạ nội bộ qua bảng `blocks`; không trả danh sách nickname liên quan.

Thêm bucket hoạt động UNIQUE(connection,bucket), request/grant theo owner/viewer/field và rules snapshot tại lúc tạo kết nối. Sau thu hồi/chặn/kết thúc, shared-profile kiểm tra lại quyền hiện tại và hủy cache/subscription. Thông tin riêng không được serialize rồi chỉ che bằng CSS.

### Avatar, cấp độ và giao dịch

Avatar có collection/asset/part, nguồn và giấy phép, min level, trạng thái cấp phát và fallback. Generator dùng seed ngẫu nhiên và version lưu nội bộ, tạo ảnh tĩnh một lần. Asset ngừng cấp không làm hỏng avatar cũ; gỡ hoàn toàn phải thay fallback trên các bề mặt.

Thêm level rules/version, user level, số dư xu khóa và ledger riêng. Nâng cấp khóa tài khoản và ghi ledger cùng transaction. Quà giữ snapshot giá/icon. Chuyển xu khóa hai tài khoản theo thứ tự ID ổn định, kiểm tra hạn mức và idempotency trong cùng transaction. Mọi đường ghi ví hiện có phải tương thích cơ chế khóa; hai lần gọi `adjust_coin` độc lập không tạo thành chuyển xu nguyên tử.

Reward UNIQUE(user,task,date,pair) với source key xác định; dùng ngày Việt Nam. Điều chỉnh dùng giao dịch bù có lý do/actor, không sửa ledger gốc. Snapshot cấu hình kết nối và giao dịch khác với quyền bảo mật hiện tại: chặn/khóa luôn có hiệu lực ngay.

### Realtime và media

Node tiếp tục không truy DB, gọi PHP xác thực subscription theo token người dùng và context. Thêm kênh group trực tiếp, summary nhóm đã lưu và anonymous connection. Thu hồi quyền phải hủy subscription và ngăn phát tiếp; reconnect xác thực lại và tải bù qua API.

Không phát sự kiện ẩn danh bằng `to: all`. Trong nhóm có lọc chặn theo viewer, cần lọc người nhận phía server hoặc fanout theo user, không gửi tin bị chặn xuống rồi giấu ở UI. Presence đếm user duy nhất trong từng nhóm; mở nhiều tab không tăng online.

Payload ẩn danh dùng allowlist: message ID, context public ID, public participant ID, nickname/avatar biểu tượng, type/content/time, mine. Kiểm tra cả reply, online, sticky, lỗi, email/push và analytics. Không công khai user ID, slug, avatar thật hoặc ánh xạ identity-user.

Ảnh ẩn danh nếu bật phải có endpoint có quyền, tên ngẫu nhiên, bỏ EXIF và kiểm tra định dạng thực. Không tái dùng đường `uploads/chat/...` công khai hiện tại cho media riêng. Chặn truy cập trực tiếp file ở web server; PHP có thể kiểm tra quyền rồi chuyển file qua cơ chế nội bộ của server.

Outbox ghi cùng transaction với tin/giao dịch, worker publish sau commit; polling là đường tải bù. Client dedupe bằng message ID và client key, hủy listener/request cũ khi đổi nhóm. Read chỉ ghi khi focus và đã đọc đến vị trí; tải lịch sử không tự đánh dấu toàn bộ.

## 5. Trình tự triển khai và điểm nghiệm thu

| Giai đoạn | Công việc chính | Điều kiện bật |
| --- | --- | --- |
| 1 – Nhiều nhóm | Migration nhóm mặc định, admin CRUD/role, landing/discovery, lịch sử/send/reply/pin, follow/sticky/unread, subscription/presence và outbox | Legacy chỉ đọc nhóm mặc định; không trộn tin, trùng tin, unread sai; quyền readonly/closed/ban hiệu lực HTTP và WS |
| 2 – Ẩn danh | Identity, avatar/catalog/admin/generator, serializer, protected media, chặn/báo cáo/audit | Không rò hồ sơ thật trên mọi bề mặt; khách không lấy lịch sử/media; fallback avatar hoạt động |
| 3 – Kết nối riêng | Invitation/cooldown, pair locking, active minutes, share requests/grants/revoke, chuyển sang chat thường | Chỉ nhắn sau accept; nhiều thiết bị không nhân phút; chia sẻ đúng chiều/field; chặn/thu hồi có hiệu lực |
| 4 – Kinh tế | Xu khóa/reward/level, gift/transfer, hạn mức/version, ledger/outbox/admin tra cứu | Test đồng thời/retry không âm ví hoặc trừ trùng; debit-credit cân bằng; quà không tăng ví người nhận |

Mỗi giai đoạn có feature flag phía server; tắt flag chặn API/subscription tương ứng, không chỉ ẩn menu. Không bật giao dịch thật trước kiểm thử ledger và cạnh tranh DB.

Migration tạo nhóm mặc định **Nhóm Hẹn Hò 4 Phương**, slug `hen-ho-4-phuong`, gán tin cũ về nhóm này và giữ nguyên ID/người gửi/thời gian. Không chuyển lịch sử công khai thành ẩn danh, không tự follow toàn bộ user, không làm tin cũ thành unread. User cũ cấp 1/xu khóa 0; giữ ví/VIP/streak. Dùng migration riêng có version/backfill và bước kiểm tra hoàn tất trước bật flag. Rollback bằng tắt chức năng, bảo toàn lịch sử/giao dịch.

## 6. Kiểm thử cần bổ sung

1. Ma trận guest/user chưa xác thực/user hợp lệ/moderator/admin × public/anonymous × active/readonly/closed/banned, cho cả HTTP, WS, media và API cũ.
2. Legacy `ajax/phong-chat`, `api/room`, online/preview và `Realtime::phong_moi` không đọc/phát tin ngoài nhóm mặc định. Chat thường/email vẫn đúng, ví/VIP/streak không thay đổi ngoài nghiệp vụ được thêm.
3. Dò payload/HTML/media/notification/cache tìm ID, slug, email, ảnh/tên thật; kiểm tra xuyên nhóm, xuyên nickname, reply và shared-profile của người khác.
4. Concurrent cấp identity, lời mời/accept, client retry, nhiều tab, reconnect, follow/unfollow/mute, read marker giảm, soft-delete và mất publish sau commit.
5. Đồng hồ giả quanh mốc 15/30/60; thiếu level, consent một chiều, revoke/end/block; không cộng khi tab nền, thiếu heartbeat hoặc chỉ có quà/spam.
6. DB InnoDB thực với hai kết nối độc lập: nâng cấp/chuyển xu/tặng quà đồng thời, request key trùng hoặc đổi payload, hạn mức ngày, thiếu tiền, rollback, ledger bù. Test mock hiện có không đủ chứng minh khóa và tính nguyên tử.
7. Mobile: danh sách/ô nhập/bàn phím/safe area, cuộn tải cũ, giữ vị trí ảnh, nút tin mới, nội dung dài và đổi nhóm nhanh.

Các test hiện có liên quan để giữ hồi quy: `tests/chat_online_members.php`, `tests/message_without_match.php`, `tests/message_email_privacy.php`, `tests/notification_actors.php`, `tests/seo_metadata.php`. Chưa chạy trong lượt nghiên cứu này vì chưa đổi runtime.

## 7. Tích hợp giao diện và bàn giao

Trang chủ hiện có hai khối ẩn danh chưa có URL. Sau nghiệm thu giai đoạn 2, Phòng Chat Ẩn Danh có thể dẫn tới `/nhom?identity_mode=anonymous`. Sau giai đoạn 3, Ghép Đôi Ẩn Danh dẫn tới danh sách lời mời/kết nối hoặc màn hình chọn nhóm để làm quen; URL riêng cần chốt cùng giao diện. Không gắn link vào chức năng chưa hoạt động. Tìm Bạn Quanh Đây vẫn là `/hen-ho`.

Bàn giao triển khai cần gồm migration, route/API, cấu hình PHP/Node/proxy, seed avatar/quà/cấp, flags, worker outbox, hướng dẫn admin/kiểm duyệt/ledger, checklist bật/tắt và kết quả test. Landing nhóm có metadata/canonical; nhóm đóng noindex/loại sitemap; nội dung ẩn danh/media/private không vào sitemap và bắt buộc quyền truy cập.

Trước khi chạy trên môi trường thật cần đối chiếu schema đã migrate, luật tuổi/xác thực hiện hành, ACL admin, cách phục vụ uploads, cấu hình PHP–Node và tất cả nơi ghi số dư. Không cần khóa các lựa chọn avatar hoặc mức thưởng để bắt đầu nền nhóm; có thể dùng các mặc định tài liệu làm cấu hình ban đầu. Chưa chạy migration hoặc thay đổi chức năng chat trong lượt nghiên cứu này.
