<?php
// Run: php tests/daily_match_reset.php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_daily.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$model = new M_daily();
$day = new ReflectionMethod(M_daily::class, 'ngay_goi_y');
$expiry = new ReflectionMethod(M_daily::class, 'han_dung');
$cases = array(
    array('2026-10-03 00:00:00', '2026-10-02', '2026-10-03 08:00:00'),
    array('2026-10-03 07:59:59', '2026-10-02', '2026-10-03 08:00:00'),
    array('2026-10-03 08:00:00', '2026-10-03', '2026-10-04 08:00:00'),
    array('2026-10-03 23:59:59', '2026-10-03', '2026-10-04 08:00:00'),
    array('2027-01-01 00:00:00', '2026-12-31', '2027-01-01 08:00:00'),
);
foreach ($cases as [$now, $expectedDay, $expectedExpiry]) {
    $timestamp = strtotime($now);
    $actualExpiry = $expiry->invoke($model, $timestamp);
    if ($day->invoke($model, $timestamp) !== $expectedDay || $actualExpiry !== $expectedExpiry
        || strtotime($actualExpiry) - $timestamp > 86400 || strtotime($actualExpiry) <= $timestamp) {
        throw new Exception('Wrong daily cycle at ' . $now);
    }
}
echo "Daily reset boundary checks passed.\n";
