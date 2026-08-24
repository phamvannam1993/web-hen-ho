<?php
/**
 * Điền các trường hồ sơ còn trống cho thành viên đang có, để bộ lọc tìm kiếm
 * (chiều cao, hôn nhân, học vấn, con cái, hút thuốc, uống rượu, sở thích)
 * có dữ liệu mà lọc ra kết quả.
 *
 * Chạy:  php database/fill_member_profiles.php
 *
 * Chỉ ghi vào ô đang trống, không đè lên dữ liệu người dùng đã tự nhập.
 */

$env = __DIR__ . '/../.env';
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
    exit("Không kết nối được cơ sở dữ liệu. Kiểm tra DB_USER / DB_PASS trong .env\n");
}

$nghe      = array('Nhân viên văn phòng', 'Kinh doanh tự do', 'Giáo viên', 'Kỹ sư', 'Kế toán',
                   'Điều dưỡng', 'Công nhân', 'Lái xe', 'Bán hàng online', 'Đầu bếp',
                   'Thiết kế đồ hoạ', 'Lập trình viên', 'Nhân viên ngân hàng', 'Bác sĩ');
$hoc_van   = array('thpt', 'trung_cap', 'cao_dang', 'dai_hoc', 'dai_hoc', 'sau_dai_hoc');
$hon_nhan  = array('doc_than', 'doc_than', 'doc_than', 'ly_hon', 'goa');
$muc_do    = array('khong', 'khong', 'thinh_thoang', 'thuong_xuyen');
$gioi_thieu = array(
    'Mình sống tình cảm, thích nấu ăn và đi du lịch. Mong tìm một người bạn chân thành để chia sẻ buồn vui.',
    'Tính cách hoà đồng, vui vẻ. Rảnh thì hay cà phê với bạn bè, thích xem phim và nghe nhạc nhẹ.',
    'Đã ổn định công việc, mong tìm người nghiêm túc để tiến tới lâu dài.',
    'Ít nói nhưng sống nội tâm, quan tâm người khác. Hy vọng gặp được người hiểu mình.',
    'Yêu thích thể thao và các hoạt động ngoài trời. Mong tìm người bạn đồng hành cùng sở thích.',
);

$users     = $pdo->query("SELECT * FROM users WHERE role = 'member' AND deleted_at IS NULL")->fetchAll();
$interests = $pdo->query("SELECT id FROM interests")->fetchAll(PDO::FETCH_COLUMN);
$provinces = $pdo->query("SELECT id FROM provinces")->fetchAll(PDO::FETCH_COLUMN);

$updated = $added_interest = 0;

foreach ($users as $u) {
    $set = array();
    $nu  = $u['gender'] === 'female';

    if ($u['height_cm'] === null)      $set['height_cm']      = $nu ? rand(150, 168) : rand(162, 182);
    if ($u['weight_kg'] === null)      $set['weight_kg']      = $nu ? rand(42, 58)  : rand(55, 78);
    if (!$u['job'])                    $set['job']            = $nghe[array_rand($nghe)];
    if ($u['education'] === null)      $set['education']      = $hoc_van[array_rand($hoc_van)];
    if ($u['marital_status'] === null) $set['marital_status'] = $hon_nhan[array_rand($hon_nhan)];
    if ($u['smoking'] === null)        $set['smoking']        = $nu ? 'khong' : $muc_do[array_rand($muc_do)];
    if ($u['drinking'] === null)       $set['drinking']       = $muc_do[array_rand($muc_do)];
    // has_children mặc định là 0 nên không thể dựa vào NULL để biết đã khai hay chưa.
    // Với tài khoản mẫu thì gán ngẫu nhiên để bộ lọc có cả hai nhóm mà thử.
    if (strpos($u['email'], '@demo.local') !== false && (int) $u['has_children'] === 0) {
        $set['has_children'] = rand(0, 3) === 0 ? 1 : 0;
    }
    if (!$u['bio'])                    $set['bio']            = $gioi_thieu[array_rand($gioi_thieu)];
    if ($u['province_id'] === null && $provinces) {
        $set['province_id'] = $provinces[array_rand($provinces)];
    }
    if ($u['birthday'] === null) {
        $set['birthday'] = date('Y-m-d', strtotime('-' . rand(22, 45) . ' years -' . rand(0, 364) . ' days'));
    }

    if ($set) {
        $cols = array();
        foreach ($set as $k => $v) { $cols[] = "`$k` = ?"; }
        $st = $pdo->prepare("UPDATE users SET " . implode(', ', $cols) . " WHERE id = ?");
        $st->execute(array_merge(array_values($set), array($u['id'])));
        $updated++;
        echo "  cập nhật {$u['display_name']}: " . implode(', ', array_keys($set)) . "\n";
    }

    // Ai chưa có sở thích thì gán 2-4 cái ngẫu nhiên
    $has = (int) $pdo->query("SELECT COUNT(*) FROM user_interests WHERE user_id = " . (int) $u['id'])->fetchColumn();
    if ($has === 0 && $interests) {
        shuffle($interests);
        foreach (array_slice($interests, 0, rand(2, 4)) as $iid) {
            $pdo->prepare("INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (?, ?)")
                ->execute(array($u['id'], $iid));
        }
        $added_interest++;
    }

    // Thiếu tiêu chí tìm kiếm thì tạo mặc định, nếu không sẽ không được gợi ý cho ai
    $pref = (int) $pdo->query("SELECT COUNT(*) FROM user_preferences WHERE user_id = " . (int) $u['id'])->fetchColumn();
    if ($pref === 0) {
        $pdo->prepare("INSERT INTO user_preferences (user_id, seeking_gender, age_min, age_max, purpose)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute(array($u['id'], $nu ? 'male' : 'female', rand(20, 28), rand(35, 55),
                            array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon')[rand(0, 3)]));
    }
}

echo "\nĐã bổ sung hồ sơ cho $updated thành viên, gán sở thích cho $added_interest người.\n";
