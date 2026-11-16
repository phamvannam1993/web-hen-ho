<?php
/** Isolated CI SQLite tests: search context authorization, cutoff and visibility. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH',dirname(__DIR__).'/system/');
function log_message($l,$m){} function is_php($v){return version_compare(PHP_VERSION,$v,'>=');}
function base_url($path){return '/'.$path;}
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label."\n";}
require BASEPATH.'database/DB_driver.php';require BASEPATH.'database/DB_query_builder.php';class CI_DB extends CI_DB_query_builder{}
require BASEPATH.'database/drivers/sqlite3/sqlite3_driver.php';
$db=new CI_DB_sqlite3_driver(array('database'=>':memory:','db_debug'=>true));$db->initialize();
class MY_Controller{public $db,$auth,$input,$output,$m_interaction,$last;protected function json($data,$status=200){return $this->last=array('status'=>$status,'data'=>$data);}}
require dirname(__DIR__).'/application/controllers/Ajax.php';
class ContextAjax extends Ajax{public function __construct(){}}
$c=new ContextAjax();$c->db=$db;
$c->auth=new class{public $uid=1;function id(){return $this->uid;}function check(){return $this->uid>0;}};
$c->input=new class{public $values=array(),$method='get';function method(){return $this->method;}function get($name){return $this->values[$name]??null;}};
$c->output=new class{function set_header($s){return $this;}};
$c->m_interaction=new class{public $cutoff=10;function conversation_cutoff($id,$me){return $this->cutoff;}};
$db->query('CREATE TABLE conversations(id INTEGER PRIMARY KEY,user_low_id INTEGER,user_high_id INTEGER)');
$db->query('CREATE TABLE messages(id INTEGER PRIMARY KEY,conversation_id INTEGER,sender_id INTEGER,type TEXT,content TEXT,created_at TEXT,deleted_at TEXT)');
$db->insert('conversations',array('id'=>1,'user_low_id'=>1,'user_high_id'=>2));
for($i=1;$i<=65;$i++)$db->insert('messages',array('id'=>$i,'conversation_id'=>$i===35?2:1,'sender_id'=>$i%2?1:2,'type'=>$i===32?'image':'text','content'=>$i===32?'uploads/test.jpg':'Tin '.$i,'created_at'=>'2026-11-16 12:00:00','deleted_at'=>$i===31?'2026-11-16':null));
$c->input->values=array('around'=>'30');$r=$c->search_messages(1);$rows=$r['data']['messages'];$ids=array_column($rows,'id');
check($r['status']===200&&count($ids)===31&&in_array(30,$ids,true)&&!in_array(31,$ids,true)&&!in_array(35,$ids,true),'private context bounded, target present, recalled and other conversations excluded');
$sorted=$ids;sort($sorted);check($ids===$sorted&&min($ids)>10,'private context chronological and respects deletion cutoff');
$images=array_values(array_filter($rows,fn($row)=>$row['type']==='image'));check($images[0]['content']==='/uploads/test.jpg','private image uses application URL');
foreach(array('10'=>404,'31'=>404,'35'=>404,'999'=>404,'0'=>422,'-1'=>422,'oops'=>422,'9999999999999999999999'=>422)as$id=>$status){$c->input->values=array('around'=>(string)$id);check($c->search_messages(1)['status']===$status,'private rejects target '.$id);}
$c->input->values=array('around'=>array('30'));check($c->search_messages(1)['status']===422,'private rejects array ID');
$c->input->values=array('around'=>'30');$c->auth->uid=3;check($c->search_messages(1)['status']===403,'private denies non-member');$c->auth->uid=1;
$c->input->method='post';check($c->search_messages(1)['status']===405,'context is GET only');$c->input->method='get';
class CI_Model{public $db;}
require dirname(__DIR__).'/application/models/M_chat_group.php';
class ContextGroup extends M_chat_group{public function serialize($row,$actor){return array('id'=>(int)$row['id'],'content'=>$row['content']);}}
foreach(array(
'CREATE TABLE users(id INTEGER PRIMARY KEY,display_name TEXT,nickname TEXT,avatar TEXT,gender TEXT,slug TEXT,is_vip INTEGER,vip_expired_at TEXT,role TEXT)',
'CREATE TABLE chat_groups(id INTEGER PRIMARY KEY,identity_mode TEXT)',
'CREATE TABLE chat_group_people(id INTEGER PRIMARY KEY,group_id INTEGER,public_id TEXT,nickname TEXT,user_id INTEGER,is_banned INTEGER,actor_key TEXT)',
'CREATE TABLE chat_group_blocks(actor_key TEXT,blocked_actor_key TEXT)',
'CREATE TABLE room_messages(id INTEGER PRIMARY KEY,room_id INTEGER,user_id INTEGER,sender_public_id TEXT,actor_key TEXT,deleted_at TEXT,content TEXT)',
'CREATE TABLE blocks(user_id INTEGER,blocked_id INTEGER)')as$sql)$db->query($sql);
$db->insert('chat_groups',array('id'=>2,'identity_mode'=>'guest'));
$db->insert('chat_group_people',array('id'=>1,'group_id'=>2,'public_id'=>'guest1','nickname'=>'Mây','is_banned'=>0,'actor_key'=>'g:1'));
$db->insert('chat_group_blocks',array('actor_key'=>'g:1','blocked_actor_key'=>'g:3'));
for($i=1;$i<=65;$i++)$db->insert('room_messages',array('id'=>$i,'room_id'=>$i===35?3:2,'sender_public_id'=>'guest1','actor_key'=>$i===32?'g:3':'g:2','content'=>'Tin '.$i,'deleted_at'=>$i===31?'2026-11-16':null));
$g=new ContextGroup();$g->db=$db;$group=array('id'=>2,'identity_mode'=>'guest','status'=>'active');$actor=array('key'=>'g:1','user'=>null);
$ids=array_column($g->message_context($group,$actor,'30')['messages'],'id');
check(count($ids)===31&&in_array(30,$ids,true)&&!in_array(31,$ids,true)&&!in_array(32,$ids,true)&&!in_array(35,$ids,true),'group context uses same room, deletion and block visibility');
foreach(array('31','32','35','999')as$id){try{$g->message_context($group,$actor,$id);throw new Exception('Expected rejection');}catch(RuntimeException $e){check($e->getCode()===404,'group rejects inaccessible target '.$id);}}
$db->where('id',1)->update('chat_group_people',array('is_banned'=>1));try{$g->message_context($group,$actor,'30');throw new Exception('Expected rejection');}catch(RuntimeException $e){check($e->getCode()===403,'group denies banned viewer');}
