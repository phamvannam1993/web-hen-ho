<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Packages extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_billing');
    }

    public function index()
    {
        $this->render('admin/packages/index', array(
            'title'    => 'Gói dịch vụ',
            'packages' => $this->m_billing->packages(null, false),
        ));
    }

    public function edit($id = null)
    {
        $pkg = $id ? $this->m_billing->find_package($id) : null;
        if ($id && !$pkg) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $new_id = $this->m_billing->save_package(array(
                'type'          => $this->input->post('type'),
                'name'          => $this->input->post('name', true),
                'price'         => (int) $this->input->post('price'),
                'duration_days' => $this->input->post('duration_days') ?: null,
                'coin_amount'   => $this->input->post('coin_amount') ?: null,
                'bonus_coin'    => (int) $this->input->post('bonus_coin'),
                'description'   => $this->input->post('description', true),
                'is_active'     => (int) (bool) $this->input->post('is_active'),
                'sort'          => (int) $this->input->post('sort'),
            ), $id);

            $this->log_action('save_package', 'packages', $new_id);
            set_flash('success', 'Đã lưu gói dịch vụ.');
            redirect('admin/packages');
        }

        $this->render('admin/packages/edit', array(
            'title' => $id ? 'Sửa gói dịch vụ' : 'Thêm gói dịch vụ',
            'p'     => $pkg,
        ));
    }

    public function delete($id)
    {
        $this->m_billing->remove_package($id);
        $this->log_action('delete_package', 'packages', $id);
        set_flash('success', 'Đã xoá gói dịch vụ.');
        redirect('admin/packages');
    }
}
