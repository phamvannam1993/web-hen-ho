<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Banners extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_banner');
    }

    public function index()
    {
        $this->render('admin/banners/index', array(
            'title'   => 'Banner quảng cáo',
            'banners' => $this->m_banner->all(),
        ));
    }

    public function edit($id = null)
    {
        $banner = $id ? $this->m_banner->find($id) : null;
        if ($id && !$banner) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $data = array(
                'position'  => $this->input->post('position', true),
                'title'     => $this->input->post('title', true),
                'link'      => $this->input->post('link', true),
                'sort'      => (int) $this->input->post('sort'),
                'is_active' => (int) (bool) $this->input->post('is_active'),
            );

            if (!empty($_FILES['image']['name'])) {
                $dir = FCPATH . 'uploads/banners';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $this->load->library('upload', array(
                    'upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp|gif',
                    'max_size' => 5120, 'encrypt_name' => true,
                ));
                if ($this->upload->do_upload('image')) {
                    $up = $this->upload->data();
                    $data['image'] = 'uploads/banners/' . $up['file_name'];
                }
            } elseif (!$id) {
                set_flash('danger', 'Vui lòng chọn ảnh banner.');
                redirect('admin/banners/edit');
            }

            $new_id = $this->m_banner->save($data, $id);
            $this->log_action('save_banner', 'banners', $new_id);
            set_flash('success', 'Đã lưu banner.');
            redirect('admin/banners');
        }

        $this->render('admin/banners/edit', array(
            'title' => $id ? 'Sửa banner' : 'Thêm banner',
            'b'     => $banner,
        ));
    }

    public function delete($id)
    {
        $this->m_banner->remove($id);
        $this->log_action('delete_banner', 'banners', $id);
        set_flash('success', 'Đã xoá banner.');
        redirect('admin/banners');
    }
}
