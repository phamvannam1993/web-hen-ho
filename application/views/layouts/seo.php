<?php defined('BASEPATH') OR exit('No direct script access allowed');
$description = $meta_desc ?? '';
$image = base_url('assets/images/logo.png');
if (!empty($article['thumbnail'])) $image = base_url(ltrim($article['thumbnail'], '/'));
$organization = array('@type' => 'Organization', '@id' => site_url() . '#organization',
    'name' => $site_name, 'url' => site_url(), 'logo' => base_url('assets/images/logo.png'));
if (!empty($settings['contact_email'])) $organization['email'] = $settings['contact_email'];
if (!empty($settings['hotline'])) $organization['telephone'] = $settings['hotline'];
if (!empty($settings['company_name'])) $organization['legalName'] = $settings['company_name'];
if (!empty($settings['tax_code'])) $organization['taxID'] = $settings['tax_code'];
if (seo_social_urls($settings)) $organization['sameAs'] = array_values(seo_social_urls($settings));
$graph = array();
if ($seo_path === '') {
    $graph[] = $organization;
    $graph[] = array('@type' => 'WebSite', '@id' => site_url() . '#website', 'url' => site_url(),
        'name' => $site_name, 'inLanguage' => 'vi', 'publisher' => array('@id' => $organization['@id']));
}
$type = !empty($article) ? 'BlogPosting' : (preg_match('~^(hen-ho|tam-su|tin-tuc)(/|$)~', $seo_path) ? 'CollectionPage' : 'WebPage');
if ($seo_path === 'gioi-thieu') $type = 'AboutPage';
if ($seo_path === 'lien-he') $type = 'ContactPage';
if ($can_index) {
    $webpage = array('@type' => $type, '@id' => $canonical_url, 'url' => $canonical_url,
        'name' => $page_title, 'description' => $description, 'inLanguage' => 'vi');
    if (!empty($article)) {
        $webpage['headline'] = $article['title'];
        $webpage['image'] = $image;
        foreach (array('published_at' => 'datePublished', 'updated_at' => 'dateModified') as $field => $property) {
            if (!empty($article[$field]) && strtotime($article[$field]) !== false) $webpage[$property] = date(DATE_ATOM, strtotime($article[$field]));
        }
    }
    $graph[] = $webpage;
}
if ($seo_path !== '' && $can_index) {
    $items = array(array('@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => site_url()));
    if (!empty($article)) $items[] = array('@type' => 'ListItem', 'position' => 2, 'name' => 'Cẩm nang hẹn hò', 'item' => site_url('tin-tuc'));
    $items[] = array('@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $title, 'item' => $canonical_url);
    $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => $items);
}
?>
<title><?= e($page_title) ?></title>
<meta name="robots" content="<?= $can_index ? 'index, follow' : 'noindex, follow' ?>">
<link rel="canonical" href="<?= e($canonical_url) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical_url) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta property="og:image:alt" content="<?= e($site_name) ?>">
<meta property="og:type" content="<?= !empty($article) ? 'article' : 'website' ?>">
<meta property="og:locale" content="vi_VN">
<meta property="og:site_name" content="<?= e($site_name) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($page_title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($image) ?>">
<?php if ($graph): ?>
<script type="application/ld+json"><?= json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
