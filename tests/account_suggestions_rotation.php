<?php
define('BASEPATH', __DIR__);
class CI_Model {}
require __DIR__ . '/../application/models/M_user.php';
class RotationUser extends M_user {
    public $candidates = array();
    public function suggestions($user, $limit = 12, $offset = 0) {
        if ($limit < 30 || $offset !== 0) throw new Exception('Must use suitable shortlist');
        return $this->candidates;
    }
}
function verify_rotation($ok, $message) { if (!$ok) throw new Exception($message); }
function vietnam_time($date) { return (new DateTimeImmutable($date, new DateTimeZone('Asia/Ho_Chi_Minh')))->getTimestamp(); }
date_default_timezone_set('America/New_York'); // Must be independent of server timezone.
$model = new RotationUser();
$user = array('id' => 12);
for ($id = 1; $id <= 10; $id++) $model->candidates[] = array('id' => $id, 'match_score' => 100 - $id);
$before = $model->account_suggestions($user, 3, vietnam_time('2026-10-05 07:59:59'));
$earlier = $model->account_suggestions($user, 3, vietnam_time('2026-10-04 08:00:00'));
$after = $model->account_suggestions($user, 3, vietnam_time('2026-10-05 08:00:00'));
verify_rotation($before === $earlier, 'Stable until 08:00');
verify_rotation($before !== $after, 'Rotate at exactly 08:00');
verify_rotation($after === $model->account_suggestions($user, 3, vietnam_time('2026-10-06 07:59:59')), 'Stable entire 24-hour cycle');
verify_rotation(count(array_unique(array_column($after, 'id'))) === 3, 'Three distinct candidates');
verify_rotation($after !== $model->account_suggestions(array('id' => 99), 3, vietnam_time('2026-10-05 08:00:00')), 'Personal order per member');
$model->candidates = array_reverse($model->candidates);
verify_rotation($after === $model->account_suggestions($user, 3, vietnam_time('2026-10-05 08:00:00')), 'Score-order changes alone do not reshuffle selection');
$model->candidates = array_slice($model->candidates, 0, 2);
verify_rotation(count($model->account_suggestions($user)) === 2, 'Do not duplicate small pools');
$model->candidates = array();
verify_rotation($model->account_suggestions($user) === array(), 'Empty suitable pool');
echo "PASS: account suggestion rotation at 08:00 Vietnam, stable cycles and eligible shortlist.\n";
