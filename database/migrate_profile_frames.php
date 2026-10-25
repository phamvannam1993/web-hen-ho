<?php
// Run: php database/migrate_profile_frames.php; also included by database/update.php.
if (!isset($pdo)) {
    $env = __DIR__ . '/../.env';
    if (is_readable($env)) {
        foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'web_hen_ho') . ';charset=utf8mb4',
            getenv('DB_USER') ?: 'root', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
    } catch (PDOException $e) {
        fwrite(STDERR, "Không kết nối được cơ sở dữ liệu. Kiểm tra DB_* trong .env\n");
        exit(1);
    }
}
if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'profile_frame'")->fetch()) {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_frame VARCHAR(32) NOT NULL DEFAULT 'none'");
    echo "Đã thêm khung hồ sơ chat.\n";
} else { echo "Đã có khung hồ sơ chat.\n"; }
$pdo->exec("CREATE TABLE IF NOT EXISTS user_profile_frames (
    user_id BIGINT UNSIGNED NOT NULL,
    frame_key VARCHAR(32) NOT NULL,
    price_paid INT UNSIGNED NOT NULL DEFAULT 0,
    purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, frame_key),
    CONSTRAINT fk_profile_frame_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Đã sẵn sàng kho khung trả phí.\n";

// Change only the default; preserve frames members have already selected.
$pdo->exec("ALTER TABLE users ALTER COLUMN profile_frame SET DEFAULT 'none'");
