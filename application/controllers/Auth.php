<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_user');
        $this->load->library('mailer');
    }

    public function register()
    {
        if ($this->auth->check()) {
            redirect('tai-khoan');
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('display_name', 'Tên hiển thị', 'required|min_length[2]|max_length[100]');
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[190]');
            $this->form_validation->set_rules('phone', 'Số điện thoại', 'trim|max_length[20]');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Xác nhận mật khẩu', 'required|matches[password]');
            $this->form_validation->set_rules('gender', 'Giới tính', 'required|in_list[male,female,other]');
            $this->form_validation->set_rules('agree', 'Điều khoản', 'required');

            if ($this->form_validation->run()) {
                $email = $this->input->post('email', true);
                $phone = $this->input->post('phone', true);

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
                $result = $this->auth->attempt(
                    $this->input->post('identity', true),
                    $this->input->post('password'),
                    (bool) $this->input->post('remember')
                );
                if ($result === true) {
                    $next = $this->input->get('next');
                    redirect($next ? urldecode($next) : 'tai-khoan');
                }
                set_flash('danger', $result === 'locked'
                    ? 'Tài khoản đang bị khoá. Liên hệ hỗ trợ để được trợ giúp.'
                    : 'Email/SĐT hoặc mật khẩu không đúng.');
            }
        }

        $this->render('auth/login', array('title' => 'Đăng nhập'));
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
