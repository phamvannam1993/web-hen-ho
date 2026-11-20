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
