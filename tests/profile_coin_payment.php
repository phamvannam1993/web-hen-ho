<?php
/** Run: php tests/profile_coin_payment.php. SQLite fixtures, no .env or real balances. */
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__) . '/system/');
function log_message($level, $message) {}
function is_php($v) { return version_compare(PHP_VERSION, $v, '>='); }
function profile_unlock_cost() { return 20; }
function super_admin_unlimited($user, $type) { return false; }
function display_name($user) { return $user['display_name']; }
function avatar_url($avatar, $gender) { return '/avatar'; }
function age_from($birthday) { return 30; }
function site_url($path) { return '/' . $path; }
function verify_payment($ok, $label) { if (!$ok) throw new RuntimeException($label); }
require BASEPATH . 'database/DB_driver.php';
require BASEPATH . 'database/DB_query_builder.php';
class CI_DB extends CI_DB_query_builder {}
require BASEPATH . 'database/drivers/sqlite3/sqlite3_driver.php';
class PaymentDb extends CI_DB_sqlite3_driver {
    protected function _execute($sql) { return parent::_execute(preg_replace('/\s+FOR UPDATE\b/i', '', $sql)); }
}
class CI_Model { public $db; }
require dirname(__DIR__) . '/application/models/M_profile_access.php';
$db = new PaymentDb(array('database' => ':memory:', 'db_debug' => false)); $db->initialize();
foreach (array(
    'CREATE TABLE users(id INTEGER PRIMARY KEY,display_name TEXT,slug TEXT,gender TEXT,avatar TEXT,province_id INTEGER,birthday TEXT,status TEXT,deleted_at TEXT,charge_enabled INTEGER,contact_vip_required INTEGER,job TEXT,bio TEXT,coin_balance INTEGER,transferable_coin_balance INTEGER)',
    'CREATE TABLE user_preferences(user_id INTEGER,seeking_gender TEXT,purpose TEXT)',
    'CREATE TABLE blocks(user_id INTEGER,blocked_id INTEGER)',
    'CREATE TABLE profile_purchases(buyer_id INTEGER,owner_id INTEGER,amount INTEGER,UNIQUE(buyer_id,owner_id))',
    'CREATE TABLE coin_transactions(user_id INTEGER,amount INTEGER,transferable_amount INTEGER,balance_after INTEGER,reason TEXT,ref_type TEXT,ref_id INTEGER,note TEXT)',
) as $sql) $db->query($sql);
foreach (array(1, 2) as $id) {
    $db->insert('users', array('id' => $id, 'display_name' => 'Member ' . $id, 'slug' => 'member-' . $id,
        'gender' => 'female', 'avatar' => 'avatar.png', 'province_id' => 1, 'birthday' => '1990-01-01',
        'status' => 'active', 'charge_enabled' => 1, 'contact_vip_required' => 1,
        'job' => '', 'bio' => '', 'coin_balance' => 0, 'transferable_coin_balance' => 0));
    $db->insert('user_preferences', array('user_id' => $id, 'seeking_gender' => 'all', 'purpose' => 'hen_ho'));
}
$model = new M_profile_access(); $model->db = $db;
function wallet($id) { global $db; return $db->where('id', $id)->get('users')->row_array(); }
foreach (array(array(100, 0, 0), array(20, 0, 0), array(25, 15, 10), array(100, 100, 20)) as $case) {
    list($balance, $eligible, $eligibleSpent) = $case;
    $db->empty_table('profile_purchases'); $db->empty_table('coin_transactions');
    $db->where('id', 1)->update('users', array('coin_balance' => $balance, 'transferable_coin_balance' => $eligible));
    $db->where('id', 2)->update('users', array('coin_balance' => 0, 'transferable_coin_balance' => 0));
    $quote = $model->quote(wallet(2), 1);
    verify_payment($quote['spendable_balance'] === $balance, 'Quote uses all coins');
    $result = $model->purchase(2, 1, $quote['version']);
    verify_payment($result['charged'] === 20 && $model->purchased(1, 2), 'Bonus and mixed balances can unlock');
    verify_payment((int) wallet(1)['coin_balance'] === $balance - 20 && (int) wallet(2)['coin_balance'] === 20,
        'Buyer charged once and owner receives full amount');
    verify_payment((int) wallet(1)['transferable_coin_balance'] === $eligible - $eligibleSpent
        && (int) wallet(2)['transferable_coin_balance'] === $eligibleSpent, 'Legacy provenance remains nonnegative and conserved');
    $rows = $db->get('coin_transactions')->result_array();
    verify_payment(count($rows) === 2 && (int) $rows[0]['amount'] === -20 && (int) $rows[1]['amount'] === 20
        && (int) $rows[0]['transferable_amount'] === -$eligibleSpent
        && (int) $rows[1]['transferable_amount'] === $eligibleSpent, 'Ledger matches both wallets');
    verify_payment($model->purchase(2, 1, $quote['version'])['charged'] === 0
        && $db->count_all_results('coin_transactions') === 2, 'Retry cannot charge twice');
}
$db->empty_table('profile_purchases'); $db->empty_table('coin_transactions');
$db->where('id', 1)->update('users', array('coin_balance' => 19, 'transferable_coin_balance' => 0));
$version = $model->quote(wallet(2), 1)['version'];
try { $model->purchase(2, 1, $version); throw new Exception('Expected insufficient funds'); }
catch (RuntimeException $e) { verify_payment($e->getCode() === 422, 'Insufficient total still rejected'); }
verify_payment((int) wallet(1)['coin_balance'] === 19 && !$model->purchased(1, 2)
    && $db->count_all_results('coin_transactions') === 0, 'Failed payment changes no funds or permissions');
$db->where('id', 1)->update('users', array('coin_balance' => 100));
$beforeOwner = wallet(2)['coin_balance'];
$db->query("CREATE TRIGGER fail_payment BEFORE INSERT ON profile_purchases BEGIN SELECT RAISE(ABORT,'fixture failure'); END");
set_error_handler(function ($severity, $message) { return strpos($message, 'fixture failure') !== false; });
try { $model->purchase(2, 1, $version); throw new Exception('Expected transaction failure'); }
catch (RuntimeException $e) { verify_payment($e->getCode() === 503, 'Failed entitlement aborts payment'); }
restore_error_handler();
verify_payment((int) wallet(1)['coin_balance'] === 100 && wallet(2)['coin_balance'] === $beforeOwner
    && !$model->purchased(1, 2), 'Rollback restores both wallets');
echo "PASS: all coin sources pay, exact balance, insufficient funds, balanced ledger, duplicate confirmation and atomic rollback.\n";
