<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts extends Admin_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_post', 'm_category', 'm_province'));
    }

    public function index($page = 1)
    {
        $filters = array_filter(array(
            'keyword'     => $this->input->get('q', true),
            'status'      => $this->input->get('status'),
            'category_id' => $this->input->get('category_id'),
            'province_id' => $this->input->get('province_id'),
        ));
        $page  = max(1, (int) $page);
        $total = $this->m_post->admin_count($filters);

        $this->render('admin/posts/index', array(
            'title'      => 'Quản lý tin đăng',
            'posts'      => $this->m_post->admin_list($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links('admin/posts', $page, $total, $this->per_page, $this->input->get()),
            'filters'    => $filters,
            'categories' => $this->m_category->all('post'),
            'provinces'  => $this->m_province->all(),
        ));
    }

    public function edit($id = null)
    {
        $post = $id ? $this->m_post->find($id) : null;
        if ($id && !$post) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $data = array(
                'category_id'    => $this->input->post('category_id') ?: null,
                'province_id'    => $this->input->post('province_id') ?: null,
                'title'          => $this->input->post('title', true),
                'nickname'       => $this->input->post('nickname', true),
                'district'       => $this->input->post('district', true),
                'content'        => $this->input->post('content'),
                'intro'          => $this->input->post('intro', true),
                'job'            => $this->input->post('job', true),
                'wish'           => $this->input->post('wish', true),
                'personality'    => $this->input->post('personality', true),
                'gender'         => $this->input->post('gender'),
                'seeking'        => $this->input->post('seeking'),
                'age'            => $this->input->post('age') ?: null,
                'height_cm'      => $this->input->post('height_cm') ?: null,
                'weight_kg'      => $this->input->post('weight_kg') ?: null,
                'marital_status' => $this->input->post('marital_status') ?: null,
                'purpose'        => $this->input->post('purpose'),
                'contact_type'   => $this->input->post('contact_type'),
                'contact_value'  => $this->input->post('contact_value', true),
                'contact_cost'   => (int) $this->input->post('contact_cost'),
                'is_verified'    => (int) (bool) $this->input->post('is_verified'),
                'is_featured'    => (int) (bool) $this->input->post('is_featured'),
                'status'         => $this->input->post('status'),
            );

            $cover = $this->upload_image('cover');
            if ($cover) {
                $data['cover'] = $cover;
            }

            if ($id) {
                $this->m_post->update_post($id, $data);
            } else {
                $data['user_id'] = $this->auth->id();
                $data['slug']    = unique_slug('posts', $data['title']);
                $data['published_at'] = $data['status'] === 'approved' ? date('Y-m-d H:i:s') : null;
                $data['expired_at']   = date('Y-m-d H:i:s', strtotime('+' . (int) setting('post_expire_days', 30) . ' days'));
                $this->db->insert('posts', $data);
                $id = $this->db->insert_id();
            }

            // ảnh phụ
            $gallery = $this->upload_gallery('images');
            if ($gallery) {
                $this->m_post->add_images($id, $gallery);
            }

            $this->log_action('save_post', 'posts', $id);
            set_flash('success', 'Đã lưu tin đăng.');
            redirect('admin/posts/edit/' . $id);
        }

        $this->render('admin/posts/edit', array(
            'title'      => $id ? 'Sửa tin đăng' : 'Thêm tin đăng',
            'p'          => $post,
            'images'     => $id ? $this->m_post->images($id) : array(),
            'categories' => $this->m_category->all('post'),
            'provinces'  => $this->m_province->all(),
        ));
    }

    public function moderate($id, $status)
    {
        if (!in_array($status, array('approved', 'rejected', 'hidden', 'expired'), true)) {
            show_404();
        }
        $this->m_post->moderate($id, $status, $this->input->post('reason', true) ?: $this->input->get('reason'));
        $this->log_action('moderate:' . $status, 'posts', $id);
        set_flash('success', 'Đã cập nhật trạng thái tin.');
        redirect($this->input->server('HTTP_REFERER') ?: 'admin/posts');
    }

    /** Duyệt hàng loạt từ danh sách. */
    public function bulk()
    {
        $ids    = (array) $this->input->post('ids');
        $action = $this->input->post('bulk_action');
        foreach ($ids as $id) {
            if ($action === 'delete') {
                $this->m_post->soft_delete((int) $id);
            } elseif (in_array($action, array('approved', 'rejected', 'hidden'), true)) {
                $this->m_post->moderate((int) $id, $action);
            } elseif ($action === 'feature') {
                $this->m_post->set_featured((int) $id, true);
            }
        }
        $this->log_action('bulk:' . $action, 'posts', null);
        set_flash('success', 'Đã xử lý ' . count($ids) . ' tin.');
        redirect('admin/posts');
    }

    public function feature($id, $on = 1)
    {
        $this->m_post->set_featured($id, (bool) $on, (int) $this->input->get('days') ?: 7);
        set_flash('success', 'Đã cập nhật tin nổi bật.');
        redirect($this->input->server('HTTP_REFERER') ?: 'admin/posts');
    }

    public function delete($id)
    {
        $this->m_post->soft_delete($id);
        $this->log_action('delete_post', 'posts', $id);
        set_flash('success', 'Đã xoá tin đăng.');
        redirect('admin/posts');
    }

    public function delete_image($post_id, $image_id)
    {
        $this->m_post->delete_image($image_id, $post_id);
        redirect('admin/posts/edit/' . $post_id);
    }

    /* ------------------ Tải ảnh ------------------ */

    private function upload_config()
    {
        $dir = FCPATH . 'uploads/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        );
    }

    private function upload_image($field)
    {
        if (empty($_FILES[$field]['name'])) {
            return null;
        }
        $this->load->library('upload', $this->upload_config());
        if (!$this->upload->do_upload($field)) {
            set_flash('warning', strip_tags($this->upload->display_errors()));
            return null;
        }
        $data = $this->upload->data();
        return 'uploads/' . date('Y/m') . '/' . $data['file_name'];
    }

    private function upload_gallery($field)
    {
        if (empty($_FILES[$field]['name'][0])) {
            return array();
        }
        $this->load->library('upload', $this->upload_config());
        $paths = array();
        $files = $_FILES[$field];
        for ($i = 0; $i < count($files['name']); $i++) {
            $_FILES['single'] = array(
                'name' => $files['name'][$i], 'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            );
            if ($this->upload->do_upload('single')) {
                $data = $this->upload->data();
                $paths[] = 'uploads/' . date('Y/m') . '/' . $data['file_name'];
            }
        }
        return $paths;
    }
}
