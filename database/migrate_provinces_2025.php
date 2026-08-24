<?php
/**
 * Cập nhật danh mục tỉnh/thành theo sắp xếp hành chính có hiệu lực 01/07/2025:
 * 6 thành phố trực thuộc trung ương + 28 tỉnh = 34 đơn vị.
 *
 * Chạy:  php database/migrate_provinces_2025.php
 *
 * Kịch bản chạy lại được nhiều lần. Tỉnh cũ đã bị sáp nhập sẽ được chuyển
 * thành viên / tin đăng / tiêu chí tìm kiếm sang tỉnh kế thừa rồi mới xoá,
 * nên không có bản ghi nào bị mất khu vực.
 */

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

/** 34 đơn vị hành chính hiện hành: tên => array(slug, miền). Thứ tự trong mảng là thứ tự hiển thị. */
$provinces = array(
    // Thành phố trực thuộc trung ương xếp trước cho dễ chọn
    'Hà Nội'          => array('ha-noi', 'bac'),
    'TP Hồ Chí Minh'  => array('tp-ho-chi-minh', 'nam'),
    'Hải Phòng'       => array('hai-phong', 'bac'),
    'Đà Nẵng'         => array('da-nang', 'trung'),
    'Huế'             => array('hue', 'trung'),
    'Cần Thơ'         => array('can-tho', 'nam'),
    // Miền Bắc
    'Quảng Ninh'      => array('quang-ninh', 'bac'),
    'Bắc Ninh'        => array('bac-ninh', 'bac'),
    'Hưng Yên'        => array('hung-yen', 'bac'),
    'Ninh Bình'       => array('ninh-binh', 'bac'),
    'Phú Thọ'         => array('phu-tho', 'bac'),
    'Thái Nguyên'     => array('thai-nguyen', 'bac'),
    'Lào Cai'         => array('lao-cai', 'bac'),
    'Tuyên Quang'     => array('tuyen-quang', 'bac'),
    'Cao Bằng'        => array('cao-bang', 'bac'),
    'Lạng Sơn'        => array('lang-son', 'bac'),
    'Sơn La'          => array('son-la', 'bac'),
    'Điện Biên'       => array('dien-bien', 'bac'),
    'Lai Châu'        => array('lai-chau', 'bac'),
    // Miền Trung
    'Thanh Hóa'       => array('thanh-hoa', 'trung'),
    'Nghệ An'         => array('nghe-an', 'trung'),
    'Hà Tĩnh'         => array('ha-tinh', 'trung'),
    'Quảng Trị'       => array('quang-tri', 'trung'),
    'Quảng Ngãi'      => array('quang-ngai', 'trung'),
    'Gia Lai'         => array('gia-lai', 'trung'),
    'Đắk Lắk'         => array('dak-lak', 'trung'),
    'Khánh Hòa'       => array('khanh-hoa', 'trung'),
    'Lâm Đồng'        => array('lam-dong', 'trung'),
    // Miền Nam
    'Đồng Nai'        => array('dong-nai', 'nam'),
    'Tây Ninh'        => array('tay-ninh', 'nam'),
    'Vĩnh Long'       => array('vinh-long', 'nam'),
    'Đồng Tháp'       => array('dong-thap', 'nam'),
    'An Giang'        => array('an-giang', 'nam'),
    'Cà Mau'          => array('ca-mau', 'nam'),
);

/**
 * Tỉnh cũ (trước 01/07/2025) => tỉnh kế thừa sau sáp nhập.
 * Dùng để chuyển dữ liệu đang trỏ vào tỉnh cũ, và để nhận diện tỉnh cần xoá.
 */
$merged = array(
    'ba-ria-vung-tau' => 'tp-ho-chi-minh',
    'binh-duong'      => 'tp-ho-chi-minh',
    'thua-thien-hue'  => 'hue',
    'long-an'         => 'tay-ninh',
    'tien-giang'      => 'dong-thap',
    'kien-giang'      => 'an-giang',
    'ha-giang'        => 'tuyen-quang',
    'yen-bai'         => 'lao-cai',
    'bac-kan'         => 'thai-nguyen',
    'vinh-phuc'       => 'phu-tho',
    'hoa-binh'        => 'phu-tho',
    'bac-giang'       => 'bac-ninh',
    'thai-binh'       => 'hung-yen',
    'ha-nam'          => 'ninh-binh',
    'nam-dinh'        => 'ninh-binh',
    'quang-binh'      => 'quang-tri',
    'kon-tum'         => 'quang-ngai',
    'binh-dinh'       => 'gia-lai',
    'ninh-thuan'      => 'khanh-hoa',
    'dak-nong'        => 'lam-dong',
    'binh-thuan'      => 'lam-dong',
    'phu-yen'         => 'dak-lak',
    'binh-phuoc'      => 'dong-nai',
    'ben-tre'         => 'vinh-long',
    'tra-vinh'        => 'vinh-long',
    'bac-lieu'        => 'ca-mau',
    'soc-trang'       => 'can-tho',
    'hau-giang'       => 'can-tho',
    'quang-nam'       => 'da-nang',
);

$pdo->beginTransaction();
try {
    // Bước 1: cập nhật / thêm 34 đơn vị hiện hành. Giữ nguyên id của tỉnh đã có
    // để không phải đụng tới dữ liệu người dùng.
    $by_slug = array();
    foreach ($pdo->query("SELECT id, name, slug FROM provinces") as $r) {
        $by_slug[$r['slug']] = (int) $r['id'];
    }

    $sort = 0;
    $added = $renamed = 0;
    foreach ($provinces as $name => $info) {
        list($slug, $region) = $info;
        $sort += 10;

        // Thừa Thiên Huế đổi tên thành Huế: tìm theo slug cũ để giữ nguyên id
        $id = null;
        if (isset($by_slug[$slug])) {
            $id = $by_slug[$slug];
        } else {
            $old = array_search($slug, $merged, true);   // slug cũ trỏ tới chính tỉnh này
            foreach (array_keys($merged, $slug, true) as $old_slug) {
                // Chỉ đổi tên khi tỉnh mới trùng tên gốc (Thừa Thiên Huế -> Huế),
                // các trường hợp sáp nhập thật sự để bước 2 xử lý
                if (isset($by_slug[$old_slug]) && $old_slug === 'thua-thien-hue') {
                    $id = $by_slug[$old_slug];
                    $renamed++;
                    break;
                }
            }
        }

        if ($id) {
            $pdo->prepare("UPDATE provinces SET name = ?, slug = ?, region = ?, sort = ? WHERE id = ?")
                ->execute(array($name, $slug, $region, $sort, $id));
        } else {
            $pdo->prepare("INSERT INTO provinces (name, slug, region, sort) VALUES (?, ?, ?, ?)")
                ->execute(array($name, $slug, $region, $sort));
            $added++;
        }
        $by_slug[$slug] = $id ?: (int) $pdo->lastInsertId();
    }

    // Bước 2: chuyển dữ liệu của các tỉnh đã bị sáp nhập sang tỉnh kế thừa, rồi xoá
    $moved = $removed = 0;
    foreach ($pdo->query("SELECT id, name, slug FROM provinces") as $r) {
        $slug = $r['slug'];
        if (isset($provinces_by_slug)) { /* noop */ }
        $still_valid = false;
        foreach ($provinces as $info) {
            if ($info[0] === $slug) { $still_valid = true; break; }
        }
        if ($still_valid) {
            continue;
        }

        $target_slug = isset($merged[$slug]) ? $merged[$slug] : null;
        $target_id   = $target_slug && isset($by_slug[$target_slug]) ? $by_slug[$target_slug] : null;

        if ($target_id) {
            foreach (array('users', 'posts', 'user_preferences') as $table) {
                $st = $pdo->prepare("UPDATE $table SET province_id = ? WHERE province_id = ?");
                $st->execute(array($target_id, $r['id']));
                $moved += $st->rowCount();
            }
            echo "  chuyển {$r['name']} -> $target_slug\n";
        } else {
            echo "  ! {$r['name']} không có tỉnh kế thừa, dữ liệu sẽ để trống khu vực\n";
        }

        $pdo->prepare("DELETE FROM provinces WHERE id = ?")->execute(array($r['id']));
        $removed++;
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    exit("Lỗi, đã hoàn tác toàn bộ: " . $e->getMessage() . "\n");
}

$total = (int) $pdo->query("SELECT COUNT(*) FROM provinces")->fetchColumn();
echo "\nThêm mới: $added | Đổi tên: $renamed | Xoá tỉnh cũ: $removed | Bản ghi được chuyển: $moved\n";
echo "Tổng số tỉnh/thành hiện có: $total\n";
