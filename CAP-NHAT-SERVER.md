# Cập nhật lên máy chủ

## 1. Sao lưu trước (bắt buộc)

    mysqldump -u DB_USER -p TEN_DB > ~/backup-$(date +%F).sql

## 2. Kéo code mới

    cd /var/www/web-hen-ho
    git pull

## 3. Chạy cập nhật dữ liệu

    php database/update.php

Lệnh này làm ba việc, chạy lại nhiều lần vẫn an toàn:

1. Thêm các khoá cấu hình mới — thông tin công ty, mạng xã hội, công tắc bật/tắt đăng tin
2. Cập nhật danh mục tỉnh/thành sang 34 đơn vị hành chính hiện hành; thành viên
   thuộc tỉnh đã sáp nhập được chuyển sang tỉnh kế thừa, không ai bị mất khu vực
3. Điền các trường hồ sơ còn trống (chiều cao, học vấn, hôn nhân, hút thuốc,
   uống rượu, sở thích...) để bộ lọc tìm kiếm có dữ liệu mà lọc

### Muốn thêm cả thành viên mẫu

    php database/update.php 30

Số 30 là số tài khoản mẫu cần tạo. Chúng có email dạng `@demo.local`,
mật khẩu `123456`. Gỡ lại khi không cần nữa:

    DELETE FROM users WHERE email LIKE '%@demo.local';

## 4. Sau khi chạy

- Nhấn Ctrl+Shift+R trên trình duyệt để nạp lại CSS mới
- Vào Quản trị → Cấu hình điền: tên công ty, mã số thuế, địa chỉ, Zalo,
  và link Facebook / YouTube / TikTok / Instagram (bỏ trống thì footer tự ẩn)

## Nếu cần quay lại

    mysql -u DB_USER -p TEN_DB < ~/backup-YYYY-MM-DD.sql
