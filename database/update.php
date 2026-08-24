<?php
/**
 * Cập nhật dữ liệu cho bản đã cài sẵn (chạy trên máy chủ sau khi kéo code mới).
 *
 *   php database/update.php
 *
 * Gồm ba việc, đều chạy lại được nhiều lần mà không hỏng gì:
 *   1. Thêm các khoá cấu hình mới (thông tin công ty, mạng xã hội, công tắc đăng tin)
 *   2. Cập nhật danh mục tỉnh/thành theo 34 đơn vị hành chính hiện hành
 *   3. Dọn trạng thái "đang online" giả của tài khoản mẫu
 *   4. Điền các trường hồ sơ còn trống để bộ lọc tìm kiếm có dữ liệu mà lọc
 *   5. (tuỳ chọn) Sinh thêm thành viên mẫu:  php database/update.php 30
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

echo "\n== 3. Dọn trạng thái online giả ==\n";
// Tài khoản mẫu từng được sinh với thời điểm hoạt động sát giờ tạo nên luôn
// hiện nhãn ONLINE dù không ai dùng. Đẩy chúng về quá khứ; tài khoản thật
// không bị đụng tới.
$st = $pdo->prepare("UPDATE users SET last_active_at = NOW() - INTERVAL (30 + FLOOR(RAND()*10000)) MINUTE
                      WHERE email LIKE '%@demo.local'
                        AND last_active_at > NOW() - INTERVAL 5 MINUTE");
$st->execute();
echo '  Đã chỉnh ' . $st->rowCount() . " tài khoản mẫu.\n";

echo "\n== 4. Hồ sơ thành viên ==\n";
require $root . '/database/fill_member_profiles.php';

if ($demo > 0) {
    echo "\n== 5. Thành viên mẫu ==\n";
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
