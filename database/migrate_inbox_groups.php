<?php
/** Usage: php database/migrate_inbox_groups.php [--dry-run] */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $sql = file_get_contents(__DIR__ . '/migrate_inbox_groups.sql');
    if ($sql === false) throw new RuntimeException('Không đọc được migrate_inbox_groups.sql.');
    if (in_array('--dry-run', $argv, true)) {
        echo "Tạo nếu thiếu: inbox_groups, inbox_group_members, inbox_group_messages.\n";
        echo "Bổ sung client_message_id và khóa uq_inbox_message_retry nếu thiếu.\n";
        echo "Chưa kết nối database hoặc thực thi SQL.\n";
        exit(0);
    }

    // Match the website's .env loading rules; server environment takes precedence.
    $env = __DIR__ . '/../.env';
    if (is_readable($env)) {
        foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            if (getenv($key) === false) putenv($key . '=' . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
                . ';dbname=' . (getenv('DB_NAME') ?: 'web_hen_ho') . ';charset=utf8mb4',
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
    } catch (PDOException $e) {
        throw new RuntimeException('Không kết nối được database. Kiểm tra DB_HOST, DB_NAME, DB_USER, DB_PASS trong .env và extension pdo_mysql của PHP CLI.');
    }
    $lock = (int) $pdo->query("SELECT GET_LOCK('inbox-groups-migration', 10)")->fetchColumn();
    if ($lock !== 1) throw new RuntimeException('Có tiến trình khác đang cập nhật nhóm inbox. Hãy chạy lại sau.');
    try {
        // This schema contains only CREATE TABLE statements, without stored routines.
        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) !== '') $pdo->exec($statement);
        }
        if (!$pdo->query("SHOW COLUMNS FROM inbox_group_messages LIKE 'client_message_id'")->fetch()) {
            $pdo->exec('ALTER TABLE inbox_group_messages ADD COLUMN client_message_id VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL');
        }
        if (!$pdo->query("SHOW INDEX FROM inbox_group_messages WHERE Key_name = 'uq_inbox_message_retry'")->fetch()) {
            $pdo->exec('ALTER TABLE inbox_group_messages ADD UNIQUE KEY uq_inbox_message_retry (group_id, sender_id, client_message_id)');
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('inbox-groups-migration')");
    }
    echo "Nhóm inbox đã sẵn sàng. Tải lại trang /messenger.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Cập nhật nhóm inbox thất bại: ' . $e->getMessage() . "\n");
    exit(1);
}
