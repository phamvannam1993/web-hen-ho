<?php
// Included by database/update.php; use its existing PDO connection.
if (!isset($pdo)) {
    fwrite(STDERR, "Chạy php database/update.php để cập nhật trả lời tin nhắn.\n");
    exit(1);
}
if (!$pdo->query("SHOW COLUMNS FROM messages LIKE 'reply_to_id'")->fetch()) {
    $pdo->exec('ALTER TABLE messages ADD COLUMN reply_to_id BIGINT UNSIGNED DEFAULT NULL,
        ADD KEY idx_message_reply (reply_to_id)');
}
echo "Đã sẵn sàng trả lời tin nhắn riêng.\n";
