<?php
// Run: php tests/message_reply.php; in-memory fixtures, no .env/database.
require __DIR__ . '/conversation_delete.php';
require APPPATH . 'libraries/Realtime.php';
class ReplyDb extends DeleteDb {
    public $repliesAvailable=true,$insertOk=true,$updateOk=true,$nextId=12,$parentQueries=0,$failParent=false,$failLock=false;
    public $writeOrder=array();
    function field_exists($field,$table) { return $this->repliesAvailable; }
    function insert($table,$data) {
        if (!$this->insertOk) return false;
        $data+=array('id'=>$this->nextId++,'read_at'=>null,'deleted_at'=>null,'created_at'=>'2026-11-10 12:03:00');
        $this->rows[$data['id']]=$data;$this->writeOrder[]='insert';return true;
    }
    function insert_id() {return $this->nextId-1;}
    function get($table) {
        if ($table==='users') {$this->conditions=array();return new RecallResult(array(array('id'=>1,'display_name'=>'A','avatar'=>null,'gender'=>'other')));}
        if ($table==='user_streaks' || $table==='streak_badges') {$this->conditions=array();return new RecallResult(array());}
        if (isset($this->conditions['id'])) {
            $row=$this->rows[$this->conditions['id']] ?? null;$this->conditions=array();return new RecallResult($row ? array($row) : array());
        }
        return parent::get($table);
    }
    function query($sql,$params=array()) {
        $sql=preg_replace('/\s+/',' ',trim($sql));
        if ($sql==='SELECT id FROM conversations WHERE id = ? FOR UPDATE') {
            $this->writeOrder[]='conversation';return $this->failLock ? false : new RecallResult(array(array('id'=>9)));
        }
        if (strpos($sql,'SELECT id FROM messages WHERE id = ? AND conversation_id = ?')===0) {
            $this->writeOrder[]='parent';if($this->failParent)return false;
            $row=$this->rows[$params[0]] ?? null;
            return new RecallResult($row && $row['conversation_id']===$params[1] && !$row['deleted_at'] ? array($row) : array());
        }
        if (strpos($sql,'SELECT id, sender_id, type, content, deleted_at FROM messages')===0) {
            $this->parentQueries++;$rows=array();
            foreach($this->rows as $row)if($row['conversation_id']===$params[0] && $row['id']>$params[1] && in_array($row['id'],array_slice($params,2),true))$rows[]=$row;
            return new RecallResult($rows);
        }
        if (strpos($sql,'UPDATE conversations SET')===0) {$this->writeOrder[]='update';return $this->updateOk;}
        if (strpos($sql,'SELECT DATE(created_at) AS day FROM messages')===0) return new RecallResult(array());
        return parent::query($sql,$params);
    }
}
class ReplyPublish {
    public $db,$calls=0;
    function tin_rieng(...$args) {verify_recall($this->db->committed,'Publish only after commit');$this->calls++;}
}
class ReplyEmail {
    public $db,$calls=0;
    function new_message(...$args) {verify_recall($this->db->committed,'Email only after commit');$this->calls++;}
}
class ReplyModel extends M_interaction {
    public $db,$load,$realtime,$emailer;
    function message_permission($sender_id,$receiver_id,$check_vip=true) {return null;}
    function conversation_with($user_id,$other_id,$create=true) {return array('id'=>9);}
}
function reply_model() {
    $m=new ReplyModel();$m->db=new ReplyDb();$m->load=new DeleteLoad();
    $m->realtime=new ReplyPublish();$m->realtime->db=$m->db;$m->emailer=new ReplyEmail();$m->emailer->db=$m->db;return $m;
}
class ReplyRealtime extends Realtime {
    public $events=array();
    function __construct($ci) {(new ReflectionProperty(Realtime::class,'CI'))->setValue($this,$ci);}
    function publish($to,array $event,$except=null) {$this->events[$to]=$event;}
}
$before=$count;
foreach(array('text','image') as $type) {
    $m=reply_model();$r=$m->send_message(1,2,'answer',$type,'5');
    verify_recall($r['ok'] && $m->db->rows[12]['reply_to_id']===5,'Reply association saved for '.$type);
    verify_recall($m->db->writeOrder===array('conversation','parent','insert','update'),'Reply lock/write order');
    verify_recall($m->realtime->calls===1 && $m->emailer->calls===1,'Successful reply notifies once');
}
foreach(array(7,11,99,-1,array(5),'5x') as $id) {
    $m=reply_model();$r=$m->send_message(1,2,'answer','text',$id);
    verify_recall(!$r['ok'] && $r['status']===422 && !isset($m->db->rows[12]),'Invalid/foreign/recalled parent rejected');
    verify_recall(!$m->realtime->calls && !$m->emailer->calls,'Rejected reply emits no event');
}
$m=reply_model();$m->db->markers['9:1']=5;
verify_recall(!$m->send_message(1,2,'answer','text',5)['ok'],'Hidden parent rejected for sender');
foreach(array('beginOk','statusOk','commitOk','insertOk','updateOk') as $flag) {
    $m=reply_model();$m->db->$flag=false;$r=$m->send_message(1,2,'answer','text',5);
    verify_recall(!$r['ok'] && !isset($m->db->rows[12]) && !$m->realtime->calls && !$m->emailer->calls,'Failed '.$flag.' rolls back without ghost notifications');
}
foreach(array('failParent','failLock') as $flag) {
    $m=reply_model();$m->db->$flag=true;
    verify_recall(!$m->send_message(1,2,'answer','text',5)['ok'] && $m->db->rolledBack,'Failed parent/lock query rolls back');
}
$m=reply_model();$m->db->repliesAvailable=false;$r=$m->send_message(1,2,'answer','text',5);
verify_recall(!$r['ok'] && $r['status']===503 && !$m->db->writeOrder,'Missing migration rejected before writing');
// Normal sending still works on the old schema (the standard regression suite also covers this).
$m->db->committed=true;
verify_recall($m->send_message(1,2,'normal')['ok'],'Normal sending unaffected by missing reply column');
$m=reply_model();$row=$m->db->rows[5];$row['id']=12;$row['reply_to_id']=5;
$row2=$row;$row2['id']=13;$row2['reply_to_id']=8;
$rows=$m->with_replies(array($row,$row2),9,1);
verify_recall($m->db->parentQueries===1 && $rows[0]['reply']['mine'] && $rows[0]['reply']['content']==='original-private-content-5','Parents resolved in one batch');
verify_recall($rows[1]['reply']['content']==='[Hình ảnh]' && strpos(json_encode($rows[1]['reply']),'original-private-content-8')===false,'Image quote never exposes image path');
$m->db->markers['9:2']=5;
$peer=$m->with_replies(array($row),9,2)[0]['reply'];
verify_recall(!$peer['available'] && $peer['content']==='Tin nhắn không còn hiển thị','Recipient cutoff hides original quote');
$m->db->rows[5]['deleted_at']='2026-11-10 12:04:00';
$recalled=$m->with_replies(array($row),9,1)[0]['reply'];
verify_recall($recalled['recalled'] && $recalled['content']==='Tin nhắn đã được thu hồi','Recalled parent quote sanitized on reload');
$row['reply_to_id']=7;
verify_recall(!$m->with_replies(array($row),9,1)[0]['reply']['available'],'Foreign-conversation reference never leaks content');
$row['reply_to_id']=5;$row['deleted_at']='2026-11-10 12:04:00';
verify_recall($m->with_replies(array($row),9,1)[0]['reply']===null,'Recalled reply does not retain original quote');
$m=reply_model();$row=$m->db->rows[5];$row['id']=12;$row['content']='new reply';$row['reply_to_id']=5;$m->db->markers['9:2']=5;
class ReplyNotificationDefault { function enabled($conversation_id,$user_id) { return true; } }
$ci=(object)array('load'=>new DeleteLoad(),'m_interaction'=>$m,'m_conversation_notification'=>new ReplyNotificationDefault());$rt=new ReplyRealtime($ci);$rt->tin_rieng(9,$row,1,2);
verify_recall($rt->events[1]['message']['reply']['available'] && !$rt->events[2]['message']['reply']['available'],'Realtime resolves quote separately for each viewer');
verify_recall(strpos(json_encode($rt->events[2]),'original-private-content-5')===false,'Recipient realtime contains no hidden parent content');
$m->db->markers['9:2']=12;$rt=new ReplyRealtime($ci);$rt->tin_rieng(9,$row,1,2);
verify_recall(!isset($rt->events[2]),'Realtime suppresses messages already cleared by recipient');
class PartialReplyModel {
    public $calls=array();
    function send_message($sender,$receiver,$content,$type,$reply) {
        $this->calls[]=array($type,$reply);
        return $type==='image' ? array('ok'=>true,'message_id'=>50,'conversation_id'=>9)
            : array('ok'=>false,'status'=>422,'message'=>'Parent recalled');
    }
}
class PartialReplyApi extends Chat {
    public $me=array('id'=>1),$m_interaction,$result,$status;
    function __construct() {$this->m_interaction=new PartialReplyModel();}
    protected function can_auth() {return true;}
    protected function can_xac_thuc($label) {return true;}
    protected function body($key=null,$default=null) {return array('receiver_id'=>2,'content'=>'unsent text','reply_to_id'=>'5')[$key] ?? $default;}
    protected function tai_anh($field,$folder='',$size=5120) {return 'uploads/chat/sent.png';}
    protected function loi($message,$code=400,$extra=array()) {$this->status=$code;$this->result=array_merge(array('ok'=>false,'message'=>$message),$extra);}
}
$partial=new PartialReplyApi();$partial->send();
verify_recall($partial->status===422 && $partial->result['image_sent'] && $partial->result['message_ids']===array(50)
    && $partial->result['conversation_id']===9,'Mobile partial response identifies image already committed');
verify_recall($partial->m_interaction->calls===array(array('image','5'),array('text','5')),'Both image and text carry parent ID');
echo 'PASS: '.($count-$before)." reply assertions; text/image, parent ownership scope, cutoff privacy, batching, rollback, migration and per-recipient realtime.\n";
