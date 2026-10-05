<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Public editorial pages only; profiles, search and thin locations stay private. */
function seo_indexable($path)
{
    $path = trim($path, '/');
    return $path === '' || preg_match('~^(hen-ho|tam-su)(/(nam|nu|gay|les))?(/trang/[1-9][0-9]*)?$~D', $path)
        || preg_match('~^tin-tuc(?:/[a-z0-9-]+|/trang/[1-9][0-9]*)?$~D', $path)
        || in_array($path, array('gioi-thieu', 'lien-he', 'noi-quy', 'dieu-khoan', 'bao-mat', 'an-toan', 'swipe-match'), true);
}

/** Do not invent account URLs from placeholder handles. */
function seo_social_urls($settings)
{
    $domains = array('facebook' => 'facebook.com', 'instagram' => 'instagram.com', 'youtube' => 'youtube.com', 'tiktok' => 'tiktok.com');
    $urls = array();
    foreach ($domains as $name => $domain) {
        $url = trim($settings[$name . '_url'] ?? '');
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain)) {
            $urls[$name] = $url;
        }
    }
    return $urls;
}
