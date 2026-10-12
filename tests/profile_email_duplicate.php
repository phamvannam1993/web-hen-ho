<?php
// Run: php tests/profile_email_duplicate.php
define('BASEPATH', __DIR__);
class Member_Controller { protected $data = array(); }
require __DIR__ . '/../application/controllers/Account.php';
class DuplicateEmailAuth {
    public function id() { return 7; }
    public function user() { return array('email' => 'original@example.com'); }
}
class DuplicateEmailUsers {
    public $args;
    public function email_exists($email, $ignore_id) {
        $this->args = array($email, $ignore_id);
        return $email === 'taken@example.com';
    }
}
class DuplicateEmailValidation {
    public $message;
    public function set_message($rule, $message) { $this->message = $message; }
}
class DuplicateEmailAccount extends Account {
    public $auth, $m_user, $form_validation;
    public function __construct() {
        $this->auth = new DuplicateEmailAuth();
        $this->m_user = new DuplicateEmailUsers();
        $this->form_validation = new DuplicateEmailValidation();
    }
    public function view_data() { return $this->data; }
}
function assert_duplicate($condition, $message) {
    if (!$condition) throw new Exception($message);
}
$account = new DuplicateEmailAccount();
assert_duplicate(!$account->email_hop_le('taken@example.com'), 'Duplicate email blocks saving');
assert_duplicate($account->m_user->args === array('taken@example.com', 7), 'Exclude current account from duplicate check');
$email_bi_trung = $account->view_data()['email_bi_trung'];
assert_duplicate($email_bi_trung, 'Tell profile view to restore original email');
assert_duplicate(strpos($account->form_validation->message, 'giữ nguyên') !== false, 'Explain original email is retained');
// Execute the actual profile value helpers to verify the displayed value after rejection.
$view = file_get_contents(__DIR__ . '/../application/views/account/profile.php');
$start = strpos($view, '$goc = function');
$end = strpos($view, '$v   = function', $start);
$da_gui = true;
$me = $account->auth->user();
$pref = array();
$_POST = array('email' => 'taken@example.com', 'display_name' => 'Edited name');
eval(substr($view, $start, $end - $start));
assert_duplicate($goc('email') === 'original@example.com', 'Display original email after duplicate rejection');
assert_duplicate($goc('display_name') === 'Edited name', 'Keep other profile edits');
assert_duplicate($account->email_hop_le('original@example.com'), 'Allow unchanged email');
echo "PASS: duplicate email blocked, clear warning set, original email restored while other edits retained.\n";
