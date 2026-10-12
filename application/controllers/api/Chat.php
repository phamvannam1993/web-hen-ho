<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tin nhắn riêng và phòng chat chung — bản API của các hàm chat trong
 * controllers/Ajax.php. Ứng dụng hỏi định kỳ bằng ?after=<id tin cuối>.
 */
class Chat extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_interaction');
        $this->load->library('realtime');
    }

    /** GET /api/conversations */
    public function conversations()
    {
        if (!$this->can_auth()) {
            return;
        }
        $me = (int) $this->me['id'];
        $items = array();
        foreach ($this->m_interaction->conversations($me) as $r) {
            $items[] = array(
                'id'      => (int) $r['id'],
                'user'    => array(
                    'id'       => (int) $r['other_id'],
                    'name'     => display_name($r),
                    'slug'     => $r['user_slug'],
                    'avatar'   => avatar_url($r['avatar'], $r['gender']),
                    'online'   => (bool) is_online($r['last_active_at']),
                    'activity' => $this->hoat_dong($r['last_active_at']),
                ),
                // Thả tim là có hội thoại ngay (chưa có tin nào) — như web
                'last'    => !$r['last_message_id'] ? 'Bắt đầu trò chuyện'
                    : $this->tom_tat($r['last_type'], $r['last_content'], (int) $r['last_sender_id'] === $me),
                'empty'   => !$r['last_message_id'],
                'time'    => $r['last_at'] ? time_ago($r['last_at']) : '',
                'unread'  => (int) $r['unread'],
            );
        }

        return $this->ok(array(
            'items'  => $items,
            'unread' => (int) $this->m_interaction->unread_count($me),
        ));
    }

    /** POST /api/conversations/open/{user_id} — mở (hoặc tạo) hội thoại, chỉ khi đã ghép đôi. */
    public function open($user_id = 0)
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('nhắn tin')) {
            return;
        }
        $user_id = (int) $user_id;
        if ($user_id === (int) $this->me['id']) {
            return $this->loi('Không thể tự nhắn cho mình.', 422);
        }
        $other = $this->m_user->find($user_id);
        if (!$other) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }
        // Nhắn tin không cần ghép đôi nữa — chỉ vướng chặn hoặc "chỉ nhận tin từ VIP"
        $loi = $this->m_interaction->message_permission($this->me['id'], $user_id);
        if ($loi !== null) {
            return $this->loi($loi, 403);
        }
        $conv = $this->m_interaction->conversation_with($this->me['id'], $user_id);
        if (!$conv) {
            return $this->loi('Không mở được hội thoại với người này.', 403);
        }
        return $this->ok(array('id' => (int) $conv['id'], 'user' => $this->ho_so($other)));
    }

    /**
     * GET /api/conversations/{id}?after=<id>
     * Lần đầu (after=0): 50 tin gần nhất. Sau đó chỉ tin mới hơn after.
     */
    public function messages($conversation_id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $me   = (int) $this->me['id'];
        $conv = $this->db->where('id', (int) $conversation_id)->get('conversations')->row_array();
        if (!$conv || !in_array($me, array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
            return $this->loi('Không có quyền xem hội thoại này.', 403);
        }
        $other_id = (int) $conv['user_low_id'] === $me ? (int) $conv['user_high_id'] : (int) $conv['user_low_id'];

        $after = (int) $this->input->get('after');
        $this->db->select('id, sender_id, type, content, created_at, read_at')
            ->from('messages')->where('conversation_id', $conv['id'])->where('deleted_at', null);
        if ($after > 0) {
            $rows = $this->db->where('id >', $after)->order_by('id', 'ASC')->limit(50)->get()->result_array();
        } else {
            $rows = array_reverse($this->db->order_by('id', 'DESC')->limit(50)->get()->result_array());
        }

        // đánh dấu đã đọc phần của đối phương
        $this->m_interaction->mark_read($conv['id'], $me);

        $ds = array();
        foreach ($rows as $r) {
            $cua_toi = (int) $r['sender_id'] === $me;
            $ds[] = $this->tin($r, $cua_toi) + array('seen' => $cua_toi && !empty($r['read_at']));
        }

        // đối phương đã đọc hết tin của tôi chưa
        $seen = (int) $this->db->from('messages')->where('conversation_id', $conv['id'])
            ->where('sender_id', $me)->where('read_at', null)->count_all_results() === 0;

        $ra = array('messages' => $ds, 'seen' => $seen);
        if ($after <= 0) {
            $other = $this->m_user->find($other_id);
            $ra['partner']  = $other ? $this->ho_so($other) + array('activity' => $this->hoat_dong($other['last_active_at'])) : null;
            $loi = $this->m_interaction->message_permission($me, $other_id);
            $ra['can_send']   = $loi === null;
            $ra['send_error'] = $loi;
        }
        return $this->ok($ra);
    }

    /** POST /api/messages {receiver_id, content} (+ "image" multipart) */
    public function send()
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('nhắn tin')) {
            return;
        }
        $nhan    = (int) $this->body('receiver_id');
        $content = trim(strip_tags((string) $this->body('content')));
        $image   = $this->tai_anh('image', 'chat', 10240);

        if ($content === '' && !$image) {
            return $this->loi($this->loi_anh ?: 'Hãy nhập nội dung hoặc chọn ảnh.', 422);
        }

        $gui = array();
        // Ảnh gửi kèm được lưu thành một tin riêng loại "image", như web
        if ($image) {
            $kq = $this->m_interaction->send_message($this->me['id'], $nhan, $image, 'image');
            if (!$kq['ok']) {
                return $this->loi($kq['message'], 403);
            }
            $gui[] = $kq['message_id'];
        }
        if ($content !== '') {
            $kq = $this->m_interaction->send_message($this->me['id'], $nhan, mb_substr($content, 0, 2000));
            if (!$kq['ok']) {
                return $this->loi($kq['message'], 403);
            }
            $gui[] = $kq['message_id'];
        }

        $rows = $this->db->where_in('id', $gui)->order_by('id')->get('messages')->result_array();
        return $this->ok(array(
            'conversation_id' => (int) $kq['conversation_id'],
            'messages'        => array_map(function ($r) {
                return $this->tin($r, true) + array('seen' => false);
            }, $rows),
        ), 201);
    }

    /* ====================== Phòng chat chung ====================== */

    /** GET /api/room?after=<id> — khách cũng xem được. */
    public function room()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $me    = $this->me ? (int) $this->me['id'] : 0;
        $after = (int) $this->input->get('after');

        $this->db->select('r.id, r.user_id, r.type, r.content, r.created_at,
                           u.display_name, u.nickname, u.avatar, u.gender, u.slug')
            ->from('room_messages r')->join('users u', 'u.id = r.user_id')
            ->where('r.deleted_at', null);
        if ($after > 0) {
            $rows = $this->db->where('r.id >', $after)->order_by('r.id', 'ASC')->limit(50)->get()->result_array();
        } else {
            $rows = array_reverse($this->db->order_by('r.id', 'DESC')->limit(30)->get()->result_array());
        }

        $ds = array();
        foreach ($rows as $r) {
            $ds[] = $this->tin($r, $me && (int) $r['user_id'] === $me) + array(
                'user' => array(
                    'id'     => (int) $r['user_id'],
                    'name'   => display_name($r),
                    'slug'   => $r['slug'],
                    'avatar' => avatar_url($r['avatar'], $r['gender']),
                ),
            );
        }

        $online = (int) $this->db->where('last_active_at >', date('Y-m-d H:i:s', time() - 300))
            ->where('status', 'active')->where('deleted_at', null)->count_all_results('users');

        return $this->ok(array('messages' => $ds, 'online' => $online, 'guest' => !$me));
    }

    /** POST /api/room {content} (+ "image") */
    public function room_send()
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('nhắn tin')) {
            return;
        }
        $me      = (int) $this->me['id'];
        $content = trim(strip_tags((string) $this->body('content')));
        $image   = $this->tai_anh('image', 'chat', 10240);
        if ($content === '' && !$image) {
            return $this->loi($this->loi_anh ?: 'Hãy nhập nội dung hoặc chọn ảnh.', 422);
        }
        // Chống spam: tối đa 15 tin mỗi phút cho một người
        $gan_day = (int) $this->db->where('user_id', $me)
            ->where('created_at >', date('Y-m-d H:i:s', time() - 60))
            ->count_all_results('room_messages');
        if ($gan_day >= 15) {
            return $this->loi('Bạn nhắn hơi nhanh, nghỉ một chút nhé.', 429);
        }
        if ($image) {
            $this->db->insert('room_messages', array('user_id' => $me, 'type' => 'image', 'content' => $image));
            $this->realtime->phong_moi($this->db->insert_id());
        }
        if ($content !== '') {
            $this->db->insert('room_messages', array('user_id' => $me, 'type' => 'text',
                'content' => mb_substr($content, 0, 1000)));
            $this->realtime->phong_moi($this->db->insert_id());
        }
        return $this->ok(array(), 201);
    }

    /**
     * POST /api/realtime/read {uid, conversation_id} — chỉ máy chủ Node gọi
     * (header X-Realtime-Secret), khi khung chat web gửi chat.read qua WebSocket.
     */
    public function realtime_read()
    {
        if (!$this->realtime->la_may_chu($this->input->get_request_header('X-Realtime-Secret', true))) {
            return $this->loi('Không có quyền.', 403);
        }
        $uid  = (int) $this->body('uid');
        $conv = $this->db->where('id', (int) $this->body('conversation_id'))->get('conversations')->row_array();
        if (!$conv || !in_array($uid, array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
            return $this->loi('Không có hội thoại này.', 404);
        }
        $this->m_interaction->mark_read($conv['id'], $uid);
        return $this->ok();
    }

    /** GET /api/realtime — địa chỉ + mã WebSocket nếu máy chủ realtime đang bật. */
    public function realtime()
    {
        if (!$this->can_auth()) {
            return;
        }
        $bat = $this->realtime->enabled() && $this->auth->da_xac_thuc();
        return $this->ok(array(
            'enabled' => $bat,
            'url'     => $bat ? $this->realtime->url() : null,
            'token'   => $bat ? $this->realtime->token($this->me['id']) : null,
        ));
    }

    /**
     * GET /api/online-members?after=<id> — người đang online (5 phút), như
     * ajax/online-members của khung chat web. Chỉ thông tin công khai.
     */
    public function online_members()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $after = max(0, (int) $this->input->get('after'));
        $me = $this->me ? (int) $this->me['id'] : 0;
        $this->db->select('id, display_name, nickname, avatar, gender, slug')
            ->from('users')->where('status', 'active')->where('deleted_at', null)
            ->where('last_active_at >', date('Y-m-d H:i:s', time() - 300))
            ->where('birthday <=', date('Y-m-d', strtotime('-18 years')))
            ->where_in('role', array('member', 'admin', 'moderator'))
            ->where('id >', $after);
        if ($me) {
            $this->db->where("id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = $me)", null, false)
                ->where("id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $me)", null, false);
        }
        $rows = $this->db->order_by('id', 'ASC')->limit(61)->get()->result_array();
        $more = count($rows) > 60;
        $rows = array_slice($rows, 0, 60);

        $ds = array();
        foreach ($rows as $r) {
            $ds[] = array('id' => (int) $r['id'], 'name' => display_name($r), 'slug' => $r['slug'],
                'avatar' => avatar_url($r['avatar'], $r['gender']), 'mine' => (int) $r['id'] === $me);
        }
        return $this->ok(array('members' => $ds, 'next' => $more && $rows ? (int) end($rows)['id'] : null));
    }

    /* ---------------------------------------------------------------- */

    /** "Hoạt động 5 phút trước" — giống dòng trạng thái khung chat web. */
    private function hoat_dong($last_active_at)
    {
        if (is_online($last_active_at)) {
            return 'Đang online';
        }
        return $last_active_at ? 'Hoạt động ' . time_ago($last_active_at) : 'Chưa có thông tin hoạt động';
    }

    private function tin(array $r, $cua_toi)
    {
        return array(
            'id'      => (int) $r['id'],
            'mine'    => (bool) $cua_toi,
            'type'    => $r['type'],
            'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
            'time'    => date('H:i', strtotime($r['created_at'])),
            'day'     => $this->nhan_ngay($r['created_at']),
        );
    }

    /** Nhãn ngày để chèn vạch ngăn khi cuộn qua ngày khác. */
    private function nhan_ngay($datetime)
    {
        $ngay = date('Y-m-d', strtotime($datetime));
        if ($ngay === date('Y-m-d')) {
            return 'Hôm nay';
        }
        if ($ngay === date('Y-m-d', strtotime('-1 day'))) {
            return 'Hôm qua';
        }
        return date('d/m/Y', strtotime($datetime));
    }

    /** Một dòng xem trước cho tin cuối — tin ảnh không hiện đường dẫn tệp. */
    private function tom_tat($type, $content, $cua_minh)
    {
        $dau = $cua_minh ? 'Bạn: ' : '';
        if ($type === 'image') {
            return $dau . 'Đã gửi một ảnh';
        }
        if ($type === 'system') {
            return excerpt((string) $content, 60);
        }
        return $dau . excerpt(trim((string) $content), 60);
    }
}
