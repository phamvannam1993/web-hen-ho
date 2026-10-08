<?php
// php tests/interest_unread.php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_interest_badge.php';
class InterestResult {
    private $rows;
    function __construct($rows) { $this->rows = $rows; }
    function result_array() { return $this->rows; }
}
class InterestDb {
    public $ready = true, $can_create = true, $db_debug = true, $data_cache = array(), $events = array(), $seen = array(), $queries = array();
    function table_exists($name) { return $this->ready; }
    function where($key, $value) { return $this; }
    function update($table, $data) {}
    function query($sql) {
        $this->queries[] = $sql;
        if (strpos($sql, 'CREATE TABLE IF NOT EXISTS') === 0) {
            if (!$this->can_create) return false;
            $this->ready = true;
            return true;
        }
        if (strpos($sql, 'INSERT INTO') === 0) {
            preg_match("/SELECT (\d+), '([^']+)'/", $sql, $match);
            $this->seen[$match[1]][$match[2]] = $this->events[$match[2]];
            return new InterestResult(array());
        }
        preg_match('/(?:seen.user_id = |l.target_id = )(\d+)/', $sql, $owner);
        $id = $owner[1];
        $rows = array();
        foreach ($this->events as $tab => $events) {
            $count = 0;
            foreach ($events as $item => $version) {
                if (strpos($sql, 'LEFT JOIN account_interest_seen') === false || ($this->seen[$id][$tab][$item] ?? null) !== $version) $count++;
            }
            $rows[] = array('tab' => $tab, 'unread' => $count);
        }
        return new InterestResult($rows);
    }
}
class InterestModel extends M_interest_badge { public $db; }
function check_interest($ok, $message) { if (!$ok) throw new Exception($message); }
$model = new InterestModel(); $model->db = new InterestDb();
foreach (array('thich-ban' => 2, 'ghep-doi' => 3, 'da-xem' => 1, 'ban-thich' => 10) as $tab => $size) {
    $model->db->events[$tab] = array_fill(1, $size, '1');
}
check_interest(array_sum($model->counts(7)) === 16, 'Count all four unread tabs without UI list limits');
check_interest(strpos(end($model->db->queries), 'CAST(seen.version AS BINARY) = CAST(events.version AS BINARY)') !== false,
    'Event version equality must work across unicode_ci and general_ci collations');
$model->mark_seen(7, 'ban-thich');
$counts = $model->counts(7);
check_interest($counts['ban-thich'] === 0 && array_sum($counts) === 6, 'Viewing outgoing tab subtracts only outgoing entries');
check_interest(array_sum($model->counts(8)) === 16, 'Read state belongs to current account only');
$model->mark_seen(7, 'thich-ban');
check_interest(array_sum($model->counts(7)) === 4, 'Second tab clears independently');
$model->mark_seen(7, 'da-xem');
$model->db->events['da-xem'][1] = '2';
check_interest($model->counts(7)['da-xem'] === 1, 'Repeated view produces new unread version');
$model->db->events['ban-thich'][11] = '11';
check_interest($model->counts(7)['ban-thich'] === 1, 'New like after read is unread');
check_interest($model->counts(7, false)['ban-thich'] === 11, 'Reading does not delete underlying likes or total tab counts');
check_interest(!$model->mark_seen(7, "ban-thich'"), 'Reject unsupported tab');
$fresh = new InterestModel(); $fresh->db = new InterestDb();
$fresh->db->events = $model->db->events;
$fresh->db->ready = false;
check_interest(array_sum($fresh->counts(7)) === 17 && $fresh->db->ready, 'Missing migration initializes storage and counts likes immediately');
check_interest($fresh->db->db_debug === true, 'Database debug setting is restored after setup');
$fresh->mark_seen(7, 'ban-thich');
check_interest($fresh->counts(7)['ban-thich'] === 0, 'Auto-created storage still persists tab read state');
$denied = new InterestModel(); $denied->db = new InterestDb();
$denied->db->ready = false; $denied->db->can_create = false;
check_interest(array_sum($denied->counts(7)) === 0, 'Denied schema permission avoids SQL failure');
echo "PASS: tab totals, independent read state, account isolation, new events and automatic storage setup.\n";
