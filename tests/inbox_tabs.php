<?php
// php tests/inbox_tabs.php [browser-output.html] [mixed|groups|empty]
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
function e($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function base_url($path) { return 'file:///' . str_replace('\\', '/', FCPATH) . $path; }
function site_url($path) { return 'https://example.test/' . $path; }
function tk_icon($name) { return ''; }
function display_name($user) { return $user['display_name']; }
function excerpt($s, $n) { return mb_substr($s, 0, $n); }
function avatar_url($path, $gender) { return ''; }
function is_online($date) { return false; }
function time_ago($date) { return '1 giờ trước'; }
$case = $argv[2] ?? 'mixed';
$blocked_user_ids = array();
$partner = null; $message_action_token = str_repeat('a',64); $conversation_id = 0; $user = array('id'=>1);
$chat_groups = array(); $conversations = array();
if ($case !== 'empty') {
    $group = array('id'=>5,'name'=>'Nhóm ẩn danh','mode'=>'guest','url'=>'#anonymous-group',
        'avatar'=>'','last'=>'Xin chào','last_sender'=>'Khách','unread'=>2);
    $chat_groups[] = $group;
    $group['id']=6; $group['name']='Nhóm công khai'; $group['mode']='public'; $chat_groups[]=$group;
}
if ($case === 'mixed') {
    $conversations[] = array('id'=>9,'other_id'=>2,'display_name'=>'Nguyễn Văn An','last_message_id'=>12,'last_type'=>'text',
        'last_content'=>'Xin chào','last_sender_id'=>1,'avatar'=>null,'gender'=>'other','last_active_at'=>null,
        'last_at'=>'2026-11-10 10:00:00','unread'=>0);
}
ob_start(); include FCPATH . 'application/views/account/messages.php'; $view = ob_get_clean();
if (substr_count($view,'role="tab"') !== 2 || strpos($view,'Nhóm công khai') !== false) throw new Exception('Incorrect tabs/group scope');
if (!isset($argv[1])) { echo "PASS: actual view renders two tabs and excludes public groups ($case).\n"; exit; }
$assertions = <<<'JS'
<script>
window.addEventListener('load',async function () {
 var n=0,result=document.getElementById('test-result');
 function check(ok,msg){if(!ok)throw new Error(msg);n++;}
 function visible(selector){return Array.from(document.querySelectorAll(selector)).filter(function(el){return !!el.getClientRects().length;});}
 function tick(){return new Promise(function(resolve){setTimeout(resolve,10);});}
 var priority=document.getElementById('tk-ms-tab-priority'),clubs=document.getElementById('tk-ms-tab-clubs');
 var query=document.getElementById('tk-ms-search'),panel=document.getElementById('tk-ms-inbox-panel');
 var hasGroups=!!panel.querySelector('[data-inbox-group]'),hasPrivate=!!panel.querySelector('.tk-ms-conversation-row');
 function search(text){query.value=text;query.dispatchEvent(new Event('input'));}
 try {
  check(priority.getAttribute('aria-selected')==='true','Priority is default');
  check(visible('[data-inbox-group]').length===(hasGroups?1:0),'Priority includes anonymous groups');
  check(visible('.tk-ms-conversation-row').length===(hasPrivate?1:0),'Priority includes inboxes');
  check(!document.querySelector('.tk-ms-private-title,details.tk-ms-groups'),'Old collapsible headings removed');
  clubs.click();
  check(visible('.tk-ms-conversation-row').length===0,'Clubs excludes private inbox');
  check(visible('[data-inbox-group]').length===(hasGroups?1:0),'Clubs includes anonymous groups');
  check(panel.getAttribute('aria-labelledby')===clubs.id && priority.tabIndex===-1,'Tab accessibility state changes');
  if(!hasGroups)check(!document.getElementById('tk-ms-nores').hidden,'Empty Clubs has empty state');
  search('Nguyễn');check(visible('[data-inbox-group]').length===0,'Clubs search excludes private match');
  priority.click();check(visible('.tk-ms-conversation-row').length===(hasPrivate?1:0),'Search updates for selected tab');
  search('NHÓM');check(visible('[data-inbox-group]').length===(hasGroups?1:0),'Case-insensitive group search');
  search('nonexistent');check(!document.getElementById('tk-ms-nores').hidden,'No-match state shown');
  search('');clubs.focus();clubs.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowLeft',bubbles:true}));
  check(document.activeElement===priority && priority.getAttribute('aria-selected')==='true','Arrow keys switch/focus tabs');
  priority.dispatchEvent(new KeyboardEvent('keydown',{key:'End',bubbles:true}));
  check(document.activeElement===clubs && clubs.getAttribute('aria-selected')==='true','End selects Clubs');
  priority.click();
  if(hasPrivate){panel.querySelector('.tk-ms-conversation-row').remove();await tick();check(visible('[data-inbox-group]').length===1 && document.getElementById('tk-ms-nores').hidden,'Deleting last inbox preserves visible group list');}
  if(hasGroups){var group=panel.querySelector('[data-inbox-group]');check(group.dataset.panelUrl.includes('/ajax/nhom-panel/5'),'Group panel route preserved');}
  if(!hasGroups && !hasPrivate)check(visible('[data-private-empty]').length===1,'Empty priority shows introduction');
  var extra=document.createElement('ul');
  for(var i=0;i<25;i++){var li=document.createElement('li');li.textContent='scroll '+i;li.style.height='40px';extra.append(li);}
  panel.append(extra);panel.scrollTop=100;
  check(panel.scrollTop>0,'Shared list scrolls');
  var target=extra.firstElementChild;
  function touch(type,y){var event=new Event(type,{bubbles:true,cancelable:true});Object.defineProperty(event,'touches',{value:[{clientY:y}]});target.dispatchEvent(event);return event;}
  touch('touchstart',150);check(!touch('touchmove',140).defaultPrevented,'Touch scrolling uses new shared panel');
  result.dataset.status='pass';result.textContent='PASS: '+n+' browser assertions; actual sidebar tabs, scopes, search, keyboard, empty/delete states and scrolling.';
 }catch(error){result.dataset.status='fail';result.textContent='FAIL: '+error.stack;}
});
</script>
JS;
$html = '<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="'.base_url('assets/site/css/account/messages.css').'">'
    . '<style>:root{--tk-border:#eee;--tk-primary:#d1273f;--tk-muted-fg:#777}.tk-ms{height:600px!important;display:flex!important}.tk-ms-side{width:340px;height:600px}.tk-ms-pane{display:none!important}</style>'
    . $view . '<pre id="test-result" data-status="running">RUNNING</pre>' . $assertions;
file_put_contents($argv[1],$html);
echo "Rendered browser fixture ($case).\n";
