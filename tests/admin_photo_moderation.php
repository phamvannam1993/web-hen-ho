<?php
// Run: php tests/admin_photo_moderation.php
define('BASEPATH', __DIR__);
class Admin_Controller {
    public $input, $db, $logs = array();
    protected function log_action($action, $table, $id) { $this->logs[] = array($action, $table, $id); }
}
function show_error($message, $code) { throw new RuntimeException($message, $code); }
function show_404() { throw new RuntimeException('Not found', 404); }
$flash = null; $redirect = null;
function set_flash($type, $message) { global $flash; $flash = array($type, $message); }
function redirect($path) { global $redirect; $redirect = $path; }
require __DIR__ . '/../application/controllers/admin/Photos.php';
class PhotoTestInput {
    public $method = 'post', $status = 'approved';
    function method() { return $this->method; }
    function post($key) { return $this->status; }
    function get($key, $xss = false) { return array('status' => 'pending', 'q' => 'A & B', 'page' => 2)[$key] ?? null; }
}
class PhotoTestDB {
    public $exists = true, $success = true, $writes = array(), $id;
    function select($columns) { return $this; }
    function from($table) { return $this; }
    function join($table, $condition) { return $this; }
    function where($column, $value) { if ($column === 'id') { $this->id = $value; } return $this; }
    function get() { return $this; }
    function row_array() { return $this->exists ? array('id' => 7) : null; }
    function update($table, $values) { $this->writes[] = array($table, $values, $this->id); return $this->success; }
}
function verify($ok, $label) { if (!$ok) { throw new Exception($label); } }
foreach (array('approved', 'rejected') as $status) {
    $controller = new Photos(); $controller->input = new PhotoTestInput(); $controller->db = new PhotoTestDB();
    $controller->input->status = $status;
    $controller->moderate(7);
    verify($controller->db->writes === array(array('user_photos', array('status' => $status), 7)), 'Correct photo/status');
    verify(count($controller->logs) === 1 && $flash[0] === 'success', 'Audit and confirmation');
    verify($redirect === 'admin/photos/trang/2?status=pending&q=A+%26+B', 'Preserve filters and page safely');
}
foreach (array('get', 'invalid', 'missing') as $scenario) {
    $controller = new Photos(); $controller->input = new PhotoTestInput(); $controller->db = new PhotoTestDB();
    if ($scenario === 'get') { $controller->input->method = 'get'; }
    if ($scenario === 'invalid') { $controller->input->status = 'deleted'; }
    if ($scenario === 'missing') { $controller->db->exists = false; }
    try { $controller->moderate(7); throw new Exception('Expected rejection'); }
    catch (RuntimeException $error) { verify($error->getCode() === array('get' => 405, 'invalid' => 400, 'missing' => 404)[$scenario], 'Correct error'); }
    verify(!$controller->db->writes && !$controller->logs, 'Rejected request must not write');
}
$controller = new Photos(); $controller->input = new PhotoTestInput(); $controller->db = new PhotoTestDB();
$controller->db->success = false; $controller->moderate(7);
verify($flash[0] === 'danger' && !$controller->logs, 'Failed update must not report success');
echo 'PASS: approve/reject, audit, pagination, GET/invalid/missing guards and update failure' . PHP_EOL;
