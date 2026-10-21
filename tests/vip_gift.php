<?php
/** Real database checks, isolated from the configured application database. */
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', dirname(__DIR__) . '/');
define('ENVIRONMENT', 'testing');
function check_group($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
$testName = 'sc_vip_test_' . bin2hex(random_bytes(5));
$host = getenv('CHAT_TEST_DB_HOST') ?: '127.0.0.1';
$username = getenv('CHAT_TEST_DB_USER') ?: 'root';
$password = getenv('CHAT_TEST_DB_PASS') ?: '';
$pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $username, $password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$pdo->exec("CREATE DATABASE `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$testName`"); $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?[a-z_]+`?\s*\(.*?\) ENGINE=InnoDB[^;]*;/si', file_get_contents(FCPATH . 'database/schema.sql'), $ddl);
foreach ($ddl[0] as $sql) $pdo->exec($sql);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
$pdo->exec("INSERT INTO users(id,uuid,email,password_hash,display_name,slug,gender,birthday,status,role) VALUES (1,UUID(),'vip-admin@test.invalid','unused','VIP Admin','vip-admin','male','1990-01-01','active','admin'),(2,UUID(),'vip-member@test.invalid','unused','VIP Member','vip-member','female','1990-01-01','active','member')");
require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';
$db = DB(array('hostname'=>$host,'username'=>$username,'password'=>$password,'database'=>$testName,'dbdriver'=>'mysqli','dbprefix'=>'','pconnect'=>false,'db_debug'=>true,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'));
class CI_Model { public $db; }
function base_url($path='') { return '/' . $path; }

$pdo->exec("INSERT INTO users(id,uuid,email,password_hash,display_name,slug,gender,birthday,status,role) VALUES (3,UUID(),'vip-free@test.invalid','unused','Free Member','vip-free','male','1990-01-01','active','member')");
$db->where('id',1)->update('users', array('is_vip'=>1,'vip_expired_at'=>null));
$future = date('Y-m-d H:i:s', strtotime('+10 days'));
$db->where('id',2)->update('users', array('is_vip'=>1,'vip_expired_at'=>$future));
require FCPATH . 'database/migrate_vip_gift.php';
$vipMember = $db->where('id',2)->get('users')->row_array();
check_group($vipMember['is_vip'] == 1 && $vipMember['vip_expired_at'] === date('Y-m-d H:i:s', strtotime($future . ' +30 days')), 'Existing VIP gets thirty extra days');
$vipLifetime = $db->where('id',1)->get('users')->row_array();
check_group($vipLifetime['vip_expired_at'] === null, 'Lifetime VIP is never downgraded');
$freeUser = $db->where('id',3)->get('users')->row_array();
check_group($freeUser['is_vip'] == 1 && abs(strtotime($freeUser['vip_expired_at']) - strtotime('+30 days')) < 5, 'Existing non-VIP member receives thirty days');
check_group($db->where('user_id',3)->count_all_results('notifications') === 1, 'Existing member receives one gift notification');

$notificationsBefore = $db->count_all_results('notifications');
require FCPATH . 'database/migrate_vip_gift.php';
check_group($db->count_all_results('notifications') === $notificationsBefore, 'Rerunning campaign never duplicates notifications');
check_group($db->where('id',2)->get('users')->row_array()['vip_expired_at'] === $vipMember['vip_expired_at'], 'Rerunning campaign never extends VIP twice');
function setting($key, $default = null) { return $default; }
function unique_slug($table, $slug) { return $slug; }
require APPPATH . 'models/M_user.php';
class GiftTestUser extends M_user { public function recalc_profile_score($id) {} }
$giftModel = new GiftTestUser(); $giftModel->db = $db;
$newId = $giftModel->register(array('email'=>'gift-test@example.invalid','phone'=>null,'password'=>'test-password','display_name'=>'VIP Welcome','gender'=>'female','birthday'=>'1995-01-01','province_id'=>null));
$newUser = $db->where('id',$newId)->get('users')->row_array();
check_group($newUser['is_vip'] == 1 && abs(strtotime($newUser['vip_expired_at']) - strtotime('+30 days')) < 5, 'New registration receives thirty days of VIP immediately');
require FCPATH . 'database/migrate_vip_gift.php';
check_group($db->where('id',$newId)->get('users')->row_array()['vip_expired_at'] === $newUser['vip_expired_at'], 'Signup gift cannot be granted again by existing-member migration');
check_group($db->where('user_id',$newId)->count_all_results('notifications') === 0, 'New user uses welcome popup rather than existing-member notification');
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function tk_icon($name) { return ''; }
class GiftTestSession {
    public $id;
    public function userdata($key) { return $this->id; }
    public function unset_userdata($key) { $this->id = null; }
}
class GiftTestView {
    public $session;
    public function render($user) { ob_start(); require APPPATH . 'views/layouts/_signup_vip_gift.php'; return ob_get_clean(); }
}
$giftView = new GiftTestView(); $giftView->session = new GiftTestSession(); $giftView->session->id = $newId;
check_group(strpos($giftView->render($newUser), 'Bạn đã nhận được 30 ngày VIP!') !== false, 'New user sees welcome VIP popup');
check_group($giftView->render($newUser) === '', 'VIP welcome popup appears once');
check_group($giftView->render($vipMember) === '', 'Existing member never receives signup popup');

function site_url($path='') { return '/' . $path; }
$db->where('id',3)->update('users',array('is_vip'=>0,'vip_expired_at'=>date('Y-m-d H:i:s',strtotime('-2 days'))));
$beforeBulk = $db->where('id',2)->get('users')->row_array()['vip_expired_at'];
$operation = bin2hex(random_bytes(16));
$bulk = $giftModel->grant_vip_all(60, $operation);
check_group($bulk['granted'] === 3 && $bulk['lifetime'] === 1, 'Admin operation grants every eligible member and preserves lifetime VIP');
check_group($db->where('id',2)->get('users')->row_array()['vip_expired_at'] === date('Y-m-d H:i:s',strtotime($beforeBulk . ' +60 days')), 'Admin sixty-day grant extends active VIP');
check_group(abs(strtotime($db->where('id',3)->get('users')->row_array()['vip_expired_at'])-strtotime('+60 days')) < 5, 'Expired VIP restarts from current date');
$bulkNotifications = $db->count_all_results('notifications');
$repeat = $giftModel->grant_vip_all(60, $operation);
check_group($repeat['granted'] === 0 && $bulkNotifications === $db->count_all_results('notifications'), 'Double-submit never grants or notifies twice');
$next = $giftModel->grant_vip_all(30, bin2hex(random_bytes(16)));
check_group($next['granted'] === 3, 'A separately confirmed admin operation can grant another duration');
foreach (array(0, -1, 3651) as $invalidDays) {
    $rejected = false;
    try { $giftModel->grant_vip_all($invalidDays, bin2hex(random_bytes(16))); }
    catch (InvalidArgumentException $e) { $rejected = true; }
    check_group($rejected, 'Invalid grant duration is rejected: ' . $invalidDays);
}
