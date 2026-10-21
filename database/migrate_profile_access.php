<?php
/** CLI: php database/migrate_profile_access.php. Additive, repeatable migration. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!isset($pdo)) {
    foreach (is_readable(__DIR__.'/../.env') ? file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES) : array() as $line) {
        if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m) && getenv($m[1]) === false) putenv($m[1].'='.trim($m[2], " \t\"'"));
    }
    $pdo = new PDO('mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').';dbname='.(getenv('DB_NAME') ?: 'web_hen_ho').';charset=utf8mb4', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
}
$pdo->exec("SET time_zone='+07:00'");
foreach (array('charge_enabled'=>'TINYINT NOT NULL DEFAULT 1','contact_vip_required'=>'TINYINT NOT NULL DEFAULT 1','shared_phone'=>'VARCHAR(40) DEFAULT NULL','shared_zalo'=>'VARCHAR(100) DEFAULT NULL','shared_email'=>'VARCHAR(190) DEFAULT NULL','transferable_coin_balance'=>'INT NOT NULL DEFAULT 0') as $column=>$definition) {
    if (!$pdo->query("SHOW COLUMNS FROM users LIKE '$column'")->fetch()) $pdo->exec("ALTER TABLE users ADD `$column` $definition");
}
if (!$pdo->query("SHOW COLUMNS FROM coin_transactions LIKE 'transferable_amount'")->fetch()) $pdo->exec('ALTER TABLE coin_transactions ADD transferable_amount INT NOT NULL DEFAULT 0');
$pdo->exec('CREATE TABLE IF NOT EXISTS profile_purchases (buyer_id BIGINT UNSIGNED NOT NULL,owner_id BIGINT UNSIGNED NOT NULL,amount INT NOT NULL DEFAULT 20,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(buyer_id,owner_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE IF NOT EXISTS profile_access_migrations (version VARCHAR(80) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->beginTransaction();
try {
    $users = $pdo->query('SELECT id,coin_balance FROM users ORDER BY id FOR UPDATE')->fetchAll(PDO::FETCH_ASSOC);
    if (!$pdo->query("SELECT version FROM profile_access_migrations WHERE version='transferable-v1' FOR UPDATE")->fetch()) {
        $update=$pdo->prepare('UPDATE users SET transferable_coin_balance=? WHERE id=?');
        $history=$pdo->prepare("SELECT t.amount,t.balance_after,t.reason,t.transferable_amount,t.ref_type,p.coin_amount FROM coin_transactions t LEFT JOIN orders o ON t.ref_type='order' AND o.id=t.ref_id LEFT JOIN packages p ON p.id=o.package_id WHERE t.user_id=? ORDER BY t.id");
        foreach ($users as $u) {
            $eligible=0; $history->execute(array($u['id']));
            foreach ($history->fetchAll(PDO::FETCH_ASSOC) as $t) {
                if ($t['amount']>0 && $t['transferable_amount']>0) $eligible+=(int)$t['transferable_amount'];
                elseif ($t['amount']>0 && $t['reason']==='nap' && $t['coin_amount']!==null) $eligible+=min((int)$t['amount'],(int)$t['coin_amount']);
                if ($t['amount']<0 && $t['ref_type']==='profile_transfer') $eligible=max(0,$eligible+(int)$t['amount']);
                $eligible=max(0,min($eligible,(int)$t['balance_after']));
            }
            $update->execute(array(max(0,min($eligible,(int)$u['coin_balance'])),$u['id']));
        }
        $pdo->exec("INSERT INTO profile_access_migrations VALUES ('transferable-v1')");
    }
    if (!$pdo->query("SELECT version FROM profile_access_migrations WHERE version='legacy-purchases-v1' FOR UPDATE")->fetch()) {
        $pdo->exec("INSERT IGNORE INTO profile_purchases(buyer_id,owner_id,amount,created_at) SELECT t.user_id,t.ref_id,20,MIN(t.created_at) FROM coin_transactions t JOIN users owner ON owner.id=t.ref_id WHERE t.ref_type='chat_profile' AND t.amount=-20 AND t.user_id<>t.ref_id GROUP BY t.user_id,t.ref_id");
        $pdo->exec("INSERT INTO profile_access_migrations VALUES ('legacy-purchases-v1')");
    }
    $pdo->commit(); echo "Profile access ready; existing balances, history and paid profile rights preserved.\n";
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
