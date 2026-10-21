<?php
/** Real database checks, isolated from the configured application database. */
define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', dirname(__DIR__) . '/');
define('ENVIRONMENT', 'testing');
function check_group($ok, $message) { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
$testName = 'sc_profile_test_' . bin2hex(random_bytes(5));
$host = getenv('CHAT_TEST_DB_HOST') ?: '127.0.0.1';
$username = getenv('CHAT_TEST_DB_USER') ?: 'root';
$password = getenv('CHAT_TEST_DB_PASS') ?: '';
$pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $username, $password, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
$pdo->exec("CREATE DATABASE `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$testName`"); $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?[a-z_]+`?\s*\(.*?\) ENGINE=InnoDB[^;]*;/si', file_get_contents(FCPATH . 'database/schema.sql'), $ddl);
foreach ($ddl[0] as $sql) $pdo->exec($sql);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
$pdo->exec("INSERT INTO users(id,uuid,email,password_hash,display_name,slug,gender,birthday,status,role) VALUES (1,UUID(),'vip-admin@test.invalid','unused','VIP Admin','vip-admin','male','1990-01-01','active','admin'),(2,UUID(),'vip-member@test.invalid','unused','VIP Member','vip-member','female','1990-01-01','active','member')");
require BASEPATH . 'core/Common.php';
require BASEPATH . 'database/DB.php';
$db = DB(array('hostname'=>$host,'username'=>$username,'password'=>$password,'database'=>$testName,'dbdriver'=>'mysqli','dbprefix'=>'','pconnect'=>false,'db_debug'=>true,'char_set'=>'utf8mb4','dbcollat'=>'utf8mb4_unicode_ci'));
class CI_Model { public $db; }
function base_url($path='') { return '/' . $path; }


function site_url($path='') { return '/' . $path; }
function display_name($u) { return $u['display_name']; }
function avatar_url($avatar,$gender) { return '/avatar/' . $avatar; }
function age_from($date) { return 30; }
require APPPATH . 'models/M_profile_access.php';
require FCPATH . 'database/migrate_profile_access.php';
$pdo->exec("INSERT INTO provinces(id,name,slug) VALUES (1,'Test Province','test-province')");
$pdo->exec("UPDATE users SET avatar='avatar.png',province_id=1,job='Engineer',bio='A short bio',phone=CONCAT('090SECRET',id),email=CONCAT('private',id,'@test.invalid'),shared_phone='0912345678',shared_zalo='public-zalo',shared_email='public@test.invalid'");
$pdo->exec("INSERT INTO user_preferences(user_id,seeking_gender,purpose) VALUES (1,'all','hen_ho'),(2,'all','hen_ho')");
$access=new M_profile_access(); $access->db=$db;
function owner() { global $db; return $db->where('id',2)->get('users')->row_array(); }
function denied($fn,$code,$label) { try { $fn(); } catch (RuntimeException $e) { check_group($e->getCode()===$code,$label); return; } throw new RuntimeException('Expected denial: '.$label); }
$db->where('id',1)->update('users',array('coin_balance'=>100,'transferable_coin_balance'=>0,'is_vip'=>0));
$quote=$access->quote(owner(),1);
check_group($quote['cost']===20 && $quote['contact_vip_required'], 'Default settings charge twenty coins and require VIP for contact');
check_group(!isset($quote['phone'],$quote['email'],$quote['shared_phone'],$quote['height_cm']) && !str_contains(json_encode($quote),'SECRET'), 'Preview never contains locked contacts or login credentials');
denied(function () use ($access,$quote) { $access->purchase(2,1,$quote['version']); },422,'Locked bonus cannot pay for profile');
check_group($db->where('id',1)->get('users')->row_array()['coin_balance']==100 && !$access->purchased(1,2),'Failed purchase leaves balance and rights untouched');
$db->where('id',1)->update('users',array('transferable_coin_balance'=>100));
$first=$access->purchase(2,1,$quote['version']);
check_group($first['charged']===20 && $access->purchased(1,2),'Purchase grants permanent pair permission');
check_group($db->where('id',1)->get('users')->row_array()['coin_balance']==80 && $db->where('id',2)->get('users')->row_array()['coin_balance']==20,'Exactly twenty coins transfer without a fee');
check_group($access->purchase(2,1,$quote['version'])['charged']===0,'Retry never transfers twice');
check_group($db->where('ref_type','profile_transfer')->count_all_results('coin_transactions')===2,'Transfer records one debit and one credit');
denied(function () use ($access) { $access->contacts(owner(),1); },403,'Non-VIP buyer cannot read VIP contact');
$db->where('id',1)->update('users',array('is_vip'=>1,'vip_expired_at'=>date('Y-m-d H:i:s',strtotime('+1 day'))));
check_group($access->contacts(owner(),1)['phone']==='090SECRET2','Active VIP sees the existing Zalo number');
$db->where('id',1)->update('users',array('vip_expired_at'=>date('Y-m-d H:i:s',strtotime('-1 day'))));
denied(function () use ($access) { $access->contacts(owner(),1); },403,'VIP expiry relocks contact');
check_group($access->can_detail(owner(),1),'VIP expiry never revokes purchased details');
foreach (array(0,1) as $charge) foreach (array(0,1) as $vip) {
    $db->where('id',2)->update('users',array('charge_enabled'=>$charge,'contact_vip_required'=>$vip));
    check_group($access->can_detail(owner(),1),'Purchased detail remains visible for flag combination '.$charge.'/'.$vip);
    if ($vip) denied(function () use ($access) { $access->contacts(owner(),1); },403,'VIP gate remains independent of fee '.$charge);
    else check_group($access->contacts(owner(),1)['zalo']==='090SECRET2','Shared contact available without VIP when switch disabled '.$charge);
}
foreach (array(0,1) as $fee) foreach (array(0,1) as $contactVip) {
    $db->where('buyer_id',1)->where('owner_id',2)->delete('profile_purchases');
    $db->where('id',2)->update('users',array('charge_enabled'=>$fee,'contact_vip_required'=>$contactVip));
    $matrixQuote=$access->quote(owner(),1);
    check_group($matrixQuote['cost']===($fee ? 20 : 0) && (bool)$access->can_detail(owner(),1)===!((bool)$fee),'Unpaid access honors independent switch combination '.$fee.'/'.$contactVip);
    if ($fee) $access->purchase(2,1,$matrixQuote['version']);
    if ($contactVip) denied(function () use ($access) { $access->contacts(owner(),1); },403,'Expired VIP cannot read VIP contact for combination '.$fee.'/'.$contactVip);
    else check_group($access->contacts(owner(),1)['phone']==='090SECRET2','Contact switch OFF permits shared contact for combination '.$fee.'/'.$contactVip);
}
$db->where('buyer_id',1)->where('owner_id',2)->delete('profile_purchases');
$db->where('id',2)->update('users',array('charge_enabled'=>0,'contact_vip_required'=>0));
$free=$access->quote(owner(),1); check_group($access->purchase(2,1,$free['version'])['charged']===0 && !$access->purchased(1,2),'Free view never creates a permanent paid entitlement');
$db->where('id',2)->update('users',array('charge_enabled'=>1));
check_group(!$access->can_detail(owner(),1),'Turning charging on relocks previously free views');
denied(function () use ($access,$free) { $access->purchase(2,1,$free['version']); },409,'Settings changed while popup is open require another explicit confirmation');
$db->insert('blocks',array('user_id'=>2,'blocked_id'=>1));
denied(function () use ($access) { $access->quote(owner(),1); },403,'Blocked profile cannot be previewed or purchased');
$db->where('user_id',2)->delete('blocks');
$db->where('id',2)->update('users',array('avatar'=>null));
denied(function () use ($access) { $access->quote(owner(),1); },404,'Hidden incomplete profile cannot be purchased');
$db->where('id',2)->update('users',array('avatar'=>'avatar.png','deleted_at'=>date('Y-m-d H:i:s')));
denied(function () use ($access) { $access->quote(owner(),1); },404,'Deleted profile cannot be purchased');
$db->where('id',2)->update('users',array('deleted_at'=>null));
$balanceBefore=$db->where('id',1)->get('users')->row_array();
require FCPATH . 'database/migrate_profile_access.php';
check_group($db->where('id',1)->get('users')->row_array()['transferable_coin_balance']==$balanceBefore['transferable_coin_balance'],'Migration rerun never resets existing transferable balances');
require APPPATH . 'models/M_user.php';
$wallet=new M_user();$wallet->db=$db;
$wallet->adjust_coin(1,50,'bonus');
check_group($db->where('id',1)->get('users')->row_array()['transferable_coin_balance']==$balanceBefore['transferable_coin_balance'],'Bonus credits remain locked');
$wallet->adjust_coin(1,110,'nap',null,null,'100 paid + 10 bonus',100);
check_group($db->where('id',1)->get('users')->row_array()['transferable_coin_balance']==$balanceBefore['transferable_coin_balance']+100,'Only paid portion of top-up is transferable');
echo "PASS profile access, contact privacy, wallet accounting and entitlement persistence. Database: $testName\n";

// Two actual DB connections race to buy the same pair.
$db->where('buyer_id',1)->where('owner_id',2)->delete('profile_purchases');
$beforeRace=$db->where('id',1)->get('users')->row_array()['coin_balance'];
$version=$access->quote(owner(),1)['version'];
$workers=array();
for ($i=0;$i<2;$i++) {
    $process=proc_open(array(PHP_BINARY,__DIR__.'/profile_purchase_worker.php',$testName,$version),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    $workers[]=array($process,$pipes);
}
$charged=0;
foreach ($workers as $worker) {
    $result=stream_get_contents($worker[1][1]);$error=stream_get_contents($worker[1][2]);
    foreach ($worker[1] as $pipe) fclose($pipe);
    check_group(proc_close($worker[0])===0,'Concurrent purchase worker succeeds: '.$error);
    $charged+=json_decode($result,true)['charged'];
}
check_group($charged===20 && $db->where('id',1)->get('users')->row_array()['coin_balance']==$beforeRace-20,'Concurrent purchases transfer exactly twenty coins once');
