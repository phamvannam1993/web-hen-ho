<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| CẤU HÌNH GỬI EMAIL
| -------------------------------------------------------------------
| Thông tin nhạy cảm đọc từ file .env ở thư mục gốc, không ghi thẳng
| vào mã nguồn để tránh lộ khi đưa lên git.
|
| Với Gmail phải dùng "Mật khẩu ứng dụng" (App Password) chứ không phải
| mật khẩu đăng nhập thường, và tài khoản cần bật xác thực hai bước.
*/

$mail_host = getenv('MAIL_HOST') ?: '';
$mail_port = (int) (getenv('MAIL_PORT') ?: 587);
$mail_enc  = strtolower(getenv('MAIL_ENCRYPTION') ?: 'tls');

// Không khai báo máy chủ thì dùng hàm mail() của hệ thống
$config['protocol'] = $mail_host !== '' ? 'smtp' : 'mail';

$config['smtp_host'] = ($mail_enc === 'ssl' ? 'ssl://' : '') . $mail_host;
$config['smtp_port'] = $mail_port;
$config['smtp_user'] = getenv('MAIL_USERNAME') ?: '';
$config['smtp_pass'] = getenv('MAIL_PASSWORD') ?: '';
$config['smtp_crypto'] = ($mail_enc === 'tls') ? 'tls' : '';
$config['smtp_timeout'] = 15;
$config['smtp_keepalive'] = TRUE;

$config['mailtype']  = 'html';
$config['charset']   = 'utf-8';
$config['newline']   = "\r\n";
$config['crlf']      = "\r\n";
$config['wordwrap']  = TRUE;
$config['validate']  = TRUE;
$config['priority']  = 3;
