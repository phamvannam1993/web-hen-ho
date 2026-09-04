<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Khu vực thành viên: hồ sơ, tin đăng, tin nhắn, ví xu. */
class Account extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_post', 'm_category', 'm_interaction',
                                 'm_notification', 'm_billing'));
    }

    public function index()
    {
        $me = $this->auth->user();
        $this->render('account/index', array(
            'title'         => 'Tài khoản của tôi',
            'me'            => $me,
            'post_count'    => $this->db->where('user_id', $me['id'])->where('deleted_at', null)->count_all_results('posts'),
            'liked_me'      => $this->m_interaction->liked_me($me['id'], 8),
            'matches'       => $this->m_interaction->matches($me['id'], 8),
            'unread_msg'    => $this->m_interaction->unread_count($me['id']),
            'unread_noti'   => $this->m_notification->unread_count($me['id']),
            'recent_posts'  => $this->m_post->by_user($me['id'], null, 5),
        ));
    }

    /** Cập nhật hồ sơ cá nhân + tiêu chí tìm kiếm. */
    public function profile()
    {
        $me = $this->auth->user();

        if ($this->input->method() === 'post') {
            // Hồ sơ phải khai đủ mới cho lưu — chỉ kiểm tra có nhập hay chưa,
            // thông báo lấy từ application/language/vietnamese/form_validation_lang.php
            $bat_buoc = array(
                'display_name'   => 'Tên hiển thị',
                'gender'         => 'Giới tính',
                'birthday'       => 'Ngày sinh',
                'province_id'    => 'Khu vực',
                'height_cm'      => 'Chiều cao',
                'weight_kg'      => 'Cân nặng',
                'job'            => 'Nghề nghiệp',
                'education'      => 'Học vấn',
                'marital_status' => 'Tình trạng hôn nhân',
                'has_children'   => 'Con cái',
                'confide_topic'  => 'Chủ đề muốn tâm sự',
                'smoking'        => 'Hút thuốc',
                'drinking'       => 'Uống rượu bia',
                'bio'            => 'Giới thiệu bản thân',
                'interests[]'    => 'Sở thích',
                'seeking_gender' => 'Muốn tìm',
                'purpose'        => 'Mục đích',
                'age_min'        => 'Tuổi từ',
                'age_max'        => 'Tuổi đến',
                'allow_message'  => 'Ai được nhắn tin',
            );
            foreach ($bat_buoc as $o => $ten) {
                $this->form_validation->set_rules($o, $ten, 'required');
            }

            // Ảnh đại diện: chỉ bắt với người chưa từng tải lên, không bắt tải lại mỗi lần
            if (empty($me['avatar']) && empty($_FILES['avatar']['name'])) {
                $this->form_validation->set_rules('avatar', 'Ảnh đại diện', 'required');
            }

            if ($this->form_validation->run()) {
                $data = array(
                    'display_name'   => $this->input->post('display_name', true),
                    'nickname'       => $this->input->post('nickname', true) ?: null,
                    'gender'         => $this->input->post('gender'),
                    'birthday'       => $this->input->post('birthday') ?: null,
                    'province_id'    => $this->input->post('province_id') ?: null,
                    'bio'            => $this->input->post('bio', true),
                    'job'            => $this->input->post('job', true),
                    'height_cm'      => $this->input->post('height_cm') ?: null,
                    'weight_kg'      => $this->input->post('weight_kg') ?: null,
                    'education'      => $this->input->post('education') ?: null,
                    'marital_status' => $this->input->post('marital_status') ?: null,
                    'smoking'        => $this->input->post('smoking') ?: null,
                    'drinking'       => $this->input->post('drinking') ?: null,
                    'confide_topic'  => $this->input->post('confide_topic') ?: null,
                );
                if ($this->input->post('has_children') !== null && $this->input->post('has_children') !== '') {
                    $data['has_children'] = (int) $this->input->post('has_children');
                }

                $avatar = $this->upload_image('avatar');
                if ($avatar) {
                    $data['avatar'] = $avatar;
                }
                $this->m_user->update_profile($me['id'], $data);

                // tiêu chí ghép đôi
                $pref = array(
                    'seeking_gender' => $this->input->post('seeking_gender'),
                    'age_min'        => (int) $this->input->post('age_min') ?: 18,
                    'age_max'        => (int) $this->input->post('age_max') ?: 60,
                    'purpose'        => $this->input->post('purpose'),
                    'allow_message'  => $this->input->post('allow_message'),
                    'show_online'    => (int) (bool) $this->input->post('show_online'),
                );
                // Sở thích: ghi lại toàn bộ lựa chọn hiện tại
                $this->db->where('user_id', $me['id'])->delete('user_interests');
                foreach ((array) $this->input->post('interests') as $iid) {
                    $iid = (int) $iid;
                    if ($iid > 0) {
                        $this->db->replace('user_interests', array('user_id' => $me['id'], 'interest_id' => $iid));
                    }
                }

                $exists = $this->db->where('user_id', $me['id'])->count_all_results('user_preferences') > 0;
                if ($exists) {
                    $this->db->where('user_id', $me['id'])->update('user_preferences', $pref);
                } else {
                    $pref['user_id'] = $me['id'];
                    $this->db->insert('user_preferences', $pref);
                }

                set_flash('success', 'Đã cập nhật hồ sơ.');
                redirect('tai-khoan/ho-so');
            }
        }

        $this->render('account/profile', array(
            'title'         => 'Hồ sơ của tôi',
            // Danh sách mục còn trống, để hiện bảng nhắc ngay đầu trang
            'thieu'         => $this->m_user->thieu_thong_tin($me['id']),
            'me'            => $this->m_user->find($me['id']),
            'pref'          => $this->db->where('user_id', $me['id'])->get('user_preferences')->row_array(),
            'all_interests' => $this->db->order_by('name')->get('interests')->result_array(),
            'my_interests'  => array_map('intval', array_column(
                $this->db->select('interest_id')->where('user_id', $me['id'])
                    ->get('user_interests')->result_array(), 'interest_id')),
        ));
    }

    /** Quản lý album ảnh cá nhân. */
    public function photos()
    {
        $me = $this->auth->user();

        if ($this->input->method() === 'post') {
            foreach ($this->upload_gallery('photos') as $path) {
                $this->db->insert('user_photos', array(
                    'user_id' => $me['id'],
                    'path'    => $path,
                    'status'  => setting('auto_approve_post', '0') === '1' ? 'approved' : 'pending',
                ));
            }
            set_flash('success', 'Đã tải ảnh lên, ảnh sẽ hiển thị sau khi được duyệt.');
            redirect('tai-khoan/anh');
        }

        $this->render('account/photos', array(
            'title'  => 'Ảnh của tôi',
            'photos' => $this->db->where('user_id', $me['id'])->order_by('sort')->get('user_photos')->result_array(),
        ));
    }

    public function delete_photo($id)
    {
        $this->db->where('id', $id)->where('user_id', $this->auth->id())->delete('user_photos');
        redirect('tai-khoan/anh');
    }

    /** Danh sách tin của tôi. */
    public function posts()
    {
        $this->require_posts_enabled();
        $this->render('account/posts', array(
            'title' => 'Tin đăng của tôi',
            'posts' => $this->m_post->by_user($this->auth->id(), null, 50),
        ));
    }

    public function create_post()
    {
        $this->require_posts_enabled();
        return $this->edit_post(null);
    }

    public function edit_post($id = null)
    {
        $this->require_posts_enabled();
        $me   = $this->auth->user();
        $post = $id ? $this->m_post->find($id) : null;
        if ($id && (!$post || (int) $post['user_id'] !== (int) $me['id'])) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Tiêu đề', 'required|min_length[10]|max_length[255]');
            $this->form_validation->set_rules('content', 'Nội dung', 'required|min_length[30]');

            if ($this->form_validation->run()) {
                $data = array(
                    'category_id'    => $this->input->post('category_id') ?: null,
                    'province_id'    => $this->input->post('province_id') ?: null,
                    'title'          => $this->input->post('title', true),
                    'content'        => $this->input->post('content'),
                    'nickname'       => $this->input->post('nickname', true),
                    'district'       => $this->input->post('district', true),
                    'intro'          => $this->input->post('intro', true),
                    'job'            => $this->input->post('job', true),
                    'wish'           => $this->input->post('wish', true),
                    'personality'    => $this->input->post('personality', true),
                    'gender'         => $this->input->post('gender'),
                    'seeking'        => $this->input->post('seeking'),
                    'age'            => $this->input->post('age') ?: null,
                    'height_cm'      => $this->input->post('height_cm') ?: null,
                    'weight_kg'      => $this->input->post('weight_kg') ?: null,
                    'marital_status' => $this->input->post('marital_status') ?: null,
                    'purpose'        => $this->input->post('purpose'),
                    'contact_type'   => $this->input->post('contact_type'),
                    'contact_value'  => $this->input->post('contact_value', true),
                );

                $cover = $this->upload_image('cover');
                if ($cover) {
                    $data['cover'] = $cover;
                }

                if ($id) {
                    // sửa tin đã duyệt thì đưa về chờ duyệt lại
                    $data['status'] = 'pending';
                    $this->m_post->update_post($id, $data);
                } else {
                    $data['user_id'] = $me['id'];
                    $id = $this->m_post->create($data);
                }

                foreach ($this->upload_gallery('images') as $i => $path) {
                    $this->db->insert('post_images', array('post_id' => $id, 'path' => $path, 'sort' => $i));
                }

                set_flash('success', 'Đã lưu tin. Tin sẽ hiển thị sau khi ban quản trị duyệt.');
                redirect('tai-khoan/tin-dang');
            }
        }

        $this->render('account/post_form', array(
            'title'      => $id ? 'Sửa tin đăng' : 'Đăng tin hẹn hò',
            'p'          => $post,
            'images'     => $id ? $this->m_post->images($id) : array(),
            'post_categories' => $this->m_category->all('post'),
        ));
    }

    public function delete_post($id)
    {
        $this->require_posts_enabled();
        $this->m_post->soft_delete($id, $this->auth->id());
        set_flash('success', 'Đã xoá tin.');
        redirect('tai-khoan/tin-dang');
    }

    /** Ai thích tôi / tôi thích ai / ghép đôi. */
    public function likes()
    {
        $me = $this->auth->user();
        $this->render('account/likes', array(
            'title'    => 'Quan tâm & ghép đôi',
            'liked_me' => $this->m_interaction->liked_me($me['id']),
            'my_likes' => $this->m_interaction->my_likes($me['id']),
            'matches'  => $this->m_interaction->matches($me['id']),
        ));
    }

    public function messages($conversation_id = null)
    {
        $me = $this->auth->user();
        $messages = array();
        $partner  = null;

        // Bấm "Nhắn tin" ở trang cá nhân sẽ tới đây kèm ?to=ID:
        // mở sẵn (hoặc tạo mới) hội thoại với người đó.
        $to = (int) $this->input->get('to');
        if ($to && $to !== (int) $me['id']) {
            $conv = $this->m_interaction->conversation_with($me['id'], $to);
            if ($conv) {
                redirect('tai-khoan/tin-nhan/' . $conv['id']);
            }
        }

        if ($conversation_id) {
            $conv = $this->db->where('id', $conversation_id)->get('conversations')->row_array();
            if (!$conv || !in_array((int) $me['id'], array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
                show_404();
            }
            $other_id = (int) $conv['user_low_id'] === (int) $me['id'] ? $conv['user_high_id'] : $conv['user_low_id'];
            $partner  = $this->m_user->find($other_id);
            $messages = $this->m_interaction->messages($conversation_id);
            $this->m_interaction->mark_read($conversation_id, $me['id']);
        }

        $this->render('account/messages', array(
            'title'         => 'Tin nhắn',
            'conversations' => $this->m_interaction->conversations($me['id']),
            'messages'      => $messages,
            'partner'       => $partner,
            'conversation_id' => $conversation_id,
        ));
    }

    public function notifications()
    {
        $me = $this->auth->user();
        $list = $this->m_notification->for_user($me['id']);
        $this->m_notification->mark_all_read($me['id']);

        $this->render('account/notifications', array(
            'title'         => 'Thông báo',
            'notifications' => $list,
        ));
    }

    /** Ví xu, gói VIP và lịch sử đơn. */
    public function wallet()
    {
        $me = $this->auth->user();

        if ($this->input->method() === 'post') {
            $order = $this->m_billing->create_order($me['id'], $this->input->post('package_id'), $this->input->post('method'));
            if ($order) {
                set_flash('info', 'Đã tạo đơn ' . $order['code'] . '. Vui lòng chuyển khoản với nội dung là mã đơn, '
                    . 'hệ thống sẽ cộng xu/VIP sau khi xác nhận.');
            } else {
                set_flash('danger', 'Gói không hợp lệ.');
            }
            redirect('tai-khoan/nap-xu');
        }

        $this->render('account/wallet', array(
            'title'    => 'Nạp xu / VIP',
            'me'       => $this->m_user->find($me['id']),
            'packages' => $this->m_billing->packages(),
            'orders'   => $this->m_billing->orders_of($me['id']),
            'coins'    => $this->m_billing->coin_history($me['id']),
        ));
    }

    public function password()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('current', 'Mật khẩu hiện tại', 'required');
            $this->form_validation->set_rules('password', 'Mật khẩu mới', 'required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Xác nhận', 'required|matches[password]');

            if ($this->form_validation->run()) {
                $me = $this->auth->user();
                if (!password_verify($this->input->post('current'), $me['password_hash'])) {
                    set_flash('danger', 'Mật khẩu hiện tại không đúng.');
                } else {
                    $this->m_user->change_password($me['id'], $this->input->post('password'));
                    set_flash('success', 'Đã đổi mật khẩu.');
                    redirect('tai-khoan');
                }
            }
        }

        $this->render('account/password', array('title' => 'Đổi mật khẩu'));
    }

    /* ------------------------- Tải ảnh ------------------------- */

    private function upload_config()
    {
        $dir = FCPATH . 'uploads/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        );
    }

    private function upload_image($field)
    {
        if (empty($_FILES[$field]['name'])) {
            return null;
        }
        $this->load->library('upload', $this->upload_config());
        if (!$this->upload->do_upload($field)) {
            set_flash('warning', strip_tags($this->upload->display_errors()));
            return null;
        }
        $data = $this->upload->data();
        return 'uploads/' . date('Y/m') . '/' . $data['file_name'];
    }

    private function upload_gallery($field)
    {
        if (empty($_FILES[$field]['name'][0])) {
            return array();
        }
        $this->load->library('upload', $this->upload_config());
        $paths = array();
        for ($i = 0; $i < count($_FILES[$field]['name']); $i++) {
            $_FILES['single'] = array(
                'name'     => $_FILES[$field]['name'][$i],
                'type'     => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error'    => $_FILES[$field]['error'][$i],
                'size'     => $_FILES[$field]['size'][$i],
            );
            if ($this->upload->do_upload('single')) {
                $data = $this->upload->data();
                $paths[] = 'uploads/' . date('Y/m') . '/' . $data['file_name'];
            }
        }
        return $paths;
    }
}
