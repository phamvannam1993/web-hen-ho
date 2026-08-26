# Cập nhật lên máy chủ

## Các bước

Đăng nhập SSH vào máy chủ rồi chạy lần lượt:

    # 1. Vào thư mục dự án (sửa lại cho đúng đường dẫn trên máy của bạn)
    cd /var/www/web-hen-ho

    # 2. Sao lưu cơ sở dữ liệu — luôn làm bước này trước
    mysqldump -u DB_USER -p TEN_DB > ~/backup-$(date +%F-%H%M).sql

    # 3. Lấy code mới
    git pull

    # 4. Cập nhật dữ liệu
    php database/update.php

Lệnh ở bước 4 in ra tiến trình theo từng phần:

    == 1. Khoá cấu hình mới ==
    == 2. Danh mục tỉnh/thành ==
    == 3. Cột dữ liệu mới ==
    == 4. Dọn trạng thái online giả ==
    == 5. Hồ sơ thành viên ==

Chạy lại nhiều lần vẫn an toàn: lần thứ hai trở đi mọi mục sẽ báo `0`,
nghĩa là không có gì phải làm thêm.

## Muốn tạo thêm thành viên mẫu

    php database/update.php 30

Số `30` là số tài khoản cần tạo. Chúng có email dạng `@demo.local`,
mật khẩu `123456`, và được điền sẵn hồ sơ đầy đủ để hiện lên các trang.

Gỡ khi không cần nữa:

    mysql -u DB_USER -p TEN_DB -e "DELETE FROM users WHERE email LIKE '%@demo.local';"

## Lệnh này làm những gì

1. Thêm các khoá cấu hình mới (thông tin công ty, mạng xã hội, công tắc đăng tin,
   công tắc chỉ hiện người đang online)
2. Cập nhật danh mục tỉnh/thành sang 34 đơn vị hành chính hiện hành. Thành viên
   thuộc tỉnh đã sáp nhập được chuyển sang tỉnh kế thừa trước khi xoá tỉnh cũ,
   nên không ai bị mất khu vực
3. Thêm cột `confide_topic` (chủ đề tâm sự) vào bảng `users` và gán chủ đề cho
   những hồ sơ còn trống, để trang `/tam-su` có nội dung
4. Đưa các tài khoản mẫu đang bị đánh dấu "đang online" về quá khứ, để nhãn
   ONLINE chỉ phản ánh người thật đang mở trang
5. Điền các trường hồ sơ còn trống (chiều cao, cân nặng, nghề, học vấn, hôn nhân,
   con cái, hút thuốc, uống rượu, sở thích) để bộ lọc tìm kiếm có dữ liệu mà lọc

Chỉ ghi vào ô đang trống, **không đè lên dữ liệu người dùng đã tự nhập**.

## Sau khi chạy

- Nhấn `Ctrl+Shift+R` trên trình duyệt để nạp lại CSS mới
- Vào **Quản trị → Cấu hình** điền: tên công ty, mã số thuế, địa chỉ, Zalo,
  link Facebook / YouTube / TikTok / Instagram (bỏ trống thì footer tự ẩn mục đó)

## Nếu cần quay lại

    mysql -u DB_USER -p TEN_DB < ~/backup-YYYY-MM-DD-HHMM.sql

## Lưu ý

- **Không chạy `database/schema.sql` trên máy chủ đang có dữ liệu.** Tệp đó chứa
  lệnh `DROP TABLE` và tự trỏ vào cơ sở dữ liệu `web_hen_ho`, chạy vào là mất sạch.
  Chỉ dùng khi cài mới hoàn toàn.
- Lệnh `php` phải là bản CLI có sẵn extension `pdo_mysql`. Kiểm tra nhanh:

      php -m | grep pdo_mysql

- Thông số kết nối lấy từ tệp `.env` ở thư mục gốc dự án. Nếu báo
  *"Không kết nối được cơ sở dữ liệu"*, kiểm tra `DB_HOST`, `DB_NAME`,
  `DB_USER`, `DB_PASS` trong đó.
