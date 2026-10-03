<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_otp'));
        $this->load->library(array('mailer', 'emailer'));
    }

    /** Phải đủ 18 tuổi mới được đăng ký. */
    public function du_18_tuoi($ngay)
    {
        $d = DateTime::createFromFormat('Y-m-d', (string) $ngay);
        if (!$d || $d->format('Y-m-d') !== $ngay || $d > new DateTime()) {
            $this->form_validation->set_message('du_18_tuoi', 'Ngày sinh không hợp lệ.');
            return false;
        }
        if ($d->diff(new DateTime())->y < 18) {
            $this->form_validation->set_message('du_18_tuoi', 'Bạn cần đủ 18 tuổi để tham gia.');
            return false;
        }
        return true;
    }

    /**
     * Đăng ký: chỉ hỏi những gì cần để tài khoản tồn tại (tên hiển thị, email,
     * mật khẩu, giới tính, ngày sinh). Khu vực, số điện thoại, ảnh… hỏi sau ở
     * bước "Bắt đầu" và trang Hồ sơ. Đăng ký xong vào luôn, xác thực email sau.
     */
    public function register()
    {
        if ($this->auth->check()) {
            redirect('tai-khoan');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('display_name', 'Tên hiển thị', 'required|trim|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[190]');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required|min_length[6]');
            $this->form_validation->set_rules('gender', 'Giới tính', 'required|in_list[male,female,other]');
            $this->form_validation->set_rules('birthday', 'Ngày sinh', 'required|callback_du_18_tuoi');
            $this->form_validation->set_rules('agree', 'Điều khoản', 'required');

            if ($this->form_validation->run()) {
                $email = $this->input->post('email', true);

                if ($this->m_user->email_exists($email)) {
                    set_flash('danger', 'Email đã được sử dụng. Bạn có thể đăng nhập hoặc lấy lại mật khẩu.');
                } else {
                    $id = $this->m_user->register(array(
                        'display_name' => $this->input->post('display_name', true),
                        'email'        => $email,
                        'phone'        => null,
                        'password'     => $this->input->post('password'),
                        'gender'       => $this->input->post('gender'),
                        'birthday'     => $this->input->post('birthday'),
                        'province_id'  => null,
                    ));
                    // Thư chào mừng xếp hàng đợi, gửi sau 1 phút
                    $this->emailer->welcome($id);

                    if (setting('otp_register', '1') === '1') {
                        $this->gui_link($id);
                    } else {
                        $this->db->where('id', $id)->update('users', array('email_verified_at' => date('Y-m-d H:i:s')));
                        set_flash('success', 'Đăng ký thành công! Chào mừng bạn.');
                    }

                    $this->auth->login($this->m_user->find($id));
                    redirect('tai-khoan/bat-dau');
                }
            }
        }

        $this->render('auth/register', array(
            'title'   => 'Đăng ký tài khoản',
            // Ô ngày sinh không cho chọn ngày làm người dùng chưa đủ 18 tuổi
            'max_dob' => date('Y-m-d', strtotime('-18 years')),
        ));
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

                // Chưa xác thực email vẫn đăng nhập được; chỉ thả tim và nhắn tin
                // bị khoá cho tới khi bấm link trong thư (xem Userauth::da_xac_thuc).
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

    /** Nơi cần tới sau khi đăng nhập xong. */
    private function sau_dang_nhap()
    {
        $next = $this->input->get('next');
        return $next ? urldecode($next) : 'tai-khoan';
    }

    /* ===================== Xác thực email bằng link một chạm ===================== */

    /** Gửi (hoặc gửi lại) thư có link xác thực. Kết quả báo qua flash. */
    private function gui_link($user_id)
    {
        $user = $this->m_user->find($user_id);
        if (!$user || empty($user['email'])) {
            return false;
        }

        $link = site_url('xac-thuc-email/' . $this->m_otp->tao_link($user_id));
        $sent = $this->mailer->send(
            $user['email'],
            'Xác thực email - ' . setting('site_name', 'Saigon Cupid'),
            'verify_email',
            array(
                'name'  => display_name($user),
                'link'  => $link,
                'hours' => M_otp::GIO_SONG_LINK,
            )
        );

        if ($sent) {
            set_flash('success', 'Đã gửi link xác thực tới ' . mask_email($user['email'])
                . '. Không thấy thư thì kiểm tra cả thư mục Spam nhé.');
            return true;
        }

        // Gửi hỏng thì nói thật; lúc đang phát triển thì hiện link ra cho đỡ tắc
        set_flash('warning', ENVIRONMENT === 'production'
            ? 'Hệ thống chưa gửi được email xác thực. Bấm "Gửi lại" sau ít phút hoặc liên hệ hỗ trợ.'
            : 'Chưa gửi được email. Link xác thực (chỉ hiện khi đang phát triển): ' . $link);
        return false;
    }

    /** Trang "Kiểm tra email" — nơi banner nhắc xác thực trỏ tới. */
    public function verify()
    {
        if (!$this->auth->check()) {
            redirect('dang-nhap?next=' . urlencode('xac-thuc'));
        }
        if ($this->auth->da_xac_thuc()) {
            set_flash('success', 'Email của bạn đã được xác thực.');
            redirect('tai-khoan');
        }

        $me = $this->auth->user();
        $this->render('auth/check_email', array(
            'title'    => 'Xác thực email',
            'email'    => mask_email($me['email']),
            'cho_giay' => $this->m_otp->con_cho($me['id'], 'register'),
            'gio_song' => M_otp::GIO_SONG_LINK,
        ));
    }

    /** Gửi lại link xác thực, có chặn bấm liên tục. Chỉ nhận POST. */
    public function resend()
    {
        if (!$this->auth->check()) {
            redirect('dang-nhap');
        }
        if ($this->input->method() !== 'post' || $this->auth->da_xac_thuc()) {
            redirect('xac-thuc');
        }

        $me      = $this->auth->user();
        $con_cho = $this->m_otp->con_cho($me['id'], 'register');
        if ($con_cho > 0) {
            set_flash('warning', 'Vui lòng chờ thêm ' . $con_cho . ' giây rồi hãy gửi lại.');
        } else {
            $this->gui_link($me['id']);
        }
        redirect('xac-thuc');
    }

    /** Người dùng bấm link trong thư. */
    public function verify_link($token = '')
    {
        $uid = $this->m_otp->dung_link($token);
        if (!$uid) {
            set_flash('danger', 'Link xác thực đã hết hạn hoặc đã được dùng. '
                . 'Đăng nhập rồi bấm "Gửi lại" để nhận link mới.');
            redirect($this->auth->check() ? 'xac-thuc' : 'dang-nhap');
        }

        $user = $this->m_user->find($uid);
        if ($user) {
            $this->db->where('id', $uid)->update('users', array(
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status' => $user['status'] === 'pending' ? 'active' : $user['status'],
            ));
        }

        set_flash('success', 'Xác thực email thành công! Bạn đã có thể thả tim và nhắn tin.');
        redirect($this->auth->check() ? 'tai-khoan' : 'dang-nhap');
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
