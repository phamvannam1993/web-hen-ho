# Bản mẫu cấu hình

Thư mục `application/config/` bị loại khỏi git vì chứa mật khẩu cơ sở dữ liệu,
mật khẩu email và khoá ký token. Các tệp ở đây là bản mẫu để dựng lại.

## Khi cài trên máy mới

```bash
cp config-example/*.php application/config/
cp .env.example .env
```

Sau đó mở `.env` và điền thông tin thật. Các tệp cấu hình đều đọc giá trị
nhạy cảm từ `.env` nên thường không phải sửa gì thêm trong `application/config/`.

## Sinh khoá ngẫu nhiên

```bash
php -r 'echo bin2hex(random_bytes(16)), "\n";'   # APP_KEY
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"  # REALTIME_SECRET
```

`REALTIME_SECRET` phải đặt giống nhau ở cả `.env` này và `websoket/.env`,
nếu lệch thì chat thời gian thực không kết nối được.

## Phân quyền trên máy chủ

```bash
chmod 640 .env
chown -R www-data:www-data writable uploads
chmod -R 775 writable uploads
```
