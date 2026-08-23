<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Thông tin kết nối cơ sở dữ liệu.
| Giá trị thật đọc từ tệp .env ở thư mục gốc, không ghi thẳng vào đây.
*/

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => getenv('DB_HOST') ?: 'localhost',
	'username' => getenv('DB_USER') ?: 'root',
	'password' => getenv('DB_PASS') !== FALSE ? getenv('DB_PASS') : '',
	'database' => getenv('DB_NAME') ?: 'web_hen_ho',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_unicode_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
