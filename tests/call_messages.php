<?php
// php -d extension=sqlite3 tests/call_messages.php — isolated database, no .env.
if (!class_exists('SQLite3')) { fwrite(STDERR, "Requires SQLite3.\n"); exit(1); }
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__) . '/system/');
define('APPPATH', dirname(__DIR__) . '/application/');
function log_message($level,$message) {}
function is_php($v) { return version_compare(PHP_VERSION,$v,'>='); }
function check($ok,$message) { if (!$ok) throw new Exception($message); }
require BASEPATH.'database/DB_driver.php';
require BASEPATH.'database/DB_query_builder.php';
class CI_DB extends CI_DB_query_builder {}
require BASEPATH.'database/drivers/sqlite3/sqlite3_driver.php';
class MemoryDb extends CI_DB_sqlite3_driver {
    public $failUpdate = false;
    protected function _execute($sql) {
        if ($this->failUpdate && strpos($sql,'UPDATE conversations SET') === 0) return false;
        return parent::_execute(preg_replace('/\s+FOR UPDATE\b/i','',$sql));
    }
}
class CI_Model { public $db; }
class CI_Controller {}
require APPPATH.'models/M_call_message.php';
require APPPATH.'models/M_interaction.php';
require APPPATH.'controllers/Voice_calls.php';
$db = new MemoryDb(array('database'=>':memory:','db_debug'=>false)); $db->initialize();
$db->query('CREATE TABLE conversations(id INTEGER PRIMARY KEY,user_low_id INTEGER,user_high_id INTEGER,last_message_id INTEGER,last_message_at TEXT)');
$db->query('CREATE TABLE messages(id INTEGER PRIMARY KEY AUTOINCREMENT,conversation_id INTEGER,sender_id INTEGER,type TEXT,content TEXT,call_id TEXT UNIQUE,created_at TEXT DEFAULT CURRENT_TIMESTAMP,deleted_at TEXT)');
$db->insert('conversations',array('id'=>9,'user_low_id'=>1,'user_high_id'=>2));
$db->insert('conversations',array('id'=>10,'user_low_id'=>1,'user_high_id'=>3));
$model = new M_call_message(); $model->db = $db;
$data = array('callId'=>'10000000-0000-4000-8000-000000000001','conversationId'=>9,
    'uid'=>1,'peerId'=>2,'video'=>false,'outcome'=>'completed','durationSeconds'=>600);
$saved = $model->save_result($data);
check($saved['created'] && $saved['row']['type']==='system' && $saved['row']['content']==='Cuộc gọi thoại · 10 phút 0 giây','ten-minute duration');
check((int)$db->get('conversations')->row('last_message_id')===(int)$saved['row']['id'],'conversation preview updated');
$retry = $data; $retry['durationSeconds']=700;
check(!$model->save_result($retry)['created'],'retry does not create a second row');
check($db->get('messages')->num_rows()===1 && $db->get('messages')->row('content')===$saved['row']['content'],'retry cannot edit result');
function rejected($fn,$status) {
    try { $fn(); throw new Exception('Expected rejection'); }
    catch (InvalidArgumentException $e) { check($status===$e->getCode(),'validation status'); }
    catch (RuntimeException $e) { check($status===$e->getCode(),'failure status'); }
}
$forged=$data; $forged['conversationId']=10;$forged['peerId']=3;
rejected(fn()=>$model->save_result($forged),409);
$forged=$data;$forged['peerId']=3;
rejected(fn()=>$model->save_result($forged),403);
foreach (array('callId'=>'bad','durationSeconds'=>3001,'uid'=>'1','outcome'=>'fake','video'=>'yes') as $key=>$value) {
    $invalid=$data; $invalid[$key]=$value; rejected(fn()=>$model->save_result($invalid),422);
}
foreach (array('missed','declined','busy','failed') as $i=>$outcome) {
    $next=$data;$next['callId']=sprintf('10000000-0000-4000-8000-%012d',$i+2);
    $next['durationSeconds']=0;$next['outcome']=$outcome;$next['video']=true;
    $row=$model->save_result($next)['row'];
    check(strpos($row['content'],'Cuộc gọi video')===0 && strpos($row['content'],'phút')===false,'nonconnected outcome');
}
$interaction=new M_interaction();$interaction->db=$db;
rejected(fn()=>$interaction->recall_message(9,1,$saved['row']['id']),403);
check($db->where('id',$saved['row']['id'])->get('messages')->row('deleted_at')===null,'call remains immutable');
$before=$db->count_all('messages');$db->failUpdate=true;
$failed=$data;$failed['callId']='10000000-0000-4000-8000-000000000009';
rejected(fn()=>$model->save_result($failed),503);$db->failUpdate=false;
check($db->count_all('messages')===$before,'failed preview rolls back message');

// Internal endpoint rejects untrusted callers before loading the persistence model.
class CallOutput {
    public $status=200,$body;
    function set_status_header($s) {$this->status=$s;return $this;}
    function set_output($s) {$this->body=json_decode($s,true);return $this;}
}
class CallInput {
    public $verb='post',$secret='wrong',$raw_input_stream='{}';
    function method(){return $this->verb;}
    function get_request_header($n,$x){return $this->secret;}
}
class CallRealtime {
    public $events=0;
    function la_may_chu($s){return $s==='fixture-secret';}
    function tin_rieng(...$args){$this->events++;}
}
class CallLoader { function model($name) {} }
class CallController extends Voice_calls {
    public $input,$output,$realtime,$load,$m_call_message;
    function __construct($model){$this->input=new CallInput();$this->output=new CallOutput();$this->realtime=new CallRealtime();$this->load=new CallLoader();$this->m_call_message=$model;}
}
$c=new CallController($model);$c->completed();check($c->output->status===403,'internal secret required');
$c->input->verb='get';$c->completed();check($c->output->status===405,'POST required');
$c->input->verb='post';$c->input->secret='fixture-secret';
$new=$data;$new['callId']='10000000-0000-4000-8000-000000000010';
$c->input->raw_input_stream=json_encode($new);$c->completed();$c->completed();
check($c->output->status===200&&$c->output->body['ok']&&$c->realtime->events===1,'only new result publishes a message');
echo "PASS: duration, outcomes, immutable/idempotent history, conversation ownership, rollback and internal authentication.\n";
