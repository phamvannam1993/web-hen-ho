<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Các endpoint AJAX cho tương tác: thích, nhắn tin, báo cáo, bình luận tin đăng. */
class Ajax extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_report', 'm_post'));
    }

    private function require_login()
    {
        if (!$this->auth->check()) {
            $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập để thực hiện.'), 401);
            return false;
        }

        // Hồ sơ chưa khai đủ thì không dùng được tính năng nào: thích, nhắn tin,
        // bình luận, báo cáo. Cổng chặn ở MY_Controller không bắt được nhóm này
        // vì các lời gọi ajax được chừa ra để không nhận về HTML thay cho JSON.
        $me = $this->auth->user();
        if (!in_array($me['role'], array('admin', 'moderator'), true)) {
            $this->load->model('m_user');
            $thieu = $this->m_user->thieu_thong_tin($me['id']);
            if ($thieu) {
                $this->json(array(
                    'ok'      => false,
                    'need'    => 'profile',
                    'url'     => site_url('tai-khoan/ho-so'),
                    'message' => 'Bạn cần hoàn thiện hồ sơ (còn thiếu '
                        . count($thieu) . ' mục) trước khi dùng tính năng này.',
                ), 403);
                return false;
            }
        }
        return true;
    }

    /** Thích / bỏ thích thành viên hoặc tin đăng. */
    public function like()
    {
        if (!$this->require_login()) {
            return;
        }
        $type = $this->input->post('type') === 'post' ? 'post' : 'user';
        $id   = (int) $this->input->post('id');
        if ($id <= 0 || ($type === 'user' && $id === (int) $this->auth->id())) {
            return $this->json(array('ok' => false, 'message' => 'Yêu cầu không hợp lệ.'));
        }

        $result = $this->m_interaction->toggle_like($this->auth->id(), $type, $id);
        $result['ok'] = true;
        $result['message'] = $result['matched']
            ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
            : ($result['liked'] ? 'Đã gửi lượt thích.' : 'Đã bỏ thích.');

        return $this->json($result);
    }

    public function send_message()
    {
        if (!$this->require_login()) {
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
                'type'    => $r['type'],
                'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
                'time'    => date('H:i d/m', strtotime($r['created_at'])),
            );
        }

        return $this->json(array(
            'ok'       => true,
            'guest'    => !$me,
            'messages' => $messages,
            // Không tính tài khoản đã xoá mềm
            'online'   => (int) $this->db->where('last_active_at >', date('Y-m-d H:i:s', time() - 300))
                ->where('status', 'active')->where('deleted_at', null)
                ->count_all_results('users'),
        ));
    }

    /** Gửi tin vào phòng chat chung. */
    public function room_send()
    {
        if (!$this->require_login()) {
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
                'name'    => display_name($r),
                'avatar'  => avatar_url($r['avatar'], $r['gender']),
                'online'  => (bool) is_online($r['last_active_at']),
                'last'    => $r['last_content'] ? excerpt($r['last_content'], 38) : 'Bắt đầu trò chuyện',
                'unread'  => (int) $r['unread'],
            );
        }

        return $this->json(array(
            'ok'     => true,
            'items'  => $items,
            'unread' => (int) $this->m_interaction->unread_count($me),
        ));
    }

    /** Mở (hoặc tạo) hội thoại với một người, dùng cho nút "Nhắn tin". */
    public function open_conversation($user_id)
    {
        if (!$this->require_login()) {
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

        $conv = $this->m_interaction->conversation_with($this->auth->id(), $user_id);
        return $this->json(array(
            'ok'      => true,
            'id'      => (int) $conv['id'],
            'user_id' => (int) $other['id'],
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
            $messages[] = array(
                'id'      => (int) $r['id'],
                'mine'    => (int) $r['sender_id'] === (int) $me,
                'type'    => $r['type'],
                'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
                'time'    => date('H:i d/m', strtotime($r['created_at'])),
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
