<?php
// Run: php tests/email_verification_resend.php
define('BASEPATH', __DIR__);
define('ENVIRONMENT', 'production');
class MY_Controller {}
class M_otp { const GIO_SONG_LINK = 48; }
require __DIR__ . '/../application/controllers/Auth.php';
function redirect($url) { throw new RuntimeException($url); }
function set_flash($type, $text) { $GLOBALS['flash'] = array($type, $text); }
function site_url($path) { return 'https://example.test/' . $path; }
function setting($key, $default) { return $default; }
function display_name($user) { return 'Member'; }
function mask_email($email) { return $email; }
class ResendAuth {
    public function check() { return true; }
    public function id() { return 7; }
    public function da_xac_thuc() { throw new Exception('Do not use feature exemption for email verification'); }
}
class ResendUsers {
    public $user = array('id' => 7, 'email' => 'new@example.com', 'email_verified_at' => null, 'role' => 'admin');
    public function find($id) { return $this->user; }
}
class ResendInput {
    public $method = 'post';
    public function method() { return $this->method; }
    public function post($key) { return $key === 'from_profile' ? '1' : null; }
}
class ResendOtp {
    public $wait = 0, $created = 0;
    public function con_cho($id, $purpose) { return $this->wait; }
    public function tao_link($id) { ++$this->created; return 'test-token'; }
}
class ResendMailer {
    public $recipients = array();
    public function send($email, $subject, $view, $data) { $this->recipients[] = $email; return true; }
}
class ResendController extends Auth {
    public $auth, $m_user, $input, $m_otp, $mailer, $rendered;
    public function __construct() {
        $this->auth = new ResendAuth(); $this->m_user = new ResendUsers();
        $this->input = new ResendInput(); $this->m_otp = new ResendOtp(); $this->mailer = new ResendMailer();
    }
    public function render($view, $data) { $this->rendered = $view; }
}
function expect_resend($ok, $message) { if (!$ok) throw new Exception($message); }
function run_resend($controller, $expected) {
    try { $controller->resend(); throw new Exception('Missing redirect'); }
    catch (RuntimeException $error) { expect_resend($error->getMessage() === $expected, 'Correct destination'); }
}
$controller = new ResendController();
$controller->verify();
expect_resend($controller->rendered === 'auth/check_email', 'Unverified admin sees verification page');
run_resend($controller, 'tai-khoan/ho-so');
expect_resend($controller->mailer->recipients === array('new@example.com'), 'Send to current saved email');
$controller->m_otp->wait = 30;
run_resend($controller, 'tai-khoan/ho-so');
expect_resend(count($controller->mailer->recipients) === 1 && $GLOBALS['flash'][0] === 'warning', 'Respect cooldown');
$controller->m_user->user['email_verified_at'] = '2026-10-03 12:00:00';
run_resend($controller, 'tai-khoan/ho-so');
expect_resend(count($controller->mailer->recipients) === 1, 'Do not resend verified email');
$controller->m_user->user['email'] = null;
run_resend($controller, 'tai-khoan/ho-so');
expect_resend($GLOBALS['flash'][0] === 'warning', 'Missing email asks user to save profile');
$controller->input->method = 'get';
run_resend($controller, 'xac-thuc');
expect_resend($controller->m_otp->created === 1, 'GET never creates a token');
echo "PASS: actual email verification state, saved recipient, cooldown, verified/missing email and POST-only resend.\n";
