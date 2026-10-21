<?php
/** Run once after deployment: php database/migrate_vip_gift.php. Safe to rerun. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!isset($pdo)) {
    foreach (is_readable(__DIR__ . '/../.env') ? file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES) : array() as $line) {
        if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m) && getenv($m[1]) === false) putenv($m[1] . '=' . trim($m[2], " \t\"'"));
    }
    $pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';dbname=' . (getenv('DB_NAME') ?: 'web_hen_ho') . ';charset=utf8mb4', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
}
$pdo->exec("SET time_zone = '+07:00'");
$pdo->exec('CREATE TABLE IF NOT EXISTS vip_gift_grants (user_id BIGINT UNSIGNED NOT NULL, campaign VARCHAR(60) NOT NULL, source VARCHAR(20) NOT NULL, granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,campaign)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$giftCount = 0;
try {
    $pdo->beginTransaction();
    $giftUsers = $pdo->query("SELECT id,is_vip,vip_expired_at FROM users WHERE deleted_at IS NULL ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_ASSOC);
    $giftInsert = $pdo->prepare("INSERT IGNORE INTO vip_gift_grants(user_id,campaign,source) VALUES (?,'vip30_20261021','existing')");
    $giftUpdate = $pdo->prepare("UPDATE users SET vip_expired_at=CASE WHEN is_vip=1 AND vip_expired_at IS NULL THEN NULL ELSE DATE_ADD(GREATEST(COALESCE(vip_expired_at,NOW()),NOW()),INTERVAL 30 DAY) END,is_vip=1 WHERE id=?");
    $giftNotify = $pdo->prepare("INSERT INTO notifications(user_id,type,title,body,url) VALUES (?,'system','Bạn nhận được quà VIP 30 ngày!',?,'/tai-khoan')");
    foreach ($giftUsers as $giftUser) {
        $giftInsert->execute(array($giftUser['id']));
        if (!$giftInsert->rowCount()) continue;
        $giftUpdate->execute(array($giftUser['id']));
        $body = $giftUser['is_vip'] && $giftUser['vip_expired_at'] === null
            ? 'Saigon Cupid tặng thành viên 30 ngày VIP. Bạn đã có VIP vĩnh viễn nên tiếp tục giữ nguyên toàn bộ quyền lợi.'
            : 'Saigon Cupid đã tặng bạn 30 ngày VIP. Nếu VIP còn hạn, 30 ngày được cộng tiếp vào thời hạn hiện tại. Khám phá các quyền lợi VIP trong tài khoản nhé!';
        $giftNotify->execute(array($giftUser['id'], $body));
        $giftCount++;
    }
    $pdo->commit();
    echo "VIP gift ready: $giftCount users granted and notified.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}
