<?php
// Run: php tests/profile_login_render.php
define('BASEPATH', __DIR__);
class CI_Controller {}
require __DIR__ . '/../application/core/MY_Controller.php';
class RenderAuth { public $user; function user() { return $this->user; } function check() { return $this->user !== null; } function id() { return $this->user['id']; } }
class RenderInteraction {
    function my_likes_count($id) { return 10; }
    function liked_me_count($id) { return 2; }
    function unread_count($id) { return 3; }
}
class RenderBadges {
    public $counts = array('interest' => 10, 'liked' => 2, 'viewers' => 1, 'msg' => 3, 'noti' => 4, 'total' => 17);
    function menu_counts($id) { return $this->counts; }
}
class RenderOutput {
    public $headers = array();
    function set_header($header) { $this->headers[] = $header; return $this; }
}
class RenderLoader {
    public $data;
    function model($name) {}
    function view($view, $data) { $this->data = $data; }
}
class ProfileRenderController extends MY_Controller {
    public $auth, $output, $load, $m_interaction, $m_interest_badge;
    function __construct() {
        $this->auth = new RenderAuth();
        $this->output = new RenderOutput();
        $this->load = new RenderLoader();
        $this->m_interaction = new RenderInteraction();
        $this->m_interest_badge = new RenderBadges();
    }
    function profile($cached_user, $override = array()) {
        $this->data = array('user' => $cached_user, 'unread_noti' => 4);
        $this->render('members/profile', $override);
    }
}
function verify($condition, $message) { if (!$condition) throw new Exception($message); }
$controller = new ProfileRenderController();
$member = array('id' => 7, 'display_name' => 'Viewer');
$controller->auth->user = $member;
$controller->profile(null);
verify($controller->load->data['user'] === $member, 'Logged-in viewer replaces stale guest data');
verify($controller->load->data['account_badge_total'] === 17, 'Header uses total without duplicate interest subcategories');
$controller->profile(null, array('tk' => array('interest' => 10, 'liked' => 1, 'msg' => 2, 'noti' => 3)));
verify($controller->load->data['account_badge_total'] === 17 && $controller->load->data['tk']['interest'] === 10, 'Account page refreshes menu and header from same unread counts');
verify(in_array('Cache-Control: private, no-store, no-cache, must-revalidate', $controller->output->headers), 'Prevent caching personalized HTML including guest state');
$controller->profile(null, array('user' => null, 'matched' => true));
verify($controller->load->data['user'] === $member && $controller->load->data['matched'], 'Current auth wins without changing profile state');
$controller->auth->user = null;
$controller->profile($member);
verify($controller->load->data['user'] === null, 'Logged-out viewer does not inherit stale authenticated data');
verify($controller->load->data['account_badge_total'] === 0, 'Guests have no account badge');
verify($controller->load->data['content_view'] === 'members/profile', 'Correct profile view');
echo "PASS: current session used for profile render; stale guest/logged-in data discarded; HTML caching disabled.\n";
