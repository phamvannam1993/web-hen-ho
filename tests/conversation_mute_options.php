<?php
require __DIR__ . '/conversation_notifications.php';
class TimedNotificationDb extends NotificationDb {
    public $advanced=true;
    function field_exists($field,$table) {return $this->advanced;}
    function query($sql,$params=array()) {
        if(strpos($sql,'SELECT notifications_enabled,')===0) {
            if($this->failRead)return false;
            $row=$this->preferences[$params[0].':'.$params[1]] ?? null;
            return new RecallResult($row ? array($row) : array());
        }
        if(strpos($sql,'INSERT INTO conversation_notification_preferences')===0) {
            if($this->failWrite)return false;
            preg_match('/preferences \(([^)]+)\)/',$sql,$match);
            $values=array_combine(array_map('trim',explode(',',$match[1])),$params);
            $key=$params[0].':'.$params[1];
            $this->preferences[$key]=array_merge(array('notifications_enabled'=>1,'calls_notifications_enabled'=>1,
                'message_muted_until'=>null,'calls_muted_until'=>null),$this->preferences[$key] ?? array(),$values);
            return true;
        }
        return parent::query($sql,$params);
    }
}
class TimedNotificationModel extends NotificationModel {
    public $clock=1800000000;
    protected function now(){return $this->clock;}
}
function timed_model(){$m=new TimedNotificationModel();$m->db=new TimedNotificationDb();return $m;}
$before=$count;
foreach(array('messages','calls','both') as $scope)foreach(array(0,900,3600,28800,86400) as $duration){
    $m=timed_model();$state=$m->configure(9,1,$scope,$duration);
    verify_recall($state['muted'] && $state['notifications_enabled']===($scope==='calls')
        && $state['calls_notifications_enabled']===($scope==='messages'),'Mute only chosen scope');
    verify_recall($m->enabled(9,2) && $m->enabled(9,2,'calls'),'Other participant unaffected');
    $deadline=$duration ? $m->clock+$duration : null;
    verify_recall(($state['message_muted_until']===($scope==='calls'?null:$deadline))
        && ($state['calls_muted_until']===($scope==='messages'?null:$deadline)),'Server computes selected deadlines');
    if($duration){
        $m->clock+=$duration-1;
        verify_recall($m->state(9,1)['muted'],'Mute active one second before expiry');
        $m->clock++;
        verify_recall(!$m->state(9,1)['muted'] && $m->enabled(9,1) && $m->enabled(9,1,'calls'),'Automatically enabled at exact expiry');
    }else{
        $m->clock+=10000000;verify_recall($m->state(9,1)['muted'],'Indefinite mute survives elapsed time');
    }
    $state=$m->configure(9,1,'both',0,true);
    verify_recall(!$state['muted'] && !$state['message_muted_until'] && !$state['calls_muted_until'],'Enable resets both deadlines');
}
$m=timed_model();$m->configure(9,1,'messages',3600);$m->configure(9,1,'calls',900);
$m->clock+=900;
verify_recall(!$m->enabled(9,1) && $m->enabled(9,1,'calls'),'Independent expiry preserves longer message mute');
$m->save(9,1,false);$m->clock+=100000;
verify_recall(!$m->enabled(9,1),'Legacy disable clears expired deadline and means indefinite');
$m->save(9,1,true);verify_recall($m->enabled(9,1),'Legacy enable still works on migrated schema');
foreach(array(array('other',900),array(array('calls'),900),array('both',-1),array('both',901),array('calls','900')) as $args){
    $m=timed_model();try{$m->configure(9,1,$args[0],$args[1]);throw new Exception('Accepted bad option');}
    catch(InvalidArgumentException $e){verify_recall($e->getCode()===422 && !$m->db->preferences,'Invalid scope/duration rejected');}
}
foreach(array('beginOk','statusOk','commitOk') as $flag){
    $m=timed_model();$m->db->$flag=false;
    try{$m->configure(9,1,'both',900);throw new Exception('Expected transaction failure');}
    catch(RuntimeException $e){verify_recall($e->getCode()===503 && !$m->db->preferences,'Timed mutation rolls back');}
}
$m=timed_model();$m->db->failWrite=true;
try{$m->configure(9,1,'both',900);throw new Exception('Expected write failure');}
catch(RuntimeException $e){verify_recall($e->getCode()===503 && $m->db->rolledBack,'Timed write failure safe');}
$m=timed_model();$m->db->advanced=false;
try{$m->configure(9,1,'both',900);throw new Exception('Missing migration accepted');}
catch(RuntimeException $e){verify_recall($e->getCode()===503,'Old schema rejects timed options clearly');}
$m=timed_model();try{$m->configure(9,3,'both',900);throw new Exception('Outsider accepted');}
catch(RuntimeException $e){verify_recall($e->getCode()===403 && !$m->db->preferences,'Timed options require conversation membership');}
foreach(array(array('mute_scope'=>'calls','mute_duration'=>'900'),array('mute_scope'=>'both','mute_duration'=>'0')) as $options){
    $c=new NotificationController();$c->m_conversation_notification=timed_model();
    $c->input->post=array_merge($c->input->post,$options,array('notifications_enabled'=>'0'));$c->notifications(9);
    verify_recall($c->output->code===200 && $c->output->body['muted'],'Endpoint accepts valid timed options');
    verify_recall(isset($c->realtime->events[0][1]['calls_notifications_enabled']),'Actor event includes call setting');
}
foreach(array('901',array('900'),'-1','1.5') as $duration){
    $c=new NotificationController();$c->input->post+=array('notifications_enabled'=>'0','mute_scope'=>'calls','mute_duration'=>$duration);
    $c->notifications(9);verify_recall($c->output->code===422,'Endpoint validates duration whitelist');
}
echo 'PASS: '.($count-$before)." timed mute assertions; scopes, server deadlines, exact expiry, indefinite mute, compatibility, validation and rollback.\n";
