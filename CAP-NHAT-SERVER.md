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
    == 4. Toạ độ để tính khoảng cách ==
    == 5. Dọn trạng thái online giả ==
    == 6. Trạng thái lượt thích ==
    == 7. Danh mục nghề nghiệp ==
    == 8. Mã OTP qua email ==
    == 9. Hệ thống email ==
    == 10. Hồ sơ thành viên ==

Chạy lại nhiều lần vẫn an toàn: lần thứ hai trở đi mọi mục sẽ báo `0`,
nghĩa là không có gì phải làm thêm.

---

## 5. Khai báo trong `.env`

Mở tệp `.env` ở thư mục gốc và kiểm tra đủ các khoá sau.

### Bắt buộc

    APP_URL=https://saigoncupid.com/     # BỎ HẲN dòng này nếu chạy nhiều tên miền
    APP_KEY=<chuỗi ngẫu nhiên 32+ ký tự>

    DB_HOST=localhost
    DB_NAME=...
    DB_USER=...
    DB_PASS=...

**`APP_KEY`**: sinh một chuỗi riêng cho máy chủ thật, đừng dùng giá trị mặc định
trong mã nguồn. Khoá này vừa dùng để mã hoá, vừa là phần bí mật bảo vệ mã OTP —
để nguyên mặc định là ai đọc được mã nguồn cũng đoán được mã OTP.

    php -r 'echo bin2hex(random_bytes(24)), "\n";'

**`APP_URL`**: mọi đường dẫn ảnh và liên kết trong email đều sinh theo giá trị
này. Khai sai cổng hoặc sai tên miền là ảnh hỏng và link trong thư dẫn sai chỗ.
Bỏ trống thì hệ thống tự nhận theo tên miền người dùng đang truy cập.

### Gửi email (bắt buộc nếu muốn có OTP và thông báo)

    MAIL_HOST=smtp.gmail.com
    MAIL_PORT=587
    MAIL_USERNAME=...
    MAIL_PASSWORD=...
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=hello@saigoncupid.com
    MAIL_FROM_NAME="Saigon Cupid"

Thiếu nhóm này thì tiến trình gửi thư **tự dừng và giữ nguyên hàng đợi**, không
làm hỏng dữ liệu — nhưng người dùng mới sẽ không nhận được mã xác thực để đăng ký.

### Chat thời gian thực (tuỳ chọn)

    REALTIME_WS_URL=wss://.../ws
    REALTIME_SECRET=...

Bỏ trống thì khung chat vẫn chạy, chỉ là tải tin mới theo chu kỳ 4 giây thay vì
tức thời.

---

## 6. Múi giờ

Kiểm tra PHP và MySQL có cùng múi giờ không:

    php -r 'echo "PHP: ", date("Y-m-d H:i:s"), "\n";'
    mysql -u DB_USER -p TEN_DB -e "SELECT NOW();"

Hai giờ này **phải khớp nhau**. Hệ thống đã tự đồng bộ múi giờ MySQL theo PHP ở
mỗi request, nhưng nếu lệch thì các script chạy bằng cron vẫn có thể sai giờ.
Nên đặt luôn múi giờ hệ thống cho chắc:

    sudo timedatectl set-timezone Asia/Ho_Chi_Minh

---

## 7. Hẹn giờ chạy nền (cron)

### Bước 1 — tìm đường dẫn php và thư mục dự án

Cron không dùng `PATH` như lúc bạn gõ tay, nên phải ghi đường dẫn tuyệt đối:

    which php          # ví dụ: /usr/bin/php
    pwd                # ví dụ: /var/www/web-hen-ho

### Bước 2 — chạy thử bằng tay trước

Chưa vội hẹn giờ. Chạy tay một lượt xem có ra kết quả không:

    cd /var/www/web-hen-ho
    php index.php cron worker

Không in gì nghĩa là hàng đợi rỗng — bình thường. Nếu báo
*"chưa cấu hình MAIL_* trong .env"* thì quay lại mục 5.

### Bước 3 — thêm vào crontab

    crontab -e

Dán bốn dòng sau, **thay `/usr/bin/php` và `/var/www/web-hen-ho` cho đúng máy
của bạn**:

    # ===== Saigon Cupid =====
    # Gửi thư đang chờ trong hàng đợi — mỗi phút
    * * * * *  cd /var/www/web-hen-ho && /usr/bin/php index.php cron worker >> /var/log/cupid-mail.log 2>&1

    # Chốt gợi ý mỗi ngày cho từng người + đánh dấu gợi ý cũ hết hạn — 8h sáng
    0 8 * * *  cd /var/www/web-hen-ho && /usr/bin/php index.php cron goi_y_ngay >> /var/log/cupid-mail.log 2>&1

    # Email gợi ý người phù hợp — 8h05 sáng
    5 8 * * *  cd /var/www/web-hen-ho && /usr/bin/php index.php cron goi_y >> /var/log/cupid-mail.log 2>&1

    # Nhắc người đã 7 ngày không vào — 10h sáng
    0 10 * * * cd /var/www/web-hen-ho && /usr/bin/php index.php cron keo_lai >> /var/log/cupid-mail.log 2>&1

    # Gom lượt thích và lượt xem trong ngày — 20h
    0 20 * * * cd /var/www/web-hen-ho && /usr/bin/php index.php cron gom_thong_bao >> /var/log/cupid-mail.log 2>&1

    # Nhắc người sắp mất chuỗi ngày hoạt động — 20h
    0 20 * * * cd /var/www/web-hen-ho && /usr/bin/php index.php cron nhac_chuoi >> /var/log/cupid-mail.log 2>&1

    # Nhắc người mới hoàn thiện hồ sơ (sau 1 ngày và 3 ngày, tối đa 2 thư) — 9h
    0 9 * * *  cd /var/www/web-hen-ho && /usr/bin/php index.php cron nhac_ho_so >> /var/log/cupid-mail.log 2>&1

Lưu lại rồi kiểm tra đã nhận chưa:

    crontab -l

### Bước 4 — tạo tệp log và cho phép ghi

    sudo touch /var/log/cupid-mail.log
    sudo chown $(whoami) /var/log/cupid-mail.log

### Bước 5 — xem có chạy không

Đợi vài phút rồi:

    tail -f /var/log/cupid-mail.log

Mỗi lượt gửi được sẽ in ra dạng:

    21:10:02  worker: gửi 3, bỏ qua 1, hỏng 0

### Những điều cần biết

- **Bốn lệnh này chỉ chạy được từ dòng lệnh.** Gọi qua trình duyệt
  (`/cron/worker`) sẽ trả 404, không ai ép gửi thư hàng loạt được.
- **Chạy chồng đã được chặn sẵn.** `worker` hẹn mỗi phút mà SMTP có lúc chậm hơn
  một phút; hệ thống dùng khoá tệp nên lượt sau tự bỏ qua, không bao giờ gửi
  trùng thư.
- **Múi giờ của cron** là múi giờ hệ thống, không phải của PHP. Nếu máy chủ chạy
  UTC thì `0 8 * * *` là 15h giờ Việt Nam. Xem mục 6 để đặt lại.
- **Nếu dùng cPanel / DirectAdmin**: vào mục *Cron Jobs* và dán đúng phần lệnh
  (từ `cd` trở đi), phần `* * * * *` điền vào các ô thời gian riêng.

### Gỡ cron khi cần

    crontab -e      # xoá bốn dòng rồi lưu lại

### Chạy thử trước khi bật cron

Ba lệnh `goi_y`, `keo_lai`, `gom_thong_bao` chỉ **xếp thư vào hàng đợi**, chưa
gửi đi. Nên chạy tay một lần rồi vào **Quản trị → Email** xem nội dung và số
lượng trước khi bật `worker`:

    php index.php cron goi_y          # xem xếp bao nhiêu thư
    # vào /admin/emails kiểm tra, thấy ổn thì mới bật cron worker

Muốn huỷ hết thư đang chờ:

    mysql -u DB_USER -p TEN_DB -e "DELETE FROM email_queue WHERE status='pending';"

---

## 8. Kiểm tra sau khi cài

- Vào **Quản trị → Cấu hình** điền: tên công ty, mã số thuế, địa chỉ, hotline,
  email hỗ trợ, link mạng xã hội. Mấy thông tin này **bắt buộc phải có** vì
  chân mọi email đều in ra (yêu cầu của CAN-SPAM và Nghị định 13/2023/NĐ-CP).
- Thử đăng ký một tài khoản mới xem có nhận được mã OTP qua email không.
- Vào **Quản trị → Email** xem hàng đợi có chạy không.
- Nhấn `Ctrl+Shift+R` trên trình duyệt để nạp lại CSS mới.

---

## Muốn tạo thêm thành viên mẫu

    php database/update.php 30

Số `30` là số tài khoản cần tạo. Chúng có email dạng `@demo.local`,
mật khẩu `123456`, và được điền sẵn hồ sơ đầy đủ để hiện lên các trang.

Gỡ khi không cần nữa:

    mysql -u DB_USER -p TEN_DB -e "DELETE FROM users WHERE email LIKE '%@demo.local';"

> **Đừng tạo tài khoản mẫu trên máy chủ thật nếu đã bật cron gửi thư.** Hệ thống
> sẽ gửi email gợi ý ghép đôi tới các địa chỉ `@demo.local` không có thật, làm
> tăng tỉ lệ thư bị trả về và ảnh hưởng uy tín tên miền khi gửi thư.

---

## Nếu cần quay lại

    mysql -u DB_USER -p TEN_DB < ~/backup-YYYY-MM-DD-HHMM.sql

---

## Lưu ý

- **Không chạy `database/schema.sql` trên máy chủ đang có dữ liệu.** Tệp đó chứa
  lệnh `DROP TABLE` và tự trỏ vào cơ sở dữ liệu `web_hen_ho`, chạy vào là mất sạch.
  Chỉ dùng khi cài mới hoàn toàn.
- Lệnh `php` phải là bản CLI có sẵn extension `pdo_mysql`. Kiểm tra nhanh:

      php -m | grep pdo_mysql

- Thông số kết nối lấy từ tệp `.env` ở thư mục gốc dự án. Nếu báo
  *"Không kết nối được cơ sở dữ liệu"*, kiểm tra `DB_HOST`, `DB_NAME`,
  `DB_USER`, `DB_PASS` trong đó.
- Thư mục `uploads/` và `writable/` phải cho web server ghi được:

      sudo chown -R www-data:www-data uploads writable

- Tệp `.env` không được để lộ ra ngoài. Kiểm tra bằng cách mở
  `https://tên-miền/.env` — phải trả về 403 hoặc 404, không được hiện nội dung.
