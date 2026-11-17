<?php
// Run: php tests/interest_cards_render.php
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
function site_url($path = '') { return '/' . $path; }
function base_url($path = '') { return '/' . $path; }
function setting($key, $default = '') { return $default; }
class InterestCardPurchases
{
    public function where($key, $value) { return $this; }
    public function get($table) { return $this; }
    public function result_array() { return array(array('owner_id' => 3)); }
}
$instance = (object) array('db' => new InterestCardPurchases());
function &get_instance() { global $instance; return $instance; }
require FCPATH . 'application/helpers/app_helper.php';
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function render_interest_card($id, $paid, $hide_price, $type = '')
{
    $user = array('id' => 1);
    $m = array(
        'id' => $id, 'slug' => 'member-' . $id, 'display_name' => '<Test Member>',
        'birthday' => '1995-01-01', 'last_active_at' => null, 'created_at' => null,
        'avatar' => null, 'gender' => 'female', 'province_name' => 'Test Province',
        'charge_enabled' => $paid, 'marital_status' => 'doc_than', 'height_cm' => 167,
        'bio' => '', 'liked_at' => '2026-01-01 12:00:00', 'viewed_at' => '2026-01-01 12:00:00',
    );
    $dating_card = $hide_actions = true;
    $hide_profile_price = $hide_price;
    $interest_card_type = $type;
    ob_start();
    include FCPATH . 'application/views/members/_card.php';
    return ob_get_clean();
}
foreach (array(2 => true, 3 => true, 4 => false) as $id => $paid) {
    $html = render_interest_card($id, $paid, true);
    if (str_contains($html, 'is-profile-paid') !== $paid) {
        throw new Exception('Paid profiles must retain their gold border, including purchased profiles');
    }
    if (str_contains($html, 'profile-price-label') || str_contains($html, '🔒')) {
        throw new Exception('Interest cards must not render a price or lock badge');
    }
    if (!str_contains($html, 'data-profile-preview-card="member-' . $id . '"')) {
        throw new Exception('The normal profile preview and purchase flow must remain available');
    }
    if (!str_contains($html, 'Test Province') || !str_contains($html, '&lt;Test Member&gt;')) {
        throw new Exception('Cards must show location and escape member names');
    }
    if (str_contains($html, '167 cm') !== ($id !== 2)) {
        throw new Exception('Hiding the price must not expose unpaid profile details');
    }
}
if (!str_contains(render_interest_card(2, true, false), 'profile-price-label')) {
    throw new Exception('The dating card must still render its price badge by default');
}
foreach (array('request', 'matched', 'viewed', 'liked') as $type) {
    $html = render_interest_card(2, true, true, $type);
    if (!str_contains($html, 'is-profile-paid') || str_contains($html, 'profile-price-label')) {
        throw new Exception('Every interest tab must retain gold borders without price badges');
    }
    if (str_contains($html, 'data-like-request="2"') !== ($type === 'request')) {
        throw new Exception('Only incoming likes may be removed through reply actions');
    }
    foreach (array('<button', 'pcard-actions', 'tk-lk-card-actions', 'btn-swipe', 'data-like-reply', 'data-card-action', 'data-chat-with') as $control) {
        if (str_contains($html, $control)) {
            throw new Exception('Interest cards must render no action controls: ' . $type);
        }
    }
    if (!str_contains($html, 'data-profile-preview-card="member-2"')) {
        throw new Exception('Tapping the card must still open the profile preview');
    }
}
echo "PASS: free, paid and purchased interest cards; hidden prices, gold borders, profile preview and detail permissions.\n";
echo "PASS: all four interest tabs have no action controls and retain profile preview.\n";
