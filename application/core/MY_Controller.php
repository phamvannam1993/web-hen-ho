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
        //
        // Hồ sơ chưa đủ KHÔNG còn khoá người dùng ở trang Hồ sơ nữa (hoàn thiện dần):
        // dùng web bình thường, chỉ chưa hiện ra danh sách công khai, kèm banner nhắc.
        // Chưa xác thực email thì chỉ khoá thả tim và nhắn tin (Userauth::da_xac_thuc).
        $this->data['chua_xac_thuc'] = false;
        $this->data['ho_so_an']      = array();   // mục còn thiếu để hồ sơ được hiện công khai
        $this->data['ho_so_pt']      = 100;       // % hoàn thiện, cho thanh tiến độ trên banner
        if ($this->auth->check()) {
            $this->auth->touch_active();

            // Chấm công chuỗi ngày hoạt động. Gọi mỗi trang nhưng chỉ tính một
            // lần mỗi ngày, nên không tốn thêm truy vấn ghi.
            $this->load->model('m_streak');
            $this->data['streak'] = $this->m_streak->cham_cong($this->auth->id());

            $me = $this->auth->user();
            if (!in_array($me['role'], array('super_admin', 'admin', 'moderator'), true)) {
                $this->load->model('m_user');
                $this->data['chua_xac_thuc'] = !$this->auth->da_xac_thuc();
                $this->data['ho_so_an']      = $this->m_user->thieu_thong_tin($me['id']);
                $this->data['ho_so_pt']      = tk_ho_so_day_du($me)['phan_tram'];
            }
            if ($this->data['chua_xac_thuc']) {
                // Không cấp mã WebSocket thì khung chat không gửi được,
                // chặn tận gốc thay vì chỉ giấu giao diện
                $this->data['ws_token'] = '';
                $this->data['ws_url']   = '';
            }
        }
    }

    /**
     * Lời nhắc thêm ảnh ngay lúc vừa thả tim mà hồ sơ chưa có ảnh — nhắc đúng
     * lúc người dùng thấy lợi ích, thay vì chặn từ đầu. Mỗi phiên nhắc một lần.
     */
    protected function nhac_them_anh()
    {
        $me = $this->auth->user();
        if (!empty($me['avatar']) || $this->session->userdata('da_nhac_anh')) {
            return null;
        }
        $this->session->set_userdata('da_nhac_anh', 1);
        return array(
            'title'   => 'Thêm ảnh để người ấy thấy bạn',
            'message' => 'Đã gửi lượt thích! Nhưng hồ sơ chưa có ảnh nên người ấy khó thích lại — '
                       . 'hồ sơ có ảnh nhận nhiều lượt thích lại hơn hẳn. Thêm một ảnh chỉ mất vài giây.',
            'url'     => site_url('tai-khoan/bat-dau'),
            'action'  => 'Thêm ảnh',
        );
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

    /** Load member lists in batches while preserving filters and sorting. */
    protected function render_member_list($view, $page, $data)
    {
        $query = (array) $this->input->get();
        unset($query['member_feed']);
        $next = $page * 10 < $data['total'] && !empty($data['members'])
            ? site_url($data['base_url'] . '/trang/' . ($page + 1))
                . ($query ? '?' . http_build_query($query) : '')
            : null;
        if ($this->input->get('member_feed') === '1') {
            $html = '';
            $card = $view === 'confide/index' ? 'confide/_card' : 'members/_card';
            foreach ($data['members'] as $member) {
                $html .= $this->load->view($card, array_merge($this->data, array(
                    'm' => $member, 'dating_card' => $view === 'dating/index',
                )), true);
            }
            $this->output->set_header('Cache-Control: private, no-store');
            return $this->json(array('html' => $html, 'next' => $next));
        }
        $data['pagination'] = $next
            ? '<div data-member-feed><a class="btn-nav-ghost" data-feed-next href="' . e($next)
                . '">Xem thêm</a><p data-feed-status role="status" aria-live="polite"></p></div>'
            : '';
        return $this->render($view, $data);
    }

    protected function render($view, $data = array())
    {
        $data = array_merge($this->data, $data);
        // Giao diện phụ thuộc phiên đăng nhập, kể cả khi đang xem hồ sơ công khai.
        // Không dùng lại HTML dành cho khách từ cache sau khi đăng nhập.
        $this->output->set_header('Cache-Control: private, no-store, no-cache, must-revalidate')
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: 0');
        $data['user'] = $this->auth->user();
        $data['account_badge_total'] = 0;
        $data['account_menu_counts'] = array();
        if ($this->auth->check()) {
            $this->load->model('m_interest_badge');
            $counts = $this->m_interest_badge->menu_counts($this->auth->id());
            $data['account_menu_counts'] = $counts;
            if (isset($data['tk'])) $data['tk'] = array_merge($data['tk'], $counts);
            $data['unread_noti'] = $counts['noti'];
            // Incoming likes and profile views are included in interest already.
            $data['account_badge_total'] = $counts['total'];
        }
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
        $this->load->model(array('m_user', 'm_setting'));
        // Nạp sẵn cấu hình như MY_Controller: M_user::only_online() đọc cấu hình
        // giữa lúc đang dựng câu truy vấn tìm kiếm — lần đọc đầu tiên mà chạm DB
        // ở đó sẽ xoá mất phần FROM đang dựng dở.
        $this->m_setting->all();

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
        return $this->xac_thuc(true);
    }

    /**
     * Đăng nhập không bắt buộc — dùng cho màn hình khách cũng xem được
     * (khám phá, danh sách hẹn hò, trang cá nhân). Không gửi token thì là
     * khách; gửi token hỏng thì vẫn báo 401 để ứng dụng biết mà đăng nhập lại.
     */
    protected function thu_auth()
    {
        return $this->xac_thuc(false);
    }

    private function xac_thuc($bat_buoc)
    {
        $token = $this->lay_token();
        if (!$token) {
            if (!$bat_buoc) {
                return true;
            }
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
        // Các model hỏi $this->auth (is_vip, id, đã thích chưa…) như ở web
        $this->auth->dung_cho_api($u);
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

    /** VIP còn hạn — cột is_vip có thể còn 1 khi đã quá vip_expired_at. */
    protected function la_vip()
    {
        return $this->me && $this->auth->is_vip();
    }

    /**
     * Giống web: chưa xác thực email thì chưa thả tim / nhắn tin / bình luận.
     * Trả về false và đã xuất lỗi 403 kèm need_verify.
     */
    protected function can_xac_thuc($viec = 'thả tim và nhắn tin')
    {
        if ($this->auth->da_xac_thuc()) {
            return true;
        }
        $this->loi('Bạn cần xác thực email (bấm link trong thư) để ' . $viec . '.', 403,
            array('need_verify' => true));
        return false;
    }

    /** Rút gọn một hồ sơ thành dạng trả về cho ứng dụng. */
    private function profile_detail_allowed(array $u)
    {
        $this->load->model('m_profile_access');
        $owner = $this->db->where('id',$u['id'])->get('users')->row_array();
        if (!$owner) return false;
        try { $this->m_profile_access->allowed_target($owner,$this->me['id'] ?? 0); }
        catch (RuntimeException $e) { return false; }
        return $this->m_profile_access->can_detail($owner,$this->me['id'] ?? 0);
    }

    protected function ho_so(array $u, $day_du = false)
    {
        if ($day_du && !$this->profile_detail_allowed($u)) {
            return $this->ho_so($u,false) + array('job'=>$u['job'] ?? null,'bio'=>mb_substr(strip_tags((string)($u['bio'] ?? '')),0,180),'detail_locked'=>true);
        }
        $ra = array(
            'id'       => (int) $u['id'],
            'name'     => display_name($u),
            'slug'     => $u['slug'] ?? null,
            'avatar'   => avatar_url($u['avatar'] ?? null, $u['gender'] ?? 'other'),
            'gender'   => $u['gender'] ?? null,
            'age'      => age_from($u['birthday'] ?? null),
            'province' => $u['province_name'] ?? $this->ten_tinh($u['province_id'] ?? null),
            'online'   => (bool) is_online($u['last_active_at'] ?? null),
            'verified' => ($u['kyc_status'] ?? '') === 'verified',
            'is_vip'   => member_has_vip($u),
        );
        if (isset($u['liked'])) {
            $ra['liked'] = (bool) $u['liked'];
        }

        if ($day_du) {
            $ra += array(
                'job'       => $u['job'] ?? null,
                'height_cm' => !empty($u['height_cm']) ? (int) $u['height_cm'] : null,
                'bio'       => $u['bio'] ?? null,
                'interests' => !empty($u['interest_names']) ? explode('|', $u['interest_names']) : array(),
            );
        }
        return $ra;
    }

    /**
     * Tên tỉnh theo id — vài truy vấn của model (matches, my_likes…) chỉ lấy
     * u.* chứ không JOIN bảng tỉnh. Bảng tỉnh nhỏ nên nạp một lần rồi tra mảng.
     */
    protected function ten_tinh($id)
    {
        static $map = null;
        if (!$id) {
            return null;
        }
        if ($map === null) {
            $this->load->model('m_province');
            $map = array_column($this->m_province->all(), 'name', 'id');
        }
        return $map[$id] ?? null;
    }

    /**
     * Hồ sơ đầy đủ cho thẻ vuốt và trang cá nhân — cùng các trường mà
     * discover/_card.php và members/profile.php đang hiện trên web.
     */
    protected function ho_so_chi_tiet(array $u)
    {
        if (!$this->profile_detail_allowed($u)) return $this->ho_so($u,true);
        $ra = $this->ho_so($u, true) + array(
            'weight_kg'      => !empty($u['weight_kg']) ? (int) $u['weight_kg'] : null,
            'education'      => $u['education'] ?? null,
            'marital_status' => $u['marital_status'] ?? null,
            'has_children'   => isset($u['has_children']) ? (bool) $u['has_children'] : null,
            'smoking'        => $u['smoking'] ?? null,
            'drinking'       => $u['drinking'] ?? null,
            'purpose'        => $u['purpose'] ?? null,
            'confide_topic'  => $u['confide_topic'] ?? null,
            'zodiac'         => zodiac($u['birthday'] ?? null) ?: null,
            'joined'         => !empty($u['created_at']) ? date('m/Y', strtotime($u['created_at'])) : null,
            'last_active'    => !empty($u['last_active_at'])
                ? (is_online($u['last_active_at']) ? 'Đang online' : time_ago($u['last_active_at'])) : null,
        );
        if (isset($u['match_score'])) {
            $ra['match_score'] = min(100, (int) $u['match_score']);
        }
        if (array_key_exists('khoang_cach', $u) && $u['khoang_cach'] !== null) {
            $ra['distance_km'] = (float) $u['khoang_cach'];
        }
        if (!empty($u['photo_paths'])) {
            $ra['photos'] = array_map(function ($p) {
                return base_url(ltrim($p, '/'));
            }, array_slice(explode('|', $u['photo_paths']), 0, 6));
        }
        return $ra;
    }

    /** Lý do tải ảnh hỏng gần nhất, để báo đúng nguyên nhân. */
    protected $loi_anh = null;

    /**
     * Nhận một ảnh tải lên (multipart) ở trường $field, lưu vào uploads/$thu_muc/Y/m.
     * Trả về đường dẫn tương đối, hoặc null (không gửi ảnh, hoặc lỗi — xem $loi_anh).
     */
    protected function tai_anh($field, $thu_muc = '', $max_kb = 5120)
    {
        $this->loi_anh = null;
        if (empty($_FILES[$field]['name'])) {
            return null;
        }
        if (!empty($_FILES[$field]['error']) && $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $this->loi_anh = in_array($_FILES[$field]['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), true)
                ? 'Ảnh quá lớn.' : 'Không nhận được ảnh, vui lòng thử lại.';
            return null;
        }

        $tuong_doi = 'uploads/' . ($thu_muc !== '' ? $thu_muc . '/' : '') . date('Y/m');
        $dir = FCPATH . $tuong_doi;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif|heic|heif',
            'max_size'      => $max_kb,
            'encrypt_name'  => true,
        ));
        if (!$this->upload->do_upload($field)) {
            $this->loi_anh = trim(strip_tags($this->upload->display_errors('', ''))) ?: 'Không tải được ảnh.';
            return null;
        }
        return $tuong_doi . '/' . $this->upload->data('file_name');
    }

    /**
     * Điểm gốc để tính khoảng cách "Gần bạn nhất" — cùng luật với Dating::index:
     * vị trí thật (cấp trong 30 ngày) nếu có, không thì toạ độ tỉnh đang chọn
     * hoặc tỉnh trong hồ sơ.
     */
    protected function diem_goc($origin_province = 0)
    {
        $this->load->model('m_province');
        $me = $this->me;
        if ($this->m_user->location_ready() && $me && !empty($me['location_updated_at'])
            && strtotime($me['location_updated_at']) >= time() - 30 * 86400
            && is_numeric($me['lat']) && is_numeric($me['lng'])
            && abs($me['lat']) <= 90 && abs($me['lng']) <= 180) {
            return array('lat' => $me['lat'], 'lng' => $me['lng'], 'real' => true, 'province_id' => (int) ($me['province_id'] ?? 0));
        }
        $origin_province = (int) $origin_province ?: (int) ($me['province_id'] ?? 0);
        if (!$origin_province) {
            return null;
        }
        foreach ($this->m_province->all() as $province) {
            if ((int) $province['id'] === $origin_province) {
                $toa_do = $this->m_user->province_coordinates($province);
                return $toa_do ? array('lat' => $toa_do[0], 'lng' => $toa_do[1], 'real' => false, 'province_id' => $origin_province) : null;
            }
        }
        return null;
    }

    /**
     * Khoảng cách hiển thị trên thẻ hồ sơ, giống members/_card.php của web.
     * Trả về null khi không tính được.
     */
    protected function nhan_khoang_cach(array $u)
    {
        if (!array_key_exists('distance_km', $u)) {
            return null;
        }
        if (!empty($u['distance_same_province']) && empty($u['distance_real'])) {
            return 'Cùng tỉnh/thành';
        }
        if ($u['distance_km'] === null) {
            return null;
        }
        $km = (float) $u['distance_km'];
        return (empty($u['distance_real']) ? 'Ước tính · ' : '') . 'Cách bạn '
            . ($km < 1 ? 'dưới 1' : number_format(round($km), 0, ',', '.')) . ' km';
    }

    /**
     * Thả tim bị từ chối vì hồ sơ chưa đủ (M_interaction::like_profile_error):
     * trả 403 kèm need=profile và danh sách mục còn thiếu để ứng dụng mời
     * bổ sung. Trả về true nếu đã xuất lỗi.
     */
    protected function loi_tha_tim($kq)
    {
        if (!is_array($kq) || !isset($kq['ok']) || $kq['ok']) {
            return false;
        }
        $this->loi($kq['message'] ?? 'Không thả tim được.', ($kq['need'] ?? '') === 'profile' ? 403 : 409, array(
            'need'    => $kq['need'] ?? null,
            'missing' => $kq['missing'] ?? array(),
        ));
        return true;
    }

    /** Số trang từ ?page=, bắt đầu từ 1. */
    protected function trang()
    {
        return max(1, (int) $this->input->get('page'));
    }
}
