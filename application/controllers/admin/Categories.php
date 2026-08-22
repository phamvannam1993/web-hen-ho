<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Categories extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_category');
    }

    public function index()
    {
        $type = $this->input->get('type') ?: 'post';
        $this->render('admin/categories/index', array(
            'title'      => 'Quản lý danh mục',
            'type'       => $type,
            'categories' => $this->m_category->all($type, false),
        ));
    }

    public function edit($id = null)
    {
        $cat = $id ? $this->m_category->find($id) : null;
        if ($id && !$cat) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $new_id = $this->m_category->save(array(
                'parent_id'   => $this->input->post('parent_id') ?: null,
                'type'        => $this->input->post('type'),
                'name'        => $this->input->post('name', true),
                'slug'        => $this->input->post('slug', true),
                'description' => $this->input->post('description', true),
                'seo_title'   => $this->input->post('seo_title', true),
                'seo_desc'    => $this->input->post('seo_desc', true),
                'sort'        => (int) $this->input->post('sort'),
                'is_active'   => (int) (bool) $this->input->post('is_active'),
            ), $id);

            $this->log_action('save_category', 'categories', $new_id);
            set_flash('success', 'Đã lưu danh mục.');
            redirect('admin/categories?type=' . $this->input->post('type'));
        }

        $this->render('admin/categories/edit', array(
            'title'  => $id ? 'Sửa danh mục' : 'Thêm danh mục',
            'c'      => $cat,
            'parents' => $this->m_category->all(null, false),
        ));
    }

    public function delete($id)
    {
        $this->m_category->remove($id);
        $this->log_action('delete_category', 'categories', $id);
        set_flash('success', 'Đã xoá danh mục.');
        redirect('admin/categories');
    }
}
