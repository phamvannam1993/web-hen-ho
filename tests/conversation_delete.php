<?php
// Run: php tests/conversation_delete.php (fixtures only; no .env/database).
require __DIR__ . '/message_recall.php';
require APPPATH . 'models/M_conversation_pin.php';
class DeleteDb extends RecallDb {
    public $markers = array(), $markerSnapshot, $available = true, $lastId = 11, $lateMessage = null;
    public $updated = 0;
    function table_exists($table) { return $table === 'conversation_deletions' ? $this->available : parent::table_exists($table); }
    function trans_begin() { $this->markerSnapshot = $this->markers; return parent::trans_begin(); }
    function trans_rollback() { parent::trans_rollback(); $this->markers = $this->markerSnapshot; }
    function query($sql, $params = array()) {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        $handled = strpos($sql, 'SELECT deleted_through_message_id') === 0
            || strpos($sql, 'SELECT id FROM messages WHERE conversation_id = ? ORDER BY') === 0
            || strpos($sql, 'INSERT INTO conversation_deletions') === 0
            || strpos($sql, 'INSERT IGNORE INTO conversations') === 0
            || strpos($sql, 'SELECT c.*,') === 0 || strpos($sql,'SELECT COUNT(*) AS c') === 0
            || strpos($sql,'SELECT id, user_low_id, user_high_id') === 0
            || strpos($sql,'SELECT m.id, m.conversation_id') === 0;
        if (!$handled) return parent::query($sql,$params);
        $this->queries++;
        if ($this->queries === $this->failQuery) return false;
        if (strpos($sql,'SELECT deleted_through_message_id') === 0) {
            $key = $params[0] . ':' . $params[1];
            return new RecallResult(isset($this->markers[$key]) ? array(array('deleted_through_message_id'=>$this->markers[$key])) : array());
        }
        if (strpos($sql,'SELECT id FROM messages WHERE conversation_id = ? ORDER BY') === 0) {
            verify_recall(strpos($sql,'FOR UPDATE') !== false,'Deletion locks latest message');
            if ($this->lateMessage) $this->rows[$this->lateMessage['id']] = $this->lateMessage;
            $ids = array_keys(array_filter($this->rows,function ($r) use ($params) { return $r['conversation_id'] === $params[0]; }));
            return new RecallResult($ids ? array(array('id'=>max($ids))) : array());
        }
        if (strpos($sql,'INSERT INTO conversation_deletions') === 0) {
            verify_recall(strpos($sql,'GREATEST(') !== false,'Deletion cutoff never decreases');
            $key = $params[0] . ':' . $params[1];
            $this->markers[$key] = max($this->markers[$key] ?? 0,$params[2]); return true;
        }
        if (strpos($sql,'INSERT IGNORE INTO conversations') === 0) return true;
        if (strpos($sql,'SELECT c.*,') === 0) {
            verify_recall(!$this->available || (strpos($sql,'d.user_id = '.(int)$params[0]) !== false
                && strpos($sql,'COALESCE(c.last_message_id, 0) > d.deleted_through_message_id') !== false
                && strpos($sql,'x.id > COALESCE(d.deleted_through_message_id, 0)') !== false),'List and per-row unread use actor cutoff');
            $key = '9:' . $params[0];
            return new RecallResult(isset($this->markers[$key]) && $this->lastId <= $this->markers[$key]
                ? array() : array(array('id'=>9,'last_message_id'=>$this->lastId)));
        }
        if (strpos($sql,'SELECT COUNT(*) AS c') === 0) {
            verify_recall(!$this->available || strpos($sql,'NOT EXISTS (SELECT 1 FROM conversation_deletions') !== false,'Global unread applies deletion cutoff');
            $cutoff = $this->markers['9:'.$params[0]] ?? 0; $n = 0;
            foreach ($this->rows as $r) if ($r['conversation_id'] === 9 && $r['sender_id'] !== $params[0] && !$r['read_at'] && $r['id'] > $cutoff) $n++;
            return new class($n) { private $n; function __construct($n) {$this->n=$n;} function row($key) {return $this->n;} };
        }
        if (strpos($sql,'SELECT id, user_low_id, user_high_id') === 0) {
            return new RecallResult($params[0] === 9 ? array(array('id'=>9,'user_low_id'=>1,'user_high_id'=>2)) : array());
        }
        if (strpos($sql,'SELECT m.id, m.conversation_id') === 0) {
            verify_recall(strpos($sql,'m.id > ?') !== false,'Pin state filters cutoff');
            $r = $this->pin ? $this->rows[$this->pin['message_id']] : null;
            return new RecallResult($r && $r['conversation_id'] === $params[0] && $r['id'] > $params[1] && !$r['deleted_at'] ? array($r) : array());
        }
        throw new Exception('Unhandled deletion query');
    }
    function update($table,$data) {
        $this->updated=0;
        foreach ($this->rows as &$r) {
            if ($r['conversation_id'] !== $this->conditions['conversation_id'] || $r['id'] <= $this->conditions['id >']
                || $r['sender_id'] === $this->conditions['sender_id !='] || $r['read_at']) continue;
            $r['read_at']=$data['read_at']; $this->updated++;
        }
        unset($r); $this->conditions=array(); return true;
    }
    function affected_rows() { return $this->updated; }
}
class DeleteLoad extends RecallLoader { function library($name) {} }
class DeleteRealtime {
    public $events=array();
    function publish($to,$event) { $this->events[]=array($to,$event); }
}
class DeleteController extends RecallController {
    public $realtime;
    function __construct() {
        parent::__construct(); $this->db = new DeleteDb(); $this->m_interaction->db=$this->db;
        $this->load = new DeleteLoad(); $this->realtime = new DeleteRealtime();
        $this->input->method='post'; $token=str_repeat('a',64);
        $this->input->post['action_token']=$token; $this->session->data['private_message_action_token']=$token;
    }
}
class DeletePin extends M_conversation_pin {
    public $db,$load,$m_interaction;
}
function delete_model() { $m=new RecallModel(); $m->db=new DeleteDb(); return $m; }
$before=$count;
$m=delete_model(); $original=$m->db->rows; $pin=$m->db->pin;
$r=$m->delete_conversation(9,1);
verify_recall($r===array('id'=>9,'deleted_through_message_id'=>11),'Delete returns actual latest message ID');
verify_recall($m->db->rows===$original && $m->db->pin===$pin,'Messages and shared pin preserved');
verify_recall($m->messages(9,100,1)===array() && count($m->messages(9,100,2))===4,'Only actor history cleared');
verify_recall($m->conversations(1)===array() && count($m->conversations(2))===1,'Only actor conversation hidden');
verify_recall($m->unread_count(1)===0 && $m->unread_count(2)>0,'Only actor unread reset');
$m->mark_read(9,1);
verify_recall($m->db->rows===$original,'Deletion does not mark hidden peer messages read');
$pinModel=new DeletePin(); $pinModel->db=$m->db; $pinModel->load=new DeleteLoad(); $pinModel->m_interaction=$m;
verify_recall($pinModel->state(9,1)['pinned']===null && $pinModel->state(9,2)['pinned']['id']===5,'Shared pin only hidden from deleting user');
try { $pinModel->save(9,1,5,true); throw new Exception('Old pin accepted'); }
catch (RuntimeException $e) { verify_recall($e->getCode()===404,'Hidden message cannot be pinned'); }
$m->db->queries=0;
denied_recall($m,array(9,1,5),404);
$new=$m->db->rows[5]; $new['id']=12; $new['sender_id']=2; $new['content']='new message';
$m->db->rows[12]=$new; $m->db->lastId=12;
verify_recall(count($m->conversations(1))===1 && array_column($m->messages(9,100,1),'id')===array(12),'New message restores conversation without old history');
verify_recall(count($m->messages(9,100,2))===5,'Peer retains all history plus new message');
$m->db->queries=0; $m->delete_conversation(9,1);
verify_recall($m->conversation_cutoff(9,1)===12 && $m->conversation_cutoff(9,2)===0,'Repeated deletion advances only actor cutoff');
$m=delete_model(); $m->db->markers['9:1']=99;
$m->delete_conversation(9,1);
verify_recall($m->conversation_cutoff(9,1)===99,'Existing larger cutoff is preserved');
$m=delete_model(); $m->db->rows=array(); $m->db->lastId=0; $m->delete_conversation(9,1);
verify_recall($m->conversation_cutoff(9,1)===0 && !$m->conversations(1),'Empty conversation hidden with zero cutoff');
$m=delete_model(); $m->db->lastId=5; $m->db->lateMessage=$new;
verify_recall($m->delete_conversation(9,1)['deleted_through_message_id']===12,'Snapshot includes newest row even with stale conversation pointer');
for ($failure=1;$failure<=4;$failure++) {
    $m=delete_model(); $m->db->failQuery=$failure;
    try { $m->delete_conversation(9,1); throw new Exception('Expected database failure'); }
    catch (RuntimeException $e) { verify_recall($e->getCode()===503 && !$m->db->markers && $m->db->rolledBack,'Delete rollback on each query failure'); }
}
foreach (array('beginOk','statusOk','commitOk') as $flag) {
    $m=delete_model(); $m->db->$flag=false;
    try { $m->delete_conversation(9,1); throw new Exception('Expected transaction failure'); }
    catch (RuntimeException $e) { verify_recall($e->getCode()===503 && !$m->db->markers,'Delete transaction failure safe'); }
}
foreach (array(array(9,3,403),array(10,1,403),array(0,1,422),array(9,array(),422)) as $args) {
    $m=delete_model(); $code=array_pop($args);
    try { $m->delete_conversation(...$args); throw new Exception('Expected denial'); }
    catch (RuntimeException | InvalidArgumentException $e) { verify_recall($e->getCode()===$code && !$m->db->markers,'Unauthorized or malformed deletion rejected'); }
}
$m=delete_model(); $m->db->available=false;
verify_recall($m->conversation_cutoff(9,1)===0 && count($m->conversations(1))===1,'Old schema remains readable');
try { $m->delete_conversation(9,1); throw new Exception('Expected migration required'); }
catch (RuntimeException $e) { verify_recall($e->getCode()===503,'Missing migration rejects delete'); }
$m=delete_model(); $m->db->failQuery=1;
try { $m->conversation_cutoff(9,1); throw new Exception('Failed marker read accepted'); }
catch (RuntimeException $e) { verify_recall($e->getCode()===503,'Marker query error never exposes old history'); }
$c=new DeleteController(); $c->delete_conversation(9);
verify_recall($c->output->code===200 && $c->output->body['deleted_through_message_id']===11,'Delete controller success');
verify_recall($c->realtime->events===array(array(1,array('t'=>'chat.deleted','conversationId'=>9,'deleted_through_message_id'=>11))),'Deletion event sent to actor only');
foreach (array('login','status','token','method','participant') as $case) {
    $c=new DeleteController(); $expected=403;
    if ($case==='login') { $c->auth->loggedIn=false; $expected=401; }
    if ($case==='status') $c->auth->user['status']='banned';
    if ($case==='token') $c->input->post['action_token']='bad';
    if ($case==='method') { $c->input->method='get'; $expected=405; }
    if ($case==='participant') $c->auth->user['id']=3;
    $c->delete_conversation(9);
    verify_recall($c->output->code===$expected && !$c->db->markers && !$c->realtime->events,'Controller rejects '.$case);
}
$c=new DeleteController(); $c->m_interaction->delete_conversation(9,1); $c->db->queries=0;
$c->input->method='get'; $c->input->get['ids']='5,6,11'; $c->recall(9);
verify_recall($c->output->body['deleted_through_message_id']===11 && !$c->output->body['recalled_ids'],'Recall polling does not expose hidden IDs');
echo 'PASS: '.($count-$before)." deletion assertions; per-user history/list/unread/pins, restored chats, tokens, rollback and actor-only events.\n";
