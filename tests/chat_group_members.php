<?php
/** php tests/chat_group_members.php — isolated SQLite, no application database. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__) . '/system/');
function log_message($level, $message) {}
function is_php($version) { return version_compare(PHP_VERSION, $version, '>='); }
function chat_group_guest_avatar_url($id) { return 'anonymous-avatar/' . $id; }
function check($ok, $label) { if (!$ok) throw new RuntimeException('FAIL: ' . $label); echo 'PASS: ' . $label . "\n"; }
function denied($fn, $code) {
    try { $fn(); } catch (InvalidArgumentException $e) { check($code === 422, 'invalid filter rejected'); return; }
    catch (RuntimeException $e) { check($e->getCode() === $code, 'access denied ' . $code); return; }
    throw new RuntimeException('Expected rejection');
}
require BASEPATH . 'database/DB_driver.php';
require BASEPATH . 'database/DB_query_builder.php';
class CI_DB extends CI_DB_query_builder {}
require BASEPATH . 'database/drivers/sqlite3/sqlite3_driver.php';
class CI_Model { public $db; }
require dirname(__DIR__) . '/application/models/M_chat_group.php';
$db = new CI_DB_sqlite3_driver(array('database' => ':memory:', 'db_debug' => false));
$db->initialize();
foreach (array(
    'CREATE TABLE users(id INTEGER PRIMARY KEY,role TEXT,status TEXT,deleted_at TEXT)',
    'CREATE TABLE chat_group_people(id INTEGER PRIMARY KEY,group_id INTEGER,public_id TEXT,nickname TEXT,user_id INTEGER,actor_key TEXT,is_banned INTEGER)',
    'CREATE TABLE chat_group_moderators(group_id INTEGER,user_id INTEGER,PRIMARY KEY(group_id,user_id))',
    'CREATE TABLE chat_group_blocks(actor_key TEXT,blocked_actor_key TEXT)',
    'CREATE TABLE blocks(user_id INTEGER,blocked_id INTEGER)'
) as $sql) $db->query($sql);
$model = new M_chat_group(); $model->db = $db;
$group = array('id' => 2, 'identity_mode' => 'guest', 'status' => 'active');
foreach (array(1 => 'super_admin', 2 => 'admin', 3 => 'moderator', 4 => 'admin', 5 => 'member') as $id => $role) {
    $db->insert('users', array('id' => $id, 'role' => $role, 'status' => 'active'));
}
$db->insert('chat_group_moderators', array('group_id' => 2, 'user_id' => 2));
$db->insert('chat_group_moderators', array('group_id' => 2, 'user_id' => 3));
for ($id = 1; $id <= 65; $id++) {
    $db->insert('chat_group_people', array('id' => $id, 'group_id' => 2, 'public_id' => 'opaque-' . $id,
        'nickname' => $id === 50 ? 'Mây 50%_x' : 'Mây ' . $id, 'user_id' => $id <= 5 ? $id : null,
        'actor_key' => $id <= 5 ? 'u:' . $id : 'g:' . $id, 'is_banned' => 0));
}
$all = array(); $cursor = '0';
do {
    $page = $model->members($group, null, '', $cursor);
    $all = array_merge($all, $page['members']); $cursor = $page['next_before'];
} while ($page['has_more']);
check(count($all) === 65 && count(array_unique(array_column($all, 'id'))) === 65, 'paging includes offline members, no duplicates or missing rows');
$byId = array_column($all, null, 'id');
check($byId['opaque-1']['role'] === 'Admin' && $byId['opaque-2']['role'] === 'Admin' && $byId['opaque-3']['role'] === 'Kiểm duyệt viên' && $byId['opaque-4']['role'] === null, 'roles follow actual group moderation permissions');
foreach ($all as $member) {
    if (array_keys($member) !== array('id', 'name', 'avatar', 'role', 'can_open_profile')) throw new RuntimeException('Unexpected payload field');
    if ($member['avatar'] !== 'anonymous-avatar/' . $member['id']) throw new RuntimeException('Real avatar leaked');
}
check($byId['opaque-1']['can_open_profile'] && !$byId['opaque-65']['can_open_profile'], 'anonymous payload whitelist and profile eligibility');
$found = $model->members($group, null, '50%_');
check(count($found['members']) === 1 && $found['members'][0]['id'] === 'opaque-50', 'Unicode search and literal wildcard escaping');
$db->where('id', 65)->update('chat_group_people', array('is_banned' => 1));
$db->where('id', 5)->update('users', array('status' => 'banned'));
$db->where('id', 4)->update('users', array('deleted_at' => '2026-11-11'));
$db->insert('chat_group_people', array('id' => 100, 'group_id' => 3, 'public_id' => 'other-group', 'nickname' => 'Mây', 'actor_key' => 'g:100', 'is_banned' => 0));
$db->insert('chat_group_blocks', array('actor_key' => 'g:64', 'blocked_actor_key' => 'u:2'));
$actor = array('key' => 'g:64', 'user' => null);
$ids = array_column($model->members($group, $actor, '', '6')['members'], 'id');
check($ids === array('opaque-3', 'opaque-1'), 'filters banned/deleted/blocked and scopes group');
denied(fn() => $model->members($group, array('key' => 'g:65', 'user' => null)), 403);
$closed = $group; $closed['status'] = 'closed'; denied(fn() => $model->members($closed, null), 403);
$private = $group; $private['identity_mode'] = 'public'; denied(fn() => $model->members($private, null), 401);
foreach (array('-1', '1e2', '999999999999999999999', array('1')) as $bad) denied(fn() => $model->members($group, null, '', $bad), 422);
denied(fn() => $model->members($group, null, array('q')), 422);
denied(fn() => $model->members($group, null, str_repeat('a', 41)), 422);
check($model->members($group, null, 'Không có')['members'] === array(), 'empty search');
