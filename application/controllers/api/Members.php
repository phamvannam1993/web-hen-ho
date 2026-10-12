<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Danh sách thành viên (Hẹn hò, Tâm sự, Tìm kiếm) và trang cá nhân —
 * bản API của controllers/Members.php, Dating.php, Confide.php.
 */
class Members extends Api_Controller
{
    private $per_page = 24;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_user_comment'));
    }

    /**
     * GET /api/members
     *   ?tab=nam|nu|gay|les   (như /hen-ho/nam, /tam-su/gay)
     *   &sort=active|new|verified|listened|vip
     *   &q= &gender= &province_id= &age_min= &age_max= &height_min= &height_max=
     *   &marital= &education= &smoking= &drinking= &online=1 &vip=1 &topic= &interests[]=
     *   &page=
     */
    public function index()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $g = function ($k) { return $this->input->get($k, true); };

        // Tab con của trang Hẹn hò / Tâm sự — cùng bộ lọc với Dating::tabs()
        $tabs = array(
            'nam' => array('gender' => 'male'),
            'nu'  => array('gender' => 'female'),
            'gay' => array('gender' => 'male', 'seeking' => 'male'),
            'les' => array('gender' => 'female', 'seeking' => 'female'),
        );
        $sort = $g('sort');
        if (!in_array($sort, array('nearby', 'active', 'new', 'verified', 'listened', 'vip'), true)) {
            $sort = 'active';
        }

        $filters = array_filter(array(
            'keyword'     => trim((string) $g('q')),
            'gender'      => in_array($g('gender'), array('male', 'female', 'other'), true) ? $g('gender') : null,
            'province_id' => (int) $g('province_id'),
            'age_min'     => (int) $g('age_min'),
            'age_max'     => (int) $g('age_max'),
            'height_min'  => (int) $g('height_min'),
            'height_max'  => (int) $g('height_max'),
            'marital'     => $g('marital'),
            'education'   => $g('education'),
            'smoking'     => $g('smoking'),
            'drinking'    => $g('drinking'),
            'online'      => $g('online'),
            'vip'         => $g('vip'),
            'topic'       => $g('topic'),
            'interests'   => is_array($this->input->get('interests')) ? $this->input->get('interests') : null,
        ));
        if (isset($tabs[$g('tab')])) {
            $filters = array_merge($filters, $tabs[$g('tab')]);
        }
        if ($g('has_children') !== null && $g('has_children') !== '') {
            $filters['has_children'] = (int) $g('has_children');
        }
        $filters['sort'] = $sort;

        // "Gần bạn nhất" và lọc bán kính của trang Hẹn hò mới
        $distance_max = (int) $g('distance_max');
        if (!in_array($distance_max, array(10, 25, 50, 100, 200, 500), true)) {
            $distance_max = 0;
        }
        if ($sort === 'nearby' || $distance_max) {
            $origin = $this->diem_goc((int) $g('origin_province') ?: (int) $g('province_id'));
            $filters['distance_origin'] = $origin;
            if ($origin && $distance_max) {
                $filters['distance_max'] = $distance_max;
            }
            if (!$origin && $sort === 'nearby') {
                $filters['sort'] = 'active';
            }
        }

        $page  = $this->trang();
        $total = $this->m_user->count_search($filters);

        $items = array();
        foreach ($this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page) as $u) {
            $items[] = $this->ho_so($u, true) + array(
                'like_count'     => (int) ($u['like_count'] ?? 0),
                'confide_topic'  => $u['confide_topic'] ?? null,
                'marital_status' => $u['marital_status'] ?? null,
                'weight_kg'      => !empty($u['weight_kg']) ? (int) $u['weight_kg'] : null,
                // Nhãn "Mới tham gia" trên thẻ hồ sơ của web
                'is_new'         => !empty($u['created_at']) && strtotime($u['created_at']) > strtotime('-7 days'),
                'distance_text'  => $this->nhan_khoang_cach($u),
            );
        }

        return $this->ok(array(
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $this->per_page,
            'has_more' => $page * $this->per_page < $total,
            // Gốc tính khoảng cách: vị trí thật hay ước tính theo tỉnh
            'distance_origin' => isset($filters['distance_origin']) && $filters['distance_origin']
                ? (!empty($filters['distance_origin']['real']) ? 'location' : 'province') : null,
        ));
    }

    /** Tìm theo id (số) hoặc slug. Trả null nếu không có / bị khoá. */
    private function tim($key)
    {
        $u = ctype_digit((string) $key) ? $this->m_user->find((int) $key) : $this->m_user->by_slug($key);
        return ($u && $u['status'] !== 'banned') ? $u : null;
    }

    /** GET /api/members/{id|slug} — trang cá nhân. */
    public function show($key = '')
    {
        if (!$this->thu_auth()) {
            return;
        }
        $m = $this->tim($key);
        if (!$m) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }
        $me_id = $this->me ? (int) $this->me['id'] : 0;
        if ($me_id && $this->m_interaction->is_blocked($me_id, $m['id'])) {
            return $this->loi('Bạn không thể xem trang cá nhân này.', 403, array('blocked' => true));
        }
        // Ghi lượt xem để gom thành thông báo "N người đã xem hồ sơ bạn"
        if ($me_id) {
            $this->m_interaction->record_view($me_id, $m['id']);
        }

        $pref = $this->db->where('user_id', $m['id'])->get('user_preferences')->row_array();
        $m['purpose'] = $pref['purpose'] ?? null;

        $so_thich = array_column($this->db->select('i.name')->from('user_interests ui')
            ->join('interests i', 'i.id = ui.interest_id')
            ->where('ui.user_id', $m['id'])->order_by('i.name')->get()->result_array(), 'name');
        $m['interest_names'] = implode('|', $so_thich);

        $anh = array();
        foreach ($this->db->where('user_id', $m['id'])->where('status', 'approved')
                     ->order_by('sort')->get('user_photos')->result_array() as $p) {
            $anh[] = array('id' => (int) $p['id'], 'url' => base_url(ltrim($p['path'], '/')));
        }

        $binh_luan = array();
        foreach ($this->m_user_comment->for_profile($m['id']) as $c) {
            $binh_luan[] = $this->binh_luan($c, $m['id']);
        }

        return $this->ok(array(
            'profile'    => $this->ho_so_chi_tiet($m) + array(
                'seeking'  => $pref['seeking_gender'] ?? null,
                'has_phone' => !empty($m['phone']),
                'cover'    => base_url(!empty($m['cover_image']) ? ltrim($m['cover_image'], '/') : 'assets/site/images/profile-cover.jpg'),
            ),
            'photos'     => $anh,
            'comments'   => $binh_luan,
            'like_count' => $this->m_interaction->count_likes('user', $m['id']),
            'liked'      => $me_id ? $this->m_interaction->has_liked($me_id, 'user', $m['id']) : false,
            // Người này đã thích mình: nút đổi thành "Thích lại" như web
            'liked_me'   => $me_id ? $this->m_interaction->has_liked($m['id'], 'user', $me_id) : false,
            'matched'    => $me_id ? $this->m_interaction->is_matched($me_id, $m['id']) : false,
            // Nhắn tin không cần ghép đôi nữa — chỉ vướng chặn hoặc "chỉ nhận tin từ VIP"
            'message_error' => $me_id && $me_id !== (int) $m['id']
                ? $this->m_interaction->message_permission($me_id, $m['id']) : null,
            'is_me'      => $me_id === (int) $m['id'],
            'unlock_cost' => $this->la_vip() ? 0 : (int) setting('unlock_cost', 20),
        ));
    }

    private function binh_luan(array $c, $chu_trang)
    {
        $me_id = $this->me ? (int) $this->me['id'] : 0;
        return array(
            'id'         => (int) $c['id'],
            'parent_id'  => $c['parent_id'] ? (int) $c['parent_id'] : null,
            'content'    => $c['content'],
            'image'      => $c['image'] ? base_url(ltrim($c['image'], '/')) : null,
            'time'       => time_ago($c['created_at']),
            'can_delete' => $me_id && ($me_id === (int) $c['user_id'] || $me_id === (int) $chu_trang),
            'user'       => array(
                'id'     => (int) $c['user_id'],
                'name'   => display_name($c),
                'slug'   => $c['user_slug'],
                'avatar' => avatar_url($c['avatar'], $c['gender']),
            ),
        );
    }

    /** POST /api/members/{id}/like — thích / bỏ thích (giống ajax/like). */
    public function like($id = 0)
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('thả tim')) {
            return;
        }
        $id = (int) $id;
        if ($id === (int) $this->me['id']) {
            return $this->loi('Không thể tự thích hồ sơ của mình.', 422);
        }
        $doi = $this->m_user->find($id);
        if (!$doi) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }
        $kq = $this->m_interaction->toggle_like($this->me['id'], 'user', $id);
        if ($this->loi_tha_tim($kq)) {
            return;
        }

        return $this->ok(array(
            'liked'   => (bool) $kq['liked'],
            'matched' => (bool) $kq['matched'],
            'count'   => (int) $kq['count'],
            'partner' => $kq['matched'] ? $this->ho_so($doi) : null,
            'nudge_photo' => $kq['liked'] && !$kq['matched'] && empty($this->me['avatar']),
            'message' => $kq['matched']
                ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
                : ($kq['liked'] ? 'Đã gửi lượt thích.' : 'Đã bỏ thích.'),
        ));
    }

    /**
     * POST /api/members/{id}/contact — mở số điện thoại.
     * Gộp hai bước "Lấy pass" + "Nhập pass" của web vào một lần gọi: trừ xu
     * (VIP miễn phí) rồi trả số luôn.
     */
    public function contact($id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $m = $this->tim($id);
        if (!$m) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }
        $this->load->model('m_access_code');
        $cost = $this->la_vip() ? 0 : (int) setting('unlock_cost', 20);
        $kq   = $this->m_access_code->issue('contact', $this->me['id'], null, $cost, 60);
        if (!$kq['ok']) {
            return $this->loi($kq['message'], 409, array('need_coins' => true));
        }
        $row = $this->m_access_code->verify($kq['code'], 'contact');
        if ($row) {
            $this->m_access_code->consume($row['id']);
        }
        return $this->ok(array(
            'contact' => $m['phone'] ?: 'Thành viên chưa cập nhật số điện thoại',
            'cost'    => $cost,
        ));
    }

    /** POST /api/members/{id}/comment {content, parent_id} + ảnh "image" (multipart) */
    public function comment($id = 0)
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('bình luận')) {
            return;
        }
        $m = $this->tim($id);
        if (!$m) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }
        $content = trim(strip_tags((string) $this->body('content')));
        $image   = $this->tai_anh('image', 'comments');
        if ($content === '' && !$image) {
            return $this->loi($this->loi_anh ?: 'Hãy nhập nội dung hoặc chọn ảnh để bình luận.', 422);
        }
        $cid = $this->m_user_comment->create($m['id'], $this->me['id'], $content,
            $this->body('parent_id'), $image);

        $row = $this->db->select('c.*, u.display_name, u.nickname, u.slug AS user_slug, u.avatar, u.gender')
            ->from('user_comments c')->join('users u', 'u.id = c.user_id')
            ->where('c.id', $cid)->get()->row_array();

        return $this->ok(array(
            'comment' => $row && $row['status'] === 'approved' ? $this->binh_luan($row, $m['id']) : null,
            'message' => $row && $row['status'] === 'approved'
                ? 'Đã gửi bình luận.' : 'Đã gửi bình luận, chờ ban quản trị duyệt.',
        ), 201);
    }

    /** POST /api/comments/{id}/delete — tác giả hoặc chủ trang được xoá. */
    public function delete_comment($id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->m_user_comment->delete_own((int) $id, $this->me['id']);
        return $this->ok();
    }

    /** POST /api/members/{id}/block — chặn: ẩn hai bên khỏi nhau, khoá nhắn tin. */
    public function block($id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $id = (int) $id;
        if ($id <= 0 || $id === (int) $this->me['id']) {
            return $this->loi('Yêu cầu không hợp lệ.', 422);
        }
        $this->m_interaction->block($this->me['id'], $id);
        return $this->ok(array('message' => 'Đã chặn người dùng này.'));
    }

    /** POST /api/members/{id}/unblock */
    public function unblock($id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->m_interaction->unblock($this->me['id'], (int) $id);
        return $this->ok(array('message' => 'Đã bỏ chặn.'));
    }

    /** POST /api/report {target_type: user|comment|message, target_id, reason, note} */
    public function report()
    {
        if (!$this->can_auth()) {
            return;
        }
        $type = $this->body('target_type');
        if (!in_array($type, array('user', 'post', 'comment', 'message'), true)) {
            return $this->loi('Đối tượng không hợp lệ.', 422);
        }
        $reason = $this->body('reason');
        if (!in_array($reason, array('lua_dao', 'noi_dung_xau', 'mao_danh', 'spam', 'khac'), true)) {
            $reason = 'khac';
        }
        $this->load->model('m_report');
        $this->m_report->create($this->me['id'], $type, (int) $this->body('target_id'),
            $reason, mb_substr(strip_tags((string) $this->body('note')), 0, 500) ?: null);

        return $this->ok(array('message' => 'Đã gửi báo cáo, ban quản trị sẽ xem xét sớm.'));
    }
}
