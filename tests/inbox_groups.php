<?php
/** Run: php tests/inbox_groups.php. Uses CI Query Builder and SQLite :memory: only. */
if (!class_exists('SQLite3')) { fwrite(STDERR, "Tests require PHP SQLite3.\n"); exit(1); }
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__) . '/system/');
function log_message($l,$m){} function is_php($v){return version_compare(PHP_VERSION,$v,'>=');}
function display_name($r){return $r['nickname'] ?: $r['display_name'];} function avatar_url($p,$g){return 'avatar:'.($p ?: $g);}
function check($ok,$label){if(!$ok)throw new Exception('FAIL: '.$label);}
require BASEPATH.'database/DB_driver.php';require BASEPATH.'database/DB_query_builder.php';class CI_DB extends CI_DB_query_builder{}
require BASEPATH.'database/drivers/sqlite3/sqlite3_driver.php';
class MemoryDb extends CI_DB_sqlite3_driver{protected function _execute($sql){return parent::_execute(preg_replace('/\s+FOR UPDATE\b/i','',$sql));}}
class CI_Model{public $db;}
require dirname(__DIR__).'/application/models/M_inbox_group.php';
$db=new MemoryDb(array('database'=>':memory:','db_debug'=>false));$db->initialize();
foreach(array('CREATE TABLE users(id INTEGER PRIMARY KEY,display_name TEXT,nickname TEXT,avatar TEXT,gender TEXT,status TEXT,deleted_at TEXT)',
'CREATE TABLE blocks(user_id INTEGER,blocked_id INTEGER,PRIMARY KEY(user_id,blocked_id))',
'CREATE TABLE inbox_groups(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,owner_id INTEGER,status TEXT DEFAULT \'active\',created_at TEXT DEFAULT CURRENT_TIMESTAMP)',
'CREATE TABLE inbox_group_members(group_id INTEGER,user_id INTEGER,joined_at TEXT DEFAULT CURRENT_TIMESTAMP,left_at TEXT,last_read_message_id INTEGER DEFAULT 0,PRIMARY KEY(group_id,user_id))')as$q)check($db->query($q)!==false,'fixture create');
$db->query("CREATE TABLE inbox_group_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,group_id INTEGER,sender_id INTEGER,type TEXT,content TEXT,client_message_id TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,deleted_at TEXT,UNIQUE(group_id,sender_id,client_message_id))");
for($id=1;$id<=65;$id++)$db->insert('users',array('id'=>$id,'display_name'=>'Thành viên '.$id,'nickname'=>$id===6?'Giá 50%_ưu đãi':'','avatar'=>'','gender'=>'female','status'=>$id===7?'banned':'active','deleted_at'=>$id===8?'2026-11-09':null));
$model=new M_inbox_group();$model->db=$db;
function denied($fn,$code=null){try{$fn();throw new Exception('Expected rejection');}catch(InvalidArgumentException $e){check($code===null||$code===422,'validation code');}catch(RuntimeException $e){check($code===$e->getCode(),'expected status '.$code.' got '.$e->getCode());}}
$group=$model->create(1,'  3 anh chị em  ',array('2','3','2'));check($group['name']==='3 anh chị em'&&$group['member_count']===3,'create/dedupe/name trim');$id=$group['id'];
check($db->where('group_id',$id)->count_all_results('inbox_group_members')===3,'owner auto member');
$added=$model->add($id,2,array('4'));check($added['added_count']===1,'nonowner member adds');
$db->where('group_id',$id)->where('user_id',4)->update('inbox_group_members',array('last_read_message_id'=>77,'joined_at'=>'2020-01-01'));$model->add($id,3,array('4','4'));$member=$db->where('group_id',$id)->where('user_id',4)->get('inbox_group_members')->row_array();check($member['last_read_message_id']===77&&$member['joined_at']==='2020-01-01','duplicate keeps cursor/join');
$db->where('group_id',$id)->where('user_id',4)->update('inbox_group_members',array('left_at'=>'2026-11-09'));denied(fn()=>$model->add($id,4,array('5')),403);$model->add($id,2,array('4'));$member=$db->where('group_id',$id)->where('user_id',4)->get('inbox_group_members')->row_array();check($member['left_at']===null&&$member['last_read_message_id']===0&&$member['joined_at']!=='2020-01-01','rejoin reset');
denied(fn()=>$model->add($id,5,array('6')),403);$db->where('id',$id)->update('inbox_groups',array('status'=>'closed'));denied(fn()=>$model->add($id,2,array('6')),403);$db->where('id',$id)->update('inbox_groups',array('status'=>'active'));
echo "PASS: tạo nhóm nguyên tử, mọi thành viên thêm người, người ngoài/đã rời/nhóm đóng bị từ chối, thêm lặp/rejoin đúng\n";
$db->insert('blocks',array('user_id'=>5,'blocked_id'=>4));denied(fn()=>$model->add($id,2,array('5')),403);check(!in_array(5,array_column($model->search_users(2,'Thành viên 5','0',$id)['users'],'id'),true),'search filters member blocks');$db->where('user_id',5)->delete('blocks');
$db->insert('blocks',array('user_id'=>5,'blocked_id'=>6));denied(fn()=>$model->add($id,2,array('5','6')),403);denied(fn()=>$model->create(1,'Nhóm lỗi',array('5','6')),403);$db->where('user_id',5)->delete('blocks');
$db->insert('blocks',array('user_id'=>1,'blocked_id'=>5));denied(fn()=>$model->create(1,'Nhóm lỗi',array('5')),403);check(!in_array(5,array_column($model->search_users(1,'Thành viên 5')['users'],'id'),true),'search blocks viewer');$db->where('user_id',1)->delete('blocks');
foreach(array('7','8','999')as$bad)denied(fn()=>$model->add($id,2,array($bad)),422);
foreach(array(array(),array('x'=>'2'),array_fill(0,51,'2'),array('0'),array('-1'),array('999999999999999999999'),array(array('2')))as$bad)denied(fn()=>$model->create(1,'Nhóm lỗi',$bad),422);
foreach(array('', 'x',str_repeat('a',121),array('name'),"\u{200B}\u{200B}")as$bad)denied(fn()=>$model->create(1,$bad,array('2')),422);
denied(fn()=>$model->create(1,'Tự nhóm',array('1')),422);denied(fn()=>$model->create(7,'Không hoạt động',array('2')),403);
echo "PASS: chặn giữa mọi cặp mới/cũ, tìm kiếm lọc chặn, tài khoản không hoạt động, dữ liệu tên/ID sai và overflow\n";
$unrelated=$model->create(5,'Nhóm riêng khác',array('6'));check($model->listing(1,'0',5)['groups']===array(),'common excludes other private groups');
for($i=0;$i<24;$i++)$model->create(1,'Nhóm '.$i,array('2'));
$all=array();$cursor='0';do{$page=$model->listing(1,$cursor,2);$all=array_merge($all,$page['groups']);$cursor=(string)$page['next_before'];}while($page['has_more']);check(count($all)===25&&count(array_unique(array_column($all,'id')))===25,'common paging');check($model->listing(5)['groups'][0]['id']===$unrelated['id'],'mine isolated');
$found=$model->search_users(1,'50%_');check(count($found['users'])===1&&$found['users'][0]['id']===6,'literal wildcard and Unicode');check(array_keys($found['users'][0])===array('id','name','avatar'),'no email/phone in search');
$all=array();$cursor='0';do{$page=$model->search_users(1,'Thành viên',$cursor);$all=array_merge($all,$page['users']);$cursor=(string)$page['next_after'];}while($page['has_more']);check(count($all)===61&&count(array_unique(array_column($all,'id')))===61,'user search pages');
denied(fn()=>$model->search_users(1,'Thành viên','0',$unrelated['id']),403);denied(fn()=>$model->listing(1,array('0')),422);denied(fn()=>$model->search_users(1,'x'),422);
echo "PASS: mine/common riêng tư, phân trang nhóm/người không trùng, Unicode/%/_, nhóm ẩn không tìm được thành viên\n";

$g=$model->create(1,'Nhóm gửi nhận',array('2','3'));$groupId=$g['id'];
$m1=$model->send($groupId,1,'Lời chào',str_repeat('a',32));$m2=$model->send($groupId,2,'Tin trả lời',str_repeat('b',32));$m3=$model->send($groupId,3,'Tin thứ ba',str_repeat('c',32));
check($model->send($groupId,1,'Lời chào',str_repeat('a',32))['id']===$m1['id'],'retry nonce exactly once');
denied(fn()=>$model->send($groupId,1,'Nội dung khác',str_repeat('a',32)),409);
denied(fn()=>$model->send($groupId,5,'Người ngoài',str_repeat('z',32)),403);
denied(fn()=>$model->messages($groupId,5),403);denied(fn()=>$model->detail($groupId,5),403);denied(fn()=>$model->read($groupId,5,(string)$m2['id']),403);
check(count($model->detail($groupId,2)['members'])===3,'detail members');
$page=$model->messages($groupId,1);check(array_column($page['messages'],'id')===array($m1['id'],$m2['id'],$m3['id']),'ascending history');
check($model->listing(1,'0',2)['groups'][0]['unread']===2,'unread before ack');
$model->read($groupId,1,(string)$m2['id']);$model->read($groupId,1,(string)$m1['id']);
check((int)$db->where('group_id',$groupId)->where('user_id',1)->get('inbox_group_members')->row('last_read_message_id')===$m2['id'],'read cursor never decreases');
$db->insert('blocks',array('user_id'=>1,'blocked_id'=>3));
check(array_column($model->messages($groupId,1)['messages'],'id')===array($m1['id'],$m2['id']),'history filters both-way block');
$listed=$model->listing(1,'0',2)['groups'][0];check($listed['last_content']==='Tin trả lời'&&$listed['unread']===0,'preview/unread use same visibility');
denied(fn()=>$model->send($groupId,1,'Không vượt chặn',str_repeat('d',32)),403);
denied(fn()=>$model->read($groupId,1,(string)$m3['id']),422);$db->where('user_id',1)->where('blocked_id',3)->delete('blocks');
$db->where('group_id',$groupId)->where('user_id',1)->update('inbox_group_members',array('left_at'=>'2026-11-10'));
denied(fn()=>$model->send($groupId,1,'Lời chào',str_repeat('a',32)),403);$db->where('group_id',$groupId)->where('user_id',1)->update('inbox_group_members',array('left_at'=>null));
for($i=0;$i<65;$i++)$model->send($groupId,2,'Tin '.$i,sprintf('key_%028d',$i));
$firstPage=$model->messages($groupId,1);check(count($firstPage['messages'])===30&&$firstPage['has_more'],'latest page');
$older=$model->messages($groupId,1,(string)$firstPage['next_before']);check(count($older['messages'])===30&&$older['has_more'],'older page');
$tail=$model->messages($groupId,1,(string)$older['next_before']);check(count($tail['messages'])===8&&!$tail['has_more'],'oldest page');
$newer=$model->messages($groupId,1,'0',(string)$tail['messages'][7]['id']);check(count($newer['messages'])===30&&$newer['has_more'],'incremental forward page');
$foreign=$model->send($unrelated['id'],5,'Nhóm khác',str_repeat('x',32));denied(fn()=>$model->read($groupId,1,(string)$foreign['id']),422);
denied(fn()=>$model->messages($groupId,1,'1','2'),422);denied(fn()=>$model->send($groupId,1,'   ',str_repeat('n',32)),422);denied(fn()=>$model->send($groupId,1,'Hi',array('n')),422);
echo "PASS chat: quyền mọi endpoint, nonce retry/conflict, lịch sử/phân trang, read tăng, preview/unread/chặn nhất quán, không vượt chặn qua nhóm\n";


$empty=$model->create(1,'Từ nhóm rỗng',array('2'));check($model->messages($empty['id'],1)['messages']===array(),'empty initial');
for($i=0;$i<35;$i++)$model->send($empty['id'],2,'Empty backlog '.$i,sprintf('empty_%026d',$i));
$forward=$model->messages($empty['id'],1,'0','0');check(count($forward['messages'])===30&&$forward['has_more']&&$forward['messages'][0]['content']==='Empty backlog 0','explicit after0 forward');
$rest=$model->messages($empty['id'],1,'0',(string)$forward['messages'][29]['id']);check(count($rest['messages'])===5&&!$rest['has_more']&&$rest['messages'][4]['content']==='Empty backlog 34','after0 backlog no gaps');
echo "PASS: nhóm rỗng rồi có 35 tin, after=0 nhận lần lượt 30+5 không bỏ sót\n";

$before=$db->count_all_results('inbox_groups');$members=$db->count_all_results('inbox_group_members');
// An injected SQLite error must roll back the group and all inserted members.
set_error_handler(function ($severity, $message) { if (strpos($message, 'fixture write failure') !== false) return true; return false; });
$db->query("CREATE TRIGGER fixture_fail BEFORE INSERT ON inbox_group_members WHEN NEW.user_id=9 BEGIN SELECT RAISE(ABORT,'fixture write failure'); END");
denied(fn()=>$model->create(1,'Rollback',array('2','9')),503);check($db->count_all_results('inbox_groups')===$before&&$db->count_all_results('inbox_group_members')===$members,'rollback partial creation');$db->query('DROP TRIGGER fixture_fail');restore_error_handler();
$debug=$db->db_debug;$db->query('DROP TABLE inbox_group_members');unset($db->data_cache['table_names']);denied(fn()=>$model->listing(1),503);check($db->db_debug===$debug,'debug flag restored');
echo "PASS: lỗi ghi giữa chừng rollback sạch, thiếu bảng trả 503 và không tự tạo; DB chỉ trong bộ nhớ\n";
echo "LIMIT: bỏ FOR UPDATE cho SQLite; chưa kiểm chứng khóa đồng thời MySQL hoặc HTTP thực\n";
