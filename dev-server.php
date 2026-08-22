<?php
/**
 * Bộ định tuyến cho máy chủ phát triển của PHP.
 *
 * Chạy:  php -S 127.0.0.1:8123 dev-server.php
 *
 * Khác với việc chỉ dùng index.php làm router, file này trả về false với các
 * tệp có thật (CSS, JS, ảnh, robots.txt…) để PHP tự phục vụ đúng kiểu MIME.
 * Nếu không, mọi tệp tĩnh đều bị đẩy qua CodeIgniter và trả về HTML,
 * khiến trình duyệt từ chối áp dụng CSS.
 *
 * Chỉ dùng khi phát triển. Trên máy chủ thật, Apache/nginx đã lo việc này.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . urldecode($path);

// Tệp có thật và không phải PHP thì để máy chủ tích hợp phục vụ
if ($path !== '/' && is_file($file) && substr($file, -4) !== '.php') {
    return false;
}

require __DIR__ . '/index.php';
