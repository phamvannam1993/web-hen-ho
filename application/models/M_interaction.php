<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Thích / ghép đôi / chặn / hội thoại / tin nhắn. */
class M_interaction extends CI_Model
{
    /* ------------------------- Thích ------------------------- */

    /** Bật/tắt lượt thích. Trả về ['liked'=>bool,'matched'=>bool,'count'=>int]. */
    public function toggle_like($user_id, $target_type, $target_id)
    {
        $exists = $this->db->where(array(
            'user_id' => $user_id, 'target_type' => $target_type, 'target_id' => $target_id,
        ))->get('likes')->row_array();

        if ($exists) {
            $this->db->where('id', $exists['id'])->delete('likes');
            $liked = false;
            // Rút lại lượt thích của một cặp đã ghép đôi thì gỡ luôn ghép đôi,
            // khung chat giữa hai người khoá lại theo.
            if ($target_type === 'user') {
                $this->unmatch($user_id, $target_id);
            }
        } else {
            $this->db->insert('likes', array(
                'user_id'     => $user_id,
                'target_type' => $target_type,
                'target_id'   => $target_id,
                'status'      => 'pending',
            ));
            $liked = true;
        }

        if ($target_type === 'post') {
            $this->db->set('like_count', 'GREATEST(like_count + ' . ($liked ? 1 : -1) . ', 0)', false)
                ->where('id', $target_id)->update('posts');
        }

        $matched = false;
        if ($liked && $target_type === 'user') {
            $this->load->model('m_notification');
            $matched = $this->check_match($user_id, $target_id);
            if (!$matched) {
                $this->m_notification->push($target_id, 'like', 'Có 1 người vừa thích bạn',
                    'Thích lại để ghép đôi và mở khung trò chuyện.',
                    site_url('tai-khoan/quan-tam'));
            }
        }

        return array(
            'liked'   => $liked,
            'matched' => $matched,
            'count'   => $this->count_likes($target_type, $target_id),
        );
    }

    public function count_likes($target_type, $target_id)
    {
        return (int) $this->db->where('target_type', $target_type)->where('target_id', $target_id)
            ->count_all_results('likes');
    }

    public function has_liked($user_id, $target_type, $target_id)
    {
        if (!$user_id) return false;
        return $this->db->where(array(
            'user_id' => $user_id, 'target_type' => $target_type, 'target_id' => $target_id,
        ))->count_all_results('likes') > 0;
    }

    /**
     * Ai đã thích tôi và đang chờ tôi trả lời.
     * Không lấy lượt đã ghép đôi (đã nằm ở danh sách ghép đôi) và lượt tôi đã
     * bỏ qua (coi như không còn tồn tại với tôi).
     */
    public function liked_me($user_id, $limit = 30)
    {
        return $this->db->select('u.*, p.name AS province_name, l.created_at AS liked_at')
            ->from('likes l')->join('users u', 'u.id = l.user_id')
            ->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('l.target_type', 'user')->where('l.target_id', $user_id)
            ->where('l.status', 'pending')
            ->where('u.deleted_at', null)
            ->order_by('l.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    /** Số lượt thích đang chờ tôi trả lời. */
    public function liked_me_count($user_id)
    {
        return (int) $this->db->from('likes l')->join('users u', 'u.id = l.user_id')
            ->where('l.target_type', 'user')->where('l.target_id', $user_id)
            ->where('l.status', 'pending')->where('u.deleted_at', null)
            ->count_all_results();
    }

    /**
     * Tôi đã thích ai (chưa thành ghép đôi).
     * Lượt bị người kia bỏ qua vẫn hiện ở đây: người gửi không bao giờ biết
     * mình đã bị từ chối.
     */
    public function my_likes($user_id, $limit = 30)
    {
        return $this->db->select('u.*')->from('likes l')->join('users u', 'u.id = l.target_id')
            ->where('l.target_type', 'user')->where('l.user_id', $user_id)
            ->where('l.status !=', 'matched')
            ->where('u.deleted_at', null)
            ->order_by('l.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    /* ------------------------- Ghép đôi ------------------------- */

    /**
     * Nếu hai bên cùng thích nhau thì tạo match + thông báo.
     *
     * Lượt thích người kia đã bị bỏ qua ('rejected') không tính là thích lại,
     * nếu không thì người bị từ chối chỉ cần thích lại là ép được ghép đôi.
     */
    private function check_match($user_id, $other_id)
    {
        $nguoc = $this->db->where(array(
            'user_id' => $other_id, 'target_type' => 'user', 'target_id' => $user_id,
        ))->get('likes')->row_array();

        if (!$nguoc || $nguoc['status'] === 'rejected') {
            return false;
        }
        $this->create_match($user_id, $other_id);
        return true;
    }

    /**
     * Chốt ghép đôi giữa hai người: đánh dấu cả hai lượt thích là 'matched',
     * tạo bản ghi matches và báo cho cả hai. Gọi lại nhiều lần không sinh trùng.
     */
    private function create_match($user_id, $other_id)
    {
        $this->db->where('target_type', 'user')
            ->group_start()
                ->group_start()->where('user_id', $user_id)->where('target_id', $other_id)->group_end()
                ->or_group_start()->where('user_id', $other_id)->where('target_id', $user_id)->group_end()
            ->group_end()
            ->update('likes', array('status' => 'matched'));

        list($low, $high) = $this->pair($user_id, $other_id);
        $exists = $this->db->where('user_low_id', $low)->where('user_high_id', $high)
            ->count_all_results('matches') > 0;
        if ($exists) {
            return;
        }

        $this->db->insert('matches', array('user_low_id' => $low, 'user_high_id' => $high));
        $this->load->model('m_notification');
        foreach (array($user_id, $other_id) as $uid) {
            $this->m_notification->push($uid, 'match', 'Ghép đôi thành công!',
                'Hai bạn đã thích nhau, hãy bắt đầu trò chuyện.', site_url('tai-khoan/tin-nhan'));
        }
    }

    /**
     * Trả lời một lượt thích đang chờ ở mục "Người thích bạn".
     *
     *   $action = 'accept' : thích lại -> ghép đôi, mở khoá chat cho cả hai
     *   $action = 'skip'   : bỏ qua    -> lượt thích chuyển 'rejected', người
     *                                     gửi không nhận được thông báo gì
     *
     * Trả về ['ok'=>bool, 'matched'=>bool, 'message'=>string].
     */
    public function respond_like($user_id, $other_id, $action)
    {
        $user_id = (int) $user_id;
        $other_id = (int) $other_id;
        if ($user_id === $other_id) {
            return array('ok' => false, 'matched' => false, 'message' => 'Yêu cầu không hợp lệ.');
        }

        $cua_ho = $this->db->where(array(
            'user_id' => $other_id, 'target_type' => 'user', 'target_id' => $user_id,
        ))->get('likes')->row_array();

        if (!$cua_ho || $cua_ho['status'] !== 'pending') {
            return array('ok' => false, 'matched' => false,
                'message' => 'Lượt thích này không còn chờ trả lời.');
        }

        if ($action === 'accept') {
            if ($this->is_blocked($user_id, $other_id)) {
                return array('ok' => false, 'matched' => false,
                    'message' => 'Không thể ghép đôi với người dùng này.');
            }
            // Thích lại: bổ sung lượt thích chiều ngược lại nếu chưa có
            $cua_toi = $this->db->where(array(
                'user_id' => $user_id, 'target_type' => 'user', 'target_id' => $other_id,
            ))->count_all_results('likes') > 0;
            if (!$cua_toi) {
                $this->db->insert('likes', array(
                    'user_id'     => $user_id,
                    'target_type' => 'user',
                    'target_id'   => $other_id,
                    'status'      => 'matched',
                ));
            }
            $this->create_match($user_id, $other_id);
            return array('ok' => true, 'matched' => true,
                'message' => 'Ghép đôi thành công! Hai bạn có thể nhắn tin cho nhau.');
        }

        // Bỏ qua: chỉ đổi trạng thái, tuyệt đối không báo cho người gửi biết
        $this->db->where('id', $cua_ho['id'])->update('likes', array('status' => 'rejected'));
        return array('ok' => true, 'matched' => false, 'message' => 'Đã bỏ qua lượt thích này.');
    }

    /** Gỡ ghép đôi (khi một bên rút lại lượt thích). Chat khoá lại theo. */
    private function unmatch($user_id, $other_id)
    {
        list($low, $high) = $this->pair($user_id, $other_id);
        $this->db->where('user_low_id', $low)->where('user_high_id', $high)->delete('matches');

        // Lượt thích còn lại của người kia quay về trạng thái chờ trả lời
        $this->db->where(array(
            'user_id' => $other_id, 'target_type' => 'user',
            'target_id' => $user_id, 'status' => 'matched',
        ))->update('likes', array('status' => 'pending'));
    }

    public function matches($user_id, $limit = 50)
    {
        return $this->db->query(
            "SELECT u.* FROM matches m
               JOIN users u ON u.id = IF(m.user_low_id = ?, m.user_high_id, m.user_low_id)
              WHERE ? IN (m.user_low_id, m.user_high_id) AND u.deleted_at IS NULL
              ORDER BY m.matched_at DESC LIMIT ?",
            array($user_id, $user_id, (int) $limit)
        )->result_array();
    }

    public function is_matched($a, $b)
    {
        list($low, $high) = $this->pair($a, $b);
        return $this->db->where('user_low_id', $low)->where('user_high_id', $high)
            ->count_all_results('matches') > 0;
    }

    /* ------------------------- Chặn ------------------------- */

    public function block($user_id, $blocked_id)
    {
        $this->db->replace('blocks', array('user_id' => $user_id, 'blocked_id' => $blocked_id));
    }

    public function unblock($user_id, $blocked_id)
    {
        $this->db->where('user_id', $user_id)->where('blocked_id', $blocked_id)->delete('blocks');
    }

    public function is_blocked($a, $b)
    {
        return $this->db->group_start()
                ->where('user_id', $a)->where('blocked_id', $b)
            ->group_end()->or_group_start()
                ->where('user_id', $b)->where('blocked_id', $a)
            ->group_end()->count_all_results('blocks') > 0;
    }

    /* ------------------------- Nhắn tin ------------------------- */

    /**
     * Lấy hội thoại giữa hai người. Chỉ tạo mới khi hai bên đã ghép đôi —
     * đây là chốt chặn cuối cùng cho luật "có match mới được nhắn tin".
     */
    public function conversation_with($user_id, $other_id, $create = true)
    {
        if ($create && !$this->is_matched($user_id, $other_id)) {
            $create = false;
        }
        list($low, $high) = $this->pair($user_id, $other_id);
        $conv = $this->db->where('user_low_id', $low)->where('user_high_id', $high)
            ->get('conversations')->row_array();
        if (!$conv && $create) {
            $this->db->insert('conversations', array('user_low_id' => $low, 'user_high_id' => $high));
            $conv = $this->db->where('id', $this->db->insert_id())->get('conversations')->row_array();
        }
        return $conv;
    }

    /** Danh sách hội thoại kèm người đối diện và tin nhắn cuối. */
    public function conversations($user_id)
    {
        return $this->db->query(
            "SELECT c.*, u.id AS other_id, u.display_name, u.slug AS user_slug, u.avatar,
                    u.gender, u.last_active_at,
                    m.content AS last_content, m.sender_id AS last_sender_id,
                    (SELECT COUNT(*) FROM messages x
                      WHERE x.conversation_id = c.id AND x.sender_id <> ? AND x.read_at IS NULL) AS unread
               FROM conversations c
               JOIN users u ON u.id = IF(c.user_low_id = ?, c.user_high_id, c.user_low_id)
          LEFT JOIN messages m ON m.id = c.last_message_id
              WHERE ? IN (c.user_low_id, c.user_high_id) AND u.deleted_at IS NULL
              ORDER BY c.last_message_at DESC, c.id DESC",
            array($user_id, $user_id, $user_id)
        )->result_array();
    }

    public function messages($conversation_id, $limit = 100)
    {
        return $this->db->where('conversation_id', $conversation_id)->where('deleted_at', null)
            ->order_by('id', 'ASC')->limit($limit)->get('messages')->result_array();
    }

    /**
     * Gửi tin nhắn. Áp dụng quy tắc quyền nhắn của người nhận (all/vip/matched).
     */
    public function send_message($sender_id, $receiver_id, $content, $type = 'text')
    {
        if ($this->is_blocked($sender_id, $receiver_id)) {
            return array('ok' => false, 'message' => 'Không thể gửi tin nhắn tới người dùng này.');
        }
        // Luật chung: chỉ nhắn tin được khi hai bên đã ghép đôi.
        if (!$this->is_matched($sender_id, $receiver_id)) {
            return array('ok' => false, 'need_match' => true,
                'message' => 'Hai bạn chưa ghép đôi. Hãy thích nhau trước khi nhắn tin.');
        }

        // Người nhận vẫn có thể siết thêm: chỉ nhận tin từ thành viên VIP.
        $pref = $this->db->where('user_id', $receiver_id)->get('user_preferences')->row_array();
        if (($pref['allow_message'] ?? 'all') === 'vip' && !$this->auth->is_vip()) {
            return array('ok' => false, 'message' => 'Người này chỉ nhận tin nhắn từ thành viên VIP.');
        }

        $conv = $this->conversation_with($sender_id, $receiver_id);
        if (!$conv) {
            return array('ok' => false, 'message' => 'Không mở được hội thoại với người này.');
        }
        $this->db->insert('messages', array(
            'conversation_id' => $conv['id'],
            'sender_id'       => $sender_id,
            'type'            => $type,
            'content'         => $content,
        ));
        $message_id = $this->db->insert_id();
        $this->db->where('id', $conv['id'])->update('conversations', array(
            'last_message_id' => $message_id,
            'last_message_at' => date('Y-m-d H:i:s'),
        ));

        $this->load->model('m_notification');
        $this->m_notification->push($receiver_id, 'message', 'Tin nhắn mới',
            excerpt($content, 80), site_url('tai-khoan/tin-nhan/' . $conv['id']));

        return array('ok' => true, 'conversation_id' => (int) $conv['id'], 'message_id' => $message_id);
    }

    public function mark_read($conversation_id, $user_id)
    {
        $this->db->where('conversation_id', $conversation_id)
            ->where('sender_id !=', $user_id)->where('read_at', null)
            ->update('messages', array('read_at' => date('Y-m-d H:i:s')));
    }

    public function unread_count($user_id)
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) AS c FROM messages m
               JOIN conversations c ON c.id = m.conversation_id
              WHERE ? IN (c.user_low_id, c.user_high_id)
                AND m.sender_id <> ? AND m.read_at IS NULL",
            array($user_id, $user_id)
        )->row('c');
    }

    /** Bảo đảm cặp id luôn theo thứ tự (nhỏ, lớn) để khoá duy nhất hoạt động. */
    private function pair($a, $b)
    {
        $a = (int) $a; $b = (int) $b;
        return $a < $b ? array($a, $b) : array($b, $a);
    }
}
