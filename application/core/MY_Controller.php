<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller gốc cho toàn site: nạp model dùng chung, dữ liệu layout, tiện ích render.
 */
class MY_Controller extends CI_Controller
{
    /** @var array dữ liệu truyền sang view */
    protected $data = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_setting', 'm_category', 'm_province'));
        $this->load->library('realtime');

        $this->data['settings']   = $this->m_setting->all();
        $this->data['title']      = $this->data['settings']['site_name'] ?? 'HenHo24';
        $this->data['meta_desc']  = $this->data['settings']['site_desc'] ?? '';
        $this->data['user']       = $this->auth->user();
        // Thông tin kết nối WebSocket cho khung chat thời gian thực
        $this->data['ws_url']     = $this->realtime->enabled() ? $this->realtime->url() : '';
        $this->data['ws_token']   = $this->auth->check() && $this->realtime->enabled()
            ? $this->realtime->token($this->auth->id())
            : '';
        $this->data['categories'] = $this->m_category->tree('post');
        $this->data['provinces']  = $this->m_province->all();
    }

    /** Render layout frontend. */
    protected function render($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['content_view'] = $view;
        $this->load->view('layouts/main', $data);
    }

    protected function json($payload, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}

/** Bắt buộc đăng nhập thành viên. */
class Member_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->auth->check()) {
            set_flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('dang-nhap?next=' . urlencode(uri_string()));
        }
        if ($this->auth->user()['status'] === 'banned') {
            $this->auth->logout();
            set_flash('danger', 'Tài khoản của bạn đã bị khoá.');
            redirect('dang-nhap');
        }
        $this->auth->touch_active();
    }
}

/** Khu vực quản trị. */
class Admin_Controller extends CI_Controller
{
    protected $data = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_setting'));
        if (!$this->auth->is_admin()) {
            redirect('admin/dang-nhap');
        }
        $this->data['admin']    = $this->auth->user();
        $this->data['settings'] = $this->m_setting->all();
        $this->data['title']    = 'Quản trị';
    }

    protected function render($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['content_view'] = $view;
        $this->load->view('admin/layouts/main', $data);
    }

    protected function json($payload, $code = 200)
    {
        $this->output->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Nhận ảnh tải lên từ CKEditor (filebrowserUploadMethod = 'form').
     * CKEditor gửi file ở trường "upload" và chờ JSON phản hồi theo đúng định dạng dưới đây.
     */
    public function ckeditor_upload()
    {
        $this->output->set_content_type('application/json', 'utf-8');

        $dir = FCPATH . 'uploads/editor/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ));

        if (!$this->upload->do_upload('upload')) {
            return $this->output->set_output(json_encode(array(
                'uploaded' => 0,
                'error'    => array('message' => strip_tags($this->upload->display_errors('', ''))),
            ), JSON_UNESCAPED_UNICODE));
        }

        $data = $this->upload->data();
        return $this->output->set_output(json_encode(array(
            'uploaded' => 1,
            'fileName' => $data['file_name'],
            'url'      => base_url('uploads/editor/' . date('Y/m') . '/' . $data['file_name']),
        ), JSON_UNESCAPED_UNICODE));
    }

    /** Ghi nhật ký thao tác quản trị. */
    protected function log_action($action, $target = null, $target_id = null)
    {
        $this->db->insert('admin_logs', array(
            'admin_id'  => $this->auth->id(),
            'action'    => $action,
            'target'    => $target,
            'target_id' => $target_id,
            'ip'        => $this->input->ip_address(),
        ));
    }
}
