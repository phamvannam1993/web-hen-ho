<?php
require __DIR__.'/profile_access.php';
$db->where('buyer_id',1)->where('owner_id',2)->delete('profile_purchases');
$db->where('id',1)->update('users',array('coin_balance'=>100,'transferable_coin_balance'=>100,'is_vip'=>0));
$db->where('id',2)->update('users',array('charge_enabled'=>1,'contact_vip_required'=>1,'height_cm'=>178,'bio'=>str_repeat('Preview text. ',30).'HIDDEN_DETAIL_SENTINEL'));
putenv('DB_HOST='.$host);putenv('DB_USER='.$username);putenv('DB_PASS='.$password);putenv('DB_NAME='.$testName);
putenv('APP_ENV=development');putenv('APP_URL=http://127.0.0.1:18943');putenv('APP_KEY=profile-http-test-secret');putenv('CHAT_GROUPS_ENABLED=0');
$log=tempnam(sys_get_temp_dir(),'profile-http-');
$server=proc_open(array(PHP_BINARY,'-S','127.0.0.1:18943','dev-server.php'),array(0=>array('pipe','r'),1=>array('file',$log,'a'),2=>array('file',$log,'a')),$pipes,FCPATH);
$cookies=tempnam(sys_get_temp_dir(),'profile-cookie-');
function profile_http($path,$data=null) {
    global $cookies;
    $ch=curl_init('http://127.0.0.1:18943/'.$path);
    curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookies,CURLOPT_COOKIEJAR=>$cookies,CURLOPT_TIMEOUT=>10));
    if ($data!==null) { curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data)); }
    $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    return array($code,$body,json_decode($body,true));
}
try {
    for($i=0;$i<30;$i++) { usleep(100000);$socket=@fsockopen('127.0.0.1',18943);if ($socket) { fclose($socket);break; } }
    $preview=profile_http('user/vip-member/preview');
    check_group($preview[0]===200 && !empty($preview[2]['preview']),'HTTP preview returns JSON');
    check_group(strpos($preview[1],'HIDDEN_DETAIL_SENTINEL')===false && strpos($preview[1],'SECRET')===false,'Preview API never leaks detailed bio or authentication contacts');
    $apiPreview=profile_http('api/members/vip-member/preview');
    check_group($apiPreview[0]===200 && strpos($apiPreview[1],'SECRET')===false && strpos($apiPreview[1],'HIDDEN_DETAIL_SENTINEL')===false,'Public API preview exposes only basic data');
    $apiLocked=profile_http('api/members/vip-member');
    check_group($apiLocked[0]===403 && strpos($apiLocked[1],'HIDDEN_DETAIL_SENTINEL')===false,'Public detail API blocks unpaid access');
    $locked=profile_http('user/vip-member');
    check_group($locked[0]===200 && strpos($locked[1],'profile-access-locked')!==false && strpos($locked[1],'HIDDEN_DETAIL_SENTINEL')===false,'Direct URL presents only preview to a guest');
    $guestPurchase=profile_http('user/vip-member/purchase',array('csrf'=>$preview[2]['csrf'],'version'=>$preview[2]['preview']['version']));
    check_group($guestPurchase[0]===401,'Guest cannot purchase even with valid preview token');
    preg_match('/henho_session\t([^\r\n]+)/',file_get_contents($cookies),$match);
    check_group(!empty($match[1]) && preg_match('/^[a-zA-Z0-9,-]+$/D',$match[1]),'Test issued its own session');
    $sessionFile=FCPATH.'writable/sessions/henho_session'.$match[1];
    check_group(is_file($sessionFile),'Only test-issued session file is changed');
    file_put_contents($sessionFile,file_get_contents($sessionFile).'user_id|i:1;');
    $preview=profile_http('user/vip-member/preview');
    check_group($preview[2]['preview']['logged_in']===true,'Signed-in preview recognizes viewer');
    check_group(profile_http('profile/vip-member/purchase',array('csrf'=>'invalid','version'=>$preview[2]['preview']['version']))[0]===403,'Profile purchase requires matching CSRF');
    $locked=profile_http('profile/vip-member');
    check_group(strpos($locked[1],'profile-access-locked')!==false && strpos($locked[1],'HIDDEN_DETAIL_SENTINEL')===false,'Legacy profile URL cannot bypass payment');
    $purchase=profile_http('user/vip-member/purchase',array('csrf'=>$preview[2]['csrf'],'version'=>$preview[2]['preview']['version']));
    check_group($purchase[0]===200 && $purchase[2]['charged']===20,'Explicit HTTP confirmation transfers twenty coins');
    $details=profile_http('user/vip-member');
    check_group($details[0]===200 && strpos($details[1],'HIDDEN_DETAIL_SENTINEL')!==false && strpos($details[1],'0912345678')===false,'Purchased details are visible but VIP contact stays absent from HTML');
    check_group(profile_http('profile/vip-member/mo-lien-he',array())[0]===403,'Old contact endpoint cannot bypass VIP');
    $db->where('id',1)->update('users',array('is_vip'=>1,'vip_expired_at'=>date('Y-m-d H:i:s',strtotime('+1 day'))));
    $details=profile_http('user/vip-member');
    check_group(strpos($details[1],'090SECRET2')!==false && strpos($details[1],'private2@test.invalid')===false,'VIP receives the existing Zalo number without private login email');
    echo "PASS real HTTP profile privacy and explicit payment.\n";
} finally {
    if (is_resource($server)) proc_terminate($server);
    foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
    if (is_resource($server)) proc_close($server);
}
