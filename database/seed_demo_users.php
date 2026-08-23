<?php
/**
 * Sinh thành viên mẫu để trang chủ / trang danh sách có dữ liệu hiển thị.
 *
 * Chạy:  php database/seed_demo_users.php [số lượng]
 * Mật khẩu mọi tài khoản mẫu: 123456
 *
 * Chỉ dùng cho môi trường phát triển. Tài khoản mẫu có email dạng @demo.local
 * nên xoá lại rất dễ:  DELETE FROM users WHERE email LIKE '%@demo.local';
 */

$count = isset($argv[1]) ? max(1, (int) $argv[1]) : 20;

// Nạp .env TRƯỚC khi đọc bất kỳ thông số nào, nếu không sẽ chỉ lấy được giá trị mặc định
$env = __DIR__ . '/../.env';
if (is_readable($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $line, 2);
        if (getenv(trim($k)) === false) {
            putenv(trim($k) . '=' . trim($v, " \t\n\r\0\x0B\"'"));
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'web_hen_ho';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {

    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (PDOException $e) {
    exit("Không kết nối được cơ sở dữ liệu ($user@$host/$name).\n"
       . "Hãy kiểm tra DB_USER và DB_PASS trong tệp .env.\n");
}

$ho  = array('Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Ngô');
$dem = array('Thị', 'Văn', 'Minh', 'Ngọc', 'Thanh', 'Quang', 'Hải', 'Thu');
$ten_nu   = array('Lan', 'Hương', 'Trang', 'Mai', 'Linh', 'Ngọc', 'Thảo', 'Hà', 'Yến', 'Nhung', 'Vân', 'Quỳnh');
$ten_nam  = array('Hùng', 'Nam', 'Tuấn', 'Dũng', 'Long', 'Sơn', 'Khánh', 'Đạt', 'Phong', 'Bảo', 'Kiên', 'Hiếu');
$nghe = array('Nhân viên văn phòng', 'Kinh doanh tự do', 'Giáo viên', 'Kỹ sư', 'Kế toán',
              'Điều dưỡng', 'Công nhân', 'Lái xe', 'Bán hàng online', 'Đầu bếp');
$gioi_thieu = array(
    'Mình sống tình cảm, thích nấu ăn và đi du lịch. Mong tìm một người bạn chân thành để chia sẻ buồn vui.',
    'Tính cách hoà đồng, vui vẻ. Rảnh thì hay cà phê với bạn bè, thích xem phim và nghe nhạc nhẹ.',
    'Đã ổn định công việc, mong tìm người nghiêm túc để tiến tới lâu dài.',
    'Ít nói nhưng sống nội tâm, quan tâm người khác. Hy vọng gặp được người hiểu mình.',
    'Yêu thích thể thao và các hoạt động ngoài trời. Mong tìm người bạn đồng hành cùng sở thích.',
    'Đơn giản, dễ chịu, không thích ồn ào. Tìm bạn tâm sự và chia sẻ mỗi ngày.',
);
$hoc_van = array('thpt', 'trung_cap', 'cao_dang', 'dai_hoc', 'sau_dai_hoc');
$hon_nhan = array('doc_than', 'doc_than', 'doc_than', 'ly_hon', 'goa');

$provinces = $pdo->query("SELECT id FROM provinces")->fetchAll(PDO::FETCH_COLUMN);
$interests = $pdo->query("SELECT id FROM interests")->fetchAll(PDO::FETCH_COLUMN);
if (!$provinces) {
    exit("Chưa có dữ liệu tỉnh/thành. Hãy chạy database/seed.sql trước.\n");
}

/** Bỏ dấu tiếng Việt để tạo slug/email. */
function to_slug($text)
{
    $map = array(
        'a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'e' => 'éèẻẽẹêếềểễệ', 'i' => 'íìỉĩị',
        'o' => 'óòỏõọôốồổỗộơớờởỡợ', 'u' => 'úùủũụưứừửữự', 'y' => 'ýỳỷỹỵ', 'd' => 'đ',
    );
    $text = mb_strtolower(trim($text), 'UTF-8');
    foreach ($map as $latin => $chars) {
        $text = preg_replace('/[' . $chars . ']/u', $latin, $text);
    }
    return trim(preg_replace('/[^a-z0-9]+/u', '-', $text), '-');
}

$hash = password_hash('123456', PASSWORD_DEFAULT);
$created = 0;

for ($i = 0; $i < $count; $i++) {
    $gender = $i % 2 === 0 ? 'female' : 'male';
    $name = $ho[array_rand($ho)] . ' ' . $dem[array_rand($dem)] . ' '
          . ($gender === 'female' ? $ten_nu[array_rand($ten_nu)] : $ten_nam[array_rand($ten_nam)]);

    $suffix = substr(md5(uniqid('', true)), 0, 5);
    $slug   = to_slug($name) . '-' . $suffix;

    // Hoạt động cuối rải đều: một số đang online, số còn lại vài giờ / vài ngày trước
    $minutes_ago = $i < 5 ? rand(0, 4) : rand(30, 60 * 24 * 7);
    $last_active = date('Y-m-d H:i:s', time() - $minutes_ago * 60);
    $created_at  = date('Y-m-d H:i:s', time() - rand(1, 90) * 86400);
    $is_vip      = $i % 5 === 0 ? 1 : 0;

    $pdo->prepare("INSERT INTO users
        (uuid, email, phone, password_hash, display_name, slug, gender, birthday, province_id,
         bio, height_cm, weight_kg, job, education, marital_status, role, status,
         is_vip, vip_expired_at, coin_balance, profile_score, last_active_at, created_at)
        VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'active',
                ?, ?, ?, ?, ?, ?)")
        ->execute(array(
            'user' . $suffix . '@demo.local',
            '09' . rand(10000000, 99999999),
            $hash,
            $name,
            $slug,
            $gender,
            date('Y-m-d', strtotime('-' . rand(20, 45) . ' years -' . rand(0, 364) . ' days')),
            $provinces[array_rand($provinces)],
            $gioi_thieu[array_rand($gioi_thieu)],
            $gender === 'female' ? rand(150, 168) : rand(162, 182),
            $gender === 'female' ? rand(42, 58) : rand(55, 78),
            $nghe[array_rand($nghe)],
            $hoc_van[array_rand($hoc_van)],
            $hon_nhan[array_rand($hon_nhan)],
            $is_vip,
            $is_vip ? date('Y-m-d H:i:s', strtotime('+' . rand(10, 300) . ' days')) : null,
            rand(0, 500),
            rand(60, 100),
            $last_active,
            $created_at,
        ));

    $user_id = (int) $pdo->lastInsertId();

    // Tiêu chí ghép đôi
    $pdo->prepare("INSERT INTO user_preferences (user_id, seeking_gender, age_min, age_max, purpose)
                   VALUES (?, ?, ?, ?, ?)")
        ->execute(array(
            $user_id,
            $gender === 'female' ? 'male' : 'female',
            rand(20, 28),
            rand(35, 55),
            array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon')[rand(0, 3)],
        ));

    // Vài sở thích ngẫu nhiên
    if ($interests) {
        shuffle($interests);
        foreach (array_slice($interests, 0, rand(2, 4)) as $iid) {
            $pdo->prepare("INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (?, ?)")
                ->execute(array($user_id, $iid));
        }
    }

    $created++;
    echo "  + $name ($gender)" . ($is_vip ? ' [VIP]' : '') . "\n";
}

echo "\nĐã tạo $created thành viên mẫu. Mật khẩu: 123456\n";
echo "Xoá lại: DELETE FROM users WHERE email LIKE '%@demo.local';\n";
