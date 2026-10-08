<?php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_interaction.php';
function excerpt($text, $length) { return $text; }
function site_url($path) { return $path; }
class MessageDb {
    public $status = 'active', $preference = 'all', $conversation = null, $table, $inserts = array();
    public function where($key, $value = null) { return $this; }
    public function select($sql) { return $this; }
    public function get($table) { $this->table = $table; return $this; }
    public function row_array() {
        if ($this->table === 'likes') return null;
        if ($this->table === 'users') return $this->status === null ? null : array('id' => 2, 'status' => $this->status);
        if ($this->table === 'user_preferences') return array('allow_message' => $this->preference);
        return $this->conversation;
    }
    public function insert($table, $data) {
        $this->inserts[] = $table;
        if ($table === 'conversations') $this->conversation = array('id' => 9);
    }
    public function insert_id() { return 9; }
    public function update($table, $data) { return true; }
}
class MessageAuth { public $vip = false; public function is_vip() { return $this->vip; } }
class MessageLoad { public function model($name) {} public function library($name) {} }
class MessageNotification { public function push(...$args) {} }
class MessageEmail { public function new_message(...$args) {} }
class MessageInteraction extends M_interaction {
    public $db, $auth, $load, $m_notification, $emailer, $blocked = false;
    public function is_blocked($a, $b) { return $this->blocked; }
    public function is_matched($a, $b) { throw new Exception('Messaging must not query match status'); }
    public function like_profile_error($id) { return null; }
    public function check_match($a, $b) { return false; }
    public function count_likes($type, $id) { return 1; }
}
function check_message($condition, $message) { if (!$condition) throw new Exception($message); }
$model = new MessageInteraction();
$model->db = new MessageDb(); $model->auth = new MessageAuth(); $model->load = new MessageLoad();
$model->m_notification = new MessageNotification(); $model->emailer = new MessageEmail();
check_message($model->conversation_with(1, 2)['id'] === 9, 'Create conversation without match');
$model->db->conversation = null;
check_message($model->toggle_like(1, 'user', 2)['liked'], 'Like adds contact');
check_message($model->db->conversation['id'] === 9, 'Like creates conversation before first message');
check_message($model->send_message(1, 2, 'Hello')['ok'], 'Send without match');
$model->db->preference = 'matched';
check_message($model->send_message(1, 2, 'Hello again')['ok'], 'Legacy matched preference no longer requires match');
$model->db->preference = 'vip';
check_message(!$model->send_message(1, 2, 'Hello')['ok'], 'VIP restriction preserved');
$model->auth->vip = true;
check_message($model->send_message(1, 2, 'Hello')['ok'], 'VIP sender allowed');
$model->blocked = true;
check_message(!$model->send_message(1, 2, 'Hello')['ok'], 'Blocked sender denied');
check_message($model->conversation_with(1, 2) === null, 'Blocked conversation open denied');
$model->blocked = false;
foreach (array('banned', 'pending', null) as $status) {
    $model->db->status = $status;
    check_message(!$model->send_message(1, 2, 'Hello')['ok'], 'Unavailable receiver denied');
}
check_message($model->message_permission(1, 1) !== null, 'Self messaging denied');
echo "PASS: create/send without matches, legacy preference, VIP restrictions, blocks and unavailable recipients.\n";
