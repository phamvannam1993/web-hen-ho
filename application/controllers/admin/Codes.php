<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quản lý mã bảo mật (pass) cho đăng ký / đăng nhập / mở liên hệ. */
class Codes extends Admin_Controller
{
    private $per_page = 30;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_access_code');
    }

    public function index($page = 1)
    {
        $purpose = $this->input->get('purpose');
        $page    = max(1, (int) $page);
        $total   = $this->m_access_code->admin_count($purpose);

        $this->render('admin/codes/index', array(
            'title'      => 'Mã bảo mật',
            'codes'      => $this->m_access_code->admin_list($purpose, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links('admin/codes', $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    /** Phát mã dùng chung nhiều lượt. */
    public function create()
    {
        $code = $this->m_access_code->create_shared(
            $this->input->post('purpose') ?: 'register',
            (int) $this->input->post('max_uses') ?: 100,
            (int) $this->input->post('days') ?: 30
        );
        $this->log_action('create_shared_code', 'access_codes', null);
        set_flash('success', 'Đã tạo mã dùng chung: ' . $code);
        redirect('admin/codes');
    }

    public function delete($id)
    {
        $this->m_access_code->remove($id);
        set_flash('success', 'Đã xoá mã.');
        redirect('admin/codes');
    }
}
