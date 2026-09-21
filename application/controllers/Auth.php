<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_otp'));
        $this->load->library('mailer');
    }

    /** Số điện thoại phải là số di động Việt Nam hợp lệ. */
    public function dien_thoai_hop_le($so)
    {
        if (chuan_hoa_dien_thoai($so) === '') {
            $this->form_validation->set_message('dien_thoai_hop_le',
                'Số điện thoại không đúng. Nhập số di động Việt Nam 10 chữ số, VD: 0912345678.');
            return false;
        }
        return true;
    }

    public function register()
    {
        if ($this->auth->check()) {
            redirect('tai-khoan');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('display_name', 'Tên hiển thị', 'required|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[190]');
            $this->form_validation->set_rules('phone', 'Số điện thoại',
                'required|trim|callback_dien_thoai_hop_le');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Xác nhận mật khẩu', 'required|matches[password]');
            $this->form_validation->set_rules('gender', 'Giới tính', 'required|in_list[male,female,other]');
            $this->form_validation->set_rules('birthday', 'Ngày sinh', 'required');
            $this->form_validation->set_rules('province_id', 'Khu vực', 'required');
            $this->form_validation->set_rules('agree', 'Điều khoản', 'required');

            if ($this->form_validation->run()) {
                $email = $this->input->post('email', true);
                $phone = chuan_hoa_dien_thoai($this->input->post('phone'));

                if ($this->m_user->email_exists($email)) {
                    set_flash('danger', 'Email đã được sử dụng.');
                } elseif ($phone && $this->m_user->phone_exists($phone)) {
                    set_flash('danger', 'Số điện thoại đã được sử dụng.');
                } else {
                    $id = $this->m_user->register(array(
                        'display_name' => $this->input->post('display_name', true),
                        'email'        => $email,
                        'phone'        => $phone,
                        'password'     => $this->input->post('password'),
                        'gender'       => $this->input->post('gender'),
                        'birthday'     => $this->input->post('birthday'),
                        'province_id'  => $this->input->post('province_id'),
                    ));
                    // Bật xác thực email thì chưa cho vào ngay, phải nhập mã trước
                    if (setting('otp_register', '1') === '1') {
                        return $this->bat_dau_otp($id, 'register');
                    }

                    $this->auth->login($this->m_user->find($id));
                    set_flash('success', 'Đăng ký thành công. Hãy hoàn thiện hồ sơ để được ghép đôi tốt hơn!');
                    redirect('tai-khoan/ho-so');
                }
            }
        }

        $this->render('auth/register', array('title' => 'Đăng ký tài khoản'));
    }

    public function login()
    {
        if ($this->auth->check()) {
            redirect('tai-khoan');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('identity', 'Email/SĐT', 'required');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required');

            if ($this->form_validation->run()) {
                $identity = $this->input->post('identity', true);
                $remember = (bool) $this->input->post('remember');

                // Đăng ký xong mà chưa xác thực email thì phải xác thực trước,
                // không thì chỉ cần đăng nhập bằng mật khẩu là né được bước này.
                if (setting('otp_register', '1') === '1') {
                    $chua_xac = $this->auth->kiem_mat_khau($identity, $this->input->post('password'));
                    if (is_array($chua_xac) && empty($chua_xac['email_verified_at'])
                        && !empty($chua_xac['email'])) {
                        set_flash('warning', 'Email của bạn chưa được xác thực. '
                            . 'Chúng tôi vừa gửi lại mã xác thực.');
                        return $this->bat_dau_otp($chua_xac['id'], 'register');
                    }
                }

                $result = $this->auth->attempt($identity, $this->input->post('password'), $remember);
                if ($result === true) {
                    redirect($this->sau_dang_nhap());
                }
                set_flash('danger', $result === 'locked'
                    ? 'Tài khoản đang bị khoá. Liên hệ hỗ trợ để được trợ giúp.'
                    : 'Email/SĐT hoặc mật khẩu không đúng.');
            }
        }

        $this->render('auth/login', array('title' => 'Đăng nhập'));
    }

    /* ===================== Mã OTP qua email ===================== */

    /** Nơi cần tới sau khi đăng nhập xong. */
    private function sau_dang_nhap()
    {
        $next = $this->input->get('next');
        return $next ? urldecode($next) : 'tai-khoan';
    }

    /**
     * Sinh mã, gửi email rồi đưa người dùng sang trang nhập mã.
     * Trạng thái "đang chờ mã" giữ trong session, chưa tạo phiên đăng nhập.
     */
    private function bat_dau_otp($user_id, $muc_dich, $remember = false)
    {
        $this->session->set_userdata('otp_cho', array(
            'user_id'  => (int) $user_id,
            'muc_dich' => $muc_dich,
            'remember' => (bool) $remember,
            'next'     => $this->input->get('next'),
        ));

        $this->gui_ma($user_id, $muc_dich);
        redirect('xac-thuc');
    }

    /** Gửi (hoặc gửi lại) mã tới email của người dùng. */
    private function gui_ma($user_id, $muc_dich)
    {
        $user = $this->m_user->find($user_id);
        if (!$user || empty($user['email'])) {
            set_flash('danger', 'Tài khoản này chưa có email nên không gửi được mã.');
            return false;
        }

        $ma   = $this->m_otp->tao($user_id, $muc_dich);
        $sent = $this->mailer->send(
            $user['email'],
            ($muc_dich === 'register' ? 'Mã xác thực email' : 'Mã đăng nhập') . ' - '
                . setting('site_name', 'Saigon Cupid'),
            'otp',
            array(
                'name'    => display_name($user),
                'code'    => $ma,
                'minutes' => M_otp::PHUT_SONG,
                'purpose' => $muc_dich,
            )
        );

        if ($sent) {
            set_flash('success', 'Đã gửi mã xác thực tới ' . mask_email($user['email']) . '.');
            return true;
        }

        // Gửi hỏng thì nói thật; lúc đang phát triển thì hiện mã ra cho đỡ tắc
        set_flash('warning', ENVIRONMENT === 'production'
            ? 'Hệ thống chưa gửi được email. Bấm "Gửi lại mã" sau ít phút hoặc liên hệ hỗ trợ.'
            : 'Chưa gửi được email. Mã (chỉ hiện khi đang phát triển): ' . $ma);
        return false;
    }

    /** Lấy trạng thái chờ mã trong session, hết hạn thì trả về null. */
    private function phien_otp()
    {
        $cho = $this->session->userdata('otp_cho');
        if (!is_array($cho) || empty($cho['user_id'])) {
            return null;
        }
        return $cho;
    }

    /** Trang nhập mã cho cả đăng ký lẫn đăng nhập. */
    public function verify()
    {
        $cho = $this->phien_otp();
        if (!$cho) {
            set_flash('danger', 'Phiên xác thực đã kết thúc. Vui lòng thao tác lại.');
            redirect('dang-nhap');
        }

        $user = $this->m_user->find($cho['user_id']);
        if (!$user) {
            $this->session->unset_userdata('otp_cho');
            redirect('dang-nhap');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('code', 'Mã xác thực', 'required');

            if ($this->form_validation->run()) {
                $kq = $this->m_otp->kiem_tra($cho['user_id'], $cho['muc_dich'], $this->input->post('code'));

                if ($kq['ok']) {
                    $this->session->unset_userdata('otp_cho');

                    if ($cho['muc_dich'] === 'register') {
                        // Xác thực xong mới kích hoạt tài khoản
                        $this->db->where('id', $user['id'])->update('users', array(
                            'email_verified_at' => date('Y-m-d H:i:s'),
                            'status' => $user['status'] === 'pending' ? 'active' : $user['status'],
                        ));
                        $this->auth->login($this->m_user->find($user['id']));
                        set_flash('success', 'Xác thực email thành công! '
                            . 'Hãy hoàn thiện hồ sơ để được ghép đôi tốt hơn.');
                        redirect('tai-khoan/ho-so');
                    }

                    // Phiên chờ cũ còn sót từ lúc có OTP đăng nhập: đăng nhập luôn
                    $this->auth->login($user);
                    redirect($cho['next'] ? urldecode($cho['next']) : 'tai-khoan');
                }

                set_flash('danger', $kq['message']);
            }
        }

        $this->render('auth/otp', array(
            'title'     => $cho['muc_dich'] === 'register' ? 'Xác thực email' : 'Xác minh đăng nhập',
            'email'     => mask_email($user['email']),
            'purpose'   => $cho['muc_dich'],
            'cho_giay'  => $this->m_otp->con_cho($cho['user_id'], $cho['muc_dich']),
            'phut_song' => M_otp::PHUT_SONG,
        ));
    }

    /** Gửi lại mã, có chặn bấm liên tục. */
    public function resend()
    {
        $cho = $this->phien_otp();
        if (!$cho) {
            redirect('dang-nhap');
        }

        $con_cho = $this->m_otp->con_cho($cho['user_id'], $cho['muc_dich']);
        if ($con_cho > 0) {
            set_flash('warning', 'Vui lòng chờ thêm ' . $con_cho . ' giây rồi hãy gửi lại mã.');
        } else {
            $this->gui_ma($cho['user_id'], $cho['muc_dich']);
        }
        redirect('xac-thuc');
    }

    public function logout()
    {
        $this->auth->logout();
        redirect('/');
    }

    /** Quên mật khẩu: sinh token đặt lại. */
    public function forgot()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            if ($this->form_validation->run()) {
                $email = $this->input->post('email', true);
                $user  = $this->db->where('email', $email)->get('users')->row_array();
                if (!$user) {
                    set_flash('danger', 'Email này chưa được đăng ký trên hệ thống. '
                        . 'Hãy kiểm tra lại hoặc tạo tài khoản mới.');
                    redirect('quen-mat-khau');
                }

                if ($user['status'] === 'banned') {
                    set_flash('danger', 'Tài khoản này đã bị khoá. Vui lòng liên hệ hỗ trợ.');
                    redirect('quen-mat-khau');
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

                $link = site_url('dat-lai-mat-khau/' . $token);
                $sent = $this->mailer->send(
                    $user['email'],
                    'Đặt lại mật khẩu - ' . setting('site_name', 'Saigon Cupid'),
                    'reset_password',
                    array(
                        'name'  => display_name($user),
                        'link'  => $link,
                        'hours' => $hours,
                    )
                );

                if ($sent) {
                    set_flash('success', 'Đã gửi hướng dẫn đặt lại mật khẩu tới '
                        . mask_email($user['email']) . '. Liên kết có hiệu lực trong '
                        . $hours . ' giờ. Nếu không thấy thư, hãy kiểm tra mục Spam.');
                } else {
                    // Không gửi được: nói thật thay vì để người dùng chờ vô ích
                    set_flash('warning', ENVIRONMENT === 'production'
                        ? 'Hệ thống chưa gửi được email, vui lòng thử lại sau hoặc liên hệ hỗ trợ.'
                        : 'Chưa gửi được email. Liên kết đặt lại (chỉ hiện khi đang phát triển): ' . $link);
                }
                redirect('quen-mat-khau');
            }
        }

        $this->render('auth/forgot', array('title' => 'Quên mật khẩu'));
    }

    public function reset($token)
    {
        $row = $this->db->where('token', $token)->where('type', 'reset_password')
            ->where('used_at', null)->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('user_tokens')->row_array();

        if (!$row) {
            set_flash('danger', 'Liên kết không hợp lệ hoặc đã hết hạn.');
            redirect('quen-mat-khau');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('password', 'Mật khẩu mới', 'required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Xác nhận', 'required|matches[password]');
            if ($this->form_validation->run()) {
                $this->m_user->change_password($row['user_id'], $this->input->post('password'));
                $this->db->where('id', $row['id'])->update('user_tokens', array('used_at' => date('Y-m-d H:i:s')));
                set_flash('success', 'Đặt lại mật khẩu thành công, mời bạn đăng nhập.');
                redirect('dang-nhap');
            }
        }

        $this->render('auth/reset', array('title' => 'Đặt lại mật khẩu', 'token' => $token));
    }
}
