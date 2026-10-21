<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Đăng ký / đăng nhập / quên mật khẩu cho ứng dụng di động. */
class Auth extends Api_Controller
{
    /** POST /api/auth/login  {identity, password, device} */
    public function login()
    {
        $identity = trim((string) $this->body('identity'));
        $password = (string) $this->body('password');

        if ($identity === '' || $password === '') {
            return $this->loi('Thiếu email/SĐT hoặc mật khẩu.', 422);
        }

        $u = $this->auth->kiem_mat_khau($identity, $password);
        if ($u === 'locked') {
            return $this->loi('Tài khoản đang bị khoá.', 403);
        }
        if (!$u) {
            return $this->loi('Email/SĐT hoặc mật khẩu không đúng.', 401);
        }

        // Chưa xác thực email vẫn đăng nhập được, giống luồng web — chỉ thả tim và
        // nhắn tin bị khoá tới khi bấm link trong thư.
        return $this->ok($this->cap_token($u));
    }

    /**
     * POST /api/auth/register
     *   {display_name, email, password, gender, birthday (Y-m-d), agree, device}
     *
     * Giống form đăng ký web: chỉ hỏi những gì cần để tài khoản tồn tại, khu vực
     * và ảnh khai sau ở bước "Bắt đầu". Đăng ký xong trả token để vào thẳng.
     */
    public function register()
    {
        $ten      = trim((string) $this->body('display_name'));
        $email    = trim((string) $this->body('email'));
        $password = (string) $this->body('password');
        $gender   = (string) $this->body('gender');
        $birthday = (string) $this->body('birthday');

        $loi = array();
        if (mb_strlen($ten) < 2 || mb_strlen($ten) > 100) {
            $loi['display_name'] = 'Tên hiển thị từ 2 đến 100 ký tự.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $loi['email'] = 'Email không hợp lệ.';
        }
        if (strlen($password) < 6) {
            $loi['password'] = 'Mật khẩu tối thiểu 6 ký tự.';
        }
        if (!in_array($gender, array('male', 'female', 'other'), true)) {
            $loi['gender'] = 'Hãy chọn giới tính.';
        }
        $d = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$d || $d->format('Y-m-d') !== $birthday || $d > new DateTime()) {
            $loi['birthday'] = 'Ngày sinh không hợp lệ.';
        } elseif ($d->diff(new DateTime())->y < 18) {
            $loi['birthday'] = 'Bạn cần đủ 18 tuổi để tham gia.';
        }
        if (!$this->body('agree')) {
            $loi['agree'] = 'Bạn cần đồng ý với điều khoản.';
        }
        if ($loi) {
            return $this->loi(reset($loi), 422, array('errors' => $loi));
        }
        if ($this->m_user->email_exists($email)) {
            return $this->loi('Email đã được sử dụng. Bạn có thể đăng nhập hoặc lấy lại mật khẩu.', 409,
                array('errors' => array('email' => 'Email đã được sử dụng.')));
        }

        $this->load->library(array('mailer', 'emailer'));
        $this->load->model('m_otp');

        $id = $this->m_user->register(array(
            'display_name' => $ten,
            'email'        => $email,
            'phone'        => null,
            'password'     => $password,
            'gender'       => $gender,
            'birthday'     => $birthday,
            'province_id'  => null,
        ));
        // Thư chào mừng xếp hàng đợi, gửi sau 1 phút
        $this->emailer->welcome($id);

        $message = 'Đăng ký thành công! Chào mừng bạn.';
        if (setting('otp_register', '1') === '1') {
            $message = $this->gui_link($id)['message'];
        } else {
            $this->db->where('id', $id)->update('users', array('email_verified_at' => date('Y-m-d H:i:s')));
        }

        return $this->ok(array_merge($this->cap_token($this->m_user->find($id)),
            array('message' => $message, 'vip_gift'=>array('days'=>30, 'message'=>'Bạn đã nhận được 30 ngày VIP miễn phí!'))), 201);
    }

    /** Phát token mới cho người dùng và dựng phần trả về chung của login/register. */
    private function cap_token(array $u)
    {
        // Token gốc chỉ xuất hiện đúng một lần ở đây, DB chỉ giữ bản băm
        $token = bin2hex(random_bytes(32));
        $this->db->insert('api_tokens', array(
            'user_id'    => $u['id'],
            'token_hash' => hash('sha256', $token),
            'device'     => mb_substr((string) $this->body('device', ''), 0, 120) ?: null,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
        ));

        // kiem_mat_khau() đọc thẳng bảng users nên chưa có tên tỉnh
        if (!array_key_exists('province_name', $u)) {
            $u = $this->m_user->find($u['id']) ?: $u;
        }
        return array(
            'token'      => $token,
            'expires_at' => date('c', strtotime('+90 days')),
            'user'       => $this->ho_so($u, true),
            // false: ứng dụng nên nhắc bấm link xác thực trong thư; thả tim và nhắn tin đang bị khoá
            'email_verified' => setting('otp_register', '1') !== '1' || !empty($u['email_verified_at']) || empty($u['email']),
        );
    }

    /** Gửi thư có link xác thực. Trả về ['ok'=>bool,'message'=>string]. */
    private function gui_link($user_id)
    {
        $user = $this->m_user->find($user_id);
        if (!$user || empty($user['email'])) {
            return array('ok' => false, 'message' => 'Tài khoản chưa có email.');
        }

        $link = site_url('xac-thuc-email/' . $this->m_otp->tao_link($user_id));
        $sent = $this->mailer->send(
            $user['email'],
            'Xác thực email - ' . setting('site_name', 'Saigon Cupid'),
            'verify_email',
            array('name' => display_name($user), 'link' => $link, 'hours' => M_otp::GIO_SONG_LINK)
        );

        if ($sent) {
            return array('ok' => true, 'message' => 'Đã gửi link xác thực tới ' . mask_email($user['email'])
                . '. Không thấy thư thì kiểm tra cả thư mục Spam nhé.');
        }
        return array('ok' => false, 'message' => ENVIRONMENT === 'production'
            ? 'Hệ thống chưa gửi được email xác thực. Bấm "Gửi lại" sau ít phút hoặc liên hệ hỗ trợ.'
            : 'Chưa gửi được email. Link xác thực (chỉ hiện khi đang phát triển): ' . $link);
    }

    /** POST /api/auth/resend-verify — gửi lại link xác thực, có chặn bấm liên tục. */
    public function resend_verify()
    {
        if (!$this->can_auth()) {
            return;
        }
        if ($this->auth->da_xac_thuc()) {
            return $this->ok(array('verified' => true, 'message' => 'Email của bạn đã được xác thực.'));
        }
        $this->load->library('mailer');
        $this->load->model('m_otp');

        $cho = $this->m_otp->con_cho($this->me['id'], 'register');
        if ($cho > 0) {
            return $this->loi('Vui lòng chờ thêm ' . $cho . ' giây rồi hãy gửi lại.', 429,
                array('wait_seconds' => $cho));
        }
        $kq = $this->gui_link($this->me['id']);
        return $kq['ok'] ? $this->ok(array('message' => $kq['message'])) : $this->loi($kq['message'], 502);
    }

    /** POST /api/auth/forgot {email} — gửi link đặt lại mật khẩu (mở trên web). */
    public function forgot()
    {
        $email = trim((string) $this->body('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->loi('Email không hợp lệ.', 422);
        }
        $user = $this->db->where('email', $email)->where('deleted_at', null)->get('users')->row_array();
        if (!$user) {
            return $this->loi('Email này chưa được đăng ký trên hệ thống.', 404);
        }
        if ($user['status'] === 'banned') {
            return $this->loi('Tài khoản này đã bị khoá. Vui lòng liên hệ hỗ trợ.', 403);
        }

        // Vô hiệu các liên kết cũ chưa dùng, mỗi lần yêu cầu chỉ còn một liên kết hợp lệ
        $this->db->where('user_id', $user['id'])
            ->where('type', 'reset_password')->where('used_at', null)
            ->update('user_tokens', array('used_at' => date('Y-m-d H:i:s')));

        $hours = 2;
        $token = bin2hex(random_bytes(24));
        $this->db->insert('user_tokens', array(
            'user_id'    => $user['id'],
            'type'       => 'reset_password',
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours')),
        ));

        $this->load->library('mailer');
        $link = site_url('dat-lai-mat-khau/' . $token);
        $sent = $this->mailer->send($user['email'],
            'Đặt lại mật khẩu - ' . setting('site_name', 'Saigon Cupid'), 'reset_password',
            array('name' => display_name($user), 'link' => $link, 'hours' => $hours));

        if (!$sent) {
            return $this->loi(ENVIRONMENT === 'production'
                ? 'Hệ thống chưa gửi được email, vui lòng thử lại sau hoặc liên hệ hỗ trợ.'
                : 'Chưa gửi được email. Liên kết đặt lại (chỉ hiện khi đang phát triển): ' . $link, 502);
        }
        return $this->ok(array('message' => 'Đã gửi hướng dẫn đặt lại mật khẩu tới '
            . mask_email($user['email']) . '. Liên kết có hiệu lực trong ' . $hours . ' giờ.'));
    }

    /** POST /api/auth/password {current, password} */
    public function password()
    {
        if (!$this->can_auth()) {
            return;
        }
        $moi = (string) $this->body('password');
        if (strlen($moi) < 6) {
            return $this->loi('Mật khẩu mới tối thiểu 6 ký tự.', 422);
        }
        if (!password_verify((string) $this->body('current'), $this->me['password_hash'])) {
            return $this->loi('Mật khẩu hiện tại không đúng.', 422);
        }
        $this->m_user->change_password($this->me['id'], $moi);
        return $this->ok(array('message' => 'Đã đổi mật khẩu.'));
    }

    /** POST /api/auth/logout — thu hồi token đang dùng. */
    public function logout()
    {
        if (!$this->can_auth()) {
            return;
        }
        $h = $this->input->get_request_header('Authorization', true);
        preg_match('/^Bearer\s+(.+)$/i', trim((string) $h), $m);

        $this->db->where('token_hash', hash('sha256', trim($m[1])))
            ->update('api_tokens', array('revoked_at' => date('Y-m-d H:i:s')));

        return $this->ok(array('message' => 'Đã đăng xuất.'));
    }

    /** GET /api/auth/me — hồ sơ, số dư, VIP, các con số cho huy hiệu trên thanh tab. */
    public function me()
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->load->model(array('m_streak', 'm_interaction', 'm_notification', 'm_interest_badge'));
        $id = $this->me['id'];
        $s  = $this->m_streak->get($id);
        // Cùng bộ đếm với menu web: mục quan tâm chưa xem (4 thẻ) + tin nhắn + thông báo
        $menu = $this->m_interest_badge->menu_counts($id);

        return $this->ok(array(
            'user'           => $this->ho_so($this->me, true),
            'coins'          => (int) $this->me['coin_balance'],
            'is_vip'         => $this->la_vip(),
            'vip_until'      => $this->la_vip() && $this->me['vip_expired_at']
                ? date('d/m/Y', strtotime($this->me['vip_expired_at'])) : null,
            'streak'         => (int) $s['current_streak'],
            'email'          => $this->me['email'],
            'email_verified' => $this->auth->da_xac_thuc(),
            // Mục còn thiếu để hồ sơ hiện công khai, và % hoàn thiện cho thanh tiến độ
            'missing'        => $this->m_user->thieu_thong_tin($id),
            'completion'     => tk_ho_so_day_du($this->me)['phan_tram'],
            'counts'         => array(
                'liked_me'      => $this->m_interaction->liked_me_count($id),
                'messages'      => (int) $menu['msg'],
                'notifications' => (int) $menu['noti'],
                'interest'      => (int) $menu['interest'],
                'interest_tabs' => $menu['interest_tabs'],
            ),
            'has_email'      => !empty($this->me['email']),
        ));
    }
}
