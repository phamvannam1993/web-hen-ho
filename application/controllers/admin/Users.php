<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Admin_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_province', 'm_billing', 'm_notification'));
    }

    public function index($page = 1)
    {
        $filters = array_filter(array(
            'keyword' => $this->input->get('q', true),
            'status'  => $this->input->get('status'),
            'role'    => $this->input->get('role'),
            'gender'  => $this->input->get('gender'),
        ));
        $page   = max(1, (int) $page);
        $total  = $this->m_user->admin_count($filters);

        $this->render('admin/users/index', array(
            'title'      => 'Quản lý thành viên',
            'users'      => $this->m_user->admin_list($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links('admin/users', $page, $total, $this->per_page, $this->input->get()),
            'filters'    => $filters,
        ));
    }

    public function view($id)
    {
        $user = $this->m_user->find($id);
        if (!$user) {
            show_404();
        }
        $this->render('admin/users/view', array(
            'title'    => 'Hồ sơ: ' . $user['display_name'],
            'u'        => $user,
            'posts'    => $this->db->where('user_id', $id)->order_by('id', 'DESC')->limit(10)->get('posts')->result_array(),
            'orders'   => $this->m_billing->orders_of($id, 10),
            'coins'    => $this->m_billing->coin_history($id, 20),
        ));
    }

    public function edit($id)
    {
        $user = $this->m_user->find($id);
        if (!$user) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->m_user->update_profile($id, array(
                'display_name'   => $this->input->post('display_name', true),
                'nickname'       => $this->input->post('nickname', true) ?: null,
                'email'          => $this->input->post('email', true) ?: null,
                'phone'          => $this->input->post('phone', true) ?: null,
                'gender'         => $this->input->post('gender'),
                'birthday'       => $this->input->post('birthday') ?: null,
                'province_id'    => $this->input->post('province_id') ?: null,
                'job'            => $this->input->post('job', true),
                'bio'            => $this->input->post('bio', true),
                'role'           => $this->input->post('role'),
                'status'         => $this->input->post('status'),
                'kyc_status'     => $this->input->post('kyc_status'),
            ));
            if ($this->input->post('password')) {
                $this->m_user->change_password($id, $this->input->post('password'));
            }
            $this->log_action('update_user', 'users', $id);
            set_flash('success', 'Đã cập nhật thành viên.');
            redirect('admin/users/view/' . $id);
        }

        $this->render('admin/users/edit', array(
            'title'     => 'Sửa thành viên',
            'u'         => $user,
            'provinces' => $this->m_province->all(),
        ));
    }

    public function set_status($id, $status)
    {
        if (!in_array($status, array('pending', 'active', 'locked', 'banned'), true)) {
            show_404();
        }
        $this->m_user->set_status($id, $status);
        $this->log_action('set_status:' . $status, 'users', $id);
        set_flash('success', 'Đã đổi trạng thái thành viên.');
        redirect($this->input->server('HTTP_REFERER') ?: 'admin/users');
    }

    /** Cộng / trừ xu thủ công. */
    public function adjust_coin($id)
    {
        $amount = (int) $this->input->post('amount');
        if ($amount !== 0) {
            $ok = $this->m_user->adjust_coin($id, $amount, 'admin_adjust', 'admin', $this->auth->id(),
                $this->input->post('note', true));
            $this->log_action('adjust_coin:' . $amount, 'users', $id);
            set_flash($ok ? 'success' : 'danger', $ok ? 'Đã cập nhật số dư xu.' : 'Số dư không đủ để trừ.');
        }
        redirect('admin/users/view/' . $id);
    }

    /** Cấp VIP thủ công. */
    public function grant_vip($id)
    {
        $days = (int) $this->input->post('days');
        if ($days > 0) {
            $this->m_user->grant_vip($id, $days);
            $this->m_notification->push($id, 'system', 'Bạn được cấp VIP', 'Thời hạn ' . $days . ' ngày.');
            $this->log_action('grant_vip:' . $days, 'users', $id);
            set_flash('success', 'Đã cấp VIP ' . $days . ' ngày.');
        }
        redirect('admin/users/view/' . $id);
    }

    public function delete($id)
    {
        $this->m_user->soft_delete($id);
        $this->log_action('delete_user', 'users', $id);
        set_flash('success', 'Đã xoá thành viên.');
        redirect('admin/users');
    }
}
