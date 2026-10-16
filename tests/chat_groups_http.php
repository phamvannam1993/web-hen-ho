<?php
/** Exercise real controllers/CSRF/cookies and legacy isolation over local HTTP. */
require __DIR__ . '/chat_groups_integration.php';
putenv('APP_ENV=development'); putenv('APP_URL=http://127.0.0.1:18937'); putenv('APP_KEY=chat-http-test-secret-only');
putenv('REALTIME_SECRET=chat-realtime-test-secret-only');
putenv('REALTIME_WS_URL=ws://127.0.0.1:18938/ws'); putenv('REALTIME_HTTP_URL=http://127.0.0.1:18938');
putenv('REALTIME_HOST=127.0.0.1'); putenv('REALTIME_PORT=18938'); putenv('REALTIME_APP_URL=http://127.0.0.1:18937');
$db->where('id',2)->update('chat_groups',array('status'=>'active'));
// Remove synthetic rate buckets from integration checks; only this isolated DB is affected.
$db->query('DELETE FROM chat_group_rate');
$barrier = sys_get_temp_dir() . '/cg-barrier-' . bin2hex(random_bytes(8));
$workers = array();
for ($w=0;$w<2;$w++) {
    $p = proc_open(array(PHP_BINARY,__DIR__.'/chat_groups_worker.php',$testName,$barrier,(string)$w),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$wp,FCPATH);
    $workers[]=array($p,$wp);
}
$deadline=microtime(true)+5;
while ((!is_file($barrier.'.0.ready') || !is_file($barrier.'.1.ready')) && microtime(true)<$deadline) usleep(10000);
file_put_contents($barrier,'go');
$workerResults=array();
foreach($workers as $worker) {
    $out=stream_get_contents($worker[1][1]); $err=stream_get_contents($worker[1][2]);
    foreach($worker[1] as $pipe) fclose($pipe);
    $exit=proc_close($worker[0]); check_group($exit===0,'Concurrent sender completed: '.$err);
    $workerResults[]=json_decode($out,true);
}
check_group($workerResults[0]['id']===$workerResults[1]['id'],'Two concurrent DB connections commit one retry message');
$log = tempnam(sys_get_temp_dir(),'cg-http-');
$server = proc_open(array(PHP_BINARY,'-S','127.0.0.1:18937','dev-server.php'),array(0=>array('pipe','r'),1=>array('file',$log,'a'),2=>array('file',$log,'a')),$pipes,FCPATH);
$nodeServer = null;
if (is_file(FCPATH.'websoket/node_modules/ws/index.js')) $nodeServer = proc_open(array('node',FCPATH.'websoket/src/server.js'),array(0=>array('pipe','r'),1=>array('file',$log,'a'),2=>array('file',$log,'a')),$nodePipes,FCPATH);
if (!is_resource($server)) throw new RuntimeException('Cannot launch local test HTTP server.');
$cookies = tempnam(sys_get_temp_dir(),'cg-cookie-');
function chat_http($path,$data=null,$token=null,$cookieFile=null) {
    global $cookies;
    $ch = curl_init('http://127.0.0.1:18937/'.$path);
    curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookieFile ?: $cookies,CURLOPT_COOKIEJAR=>$cookieFile ?: $cookies,CURLOPT_TIMEOUT=>10));
    if ($data !== null) { curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,array_filter($data,function($v){return $v instanceof CURLFile;}) ? $data : http_build_query($data)); }
    if ($token !== null) curl_setopt($ch,CURLOPT_HTTPHEADER,array('X-Chat-CSRF: '.$token));
    $text = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    return array($code,$text,json_decode($text,true));
}
try {
    for($i=0;$i<30;$i++) { usleep(100000); $probe=@fsockopen('127.0.0.1',18937); if ($probe) {fclose($probe);break;} }
    $catalog = chat_http('nhom');
    check_group($catalog[0]===200 && strpos($catalog[1], 'data-cg-card-follow')!==false, 'Discovery includes follow buttons');
    preg_match('/data-csrf="([a-f0-9]+)"/', $catalog[1], $catalogCsrf);
    check_group(chat_http('ajax/nhom/2/follow', array())[0]===403, 'Discovery follow requires CSRF');
    check_group(chat_http('ajax/nhom/2/follow', array(), $catalogCsrf[1])[0]===200, 'New guest follows directly without opening room');
    $savedGroups = chat_http('ajax/nhom/0/saved');
    check_group(in_array(2, array_column($savedGroups[2]['groups'], 'id')), 'Discovery follow persists in saved groups');
    $catalog = chat_http('nhom');
    check_group(strpos($catalog[1], 'Đã theo dõi')!==false, 'Discovery displays followed state');
    check_group(chat_http('ajax/nhom/2/unfollow', array(), $catalogCsrf[1])[0]===200, 'Discovery allows unfollow');
    $savedGroups = chat_http('ajax/nhom/0/saved');
    check_group(!in_array(2, array_column($savedGroups[2]['groups'], 'id')), 'Discovery unfollow removes saved group');
    $landing = chat_http('nhom/phong-chat-an-danh');
    if ($landing[0] !== 200) { echo $landing[1]; throw new RuntimeException('Landing failed. Log: '.$log); }
    check_group(strpos($landing[1],'data-group-chat') !== false,'Group landing renders real chat panel');
    preg_match('/data-csrf="([a-f0-9]+)"/',$landing[1],$csrf);
    check_group(!empty($csrf[1]),'Panel includes session CSRF token');
    $bad = chat_http('ajax/nhom/2/open',array());
    check_group($bad[0]===403,'POST without CSRF rejected');
    $open = chat_http('ajax/nhom/2/open',array(),$csrf[1]);
    check_group($open[0]===200 && $open[2]['identity']['guest'],'Guest opens room without logging in');
    check_group(!empty($open[2]['ticket']),'Guest gets scoped realtime ticket');
    $send = array('content'=>'HTTP guest message','client_message_id'=>'http_guest_message_01');
    $sent = chat_http('ajax/nhom/2/send',$send,$csrf[1]);
    check_group($sent[0]===200 && $sent[2]['message']['sender']['guest'],'Guest cookie authorizes anonymous send');
    $again = chat_http('ajax/nhom/2/send',$send,$csrf[1]);
    check_group($again[2]['message']['id']===$sent[2]['message']['id'],'HTTP retry has stable message ID');
    $reopen = chat_http('ajax/nhom/2/open',array(),$csrf[1]);
    check_group($reopen[2]['identity']['id']===$open[2]['identity']['id'],'Guest identity persists across HTTP requests');
    $list = chat_http('ajax/nhom/2/messages');
    check_group($list[0]===200 && count($list[2]['messages'])>0,'Guest reads group history');
    $legacy = chat_http('ajax/phong-chat');
    check_group($legacy[0]===200 && strpos($legacy[1],'HTTP guest message')===false,'Legacy room endpoint excludes new room');
    $withoutConsent = $send; $withoutConsent['guest_consent']='0'; $withoutConsent['client_message_id']='http_guest_message_02';
    check_group(chat_http('ajax/nhom/2/send',$withoutConsent,$csrf[1])[0]===200,'Guest can send without acknowledgement');
    $public = chat_http('ajax/nhom/1/send',$send,$csrf[1]);
    check_group($public[0]===401,'Guest cannot use public-room send endpoint');
    check_group(chat_http('writable/chat-media/does-not-exist.webp')[0]===403,'Dev server blocks direct private-media paths');
    // Create a logged-in fixture using only the session file issued to this isolated test client.
    $memberCookies=tempnam(sys_get_temp_dir(),'cg-member-');
    $memberLanding=chat_http('nhom/phong-chat-an-danh',null,null,$memberCookies);
    preg_match('/data-csrf="([a-f0-9]+)"/',$memberLanding[1],$memberCsrf);
    preg_match('/henho_session\t([^\r\n]+)/',file_get_contents($memberCookies),$sessionMatch);
    check_group(!empty($sessionMatch[1]) && preg_match('/^[a-zA-Z0-9,-]+$/D',$sessionMatch[1]),'Isolated member session established');
    $sessionPath=FCPATH.'writable/sessions/henho_session'.$sessionMatch[1];
    check_group(is_file($sessionPath),'Only test-issued session file selected');
    file_put_contents($sessionPath,file_get_contents($sessionPath).'user_id|i:2;');
    $db->where('id',2)->update('users',array('email_verified_at'=>date('Y-m-d H:i:s')));
    $memberOpen=chat_http('ajax/nhom/2/open',array(),$memberCsrf[1],$memberCookies);
    check_group($memberOpen[0]===200 && !$memberOpen[2]['identity']['guest'],'Logged-in HTTP client uses member identity');
    $memberSend=chat_http('ajax/nhom/2/send',array('content'=>'Member real profile','client_message_id'=>'http_member_message_01'),$memberCsrf[1],$memberCookies);
    check_group($memberSend[0]===200 && $memberSend[2]['message']['sender']['url']!==null,'Member message includes actual public profile');
    $imagePath=tempnam(sys_get_temp_dir(),'cg-image-');$img=imagecreatetruecolor(30,30);imagepng($img,$imagePath);imagedestroy($img);
    $upload=array('image'=>new CURLFile($imagePath,'image/png','test.png'),'client_message_id'=>'http_member_image_01');
    check_group(chat_http('ajax/nhom/2/image',$upload,$memberCsrf[1],$memberCookies)[0]===403,'Images disabled by default in guest-enabled rooms');
    $db->where('id',2)->update('chat_groups',array('allow_images'=>1));
    $imageSent=chat_http('ajax/nhom/2/image',$upload,$memberCsrf[1],$memberCookies);
    check_group($imageSent[0]===200,'Member image is validated and stored');
    $mediaId=$imageSent[2]['message']['id'];
    check_group(chat_http('nhom-media/'.$mediaId,null,null,$memberCookies)[0]===200,'Protected media route serves authorized image');
    $memberIdentity=$memberOpen[2]['identity']['id'];
    $db->where('group_id',2)->where('public_id',$memberIdentity)->update('chat_group_people',array('is_banned'=>1));
    check_group(chat_http('nhom-media/'.$mediaId,null,null,$memberCookies)[0]===404,'Ban removes access to protected image');
    $db->where('group_id',2)->where('public_id',$memberIdentity)->update('chat_group_people',array('is_banned'=>0));
    $db->where('id',$mediaId)->update('room_messages',array('deleted_at'=>date('Y-m-d H:i:s')));
    check_group(chat_http('nhom-media/'.$mediaId,null,null,$memberCookies)[0]===404,'Deleted image is unavailable via media URL');
    $db->where('group_id',2)->where('public_id',$open[2]['identity']['id'])->update('chat_group_people',array('is_banned'=>1));
    check_group(chat_http('ajax/nhom/2/messages')[0]===403,'Banned guest cookie cannot read history');
    $db->where('group_id',2)->where('public_id',$open[2]['identity']['id'])->update('chat_group_people',array('is_banned'=>0));
    if (is_resource($nodeServer)) {
        $nodeTest = proc_open(array('node',__DIR__.'/chat_groups_realtime.js',$open[2]['ticket']),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$nt,FCPATH);
        while (($line=fgets($nt[1]))!==false) {
            echo $line;
            if (trim($line)==='READY_BAN') {
                $db->where('group_id',2)->where('public_id',$open[2]['identity']['id'])->update('chat_group_people',array('is_banned'=>1));
                $curl=curl_init('http://127.0.0.1:18938/publish');
                curl_setopt_array($curl,array(CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_POSTFIELDS=>json_encode(array('items'=>array(array('to'=>'all','event'=>array('t'=>'group.changed','groupId'=>2))))),
                    CURLOPT_HTTPHEADER=>array('Content-Type: application/json','X-Realtime-Secret: '.getenv('REALTIME_SECRET'))));
                curl_exec($curl);curl_close($curl);
            }
        }
        $nodeErrors=stream_get_contents($nt[2]); foreach($nt as $p)fclose($p);
        check_group(proc_close($nodeTest)===0,'Realtime socket tests passed: '.$nodeErrors);
        $db->where('group_id',2)->where('public_id',$open[2]['identity']['id'])->update('chat_group_people',array('is_banned'=>0));
    } else echo "SKIP realtime runtime: install websoket dependencies first.\n";
    $db->where('id',2)->update('chat_groups',array('status'=>'closed'));
    check_group(chat_http('ajax/nhom/2/messages')[0]===403,'Closed group blocks HTTP history');
    $closed = chat_http('nhom/phong-chat-an-danh');
    check_group($closed[0]===200 && strpos($closed[1],'noindex, follow')!==false && strpos($closed[1],'data-group-chat')===false,'Closed landing is noindex with no history panel');
    check_group(chat_http('nhom/nonexistent-room')[0]===404,'Unknown slug returns 404');
    echo "HTTP checks passed. Server log: $log\n";
} finally { if (is_resource($nodeServer)) {proc_terminate($nodeServer);proc_close($nodeServer);} proc_terminate($server); proc_close($server); }
