<?php
// Included by database/update.php, using its existing PDO connection.
if (!isset($pdo)) {
    fwrite(STDERR, "Chạy php database/update.php để cập nhật lịch sử cuộc gọi.\n");
    exit(1);
}
if (!$pdo->query("SHOW COLUMNS FROM messages LIKE 'call_id'")->fetch()) {
    $pdo->exec('ALTER TABLE messages ADD COLUMN call_id CHAR(36) DEFAULT NULL');
}
if (!$pdo->query("SHOW INDEX FROM messages WHERE Key_name = 'uq_message_call'")->fetch()) {
    $pdo->exec('ALTER TABLE messages ADD UNIQUE KEY uq_message_call (call_id)');
}
echo "Đã sẵn sàng lưu tin nhắn cuộc gọi.\n";
