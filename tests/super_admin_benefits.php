<?php
define('BASEPATH', __DIR__);
require __DIR__ . '/../application/helpers/app_helper.php';
function benefit_check($value, $message) { if (!$value) throw new RuntimeException($message); }
$user = array('role'=>'super_admin','status'=>'active','deleted_at'=>null,'is_vip'=>0,'vip_expired_at'=>null);
benefit_check(super_admin_unlimited($user,'coins') && member_has_vip($user), 'Default privileges');
$user['super_unlimited_coins']=0;
benefit_check(!super_admin_unlimited($user,'coins') && member_has_vip($user), 'Independent coins switch');
$user['super_unlimited_vip']=0;
benefit_check(!member_has_vip($user), 'VIP off');
$user['is_vip']=1; $user['vip_expired_at']='2999-01-01 00:00:00';
benefit_check(member_has_vip($user), 'Existing paid VIP retained');
foreach (array('member','admin','moderator') as $role) {
    $user['role']=$role; $user['super_unlimited_coins']=1; $user['super_unlimited_vip']=1; $user['is_vip']=0;
    benefit_check(!super_admin_unlimited($user,'coins') && !member_has_vip($user), 'No privilege for '.$role);
}
$user['role']='super_admin'; $user['status']='locked';
benefit_check(!super_admin_unlimited($user,'coins'), 'Locked account');
benefit_check(!super_admin_unlimited(null,'coins'), 'Guest');
echo "PASS: defaults, independent switches, paid VIP retained, role boundaries\n";
