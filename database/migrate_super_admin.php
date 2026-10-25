<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run: php database/migrate_super_admin.php; also included by database/update.php.
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
// Expand the role before promoting existing accounts.
$pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('member','moderator','admin','super_admin') NOT NULL DEFAULT 'member'");
$pdo->exec("CREATE TABLE IF NOT EXISTS app_role_migrations (name VARCHAR(100) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try {
    $pdo->beginTransaction();
    $marker = $pdo->prepare("INSERT IGNORE INTO app_role_migrations(name) VALUES (?)");
    $marker->execute(array('promote_existing_admins_to_super_admin_v1'));
    if ($marker->rowCount() === 1) {
        $count = $pdo->exec("UPDATE users SET role='super_admin' WHERE role='admin'");
        echo "Đã chuyển $count admin hiện tại thành super admin.\n";
    } else { echo "Đã chuyển quyền trước đó; giữ nguyên admin nhóm chat tạo sau.\n"; }
    // Repair the explicitly designated bootstrap super admin once as well.
    $marker->execute(array('bootstrap_admin_henho24_super_admin_v1'));
    if ($marker->rowCount() === 1) {
        $bootstrap = $pdo->prepare("UPDATE users SET role='super_admin' WHERE email=? AND deleted_at IS NULL");
        $bootstrap->execute(array('admin@henho24.local'));
        echo "Đã cập nhật quyền super admin cho tài khoản quản trị mặc định nếu tồn tại.\n";
    }
    $pdo->commit();
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
