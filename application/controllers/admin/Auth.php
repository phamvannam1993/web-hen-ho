<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Đăng nhập khu quản trị (không kế thừa Admin_Controller để tránh vòng lặp chuyển hướng). */
class Auth extends CI_Controller
{
    public function login()
    {
        if ($this->auth->is_admin()) {
            redirect('admin');
        }

        $error = null;
        if ($this->input->method() === 'post') {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('identity', 'Email', 'required');
            $this->form_validation->set_rules('password', 'Mật khẩu', 'required');

            if ($this->form_validation->run()) {
                $ok = $this->auth->attempt($this->input->post('identity', true), $this->input->post('password'));
                if ($ok === true && $this->auth->is_admin()) {
                    $this->db->insert('admin_logs', array(
                        'admin_id' => $this->auth->id(),
                        'action'   => 'login',
                        'ip'       => $this->input->ip_address(),
                    ));
                    redirect('admin');
                }
                if ($ok === true) {
                    $this->auth->logout();
                    $error = 'Tài khoản không có quyền truy cập khu quản trị.';
                } else {
                    $error = 'Thông tin đăng nhập không đúng.';
                }
            }
        }

        $this->load->view('admin/auth/login', array('error' => $error));
    }

    public function logout()
    {
        $this->auth->logout();
        redirect('admin/dang-nhap');
    }
}
