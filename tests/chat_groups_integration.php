<?php
/** Real InnoDB regression checks in a newly-created, isolated database.
 * Usage: php tests/chat_groups_integration.php
 * Test credentials: CHAT_TEST_DB_HOST / CHAT_TEST_DB_USER / CHAT_TEST_DB_PASS.
 * Never opens the application's database and never drops databases.
 */
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', dirname(__DIR__) . '/');
define('ENVIRONMENT', 'testing');
define('VIEWPATH', APPPATH . 'views/');
function check_group($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
function expect_group_error($fn, $code, $message) {
    try { $fn(); } catch (RuntimeException $e) { check_group($e->getCode() === $code, $message); return; }
    throw new RuntimeException('Expected error: ' . $message);
}
$testName = 'sc_chat_test_' . bin2hex(random_bytes(5));
$host = getenv('CHAT_TEST_DB_HOST') ?: '127.0.0.1';
$username = getenv('CHAT_TEST_DB_USER') ?: 'root';
$password = getenv('CHAT_TEST_DB_PASS') ?: '';
$pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $username, $password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$pdo->exec("CREATE DATABASE `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$testName`"); $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?[a-z_]+`?\s*\(.*?\) ENGINE=InnoDB[^;]*;/si', file_get_contents(FCPATH . 'database/schema.sql'), $ddl);
check_group(count($ddl[0]) > 20, 'Read only CREATE TABLE statements from schema');
foreach ($ddl[0] as $sql) $pdo->exec($sql);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
$pdo->exec("INSERT INTO users(id,uuid,email,password_hash,display_name,slug,gender,birthday,status,role) VALUES
 (1,UUID(),'chat-admin@test.invalid','unused','Quản trị','chat-admin','male','1990-01-01','active','admin'),
 (2,UUID(),'chat-member@test.invalid','unused','Tên Thành Viên','chat-member','female','1990-01-01','active','member')");
$pdo->exec("INSERT INTO room_messages(user_id,type,content) VALUES (2,'text','Tin cũ')");
$oldId = $pdo->lastInsertId();
putenv('DB_HOST=' . $host); putenv('DB_USER=' . $username); putenv('DB_PASS=' . $password); putenv('DB_NAME=' . $testName);
putenv('CHAT_GROUPS_ENABLED=1');
$argv = array('migration');
require FCPATH . 'database/migrate_chat_groups.php';
require FCPATH . 'database/migrate_chat_groups.php';
check_group((int) $pdo->query('SELECT COUNT(*) FROM chat_groups')->fetchColumn() === 2, 'Migration can run twice without duplicate rooms');
check_group((int) $pdo->query("SELECT room_id FROM room_messages WHERE id=$oldId")->fetchColumn() === 1, 'Legacy ID and message stay in default room');
require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';
$db = DB(array('hostname'=>$host,'username'=>$username,'password'=>$password,'database'=>$testName,'dbdriver'=>'mysqli',
    'dbprefix'=>'','pconnect'=>false,'db_debug'=>true,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci','save_queries'=>true));
class CI_Model { public $db, $auth, $input, $config; }
class ChatTestAuth {
    public $current = null, $verified = true;
    public function user() { return $this->current; }
    public function id() { return $this->current['id'] ?? null; }
    public function da_xac_thuc() { return $this->verified; }
}
class ChatTestInput { public function ip_address() { return '127.0.0.77'; } public function cookie($name,$x=false) { return ''; } }
class ChatTestConfig { public function item($name) { return 'chat-test-secret-only'; } }
function base_url($p='') { return '/'.$p; }
function site_url($p='') { return '/'.$p; }
function display_name($r) { return $r['display_name'] ?: $r['nickname']; }
function avatar_url($a,$g) { return '/avatar/' . ($a ?: 'default'); }
require APPPATH . 'helpers/chat_group_helper.php';
require APPPATH . 'models/M_chat_group.php';
$m = new M_chat_group(); $m->db = $db; $m->auth = new ChatTestAuth(); $m->input = new ChatTestInput(); $m->config = new ChatTestConfig();
$guest = array('key'=>'g:100','user'=>null); $guest2 = array('key'=>'g:101','user'=>null);
$room = $m->group(2); $public = $m->group(1);
expect_group_error(function () use ($m,$public,$guest) { $m->access($public,$guest,true); },401,'Guests cannot send to public-only rooms');
$first = $m->send($room,$guest,'Xin chào 👋','guest_message_key_01');
$again = $m->send($room,$guest,'Xin chào 👋','guest_message_key_01');
check_group($first['id'] === $again['id'], 'Retry creates one message');
check_group($first['sender']['guest'] && !$first['sender']['url'] && !isset($first['actor_key'],$first['user_id']), 'Guest payload exposes no account key or profile');
check_group($m->person(2,$guest)['public_id'] === $first['sender']['id'], 'Guest identity stable in room');
check_group($m->person(1,$guest,true)['public_id'] !== $first['sender']['id'], 'Room identity IDs are independent');
expect_group_error(function () use ($m,$room,$guest) { $m->send($room,$guest,'Changed payload','guest_message_key_01'); },409,'Retry key cannot be reused for different content');
check_group(count($m->messages($public,$guest)) === 1, 'Default room never includes other-room messages');
check_group($m->person(2,$guest)['is_following'] == 1, 'Successful first send follows room');
$m->person(2,$guest2,true); check_group(!$m->person(2,$guest2)['is_following'], 'Reading/identity allocation does not follow');
$reply = $m->send($room,$guest2,'Trả lời','guest_message_key_02',$first['id']);
expect_group_error(function () use ($m,$room,$guest,$oldId) { $m->send($room,$guest,'Cross room reply','cross_room_reply_key',$oldId); },422,'Cross-room reply rejected');
$m->action($room,$guest,'unfollow',array());
check_group(!$m->saved($guest), 'Unfollow hides saved group');
$m->messages($room,$guest); check_group(!$m->saved($guest), 'Reading does not restore hidden group');
$m->action($room,$guest,'follow',array());
check_group(count($m->saved($guest)) === 1, 'Following restores group');
$m->action($room,$guest,'read',array('message_id'=>$reply['id']));
$m->action($room,$guest,'read',array('message_id'=>$first['id']));
check_group((int) $m->person(2,$guest)['last_read_message_id'] === $reply['id'], 'Read marker never decreases');
$m->action($room,$guest,'notifications',array('enabled'=>0));
$m->send($room,$guest2,'Tin mới','guest_message_key_03');
check_group($m->summary($room,$guest)['unread'] === 1, 'Muting notifications preserves unread');
$member = $db->where('id',2)->get('users')->row_array(); $m->auth->current = $member;
$account = array('key'=>'u:2','user'=>$member);
$real = $m->send($room,$account,'Tôi đã đăng nhập','member_message_key_01');
check_group($real['sender']['name'] === 'Tên Thành Viên' && !$real['sender']['guest'] && $real['sender']['url'] === '/profile/chat-member', 'Signed-in messages use actual public profile');
check_group($m->messages($room,$account)[0]['sender']['guest'], 'Logging in does not relabel old guest messages');
$m->auth->verified = false;
expect_group_error(function () use ($m,$room,$account) { $m->send($room,$account,'Unverified','unverified_message_key'); },403,'Unverified members cannot send');
$m->auth->verified = true;
$db->where('id',2)->update('chat_groups',array('status'=>'readonly')); $readonly = $m->group(2);
expect_group_error(function () use ($m,$readonly,$guest) { $m->send($readonly,$guest,'Readonly','readonly_message_key'); },403,'Read-only prevents sending');
$db->where('id',2)->update('chat_groups',array('status'=>'active'));
$admin = $db->where('id',1)->get('users')->row_array(); $m->auth->current = $admin;
$m->moderate($room,'pin',(string)$first['id'],'Ghim thử');
check_group($m->summary($m->group(2),$guest)['pinned']['id'] === $first['id'], 'Pin visible message');
$m->moderate($room,'delete',(string)$first['id'],'Xóa thử');
check_group(!$m->summary($m->group(2),$guest)['pinned'], 'Deleting message removes pin');
check_group(!$m->visible_message($room,$guest,$first['id']), 'Deleted content is unavailable');
$changes = $m->removed($room,$guest,array($first['id'],$reply['id']),0);
check_group(in_array($first['id'],$changes['removed'],true), 'Delete events invalidate old loaded messages');
$m->moderate($room,'ban',$m->person(2,$guest2)['public_id'],'Cấm thử');
expect_group_error(function () use ($m,$room,$guest2) { $m->messages($room,$guest2); },403,'Banned guest cannot read');
$m->moderate($room,'unban',$m->person(2,$guest2)['public_id'],'Gỡ cấm thử');
$m->action($room,$guest,'block',array('target'=>$m->person(2,$guest2)['public_id']));
check_group(!$m->visible_message($room,$guest,$reply['id']), 'Blocked identity messages filtered on server');
$db->where('id',2)->update('chat_groups',array('status'=>'closed'));
expect_group_error(function () use ($m,$guest) { $m->messages($m->group(2),$guest); },403,'Closed room denies history');
$db->where('id',2)->update('chat_groups',array('status'=>'active'));
$rateKey = hash_hmac('sha256','actor:'.$guest['key'],'chat-test-secret-only');
$db->query('INSERT INTO chat_group_rate(rate_key,bucket,hits) VALUES (?,?,8) ON DUPLICATE KEY UPDATE hits=8',array($rateKey,(int)floor(time()/60)));
expect_group_error(function () use ($m,$room,$guest) { $m->send($room,$guest,'Too fast','guest_rate_key_9999'); },429,'Guest rate limit enforced transactionally');
check_group($pdo->query('SELECT COUNT(*) FROM chat_group_outbox')->fetchColumn() > 0, 'Committed sends/moderation create outbox records');
echo "Isolated database retained for inspection: $testName\n";
