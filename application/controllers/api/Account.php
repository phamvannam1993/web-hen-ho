<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Khu Tài khoản — bản API của controllers/Account.php. Mọi hành động cần đăng nhập. */
class Account extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_notification', 'm_billing', 'm_daily', 'm_job', 'm_province', 'm_interest_badge'));
    }

    /** GET /api/account — trang Tổng quan. */
    public function index()
    {
        if (!$this->can_auth()) {
            return;
        }
        $id = $this->me['id'];
        $this->load->model('m_streak');
        $s = $this->m_streak->get($id);

        $hoat_dong = array();
        foreach ($this->m_notification->for_user($id, 5) as $n) {
            $hoat_dong[] = $this->thong_bao($n);
        }
        $goi_y = array();
        // Luân phiên mỗi ngày lúc 8h như trang Tổng quan web
        foreach ($this->m_user->account_suggestions($this->me, 3) as $u) {
            $goi_y[] = $this->ho_so($u, true) + array('match_score' => min(100, (int) $u['match_score']));
        }
        $d = $this->m_daily->today($id);

        return $this->ok(array(
            'me'        => $this->ho_so($this->me, true),
            'coins'     => (int) $this->me['coin_balance'],
            'is_vip'    => $this->la_vip(),
            'vip_until' => $this->la_vip() && $this->me['vip_expired_at']
                ? date('d/m/Y', strtotime($this->me['vip_expired_at'])) : null,
            'streak'    => (int) $s['current_streak'],
            'completion' => tk_ho_so_day_du($this->me)['phan_tram'],
            'missing'   => $this->m_user->thieu_thong_tin($id),
            'counts'    => array(
                'liked_me'      => $this->m_interaction->liked_me_count($id),
                'viewers'       => $this->m_interaction->viewer_count($id),
                'matches'       => count($this->m_interaction->matches($id, 500)),
                'messages'      => $this->m_interaction->unread_count($id),
                'notifications' => $this->m_notification->unread_count($id),
            ),
            'activities'  => $hoat_dong,
            'daily'       => $d ? array('id' => (int) $d['id'], 'status' => $d['status'],
                                        'profile' => $this->ho_so($d, true)) : null,
            'suggestions' => $goi_y,
        ));
    }

    /* ====================== Hồ sơ ====================== */

    /** GET /api/account/profile — dữ liệu cho form sửa hồ sơ. */
    public function profile()
    {
        if (!$this->can_auth()) {
            return;
        }
        if ($this->input->method() === 'post') {
            return $this->luu_ho_so();
        }
        return $this->ok($this->du_lieu_ho_so());
    }

    private function du_lieu_ho_so()
    {
        $id   = $this->me['id'];
        $u    = $this->m_user->find($id);
        $pref = $this->db->where('user_id', $id)->get('user_preferences')->row_array();

        return array(
            'profile' => array(
                'display_name'   => $u['display_name'],
                'nickname'       => $u['nickname'],
                'email'          => $u['email'],
                'phone'          => $u['phone'],
                'gender'         => $u['gender'],
                'birthday'       => $u['birthday'],
                'province_id'    => $u['province_id'] ? (int) $u['province_id'] : null,
                'bio'            => $u['bio'],
                'job'            => $u['job'],
                'height_cm'      => $u['height_cm'] ? (int) $u['height_cm'] : null,
                'weight_kg'      => $u['weight_kg'] ? (int) $u['weight_kg'] : null,
                'education'      => $u['education'],
                'marital_status' => $u['marital_status'],
                'has_children'   => (int) $u['has_children'],
                'smoking'        => $u['smoking'],
                'drinking'       => $u['drinking'],
                'confide_topic'  => $u['confide_topic'] ?? null,
                'avatar'         => avatar_url($u['avatar'], $u['gender']),
                'has_avatar'     => !empty($u['avatar']),
                'cover'          => base_url(!empty($u['cover_image']) ? ltrim($u['cover_image'], '/') : 'assets/site/images/profile-cover.jpg'),
                'has_cover'      => !empty($u['cover_image']),
                'email_verified' => !empty($u['email_verified_at']),
                'verify_required' => setting('otp_register', '1') === '1',
            ),
            'preferences' => array(
                'seeking_gender' => $pref['seeking_gender'] ?? null,
                'age_min'        => (int) ($pref['age_min'] ?? 18),
                'age_max'        => (int) ($pref['age_max'] ?? 60),
                'purpose'        => $pref['purpose'] ?? null,
                'allow_message'  => $pref['allow_message'] ?? 'all',
                'show_online'    => (bool) ($pref['show_online'] ?? 1),
            ),
            'interests'  => array_map('intval', array_column(
                $this->db->select('interest_id')->where('user_id', $id)
                    ->get('user_interests')->result_array(), 'interest_id')),
            'missing'    => $this->m_user->thieu_thong_tin($id),
            'completion' => tk_ho_so_day_du($u)['phan_tram'],
        );
    }

    /**
     * POST /api/account/profile — lưu toàn bộ form hồ sơ (JSON).
     * Bắt buộc như web: display_name, gender, birthday, seeking_gender, purpose.
     */
    private function luu_ho_so()
    {
        $b  = $this->body();
        $me = $this->me;
        $v  = function ($k) use ($b) {
            $x = $b[$k] ?? null;
            return is_string($x) ? trim($x) : $x;
        };
        $mot_trong = function ($k, array $hop_le) use ($v) {
            return in_array($v($k), $hop_le, true) ? $v($k) : null;
        };

        $loi = array();
        foreach (array('display_name' => 'Tên hiển thị', 'gender' => 'Giới tính', 'birthday' => 'Ngày sinh',
                       'seeking_gender' => 'Muốn tìm', 'purpose' => 'Mục đích') as $k => $ten) {
            if ($v($k) === null || $v($k) === '') {
                $loi[$k] = 'Trường ' . $ten . ' là bắt buộc.';
            }
        }
        $phone = '';
        if ((string) $v('phone') !== '') {
            $phone = chuan_hoa_dien_thoai($v('phone'));
            if ($phone === '') {
                $loi['phone'] = 'Số điện thoại không đúng. Nhập số di động Việt Nam 10 chữ số, VD: 0912345678.';
            } elseif ($this->m_user->phone_exists($phone, $me['id'])) {
                $loi['phone'] = 'Số điện thoại này đã có người dùng.';
            }
        }
        if (mb_strlen((string) $v('bio')) > 500) {
            $loi['bio'] = 'Giới thiệu tối đa 500 ký tự.';
        }
        // Đổi email ngay trong hồ sơ (như web): không được xoá trắng, không trùng ai
        $doi_email = false;
        if (array_key_exists('email', $b)) {
            $email = trim((string) $b['email']);
            if ($email === '' && !empty($me['email'])) {
                $loi['email'] = 'Vui lòng nhập email mới, không được xoá email hiện tại.';
            } elseif ($email !== '' && (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $loi['email'] = 'Email không hợp lệ.';
            } elseif ($email !== '' && $this->m_user->email_exists($email, $me['id'])) {
                $loi['email'] = 'Email này đã được dùng cho tài khoản khác. Email hiện tại của bạn được giữ nguyên.';
            }
            $doi_email = $email !== (string) ($me['email'] ?? '');
        }
        if ($loi) {
            return $this->loi(reset($loi), 422, array('errors' => $loi));
        }

        $tinh = (int) $v('province_id');
        $data = array(
            'display_name'   => mb_substr(strip_tags($v('display_name')), 0, 100),
            'phone'          => $phone ?: null,
            'nickname'       => mb_substr(strip_tags((string) $v('nickname')), 0, 60) ?: null,
            'gender'         => $mot_trong('gender', array('male', 'female', 'other')) ?: $me['gender'],
            'birthday'       => $v('birthday'),
            'province_id'    => $tinh > 0 && $this->m_province->find($tinh) ? $tinh : null,
            'bio'            => strip_tags((string) $v('bio')),
            // Nghề nghiệp chọn trong danh mục, không nhận chữ tự gõ
            'job'            => $this->m_job->valid_name($v('job'), $me['job']),
            'height_cm'      => (int) $v('height_cm') ?: null,
            'weight_kg'      => (int) $v('weight_kg') ?: null,
            'education'      => $mot_trong('education', array('thpt', 'trung_cap', 'cao_dang', 'dai_hoc', 'sau_dai_hoc')),
            'marital_status' => $mot_trong('marital_status', array('doc_than', 'ly_hon', 'goa', 'phuc_tap')),
            'smoking'        => $mot_trong('smoking', array('khong', 'thinh_thoang', 'thuong_xuyen')),
            'drinking'       => $mot_trong('drinking', array('khong', 'thinh_thoang', 'thuong_xuyen')),
            'confide_topic'  => $mot_trong('confide_topic',
                array('lang_nghe', 'tro_chuyen', 'cong_viec', 'gia_dinh', 'tinh_cam', 'dem_khuya')),
        );
        if ($v('has_children') !== null && $v('has_children') !== '') {
            $data['has_children'] = (int) (bool) $v('has_children');
        }
        if ($doi_email) {
            // update_profile() tự gỡ trạng thái đã xác thực khi email đổi
            $data['email'] = trim((string) $b['email']) ?: null;
        }
        $this->m_user->update_profile($me['id'], $data);

        // Tiêu chí ghép đôi
        $pref = array(
            'seeking_gender' => $mot_trong('seeking_gender', array('male', 'female', 'all')) ?: 'all',
            'age_min'        => max(18, (int) $v('age_min') ?: 18),
            'age_max'        => min(99, (int) $v('age_max') ?: 60),
            'purpose'        => $mot_trong('purpose', array('ket_ban', 'hen_ho', 'nghiem_tuc', 'ket_hon')) ?: 'hen_ho',
            'allow_message'  => $mot_trong('allow_message', array('all', 'vip', 'matched')) ?: 'all',
            'show_online'    => (int) (bool) ($v('show_online') ?? true),
        );
        if ($this->db->where('user_id', $me['id'])->count_all_results('user_preferences') > 0) {
            $this->db->where('user_id', $me['id'])->update('user_preferences', $pref);
        } else {
            $pref['user_id'] = $me['id'];
            $this->db->insert('user_preferences', $pref);
        }

        // Sở thích: ghi lại toàn bộ lựa chọn hiện tại
        if (isset($b['interests']) && is_array($b['interests'])) {
            $this->db->where('user_id', $me['id'])->delete('user_interests');
            foreach ($b['interests'] as $iid) {
                $iid = (int) $iid;
                if ($iid > 0) {
                    $this->db->replace('user_interests', array('user_id' => $me['id'], 'interest_id' => $iid));
                }
            }
        }
        // Tính lại sau khi đã lưu sở thích (sở thích là một mục của điểm)
        $this->m_user->recalc_profile_score($me['id']);

        $this->me = $this->m_user->find($me['id']);
        $this->auth->dung_cho_api($this->me);
        $message = 'Đã cập nhật hồ sơ.';
        if ($doi_email) {
            $message = 'Đã cập nhật hồ sơ và email. Từ nay hãy dùng email mới để đăng nhập.'
                . (setting('otp_register', '1') === '1' ? ' Bấm "Gửi lại email xác thực" để xác thực email mới.' : '');
        }
        return $this->ok(array_merge(array('message' => $message, 'email_changed' => $doi_email), $this->du_lieu_ho_so()));
    }

    /** POST /api/account/avatar — multipart, trường "avatar". */
    public function avatar()
    {
        if (!$this->can_auth()) {
            return;
        }
        $path = $this->tai_anh('avatar');
        if (!$path) {
            return $this->loi($this->loi_anh ?: 'Hãy chọn một ảnh.', 422);
        }
        $this->m_user->update_profile($this->me['id'], array('avatar' => $path));
        return $this->ok(array('avatar' => base_url($path), 'message' => 'Đã cập nhật ảnh đại diện.'));
    }

    /** POST /api/account/cover — ảnh bìa trang cá nhân, multipart trường "cover". */
    public function cover()
    {
        if (!$this->can_auth()) {
            return;
        }
        if (!$this->db->field_exists('cover_image', 'users')) {
            return $this->loi('Hệ thống chưa cập nhật dữ liệu ảnh bìa (chạy database/migrate_profile_cover.php).', 503);
        }
        $path = $this->tai_anh('cover');
        if (!$path) {
            return $this->loi($this->loi_anh ?: 'Hãy chọn một ảnh.', 422);
        }
        $this->m_user->update_profile($this->me['id'], array('cover_image' => $path));
        return $this->ok(array('cover' => base_url($path), 'message' => 'Đã cập nhật ảnh bìa.'));
    }

    /**
     * POST /api/account/location {lat, lng} — vị trí hiện tại cho "Gần bạn nhất"
     * ở trang Hẹn hò (giống hen-ho/vi-tri của web). Dùng được 30 ngày.
     */
    public function location()
    {
        if (!$this->can_auth()) {
            return;
        }
        $lat = $this->body('lat');
        $lng = $this->body('lng');
        if (!is_numeric($lat) || !is_numeric($lng) || !is_finite((float) $lat) || !is_finite((float) $lng)
            || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            return $this->loi('Vị trí không hợp lệ.', 422);
        }
        if (!$this->m_user->location_ready()) {
            return $this->loi('Máy chủ chưa hỗ trợ lưu vị trí.', 503);
        }
        $this->db->where('id', $this->me['id'])->update('users', array(
            'lat' => (float) $lat, 'lng' => (float) $lng, 'location_updated_at' => date('Y-m-d H:i:s'),
        ));
        return $this->ok(array('message' => 'Đã cập nhật vị trí của bạn.'));
    }

    /**
     * POST /api/account/onboarding {province_id, bio} (+ "avatar" nếu multipart)
     * Bước "Bắt đầu" sau đăng ký: việc nào làm cũng được.
     */
    public function onboarding()
    {
        if (!$this->can_auth()) {
            return;
        }
        $data = array();
        $avatar = $this->tai_anh('avatar');
        if (!$avatar && $this->loi_anh) {
            return $this->loi($this->loi_anh, 422);
        }
        if ($avatar) {
            $data['avatar'] = $avatar;
        }
        $tinh = (int) $this->body('province_id');
        if ($tinh > 0 && $this->m_province->find($tinh)) {
            $data['province_id'] = $tinh;
        }
        $bio = trim(strip_tags((string) $this->body('bio')));
        if ($bio !== '') {
            $data['bio'] = mb_substr($bio, 0, 500);
        }
        if ($data) {
            $this->m_user->update_profile($this->me['id'], $data);
        }
        $thieu = $this->m_user->thieu_thong_tin($this->me['id']);
        return $this->ok(array(
            'missing' => $thieu,
            'message' => $thieu
                ? 'Đã lưu! Còn vài bước nữa là hồ sơ hiện với mọi người — làm tiếp lúc nào cũng được.'
                : 'Tuyệt! Hồ sơ của bạn đã hiển thị với mọi người.',
        ));
    }

    /* ====================== Ảnh ====================== */

    /** GET /api/account/photos · POST (multipart "photo") để tải thêm. */
    public function photos()
    {
        if (!$this->can_auth()) {
            return;
        }
        if ($this->input->method() === 'post') {
            $path = $this->tai_anh('photo');
            if (!$path) {
                return $this->loi($this->loi_anh ?: 'Hãy chọn một ảnh.', 422);
            }
            $this->db->insert('user_photos', array(
                'user_id' => $this->me['id'],
                'path'    => $path,
                'status'  => setting('auto_approve_post', '0') === '1' ? 'approved' : 'pending',
            ));
        }

        $ds = array();
        foreach ($this->db->where('user_id', $this->me['id'])->order_by('sort')->order_by('id')
                     ->get('user_photos')->result_array() as $p) {
            $ds[] = array('id' => (int) $p['id'], 'url' => base_url(ltrim($p['path'], '/')), 'status' => $p['status']);
        }
        return $this->ok(array(
            'items'   => $ds,
            'message' => $this->input->method() === 'post'
                ? 'Đã tải ảnh lên, ảnh sẽ hiển thị sau khi được duyệt.' : null,
        ));
    }

    /** POST /api/account/photos/{id}/delete */
    public function delete_photo($id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->db->where('id', (int) $id)->where('user_id', $this->me['id'])->delete('user_photos');
        return $this->ok();
    }

    /* ====================== Quan tâm & ghép đôi ====================== */

    /**
     * GET /api/account/likes?tab=ban-thich|thich-ban|ghep-doi|da-xem
     * Bốn thẻ của trang Quan tâm (web). Mở thẻ nào là đánh dấu đã xem thẻ đó.
     * "Người thích bạn" và "Đã xem hồ sơ" theo luật che của bản thường.
     */
    public function likes()
    {
        if (!$this->can_auth()) {
            return;
        }
        $id  = $this->me['id'];
        $tab = (string) $this->input->get('tab');
        if (!in_array($tab, $this->m_interest_badge->tabs(), true)) {
            $tab = 'ban-thich';
        }
        $map = function (array $ds) {
            return array_map(function ($u) { return $this->ho_so($u, true); }, $ds);
        };
        $vip = $this->la_vip();
        $che = function (array $ds, $them = null) use ($vip) {
            $ra = array();
            foreach ($ds as $i => $u) {
                if ($vip || $i < 2) {
                    $ra[] = array('locked' => false) + $this->ho_so($u, true) + ($them ? $them($u) : array());
                } else {
                    $ra[] = array('locked' => true, 'id' => null, 'name' => null);
                }
            }
            return $ra;
        };

        $this->m_interest_badge->mark_seen($id, $tab);

        return $this->ok(array(
            'tab'      => $tab,
            'is_vip'   => $vip,
            'totals'   => $this->m_interest_badge->counts($id, false),
            // Số mục mới chưa xem của từng thẻ (thẻ đang mở vừa được đánh dấu là 0)
            'unread'   => $this->m_interest_badge->counts($id),
            'my_likes' => $map($this->m_interaction->my_likes($id, 60)),
            'liked_me' => $che($this->m_interaction->liked_me($id, 60), function ($u) {
                return array('meta' => !empty($u['liked_at']) ? 'Thích bạn ' . time_ago($u['liked_at']) : null);
            }),
            'matches'  => $map($this->m_interaction->matches($id)),
            'viewers'  => $che($this->m_interaction->viewers($id, 60), function ($u) use ($vip) {
                return array('meta' => $vip && !empty($u['viewed_at']) ? 'Đã xem ' . time_ago($u['viewed_at']) : null);
            }),
        ));
    }

    /** POST /api/account/likes/seen {tab} — đánh dấu đã xem một thẻ (khi đổi thẻ tại chỗ). */
    public function likes_seen()
    {
        if (!$this->can_auth()) {
            return;
        }
        $ok = $this->m_interest_badge->mark_seen($this->me['id'], (string) $this->body('tab'));
        return $ok ? $this->ok(array('counts' => $this->m_interest_badge->menu_counts($this->me['id'])))
                   : $this->loi('Thẻ không hợp lệ.', 422);
    }

    /* ====================== Thông báo ====================== */

    private function thong_bao(array $n)
    {
        return array(
            'id'     => (int) $n['id'],
            'type'   => $n['type'],
            'title'  => $n['title'],
            'body'   => $n['body'],
            'url'    => $n['url'],
            'time'   => time_ago($n['created_at']),
            'unread' => empty($n['read_at']),
            // Người gây ra thông báo (thích, ghép đôi, nhắn tin) — để hiện ảnh như web
            'actor'  => $n['actor'] ?? null,
        );
    }

    /** GET /api/account/notifications — mở xem là coi như đã đọc hết, như web. */
    public function notifications()
    {
        if (!$this->can_auth()) {
            return;
        }
        $ds = array_map(array($this, 'thong_bao'), $this->m_notification->for_user($this->me['id'], 50));
        $this->m_notification->mark_all_read($this->me['id']);
        return $this->ok(array('items' => $ds));
    }

    /** POST /api/account/notifications/read */
    public function notifications_read()
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->m_notification->mark_all_read($this->me['id']);
        return $this->ok();
    }

    /* ====================== Ví xu / VIP ====================== */

    /** GET /api/account/wallet · POST {package_id, method} để tạo đơn nạp. */
    public function wallet()
    {
        if (!$this->can_auth()) {
            return;
        }
        $id = $this->me['id'];
        $don_moi = null;
        if ($this->input->method() === 'post') {
            $method = in_array($this->body('method'), array('bank', 'momo', 'vnpay', 'card'), true)
                ? $this->body('method') : 'bank';
            $order = $this->m_billing->create_order($id, (int) $this->body('package_id'), $method);
            if (!$order) {
                return $this->loi('Gói không hợp lệ.', 422);
            }
            $order['package_name'] = $this->m_billing->find_package($order['package_id'])['name'] ?? null;
            $don_moi = $this->don($order);
        }

        $goi = array();
        foreach ($this->m_billing->packages() as $p) {
            $goi[] = array(
                'id'            => (int) $p['id'],
                'type'          => $p['type'],
                'name'          => $p['name'],
                'price'         => (int) $p['price'],
                'price_text'    => money($p['price']),
                'duration_days' => $p['duration_days'] ? (int) $p['duration_days'] : null,
                'coin_amount'   => $p['coin_amount'] ? (int) $p['coin_amount'] : null,
                'bonus_coin'    => (int) $p['bonus_coin'],
                'description'   => $p['description'],
            );
        }
        $xu = array();
        foreach ($this->m_billing->coin_history($id, 30) as $c) {
            $xu[] = array(
                'id'      => (int) $c['id'],
                'amount'  => (int) $c['amount'],
                'balance' => (int) $c['balance_after'],
                'reason'  => $c['reason'],
                'note'    => $c['note'],
                'time'    => date('d/m/Y H:i', strtotime($c['created_at'])),
            );
        }
        $u = $this->m_user->find($id);

        return $this->ok(array(
            'coins'     => (int) $u['coin_balance'],
            'is_vip'    => $this->la_vip(),
            'vip_until' => $this->la_vip() && $u['vip_expired_at'] ? date('d/m/Y', strtotime($u['vip_expired_at'])) : null,
            'bank_info' => setting('bank_info', ''),
            'packages'  => $goi,
            'orders'    => array_map(array($this, 'don'), $this->m_billing->orders_of($id)),
            'coin_history' => $xu,
            'order'     => $don_moi,
            'message'   => $don_moi ? 'Đã tạo đơn ' . $don_moi['code'] . '. Vui lòng chuyển khoản với nội dung là mã đơn, '
                . 'hệ thống sẽ cộng xu/VIP sau khi xác nhận.' : null,
        ));
    }

    private function don(array $o)
    {
        return array(
            'id'           => (int) $o['id'],
            'code'         => $o['code'],
            'package_name' => $o['package_name'] ?? null,
            'amount'       => (int) $o['amount'],
            'amount_text'  => money($o['amount']),
            'method'       => $o['method'],
            'status'       => $o['status'],
            'time'         => date('d/m/Y H:i', strtotime($o['created_at'])),
        );
    }

    /* ====================== Cài đặt email ====================== */

    /** GET /api/account/email-prefs · POST để lưu. */
    public function email_prefs()
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->load->model('m_email');
        $id = $this->me['id'];
        if ($this->input->method() === 'post') {
            if ($this->body('unsubscribe_all')) {
                $this->m_email->unsubscribe_all($id);
            } else {
                $this->m_email->save_prefs($id, array(
                    'new_message'      => (int) (bool) $this->body('new_message'),
                    'notification'     => (int) (bool) $this->body('notification'),
                    'match_suggest'    => (int) (bool) $this->body('match_suggest'),
                    're_engage'        => (int) (bool) $this->body('re_engage'),
                    // Tần suất chung của email gợi ý và nhắc quay lại: 1, 2, 3 hoặc 7 ngày (như web)
                    'match_every_days' => in_array((int) $this->body('match_every_days'), array(1, 2, 3, 7), true)
                        ? (int) $this->body('match_every_days') : 2,
                    'disabled_at'      => null,
                ));
            }
        }
        $p = $this->m_email->prefs($id);
        return $this->ok(array(
            'prefs' => array(
                'new_message'      => (bool) $p['new_message'],
                'notification'     => (bool) $p['notification'],
                'match_suggest'    => (bool) $p['match_suggest'],
                're_engage'        => (bool) $p['re_engage'],
                'match_every_days' => (int) $p['match_every_days'],
                'disabled'         => !empty($p['disabled_at']),
            ),
            'message' => $this->input->method() === 'post' ? 'Đã lưu cài đặt email.' : null,
        ));
    }
}
