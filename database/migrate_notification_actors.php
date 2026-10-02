<?php
// Run separately: php database/migrate_notification_actors.php
// Also included by database/update.php using its existing PDO connection.
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
if (!$pdo->query("SHOW COLUMNS FROM notifications LIKE 'actor_user_id'")->fetch()) {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN actor_user_id BIGINT UNSIGNED DEFAULT NULL AFTER user_id');
    echo "Đã thêm người gửi cho thông báo.\n";
} else {
    echo "Thông báo đã có cột người gửi.\n";
}
