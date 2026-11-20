<?php
// Run: php tests/home_mobile_render.php
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
function site_url($path = '') { return '/' . $path; }
function base_url($path = '') { return '/' . $path; }
function setting($key, $default = '') { return $default; }
class HomeCardPurchases
{
    public function where($key, $value) { return $this; }
    public function get($table) { return $this; }
    public function result_array() { return array(array('owner_id' => 3)); }
}
$instance = (object) array('db' => new HomeCardPurchases());
function &get_instance() { global $instance; return $instance; }
require FCPATH . 'application/helpers/app_helper.php';
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function home_card_fixture($id, $paid, $guest = false)
{
    $user = $guest ? null : array('id' => 1);
    $home_label = 'Cùng gu';
    $m = array('id' => $id, 'slug' => 'member-' . $id, 'display_name' => '<Member>',
        'birthday' => '1995-01-01', 'last_active_at' => null, 'avatar' => null,
        'gender' => 'female', 'province_name' => 'Hà Nội', 'charge_enabled' => $paid,
        'job' => 'Private job', 'interest_names' => 'Private interest');
    if (!$guest) $m['match_score'] = 100;
    ob_start();
    include FCPATH . 'application/views/home/_mobile_member.php';
    return ob_get_clean();
}
foreach (array(array(2, true, false, false), array(3, true, false, true), array(4, false, false, true), array(2, true, true, false)) as $case) {
    list($id, $paid, $guest, $details) = $case;
    $html = home_card_fixture($id, $paid, $guest);
    if (str_contains($html, 'is-profile-paid') !== $paid) throw new Exception('Paid border must survive purchase');
    foreach (array('Private job', 'Private interest') as $detail) {
        if (str_contains($html, $detail) !== $details) throw new Exception('Respect detail permissions: ' . $detail);
    }
    if (!str_contains($html, '&lt;Member&gt;') || !str_contains($html, 'data-profile-preview-card="member-' . $id . '"')) throw new Exception('Escape names and preserve preview');
    if (str_contains($html, 'home-mobile-compatibility') === $guest) throw new Exception('Guests must not get invented compatibility scores');
    if (str_contains($html, '5.0') || str_contains($html, 'Đang chat')) throw new Exception('Do not invent ratings or chat presence');
}
echo "PASS: mobile home cards preserve permissions, purchased borders, profile preview and real data.\n";

// Render the whole mobile page: the sentinel must follow the list, not the banner.
class MobileHomeFixture
{
    public $load;
    public function __construct() { $this->load = $this; }
    public function view($view, $data) {
        $user = null;
        extract($data);
        include FCPATH . 'application/views/' . $view . '.php';
    }
    public function render($more) {
        $user = null;
        $mobile_members_has_more = $more;
        $mobile_members = array();
        for ($id = 1; $id <= 20; $id++) {
            $mobile_members[] = array('id' => $id, 'slug' => 'member-' . $id,
                'display_name' => 'Member ' . $id, 'birthday' => null,
                'last_active_at' => null, 'avatar' => null, 'gender' => 'female',
                'province_name' => 'Hà Nội', 'charge_enabled' => false);
        }
        ob_start();
        include FCPATH . 'application/views/home/_mobile.php';
        return ob_get_clean();
    }
}
$page = new MobileHomeFixture();
$html = $page->render(true);
if (substr_count($html, 'class="hm-sug"') !== 15) throw new Exception('Render fifteen initial suggestions');
if (substr_count($html, 'data-home-load-more ') !== 1
    || strpos($html, 'data-home-load-more ') < strpos($html, 'data-profile-preview-card="member-20"')
    || !str_contains($html, 'data-offset="20"')) throw new Exception('Pagination sentinel must follow the full suggestion list');
if (str_contains($page->render(false), 'data-home-load-more ')) throw new Exception('Do not offer loading when exhausted');
echo "PASS: mobile home renders the full list and only offers pagination after the suggestions when more profiles exist.\n";
