<?php
// Run: php tests/account_overview_render.php. Fixtures only, no real account access.
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
function site_url($path = '') { return '/' . $path; }
function base_url($path = '') { return '/' . $path; }
function setting($key, $default = '') { return $default; }
class OverviewFixtureDb {
    public function where($field, $value) { return $this; }
    public function count_all_results($table) { return 2; }
}
$instance = (object)array('db' => new OverviewFixtureDb());
function &get_instance() { global $instance; return $instance; }
require FCPATH . 'application/helpers/app_helper.php';
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function render_account_overview($overrides = array(), $posts = false)
{
    $me = array_merge(array('id'=>7, 'slug'=>'member-7', 'display_name'=>'Nguyễn Minh Anh <test>',
        'avatar'=>null, 'gender'=>'female', 'bio'=>'Xin chào', 'province_id'=>1,
        'status'=>'active', 'role'=>'member', 'is_vip'=>0, 'vip_expired_at'=>null,
        'coin_balance'=>1234, 'email_verified_at'=>null), $overrides);
    $tk = array('interest'=>3, 'msg'=>4, 'viewers'=>2);
    $streak = array('streak'=>12);
    $liked_count=45; $my_liked_count=103; $match_count=26; $viewer_count=0;
    $unread_msg=4; $unread_noti=6; $post_count=9;
    $settings = array('enable_posts'=>$posts);
    ob_start(); require FCPATH . 'application/views/account/index.php'; return ob_get_clean();
}
function overview_check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$html = render_account_overview();
overview_check(str_contains($html,'Nguyễn Minh Anh &lt;test&gt;') && !str_contains($html,'Anh <test>'), 'Names must be escaped');
foreach (array('45','103','26','0') as $value) overview_check(str_contains($html,'<strong>'.$value.'</strong>'), 'Connection totals must use full supplied counts');
foreach (array('/tai-khoan/ho-so','/tai-khoan/anh','/messenger','/tai-khoan/nap-xu','/tai-khoan/chuoi',
    '/tai-khoan/email','/tai-khoan/doi-mat-khau','/dang-xuat','tab=ban-thich','tab=ghep-doi') as $url) {
    overview_check(str_contains($html,$url),'Preserve feature destination '.$url);
}
overview_check(str_contains($html,'12 ngày liên tiếp') && str_contains($html,'1,234 xu'), 'Use real streak and total spendable coins');
overview_check(str_contains($html,'Nâng cấp VIP') && !str_contains($html,'VIP đang hiệu lực'), 'Ordinary member must not be shown as VIP');
overview_check(!str_contains($html,'Tin đăng của tôi') && str_contains(render_account_overview(array(), true),'Tin đăng của tôi'), 'Post menu obeys feature flag');
$vip = render_account_overview(array('is_vip'=>1,'vip_expired_at'=>date('Y-m-d H:i:s',time()+86400),'email_verified_at'=>'2026-11-25'));
overview_check(str_contains($vip,'VIP đang hiệu lực') && str_contains($vip,'Đã xác thực email'), 'Active VIP and verification reflect account state');
$expired = render_account_overview(array('is_vip'=>1,'vip_expired_at'=>'2020-01-01 00:00:00'));
overview_check(str_contains($expired,'Nâng cấp VIP') && !str_contains($expired,'VIP đang hiệu lực'), 'Expired VIP does not get active status');
$admin = render_account_overview(array('role'=>'super_admin'));
overview_check(str_contains($admin,'∞ xu') && str_contains($admin,'không thời hạn'), 'Unlimited administrator benefits remain accurate');
foreach (array_keys(profile_frame_catalog()) as $frame) {
    $framed = render_account_overview(array('profile_frame'=>$frame));
    overview_check($frame === 'none' ? !str_contains($framed,'has-frame') : str_contains($framed,'has-frame profile-frame-preview profile-frame-preview--'.$frame), 'Overview renders equipped frame '.$frame);
}
overview_check(!str_contains(render_account_overview(array('profile_frame'=>'invalid-frame')),'has-frame'), 'Unknown frame falls back safely');
echo "PASS: account overview totals, routes, VIP expiry, verification, wallet, streak, escaping and feature flags.\n";
