<?php
// Run through database/update.php using its PDO connection.
if (!isset($pdo)) { fwrite(STDERR,"Chạy php database/update.php để cập nhật tùy chọn tắt thông báo.\n"); exit(1); }
$mute_columns = array('message_muted_until'=>'BIGINT UNSIGNED DEFAULT NULL',
    'calls_notifications_enabled'=>'TINYINT UNSIGNED NOT NULL DEFAULT 1','calls_muted_until'=>'BIGINT UNSIGNED DEFAULT NULL');
foreach ($mute_columns as $mute_column=>$mute_definition) {
    if (!$pdo->query("SHOW COLUMNS FROM conversation_notification_preferences LIKE '".$mute_column."'")->fetch()) {
        $pdo->exec('ALTER TABLE conversation_notification_preferences ADD COLUMN '.$mute_column.' '.$mute_definition);
    }
}
echo "Đã sẵn sàng tắt thông báo theo loại và thời hạn.\n";
