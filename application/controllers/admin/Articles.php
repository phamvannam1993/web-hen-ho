<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Articles extends Admin_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_article', 'm_category'));
    }

    public function index($page = 1)
    {
        $keyword = $this->input->get('q', true);
        $page    = max(1, (int) $page);
        $total   = $this->m_article->admin_count($keyword);

        $this->render('admin/articles/index', array(
            'title'      => 'Bài viết',
            'articles'   => $this->m_article->admin_list($keyword, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links('admin/articles', $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    public function edit($id = null)
    {
        $article = $id ? $this->m_article->find($id) : null;
        if ($id && !$article) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $data = array(
                'category_id' => $this->input->post('category_id') ?: null,
                'author_id'   => $this->auth->id(),
                'title'       => $this->input->post('title', true),
                'slug'        => $this->input->post('slug', true),
                'excerpt'     => $this->input->post('excerpt', true),
                'content'     => $this->input->post('content'),
                'seo_title'   => $this->input->post('seo_title', true),
                'seo_desc'    => $this->input->post('seo_desc', true),
                'status'      => $this->input->post('status'),
            );

            if (!empty($_FILES['thumbnail']['name'])) {
                $dir = FCPATH . 'uploads/' . date('Y/m');
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $this->load->library('upload', array(
                    'upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp',
                    'max_size' => 5120, 'encrypt_name' => true,
                ));
                if ($this->upload->do_upload('thumbnail')) {
                    $up = $this->upload->data();
                    $data['thumbnail'] = 'uploads/' . date('Y/m') . '/' . $up['file_name'];
                }
            }

            $new_id = $this->m_article->save($data, $id);
            $this->log_action('save_article', 'articles', $new_id);
            set_flash('success', 'Đã lưu bài viết.');
            redirect('admin/articles/edit/' . $new_id);
        }

        $this->render('admin/articles/edit', array(
            'title'      => $id ? 'Sửa bài viết' : 'Thêm bài viết',
            'a'          => $article,
            'categories' => $this->m_category->all('blog', false),
        ));
    }

    public function delete($id)
    {
        $this->m_article->remove($id);
        $this->log_action('delete_article', 'articles', $id);
        set_flash('success', 'Đã xoá bài viết.');
        redirect('admin/articles');
    }

    /** Nhận ảnh dán/tải lên từ trình soạn thảo CKEditor. */
    public function upload_image()
    {
        return $this->ckeditor_upload();
    }
}
