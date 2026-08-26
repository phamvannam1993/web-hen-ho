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
 *   6. Điền các trường hồ sơ còn trống để bộ lọc tìm kiếm có dữ liệu mà lọc
 *   7. (tuỳ chọn) Sinh thêm thành viên mẫu:  php database/update.php 30
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
$settings = array(
    // key                 giá trị mặc định            nhóm
    array('enable_posts',  '0',                        'moderation'),
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

// Toạ độ trung tâm 34 tỉnh/thành
$toa_do = array(
    'ha-noi' => array(21.028511, 105.804817), 'tp-ho-chi-minh' => array(10.762622, 106.660172),
    'hai-phong' => array(20.844912, 106.688084), 'da-nang' => array(16.047079, 108.206230),
    'hue' => array(16.463713, 107.590866), 'can-tho' => array(10.045162, 105.746857),
    'quang-ninh' => array(20.960670, 107.042490), 'bac-ninh' => array(21.186100, 106.076500),
    'hung-yen' => array(20.646200, 106.051700), 'ninh-binh' => array(20.250600, 105.974400),
    'phu-tho' => array(21.322700, 105.402400), 'thai-nguyen' => array(21.567900, 105.825300),
    'lao-cai' => array(22.485700, 103.975000), 'tuyen-quang' => array(21.823300, 105.214200),
    'cao-bang' => array(22.665600, 106.257800), 'lang-son' => array(21.853700, 106.761300),
    'son-la' => array(21.327300, 103.914100), 'dien-bien' => array(21.386000, 103.017600),
    'lai-chau' => array(22.396900, 103.458400), 'thanh-hoa' => array(19.806800, 105.776800),
    'nghe-an' => array(18.679600, 105.681400), 'ha-tinh' => array(18.342800, 105.905700),
    'quang-tri' => array(16.816600, 107.100500), 'quang-ngai' => array(15.120100, 108.792500),
    'gia-lai' => array(13.983400, 108.004900), 'dak-lak' => array(12.710000, 108.237800),
    'khanh-hoa' => array(12.238800, 109.196300), 'lam-dong' => array(11.940400, 108.458300),
    'dong-nai' => array(10.945500, 106.824200), 'tay-ninh' => array(11.310200, 106.098900),
    'vinh-long' => array(10.253700, 105.972200), 'dong-thap' => array(10.493900, 105.688200),
    'an-giang' => array(10.521200, 105.125900), 'ca-mau' => array(9.176800, 105.150400),
);
$n = 0;
foreach ($toa_do as $slug => $ll) {
    $st = $pdo->prepare("UPDATE provinces SET lat = ?, lng = ? WHERE slug = ? AND lat IS NULL");
    $st->execute(array($ll[0], $ll[1], $slug));
    $n += $st->rowCount();
}
echo "  Gán toạ độ cho $n tỉnh/thành.\n";

// Thành viên chưa có vị trí: lấy theo tỉnh, lệch ngẫu nhiên trong phạm vi tỉnh
$st = $pdo->prepare("UPDATE users u JOIN provinces p ON p.id = u.province_id
                        SET u.lat = p.lat + (RAND() - 0.5) * 0.35,
                            u.lng = p.lng + (RAND() - 0.5) * 0.35
                      WHERE u.lat IS NULL AND p.lat IS NOT NULL");
$st->execute();
echo '  Gán vị trí cho ' . $st->rowCount() . " thành viên.\n";

echo "\n== 5. Dọn trạng thái online giả ==\n";
// Tài khoản mẫu từng được sinh với thời điểm hoạt động sát giờ tạo nên luôn
// hiện nhãn ONLINE dù không ai dùng. Đẩy chúng về quá khứ; tài khoản thật
// không bị đụng tới.
$st = $pdo->prepare("UPDATE users SET last_active_at = NOW() - INTERVAL (30 + FLOOR(RAND()*10000)) MINUTE
                      WHERE email LIKE '%@demo.local'
                        AND last_active_at > NOW() - INTERVAL 5 MINUTE");
$st->execute();
echo '  Đã chỉnh ' . $st->rowCount() . " tài khoản mẫu.\n";

echo "\n== 6. Hồ sơ thành viên ==\n";
require $root . '/database/fill_member_profiles.php';

if ($demo > 0) {
    echo "\n== 7. Thành viên mẫu ==\n";
    // seed_demo_users.php đọc số lượng từ $argv[1] nên truyền thẳng tham số qua
    $argv[1] = $demo;
    require $root . '/database/seed_demo_users.php';

    // Hồ sơ vừa tạo cũng cần đủ trường để lọc ra được
    echo "\n== Bổ sung hồ sơ cho thành viên mẫu vừa tạo ==\n";
    require $root . '/database/fill_member_profiles.php';
} else {
    echo "\n(Bỏ qua bước thêm thành viên mẫu. Muốn thêm thì chạy: php database/update.php 30)\n";
}

echo "\nHoàn tất. Nhớ xoá cache trình duyệt (Ctrl+Shift+R) để nạp lại CSS mới.\n";
