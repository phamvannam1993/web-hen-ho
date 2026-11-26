<?php
// Run: php tests/account_overview_counts.php. Isolated SQLite fixture.
error_reporting(E_ALL & ~E_DEPRECATED);
define('BASEPATH', dirname(__DIR__).'/system/');
function log_message($l,$m) {} function is_php($v) { return version_compare(PHP_VERSION,$v,'>='); }
require BASEPATH.'database/DB_driver.php'; require BASEPATH.'database/DB_query_builder.php';
class CI_DB extends CI_DB_query_builder {}
require BASEPATH.'database/drivers/sqlite3/sqlite3_driver.php';
class CI_Model { public $db; }
require dirname(__DIR__).'/application/models/M_interaction.php';
$db = new CI_DB_sqlite3_driver(array('database'=>':memory:','db_debug'=>true)); $db->initialize();
$db->conn_id->createFunction('IF', function ($condition,$yes,$no) { return $condition ? $yes : $no; }, 3);
$db->query('CREATE TABLE users(id INTEGER PRIMARY KEY,deleted_at TEXT)');
$db->query('CREATE TABLE matches(user_low_id INTEGER,user_high_id INTEGER)');
$db->insert('users',array('id'=>1));
for ($id=2;$id<=27;$id++) {
    $db->insert('users',array('id'=>$id,'deleted_at'=>$id===27 ? '2026-11-25' : null));
    $db->insert('matches',array('user_low_id'=>1,'user_high_id'=>$id));
}
$db->insert('matches',array('user_low_id'=>2,'user_high_id'=>3));
$model = new M_interaction(); $model->db=$db;
if ($model->matches_count(1)!==25) throw new RuntimeException('Overview must count all matches beyond eight and exclude deleted peers');
if ($model->matches_count(2)!==2) throw new RuntimeException('Count must work for either participant and isolate the viewer');
if ($model->matches_count(999)!==0) throw new RuntimeException('Account without matches must show zero');
echo "PASS: full match totals, both participant positions, deleted profiles and account isolation.\n";
