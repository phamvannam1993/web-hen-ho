<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends Admin_Controller
{
    public function index()
    {
        $this->render('admin/pages/index', array(
            'title' => 'Trang tĩnh',
            'pages' => $this->db->order_by('id', 'DESC')->get('pages')->result_array(),
        ));
    }

    public function edit($id = null)
    {
        $page = $id ? $this->db->where('id', $id)->get('pages')->row_array() : null;
        if ($id && !$page) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $data = array(
                'title'     => $this->input->post('title', true),
                'slug'      => unique_slug('pages', $this->input->post('slug', true) ?: $this->input->post('title', true), $id),
                'content'   => $this->input->post('content'),
                'is_active' => (int) (bool) $this->input->post('is_active'),
            );

            if ($id) {
                $this->db->where('id', $id)->update('pages', $data);
            } else {
                $this->db->insert('pages', $data);
                $id = $this->db->insert_id();
            }

            $this->log_action('save_page', 'pages', $id);
            set_flash('success', 'Đã lưu trang.');
            redirect('admin/pages');
        }

        $this->render('admin/pages/edit', array(
            'title' => $id ? 'Sửa trang' : 'Thêm trang',
            'p'     => $page,
        ));
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete('pages');
        $this->log_action('delete_page', 'pages', $id);
        set_flash('success', 'Đã xoá trang.');
        redirect('admin/pages');
    }

    /** Nhận ảnh dán/tải lên từ trình soạn thảo CKEditor. */
    public function upload_image()
    {
        return $this->ckeditor_upload();
    }
}
