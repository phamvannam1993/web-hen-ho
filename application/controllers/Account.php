<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Khu vực thành viên: hồ sơ, tin đăng, tin nhắn, ví xu. */
class Account extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_post', 'm_category', 'm_interaction',
                                 'm_notification', 'm_billing', 'm_job', 'm_daily'));

        // Số liệu cho khung chung của khu Tài khoản (cột trái, thanh trên và
        // thanh dưới trên điện thoại) — trang nào cũng cần nên nạp một lần ở đây.
        $id = $this->auth->id();
        $this->data['tk'] = array(
            'me'    => $this->m_user->find($id),
            'liked' => (int) $this->m_interaction->liked_me_count($id),
            'msg'   => (int) $this->m_interaction->unread_count($id),
            'noti'  => (int) $this->data['unread_noti'],
        );
    }

    public function index()
    {
        $me = $this->auth->user();
        $this->render('account/index', array(
            'title'         => 'Tài khoản của tôi',
            'me'            => $me,
            'post_count'    => $this->db->where('user_id', $me['id'])->where('deleted_at', null)->count_all_results('posts'),
            'liked_me'      => $this->m_interaction->liked_me($me['id'], 8),
            'viewer_count'  => $this->m_interaction->viewer_count($me['id']),
            'liked_count'   => $this->m_interaction->liked_me_count($me['id']),
            'matches'       => $this->m_interaction->matches($me['id'], 8),
            'unread_msg'    => $this->m_interaction->unread_count($me['id']),
            'unread_noti'   => $this->m_notification->unread_count($me['id']),
            'recent_posts'  => $this->m_post->by_user($me['id'], null, 5),
            /* Hai khối bổ sung cho trang Tổng quan (theo bản thiết kế SaigonCupid).
               Dùng lại model sẵn có, không thêm truy vấn mới nào ngoài hai dòng này. */
            'hoat_dong'     => $this->m_notification->for_user($me['id'], 5),
            'goi_y_hom_nay' => $this->m_daily->today($me['id']),
            /* `M_daily::today()` chỉ trả MỘT người mỗi ngày (đúng luật của tính năng
               ghép đôi hằng ngày). Lưới lấy các hồ sơ phù hợp từ bộ chấm điểm
               hiện có và luân phiên mỗi ngày lúc 08:00 giờ Việt Nam. */
            'goi_y_them'    => $this->m_user->account_suggestions($me, 3),
        ));
    }

    /** Cập nhật hồ sơ cá nhân + tiêu chí tìm kiếm. */
    /** Số điện thoại phải là số di động Việt Nam và chưa ai dùng. */
    public function dien_thoai_hop_le($so)
    {
        // Không bắt buộc: bỏ trống là hợp lệ, có nhập thì phải đúng
        if (trim((string) $so) === '') {
            return true;
        }
        $chuan = chuan_hoa_dien_thoai($so);
        if ($chuan === '') {
            $this->form_validation->set_message('dien_thoai_hop_le',
                'Số điện thoại không đúng. Nhập số di động Việt Nam 10 chữ số, VD: 0912345678.');
            return false;
        }
        if ($this->m_user->phone_exists($chuan, $this->auth->id())) {
            $this->form_validation->set_message('dien_thoai_hop_le',
                'Số điện thoại này đã có người dùng.');
            return false;
        }
        return true;
    }

    public function email_hop_le($email)
    {
        if ($email === '') {
            if (!empty($this->auth->user()['email'])) {
                $this->form_validation->set_message('email_hop_le', 'Vui lòng nhập email mới, không được xoá email hiện tại.');
                return false;
            }
            return true;
        }
        if ($this->m_user->email_exists($email, $this->auth->id())) {
            $this->data['email_bi_trung'] = true;
            $this->form_validation->set_message('email_hop_le',
                'Email này đã được dùng cho tài khoản khác. Chưa lưu thay đổi; email hiện tại của bạn được giữ nguyên. Vui lòng chọn email khác.');
            return false;
        }
        return true;
    }

    public function check_profile_email()
    {
        $email = trim((string) $this->input->get('email'));
        $valid = ($email === '' && empty($this->auth->user()['email']))
            || (strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL)
                && !$this->m_user->email_exists($email, $this->auth->id()));
        $this->output->set_content_type('application/json')
            ->set_header('Cache-Control: no-store')
            ->set_output(json_encode(array('available' => (bool) $valid)));
    }

    public function profile()
    {
        $me = $this->auth->user();

        if ($this->input->method() === 'post') {
            // Hoàn thiện hồ sơ dần dần: chỉ bắt những mục mà đăng ký đã có sẵn
            // (để không ai xoá trắng được) và tiêu chí ghép đôi. Ảnh, khu vực,
            // giới thiệu, số điện thoại… khai lúc nào cũng được — thiếu ảnh hoặc
            // khu vực thì hồ sơ chỉ bị ẩn khỏi danh sách công khai, có banner nhắc.
            $bat_buoc = array(
                'display_name'   => 'Tên hiển thị',
                'gender'         => 'Giới tính',
                'birthday'       => 'Ngày sinh',
                'seeking_gender' => 'Muốn tìm',
                'purpose'        => 'Mục đích',
            );
            foreach ($bat_buoc as $o => $ten) {
                $this->form_validation->set_rules($o, $ten, 'required');
            }
            $this->form_validation->set_rules('phone', 'Số điện thoại', 'trim|callback_dien_thoai_hop_le');
            if ($this->input->post('email') !== null) {
                $this->form_validation->set_rules('email', 'Email', 'trim|max_length[190]|valid_email|callback_email_hop_le');
            }
            $this->form_validation->set_rules('bio', 'Giới thiệu bản thân', 'max_length[500]');

            if ($this->form_validation->run()) {
                $data = array(
                    'display_name'   => $this->input->post('display_name', true),
                    'phone'          => chuan_hoa_dien_thoai($this->input->post('phone')) ?: null,
                    'nickname'       => $this->input->post('nickname', true) ?: null,
                    'gender'         => $this->input->post('gender'),
                    'birthday'       => $this->input->post('birthday') ?: null,
                    'province_id'    => $this->input->post('province_id') ?: null,
                    'bio'            => $this->input->post('bio', true),
                    // Nghề nghiệp giờ chọn trong danh mục, không nhận chữ tự gõ
                    'job'            => $this->m_job->valid_name($this->input->post('job', true), $me['job']),
                    'height_cm'      => $this->input->post('height_cm') ?: null,
                    'weight_kg'      => $this->input->post('weight_kg') ?: null,
                    'education'      => $this->input->post('education') ?: null,
                    'marital_status' => $this->input->post('marital_status') ?: null,
                    'smoking'        => $this->input->post('smoking') ?: null,
                    'drinking'       => $this->input->post('drinking') ?: null,
                    'confide_topic'  => $this->input->post('confide_topic') ?: null,
                );
                $email_changed = false;
                if ($this->input->post('email') !== null) {
                    $data['email'] = trim((string) $this->input->post('email')) ?: null;
                    $email_changed = (string) $data['email'] !== (string) ($me['email'] ?? '');
                }
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

                // Tính lại sau khi đã lưu sở thích (sở thích là một mục của điểm)
                $this->m_user->recalc_profile_score($me['id']);

                set_flash('success', $email_changed
                    ? 'Đã cập nhật hồ sơ và email. Từ nay hãy dùng email mới để đăng nhập.'
                        . (setting('otp_register', '1') === '1'
                            ? ' Vui lòng bấm “Xác thực email / Gửi lại link xác thực” trong hồ sơ để xác thực email mới.'
                            : '')
                    : 'Đã cập nhật hồ sơ.');
                redirect('tai-khoan/ho-so');
            }
        }

        $this->render('account/profile', array(
            'title'         => 'Hồ sơ của tôi',
            // Danh sách mục còn trống, để hiện bảng nhắc ngay đầu trang
            'thieu'         => $this->m_user->thieu_thong_tin($me['id']),
            'tong_muc'      => $this->m_user->so_muc_bat_buoc(),
            'me'            => $this->m_user->find($me['id']),
            'jobs'          => $this->m_job->names(),
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
            'viewers'  => $this->m_interaction->viewers($me['id'], 12),
        ));
    }

    public function messages($conversation_id = null)
    {
        $me = $this->auth->user();
        $messages = array();
        $partner  = null;
        $can_send = false;

        // Bấm "Nhắn tin" ở trang cá nhân sẽ tới đây kèm ?to=ID:
        // mở sẵn (hoặc tạo mới) hội thoại với người đó.
        $to = (int) $this->input->get('to');
        if ($to && $to !== (int) $me['id']) {
            // Chưa ghép đôi thì không có hội thoại để mở
            $conv = $this->m_interaction->conversation_with($me['id'], $to);
            if ($conv) {
                redirect('tai-khoan/tin-nhan/' . $conv['id']);
            }
            set_flash('danger', 'Hai bạn chưa ghép đôi nên chưa nhắn tin được. '
                . 'Hãy bấm Thích và chờ người ấy thích lại.');
            redirect('tai-khoan/quan-tam');
        }

        if ($conversation_id) {
            $conv = $this->db->where('id', $conversation_id)->get('conversations')->row_array();
            if (!$conv || !in_array((int) $me['id'], array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
                show_404();
            }
            $other_id = (int) $conv['user_low_id'] === (int) $me['id'] ? $conv['user_high_id'] : $conv['user_low_id'];
            $partner  = $this->m_user->find($other_id);
            // Hội thoại cũ của cặp chưa (hoặc không còn) ghép đôi thì chỉ xem lại
            // được lịch sử, không gửi thêm tin nhắn.
            $can_send = $this->m_interaction->is_matched($me['id'], $other_id);
            $messages = $this->m_interaction->messages($conversation_id);
            $this->m_interaction->mark_read($conversation_id, $me['id']);
        }

        $conversations = $this->m_interaction->conversations($me['id']);
        $this->render('account/messages', array(
            'title'         => 'Tin nhắn',
            'conversations' => $conversations,
            'messages'      => $messages,
            'partner'       => $partner,
            'can_send'      => $can_send,
            'conversation_id' => $conversation_id,
        ));
    }

    /* ============ Ai đã thích bạn ============ */

    public function who_liked_me()
    {
        $me = $this->auth->user();
        $this->render('account/who_liked_me', array(
            'title'   => 'Ai đã thích bạn',
            'list'    => $this->m_interaction->liked_me($me['id'], 60),
            'tong'    => $this->m_interaction->liked_me_count($me['id']),
            'la_vip'  => (bool) $this->auth->is_vip(),
        ));
    }

    /** Lịch sử các gợi ý đã nhận. */
    public function daily_history()
    {
        $me = $this->auth->user();
        $this->load->model('m_daily');
        $this->render('account/daily_history', array(
            'title' => 'Lịch sử gợi ý',
            'list'  => $this->m_daily->history($me['id'], 60),
            'today' => $this->m_daily->today($me['id']),
        ));
    }

    /* ============ Ai đã xem hồ sơ ============ */

    public function profile_viewers()
    {
        $me = $this->auth->user();
        $this->render('account/profile_viewers', array(
            'title'  => 'Ai đã xem hồ sơ bạn',
            'list'   => $this->m_interaction->viewers($me['id'], 60),
            'tong'   => $this->m_interaction->viewer_count($me['id']),
            'la_vip' => (bool) $this->auth->is_vip(),
        ));
    }

    /* ============ Chuỗi ngày hoạt động ============ */

    public function streak()
    {
        $me = $this->auth->user();
        $this->load->model('m_streak');
        $s = $this->m_streak->get($me['id']);

        if ($this->input->method() === 'post') {
            $kq = $this->input->post('mua')
                ? $this->m_streak->mua_freeze($me['id'])
                : $this->m_streak->dung_freeze($me['id']);
            set_flash($kq['ok'] ? 'success' : 'danger', $kq['message']);
            redirect('tai-khoan/chuoi');
        }

        $this->render('account/streak', array(
            'title'     => 'Chuỗi hoạt động',
            's'         => $s,
            'badges'    => $this->m_streak->badges($me['id']),
            'ke_tiep'   => $this->m_streak->moc_ke_tiep((int) $s['current_streak']),
            'boost'     => $this->m_streak->boost($me['id']),
            'tat_ca_moc' => M_streak::MOC,
        ));
    }

    /** Trang cài đặt email: bật/tắt từng loại và chọn tần suất gợi ý. */
    public function email_prefs()
    {
        $me = $this->auth->user();
        $this->load->model('m_email');

        if ($this->input->method() === 'post') {
            if ($this->input->post('huy_tat_ca')) {
                $this->m_email->unsubscribe_all($me['id']);
                set_flash('success', 'Đã huỷ nhận toàn bộ email.');
                redirect('tai-khoan/email');
            }

            $this->m_email->save_prefs($me['id'], array(
                'new_message'      => (int) (bool) $this->input->post('new_message'),
                'notification'     => (int) (bool) $this->input->post('notification'),
                'match_suggest'    => (int) (bool) $this->input->post('match_suggest'),
                're_engage'        => (int) (bool) $this->input->post('re_engage'),
                // Tần suất chung của email gợi ý và nhắc quay lại.
                'match_every_days' => in_array((int) $this->input->post('match_every_days'), array(1, 2, 3, 7), true)
                    ? (int) $this->input->post('match_every_days') : 2,
                'disabled_at'      => null,
            ));
            set_flash('success', 'Đã lưu cài đặt email.');
            redirect('tai-khoan/email');
        }

        $this->render('account/email_prefs', array(
            'title' => 'Cài đặt email',
            'p'     => $this->m_email->prefs($me['id']),
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

    /**
     * Bước "Bắt đầu" ngay sau đăng ký: ba việc nhỏ (thêm ảnh, chọn khu vực,
     * viết một câu giới thiệu), việc nào làm cũng được, có nút "Bỏ qua, để sau".
     * Thay cho cơ chế cũ bắt khai đủ hồ sơ mới cho đi tiếp.
     */
    public function onboarding()
    {
        $me = $this->m_user->find($this->auth->id());

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('bio', 'Giới thiệu bản thân', 'trim|max_length[500]');
            $this->form_validation->set_rules('province_id', 'Khu vực', 'integer');

            if ($this->form_validation->run()) {
                $data = array();
                $avatar = $this->upload_image('avatar');
                if ($avatar) {
                    $data['avatar'] = $avatar;
                }
                $tinh = (int) $this->input->post('province_id');
                if ($tinh > 0 && $this->m_province->find($tinh)) {
                    $data['province_id'] = $tinh;
                }
                $bio = trim((string) $this->input->post('bio', true));
                if ($bio !== '') {
                    $data['bio'] = $bio;
                }

                if ($data) {
                    $this->m_user->update_profile($me['id'], $data);
                }
                // upload_image() tự đặt flash khi ảnh lỗi — giữ người dùng ở lại sửa
                if (!empty($_FILES['avatar']['name']) && !$avatar) {
                    redirect('tai-khoan/bat-dau');
                }
                if ($this->m_user->thieu_thong_tin($me['id'])) {
                    set_flash('success', 'Đã lưu! Còn vài bước nữa là hồ sơ hiện với mọi người — làm tiếp lúc nào cũng được.');
                } else {
                    set_flash('success', 'Tuyệt! Hồ sơ của bạn đã hiển thị với mọi người.');
                }
                redirect('tai-khoan');
            }
        }

        $this->render('account/onboarding', array(
            'title' => 'Bắt đầu',
            'me'    => $me,
        ));
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
