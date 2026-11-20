<?php
/** Run: php tests/message_badge_counts.php. No application DB or .env access. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__) . '/system/');
function log_message($level, $message) {}
function is_php($v) { return version_compare(PHP_VERSION, $v, '>='); }
function badge_check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
require BASEPATH . 'database/DB_driver.php';
require BASEPATH . 'database/DB_query_builder.php';
class CI_DB extends CI_DB_query_builder {}
require BASEPATH . 'database/drivers/sqlite3/sqlite3_driver.php';
class CI_Model { public $db; }
require dirname(__DIR__) . '/application/models/M_inbox_group.php';
require dirname(__DIR__) . '/application/models/M_chat_group.php';
$db = new CI_DB_sqlite3_driver(array('database' => ':memory:', 'db_debug' => true));
$db->initialize();
foreach (array(
    'CREATE TABLE users(id INTEGER PRIMARY KEY,status TEXT,deleted_at TEXT)',
    'CREATE TABLE blocks(user_id INTEGER,blocked_id INTEGER)',
    'CREATE TABLE inbox_groups(id INTEGER PRIMARY KEY,status TEXT)',
    'CREATE TABLE inbox_group_members(group_id INTEGER,user_id INTEGER,left_at TEXT,last_read_message_id INTEGER)',
    'CREATE TABLE inbox_group_messages(id INTEGER PRIMARY KEY,group_id INTEGER,sender_id INTEGER,deleted_at TEXT)',
    'CREATE TABLE chat_groups(id INTEGER PRIMARY KEY,status TEXT)',
    'CREATE TABLE chat_group_people(group_id INTEGER,actor_key TEXT,is_following INTEGER,is_hidden INTEGER,is_banned INTEGER,last_read_message_id INTEGER)',
    'CREATE TABLE room_messages(id INTEGER PRIMARY KEY,room_id INTEGER,actor_key TEXT,user_id INTEGER,deleted_at TEXT)',
    'CREATE TABLE chat_group_blocks(actor_key TEXT,blocked_actor_key TEXT)',
) as $sql) $db->query($sql);
foreach (array(1, 2, 3) as $id) $db->insert('users', array('id' => $id, 'status' => 'active'));
$private = new M_inbox_group(); $private->db = $db;
$clubs = new M_chat_group(); $clubs->db = $db;
$actor = array('key' => 'u:1', 'user' => array('id' => 1, 'status' => 'active'));
for ($id = 1; $id <= 25; $id++) {
    $db->insert('inbox_groups', array('id' => $id, 'status' => 'active'));
    $db->insert('inbox_group_members', array('group_id' => $id, 'user_id' => 1, 'last_read_message_id' => 0));
    $db->insert('inbox_group_messages', array('id' => $id, 'group_id' => $id, 'sender_id' => 2));
}
badge_check($private->unread_count(1) === 25, 'Count all groups beyond the first sidebar page');
badge_check($private->unread_count(3) === 0, 'No unread leakage to non-members');
$db->where('group_id', 1)->update('inbox_group_members', array('last_read_message_id' => 1));
$db->where('group_id', 2)->update('inbox_group_members', array('left_at' => '2026-11-20'));
$db->where('id', 3)->update('inbox_groups', array('status' => 'closed'));
$db->where('id', 4)->update('inbox_group_messages', array('deleted_at' => '2026-11-20'));
$db->where('id', 5)->update('inbox_group_messages', array('sender_id' => 1));
badge_check($private->unread_count(1) === 20, 'Exclude read, removed membership, closed, deleted and own messages');
$db->insert('blocks', array('user_id' => 2, 'blocked_id' => 1));
badge_check($private->unread_count(1) === 0, 'Private groups respect blocking in either direction');
$db->where('user_id', 2)->delete('blocks');
for ($id = 1; $id <= 7; $id++) {
    $db->insert('chat_groups', array('id' => $id, 'status' => $id === 4 ? 'closed' : 'active'));
    $db->insert('chat_group_people', array('group_id' => $id, 'actor_key' => 'u:1',
        'is_following' => $id !== 2 ? 1 : 0, 'is_hidden' => $id === 3 ? 1 : 0,
        'is_banned' => $id === 5 ? 1 : 0, 'last_read_message_id' => $id === 6 ? 6 : 0));
    $db->insert('room_messages', array('id' => $id, 'room_id' => $id, 'actor_key' => 'u:2', 'user_id' => 2));
}
badge_check($clubs->unread_count($actor) === 2, 'Clubs exclude unfollowed, hidden, closed, banned and read groups');
$db->insert('room_messages', array('id' => 8, 'room_id' => 1, 'actor_key' => 'u:1', 'user_id' => 1));
$db->insert('room_messages', array('id' => 9, 'room_id' => 1, 'actor_key' => 'g:1', 'deleted_at' => '2026-11-20'));
$db->insert('room_messages', array('id' => 10, 'room_id' => 1, 'actor_key' => 'g:2'));
$db->insert('chat_group_blocks', array('actor_key' => 'u:1', 'blocked_actor_key' => 'g:2'));
badge_check($clubs->unread_count($actor) === 2, 'Exclude own, recalled and blocked anonymous messages');
$db->insert('blocks', array('user_id' => 1, 'blocked_id' => 2));
badge_check($clubs->unread_count($actor) === 0, 'Clubs account blocking matches message visibility');
badge_check($clubs->unread_count(null) === 0, 'Guests do not get account message counts');
badge_check((int) $db->where('group_id', 1)->get('chat_group_people')->row('last_read_message_id') === 0,
    'Fetching a badge must not mark messages read');
$db->query('DROP TABLE inbox_group_members'); unset($db->data_cache['table_names']);
badge_check($private->unread_count(1) === 0, 'Missing optional group schema does not break the navigation');
echo "PASS: message badge counts all groups and respects read state, membership, privacy, blocks and missing optional schema.\n";
