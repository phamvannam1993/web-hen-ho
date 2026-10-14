<?php
// CLI-only concurrent sender for chat_groups_http.php; no web entry point.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASEPATH', __DIR__ . '/../system/'); define('APPPATH', __DIR__ . '/../application/');
define('FCPATH',dirname(__DIR__).'/'); define('ENVIRONMENT','testing'); define('VIEWPATH',APPPATH.'views/');
if (!preg_match('/^sc_chat_test_[a-f0-9]{10}$/D',$argv[1] ?? '')) throw new RuntimeException('Only isolated test DBs permitted.');
require BASEPATH.'core/Common.php'; require BASEPATH.'database/DB.php';
$db = DB(array('hostname'=>getenv('CHAT_TEST_DB_HOST') ?: '127.0.0.1','username'=>getenv('CHAT_TEST_DB_USER') ?: 'root',
    'password'=>getenv('CHAT_TEST_DB_PASS') ?: '', 'database'=>$argv[1], 'dbdriver'=>'mysqli','dbprefix'=>'','pconnect'=>false,
    'db_debug'=>true,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'));
class CI_Model { public $db,$auth,$input,$config; }
class WorkerAuth { public function user(){return null;} public function da_xac_thuc(){return false;} }
class WorkerInput { public function ip_address(){return '127.0.0.77';} }
class WorkerConfig { public function item($key){return 'chat-test-secret-only';} }
function base_url($p=''){return '/'.$p;} function site_url($p=''){return '/'.$p;}
function display_name($r){return $r['display_name'] ?: $r['nickname'];} function avatar_url($a,$g){return '/avatar/default';}
require APPPATH.'helpers/chat_group_helper.php'; require APPPATH.'models/M_chat_group.php';
$m = new M_chat_group(); $m->db=$db; $m->auth=new WorkerAuth(); $m->input=new WorkerInput(); $m->config=new WorkerConfig();
file_put_contents($argv[2].'.'.$argv[3].'.ready','1');
$until=microtime(true)+5; while(!is_file($argv[2]) && microtime(true)<$until) usleep(10000);
$message=$m->send($m->group(2),array('key'=>'g:100','user'=>null),'Concurrent retry','concurrent_retry_key_01');
echo json_encode(array('id'=>$message['id']));
