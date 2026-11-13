<?php
// Run: php tests/message_recall.php (no database or .env required).
define('BASEPATH', __DIR__);
define('APPPATH', dirname(__DIR__) . '/application/');
class CI_Model {}
class CI_Controller {}
class Api_Controller {}
function base_url($path) { return 'https://example.test/' . $path; }
require APPPATH . 'models/M_interaction.php';
require APPPATH . 'controllers/Message_actions.php';
require APPPATH . 'controllers/api/Chat.php';

class RecallResult {
    private $rows;
    function __construct($rows) { $this->rows = $rows; }
    function row_array() { return $this->rows ? $this->rows[0] : null; }
    function result_array() { return $this->rows; }
}
class RecallDb {
    public $rows, $pin, $snapshot;
    public $pinsAvailable = true, $beginOk = true, $statusOk = true, $commitOk = true;
    public $queries = 0, $failQuery = 0, $rolledBack = false, $committed = false;
    protected $conditions = array(), $ids = null;
    function __construct() {
        $this->rows = array();
        foreach (array(5 => array(9, 1, 'text'), 6 => array(9, 2, 'image'),
            7 => array(10, 1, 'text'), 8 => array(9, 1, 'image'), 11 => array(9, 1, 'image')) as $id => $data) {
            $this->rows[$id] = array('id' => $id, 'conversation_id' => $data[0], 'sender_id' => $data[1],
                'type' => $data[2], 'content' => 'original-private-content-' . $id,
                'created_at' => '2026-11-10 12:00:00', 'read_at' => null,
                'deleted_at' => $id === 11 ? '2026-11-10 12:01:00' : null);
        }
        $this->pin = array('conversation_id' => 9, 'message_id' => 5);
    }
    function trans_begin() {
        if (!$this->beginOk) return false;
        $this->snapshot = array($this->rows, $this->pin); return true;
    }
    function trans_status() { return $this->statusOk; }
    function trans_commit() { $this->committed = $this->commitOk; return $this->commitOk; }
    function trans_rollback() {
        $this->rolledBack = true;
        list($this->rows, $this->pin) = $this->snapshot;
    }
    function table_exists($table) { return $table === 'conversation_pins' && $this->pinsAvailable; }
    function query($sql, $params) {
        $this->queries++;
        if ($this->queries === $this->failQuery) return false;
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        if (strpos($sql, 'SELECT id FROM conversations') === 0) {
            if (strpos($sql, 'FOR UPDATE') !== false && $this->queries !== 1) throw new Exception('Lock conversation first');
            return new RecallResult($params[0] === 9 && in_array($params[1], array(1, 2), true) ? array(array('id' => 9)) : array());
        }
        if (strpos($sql, 'SELECT id, sender_id, deleted_at, type FROM messages') === 0) {
            if (strpos($sql, 'FOR UPDATE') === false || $this->queries !== 2) throw new Exception('Lock message second');
            $row = $this->rows[$params[1]] ?? null;
            return new RecallResult($row && $row['conversation_id'] === $params[0] ? array($row) : array());
        }
        if (strpos($sql, 'UPDATE messages') === 0) {
            if (strpos($sql, 'sender_id = ?') === false || strpos($sql, 'deleted_at IS NULL') === false) throw new Exception('Missing ownership guards');
            $row =& $this->rows[$params[2]];
            if ($row['conversation_id'] === $params[3] && $row['sender_id'] === $params[4] && $row['deleted_at'] === null) {
                $row['type'] = $params[0]; $row['content'] = $params[1]; $row['deleted_at'] = '2026-11-10 12:02:00';
            }
            return true;
        }
        if (strpos($sql, 'DELETE FROM conversation_pins') === 0) {
            if (strpos($sql, 'conversation_id = ? AND message_id = ?') === false) throw new Exception('Missing pin scope');
            if ($this->pin && $this->pin['conversation_id'] === $params[0] && $this->pin['message_id'] === $params[1]) $this->pin = null;
            return true;
        }
        throw new Exception('Unexpected query');
    }
    function select($columns) { return $this; }
    function where($key, $value = null, $escape = true) { $this->conditions[$key] = $value; return $this; }
    function where_in($key, $values) { $this->ids = $values; return $this; }
    function order_by($key, $order) { return $this; }
    function limit($count) { return $this; }
    function get($table) {
        $this->queries++;
        if ($this->queries === $this->failQuery) return false;
        $rows = array();
        foreach ($this->rows as $row) {
            if (isset($this->conditions['conversation_id']) && $row['conversation_id'] !== $this->conditions['conversation_id']) continue;
            if (isset($this->conditions['id >']) && $row['id'] <= $this->conditions['id >']) continue;
            if (array_key_exists('deleted_at IS NOT NULL', $this->conditions) && !$row['deleted_at']) continue;
            if ($this->ids !== null && !in_array($row['id'], $this->ids, true)) continue;
            $rows[] = $row;
        }
        $this->conditions = array(); $this->ids = null;
        return new RecallResult($rows);
    }
}
class RecallModel extends M_interaction { public $db; }
class RecallAuth {
    public $loggedIn = true, $user = array('id' => 1, 'status' => 'active', 'deleted_at' => null);
    function check() { return $this->loggedIn; }
    function user() { return $this->user; }
}
class RecallSession {
    public $data = array();
    function userdata($key) { return $this->data[$key] ?? null; }
    function set_userdata($key, $value) { $this->data[$key] = $value; }
}
class RecallInput {
    public $method = 'get', $get = array(), $post = array();
    function method() { return $this->method; }
    function get($key) { return $this->get[$key] ?? null; }
    function post($key) { return $this->post[$key] ?? null; }
}
class RecallOutput {
    public $code = 200, $body;
    function set_status_header($code) { $this->code = $code; return $this; }
    function set_content_type($type) { return $this; }
    function set_output($json) { $this->body = json_decode($json, true); return $this; }
}
class RecallLoader { function model($name) {} }
class RecallController extends Message_actions {
    public $auth, $input, $output, $session, $load, $db, $m_interaction;
    function __construct() {
        $this->auth = new RecallAuth(); $this->input = new RecallInput();
        $this->output = new RecallOutput(); $this->session = new RecallSession(); $this->load = new RecallLoader();
        $this->db = new RecallDb(); $this->m_interaction = new RecallModel(); $this->m_interaction->db = $this->db;
    }
}
class RecallApi extends Chat {
    public $m_interaction;
    function __construct() { $this->m_interaction = new RecallModel(); }
}
$count = 0;
function verify_recall($ok, $label) { global $count; if (!$ok) throw new Exception($label); $count++; }
function recall_model() { $m = new RecallModel(); $m->db = new RecallDb(); return $m; }
function denied_recall($model, $args, $code) {
    try { $model->recall_message(...$args); } catch (RuntimeException | InvalidArgumentException $e) {
        verify_recall($e->getCode() === $code, 'Expected rejection ' . $code); return;
    }
    throw new Exception('Expected rejection');
}
foreach (array(5, 8) as $id) {
    $m = recall_model(); $m->recall_message(9, 1, $id);
    verify_recall($m->db->rows[$id]['content'] === 'Tin nhắn đã được thu hồi'
        && $m->db->rows[$id]['type'] === 'system' && $m->db->rows[$id]['deleted_at'], 'Recall text/image');
    $snapshot = $m->db->rows[$id]; $m->db->queries = 0;
    $m->recall_message(9, 1, $id);
    verify_recall($m->db->rows[$id] === $snapshot, 'Idempotent recall');
}
foreach (array(array(9,2,5,403), array(9,3,5,403), array(9,1,7,404), array(9,1,99,404),
    array(9,2,11,403), array(0,1,5,422), array(9,-1,5,422), array(9,1,array(),422)) as $case) {
    $m = recall_model(); $snapshot = $m->db->rows; $code = array_pop($case);
    denied_recall($m, $case, $code);
    verify_recall($m->db->rows === $snapshot, 'Rejected recall preserves content');
}
foreach (array(false, true) as $available) {
    $m = recall_model(); $m->db->pinsAvailable = $available; $m->recall_message(9,1,5);
    verify_recall($m->db->committed && (!$available || $m->db->pin === null), 'Pin table optional; matching pin removed');
}
foreach (array(array('conversation_id'=>9,'message_id'=>6), array('conversation_id'=>10,'message_id'=>5)) as $pin) {
    $m = recall_model(); $m->db->pin = $pin; $m->recall_message(9,1,5);
    verify_recall($m->db->pin === $pin, 'Other pin preserved');
}
for ($i = 1; $i <= 4; $i++) {
    $m = recall_model(); $snapshot = $m->db->rows; $m->db->failQuery = $i;
    denied_recall($m, array(9,1,5), 503);
    verify_recall($m->db->rolledBack && $m->db->rows === $snapshot && $m->db->pin !== null, 'Query failure rolls back');
}
foreach (array('beginOk','statusOk','commitOk') as $flag) {
    $m = recall_model(); $snapshot = $m->db->rows; $m->db->$flag = false;
    denied_recall($m, array(9,1,5), 503);
    verify_recall($m->db->rows === $snapshot && ($flag === 'beginOk' ? !$m->db->queries : $m->db->rolledBack), 'Transaction failure safe');
}
$m = recall_model();
$rows = $m->messages(9);
verify_recall(count($rows) === 4 && $rows[3]['recalled'] && $rows[3]['type'] === 'system'
    && $rows[3]['content'] === 'Tin nhắn đã được thu hồi', 'SSR history sanitizes legacy deleted images');
$api = new RecallApi(); $serializer = new ReflectionMethod(Chat::class, 'tin');
$serialized = $serializer->invoke($api, $m->db->rows[11], true);
verify_recall($serialized['recalled'] && $serialized['type'] === 'system'
    && strpos(json_encode($serialized), 'original-private-content') === false, 'Mobile serializer hides deleted image content');

$c = new RecallController(); $c->auth->loggedIn = false; $c->recall(9);
verify_recall($c->output->code === 401 && !$c->db->queries, 'Login required');
foreach (array('banned','pending') as $status) {
    $c = new RecallController(); $c->auth->user['status'] = $status; $c->recall(9);
    verify_recall($c->output->code === 403 && !$c->db->queries, 'Inactive account rejected');
}
$c = new RecallController(); $c->auth->user['deleted_at'] = '2026-11-10'; $c->recall(9);
verify_recall($c->output->code === 403, 'Deleted account rejected');
$c = new RecallController(); $c->input->method = 'delete'; $c->recall(9);
verify_recall($c->output->code === 405 && !$c->db->queries, 'Unsupported method rejected');
$c = new RecallController(); $c->auth->user['id'] = 3; $c->recall(9);
verify_recall($c->output->code === 403 && !$c->session->data, 'Nonparticipant gets no token');
foreach (array(array('1'), '1,x', '0', '-1', '1,,2', str_repeat('1,', 100) . '1', str_repeat('9', 2101)) as $ids) {
    $c = new RecallController(); $c->input->get['ids'] = $ids; $c->recall(9);
    verify_recall($c->output->code === 422, 'Invalid or excessive ID list rejected');
}
$c = new RecallController(); $c->input->get['ids'] = '5,7,11'; $c->recall(9);
verify_recall($c->output->body['recalled_ids'] === array(11), 'Status scoped to requested conversation and deleted rows');
verify_recall((bool) preg_match('/^[a-f0-9]{64}$/D', $c->output->body['action_token']), 'GET recall creates own token');
verify_recall(strpos(json_encode($c->output->body), 'original-private-content') === false, 'Status discloses IDs only');
$token = $c->output->body['action_token']; $c->input->method = 'post';
$c->input->post = array('message_id' => '5', 'action_token' => $token); $c->db->queries = 0;
$c->recall(9);
verify_recall($c->output->code === 200 && $c->output->body === array('ok'=>true, 'id'=>5, 'recalled'=>true), 'POST recalls own message');
foreach (array(null, 'wrong', array('wrong')) as $token) {
    $c = new RecallController(); $c->input->method = 'post';
    $c->session->data['private_message_action_token'] = str_repeat('a', 64);
    $c->input->post = array('message_id'=>'5', 'action_token'=>$token); $c->recall(9);
    verify_recall($c->output->code === 403 && !$c->db->queries, 'Invalid action token rejected');
}
$c = new RecallController(); $c->input->method = 'post';
$c->session->data['private_message_action_token'] = str_repeat('a', 64);
$c->input->post = array('message_id'=>'6', 'action_token'=>str_repeat('a',64)); $c->recall(9);
verify_recall($c->output->code === 403 && $c->db->rows[6]['deleted_at'] === null, 'Forged POST cannot recall peer message');
foreach (array(1, 2) as $failure) {
    $c = new RecallController(); $c->db->failQuery = $failure; $c->input->get['ids'] = '5'; $c->recall(9);
    verify_recall($c->output->code === 503 && !$c->session->data, 'Failed read returns service error without token');
}
echo "PASS: $count assertions; recall ownership, tokens, transactions, scoped status, legacy content privacy and mobile tombstones.\n";
