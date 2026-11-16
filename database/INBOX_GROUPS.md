# Nhóm riêng trong inbox

Các bảng này tách khỏi nhóm cộng đồng/Clubs. Chỉ thành viên hiện tại mới được
xem nhóm và đọc/gửi tin. Mọi thành viên đều được tìm người theo tên/biệt danh
để thêm vào nhóm, không cần kết bạn. Người mới được đọc lịch sử nhóm.

## Khởi tạo lần đầu

Trên server, sau khi cập nhật code, chạy tại thư mục gốc dự án:

```sh
php database/migrate_inbox_groups.php
```

Lệnh tự lấy `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` từ môi trường hoặc file
`.env` giống website, không cần nhập lại tên database hay mật khẩu. PHP CLI cần
extension `pdo_mysql`, tài khoản database cần quyền tạo bảng và thay đổi cấu trúc.
Lệnh tạo các bảng còn thiếu và bổ sung `client_message_id` cùng khóa chống gửi
trùng nếu thiếu trên bảng cũ; có thể chạy lại. Nếu gặp lỗi, lệnh trả mã thoát 1
và in nguyên nhân. MySQL không rollback các thay đổi cấu trúc đã chạy thành công.
Xem trước mà không kết nối database: `php database/migrate_inbox_groups.php --dry-run`.

Không dùng `php database/migrate_inbox_groups.sql`: PHP không thực thi file SQL.

Hoặc nhập SQL trực tiếp:

Trong thư mục dự án, chạy bằng tài khoản MySQL có quyền tạo bảng:

```sh
mysql -u TEN_USER -p TEN_DATABASE < database/migrate_inbox_groups.sql
```

Thay `TEN_USER`, `TEN_DATABASE` bằng tài khoản và tên cơ sở dữ liệu của website.
MySQL sẽ hỏi mật khẩu; không điền mật khẩu vào câu lệnh. Có thể dùng mục
**Import/Nhập** trong phpMyAdmin để nhập cùng file SQL vào đúng database.

File tạo ba bảng `inbox_groups`, `inbox_group_members`, `inbox_group_messages`.
Bảng tin nhắn có khóa chống gửi trùng `client_message_id`. Không có request web
nào tự chạy migration. Không chạy trên production nếu chưa được chủ hệ thống duyệt.

Nếu từng chạy phiên bản SQL cũ chưa có `client_message_id`, cần kiểm tra cấu trúc
bảng trước khi nâng cấp; chạy lại `CREATE TABLE IF NOT EXISTS` không thêm cột vào
bảng đã có. Với bảng cũ đúng cấu trúc ban đầu, chỉ sau khi xác nhận cột và index
chưa tồn tại mới chạy:

```sql
ALTER TABLE inbox_group_messages
  ADD COLUMN client_message_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  ADD UNIQUE KEY uq_inbox_message_retry (group_id, sender_id, client_message_id);
```

## Sử dụng

1. Mở `/messenger`, chọn **Tạo nhóm** trong mục **Nhóm của bạn**.
2. Đặt tên, tìm và chọn từng người bằng tên/biệt danh, avatar và ID, rồi tạo nhóm.
3. Trong menu ⋯ cạnh tên người đang chat, dùng **Thêm vào nhóm** hoặc **Xem nhóm chung**.
4. Mở nhóm trong danh sách; **Thêm người** và **Thành viên** nằm ở đầu khung chat.

Người tạo nhóm có thể **Đổi tên nhóm** ở đầu khung chat hoặc menu ba chấm
cạnh nhóm trong danh sách. Trong **Thành viên**, người tạo nhóm có nút **Xoá**
để gỡ người khác sau khi xác nhận; không thể gỡ chính người tạo nhóm.
Người bị gỡ mất quyền đọc/gửi và không còn thấy nhóm trong danh sách.
Lịch sử tin nhắn vẫn được giữ. Mọi thành viên hiện tại vẫn được thêm người,
kể cả mời lại người đã bị gỡ. Các thao tác này không cần thêm bảng/cột mới.
5. Bản này hỗ trợ chat văn bản, tải lịch sử và số tin chưa đọc. Tin mới cập nhật
   theo chu kỳ bốn giây. Gửi ảnh và rời nhóm chưa nằm trong giao diện này.

Khi có quan hệ chặn với thành viên nhóm, người đó không thể gửi tin qua nhóm để
vượt chặn. Lịch sử/preview/tin chưa đọc được lọc theo quan hệ chặn của người xem.
Tài khoản phải được xác thực trước khi gửi tin, như cơ chế nhắn tin hiện có.

## Kiểm tra

```sh
php tests/inbox_groups.php
php tests/inbox_tabs.php
```

Bài kiểm tra nhóm dùng CI Query Builder và SQLite trong bộ nhớ, không nạp `.env`
và không kết nối database của website. Nó kiểm tra quyền thành viên, chặn, tạo/thêm,
phân trang, token gửi lại, lịch sử từ nhóm rỗng và rollback. SQLite không thay thế
kiểm chứng khóa dòng/đồng thời trên MySQL thật.
