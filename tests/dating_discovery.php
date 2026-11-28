<?php
// Run: php tests/dating_discovery.php
define('BASEPATH', __DIR__);
class MY_Controller {
    public $data, $input, $session, $m_user, $rendered, $db;
    public function render($view, $data) { $this->rendered = $data; }
    public function render_member_list($view, $page, $data) { $this->render($view, $data); }
}
require __DIR__ . '/../application/controllers/Dating.php';
class DiscoveryInput {
    public $query = array();
    public function get($key = null) { return $key === null ? $this->query : ($this->query[$key] ?? null); }
}
class DiscoverySession {
    public $values = array();
    public function userdata($key) { return $this->values[$key] ?? null; }
    public function set_userdata($key, $value) { $this->values[$key] = $value; }
}
class DiscoveryModel {
    public $count_filters, $search_filters;
    public function location_ready() { return true; }
    public function province_coordinates($province) { return array(21.0, 105.0); }
    public function dating_hero_members() { return array(); }
    public function count_search($filters) { $this->count_filters = $filters; return 0; }
    public function search($filters, $limit, $offset) { $this->search_filters = $filters; return array(); }
}
function pagination_links(...$args) { return ''; }
function check_discovery($condition, $message) { if (!$condition) throw new Exception($message); }
$controller = (new ReflectionClass(Dating::class))->newInstanceWithoutConstructor();
$controller->data = array('settings' => array(), 'user' => null, 'provinces' => array(array('id' => 1, 'name' => 'Hà Nội')));
$controller->input = new DiscoveryInput();
$controller->session = new DiscoverySession();
$controller->m_user = new DiscoveryModel();
$controller->input->query = array('q' => '  Hà Nội  ', 'gender' => 'female', 'age_min' => '45', 'age_max' => '25', 'marital' => 'ly_hon', 'province_id' => '1', 'distance_max' => '50', 'sort' => 'new');
$controller->index();
$filters = $controller->m_user->search_filters;
check_discovery($filters === $controller->m_user->count_filters, 'Count and results must apply identical filters');
check_discovery($filters['keyword'] === 'Hà Nội' && $filters['gender'] === 'female' && $filters['marital'] === 'ly_hon', 'Text, gender and marital filters reach the model');
check_discovery($filters['age_min'] === 25 && $filters['age_max'] === 45, 'Reversed age range is normalized');
check_discovery($filters['distance_origin']['real'] === false && $filters['distance_max'] === 50, 'Selected province supports estimated radius');
check_discovery($controller->rendered['heading'] === 'Cộng đồng tìm kiếm đối tượng hẹn hò và bạn bè', 'Main heading matches the discovery page');
$controller->input->query = array('gender' => 'invalid', 'marital' => 'invalid', 'age_min' => '2', 'age_max' => '999');
$controller->index();
$filters = $controller->m_user->search_filters;
foreach (array('gender', 'marital', 'age_min', 'age_max') as $key) {
    check_discovery(!isset($filters[$key]), 'Invalid ' . $key . ' is discarded');
}
$controller->session->values['dating_guest_location'] = array('lat' => 10.7, 'lng' => 106.7, 'updated_at' => time());
$controller->index();
check_discovery($controller->m_user->search_filters['distance_origin']['real'] === true, 'Guest location is used during its session lifetime');
$controller->session->values['dating_guest_location']['updated_at'] = time() - 90000;
$controller->index();
check_discovery($controller->m_user->search_filters['distance_origin'] === null, 'Expired guest location is ignored');
define('FCPATH', __DIR__ . '/../');
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function site_url($path = '') { return '/' . $path; }
function base_url($path = '') { return '/' . $path; }
class DiscoverySecurity {
    public function get_csrf_token_name() { return 'csrf'; }
    public function get_csrf_hash() { return 'token'; }
}
class DiscoveryView {
    public $security, $input;
    public function render($data) {
        extract($data);
        $sorts = array('nearby' => 'Gần bạn nhất', 'new' => 'Mới tham gia');
        $provinces = array(array('id' => 1, 'name' => 'Hà Nội'));
        ob_start();
        include __DIR__ . '/../application/views/dating/_discovery.php';
        return ob_get_clean();
    }
}
$view = new DiscoveryView();
$view->security = new DiscoverySecurity();
$view->input = $controller->input;
$html = $view->render($controller->rendered);
foreach (array('q', 'gender', 'distance_max', 'province_id', 'age_min', 'age_max', 'marital', 'sort') as $key) {
    check_discovery(strpos($html, 'name="' . $key . '"') !== false, 'Rendered form contains ' . $key);
}
check_discovery(substr_count($html, '<h1>') === 1, 'Discovery has exactly one H1');
check_discovery(strpos($html, 'id="dating-location"') !== false, 'Location button is rendered for guests');
echo "PASS: discovery filters, count consistency, age normalization, province radius, heading and guest location expiry.\n";

$controller->data['user'] = array('id'=>7,'province_id'=>1);
$controller->input->query = array();
$controller->members();
check_discovery($controller->rendered['directory_mode'] && $controller->rendered['base_url'] === 'thanh-vien', 'Directory keeps its own base URL and does not redirect by gender preference');
$controller->input->query = array('gender'=>'female','q'=>' Trang ','sort'=>'listened','has_children'=>'0','height_min'=>'150','interests'=>array('2','3','0'));
$controller->members(2);
$directory = $controller->rendered;
check_discovery($directory['filters']['keyword'] === 'Trang' && $directory['filters']['gender'] === 'female', 'Directory preserves name and gender filters');
check_discovery($directory['sort'] === 'listened' && $directory['filters']['has_children'] === '0'
    && $directory['filters']['height_min'] === '150' && $directory['filters']['interests'] === array(2,3), 'Existing directory filters and sorting remain supported');
check_discovery($directory['filters'] === $controller->m_user->count_filters, 'Directory count and results share filters');
$html = $view->render($directory);
check_discovery(strpos($html,'community-page-banner')!==false && strpos($html,'member-banner.jpg')!==false, 'Directory retains the top banner');
check_discovery(strpos($html,'Khám phá các thành viên')===false, 'Directory omits the extra heading');
check_discovery(strpos($html,'<form')===false && strpos($html,'dating-location.js')===false, 'Directory omits search/filter controls and their script');
$route = array(); require __DIR__.'/../application/config/routes.php';
check_discovery($route['thanh-vien']==='dating/members' && $route['thanh-vien/trang/(:num)']==='dating/members/$1'
    && $route['thanh-vien/(:any)']==='members/legacy_profile/$1','Directory pagination switches together while legacy profile URLs remain intact');
echo "PASS: member directory uses the shared discovery UI, keeps routes, filters, pagination and location handling.\n";
