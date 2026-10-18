<?php
/** Uses only the isolated database created by the existing integration harness. */
require __DIR__ . '/chat_groups_integration.php';
require_once APPPATH . 'libraries/Achievements.php';

$days = array('2026-10-10', '2026-10-10', '2026-10-11', '2026-10-12');
check_group(Achievements::message_streaks($days, '2026-10-12') === array('best'=>3, 'current'=>3), 'Multiple messages on one day count once');
check_group(Achievements::message_streaks($days, '2026-10-14') === array('best'=>3, 'current'=>0), 'Missed days reset current progress and preserve record');
check_group(Achievements::message_streaks(array('2026-10-10','2026-10-12'), '2026-10-12')['best'] === 1, 'Nonconsecutive messaging days do not unlock a streak');

$db->where('user_id', 2)->update('user_streaks', array('current_streak'=>10, 'longest_streak'=>10, 'last_active_date'=>date('Y-m-d')));
foreach (array('-2 days', '-1 day') as $offset) {
    $db->insert('room_messages', array('room_id'=>2, 'user_id'=>2, 'actor_key'=>'u:2', 'type'=>'text', 'content'=>'Achievement test', 'created_at'=>date('Y-m-d 12:00:00', strtotime($offset))));
}
$earned = Achievements::badges($db, 2);
$names = array_column($earned, 'name');
check_group(in_array('Mười ngày bền bỉ', $names, true), 'Ten-day login record unlocks its badge');
check_group(in_array('Kết nối mỗi ngày', $names, true), 'Messaging on three consecutive days unlocks its badge');
$awards = $db->where('user_id',2)->like('badge_code','achievement_', 'after')->count_all_results('streak_badges');
$notifications = $db->where('user_id',2)->count_all_results('notifications');
Achievements::sync($db, 2);
check_group($awards === $db->where('user_id',2)->like('badge_code','achievement_', 'after')->count_all_results('streak_badges'), 'Repeated evaluation never duplicates awards');
check_group($notifications === $db->where('user_id',2)->count_all_results('notifications'), 'Repeated evaluation never duplicates achievement notifications');
$db->where('user_id',2)->update('user_streaks', array('current_streak'=>1, 'longest_streak'=>10));
check_group(in_array('Mười ngày bền bỉ', array_column(Achievements::badges($db,2), 'name'), true), 'Earned badges survive broken streaks');
$anonymous = $m->anonymous_profile($room,$guest,$anonymousId);
check_group($anonymous['badges'] === Achievements::badges($db,2), 'Anonymous and public profiles share awarded badges');
foreach ($anonymous['badges'] as $badge) check_group(array_keys($badge) === array('name','icon','description'), 'Anonymous badge metadata contains no account identity or activity dates');
echo "PASS achievement awards, daily progress, notifications, permanence and anonymous privacy.\n";
