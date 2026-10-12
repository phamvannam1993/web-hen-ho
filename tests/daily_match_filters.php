<?php
// Run: php tests/daily_match_filters.php
// Execute the model's SQL against an isolated SQLite fixture. Only MySQL date
// syntax/functions are adapted; eligibility predicates and bindings are intact.
define('BASEPATH', __DIR__);
class CI_Model {}
function age_from($birthday) { return (int) (new DateTime($birthday))->diff(new DateTime('today'))->y; }
require __DIR__ . '/../application/models/M_user.php';
require __DIR__ . '/../application/models/M_daily.php';

class DailyTestResult {
    private $rows;
    function __construct($rows) { $this->rows = $rows; }
    function row_array() { return $this->rows ? $this->rows[0] : null; }
    function result_array() { return $this->rows; }
}
class DailyTestDB {
    public $pdo;
    private $where = array();
    function __construct() {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->sqliteCreateFunction('IF', function ($c, $a, $b) { return $c ? $a : $b; }, 3);
        $this->pdo->sqliteCreateFunction('CHAR_LENGTH', 'mb_strlen', 1);
        $this->pdo->sqliteCreateFunction('RAND', function () { return 0; }, 0);
        $this->pdo->sqliteCreateFunction('TIMESTAMPDIFF', function ($unit, $birth, $today) {
            return $birth ? (int) (new DateTime($birth))->diff(new DateTime($today))->y : null;
        }, 3);
        $this->pdo->exec("CREATE TABLE users (id INTEGER, display_name TEXT, gender TEXT,
            birthday TEXT, province_id INTEGER, avatar TEXT, bio TEXT, status TEXT,
            role TEXT, deleted_at TEXT, last_active_at TEXT);
            CREATE TABLE user_preferences (user_id INTEGER, seeking_gender TEXT, age_min INTEGER, age_max INTEGER, purpose TEXT);
            CREATE TABLE user_interests (user_id INTEGER, interest_id INTEGER);
            CREATE TABLE likes (user_id INTEGER, target_type TEXT, target_id INTEGER);
            CREATE TABLE user_passes (user_id INTEGER, passed_id INTEGER);
            CREATE TABLE matches (user_low_id INTEGER, user_high_id INTEGER);
            CREATE TABLE blocks (user_id INTEGER, blocked_id INTEGER);
            CREATE TABLE daily_matches (id INTEGER, user_id INTEGER, match_user_id INTEGER);");
    }
    function where($key, $value) { $this->where[$key] = $value; return $this; }
    function get($table) {
        $parts = array(); $values = array();
        foreach ($this->where as $key => $value) { $parts[] = "$key = ?"; $values[] = $value; }
        $this->where = array();
        return $this->query('SELECT * FROM ' . $table . ($parts ? ' WHERE ' . implode(' AND ', $parts) : ''), $values);
    }
    function query($sql, $bindings = array()) {
        if (substr_count($sql, '?') !== count($bindings)) { throw new Exception('Binding count mismatch'); }
        $sql = str_replace(array('DATE_SUB(NOW(), INTERVAL 1 MONTH)', 'NOW() - INTERVAL 1 DAY', 'CURDATE()', 'TIMESTAMPDIFF(YEAR,'),
            array("'" . date('Y-m-d H:i:s', strtotime('-1 month')) . "'", "'" . date('Y-m-d H:i:s', strtotime('-1 day')) . "'", "'" . date('Y-m-d') . "'", "TIMESTAMPDIFF('YEAR',"), $sql);
        $stmt = $this->pdo->prepare($sql);
        foreach ($bindings as $i => $value) { $stmt->bindValue($i + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        return new DailyTestResult($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
class DailyTestLoader { function model($name) {} }
class DailyFilterHarness extends M_daily {
    public $db, $load, $m_user;
}
function check($condition, $label) { if (!$condition) { throw new Exception($label); } }
$model = new DailyFilterHarness();
$model->db = new DailyTestDB();
$model->load = new DailyTestLoader();
$model->m_user = new M_user();
$pdo = $model->db->pdo;
$owner = array('id' => 1, 'birthday' => date('Y-m-d', strtotime('-30 years')), 'province_id' => 1);
$pick = new ReflectionMethod(M_daily::class, 'tim_ung_vien');
$pick->setAccessible(true);
function fixture($pdo) {
    foreach (array('users', 'user_preferences', 'likes', 'user_passes', 'matches', 'blocks', 'daily_matches') as $table) { $pdo->exec("DELETE FROM $table"); }
    $pdo->exec("INSERT INTO user_preferences VALUES (1, 'female', 25, 35, 'hen_ho'), (2, 'male', 18, 60, 'hen_ho')");
    $stmt = $pdo->prepare("INSERT INTO users VALUES (2, 'Candidate', 'female', ?, 1, 'avatar.jpg', '', 'active', 'member', NULL, ?)");
    $stmt->execute(array(date('Y-m-d', strtotime('-30 years')), date('Y-m-d H:i:s', strtotime('-20 days'))));
}
fixture($pdo);
check($pick->invoke($model, $owner)['id'] === 2, 'Valid woman active 20 days ago must qualify');
$excluded = array(
    "UPDATE users SET gender = 'male'",
    "UPDATE users SET birthday = '" . date('Y-m-d', strtotime('-24 years')) . "'",
    "UPDATE users SET birthday = '" . date('Y-m-d', strtotime('-36 years')) . "'",
    "UPDATE users SET birthday = NULL",
    "UPDATE users SET status = 'pending'",
    "UPDATE users SET role = 'admin'",
    "UPDATE users SET deleted_at = '2020-01-01'",
    "UPDATE users SET last_active_at = '" . date('Y-m-d H:i:s', strtotime('-2 months')) . "'",
    "UPDATE users SET last_active_at = NULL",
    "UPDATE users SET avatar = ''",
    "UPDATE users SET province_id = NULL",
    "UPDATE users SET display_name = ''",
    "DELETE FROM user_preferences WHERE user_id = 2",
    "UPDATE user_preferences SET purpose = '' WHERE user_id = 2",
    "INSERT INTO likes VALUES (1, 'user', 2)",
    "INSERT INTO user_passes VALUES (1, 2)",
    "INSERT INTO matches VALUES (1, 2)",
    "INSERT INTO matches VALUES (2, 1)",
    "INSERT INTO blocks VALUES (1, 2)",
    "INSERT INTO blocks VALUES (2, 1)",
    "INSERT INTO daily_matches VALUES (9, 1, 2)",
    "DELETE FROM user_preferences WHERE user_id = 1",
    "UPDATE user_preferences SET age_min = 40, age_max = 25 WHERE user_id = 1",
);
foreach ($excluded as $sql) {
    fixture($pdo); $pdo->exec($sql);
    check($pick->invoke($model, $owner) === null, 'Must exclude: ' . $sql);
}
foreach (array(25, 35) as $age) {
    fixture($pdo);
    $pdo->exec("UPDATE users SET birthday = '" . date('Y-m-d', strtotime("-$age years")) . "'");
    check($pick->invoke($model, $owner) !== null, 'Age boundary must be inclusive');
}
fixture($pdo);
$pdo->exec('INSERT INTO daily_matches VALUES (9, 1, 2)');
check($pick->invoke($model, $owner, 2, 9) !== null, 'Current saved card must not exclude itself');
$pdo->exec("UPDATE user_preferences SET seeking_gender = 'male' WHERE user_id = 1");
check($pick->invoke($model, $owner, 2, 9) === null, 'Saved card must honor updated gender');
fixture($pdo);
$pdo->exec('INSERT INTO daily_matches VALUES (9, 1, 2)');
$pdo->exec("INSERT INTO likes VALUES (1, 'user', 2)");
check($pick->invoke($model, $owner, 2, 9) === null, 'Externally liked pending card must be excluded');
check($pick->invoke($model, $owner, 2, 9, false) !== null, 'Answered card may retain its result');
echo 'PASS: daily filters, inclusive age boundaries, saved preferences and answered cards' . PHP_EOL;
