<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Madmin', 'MUser']);
        $this->load->database();
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
    }
    public function admin()
    {
        if (admin()) {
            $data = [];
            $this->load->view('admin/index', $data);
        } else {
            redirect('/admin/login/');
        }
    }

    public function dashboard() {
        if (!admin()) { 
            redirect('admin/login');
        }
        $data = [];
        $this->load->view('admin/pages/home', $data);
    }

    public function logout()
    {
        unset($_SESSION['admin']);
        header('Location: /');
        exit;
    }

    public function login()
    {
        $req = $this->input->post();
        if($req) {
            $username = $req['username'];
            $password = md5($req['password']);
            $check_admin = $this->MUser->getOneBy(['username' => $username, 'password' => $password]);
            if ($check_admin != null) {
                $this->session->set_userdata('admin', $check_admin);
                redirect('/admin/dashboard');
            } else {
                $req['message'] = 'Tài khoản hoặc mật khẩu không đúng';
                $this->load->view('admin/pages/login', $req);
            }
        } else if (!admin()) {
            $this->load->view('admin/pages/login');
        } else {
            redirect('/admin/dashboard');
        }
    }
}
