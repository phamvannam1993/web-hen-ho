<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Provinces extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_province');
    }

    public function index()
    {
        $keyword = $this->input->get('q', true);
        $region  = $this->input->get('region');

        $this->render('admin/provinces/index', array(
            'title'     => 'Quản lý tỉnh/thành',
            'provinces' => $this->m_province->admin_list($keyword, $region),
        ));
    }

    public function edit($id = null)
    {
        $province = $id ? $this->m_province->find($id) : null;
        if ($id && !$province) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', 'Tên tỉnh/thành', 'required|max_length[100]');

            if ($this->form_validation->run()) {
                $new_id = $this->m_province->save(array(
                    'name'   => $this->input->post('name', true),
                    'slug'   => $this->input->post('slug', true),
                    'region' => $this->input->post('region') ?: null,
                    'sort'   => (int) $this->input->post('sort'),
                ), $id);

                $this->log_action('save_province', 'provinces', $new_id);
                set_flash('success', 'Đã lưu tỉnh/thành.');
                redirect('admin/provinces');
            }
        }

        $this->render('admin/provinces/edit', array(
            'title'  => $id ? 'Sửa tỉnh/thành' : 'Thêm tỉnh/thành',
            'p'      => $province,
            'members' => $id ? $this->m_province->member_count($id) : 0,
        ));
    }

    public function delete($id)
    {
        $count = $this->m_province->member_count($id);
        $this->m_province->remove($id);
        $this->log_action('delete_province', 'provinces', $id);

        set_flash('success', $count > 0
            ? 'Đã xoá tỉnh/thành. ' . $count . ' thành viên chuyển sang trạng thái chưa rõ khu vực.'
            : 'Đã xoá tỉnh/thành.');
        redirect('admin/provinces');
    }
}
