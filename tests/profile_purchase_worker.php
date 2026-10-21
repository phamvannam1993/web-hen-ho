<?php
if (PHP_SAPI!=='cli' || !isset($argv[1]) || !preg_match('/^sc_profile_test_[a-f0-9]+$/D',$argv[1])) exit(1);
define('FCPATH',dirname(__DIR__).'/');
define('BASEPATH',__DIR__.'/../system/'); define('APPPATH',__DIR__.'/../application/'); define('ENVIRONMENT','testing');
require BASEPATH.'core/Common.php'; require BASEPATH.'database/DB.php';
class CI_Model { public $db; }
function site_url($p='') { return '/'.$p; }
function display_name($u) { return $u['display_name']; }
function avatar_url($a,$g) { return '/avatar/'.$a; }
function age_from($d) { return 30; }
require APPPATH.'models/M_profile_access.php';
$db=DB(array('hostname'=>getenv('CHAT_TEST_DB_HOST') ?: '127.0.0.1','username'=>getenv('CHAT_TEST_DB_USER') ?: 'root','password'=>getenv('CHAT_TEST_DB_PASS') ?: '','database'=>$argv[1],'dbdriver'=>'mysqli','dbprefix'=>'','pconnect'=>false,'db_debug'=>false,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'));
$m=new M_profile_access();$m->db=$db;
echo json_encode($m->purchase(2,1,$argv[2]));
