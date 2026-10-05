<?php
// Run: php -d extension=pdo_sqlite tests/retention_emails.php
require __DIR__ . '/daily_match_filters.php';
class CI_Controller {}
require __DIR__ . '/../application/controllers/Cron.php';
require __DIR__ . '/../application/models/M_email.php';
class RetentionDB extends DailyTestDB {
    public $members = array();
    function query($sql, $bindings = array()) {
        if (strpos($sql, 'SELECT u.* FROM users u LEFT JOIN email_prefs') !== false) return new DailyTestResult($this->members);
        $sql = str_replace('GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR \'|\')', "GROUP_CONCAT(i.name, '|')", $sql);
        $sql = str_replace('NOW() - INTERVAL 1 DAY', "'" . date('Y-m-d H:i:s', strtotime('-1 day')) . "'", $sql);
        return parent::query($sql, $bindings);
    }
}
class RetentionUsers extends M_user {
    public $users = array(), $missing = array();
    function find($id) { return $this->users[$id] ?? null; }
    function thieu_thong_tin($id) { return $this->missing[$id] ?? array(); }
}
class RetentionQueue extends M_email {
    public $settings = array(), $reserved = array();
    function prefs($id) { return $this->settings[$id] ?? array('match_every_days' => 2, 'match_suggest' => 1, 're_engage' => 1, 'disabled_at' => null); }
    function duoc_gui($id, $type, $ignore_cap = false) { $p = $this->prefs($id); return !$p['disabled_at'] && !empty($p[$type === 'match_suggest' ? 'match_suggest' : 're_engage']); }
    function retention_due($id, $days = 1) { return empty($this->reserved[$id]); }
}
class RetentionEmailer {
    public $rows = array(), $queue;
    function add($id, $type, $data) {
        if (!$this->queue->duoc_gui($id, $type)) return false;
        $this->rows[$id] = array($type, $data); $this->queue->reserved[$id] = true; return true;
    }
    function activation_nudge($id, $verify, $missing) { return $this->add($id, 'activation_nudge', array($verify, $missing)); }
    function match_suggest($id, $match, $score) { return $this->add($id, 'match_suggest', $match); }
    function re_engage($id, $likes, $avatars) { return $this->add($id, 're_engage', $likes); }
}
class RetentionInteraction { function liked_me_count($id) { return 0; } }
class RetentionCron extends Cron {
    public $db, $m_user, $m_email, $emailer, $m_interaction;
    function __construct() {}
}
$cron = new RetentionCron(); $cron->db = new RetentionDB(); $db = $cron->db->pdo;
$db->exec("CREATE TABLE provinces (id INTEGER, name TEXT);
    CREATE TABLE interests (id INTEGER, name TEXT);
    CREATE TABLE email_queue (id INTEGER, user_id INTEGER, type TEXT, related_id INTEGER, status TEXT, created_at TEXT, sent_at TEXT);
    INSERT INTO provinces VALUES (1, 'City');");
$cron->m_user = new RetentionUsers(); $cron->m_email = new RetentionQueue();
$cron->emailer = new RetentionEmailer(); $cron->emailer->queue = $cron->m_email;
$cron->m_interaction = new RetentionInteraction();
fixture($db);
$db->exec("UPDATE users SET last_active_at = '" . date('Y-m-d H:i:s') . "'");
$base = $owner + array('status' => 'active', 'role' => 'member', 'deleted_at' => null,
    'email' => 'member@example.test', 'email_verified_at' => date('Y-m-d H:i:s'),
    'last_active_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'));
$select = new ReflectionMethod(Cron::class, 'tim_nguoi_hop');
check($select->invoke($cron, $base)['id'] === 2, 'Eligible candidate selected');
$db->exec("INSERT INTO email_queue VALUES (1, 1, 'match_suggest', 2, 'sent', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "')");
check($select->invoke($cron, $base) === null, 'Recently suggested candidate excluded inside selection');
check($select->invoke($cron, $base, 2)['id'] === 2, 'Queued candidate can be revalidated despite own history');
$db->exec("DELETE FROM email_queue; INSERT INTO users SELECT 3, display_name, gender, birthday, province_id, avatar, bio, status, role, deleted_at, last_active_at FROM users WHERE id = 2;
    INSERT INTO user_preferences VALUES (3, 'male', 18, 60, 'hen_ho');
    INSERT INTO email_queue VALUES (1, 1, 'match_suggest', 2, 'pending', '" . date('Y-m-d H:i:s') . "', NULL)");
check($select->invoke($cron, $base)['id'] === 3, 'Select next candidate instead of abandoning user');
$db->exec("DELETE FROM email_queue; DELETE FROM users WHERE id = 3; DELETE FROM user_preferences WHERE user_id = 3;");
foreach (array("UPDATE users SET birthday = '" . date('Y-m-d', strtotime('-17 years')) . "'",
    "UPDATE user_preferences SET purpose = 'tam_su' WHERE user_id = 2",
    "UPDATE users SET avatar = ''",
    "INSERT INTO user_passes VALUES (1, 2)", "INSERT INTO blocks VALUES (2, 1)",
    "UPDATE users SET status = 'banned'") as $mutation) {
    fixture($db); $db->exec($mutation);
    check($select->invoke($cron, $base) === null, 'Reject incompatible/unsafe candidate: ' . $mutation);
}
fixture($db);
$db->exec("UPDATE users SET last_active_at = '" . date('Y-m-d H:i:s') . "'");
$members = array();
for ($id = 1; $id <= 5; $id++) { $u = $base; $u['id'] = $id; $members[] = $u; $cron->m_user->users[$id] = $u; }
$members[1]['email_verified_at'] = null;
$members[3]['last_active_at'] = null; // Never returned since registration.
$members[4]['last_active_at'] = date('Y-m-d H:i:s', strtotime('-200 days'));
$cron->m_user->missing[3] = array('Ảnh');
$cron->m_email->settings[5] = array('match_every_days' => 2, 'match_suggest' => 1, 're_engage' => 1, 'disabled_at' => '2026-01-01');
$cron->db->members = $members;
$db->exec("INSERT INTO user_passes VALUES (4, 2)");
ob_start(); $cron->goi_y(); $cron->goi_y(); ob_end_clean();
check(count($cron->emailer->rows) === 4, 'No duplicate dispatch; unsubscribe respected');
check($cron->emailer->rows[1][0] === 'match_suggest', 'Recently active member included');
check($cron->emailer->rows[2][1][0] === true, 'Unverified member gets verification reminder');
check($cron->emailer->rows[3][0] === 'activation_nudge', 'Incomplete member gets setup reminder');
check($cron->emailer->rows[4][0] === 're_engage', 'Never-returned member gets fallback when no match');
$queue = new M_email(); $queue->db = $cron->db;
$db->exec("INSERT INTO email_queue VALUES (9, 99, 'activation_nudge', NULL, 'pending', '2020-01-01', NULL)");
check(!$queue->retention_due(99), 'Pending mail reserves slot even if old');
$db->exec("UPDATE email_queue SET status = 'skipped' WHERE id = 9");
check($queue->retention_due(99), 'Skipped reminder can be reconsidered');
class CapQueue extends RetentionQueue {
    public $cap = true;
    function duoc_gui($id, $type, $ignore_cap = false) { return parent::duoc_gui($id, $type, $ignore_cap) && ($ignore_cap || !$this->cap); }
}
$cron->m_email = new CapQueue();
$validate = new ReflectionMethod(Cron::class, 'con_hop_le');
$mail = array('user_id' => 1, 'type' => 're_engage', 'to_email' => $base['email']);
check($validate->invoke($cron, $mail) === 'daily_cap', 'Cap defers mail rather than discarding it');
$cron->m_email->settings[1] = array('match_every_days' => 2, 'match_suggest' => 1, 're_engage' => 0, 'disabled_at' => null);
check($validate->invoke($cron, $mail) !== 'daily_cap', 'Opt-out is skipped, not deferred');
$cron->m_email = new RetentionQueue();
$mail['to_email'] = 'outdated@example.test';
check($validate->invoke($cron, $mail) === 'Recipient email changed', 'Never deliver to old email address');
$mail['to_email'] = $base['email']; $mail['type'] = 'activation_nudge';
check($validate->invoke($cron, $mail) === 'Setup already completed', 'Completed setup suppresses stale activation reminder');
$mail['type'] = 'match_suggest'; $mail['related_id'] = 2;
$db->exec("UPDATE users SET status = 'banned' WHERE id = 2");
check($validate->invoke($cron, $mail) === 'Suggested profile or recipient no longer eligible', 'Worker rejects newly suspended suggested profile');
echo "PASS: retention coverage, opt-out, no duplicates, strict candidates, fallback and pending slots.\n";
