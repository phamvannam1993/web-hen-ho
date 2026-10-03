<?php
// Run: php tests/profile_login_render.php
define('BASEPATH', __DIR__);
class CI_Controller {}
require __DIR__ . '/../application/core/MY_Controller.php';
class RenderAuth { public $user; function user() { return $this->user; } }
class RenderOutput {
    public $headers = array();
    function set_header($header) { $this->headers[] = $header; return $this; }
}
class RenderLoader {
    public $data;
    function view($view, $data) { $this->data = $data; }
}
class ProfileRenderController extends MY_Controller {
    public $auth, $output, $load;
    function __construct() {
        $this->auth = new RenderAuth();
        $this->output = new RenderOutput();
        $this->load = new RenderLoader();
    }
    function profile($cached_user, $override = array()) {
        $this->data = array('user' => $cached_user);
        $this->render('members/profile', $override);
    }
}
function verify($condition, $message) { if (!$condition) throw new Exception($message); }
$controller = new ProfileRenderController();
$member = array('id' => 7, 'display_name' => 'Viewer');
$controller->auth->user = $member;
$controller->profile(null);
verify($controller->load->data['user'] === $member, 'Logged-in viewer replaces stale guest data');
verify(in_array('Cache-Control: private, no-store, no-cache, must-revalidate', $controller->output->headers), 'Prevent caching personalized HTML including guest state');
$controller->profile(null, array('user' => null, 'matched' => true));
verify($controller->load->data['user'] === $member && $controller->load->data['matched'], 'Current auth wins without changing profile state');
$controller->auth->user = null;
$controller->profile($member);
verify($controller->load->data['user'] === null, 'Logged-out viewer does not inherit stale authenticated data');
verify($controller->load->data['content_view'] === 'members/profile', 'Correct profile view');
echo "PASS: current session used for profile render; stale guest/logged-in data discarded; HTML caching disabled.\n";
