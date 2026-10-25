<?php
// Run: php tests/super_admin_permissions.php (no database required).
define('BASEPATH', __DIR__);
class CI_Model { public $db; }
require __DIR__ . '/../application/libraries/Userauth.php';
require __DIR__ . '/../application/models/M_chat_group.php';
class RoleTestAuth extends Userauth {
    public $account;
    public function __construct() {}
    public function user() { return $this->account; }
}
class RoleTestDb {
    public $assigned = false;
    public function where($key, $value) { return $this; }
    public function count_all_results($table) { return $this->assigned ? 1 : 0; }
}
function expect_role($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$auth = new RoleTestAuth();
$chat = new M_chat_group(); $chat->db = new RoleTestDb();
$moderator = new ReflectionMethod(M_chat_group::class, 'moderator_as');
if (PHP_VERSION_ID < 80100) $moderator->setAccessible(true);
foreach (array('member','moderator','admin','super_admin') as $role) {
    $user = array('id'=>12, 'role'=>$role, 'status'=>'active', 'deleted_at'=>null);
    $auth->account = $user;
    expect_role($auth->is_admin() === ($role === 'super_admin'), "$role website access");
    foreach (array(false,true) as $assigned) {
        $chat->db->assigned = $assigned;
        $expected = $role === 'super_admin' || ($assigned && in_array($role,array('admin','moderator'),true));
        expect_role($moderator->invoke($chat, 2, $user) === $expected, "$role chat assignment");
    }
    $user['status']='locked'; $auth->account=$user;
    expect_role(!$auth->is_admin() && !$moderator->invoke($chat,2,$user), "$role locked");
    $user['status']='active'; $user['deleted_at']='2026-01-01'; $auth->account=$user;
    expect_role(!$auth->is_admin() && !$moderator->invoke($chat,2,$user), "$role deleted");
}
$auth->account=null; expect_role(!$auth->is_admin(), 'guest denied');
echo "PASS: website access, assigned chat permissions, locked/deleted accounts and guests\n";
