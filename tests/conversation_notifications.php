<?php
// In-memory fixtures; no .env or database connection.
require __DIR__ . '/message_reply.php';
require APPPATH . 'models/M_conversation_notification.php';
require APPPATH . 'libraries/Emailer.php';
require APPPATH . 'controllers/Cron.php';
function display_name($user) { return $user['display_name'] ?? 'Thành viên'; }
function avatar_url($path,$gender) { return 'https://example.test/avatar.svg'; }
function site_url($path) { return 'https://example.test/'.$path; }
function &get_instance() { global $notificationCI; return $notificationCI; }
class NotificationDb extends DeleteDb {
    public $preferences=array(),$preferenceSnapshot,$notificationsAvailable=true,$failRead=false,$failWrite=false;
    function table_exists($table) { return $table==='conversation_notification_preferences' ? $this->notificationsAvailable : parent::table_exists($table); }
    function field_exists($field,$table) { return false; }
    function trans_begin() { $this->queries=0;$this->preferenceSnapshot=$this->preferences;return parent::trans_begin(); }
    function trans_rollback() { parent::trans_rollback();$this->preferences=$this->preferenceSnapshot; }
    function query($sql,$params=array()) {
        if(strpos($sql,'SELECT notifications_enabled FROM conversation_notification_preferences')===0) {
            if($this->failRead)return false;$key=$params[0].':'.$params[1];
            return new RecallResult(isset($this->preferences[$key]) ? array(array('notifications_enabled'=>$this->preferences[$key])) : array());
        }
        if(strpos($sql,'INSERT INTO conversation_notification_preferences')===0) {
            if($this->failWrite)return false;$this->preferences[$params[0].':'.$params[1]]=$params[2];return true;
        }
        return parent::query($sql,$params);
    }
}
class NotificationModel extends M_conversation_notification { public $db; }
class NotificationController extends DeleteController {
    public $m_conversation_notification;
    function __construct() {
        parent::__construct();$this->db=new NotificationDb();$this->m_conversation_notification=new NotificationModel();
        $this->m_conversation_notification->db=$this->db;
    }
}
class NotificationQueue {
    public $rows=array(),$allowed=true;
    function enqueue(...$args) {$this->rows[]=$args;return count($this->rows);}
    function duoc_gui(...$args) {return $this->allowed;}
}
class NotificationCron extends Cron {
    public $load,$m_user,$m_email,$m_conversation_notification;
    function __construct() {}
}
class NotificationRecipient { function find($id) {return array('id'=>$id,'status'=>'active','email'=>'user@example.test');} }
$before=$count;
$model=new NotificationModel();$model->db=new NotificationDb();
verify_recall($model->enabled(9,1) && $model->enabled(9,2),'Default notifications on for both participants');
$state=$model->save(9,1,false);
verify_recall(!$state['notifications_enabled'] && !$model->enabled(9,1) && $model->enabled(9,2),'Mute is per-user');
verify_recall($model->db->rows[5]['deleted_at']===null && $model->db->pin!==null,'Mute preserves messages and pins');
$state=$model->save(9,1,true);verify_recall($state['notifications_enabled'] && $model->enabled(9,1),'Enable restores notifications');
foreach(array(array(9,3,false,403),array(10,1,false,403),array(0,1,false,422),array(9,1,'0',422)) as $args) {
    $model=new NotificationModel();$model->db=new NotificationDb();$code=array_pop($args);
    try{$model->save(...$args);throw new Exception('Expected denial');}
    catch(RuntimeException | InvalidArgumentException $e){verify_recall($e->getCode()===$code && !$model->db->preferences,'Reject unauthorized/malformed mutation');}
}
foreach(array('beginOk','statusOk','commitOk') as $flag){
    $model=new NotificationModel();$model->db=new NotificationDb();$model->db->$flag=false;
    try{$model->save(9,1,false);throw new Exception('Expected transaction error');}
    catch(RuntimeException $e){verify_recall($e->getCode()===503 && !$model->db->preferences,'Transaction failure preserves preferences');}
}
$model=new NotificationModel();$model->db=new NotificationDb();$model->db->failWrite=true;
try{$model->save(9,1,false);throw new Exception('Expected DB error');}
catch(RuntimeException $e){verify_recall($e->getCode()===503 && $model->db->rolledBack,'Write failure rolls back');}
$model->db->failRead=true;
try{$model->enabled(9,1);throw new Exception('Expected read failure');}
catch(RuntimeException $e){verify_recall($e->getCode()===503,'Read failure never defaults enabled');}
$model->db->notificationsAvailable=false;
verify_recall($model->enabled(9,1),'Old schema defaults enabled');
try{$model->save(9,1,false);throw new Exception('Expected migration required');}
catch(RuntimeException $e){verify_recall($e->getCode()===503,'Missing migration rejects mutation');}
$c=new NotificationController();$c->input->method='get';$c->session->data=array();$c->notifications(9);
verify_recall($c->output->body['notifications_enabled'] && strlen($c->output->body['action_token'])===64,'GET owns state and token');
foreach(array('login','status','method','token','value','participant') as $case){
    $c=new NotificationController();$c->input->post['notifications_enabled']='0';$expected=403;
    if($case==='login'){$c->auth->loggedIn=false;$expected=401;}
    if($case==='status')$c->auth->user['status']='banned';
    if($case==='method'){$c->input->method='delete';$expected=405;}
    if($case==='token')$c->input->post['action_token']='bad';
    if($case==='value'){$c->input->post['notifications_enabled']=array('0');$expected=422;}
    if($case==='participant')$c->auth->user['id']=3;
    $c->notifications(9);verify_recall($c->output->code===$expected && !$c->db->preferences && !$c->realtime->events,'Endpoint rejects '.$case);
}
$c=new NotificationController();$c->input->post['notifications_enabled']='0';$c->notifications(9);
verify_recall($c->output->code===200 && !$c->output->body['notifications_enabled'],'Endpoint saves mute');
verify_recall($c->realtime->events===array(array(1,array('t'=>'chat.notification-preference','conversationId'=>9,'notifications_enabled'=>false))),'Preference event only sent to actor');
$model=$c->m_conversation_notification;
$notificationCI=(object)array('load'=>new DeleteLoad(),'m_email'=>new NotificationQueue(),'m_conversation_notification'=>$model);
$emailer=new Emailer();$sender=array('display_name'=>'Sender','gender'=>'other');
verify_recall($emailer->new_message(1,$sender,9)===false && !$notificationCI->m_email->rows,'Muted conversation does not enqueue email');
verify_recall($emailer->new_message(2,$sender,9)===1,'Peer preference unaffected');
$model->save(9,1,true);verify_recall($emailer->new_message(1,$sender,9)===2,'Re-enable resumes email enqueue');
$cron=new NotificationCron();$cron->load=new DeleteLoad();$cron->m_user=new NotificationRecipient();
$cron->m_email=$notificationCI->m_email;$cron->m_conversation_notification=$model;
$validate=new ReflectionMethod(Cron::class,'con_hop_le');$queued=array('user_id'=>1,'type'=>'new_message','related_id'=>9,'to_email'=>'user@example.test');
verify_recall($validate->invoke($cron,$queued)===true,'Previously queued email eligible while enabled');
$model->save(9,1,false);verify_recall($validate->invoke($cron,$queued)==='Conversation notifications disabled','Worker suppresses email queued before mute');
$model->save(9,1,true);$notificationCI->m_email->allowed=false;
verify_recall($validate->invoke($cron,$queued)!==true,'Global email preferences still apply');
$model->db->failRead=true;verify_recall($emailer->new_message(1,$sender,9)===false,'Preference read error skips email without reporting failed send');
verify_recall($validate->invoke($cron,$queued)==='Conversation notification preferences unavailable','Worker fails closed on read error');
$senderModel=reply_model();$row=$senderModel->db->rows[5];$row['id']=12;$row['content']='new message';
$ci=(object)array('load'=>new DeleteLoad(),'m_interaction'=>$senderModel,'m_conversation_notification'=>$model);
$rt=new ReplyRealtime($ci);$rt->tin_rieng(9,$row,1,2);
verify_recall(isset($rt->events[2]) && !$rt->events[2]['notify'] && $rt->events[2]['message']['content']==='new message','Preference failure still delivers message with notify false');
$model->db->failRead=false;$model->save(9,2,false);$rt=new ReplyRealtime($ci);$rt->tin_rieng(9,$row,1,2);
verify_recall(!$rt->events[2]['notify'] && !$rt->events[1]['notify'],'Muted recipient and own messages never alert');
$model->save(9,2,true);$rt=new ReplyRealtime($ci);$rt->tin_rieng(9,$row,1,2);
verify_recall($rt->events[2]['notify'] && !$rt->events[1]['notify'],'Unmuted recipient may alert without own-device alerts');
echo 'PASS: '.($count-$before)." notification assertions; per-user state, permission/token, transactions, email queue and realtime delivery.\n";
