<?php
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function base_url($path) { return '/' . $path; }
function site_url($path) { return '/' . $path; }
function age_from($date) { return 26; }
function display_name($member) { return $member['display_name']; }
function avatar_url($avatar, $gender) { return '/avatar.jpg'; }
function gender_label($gender) { return 'Nam'; }
function is_online($time) { return false; }
function time_ago($time) { return '6 ngày trước'; }
function purpose_label($purpose) { return 'Hẹn hò'; }
function mask_contact($phone) { return '090***123'; }
$m = array_fill_keys(array('birthday', 'last_active_at', 'created_at', 'avatar', 'gender', 'job', 'province_name', 'education', 'marital_status', 'height_cm', 'weight_kg', 'smoking', 'drinking', 'phone'), '');
$m = array_merge($m, array('id' => 1, 'slug' => 'test-profile', 'display_name' => '<Test User>', 'is_vip' => false, 'kyc_status' => '', 'has_children' => 0, 'bio' => '<script>unsafe</script>'));
$prefs = $photos = $posts = $comments = $interests = $quick_links = array();
$like_count = 1;
$liked = $liked_me = $matched = false;
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
foreach (array(null, array('id' => 2, 'avatar' => '', 'gender' => 'male'), array('id' => 1, 'avatar' => '', 'gender' => 'male')) as $user) {
    $m['cover_image'] = $user ? 'uploads/2026/10/custom-cover.jpg' : null;
    ob_start();
    include FCPATH . 'application/views/members/profile.php';
    $html = ob_get_clean();
    $expected_cover = $user ? '/uploads/2026/10/custom-cover.jpg' : '/assets/site/images/profile-cover.jpg';
    if (substr_count($html, 'src="' . $expected_cover . '"') !== 2) throw new Exception('Both covers must use the uploaded image or default fallback');
    if ($user && strpos($html, '/assets/site/images/profile-cover.jpg') !== false) throw new Exception('Custom cover must replace default cover');
    if (strpos($html, '<script>unsafe</script>') !== false || strpos($html, '&lt;Test User&gt;') === false) throw new Exception('Profile text must be escaped');
    foreach (array('pp-about', 'pp-photos', 'pp-posts') as $id) {
        if (substr_count($html, 'id="' . $id . '"') !== 1) throw new Exception('Each tab needs a unique panel');
    }
    if (!$user && strpos($html, '090***123') !== false) throw new Exception('Guest must not see contact');
    if ($user && $user['id'] === 2 && strpos($html, 'data-chat-with="1"') === false) throw new Exception('Private chat action must remain available');
    if ($user && $user['id'] === 1 && strpos($html, 'tai-khoan/ho-so') === false) throw new Exception('Owner must be able to edit profile');
    foreach (array('div', 'section', 'aside', 'main', 'header') as $tag) {
        preg_match_all('/<' . $tag . '(?:\s|>)/', $html, $opens);
        if (count($opens[0]) !== substr_count($html, '</' . $tag . '>')) throw new Exception('Unbalanced ' . $tag);
    }
}
echo "PASS: guest, member and owner profiles; escaped content, contact privacy, chat/edit actions and balanced panels.\n";
