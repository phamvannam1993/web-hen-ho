<?php
// Run: php -d extension=pdo_sqlite tests/chat_online_members.php
define('BASEPATH', __DIR__);
class MY_Controller {}
require __DIR__ . '/../application/controllers/Ajax.php';
function display_name($row) { return $row['nickname'] ?: $row['display_name']; }
function avatar_url($avatar, $gender) { return $avatar ?: 'placeholder.svg'; }
function site_url($path) { return 'https://example.test/' . $path; }
class OnlineDB {
    public $pdo;
    private $columns, $table, $where = array(), $values = array(), $order, $limit;
    function __construct() { $this->pdo = new PDO('sqlite::memory:'); }
    function select($columns) { $this->columns = $columns; return $this; }
    function from($table) { $this->table = $table; return $this; }
    function where($key, $value = null, $escape = true) {
        if (!$escape) $this->where[] = $key;
        elseif ($value === null) $this->where[] = "$key IS NULL";
        else { $this->where[] = $key . (preg_match('/[><=]$/', $key) ? '' : ' =') . ' ?'; $this->values[] = $value; }
        return $this;
    }
    function where_in($key, $values) { $this->where[] = "$key IN (" . implode(',', array_fill(0, count($values), '?')) . ')'; $this->values = array_merge($this->values, $values); return $this; }
    function order_by($key, $direction) { $this->order = "$key $direction"; return $this; }
    function limit($limit) { $this->limit = $limit; return $this; }
    function get() {
        $sql = "SELECT $this->columns FROM $this->table WHERE " . implode(' AND ', $this->where) . " ORDER BY $this->order LIMIT $this->limit";
        $query = $this->pdo->prepare($sql); $query->execute($this->values);
        $this->where = $this->values = array();
        return new OnlineResult($query->fetchAll(PDO::FETCH_ASSOC));
    }
}
class OnlineResult { private $rows; function __construct($rows) { $this->rows = $rows; } function result_array() { return $this->rows; } }
class OnlineInput { public $after = 0; function get($key) { return $this->after; } }
class OnlineAuth { public $id = 1; function id() { return $this->id; } }
class OnlineAjax extends Ajax {
    public $db, $auth, $input;
    function __construct() { $this->db = new OnlineDB(); $this->auth = new OnlineAuth(); $this->input = new OnlineInput(); }
    function json($data, $code = 200) { return $data; }
}
function verify_online($ok, $message) { if (!$ok) throw new Exception($message); }
$controller = new OnlineAjax(); $pdo = $controller->db->pdo;
$pdo->exec('CREATE TABLE users (id INTEGER, display_name TEXT, nickname TEXT, avatar TEXT, gender TEXT, slug TEXT, status TEXT, deleted_at TEXT, last_active_at TEXT, birthday TEXT, role TEXT, email TEXT); CREATE TABLE blocks (user_id INTEGER, blocked_id INTEGER)');
$insert = $pdo->prepare('INSERT INTO users VALUES (?, ?, ?, NULL, ?, ?, ?, NULL, ?, ?, ?, ?)');
for ($id = 1; $id <= 70; $id++) $insert->execute(array($id, 'Member ' . $id, '', 'female', 'member-' . $id, 'active', date('Y-m-d H:i:s'), '1990-01-01', 'member', 'PRIVATE_EMAIL'));
$pdo->exec("UPDATE users SET status = 'banned' WHERE id = 2; UPDATE users SET deleted_at = '2020-01-01' WHERE id = 3;
    UPDATE users SET birthday = '" . date('Y-m-d', strtotime('-17 years')) . "' WHERE id = 4;
    UPDATE users SET last_active_at = '2020-01-01' WHERE id = 5;
    INSERT INTO blocks VALUES (1, 6), (7, 1)");
$first = $controller->online_members(); $ids = array_column($first['members'], 'id');
verify_online(count($ids) === 60 && $first['next'] !== null, 'Bounded first page with cursor');
foreach (array(2, 3, 4, 5, 6, 7) as $id) verify_online(!in_array($id, $ids), 'Exclude unavailable/underage/blocked member ' . $id);
verify_online($first['members'][0]['mine'] === true, 'Mark current member');
verify_online(strpos(json_encode($first), 'PRIVATE_EMAIL') === false, 'Do not expose private fields');
$controller->input->after = $first['next']; $last = $controller->online_members();
verify_online(count($last['members']) === 4 && $last['next'] === null, 'Remaining page returns all members without repeats');
$controller->auth->id = null; $controller->input->after = 0;
verify_online($controller->online_members()['ok'], 'Guests can view public presence');
echo "PASS: chat presence privacy, five-minute activity, blocks and complete pagination.\n";
