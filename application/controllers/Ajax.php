<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Các endpoint AJAX cho tương tác: thích, nhắn tin, báo cáo, bình luận tin đăng. */
class Ajax extends MY_Controller
{
    public function interest_count()
    {
        if (!$this->require_login()) return;
        $this->output->set_header('Cache-Control: private, no-store');
        $this->load->model('m_interest_badge');
        $counts = $this->m_interest_badge->menu_counts($this->auth->id());
        return $this->json(array_merge(array('ok' => true, 'count' => $counts['interest']), $counts));
    }
    public function interest_seen()
    {
        if (!$this->require_login()) return;
        if ($this->input->method() !== 'post') return $this->json(array('ok' => false), 405);
        $token = $this->input->post('token');
        $expected = $this->session->userdata('interest_seen_token');
        if (!is_string($token) || !$expected || !hash_equals($expected, $token)) return $this->json(array('ok' => false), 403);
        $this->load->model('m_interest_badge');
        $ok = $this->m_interest_badge->mark_seen($this->auth->id(), (string) $this->input->post('tab'));
        return $this->json(array('ok' => $ok), $ok ? 200 : 422);
    }
    public function account_suggestion()
    {
        if (!$this->require_login()) return;
        $this->output->set_header('Cache-Control: private, no-store');
        $this->load->model('m_user');
        $exclude = $this->input->post('exclude');
        $exclude = is_array($exclude) ? array_map('intval', array_slice($exclude, 0, 100)) : array();
        $home = $this->input->post('layout') === 'home';
        $candidates = $home
            ? $this->m_user->suggestions($this->auth->user(), count($exclude) + 1)
            : $this->m_user->account_suggestions($this->auth->user(), count($exclude) + 1);
        foreach ($candidates as $member) {
            if (in_array((int) $member['id'], $exclude, true)) continue;
            if ($home) {
                return $this->json(array('ok' => true,
                    'home' => $this->load->view('home/_suggestion', array('m' => $member, 'matched_ids' => array()), true)));
            }
            return $this->json(array('ok' => true,
                'compact' => $this->load->view('account/_person', array('p' => $member, 'o' => array('compact' => true)), true),
                'desktop' => $this->load->view('account/_person', array('p' => $member, 'o' => array()), true),
            ));
        }
        return $this->json(array('ok' => true, 'compact' => '', 'desktop' => '', 'home' => ''));
    }
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_report', 'm_post'));
    }

    /**
     * Bắt đăng nhập. $can_xac_thuc = true với những việc gửi tới người khác
     * (thả tim, nhắn tin, bình luận): tài khoản chưa xác thực email vẫn dùng web,
     * chỉ bị chặn đúng nhóm này — đủ hạn chế tài khoản rác.
     *
     * Hồ sơ chưa đủ KHÔNG còn bị chặn ở đây nữa (hoàn thiện hồ sơ dần dần).
     */
    private function require_login($can_xac_thuc = false)
    {
        if (!$this->auth->check()) {
            $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập để thực hiện.'), 401);
            return false;
        }
        if ($can_xac_thuc && !$this->auth->da_xac_thuc()) {
            $this->json(array(
                'ok'      => false,
                'need'    => 'verify',
                'url'     => site_url('xac-thuc'),
                'message' => 'Bạn cần xác thực email (bấm link trong thư chúng tôi đã gửi) để thả tim và nhắn tin.',
            ), 403);
            return false;
        }
        return true;
    }

    /** Thích / bỏ thích thành viên hoặc tin đăng. */
    public function like()
    {
        if (!$this->require_login(true)) {
            return;
        }
        $type = $this->input->post('type') === 'post' ? 'post' : 'user';
        $id   = (int) $this->input->post('id');
        if ($id <= 0 || ($type === 'user' && $id === (int) $this->auth->id())) {
            return $this->json(array('ok' => false, 'message' => 'Yêu cầu không hợp lệ.'));
        }

        $result = $this->m_interaction->toggle_like($this->auth->id(), $type, $id);
        if (isset($result['ok']) && !$result['ok']) {
            return $this->json($result, 403);
        }
        $result['ok'] = true;
        if ($type === 'user') {
            $this->load->model('m_interest_badge');
            $result['account_counts'] = $this->m_interest_badge->menu_counts($this->auth->id());
        }
        $result['message'] = $result['matched']
            ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
            : ($result['liked'] ? 'Đã gửi lượt thích.' : 'Đã bỏ thích.');
        // Ghép đôi đã có hộp chúc mừng riêng, không chen lời nhắc vào
        if ($type === 'user' && $result['liked'] && !$result['matched']) {
            $result['nudge'] = $this->nhac_them_anh();
        }

        return $this->json($result);
    }

    /**
     * Trả lời một lượt thích ở mục "Người thích bạn": thích lại (ghép đôi) hoặc bỏ qua.
     */
    public function respond_like()
    {
        if (!$this->require_login(true)) {
            return;
        }
        $id     = (int) $this->input->post('id');
        $action = $this->input->post('action') === 'accept' ? 'accept' : 'skip';
        if ($id <= 0) {
            return $this->json(array('ok' => false, 'message' => 'Yêu cầu không hợp lệ.'));
        }

        $result = $this->m_interaction->respond_like($this->auth->id(), $id, $action);
        if (!empty($result['ok'])) {
            $this->load->model('m_interest_badge');
            $result['account_counts'] = $this->m_interest_badge->menu_counts($this->auth->id());
        }
        return $this->json($result);
    }

    public function send_message()
    {
        if (!$this->require_login(true)) {
            return;
        }

        $receiver = (int) $this->input->post('receiver_id');
        $content  = trim((string) $this->input->post('content', true));

        // Ảnh gửi kèm được lưu thành một tin nhắn riêng loại "image"
        $image = $this->upload_chat_image();
        if ($image) {
            $sent = $this->m_interaction->send_message($this->auth->id(), $receiver, $image, 'image');
            if (!$sent['ok']) {
                if ($this->input->is_ajax_request()) {
                    return $this->json($sent);
                }
                set_flash('danger', $sent['message']);
                redirect('tai-khoan/tin-nhan');
            }
            if ($content === '') {
                if ($this->input->is_ajax_request()) {
                    return $this->json($sent);
                }
                redirect('tai-khoan/tin-nhan/' . $sent['conversation_id']);
            }
        }

        if ($content === '' && !$image) {
            // Ưu tiên báo lý do ảnh hỏng, tránh thông báo sai kiểu "hãy chọn ảnh"
            // trong khi người dùng đã chọn ảnh nhưng bị từ chối.
            $msg = $this->chat_image_error ?: 'Hãy nhập nội dung hoặc chọn ảnh.';
            if ($this->input->is_ajax_request()) {
                return $this->json(array('ok' => false, 'message' => $msg));
            }
            set_flash('danger', $msg);
            redirect('tai-khoan/tin-nhan');
        }

        $result = $this->m_interaction->send_message($this->auth->id(), $receiver, $content);
        if ($result['ok'] && $this->chat_image_error) {
            $result['warning'] = $this->chat_image_error;
        }

        if ($this->input->is_ajax_request()) {
            return $this->json($result);
        }
        set_flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Đã gửi tin nhắn.' : $result['message']);
        redirect($result['ok'] ? 'tai-khoan/tin-nhan/' . $result['conversation_id'] : 'tai-khoan/tin-nhan');
    }

    /** Trả lời gợi ý hôm nay: Thích hoặc Bỏ qua. */
    public function daily_match()
    {
        if (!$this->require_login(true)) {
            return;
        }
        $this->load->model('m_daily');

        $id  = (int) $this->input->post('id');
        $act = $this->input->post('action') === 'like' ? 'like' : 'skip';

        $kq = $this->m_daily->tra_loi($this->auth->id(), $id, $act);
        if (!empty($kq['ok']) && $act === 'like') {
            $this->load->model('m_interest_badge');
            $kq['account_counts'] = $this->m_interest_badge->menu_counts($this->auth->id());
        }
        return $this->json($kq);
    }

    /* ==================== Thông báo ==================== */

    /**
     * Danh sách thông báo cho khay xổ xuống ở chuông.
     */
    public function notifications()
    {
        if (!$this->auth->check()) {
            return $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        }
        $this->load->model('m_notification');
        $me = $this->auth->id();

        $items = array();
        foreach ($this->m_notification->for_user($me, 15) as $n) {
            $items[] = array(
                'id'     => (int) $n['id'],
                'type'   => $n['type'],
                'title'  => $n['title'],
                'body'   => $n['body'],
                'url'    => $n['url'],
                'time'   => time_ago($n['created_at']),
                'unread' => empty($n['read_at']),
                'actor'  => $n['actor'],
            );
        }

        return $this->json(array(
            'ok'     => true,
            'items'  => $items,
            'unread' => $this->m_notification->unread_count($me),
        ));
    }

    /** Đánh dấu đã đọc toàn bộ thông báo. */
    public function notifications_read()
    {
        if (!$this->auth->check()) {
            return $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        }
        $this->load->model('m_notification');
        $this->m_notification->mark_all_read($this->auth->id());

        return $this->json(array('ok' => true));
    }

    /* ==================== Phòng chat chung ==================== */

    /**
     * Lấy tin nhắn phòng chat chung.
     * Khách chưa đăng nhập vẫn xem được, nhưng muốn gửi thì phải đăng nhập
     * (xem room_send bên dưới).
     */
    public function room_messages()
    {
        $me    = $this->auth->id();   // null nếu là khách
        $after = (int) $this->input->get('after');

        // Thẻ "Trò chuyện" và dòng phòng chung trong danh sách chỉ cần con số
        // online + tin cuối, gọi kèm ?only=online để khỏi kéo cả danh sách tin.
        if ($this->input->get('only') === 'online') {
            $cuoi = $this->db->select('r.type, r.content, r.created_at, u.display_name, u.nickname')
                ->from('room_messages r')->join('users u', 'u.id = r.user_id')
                ->where('r.deleted_at', null)
                ->order_by('r.id', 'DESC')->limit(1)
                ->get()->row_array();

            return $this->json(array(
                'ok'       => true,
                'messages' => array(),
                'online'   => $this->online_count(),
                'last'     => $cuoi
                    ? display_name($cuoi) . ': ' . $this->tom_tat_tin($cuoi['type'], $cuoi['content'])
                    : 'Chưa có tin nhắn nào',
                'time'     => $cuoi ? time_ago($cuoi['created_at']) : '',
            ));
        }

        $this->db->select('r.id, r.user_id, r.type, r.content, r.created_at,
                           u.display_name, u.nickname, u.avatar, u.gender, u.slug')
            ->from('room_messages r')->join('users u', 'u.id = r.user_id')
            ->where('r.deleted_at', null);

        if ($after > 0) {
            $this->db->where('r.id >', $after)->order_by('r.id', 'ASC')->limit(50);
        } else {
            // lần đầu mở phòng: lấy 30 tin gần nhất rồi đảo lại cho đúng thứ tự
            $this->db->order_by('r.id', 'DESC')->limit(30);
        }
        $rows = $this->db->get()->result_array();
        if ($after <= 0) {
            $rows = array_reverse($rows);
        }

        $messages = array();
        foreach ($rows as $r) {
            $messages[] = array(
                'id'      => (int) $r['id'],
                'mine'    => $me && (int) $r['user_id'] === (int) $me,
                'name'    => display_name($r),
                'avatar'  => avatar_url($r['avatar'], $r['gender']),
                'slug'    => $r['slug'],
                'user_id' => (int) $r['user_id'],
                'type'    => $r['type'],
                'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
                'time'    => date('H:i', strtotime($r['created_at'])),
                'day'     => $this->nhan_ngay($r['created_at']),
            );
        }

        return $this->json(array(
            'ok'       => true,
            'guest'    => !$me,
            'messages' => $messages,
            'online'   => $this->online_count(),
        ));
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

    /** Số thành viên hoạt động trong 5 phút gần nhất; không tính tài khoản đã xoá mềm. */
    private function online_count()
    {
        return (int) $this->db->where('last_active_at >', date('Y-m-d H:i:s', time() - 300))
            ->where('status', 'active')->where('deleted_at', null)
            ->count_all_results('users');
    }

    /** Public presence only; no email, phone, bio or private activity timestamps. */
    public function online_members()
    {
        $after = max(0, (int) $this->input->get('after'));
        $me = (int) $this->auth->id();
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
        $members = array();
        foreach ($rows as $row) {
            $members[] = array('id' => (int) $row['id'], 'name' => display_name($row),
                'avatar' => avatar_url($row['avatar'], $row['gender']),
                'url' => site_url('profile/' . $row['slug']), 'mine' => (int) $row['id'] === $me);
        }
        return $this->json(array('ok' => true, 'members' => $members,
            'next' => $more && $rows ? (int) end($rows)['id'] : null));
    }

    /** Gửi tin vào phòng chat chung. */
    public function room_send()
    {
        if (!$this->require_login(true)) {
            return;
        }
        $me      = $this->auth->id();
        $content = trim((string) $this->input->post('content', true));
        $image   = $this->upload_chat_image();

        if ($content === '' && !$image) {
            return $this->json(array(
                'ok'      => false,
                'message' => $this->chat_image_error ?: 'Hãy nhập nội dung hoặc chọn ảnh.',
            ));
        }

        // Chống spam: tối đa 15 tin mỗi phút cho một người
        $recent = (int) $this->db->where('user_id', $me)
            ->where('created_at >', date('Y-m-d H:i:s', time() - 60))
            ->count_all_results('room_messages');
        if ($recent >= 15) {
            return $this->json(array('ok' => false, 'message' => 'Bạn nhắn hơi nhanh, nghỉ một chút nhé.'));
        }

        if ($image) {
            $this->db->insert('room_messages', array('user_id' => $me, 'type' => 'image', 'content' => $image));
        }
        if ($content !== '') {
            $this->db->insert('room_messages', array('user_id' => $me, 'type' => 'text', 'content' => $content));
        }

        $out = array('ok' => true);
        if ($this->chat_image_error) {
            $out['warning'] = $this->chat_image_error;
        }
        return $this->json($out);
    }

    /** Danh sách hội thoại cho khung chat nổi. */
    public function conversations()
    {
        if (!$this->require_login()) {
            return;
        }
        $me   = $this->auth->id();
        $rows = $this->m_interaction->conversations($me);

        $items = array();
        foreach ($rows as $r) {
            $items[] = array(
                'id'      => (int) $r['id'],
                'user_id' => (int) $r['other_id'],
                'profile_url' => site_url('profile/' . $r['user_slug']),
                'name'    => display_name($r),
                'avatar'  => avatar_url($r['avatar'], $r['gender']),
                'online'  => (bool) is_online($r['last_active_at']),
                'last'    => !$r['last_message_id'] ? 'Bắt đầu trò chuyện' : $this->tom_tat_tin($r['last_type'], $r['last_content'],
                                               (int) $r['last_sender_id'] === (int) $me),
                'time'    => $r['last_at'] ? time_ago($r['last_at']) : '',
                'unread'  => (int) $r['unread'],
            );
        }

        return $this->json(array(
            'ok'     => true,
            'items'  => $items,
            'unread' => (int) $this->m_interaction->unread_count($me),
        ));
    }

    /**
     * Một dòng xem trước cho tin nhắn cuối.
     *
     * Tin ảnh lưu đường dẫn tệp trong cột content, đem hiện thẳng ra danh sách
     * thì người dùng thấy "uploads/chat/2026/09/..." chứ không hiểu gì.
     */
    private function tom_tat_tin($type, $content, $cua_minh = false)
    {
        $dau = $cua_minh ? 'Bạn: ' : '';

        if ($type === 'image') {
            return $dau . 'Đã gửi một ảnh';
        }
        if ($type === 'system') {
            return excerpt((string) $content, 38);
        }

        $chu = trim((string) $content);
        if ($chu === '') {
            return $dau . 'Đã gửi một tệp';
        }
        return $dau . excerpt($chu, 38);
    }

    /** Mở (hoặc tạo) hội thoại với một người, dùng cho nút "Nhắn tin". */
    public function open_conversation($user_id)
    {
        if (!$this->require_login(true)) {
            return;
        }
        if ((int) $user_id === (int) $this->auth->id()) {
            return $this->json(array('ok' => false, 'message' => 'Không thể tự nhắn cho mình.'));
        }

        $this->load->model('m_user');
        $other = $this->m_user->find($user_id);
        if (!$other) {
            return $this->json(array('ok' => false, 'message' => 'Không tìm thấy thành viên.'), 404);
        }

        $error = $this->m_interaction->message_permission($this->auth->id(), $user_id);
        if ($error !== null) return $this->json(array('ok' => false, 'message' => $error), 403);

        $conv = $this->m_interaction->conversation_with($this->auth->id(), $user_id);
        if (!$conv) return $this->json(array('ok' => false, 'message' => 'Không mở được hội thoại với người này.'), 403);
        return $this->json(array(
            'ok'      => true,
            'id'      => (int) $conv['id'],
            'user_id' => (int) $other['id'],
            'profile_url' => site_url('profile/' . $other['slug']),
            'name'    => display_name($other),
            'avatar'  => avatar_url($other['avatar'], $other['gender']),
            'online'  => (bool) is_online($other['last_active_at']),
        ));
    }

    /**
     * Lấy tin nhắn mới hơn $after_id trong một hội thoại.
     * Giao diện gọi định kỳ để hiện tin đối phương gửi mà không phải tải lại trang.
     */
    public function poll_messages($conversation_id)
    {
        if (!$this->require_login()) {
            return;
        }
        $me   = $this->auth->id();
        $conv = $this->db->where('id', $conversation_id)->get('conversations')->row_array();

        if (!$conv || !in_array((int) $me, array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
            return $this->json(array('ok' => false, 'message' => 'Không có quyền xem hội thoại này.'), 403);
        }

        $after = (int) $this->input->get('after');
        $rows  = $this->db->select('m.id, m.sender_id, m.type, m.content, m.created_at, m.read_at')
            ->from('messages m')
            ->where('m.conversation_id', $conversation_id)
            ->where('m.id >', $after)
            ->where('m.deleted_at', null)
            ->order_by('m.id', 'ASC')->limit(50)->get()->result_array();

        // đánh dấu đã đọc phần của đối phương
        $this->m_interaction->mark_read($conversation_id, $me);

        $messages = array();
        foreach ($rows as $r) {
            $la_cua_toi = (int) $r['sender_id'] === (int) $me;
            $messages[] = array(
                'id'      => (int) $r['id'],
                'mine'    => $la_cua_toi,
                'type'    => $r['type'],
                'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
                'time'    => date('H:i', strtotime($r['created_at'])),
                'day'     => $this->nhan_ngay($r['created_at']),
                // Chỉ tin của mình mới cần nhãn "Đã xem"
                'seen'    => $la_cua_toi && !empty($r['read_at']),
                'day_key' => substr($r['created_at'], 0, 10),
            );
        }

        // đối phương đã đọc tin của tôi chưa
        $seen = (int) $this->db->from('messages')
            ->where('conversation_id', $conversation_id)
            ->where('sender_id', $me)->where('read_at', null)
            ->count_all_results() === 0;

        return $this->json(array('ok' => true, 'messages' => $messages, 'seen' => $seen));
    }

    /** Báo cáo vi phạm với thành viên / tin đăng / bình luận. */
    public function report()
    {
        if (!$this->require_login()) {
            return;
        }
        $type = $this->input->post('target_type');
        if (!in_array($type, array('user', 'post', 'comment', 'message'), true)) {
            return $this->json(array('ok' => false, 'message' => 'Đối tượng không hợp lệ.'));
        }
        $this->m_report->create(
            $this->auth->id(), $type, (int) $this->input->post('target_id'),
            $this->input->post('reason') ?: 'khac',
            $this->input->post('note', true)
        );
        return $this->json(array('ok' => true, 'message' => 'Đã gửi báo cáo, ban quản trị sẽ xem xét sớm.'));
    }

    /** Bình luận dưới tin đăng. */
    public function comment($post_id)
    {
        if (!$this->auth->check()) {
            set_flash('warning', 'Vui lòng đăng nhập để bình luận.');
            redirect('dang-nhap');
        }
        if (!$this->auth->da_xac_thuc()) {
            set_flash('warning', 'Bạn cần xác thực email trước khi bình luận.');
            redirect('xac-thuc');
        }
        $post = $this->m_post->find($post_id);
        if (!$post) {
            show_404();
        }

        $content = trim((string) $this->input->post('content', true));
        $image   = $this->upload_comment_image();

        if ($content === '' && !$image) {
            set_flash('danger', 'Hãy nhập nội dung hoặc chọn ảnh để bình luận.');
        } else {
            $this->db->insert('post_comments', array(
                'post_id'   => $post['id'],
                'user_id'   => $this->auth->id(),
                'parent_id' => $this->input->post('parent_id') ?: null,
                'content'   => $content,
                'image'     => $image,
            ));
            $this->db->set('comment_count', 'comment_count + 1', false)
                ->where('id', $post['id'])->update('posts');

            // báo cho chủ tin, trừ khi tự bình luận bài của mình
            if ((int) $post['user_id'] !== (int) $this->auth->id()) {
                $this->load->model('m_notification');
                $this->m_notification->push($post['user_id'], 'comment', 'Bình luận mới trên tin của bạn',
                    excerpt($content, 80), site_url('tin/' . $post['slug']));
            }
            set_flash('success', 'Đã gửi bình luận.');
        }
        redirect('tin/' . $post['slug'] . '#binh-luan');
    }

    /** Xoá bình luận tin đăng: chỉ tác giả bình luận hoặc chủ tin được xoá. */
    public function delete_comment($id)
    {
        if (!$this->auth->check()) {
            redirect('dang-nhap');
        }
        $comment = $this->db->select('c.*, p.slug, p.user_id AS post_owner')
            ->from('post_comments c')->join('posts p', 'p.id = c.post_id')
            ->where('c.id', $id)->get()->row_array();

        if ($comment && ((int) $comment['user_id'] === (int) $this->auth->id()
                || (int) $comment['post_owner'] === (int) $this->auth->id())) {
            $this->db->where('id', $id)->delete('post_comments');
            $this->db->set('comment_count', 'GREATEST(comment_count - 1, 0)', false)
                ->where('id', $comment['post_id'])->update('posts');
            set_flash('success', 'Đã xoá bình luận.');
        }
        redirect($comment ? 'tin/' . $comment['slug'] . '#binh-luan' : '/');
    }

    /** Lý do ảnh chat không tải lên được, để trả về đúng nguyên nhân cho người dùng. */
    private $chat_image_error = null;

    /**
     * Tải ảnh gửi trong khung chat.
     * Trả về đường dẫn tương đối, hoặc null kèm lý do trong $this->chat_image_error.
     */
    private function upload_chat_image()
    {
        $this->chat_image_error = null;

        if (empty($_FILES['image']['name'])) {
            return null;
        }
        // Người dùng có chọn file nhưng trình duyệt gửi lên lỗi (quá giới hạn của PHP...)
        if (!empty($_FILES['image']['error']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->chat_image_error = $_FILES['image']['error'] === UPLOAD_ERR_INI_SIZE
                || $_FILES['image']['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'Ảnh quá lớn, vui lòng chọn ảnh dưới 10MB.'
                : 'Không nhận được ảnh, vui lòng thử lại.';
            return null;
        }

        $dir = FCPATH . 'uploads/chat/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif|heic|heif',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        ));

        if (!$this->upload->do_upload('image')) {
            $raw = strip_tags($this->upload->display_errors('', ''));
            // Dịch các lỗi hay gặp sang tiếng Việt dễ hiểu
            if (stripos($raw, 'not allowed') !== false || stripos($raw, 'filetype') !== false) {
                $this->chat_image_error = 'Định dạng ảnh không hỗ trợ. Hãy dùng JPG, PNG, WEBP hoặc GIF.';
            } elseif (stripos($raw, 'size') !== false) {
                $this->chat_image_error = 'Ảnh quá lớn, vui lòng chọn ảnh dưới 10MB.';
            } elseif (stripos($raw, 'writable') !== false || stripos($raw, 'destination') !== false) {
                $this->chat_image_error = 'Máy chủ chưa ghi được ảnh, vui lòng báo quản trị viên.';
            } else {
                $this->chat_image_error = trim($raw) ?: 'Không tải được ảnh, vui lòng thử lại.';
            }
            return null;
        }

        $data = $this->upload->data();
        return 'uploads/chat/' . date('Y/m') . '/' . $data['file_name'];
    }

    /** Tải ảnh đính kèm bình luận, trả về đường dẫn tương đối hoặc null. */
    private function upload_comment_image()
    {
        if (empty($_FILES['image']['name'])) {
            return null;
        }
        $dir = FCPATH . 'uploads/comments/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ));
        if (!$this->upload->do_upload('image')) {
            set_flash('warning', strip_tags($this->upload->display_errors()));
            return null;
        }
        $data = $this->upload->data();
        return 'uploads/comments/' . date('Y/m') . '/' . $data['file_name'];
    }
}
