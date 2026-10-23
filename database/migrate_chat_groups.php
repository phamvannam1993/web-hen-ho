<?php
/** Additive migration. Usage: php database/migrate_chat_groups.php [--dry-run]
 * Never runs automatically on HTTP requests. Existing room IDs/content are preserved.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (is_readable(__DIR__ . '/../.env') ? file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES) : array() as $line) {
    if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m) && getenv($m[1]) === false) putenv($m[1] . '=' . trim($m[2], " \t\"'"));
}
$tables = array(
'chat_group_reactions' => 'message_id BIGINT UNSIGNED NOT NULL, actor_key VARCHAR(80) NOT NULL, emoji VARCHAR(16) NOT NULL, PRIMARY KEY(message_id,actor_key)',
'chat_group_migrations' => 'version VARCHAR(80) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
'chat_groups' => "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(100) NOT NULL UNIQUE, name VARCHAR(120) NOT NULL,
 description TEXT NOT NULL, seo_title VARCHAR(255) NOT NULL DEFAULT '', seo_description VARCHAR(500) NOT NULL DEFAULT '',
 rules TEXT NOT NULL, avatar VARCHAR(255) DEFAULT NULL, category VARCHAR(80) NOT NULL DEFAULT '',
 province_id INT UNSIGNED DEFAULT NULL, status ENUM('active','readonly','closed') NOT NULL DEFAULT 'active',
 identity_mode ENUM('public','guest') NOT NULL DEFAULT 'public', allow_images TINYINT NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0, pinned_message_id BIGINT UNSIGNED DEFAULT NULL,
 last_activity_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
'chat_guests' => 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, token_hash CHAR(64) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
'chat_group_people' => "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id BIGINT UNSIGNED NOT NULL,
 actor_key VARCHAR(80) NOT NULL, public_id CHAR(32) NOT NULL UNIQUE, nickname VARCHAR(80) NOT NULL,
 user_id BIGINT UNSIGNED DEFAULT NULL, is_following TINYINT NOT NULL DEFAULT 0, has_posted TINYINT NOT NULL DEFAULT 0,
 is_hidden TINYINT NOT NULL DEFAULT 0, notifications_enabled TINYINT NOT NULL DEFAULT 1,
 last_read_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0, last_active_at DATETIME DEFAULT NULL,
 muted_until DATETIME DEFAULT NULL, is_banned TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_group_actor(group_id,actor_key), UNIQUE KEY uq_group_nickname(group_id,nickname), KEY presence(group_id,last_active_at)",
'chat_group_moderators' => 'group_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY(group_id,user_id)',
'chat_group_blocks' => 'actor_key VARCHAR(80) NOT NULL, blocked_actor_key VARCHAR(80) NOT NULL, PRIMARY KEY(actor_key,blocked_actor_key)',
'chat_group_reports' => "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id BIGINT UNSIGNED NOT NULL,
 message_id BIGINT UNSIGNED NOT NULL, actor_key VARCHAR(80) NOT NULL, reason VARCHAR(500) NOT NULL,
 status ENUM('open','resolved') NOT NULL DEFAULT 'open', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY report_once(message_id,actor_key)",
'chat_group_logs' => 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NOT NULL, action VARCHAR(40) NOT NULL, target VARCHAR(100) NOT NULL,
 reason VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
'chat_group_rate' => 'rate_key CHAR(64) NOT NULL, bucket BIGINT NOT NULL, hits INT NOT NULL DEFAULT 0, PRIMARY KEY(rate_key,bucket)',
'chat_group_outbox' => 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id BIGINT UNSIGNED NOT NULL,
 message_id BIGINT UNSIGNED DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at DATETIME DEFAULT NULL, KEY pending(sent_at,id)',
);
if (in_array('--dry-run', $argv, true)) {
    echo "Version: chat-groups-v1\nCreate if absent: " . implode(', ', array_keys($tables)) . "\n";
    echo "Extend room_messages; make user_id nullable; seed default/public and guest rooms. No SQL executed.\n";
    echo "Add chat_groups.seo_title and chat_groups.seo_description if absent.\n";
    exit;
}
try {
    $pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';dbname=' . (getenv('DB_NAME') ?: 'web_hen_ho') . ';charset=utf8mb4', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $pdo->exec("SET time_zone = '+07:00'");
    if (!$pdo->query("SELECT GET_LOCK('saigoncupid.chat-groups-v1', 10)")->fetchColumn()) throw new RuntimeException('Another migration is running.');
    foreach ($tables as $name => $body) $pdo->exec("CREATE TABLE IF NOT EXISTS `$name` ($body) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    foreach (array('seo_title' => "VARCHAR(255) NOT NULL DEFAULT ''", 'seo_description' => "VARCHAR(500) NOT NULL DEFAULT ''") as $column => $definition) {
        if (!$pdo->query("SHOW COLUMNS FROM chat_groups LIKE '$column'")->fetch()) $pdo->exec("ALTER TABLE chat_groups ADD `$column` $definition");
    }
    if (!$pdo->query("SHOW COLUMNS FROM chat_group_outbox LIKE 'message_id'")->fetch()) $pdo->exec('ALTER TABLE chat_group_outbox ADD message_id BIGINT UNSIGNED DEFAULT NULL');
    foreach (array('nickname_chosen' => 'TINYINT NOT NULL DEFAULT 0', 'joined_at' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP') as $column => $definition) {
        if (!$pdo->query("SHOW COLUMNS FROM chat_group_people LIKE '$column'")->fetch()) $pdo->exec("ALTER TABLE chat_group_people ADD `$column` $definition");
    }
    $columns = array_column($pdo->query('SHOW COLUMNS FROM room_messages')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $add = array('room_id' => 'BIGINT UNSIGNED NOT NULL DEFAULT 1', 'sender_public_id' => 'CHAR(32) DEFAULT NULL',
        'actor_key' => 'VARCHAR(80) DEFAULT NULL', 'reply_to_id' => 'BIGINT UNSIGNED DEFAULT NULL',
        'client_message_id' => 'VARCHAR(80) DEFAULT NULL', 'deleted_by' => 'BIGINT UNSIGNED DEFAULT NULL', 'caption' => 'TEXT DEFAULT NULL');
    foreach ($add as $name => $definition) if (!in_array($name, $columns, true)) $pdo->exec("ALTER TABLE room_messages ADD `$name` $definition");
    $pdo->exec('ALTER TABLE room_messages MODIFY user_id BIGINT UNSIGNED NULL');
    $indexes = array_column($pdo->query('SHOW INDEX FROM room_messages')->fetchAll(PDO::FETCH_ASSOC), 'Key_name');
    foreach (array('idx_group_message' => '(room_id,id)', 'uq_group_retry' => '(room_id,actor_key,client_message_id)') as $name => $fields) {
        if (!in_array($name, $indexes, true)) $pdo->exec('ALTER TABLE room_messages ADD ' . ($name === 'uq_group_retry' ? 'UNIQUE ' : '') . "KEY $name $fields");
    }
    $pdo->beginTransaction();
    // Seed only on first installation; names/slugs can be edited or groups deleted later.
    $seeded = $pdo->query("SELECT 1 FROM chat_group_migrations WHERE version='chat-groups-v1'")->fetchColumn();
    if (!$seeded) {
    $pdo->exec("INSERT IGNORE INTO chat_groups (id,slug,name,description,rules,identity_mode,allow_images,sort_order)
        VALUES (1,'hen-ho-4-phuong','Nhóm Hẹn Hò 4 Phương','Làm quen và kết nối bạn mới.','Tôn trọng nhau. Không spam hoặc chia sẻ thông tin riêng của người khác.','public',1,100)");
    $pdo->exec("INSERT IGNORE INTO chat_groups (slug,name,description,rules,identity_mode)
        VALUES ('phong-chat-an-danh','Phòng Chat Ẩn Danh','Khách trò chuyện bằng nickname ẩn danh. Thành viên đăng nhập dùng hồ sơ thật.','Tôn trọng nhau. Không spam, quấy rối hoặc tiết lộ thông tin cá nhân.','guest')");
    $pdo->exec("INSERT IGNORE INTO chat_group_migrations(version) VALUES ('chat-groups-v1')");
    }
    $pdo->commit();
    $pdo->query("SELECT RELEASE_LOCK('saigoncupid.chat-groups-v1')");
    echo "chat-groups-v1 ready. Existing messages remain in group 1. Enable CHAT_GROUPS_ENABLED=1 after validation.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n"); exit(1);
}
