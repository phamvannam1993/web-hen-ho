<?php
// Run: php tests/dating_distance.php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_user.php';
class DistanceDb {
    public $ready = true, $selects = array(), $orders = array(), $conditions = array();
    public function field_exists($field, $table) { return $this->ready; }
    public function select($sql, $escape = true) { $this->selects[] = $sql; return $this; }
    public function order_by($sql, $direction = '', $escape = true) { $this->orders[] = array($sql, $direction); return $this; }
    public function where($sql, $value = null, $escape = true) { $this->conditions[] = $sql; return $this; }
    public function from($table) { return $this; }
    public function join($table, $on, $type) { return $this; }
    public function limit($limit, $offset) { return $this; }
    public function get() { return $this; }
    public function result_array() { return array(); }
    public function count_all_results() { return 0; }
}
class DistanceAuth { public $logged_in = false; public function check() { return $this->logged_in; } public function id() { return 7; } }
class DistanceLoad { public function model($model) {} }
class DistanceSettings { public function all() { return array('only_online' => 0); } }
class DistanceUser extends M_user { public $db, $auth, $load, $m_setting; }
function verify_distance($condition, $message) { if (!$condition) throw new Exception($message); }
$user = new DistanceUser();
$user->db = new DistanceDb();
$user->auth = new DistanceAuth();
$user->load = new DistanceLoad();
$user->m_setting = new DistanceSettings();
$origin = array('lat' => 21.028511, 'lng' => 105.804817, 'real' => false);
$filters = array('sort' => 'nearby', 'distance_origin' => $origin, 'distance_max' => 100);
$user->search($filters, 24, 24);
verify_distance(strpos($user->db->orders[0][0], 'IS NULL') !== false, 'Unknown distances must sort last');
verify_distance($user->db->orders[1][1] === 'ASC', 'Nearest first before pagination');
verify_distance(strpos(implode(' ', $user->db->selects), '0 AS distance_real') !== false, 'Province origin is always estimated');
$where = $user->db->conditions;
$user->db->conditions = array();
$user->count_search($filters);
verify_distance($where === $user->db->conditions, 'Count and results apply identical radius filters');
$user->auth->logged_in = true;
$user->db->conditions = array();
$user->count_search($filters);
verify_distance(strpos(implode(' ', $user->db->conditions), "browse_like.user_id = 7 AND browse_like.target_type = 'user'") !== false, 'Browse excludes only profiles liked by current viewer before counting');
$user->auth->logged_in = false;
$sql = $user->distance_sql($origin);
verify_distance(strpos($sql, 'location_updated_at') !== false && strpos($sql, '30 DAY') !== false, 'Only recent consented coordinates count as real');
verify_distance(strpos($sql, 'u.lat, COALESCE(p.lat, CASE p.slug') !== false, 'Unverified synthetic coordinates fall back to province');
verify_distance(strpos($sql, 'GREATEST(-1, LEAST(1,') !== false, 'Clamp floating point error before ACOS');
$user->db->selects = array();
$origin['real'] = true;
$user->search(array('sort' => 'active', 'distance_origin' => $origin));
verify_distance(strpos(implode(' ', $user->db->selects), 'u.location_updated_at') !== false, 'Real label requires both users to have real coordinates');
verify_distance($user->distance_sql(null) === 'NULL', 'No origin gives unknown distance');
$user->db->ready = false;
verify_distance(strpos($user->distance_sql($origin), 'u.lat') === false, 'Unmigrated databases estimate without referencing missing user coordinates');
verify_distance(strpos($user->distance_sql($origin), "WHEN 'hung-yen'") !== false, 'Province estimates work before migration');
verify_distance($user->province_coordinates(array('slug' => 'ha-noi')) === array(21.028511, 105.804817), 'Origin coordinates fall back by slug');
echo "PASS: nearest ordering, radius/count consistency, coordinate source, stale locations and missing data.\n";
