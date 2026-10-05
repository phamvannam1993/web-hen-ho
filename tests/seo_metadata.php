<?php
define('BASEPATH', __DIR__);
require __DIR__ . '/../application/helpers/seo_helper.php';
function check($ok, $message) { if (!$ok) throw new Exception($message); }
function site_url($path = '') { return 'https://saigoncupid.com/' . $path; }
function base_url($path = '') { return site_url($path); }
function e($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
foreach (array('', 'hen-ho', 'tam-su/nu/trang/2', 'tin-tuc', 'tin-tuc/bai-viet', 'bao-mat', 'lien-he') as $path) check(seo_indexable($path), 'Public page: ' . $path);
foreach (array('profile/member', 'tai-khoan', 'tim-kiem', 'ha-noi', 'thanh-vien', 'khu-vuc', 'hen-ho/unknown') as $path) check(!seo_indexable($path), 'Private/thin page: ' . $path);
check(!seo_social_urls(array('facebook_url' => 'saigoncupid', 'youtube_url' => 'https://youtube.com.evil.example/a')), 'Reject broken or deceptive URLs');
check(count(seo_social_urls(array('facebook_url' => 'https://www.facebook.com/saigoncupid'))) === 1, 'Valid configured profile');
$seo_path = ''; $can_index = true; $canonical_url = site_url(); $page_title = '</script><script>alert(1)</script>';
$site_name = 'Saigon Cupid'; $meta_desc = 'Kết nối'; $title = 'Trang chủ'; $settings = array(); $article = null;
ob_start(); require __DIR__ . '/../application/views/layouts/seo.php'; $html = ob_get_clean();
preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
$schema = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
check(count($schema['@graph']) === 3, 'Organization, WebSite and WebPage graph');
check(strpos($match[1], '<script>') === false, 'Safe JSON-LD encoding');
check(strpos($html, 'og:locale') !== false && strpos($html, 'twitter:card') !== false, 'Social metadata');
$seo_path = 'profile/member'; $can_index = false;
ob_start(); require __DIR__ . '/../application/views/layouts/seo.php'; $html = ob_get_clean();
check(strpos($html, 'noindex, follow') !== false && strpos($html, 'application/ld+json') === false, 'Private page stays out of index and schema');
echo "PASS: indexing policy, social URLs, metadata and safe structured data.\n";
