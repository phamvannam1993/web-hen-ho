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
        $this->data['title']      = $this->data['settings']['site_name'] ?? 'Saigon Cupid';
        $this->data['meta_desc']  = $this->data['settings']['site_desc'] ?? '';
        $this->data['user']       = $this->auth->user();
        // Thông tin kết nối WebSocket cho khung chat thời gian thực
        $this->data['ws_url']     = $this->realtime->enabled() ? $this->realtime->url() : '';
        $this->data['ws_token']   = $this->auth->check() && $this->realtime->enabled()
            ? $this->realtime->token($this->auth->id())
            : '';
        $this->data['categories'] = $this->m_category->tree('post');
        $this->data['provinces']  = $this->m_province->all();

        // Ghi nhận hoạt động ở MỌI trang, không chỉ khu vực tài khoản. Trước đây
        // chỉ các trang bắt buộc đăng nhập mới gọi, nên người đang duyệt trang chủ
        // hay khám phá không được tính là đang online.
        // Hồ sơ chưa khai đủ thì khoá mọi tính năng, kể cả chat thời gian thực
        $this->data['ho_so_chua_xong'] = false;
        if ($this->auth->check()) {
            $this->auth->touch_active();

            $me = $this->auth->user();
            if (!in_array($me['role'], array('admin', 'moderator'), true)) {
                $this->load->model('m_user');
                $this->data['ho_so_chua_xong'] = (bool) $this->m_user->thieu_thong_tin($me['id']);
            }
            if ($this->data['ho_so_chua_xong']) {
                // Không cấp mã WebSocket thì khung chat không kết nối được,
                // chặn tận gốc thay vì chỉ giấu giao diện
                $this->data['ws_token'] = '';
                $this->data['ws_url']   = '';
            }

            $this->chan_khi_ho_so_chua_xong();
        }
    }

    /**
     * Người mới đăng ký phải khai xong hồ sơ mới được đi tiếp — cách các trang
     * hẹn hò lớn vẫn làm, vì hồ sơ trống thì không ghép đôi được cho ai.
     *
     * Một số đường dẫn phải chừa ra để không rơi vào vòng lặp chuyển hướng:
     * chính trang sửa hồ sơ, đăng xuất, các lời gọi ajax và tệp cho máy tìm kiếm.
     */
    protected function chan_khi_ho_so_chua_xong()
    {
        $me = $this->auth->user();

        // Ban quản trị không bị chặn, họ không dùng hồ sơ để ghép đôi
        if (in_array($me['role'], array('admin', 'moderator'), true)) {
            return;
        }

        $uri  = uri_string();
        $chua = array(
            'tai-khoan/ho-so', 'dang-xuat', 'dang-nhap', 'dang-ky',
            'robots.txt', 'sitemap',
        );
        foreach ($chua as $bo) {
            if ($uri === $bo || strpos($uri, $bo) === 0) {
                return;
            }
        }
        if (strpos($uri, 'ajax') === 0 || $this->input->is_ajax_request()) {
            return;
        }

        if (empty($this->data['ho_so_chua_xong'])) {
            return;
        }
        $thieu = $this->m_user->thieu_thong_tin($me['id']);

        set_flash('warning', 'Bạn cần hoàn thiện hồ sơ trước khi dùng tiếp: '
            . implode(', ', array_slice($thieu, 0, 4))
            . (count($thieu) > 4 ? ' và ' . (count($thieu) - 4) . ' mục nữa.' : '.'));
        redirect('tai-khoan/ho-so');
    }

    /** Render layout frontend. */
    /**
     * Tính năng đăng tin hẹn hò đang tạm tắt (Cấu hình -> Kiểm duyệt).
     * Khi tắt, mọi đường dẫn liên quan đưa người dùng về trang khám phá.
     */
    protected function posts_enabled()
    {
        return !empty($this->data['settings']['enable_posts']);
    }

    protected function require_posts_enabled()
    {
        if (!$this->posts_enabled()) {
            set_flash('warning', 'Tính năng đăng tin đang tạm ngưng. Bạn hãy kết nối qua hồ sơ thành viên.');
            redirect('swipe-match');
        }
    }

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
