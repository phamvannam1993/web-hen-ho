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
        // Phải chạy trước mọi truy vấn có dính thời gian
        dong_bo_mui_gio_db($this);

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
        // Số thông báo chưa đọc cho chuông trên thanh đầu trang
        $this->data['unread_noti'] = 0;
        $this->data['streak']      = null;
        if ($this->auth->check()) {
            $this->load->model('m_notification');
            $this->data['unread_noti'] = (int) $this->m_notification->unread_count($this->auth->id());
        }

        // Ghi nhận hoạt động ở MỌI trang, không chỉ khu vực tài khoản. Trước đây
        // chỉ các trang bắt buộc đăng nhập mới gọi, nên người đang duyệt trang chủ
        // hay khám phá không được tính là đang online.
        // Hồ sơ chưa khai đủ thì khoá mọi tính năng, kể cả chat thời gian thực
        $this->data['ho_so_chua_xong'] = false;
        if ($this->auth->check()) {
            $this->auth->touch_active();

            // Chấm công chuỗi ngày hoạt động. Gọi mỗi trang nhưng chỉ tính một
            // lần mỗi ngày, nên không tốn thêm truy vấn ghi.
            $this->load->model('m_streak');
            $this->data['streak'] = $this->m_streak->cham_cong($this->auth->id());

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
        dong_bo_mui_gio_db($this);

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

/**
 * Lớp gốc cho API dành cho ứng dụng di động.
 *
 * Khác với web: không dùng phiên đăng nhập mà dùng token gửi trong header
 *   Authorization: Bearer <token>
 * Token chỉ lưu bản băm trong cơ sở dữ liệu, rò bảng cũng không đăng nhập
 * hộ ai được.
 */
class Api_Controller extends CI_Controller
{
    /** @var array|null người dùng đã xác thực */
    protected $me = null;

    public function __construct()
    {
        parent::__construct();
        dong_bo_mui_gio_db($this);
        $this->load->model('m_user');

        // Ứng dụng chạy ở tên miền khác nên phải mở CORS
        $this->output->set_header('Access-Control-Allow-Origin: *');
        $this->output->set_header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

        if ($this->input->method() === 'options') {
            $this->output->set_status_header(204)->_display();
            exit;
        }
    }

    /** Trả JSON và dừng. */
    protected function ok($data = array(), $code = 200)
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array_merge(array('ok' => true), $data),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function loi($message, $code = 400, $extra = array())
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array_merge(
                array('ok' => false, 'message' => $message), $extra),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** Đọc thân yêu cầu: nhận cả JSON lẫn form thường. */
    protected function body($key = null, $default = null)
    {
        static $data = null;
        if ($data === null) {
            $raw  = file_get_contents('php://input');
            $json = json_decode($raw, true);
            $data = is_array($json) ? $json : $_POST;
        }
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    /**
     * Bắt buộc phải có token hợp lệ. Gọi ở đầu mỗi hành động cần đăng nhập.
     * Trả về false và đã xuất lỗi nếu không hợp lệ.
     */
    protected function can_auth()
    {
        $token = $this->lay_token();
        if (!$token) {
            $this->loi('Thiếu token. Gửi header: Authorization: Bearer <token>', 401);
            return false;
        }

        $row = $this->db->where('token_hash', hash('sha256', $token))
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('api_tokens')->row_array();

        if (!$row) {
            $this->loi('Token không hợp lệ hoặc đã hết hạn.', 401);
            return false;
        }

        $u = $this->m_user->find($row['user_id']);
        if (!$u || $u['deleted_at'] || in_array($u['status'], array('banned', 'locked'), true)) {
            $this->loi('Tài khoản không dùng được.', 403);
            return false;
        }

        // Ghi lại lần dùng gần nhất, tối đa mỗi 5 phút một lần để đỡ ghi liên tục
        if (!$row['last_used_at'] || strtotime($row['last_used_at']) < time() - 300) {
            $this->db->where('id', $row['id'])
                ->update('api_tokens', array('last_used_at' => date('Y-m-d H:i:s')));
        }

        $this->me = $u;
        $this->db->where('id', $u['id'])->update('users', array(
            'last_active_at' => date('Y-m-d H:i:s'),
        ));

        // Chuỗi ngày vẫn được chấm công khi dùng qua ứng dụng
        $this->load->model('m_streak');
        $this->m_streak->cham_cong($u['id']);

        return true;
    }

    private function lay_token()
    {
        $h = $this->input->get_request_header('Authorization', true);
        if ($h && preg_match('/^Bearer\s+(.+)$/i', trim($h), $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /** Rút gọn một hồ sơ thành dạng trả về cho ứng dụng. */
    protected function ho_so(array $u, $day_du = false)
    {
        $ra = array(
            'id'       => (int) $u['id'],
            'name'     => display_name($u),
            'slug'     => $u['slug'] ?? null,
            'avatar'   => avatar_url($u['avatar'] ?? null, $u['gender'] ?? 'other'),
            'gender'   => $u['gender'] ?? null,
            'age'      => age_from($u['birthday'] ?? null),
            'province' => $u['province_name'] ?? null,
            'online'   => (bool) is_online($u['last_active_at'] ?? null),
        );

        if ($day_du) {
            $ra += array(
                'job'       => $u['job'] ?? null,
                'height_cm' => isset($u['height_cm']) ? (int) $u['height_cm'] : null,
                'bio'       => $u['bio'] ?? null,
                'interests' => !empty($u['interest_names']) ? explode('|', $u['interest_names']) : array(),
            );
        }
        return $ra;
    }
}
