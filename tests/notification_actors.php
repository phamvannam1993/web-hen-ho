<?php
// Run: php tests/notification_actors.php
define('BASEPATH', __DIR__);
class CI_Model {}
function base_url($path) { return '/assets-root/' . $path; }
function site_url($path) { return '/site-root/' . $path; }
require __DIR__ . '/../application/helpers/app_helper.php';
require __DIR__ . '/../application/models/M_notification.php';

class NotificationTestDB {
    public $supports = true, $inserted, $rows = array(), $filters = array(), $joined = false;
    public $user = array('id' => 42, 'nickname' => 'Bằng Lăng', 'display_name' => 'Tên thật');
    function field_exists($column, $table) { return $this->supports; }
    function select($fields) { return $this; }
    function from($table) { return $this; }
    function where($key, $value) { $this->filters[$key] = $value; return $this; }
    function get($table = null) { return $this; }
    function row_array() { return $this->user; }
    function result_array() { return $this->rows; }
    function insert($table, $row) { $this->inserted = $row; }
    function join($table, $condition, $type) { $this->joined = $type === 'left'; return $this; }
    function order_by($field, $direction) { return $this; }
    function limit($limit) { return $this; }
}
class NotificationTestModel extends M_notification { public $db; }
function verify($condition, $message) { if (!$condition) throw new Exception($message); }
$model = new NotificationTestModel();
$model->db = new NotificationTestDB();
foreach (array(
    'like' => 'Bằng Lăng đã thích bạn',
    'match' => 'Bạn và Bằng Lăng đã ghép đôi',
    'message' => 'Bằng Lăng đã gửi tin nhắn cho bạn',
) as $type => $title) {
    $model->push(7, $type, 'Generic title', 'Body', '/destination', 42);
    verify($model->db->inserted['actor_user_id'] === 42, 'Store sender for ' . $type);
    verify($model->db->inserted['user_id'] === 7, 'Preserve recipient for ' . $type);
    verify($model->db->inserted['title'] === $title, 'Use public nickname for ' . $type);
    verify($model->db->inserted['url'] === '/destination', 'Preserve destination');
}
$model->db->rows = array(
    array('id' => 1, 'type' => 'like', 'title' => 'Generic', 'actor_id' => 42,
        'actor_display_name' => 'Tên thật', 'actor_nickname' => 'Bằng Lăng',
        'actor_avatar' => 'uploads/avatar.jpg', 'actor_gender' => 'female'),
    array('id' => 2, 'type' => 'system', 'title' => 'System notification', 'actor_id' => null),
    array('id' => 3, 'type' => 'match', 'title' => 'Deleted user notification', 'actor_id' => null),
);
$rows = $model->for_user(7);
verify($model->db->joined && $model->db->filters['n.user_id'] === 7, 'Join actors while filtering by recipient');
verify($rows[0]['actor']['id'] === 42 && $rows[0]['actor']['name'] === 'Bằng Lăng', 'Correct actor metadata');
verify($rows[0]['actor']['avatar'] === '/assets-root/uploads/avatar.jpg', 'Resolve uploaded avatar');
verify($rows[0]['title'] === 'Bằng Lăng đã thích bạn', 'Refresh title with public name');
verify($rows[1]['actor'] === null && $rows[1]['title'] === 'System notification', 'Keep system notifications');
verify($rows[2]['actor'] === null && $rows[2]['title'] === 'Deleted user notification', 'Keep notification when actor is deleted');
$model->db->rows[0]['actor_avatar'] = '';
verify(strpos($model->for_user(7)[0]['actor']['avatar'], 'avatar-female.svg') !== false, 'Fallback avatar');
$model->db->rows[0]['type'] = 'match';
$model->db->rows[0]['url'] = '/tai-khoan/tin-nhan';
verify($model->for_user(7)[0]['url'] === '/site-root/tai-khoan/tin-nhan?to=42', 'Existing match notification opens actor inbox');
$model->db->rows[0]['actor_id'] = 7;
verify($model->for_user(42)[0]['url'] === '/site-root/tai-khoan/tin-nhan?to=7', 'Other recipient opens opposite partner inbox');
$legacy = new NotificationTestModel();
$legacy->db = new NotificationTestDB();
$legacy->db->supports = false;
$legacy->push(7, 'like', 'Generic', null, null, 42);
verify(!array_key_exists('actor_user_id', $legacy->db->inserted), 'Run safely before migration');
verify($legacy->db->inserted['title'] === 'Bằng Lăng đã thích bạn', 'Include name before migration');
echo "Notification actor tests passed.\n";
