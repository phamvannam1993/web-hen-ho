<?php
/**
 * Router chỉ dùng cho máy chủ thử tại chỗ:
 *     php -S 127.0.0.1:8000 -t . router-dev.php
 *
 * Máy chủ có sẵn của PHP coi mọi đường dẫn có đuôi (.xml, .txt…) là tệp tĩnh nên
 * trả 404 cho /sitemap.xml. Tệp này đẩy các đường dẫn không phải tệp thật vào
 * index.php, giống RewriteRule trong .htaccess của Apache trên máy chủ thật.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) return false;

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
require __DIR__ . '/index.php';
