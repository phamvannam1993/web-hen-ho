<?php
// Run: php tests/profile_email_change.php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_user.php';
class EmailTestDb {
    public $updates = array(), $deleted = array(), $filters = array();
    public function trans_start() {}
    public function trans_complete() {}
    public function where($key, $value) { $this->filters[$key] = $value; return $this; }
    public function where_in($key, $value) { $this->filters[$key] = $value; return $this; }
    public function delete($table) { $this->deleted[] = array($table, $this->filters); $this->filters = array(); }
    public function update($table, $data) { $this->updates[] = array($table, $data, $this->filters); $this->filters = array(); }
}
class EmailTestUser extends M_user {
    public $db;
    public function find($id) { return array('email' => 'old@example.com'); }
    public function recalc_profile_score($id) {}
}
function check_email_test($condition, $message) {
    if (!$condition) throw new Exception($message);
}
$user = new EmailTestUser();
$user->db = new EmailTestDb();
$user->update_profile(7, array('email' => 'new@example.com'));
check_email_test($user->db->updates[0][1]['email'] === 'new@example.com', 'New email saved');
check_email_test(array_key_exists('email_verified_at', $user->db->updates[0][1]) && $user->db->updates[0][1]['email_verified_at'] === null, 'New email must be verified again');
check_email_test($user->db->updates[0][2]['id'] === 7, 'Only the current account is updated');
check_email_test($user->db->deleted === array(array('user_tokens', array('user_id' => 7, 'type' => array('verify_email', 'reset_password', 'otp')))), 'Old email tokens invalidated for this account only');
foreach (array(array('email' => 'old@example.com'), array('display_name' => 'Name')) as $data) {
    $user->db = new EmailTestDb();
    $user->update_profile(7, $data);
    check_email_test(!$user->db->deleted, 'Unchanged email keeps tokens');
    check_email_test(!array_key_exists('email_verified_at', $user->db->updates[0][1]), 'Unchanged email keeps verification');
}
echo "PASS: email change saves new email and resets verification/tokens; unchanged email preserves verification.\n";
