<?php
// Run: php tests/like_profile_requirement.php
define('BASEPATH', __DIR__);
class CI_Model {}
$testInstance = null;
function &get_instance() { global $testInstance; return $testInstance; }
function site_url($path) { return '/' . $path; }
require __DIR__ . '/../application/helpers/app_helper.php';
require __DIR__ . '/../application/models/M_interaction.php';
class LikeTestLoader { function model($name) {} function helper($name) {} }
class LikeTestUser {
    public $user, $missing = array();
    function find($id) { return $this->user; }
    function thieu_thong_tin($id) { return $this->missing; }
}
class LikeTestDB {
    public $interests = 1, $row = null, $writes = 0;
    function where($key, $value = null) { return $this; }
    function get($table) { return $this; }
    function row_array() { return $this->row; }
    function count_all_results($table) { return $this->interests; }
    function insert($table, $data) { $this->writes++; }
    function delete($table) { $this->writes++; }
    function update($table) { $this->writes++; }
    function set($key, $value, $escape = true) { return $this; }
}
class LikeTestModel extends M_interaction {
    public $load, $m_user, $db;
    function count_likes($type, $id) { return 0; }
}
function verify($ok, $label) { if (!$ok) { throw new Exception($label); } }
$model = new LikeTestModel();
$model->load = new LikeTestLoader();
$model->m_user = new LikeTestUser();
$model->db = new LikeTestDB();
$testInstance = $model;
$full = array('id' => 1, 'avatar' => 'avatar.jpg', 'bio' => 'Hello', 'province_id' => 1);
$model->m_user->user = $full;
verify($model->like_profile_error(1) === null, 'Complete profile must qualify');
foreach (array('avatar', 'bio', 'province_id', 'interests', 'required') as $field) {
    $model->m_user->user = $full;
    $model->m_user->missing = $field === 'required' ? array('Ngày sinh') : array();
    $model->db->interests = $field === 'interests' ? 0 : 1;
    if (array_key_exists($field, $full)) { $model->m_user->user[$field] = ''; }
    $model->db->row = null;
    $error = $model->toggle_like(1, 'user', 2);
    verify($error['ok'] === false && $error['need'] === 'profile' && !empty($error['missing']), 'Block missing ' . $field);
    verify($model->db->writes === 0, 'Rejected like must not write to DB');
    $model->db->row = array('id' => 3, 'status' => 'pending');
    $error = $model->respond_like(1, 2, 'accept');
    verify($error['ok'] === false && $error['need'] === 'profile', 'Block like-back for missing ' . $field);
    verify($model->db->writes === 0, 'Rejected like-back must not write');
}
$model->m_user->user = $full;
$model->m_user->missing = array();
$model->db->interests = 1;
$model->db->row = null;
$result = $model->toggle_like(1, 'post', 2);
verify($result['liked'] === true && $model->db->writes === 2, 'Complete profile can like');
$model->m_user->user = null;
$model->db->row = array('id' => 3);
$result = $model->toggle_like(1, 'post', 2);
verify($result['liked'] === false, 'Incomplete profile may withdraw an existing like');
echo 'PASS: completion fields, rejected likes/like-back without writes, complete profile and unlike' . PHP_EOL;
