<?php
// Run: php tests/message_email_privacy.php
define('BASEPATH', __DIR__);
class CI_Controller {}
function site_url($path) { return 'https://example.test/' . $path; }
function base_url($path) { return 'https://example.test/' . $path; }
function &get_instance() { global $emailTestCI; return $emailTestCI; }
require __DIR__ . '/../application/helpers/app_helper.php';
require __DIR__ . '/../application/libraries/Emailer.php';
require __DIR__ . '/../application/controllers/Cron.php';
class EmailTestLoader {
    function model($names) {}
    function view($path, $data) { extract($data); include __DIR__ . '/../application/views/' . $path . '.php'; }
}
class EmailTestQueue {
    public $rows = array(), $allowed = true;
    function enqueue($id, $type, $subject, $view, $payload, $opts) {
        $this->rows[] = compact('id', 'type', 'subject', 'view', 'payload', 'opts');
        return count($this->rows);
    }
    function da_gui_gan_day() { throw new Exception('Must not throttle message notifications'); }
    function duoc_gui($id, $type) { return $this->allowed; }
}
class EmailTestRenderer {
    public $load;
    function render($data) {
        extract($data);
        ob_start();
        include __DIR__ . '/../application/views/emails/new_message.php';
        return ob_get_clean();
    }
}
class EmailTestCron extends Cron {
    public $m_email, $m_user;
    function __construct() {}
}
class EmailTestRecipient {
    function find($id) { return array('id' => $id, 'status' => 'active', 'deleted_at' => null, 'email' => 'member@example.test'); }
}
function verify($ok, $label) { if (!$ok) throw new Exception($label); }
$emailTestCI = (object) array('load' => new EmailTestLoader(), 'm_email' => new EmailTestQueue());
$emailer = new Emailer();
$sender = array('display_name' => 'Tên thật', 'nickname' => 'Bằng Lăng', 'avatar' => 'uploads/avatar.jpg', 'gender' => 'female');
$emailer->new_message(7, $sender, 42);
$emailer->new_message(7, $sender, 42);
verify(count($emailTestCI->m_email->rows) === 2, 'Each message queues an email even in the same conversation');
foreach ($emailTestCI->m_email->rows as $row) {
    verify($row['id'] === 7 && $row['type'] === 'new_message', 'Correct recipient and email type');
    verify($row['payload']['sender'] === 'Bằng Lăng', 'Use public name');
    verify($row['payload']['avatar'] === 'https://example.test/uploads/avatar.jpg', 'Sender avatar');
    verify($row['payload']['link'] === 'https://example.test/tai-khoan/tin-nhan/42', 'Open exact inbox');
    verify(!isset($row['payload']['preview']) && !isset($row['payload']['content']), 'No message content in queue');
    verify(empty($row['opts']['delay_minutes']), 'Ready for next worker run');
}
$renderer = new EmailTestRenderer(); $renderer->load = new EmailTestLoader();
$payload = $emailTestCI->m_email->rows[0]['payload'];
$payload['preview'] = 'PRIVATE_MESSAGE_MUST_NOT_APPEAR';
$html = $renderer->render($payload);
verify(strpos($html, $payload['preview']) === false, 'Legacy queued previews never appear in email');
verify(strpos($html, 'Xem tin nhắn') !== false && strpos($html, $payload['avatar']) !== false, 'Email includes avatar and CTA');
$emailer->new_message(7, array('display_name' => 'Bạn mới', 'gender' => 'male'), 42);
verify(strpos($emailTestCI->m_email->rows[2]['payload']['avatar'], 'avatar-male.svg') !== false, 'Fallback avatar');
$cron = new EmailTestCron(); $cron->m_email = $emailTestCI->m_email;
$cron->m_user = new EmailTestRecipient();
$validate = new ReflectionMethod(Cron::class, 'con_hop_le'); $validate->setAccessible(true);
verify($validate->invoke($cron, array('user_id' => 7, 'type' => 'new_message', 'related_id' => 42, 'to_email' => 'member@example.test')) === true, 'Do not suppress emails for online/read users');
$cron->m_email->allowed = false;
verify($validate->invoke($cron, array('user_id' => 7, 'type' => 'new_message', 'related_id' => 42, 'to_email' => 'member@example.test')) !== true, 'Respect email opt-out');
echo "PASS: per-message emails, sender/avatar, exact inbox link, no previews, no delay, preferences respected.\n";
