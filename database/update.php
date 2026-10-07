<?php
/**
 * Cập nhật dữ liệu cho bản đã cài sẵn (chạy trên máy chủ sau khi kéo code mới).
 *
 *   php database/update.php
 *
 * Gồm ba việc, đều chạy lại được nhiều lần mà không hỏng gì:
 *   1. Thêm các khoá cấu hình mới (thông tin công ty, mạng xã hội, công tắc đăng tin)
 *   2. Cập nhật danh mục tỉnh/thành theo 34 đơn vị hành chính hiện hành
 *   3. Thêm cột dữ liệu mới (chủ đề tâm sự)
 *   4. Toạ độ tỉnh/thành và vị trí thành viên, để tính khoảng cách
 *   5. Dọn trạng thái "đang online" giả của tài khoản mẫu
 *   6. Trạng thái lượt thích (chờ trả lời / ghép đôi / bị bỏ qua) cho luật chat mới
 *   7. Danh mục nghề nghiệp cho ô chọn nghề trong hồ sơ
 *   8. Mã OTP gửi qua email khi đăng ký / đăng nhập
 *   9. Hệ thống email: cài đặt của từng người + hàng đợi gửi thư
 *  10. Điền các trường hồ sơ còn trống để bộ lọc tìm kiếm có dữ liệu mà lọc
 *  11. (tuỳ chọn) Sinh thêm thành viên mẫu:  php database/update.php 30
 *
 * Thành viên mẫu có email dạng @demo.local nên gỡ lại rất dễ:
 *   DELETE FROM users WHERE email LIKE '%@demo.local';
 *
 * KHÔNG đụng tới cấu trúc bảng và không xoá dữ liệu người dùng.
 * Nên sao lưu trước:  mysqldump -u USER -p TEN_DB > backup.sql
 */

$root = __DIR__ . '/..';
$demo = isset($argv[1]) ? max(0, (int) $argv[1]) : 0;   // số thành viên mẫu cần thêm

// Nạp .env để lấy thông số kết nối
$env = $root . '/.env';
if (is_readable($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        if (getenv(trim($k)) === false) putenv(trim($k) . '=' . trim($v, " \t\n\r\0\x0B\"'"));
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: 'localhost')
            . ';dbname=' . (getenv('DB_NAME') ?: 'web_hen_ho') . ';charset=utf8mb4',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
    );
} catch (PDOException $e) {
    exit("Không kết nối được cơ sở dữ liệu. Kiểm tra DB_* trong .env\n");
}

echo "== 1. Khoá cấu hình mới ==\n";
$pdo->exec(file_get_contents(__DIR__ . '/interest_seen.sql'));
$settings = array(
    // key                 giá trị mặc định            nhóm
    array('enable_posts',  '0',                        'moderation'),
    array('otp_register',  '1',                        'security'),
    array('only_online',   '0',                        'moderation'),
    array('company_name',  'CÔNG TY TNHH SAIGON CUPID', 'company'),
    array('tax_code',      '',                         'company'),
    array('address',       '',                         'company'),
    array('zalo',          '',                         'contact'),
    array('facebook_url',  '',                         'social'),
    array('youtube_url',   '',                         'social'),
    array('tiktok_url',    '',                         'social'),
    array('instagram_url', '',                         'social'),
);
$added = 0;
foreach ($settings as $row) {
    list($key, $value, $group) = $row;
    $st = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
    $st->execute(array($key));
    if ((int) $st->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, ?)")
            ->execute(array($key, $value, $group));
        echo "  + $key\n";
        $added++;
    }
}
echo $added ? "  Đã thêm $added khoá.\n" : "  Đã có đủ, không thêm gì.\n";

echo "\n== 2. Danh mục tỉnh/thành ==\n";
require $root . '/database/migrate_provinces_2025.php';

echo "\n== 3. Cột dữ liệu mới ==\n";
// Chủ đề tâm sự, phục vụ trang /tam-su
$co = $pdo->query("SHOW COLUMNS FROM users LIKE 'confide_topic'")->fetch();
if (!$co) {
    $pdo->exec("ALTER TABLE users
        ADD COLUMN confide_topic ENUM('lang_nghe','tro_chuyen','cong_viec','gia_dinh','tinh_cam','dem_khuya')
        NULL COMMENT 'Chủ đề tâm sự mong muốn' AFTER bio");
    echo "  + thêm cột confide_topic\n";
} else {
    echo "  Đã có cột confide_topic.\n";
}
// Gán chủ đề cho hồ sơ còn trống để trang Tâm sự có nội dung
$st = $pdo->prepare("UPDATE users SET confide_topic = ELT(1 + FLOOR(RAND()*6),
        'lang_nghe','tro_chuyen','cong_viec','gia_dinh','tinh_cam','dem_khuya')
      WHERE role = 'member' AND confide_topic IS NULL");
$st->execute();
echo '  Gán chủ đề cho ' . $st->rowCount() . " hồ sơ.\n";

echo "\n== 4. Toạ độ để tính khoảng cách ==\n";
// Dùng cho dòng "Cách bạn X km" ở trang Khám phá
foreach (array('provinces' => array('lat', 'lng'), 'users' => array('lat', 'lng')) as $bang => $cot) {
    foreach ($cot as $c) {
        $co = $pdo->query("SHOW COLUMNS FROM `$bang` LIKE '$c'")->fetch();
        if (!$co) {
            $pdo->exec("ALTER TABLE `$bang` ADD COLUMN `$c` DECIMAL(9,6) NULL");
            echo "  + thêm cột $bang.$c\n";
        }
    }
}

if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'location_updated_at'")->fetch()) {
    $pdo->exec("ALTER TABLE users ADD COLUMN location_updated_at DATETIME NULL COMMENT 'Thời điểm người dùng cấp vị trí trình duyệt'");
}

// Toạ độ trung tâm 34 tỉnh/thành
$toa_do = require $root . '/application/config/province_coordinates.php';
$n = 0;
foreach ($toa_do as $slug => $ll) {
    $st = $pdo->prepare("UPDATE provinces SET lat = ?, lng = ? WHERE slug = ? AND lat IS NULL");
    $st->execute(array($ll[0], $ll[1], $slug));
    $n += $st->rowCount();
}
echo "  Gán toạ độ cho $n tỉnh/thành.\n";

// Không tạo vị trí giả cho thành viên; khoảng cách ước tính dùng trực tiếp tỉnh/thành.

echo "\n== 5. Dọn trạng thái online giả ==\n";
// Tài khoản mẫu từng được sinh với thời điểm hoạt động sát giờ tạo nên luôn
// hiện nhãn ONLINE dù không ai dùng. Đẩy chúng về quá khứ; tài khoản thật
// không bị đụng tới.
$st = $pdo->prepare("UPDATE users SET last_active_at = NOW() - INTERVAL (30 + FLOOR(RAND()*10000)) MINUTE
                      WHERE email LIKE '%@demo.local'
                        AND last_active_at > NOW() - INTERVAL 5 MINUTE");
$st->execute();
echo '  Đã chỉnh ' . $st->rowCount() . " tài khoản mẫu.\n";

echo "\n== 6. Trạng thái lượt thích ==\n";
// Chat chỉ mở khi hai bên đã ghép đôi, nên mỗi lượt thích cần biết mình đang
// ở trạng thái nào: chờ người kia trả lời, đã thành ghép đôi, hay bị bỏ qua.
$co = $pdo->query("SHOW COLUMNS FROM likes LIKE 'status'")->fetch();
if (!$co) {
    $pdo->exec("ALTER TABLE likes
        ADD COLUMN `status` ENUM('pending','matched','rejected') NOT NULL DEFAULT 'pending'
            COMMENT 'chỉ dùng cho target_type=user' AFTER target_id,
        ADD KEY `idx_like_status` (target_type, target_id, `status`)");
    echo "  + thêm cột status cho bảng likes\n";
} else {
    echo "  Đã có cột status.\n";
}

// Hai bên cùng thích nhau nhưng chưa có bản ghi matches (dữ liệu cũ) thì tạo bù.
$st = $pdo->prepare("INSERT IGNORE INTO matches (user_low_id, user_high_id)
        SELECT DISTINCT LEAST(a.user_id, a.target_id), GREATEST(a.user_id, a.target_id)
          FROM likes a
          JOIN likes b ON b.user_id = a.target_id AND b.target_id = a.user_id
                      AND b.target_type = 'user'
         WHERE a.target_type = 'user'
           AND a.status <> 'rejected' AND b.status <> 'rejected'");
$st->execute();
echo '  Tạo bù ' . $st->rowCount() . " ghép đôi còn thiếu.\n";

// Lượt thích thuộc một cặp đã ghép đôi phải mang trạng thái 'matched' để không
// lọt vào danh sách "Người thích bạn" đang chờ trả lời.
$st = $pdo->prepare("UPDATE likes l
        JOIN matches m ON m.user_low_id = LEAST(l.user_id, l.target_id)
                      AND m.user_high_id = GREATEST(l.user_id, l.target_id)
           SET l.status = 'matched'
         WHERE l.target_type = 'user' AND l.status <> 'matched'");
$st->execute();
echo '  Đánh dấu ' . $st->rowCount() . " lượt thích đã ghép đôi.\n";

echo "\n== 7. Danh mục nghề nghiệp ==\n";
// Ô "Nghề nghiệp" trong hồ sơ đổi từ gõ tay sang chọn trong danh mục này.
$pdo->exec("CREATE TABLE IF NOT EXISTS `jobs` (
  `id`        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(120) NOT NULL,
  `sort`      SMALLINT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jobs_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$nghe = require $root . '/database/jobs_list.php';
$st = $pdo->prepare("INSERT IGNORE INTO jobs (name) VALUES (?)");
$them = 0;
foreach ($nghe as $ten) {
    $st->execute(array($ten));
    $them += $st->rowCount();
}
echo '  Thêm ' . $them . ' nghề mới (danh mục có ' . count($nghe) . " mục).\n";

// Nghề cũ người dùng đã gõ tay mà chưa có trong danh mục thì đưa vào luôn,
// nếu không họ mở hồ sơ ra sẽ thấy ô nghề nghiệp trống trơn.
$st = $pdo->prepare("INSERT IGNORE INTO jobs (name)
        SELECT DISTINCT TRIM(job) FROM users
         WHERE job IS NOT NULL AND TRIM(job) <> ''");
$st->execute();
echo '  Giữ lại ' . $st->rowCount() . " nghề người dùng đã tự nhập.\n";

echo "\n== 8. Mã OTP qua email ==\n";
// Mỗi mã OTP đếm riêng số lần nhập sai để khoá lại sau vài lần đoán bừa.
$co = $pdo->query("SHOW COLUMNS FROM user_tokens LIKE 'attempts'")->fetch();
if (!$co) {
    $pdo->exec("ALTER TABLE user_tokens
        ADD COLUMN `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0
            COMMENT 'số lần nhập sai mã OTP' AFTER expires_at");
    echo "  + thêm cột attempts cho bảng user_tokens\n";
} else {
    echo "  Đã có cột attempts.\n";
}

// Bảng cũ có thể chưa có 'otp' trong danh sách loại token
$cot = $pdo->query("SHOW COLUMNS FROM user_tokens LIKE 'type'")->fetch();
if ($cot && strpos($cot['Type'], "'otp'") === false) {
    $pdo->exec("ALTER TABLE user_tokens
        MODIFY `type` ENUM('verify_email','reset_password','remember','otp') NOT NULL");
    echo "  + bổ sung loại token 'otp'\n";
} else {
    echo "  Loại token 'otp' đã sẵn sàng.\n";
}

// Tài khoản đã tồn tại từ trước khi có tính năng OTP thì coi như đã xác thực.
// Không làm bước này thì bật OTP lên là toàn bộ người dùng cũ bị đá ra màn
// nhập mã, mà nhiều người trong số đó đăng ký bằng email không có thật.
$st = $pdo->prepare("UPDATE users
        SET email_verified_at = COALESCE(created_at, NOW())
      WHERE email_verified_at IS NULL
        AND email IS NOT NULL AND email <> ''
        AND deleted_at IS NULL");
$st->execute();
echo '  Ân xá ' . $st->rowCount() . " tài khoản cũ (coi như đã xác thực email).\n";

echo "\n== 9. Hệ thống email ==\n";
$pdo->exec("CREATE TABLE IF NOT EXISTS `email_prefs` (
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `welcome`       TINYINT(1) NOT NULL DEFAULT 1,
  `new_message`   TINYINT(1) NOT NULL DEFAULT 1,
  `notification`  TINYINT(1) NOT NULL DEFAULT 1,
  `match_suggest` TINYINT(1) NOT NULL DEFAULT 1,
  `re_engage`     TINYINT(1) NOT NULL DEFAULT 1,
  `match_every_days` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `token`         CHAR(40) NOT NULL,
  `bounce_count`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `disabled_at`   DATETIME DEFAULT NULL,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_email_prefs_token` (`token`),
  CONSTRAINT `fk_email_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `type`       VARCHAR(40) NOT NULL,
  `to_email`   VARCHAR(190) NOT NULL,
  `subject`    VARCHAR(255) NOT NULL,
  `view`       VARCHAR(60) NOT NULL,
  `payload`    TEXT DEFAULT NULL,
  `related_id` BIGINT UNSIGNED DEFAULT NULL,
  `send_after` DATETIME NOT NULL,
  `status`     ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error`      VARCHAR(255) DEFAULT NULL,
  `sent_at`    DATETIME DEFAULT NULL,
  `opened_at`  DATETIME DEFAULT NULL,
  `clicked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_due` (`status`,`send_after`),
  KEY `idx_email_user_type` (`user_id`,`type`,`status`,`sent_at`),
  KEY `idx_email_related` (`type`,`related_id`,`sent_at`),
  CONSTRAINT `fk_email_queue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  Đã có hai bảng email_prefs và email_queue.\n";

// Ai chưa có dòng cài đặt thì tạo sẵn, kèm mã huỷ đăng ký riêng
$st = $pdo->prepare("INSERT IGNORE INTO email_prefs (user_id, token)
        SELECT u.id, LEFT(SHA2(CONCAT(u.id, '-', u.uuid, '-', RAND()), 256), 40)
          FROM users u WHERE u.deleted_at IS NULL");
$st->execute();
echo '  Tạo cài đặt email cho ' . $st->rowCount() . " thành viên.\n";

// Nhánh A/B của tiêu đề thư, để so tỉ lệ mở giữa hai cách viết
$co = $pdo->query("SHOW COLUMNS FROM email_queue LIKE 'variant'")->fetch();
if (!$co) {
    $pdo->exec("ALTER TABLE email_queue
        ADD COLUMN `variant` CHAR(1) DEFAULT NULL COMMENT 'A hoặc B, NULL nếu loại thư không thử nghiệm'
        AFTER `subject`");
    echo "  + thêm cột variant cho email_queue\n";
} else {
    echo "  Đã có cột variant.\n";
}

// Ghi ai đã xem hồ sơ ai, để gom thành thông báo "N người đã xem hồ sơ bạn"
$pdo->exec("CREATE TABLE IF NOT EXISTS `profile_views` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `viewer_id`  BIGINT UNSIGNED NOT NULL,
  `owner_id`   BIGINT UNSIGNED NOT NULL,
  `viewed_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_view_pair` (`viewer_id`,`owner_id`),
  KEY `idx_view_owner` (`owner_id`,`viewed_at`),
  CONSTRAINT `fk_view_viewer` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_view_owner`  FOREIGN KEY (`owner_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  Đã có bảng profile_views.\n";

echo "\n== 10. Gợi ý mỗi ngày, chuỗi hoạt động ==\n";
$pdo->exec("CREATE TABLE IF NOT EXISTS `daily_matches` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       BIGINT UNSIGNED NOT NULL,
  `match_user_id` BIGINT UNSIGNED NOT NULL,
  `match_date`    DATE NOT NULL,
  `status`        ENUM('pending','liked','skipped','expired','matched') NOT NULL DEFAULT 'pending',
  `score`         SMALLINT NOT NULL DEFAULT 0,
  `expires_at`    DATETIME NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_user_date` (`user_id`,`match_date`),
  KEY `idx_daily_user` (`user_id`,`match_date`),
  CONSTRAINT `fk_daily_user`  FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_daily_match` FOREIGN KEY (`match_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `user_streaks` (
  `user_id`             BIGINT UNSIGNED NOT NULL,
  `current_streak`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `longest_streak`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `last_active_date`    DATE DEFAULT NULL,
  `streak_freeze_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_streak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `streak_badges` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `badge_code`  VARCHAR(50) NOT NULL,
  `achieved_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_badge` (`user_id`,`badge_code`),
  CONSTRAINT `fk_badge_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$co = $pdo->query("SHOW COLUMNS FROM profile_views LIKE 'view_count'")->fetch();
if (!$co) {
    $pdo->exec("ALTER TABLE profile_views ADD COLUMN `view_count` INT UNSIGNED NOT NULL DEFAULT 1 AFTER viewed_at");
    echo "  + thêm cột view_count\n";
}
echo "  Đã có ba bảng daily_matches, user_streaks, streak_badges.\n";

// Token cho ứng dụng di động gọi API. Tách khỏi user_tokens vì cần thêm
// thông tin thiết bị và thời điểm dùng gần nhất để thu hồi khi mất máy.
$pdo->exec("CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED NOT NULL,
  `token_hash`   CHAR(64) NOT NULL COMMENT 'chỉ lưu bản băm, không lưu token gốc',
  `device`       VARCHAR(120) DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `expires_at`   DATETIME NOT NULL,
  `revoked_at`   DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_token` (`token_hash`),
  KEY `idx_api_user` (`user_id`,`revoked_at`),
  CONSTRAINT `fk_api_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  Đã có bảng api_tokens.\n";

echo "\n== 11. Hồ sơ thành viên ==\n";
require $root . '/database/fill_member_profiles.php';

if ($demo > 0) {
    echo "\n== 12. Thành viên mẫu ==\n";
    // seed_demo_users.php đọc số lượng từ $argv[1] nên truyền thẳng tham số qua
    $argv[1] = $demo;
    require $root . '/database/seed_demo_users.php';

    // Hồ sơ vừa tạo cũng cần đủ trường để lọc ra được
    echo "\n== Bổ sung hồ sơ cho thành viên mẫu vừa tạo ==\n";
    require $root . '/database/fill_member_profiles.php';
} else {
    echo "\n(Bỏ qua bước thêm thành viên mẫu. Muốn thêm thì chạy: php database/update.php 30)\n";
}

require __DIR__ . '/migrate_notification_actors.php';
echo "\nHoàn tất. Nhớ xoá cache trình duyệt (Ctrl+Shift+R) để nạp lại CSS mới.\n";
