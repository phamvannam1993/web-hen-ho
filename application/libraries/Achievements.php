<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Permanent awards backed by the existing unique (user_id, badge_code) key. */
class Achievements
{
    public static function definitions()
    {
        return array(
            'login_3' => array('name'=>'Khởi đầu đều đặn', 'icon'=>'🌱', 'kind'=>'login', 'goal'=>3),
            'login_7' => array('name'=>'Một tuần gắn bó', 'icon'=>'🌟', 'kind'=>'login', 'goal'=>7),
            'login_10' => array('name'=>'Mười ngày bền bỉ', 'icon'=>'🔥', 'kind'=>'login', 'goal'=>10),
            'login_30' => array('name'=>'Một tháng đồng hành', 'icon'=>'🏆', 'kind'=>'login', 'goal'=>30),
            'login_100' => array('name'=>'Người bạn lâu năm', 'icon'=>'👑', 'kind'=>'login', 'goal'=>100),
            'chat_1' => array('name'=>'Lời chào đầu tiên', 'icon'=>'👋', 'kind'=>'chat', 'goal'=>1),
            'chat_3' => array('name'=>'Kết nối mỗi ngày', 'icon'=>'💬', 'kind'=>'chat', 'goal'=>3),
            'chat_7' => array('name'=>'Một tuần sẻ chia', 'icon'=>'🫶', 'kind'=>'chat', 'goal'=>7),
            'chat_30' => array('name'=>'Người giữ nhịp', 'icon'=>'💎', 'kind'=>'chat', 'goal'=>30),
        );
    }

    /** Distinct calendar days count once, including private and group messages. */
    public static function message_streaks($dates, $today)
    {
        $dates = array_values(array_unique($dates));
        sort($dates);
        $best = 0; $run = 0; $previous = null;
        foreach ($dates as $day) {
            if ($day > $today) continue;
            $run = $previous && $day === date('Y-m-d', strtotime($previous . ' +1 day')) ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $day;
        }
        $current = $previous && $previous >= date('Y-m-d', strtotime($today . ' -1 day')) ? $run : 0;
        return array('best'=>$best, 'current'=>$current);
    }

    public static function sync($db, $user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id < 1) return array();
        $today = date('Y-m-d');
        $login = $db->where('user_id', $user_id)->get('user_streaks')->row_array();
        $days = $db->query('SELECT DATE(created_at) AS day FROM messages WHERE sender_id=?
            UNION SELECT DATE(created_at) AS day FROM room_messages WHERE user_id=?', array($user_id, $user_id))->result_array();
        $chat = self::message_streaks(array_column($days, 'day'), $today);
        $best = array('login'=>$login ? max((int) $login['longest_streak'], (int) $login['current_streak']) : 0, 'chat'=>$chat['best']);
        $current = array('login'=>$login && $login['last_active_date'] >= date('Y-m-d', strtotime('-1 day')) ? (int) $login['current_streak'] : 0, 'chat'=>$chat['current']);
        $owned = array_column($db->where('user_id', $user_id)->get('streak_badges')->result_array(), 'badge_code');
        $result = array();
        foreach (self::definitions() as $code => $definition) {
            $storage_code = 'achievement_' . $code;
            $earned = in_array($storage_code, $owned, true);
            if (!$earned && $best[$definition['kind']] >= $definition['goal']) {
                $db->query('INSERT IGNORE INTO streak_badges(user_id,badge_code) VALUES (?,?)', array($user_id, $storage_code));
                if ($db->affected_rows() === 1) {
                    $db->insert('notifications', array('user_id'=>$user_id, 'type'=>'system',
                        'title'=>'Bạn vừa đạt huy hiệu ' . $definition['name'] . '!',
                        'body'=>'Xem thành tựu và mốc tiếp theo của bạn.', 'url'=>site_url('tai-khoan/chuoi')));
                }
                $earned = true;
            }
            $definition['code'] = $code;
            $definition['description'] = ($definition['kind'] === 'login' ? 'Đăng nhập ' : 'Gửi tin nhắn trong ') . $definition['goal'] . ' ngày liên tiếp';
            $definition['earned'] = $earned;
            $definition['progress'] = min($definition['goal'], $current[$definition['kind']]);
            $result[] = $definition;
        }
        return $result;
    }

    /** Only badge metadata is public: no dates, IDs, recipients, or activity history. */
    public static function badges($db, $user_id)
    {
        $badges = array();
        foreach (self::sync($db, $user_id) as $item) {
            if ($item['earned']) $badges[] = array('name'=>$item['name'], 'icon'=>$item['icon'], 'description'=>$item['description']);
        }
        return $badges;
    }
}
