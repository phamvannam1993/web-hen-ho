<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_user extends CI_Model
{
    public function find($id)
    {
        return $this->db->select('u.*, p.name AS province_name')
            ->from('users u')->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('u.id', $id)->where('u.deleted_at', null)
            ->get()->row_array();
    }

    public function by_slug($slug)
    {
        return $this->db->select('u.*, p.name AS province_name')
            ->from('users u')->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('u.slug', $slug)->where('u.deleted_at', null)
            ->get()->row_array();
    }

    public function email_exists($email, $ignore_id = null)
    {
        $this->db->where('email', $email);
        if ($ignore_id) $this->db->where('id !=', $ignore_id);
        return $this->db->count_all_results('users') > 0;
    }

    public function phone_exists($phone, $ignore_id = null)
    {
        $this->db->where('phone', $phone);
        if ($ignore_id) $this->db->where('id !=', $ignore_id);
        return $this->db->count_all_results('users') > 0;
    }

    /** Tạo thành viên mới + thiết lập mặc định + xu tặng. */
    public function register(array $input)
    {
        $bonus = (int) setting('signup_bonus_coin', 50);
        $id = null;

        $this->db->trans_start();
        $this->db->insert('users', array(
            'uuid'          => $this->uuid(),
            'email'         => $input['email'] ?: null,
            'phone'         => $input['phone'] ?: null,
            'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
            'display_name'  => $input['display_name'],
            'slug'          => unique_slug('users', $input['display_name'] . '-' . substr(md5(uniqid('', true)), 0, 5)),
            'gender'        => $input['gender'],
            'birthday'      => $input['birthday'] ?: null,
            'province_id'   => $input['province_id'] ?: null,
            'status'        => setting('auto_approve_user', '1') === '1' ? 'active' : 'pending',
            'coin_balance'  => $bonus,
        ));
        $id = $this->db->insert_id();

        $this->db->insert('user_preferences', array(
            'user_id'        => $id,
            'seeking_gender' => $input['gender'] === 'male' ? 'female' : ($input['gender'] === 'female' ? 'male' : 'all'),
        ));

        if ($bonus > 0) {
            $this->db->insert('coin_transactions', array(
                'user_id'       => $id,
                'amount'        => $bonus,
                'balance_after' => $bonus,
                'reason'        => 'bonus',
                'note'          => 'Xu tặng khi đăng ký',
            ));
        }
        $this->db->trans_complete();

        $this->recalc_profile_score($id);
        return $id;
    }

    public function update_profile($id, array $data)
    {
        $this->db->trans_start();
        if (array_key_exists('email', $data)) {
            $current = $this->find($id);
            if ((string) $data['email'] !== (string) ($current['email'] ?? '')) {
                $data['email_verified_at'] = null;
                $this->db->where('user_id', $id)
                    ->where_in('type', array('verify_email', 'reset_password', 'otp'))
                    ->delete('user_tokens');
            }
        }
        $this->db->where('id', $id)->update('users', $data);
        $this->db->trans_complete();
        $this->recalc_profile_score($id);
    }

    public function change_password($id, $plain)
    {
        $this->db->where('id', $id)->update('users', array(
            'password_hash' => password_hash($plain, PASSWORD_DEFAULT),
        ));
    }

    /**
     * Danh sách thành viên có lọc.
     * $filters: gender, province_id, age_min, age_max, keyword, online, vip, sort
     */
    public function search(array $filters, $limit = 12, $offset = 0)
    {
        $this->build_search($filters);
        $select = 'u.*, p.name AS province_name,
            (SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR "|")
               FROM user_interests ui JOIN interests i ON i.id = ui.interest_id
              WHERE ui.user_id = u.id) AS interest_names';

        $select .= ", (SELECT COUNT(*) FROM likes lk
                        WHERE lk.target_type = 'user' AND lk.target_id = u.id) AS like_count";

        // Người đang đăng nhập cần biết mình đã thích ai, nếu không thì sau khi
        // tải lại trang nút sẽ quay về "Thích" dù lượt thích đã được ghi nhận.
        $viewer = $this->auth->check() ? (int) $this->auth->id() : 0;
        if ($viewer) {
            $select .= ", (SELECT COUNT(*) FROM likes l
                            WHERE l.user_id = $viewer AND l.target_type = 'user'
                              AND l.target_id = u.id) AS liked";
        }
        $this->db->select($select, false);
        if (array_key_exists('distance_origin', $filters)) {
            $km = $this->distance_sql($filters['distance_origin']);
            $real = $this->real_location_sql();
            $this->db->select("$km AS distance_km, " . (!empty($filters['distance_origin']['real']) ? $real : '0') . ' AS distance_real', false);
            $province_id = (int) ($filters['distance_origin']['province_id'] ?? 0);
            $this->db->select(($province_id ? "(u.province_id = $province_id)" : '0') . ' AS distance_same_province', false);
        }

        switch ($filters['sort'] ?? 'active') {
            case 'nearby':
                $km = $this->distance_sql($filters['distance_origin'] ?? null);
                $this->db->order_by("($km) IS NULL", 'ASC', false)
                    ->order_by($km, 'ASC', false)->order_by('u.last_active_at', 'DESC')->order_by('u.id', 'DESC');
                break;
            case 'new':   $this->db->order_by('u.created_at', 'DESC'); break;
            case 'vip':   $this->db->order_by('u.is_vip', 'DESC')->order_by('u.last_active_at', 'DESC'); break;
            case 'listened':
                // Người nhận nhiều lượt quan tâm nhất, tức được nhiều người tìm đến
                $this->db->order_by("(SELECT COUNT(*) FROM likes lk
                                       WHERE lk.target_type = 'user' AND lk.target_id = u.id)", 'DESC', false)
                         ->order_by('u.last_active_at', 'DESC');
                break;
            case 'verified':
                $this->db->order_by("u.kyc_status = 'verified'", 'DESC', false)
                         ->order_by('u.last_active_at', 'DESC');
                break;
            default:      $this->db->order_by('u.last_active_at', 'DESC');
        }
        return $this->db->limit($limit, $offset)->get()->result_array();
    }

    public function count_search(array $filters)
    {
        $this->build_search($filters);
        return $this->db->count_all_results();
    }

    /** Cấu hình "chỉ hiện thành viên đang online" (Quản trị -> Cấu hình). */
    private function only_online()
    {
        static $on = null;
        if ($on === null) {
            $this->load->model('m_setting');
            $all = $this->m_setting->all();
            $on  = !empty($all['only_online']);
        }
        return $on;
    }

    private function build_search(array $f)
    {
        $this->db->from('users u')->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('u.status', 'active')->where('u.deleted_at', null)
            ->where('u.role', 'member')
            ->where($this->dieu_kien_ho_so_du('u'), null, false);
        if ($this->auth->check()) {
            $viewer = (int) $this->auth->id();
            $this->db->where("NOT EXISTS (SELECT 1 FROM likes browse_like
                WHERE browse_like.user_id = $viewer AND browse_like.target_type = 'user'
                  AND browse_like.target_id = u.id)", null, false);
        }
        if (!empty($f['distance_max']) && !empty($f['distance_origin'])) {
            $this->db->where('(' . $this->distance_sql($f['distance_origin']) . ') <= ' . (int) $f['distance_max'], null, false);
        }

        if (!empty($f['gender']))      $this->db->where('u.gender', $f['gender']);
        if (!empty($f['province_id'])) $this->db->where('u.province_id', (int) $f['province_id']);
        if (!empty($f['vip']))         $this->db->where('u.is_vip', 1);
        // Chỉ hiện người đang online: bật/tắt ở Quản trị -> Cấu hình -> Kiểm duyệt.
        // Bộ lọc "đang online" của người dùng cũng dẫn tới cùng điều kiện này.
        if (!empty($f['online']) || $this->only_online()) {
            $this->db->where('u.last_active_at >', date('Y-m-d H:i:s', time() - 300));
        }
        if (!empty($f['age_min']))     $this->db->where('u.birthday <=', date('Y-m-d', strtotime('-' . (int) $f['age_min'] . ' years')));
        if (!empty($f['age_max']))     $this->db->where('u.birthday >=', date('Y-m-d', strtotime('-' . ((int) $f['age_max'] + 1) . ' years')));
        // Trang Hẹn hò: lọc theo xu hướng tìm kiếm (tab Gay / Les) và tích xanh
        if (!empty($f['seeking'])) {
            $this->db->join('user_preferences sp', 'sp.user_id = u.id', 'left')
                     ->where('sp.seeking_gender', $f['seeking']);
        }
        if (!empty($f['verified'])) $this->db->where('u.kyc_status', 'verified');
        // Trang Tâm sự: lọc theo chủ đề mong muốn
        if (!empty($f['topic'])) $this->db->where('u.confide_topic', $f['topic']);

        if (!empty($f['marital']))   $this->db->where('u.marital_status', $f['marital']);
        if (!empty($f['education']))  $this->db->where('u.education', $f['education']);
        if (!empty($f['smoking']))    $this->db->where('u.smoking', $f['smoking']);
        if (!empty($f['drinking']))   $this->db->where('u.drinking', $f['drinking']);
        if (!empty($f['height_min'])) $this->db->where('u.height_cm >=', (int) $f['height_min']);
        if (!empty($f['height_max'])) $this->db->where('u.height_cm <=', (int) $f['height_max']);
        if (isset($f['has_children']) && $f['has_children'] !== '') {
            $this->db->where('u.has_children', (int) $f['has_children']);
        }
        // Lọc theo sở thích: chỉ giữ người có đủ mọi sở thích được chọn
        if (!empty($f['interests']) && is_array($f['interests'])) {
            $ids = array_filter(array_map('intval', $f['interests']));
            if ($ids) {
                $this->db->where('(SELECT COUNT(*) FROM user_interests ui
                                    WHERE ui.user_id = u.id AND ui.interest_id IN (' . implode(',', $ids) . ')) = '
                                 . count($ids), null, false);
            }
        }

        if (!empty($f['keyword'])) {
            // Ô tìm kiếm ngoài trang chủ ghi "tên, khu vực hoặc mô tả" nên phải
            // tìm cả nghề nghiệp và tên tỉnh, không chỉ tên và giới thiệu.
            $this->db->group_start()
                ->like('u.display_name', $f['keyword'])
                ->or_like('u.nickname', $f['keyword'])
                ->or_like('u.bio', $f['keyword'])
                ->or_like('u.job', $f['keyword'])
                ->or_like('p.name', $f['keyword'])
                ->group_end();
        }
    }

    public function location_ready()
    {
        return $this->db->field_exists('location_updated_at', 'users')
            && $this->db->field_exists('lat', 'provinces') && $this->db->field_exists('lng', 'provinces');
    }

    private function real_location_sql()
    {
        if (!$this->location_ready()) return '0';
        return "(u.location_updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND u.lat BETWEEN -90 AND 90 AND u.lng BETWEEN -180 AND 180)";
    }

    public function province_coordinates(array $province)
    {
        if (isset($province['lat'], $province['lng'])) return array((float) $province['lat'], (float) $province['lng']);
        $coordinates = require __DIR__ . '/../config/province_coordinates.php';
        return $coordinates[$province['slug'] ?? ''] ?? null;
    }

    private function province_coordinate_sql($axis)
    {
        $coordinates = require __DIR__ . '/../config/province_coordinates.php';
        $sql = 'CASE p.slug';
        foreach ($coordinates as $slug => $point) {
            $sql .= " WHEN '" . $slug . "' THEN " . (float) $point[$axis === 'lat' ? 0 : 1];
        }
        $sql .= ' ELSE NULL END';
        return $this->db->field_exists($axis, 'provinces') ? "COALESCE(p.$axis, $sql)" : "($sql)";
    }

    public function distance_sql($origin)
    {
        if (!$origin) return 'NULL';
        $lat = (float) $origin['lat'];
        $lng = (float) $origin['lng'];
        $real = $this->real_location_sql();
        $province_lat = $this->province_coordinate_sql('lat');
        $province_lng = $this->province_coordinate_sql('lng');
        $target_lat = $real === '0' ? $province_lat : "IF($real, u.lat, $province_lat)";
        $target_lng = $real === '0' ? $province_lng : "IF($real, u.lng, $province_lng)";
        return "(6371 * ACOS(GREATEST(-1, LEAST(1,
            COS(RADIANS($lat)) * COS(RADIANS($target_lat)) * COS(RADIANS($target_lng) - RADIANS($lng))
            + SIN(RADIANS($lat)) * SIN(RADIANS($target_lat))))))";
    }

    /**
     * Điều kiện SQL: chỉ những hồ sơ đã khai đủ mới được hiện ra ngoài.
     * Danh sách cột khớp đúng với M_user::thieu_thong_tin() để hai bên không lệch nhau.
     *
     * @param string $a bí danh bảng users trong câu truy vấn
     */
    public function dieu_kien_ho_so_du($a = 'u')
    {
        $dk = array("$a.birthday <= '" . date('Y-m-d', strtotime('-18 years')) . "'");
        foreach (array('display_name', 'gender', 'birthday', 'province_id', 'avatar') as $c) {
            $dk[] = "$a.$c IS NOT NULL";
        }
        foreach (array('display_name', 'avatar') as $c) {
            $dk[] = "$a.$c <> ''";
        }
        $dk[] = "EXISTS (SELECT 1 FROM user_preferences up WHERE up.user_id = $a.id
                          AND up.seeking_gender IS NOT NULL AND up.purpose IS NOT NULL)";
        return implode(' AND ', $dk);
    }

    /** Tổng số mục cần để hồ sơ hiện công khai, dùng cho thanh tiến độ ở trang hồ sơ. */
    public function so_muc_bat_buoc()
    {
        return 7;   // 5 mục trong users + 2 mục tiêu chí tìm kiếm
    }

    /**
     * Những mục còn thiếu để hồ sơ được HIỆN CÔNG KHAI (danh sách Hẹn hò, Ghép
     * đôi ẩn, Thành viên, gợi ý…). Thiếu thì người dùng vẫn dùng web bình thường,
     * chỉ là hồ sơ bị ẩn và có banner nhắc. Mảng rỗng nghĩa là đã đủ.
     *
     * PHẢI khớp với dieu_kien_ho_so_du() — hai bên là một luật.
     */
    public function thieu_thong_tin($user_id)
    {
        $u = $this->db->where('id', $user_id)->get('users')->row_array();
        if (!$u) {
            return array();
        }
        $pref = $this->db->where('user_id', $user_id)->get('user_preferences')->row_array();

        // Ảnh và khu vực lên đầu: hai mục người mới đăng ký luôn còn thiếu
        // (đăng ký chỉ hỏi tên, giới tính, ngày sinh).
        $can = array(
            'avatar'       => 'Ảnh đại diện',
            'province_id'  => 'Khu vực',
            'display_name' => 'Tên hiển thị',
            'gender'       => 'Giới tính',
            'birthday'     => 'Ngày sinh',
        );

        $thieu = array();
        foreach ($can as $cot => $ten) {
            if ($u[$cot] === null || $u[$cot] === '') {
                $thieu[] = $ten;
            }
        }
        foreach (array('seeking_gender' => 'Muốn tìm', 'purpose' => 'Mục đích') as $cot => $ten) {
            if (!$pref || $pref[$cot] === null || $pref[$cot] === '') {
                $thieu[] = $ten;
            }
        }

        return $thieu;
    }

    /**
     * Danh sách hồ sơ để vuốt ở trang Khám phá.
     *
     * Chỉ lọc cứng đúng một điều kiện: giới tính phải khớp hướng tìm kiếm
     *   nam  -> chỉ hiện nữ
     *   nữ   -> chỉ hiện nam
     *   gay  -> chỉ hiện nam,  les -> chỉ hiện nữ
     * Không đòi hỏi người ta cũng phải đang tìm mình nữa, nên trong nhóm giới tính
     * đó thì hiện hết, chỉ xếp người hợp nhất lên trước. Điểm tương hợp tính ngay
     * trong SQL để sắp đúng trên toàn bộ dữ liệu chứ không riêng trang đang xem:
     *   +40  đúng giới tính tôi đang tìm
     *   +25  họ cũng đang tìm giới tính của tôi   (hợp nhau hai chiều)
     *   +15  tuổi họ nằm trong khoảng tôi tìm
     *   +20  cùng tỉnh/thành
     *   +15  cùng mục đích hẹn hò
     *   +5   mỗi sở thích chung (tối đa 20)
     *   +10  đang online
     *   +0-10 theo mức hoàn thiện hồ sơ
     * Chỉ loại trừ: chính mình, người đã chặn/bị chặn, người đã thích hoặc bỏ qua.
     *
     * $view: 'male' | 'female' | 'gay' | 'les' — khách chưa đăng nhập chọn nhóm
     * giới tính muốn xem.
     */
    public function deck($me, $view = null, $filters = array(), $limit = 20, $offset = 0)
    {
        list($gioi_ung_vien, $ung_vien_tim) = $this->cap_doi_phu_hop($me, $view);
        $where = $this->dieu_kien_deck($me, $gioi_ung_vien, $filters);

        // Khoảng cách theo đường chim bay (công thức Haversine, bán kính Trái Đất 6371 km)
        $km = 'NULL';
        if ($me && !empty($me['lat']) && !empty($me['lng'])) {
            $lat = (float) $me['lat'];
            $lng = (float) $me['lng'];
            $km  = "ROUND(6371 * ACOS(LEAST(1,
                        COS(RADIANS($lat)) * COS(RADIANS(u.lat)) * COS(RADIANS(u.lng) - RADIANS($lng))
                      + SIN(RADIANS($lat)) * SIN(RADIANS(u.lat)))), 1)";
        }

        $binds = array();

        // Điểm giới tính, hai vế:
        //   +40 họ đúng giới tính tôi đang tìm      (nam tìm nữ -> nữ được cộng)
        //   +25 họ cũng đang tìm giới tính của tôi  (nữ ấy tìm nam -> hợp hai chiều)
        // Người chưa khai nhu cầu thì mặc định coi như tìm giới tính đối lập, nên
        // chỉ cộng khi giới tính của họ khác giới tính tôi ($ung_vien_tim là giới
        // tính của chính tôi). Trước đây so với giới tính đối lập nên bị ngược:
        // người cùng giới với tôi lại được cộng điểm "hợp nhau".
        $diem = "IF(u.gender = ?, 40, 0) + IF(pr.seeking_gender = ? OR pr.seeking_gender = 'all'
                    OR (pr.seeking_gender IS NULL AND u.gender <> ?), 25, 0)";
        $binds[]  = $gioi_ung_vien;
        $binds[]  = $ung_vien_tim;
        $binds[]  = $ung_vien_tim;

        if ($me) {
            $pref    = $this->db->where('user_id', $me['id'])->get('user_preferences')->row_array();
            $age_min = (int) ($pref['age_min'] ?? 18);
            $age_max = (int) ($pref['age_max'] ?? 60);
            $purpose = $pref['purpose'] ?? 'hen_ho';
            $id      = (int) $me['id'];

            $diem .= "
                 + IF(TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) BETWEEN ? AND ?, 15, 0)
                 + IF(u.province_id IS NOT NULL AND u.province_id = ?, 20, 0)
                 + IF(pr.purpose = ?, 15, 0)
                 + LEAST(20, 5 * (
                       SELECT COUNT(*) FROM user_interests mine
                       JOIN user_interests theirs
                         ON theirs.interest_id = mine.interest_id AND theirs.user_id = u.id
                      WHERE mine.user_id = $id
                   ))";
            $binds[] = $age_min;
            $binds[] = $age_max;
            $binds[] = (int) $me['province_id'];
            $binds[] = $purpose;
        }

        $diem .= "
             + IF(u.last_active_at > NOW() - INTERVAL 5 MINUTE, 10, 0)
             + ROUND(u.profile_score / 10)";

        $sql = "
            SELECT u.*, p.name AS province_name, $km AS khoang_cach,
                   ($diem) AS match_score,
                   (SELECT GROUP_CONCAT(ph.path ORDER BY ph.sort SEPARATOR '|')
                      FROM user_photos ph
                     WHERE ph.user_id = u.id AND ph.status = 'approved') AS photo_paths,
                   (SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR '|')
                      FROM user_interests ui JOIN interests i ON i.id = ui.interest_id
                     WHERE ui.user_id = u.id) AS interest_names
              FROM users u
         LEFT JOIN provinces p ON p.id = u.province_id
         LEFT JOIN user_preferences pr ON pr.user_id = u.id
             WHERE $where
          ORDER BY match_score DESC, u.last_active_at DESC, u.id DESC
             LIMIT ? OFFSET ?";

        $binds[] = (int) $limit;
        $binds[] = (int) $offset;

        return $this->db->query($sql, $binds)->result_array();
    }

    /** Đếm tổng số hồ sơ còn lại cho khung vuốt. */
    public function count_deck($me, $view = null, $filters = array())
    {
        list($gioi_ung_vien) = $this->cap_doi_phu_hop($me, $view);
        $where = $this->dieu_kien_deck($me, $gioi_ung_vien, $filters);

        return (int) $this->db->query("SELECT COUNT(*) c FROM users u WHERE $where")->row('c');
    }

    /**
     * Điều kiện lọc dùng chung cho khung vuốt: giới tính đúng hướng tìm kiếm,
     * bỏ những người không thể hiện được (chính mình, đã chặn, đã thích, đã bỏ qua),
     * cộng thêm bộ lọc tỉnh/tuổi người dùng tự chọn.
     */
    private function dieu_kien_deck($me, $gioi_ung_vien, $filters = array())
    {
        $where = "u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL";
        $where .= ' AND ' . $this->dieu_kien_ho_so_du('u');
        $where .= " AND u.gender = " . $this->db->escape($gioi_ung_vien);

        if ($me) {
            $id = (int) $me['id'];
            $where .= "
                AND u.id <> $id
                AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = $id)
                AND u.id NOT IN (SELECT user_id    FROM blocks WHERE blocked_id = $id)
                AND u.id NOT IN (SELECT passed_id  FROM user_passes WHERE user_id = $id)
                AND u.id NOT IN (SELECT target_id  FROM likes
                                  WHERE user_id = $id AND target_type = 'user')";
        }

        if (!empty($filters['province_id'])) {
            $where .= ' AND u.province_id = ' . (int) $filters['province_id'];
        }
        if (!empty($filters['age_min'])) {
            $where .= " AND u.birthday <= '"
                . date('Y-m-d', strtotime('-' . (int) $filters['age_min'] . ' years')) . "'";
        }
        if (!empty($filters['age_max'])) {
            $where .= " AND u.birthday >= '"
                . date('Y-m-d', strtotime('-' . ((int) $filters['age_max'] + 1) . ' years')) . "'";
        }

        return $where;
    }

    /**
     * Từ hồ sơ người xem (hoặc lựa chọn của khách) suy ra nên ưu tiên ai lên trước.
     * Trả về [giới tính nên xếp trước, giới tính mà họ nên đang tìm].
     */
    private function cap_doi_phu_hop($me, $view = null)
    {
        // Khách chưa đăng nhập: tự chọn nhóm muốn xem ở màn hình chào
        if (!$me) {
            switch ($view) {
                case 'male':   return array('male', 'female');    // khách muốn xem bạn trai
                case 'female': return array('female', 'male');
                case 'gay':    return array('male', 'male');
                case 'les':    return array('female', 'female');
                default:       return array('female', 'male');
            }
        }

        // Thành viên đã đăng nhập: luôn hiện giới tính đối lập.
        // Nam -> chỉ nữ, nữ -> chỉ nam, không phụ thuộc mục "đang tìm" trong hồ sơ.
        $toi_la = $me['gender'] === 'male' ? 'male' : 'female';
        return array($toi_la === 'male' ? 'female' : 'male', $toi_la);
    }

    /**
     * Gợi ý ghép đôi dựa trên hồ sơ hai bên.
     *
     * Điểm tương hợp được tính ngay trong SQL để sắp xếp chính xác trên toàn bộ
     * tập dữ liệu (không chỉ trong trang hiện tại):
     *   +30  đúng giới tính mà tôi đang tìm
     *   +25  tôi nằm trong khoảng tuổi người ta tìm  (ghép đôi hai chiều)
     *   +20  cùng tỉnh/thành
     *   +15  cùng mục đích hẹn hò
     *   +5   mỗi sở thích chung (tối đa 20)
     *   +10  đang online
     *   +0-10 theo mức hoàn thiện hồ sơ
     * Loại trừ: chính mình, người đã chặn/bị chặn, người tôi đã thích hoặc đã bỏ qua.
     */
    /** Rotate a shortlist of suitable profiles at 08:00 Vietnam time. */
    public function account_suggestions($user, $limit = 3, $now = null)
    {
        $limit = max(1, (int) $limit);
        $candidates = $this->suggestions($user, max(30, $limit));
        if (!$candidates) return array();

        // Stable per-member order; no random reshuffle on page reload.
        $member_id = (int) $user['id'];
        usort($candidates, function ($a, $b) use ($member_id) {
            return strcmp(hash('sha256', $member_id . ':' . $a['id']),
                hash('sha256', $member_id . ':' . $b['id']));
        });
        $clock = (new DateTimeImmutable('@' . ($now ?? time())))
            ->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        $cycle = $clock->modify('-8 hours')->format('Y-m-d');
        $day = (int) floor((new DateTimeImmutable($cycle, new DateTimeZone('UTC')))->getTimestamp() / 86400);
        $count = count($candidates);
        // Advance one position each day so a pool larger than the display count changes.
        $start = (($day % $count) + $count) % $count;
        $selected = array();
        for ($i = 0; $i < min($limit, $count); $i++) {
            $selected[] = $candidates[($start + $i) % $count];
        }
        return $selected;
    }

    public function suggestions($user, $limit = 12, $offset = 0)
    {
        $me   = (int) $user['id'];
        $du   = $this->dieu_kien_ho_so_du('u');
        $pref = $this->db->where('user_id', $me)->get('user_preferences')->row_array();

        $seeking = $pref['seeking_gender'] ?? 'all';
        $age_min = (int) ($pref['age_min'] ?? 18);
        $age_max = (int) ($pref['age_max'] ?? 60);
        $purpose = $pref['purpose'] ?? 'hen_ho';
        $my_age  = age_from($user['birthday']) ?: 0;
        $online_cond = $this->only_online()
            ? "AND u.last_active_at > '" . date('Y-m-d H:i:s', time() - 300) . "'" : '';

        $sql = "
            SELECT u.*, p.name AS province_name,
                   TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) AS age,
                   (
                       IF(? = 'all' OR u.gender = ?, 30, 0)
                     + IF(TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) BETWEEN ? AND ?, 25, 0)
                     + IF(pr.age_min IS NULL OR ? BETWEEN pr.age_min AND pr.age_max, 25, 0)
                     + IF(u.province_id IS NOT NULL AND u.province_id = ?, 20, 0)
                     + IF(pr.purpose = ?, 15, 0)
                     + LEAST(20, 5 * (
                           SELECT COUNT(*) FROM user_interests mine
                           JOIN user_interests theirs
                             ON theirs.interest_id = mine.interest_id AND theirs.user_id = u.id
                          WHERE mine.user_id = ?
                       ))
                     + IF(u.last_active_at > NOW() - INTERVAL 5 MINUTE, 10, 0)
                     + ROUND(u.profile_score / 10)
                   ) AS match_score,
                   (SELECT COUNT(*) FROM likes l
                     WHERE l.user_id = ? AND l.target_type = 'user' AND l.target_id = u.id) AS liked
              FROM users u
         LEFT JOIN provinces p  ON p.id = u.province_id
         LEFT JOIN user_preferences pr ON pr.user_id = u.id
             WHERE u.id <> ?
               AND u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
               AND $du
               AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
               AND u.id NOT IN (SELECT user_id     FROM blocks WHERE blocked_id = ?)
               AND u.id NOT IN (SELECT passed_id FROM user_passes WHERE user_id = ?)
               $online_cond
               AND NOT EXISTS (SELECT 1 FROM likes hidden_like WHERE hidden_like.user_id = $me
                   AND hidden_like.target_type = 'user' AND hidden_like.target_id = u.id)
          ORDER BY match_score DESC, u.last_active_at DESC
             LIMIT ? OFFSET ?";

        return $this->db->query($sql, array(
            $seeking, $seeking, $age_min, $age_max, $my_age,
            (int) $user['province_id'], $purpose,
            $me,                                    // sở thích chung trong match_score
            $me,                                    // cột liked
            $me,                                    // u.id <> ?
            $me, $me,                               // hai bảng chặn
            $me,                                    // đã bỏ qua
            (int) $limit, (int) $offset,
        ))->result_array();
    }

    /** Đếm số hồ sơ còn có thể gợi ý (dùng cho trang khám phá). */
    public function count_suggestions($user)
    {
        $me = (int) $user['id'];
        $du = $this->dieu_kien_ho_so_du('u');
        $online_cond = $this->only_online()
            ? "AND u.last_active_at > '" . date('Y-m-d H:i:s', time() - 300) . "'" : '';
        return (int) $this->db->query(
            "SELECT COUNT(*) c FROM users u
              WHERE u.id <> ? AND u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
                AND $du
                AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
                AND u.id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
                AND u.id NOT IN (SELECT passed_id FROM user_passes WHERE user_id = ?)
                AND NOT EXISTS (SELECT 1 FROM likes hidden_like WHERE hidden_like.user_id = $me
                    AND hidden_like.target_type = 'user' AND hidden_like.target_id = u.id)
                $online_cond",
            array($me, $me, $me, $me)
        )->row('c');
    }

    /** Nhóm thành viên theo mục đích hẹn hò, dùng dựng khối trên trang chủ. */
    public function by_purpose($purpose, $limit = 8)
    {
        // Kèm cờ đã thích để nút trên thẻ giữ đúng trạng thái sau khi tải lại trang
        $viewer = $this->auth->check() ? (int) $this->auth->id() : 0;
        $select = 'u.*, p.name AS province_name';
        if ($viewer) {
            $select .= ", (SELECT COUNT(*) FROM likes l
                            WHERE l.user_id = $viewer AND l.target_type = 'user'
                              AND l.target_id = u.id) AS liked";
        }

        $this->db->select($select, false)
            ->from('users u')
            ->join('provinces p', 'p.id = u.province_id', 'left')
            ->join('user_preferences pr', 'pr.user_id = u.id')
            ->where('pr.purpose', $purpose)
            ->where('u.status', 'active')->where('u.role', 'member')->where('u.deleted_at', null)
            ->where($this->dieu_kien_ho_so_du('u'), null, false);

        if ($viewer) {
            $this->db->where("NOT EXISTS (SELECT 1 FROM likes purpose_like WHERE purpose_like.user_id = $viewer
                AND purpose_like.target_type = 'user' AND purpose_like.target_id = u.id)", null, false);
        }
        if ($this->only_online()) {
            $this->db->where('u.last_active_at >', date('Y-m-d H:i:s', time() - 300));
        }

        return $this->db->order_by('u.is_vip', 'DESC')->order_by('u.last_active_at', 'DESC')
            ->limit($limit)->get()->result_array();
    }

    /** Danh sách cho admin. */
    public function admin_list(array $f, $limit, $offset)
    {
        $this->admin_filter($f);
        return $this->db->select('u.*, p.name AS province_name')
            ->order_by('u.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function admin_count(array $f)
    {
        $this->admin_filter($f);
        return $this->db->count_all_results();
    }

    private function admin_filter(array $f)
    {
        $this->db->from('users u')->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('u.deleted_at', null);
        if (!empty($f['status'])) $this->db->where('u.status', $f['status']);
        if (!empty($f['role']))   $this->db->where('u.role', $f['role']);
        if (!empty($f['gender'])) $this->db->where('u.gender', $f['gender']);
        if (!empty($f['keyword'])) {
            $this->db->group_start()
                ->like('u.display_name', $f['keyword'])
                ->or_like('u.nickname', $f['keyword'])
                ->or_like('u.email', $f['keyword'])
                ->or_like('u.phone', $f['keyword'])
                ->group_end();
        }
    }

    public function set_status($id, $status)
    {
        $this->db->where('id', $id)->update('users', array('status' => $status));
    }

    public function soft_delete($id)
    {
        $this->db->where('id', $id)->update('users', array('deleted_at' => date('Y-m-d H:i:s')));
    }

    /** Cộng/trừ xu và ghi sổ giao dịch. */
    public function adjust_coin($user_id, $amount, $reason, $ref_type = null, $ref_id = null, $note = null)
    {
        $this->db->trans_begin();
        $balance = (int) $this->db->select('coin_balance')->where('id', $user_id)
            ->get('users')->row('coin_balance');
        $after = $balance + $amount;
        if ($after < 0) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->where('id', $user_id)->update('users', array('coin_balance' => $after));
        $this->db->insert('coin_transactions', array(
            'user_id'       => $user_id,
            'amount'        => $amount,
            'balance_after' => $after,
            'reason'        => $reason,
            'ref_type'      => $ref_type,
            'ref_id'        => $ref_id,
            'note'          => $note,
        ));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /** Kích hoạt / gia hạn VIP. */
    public function grant_vip($user_id, $days)
    {
        $user = $this->find($user_id);
        $from = ($user['vip_expired_at'] && $user['vip_expired_at'] > date('Y-m-d H:i:s'))
            ? strtotime($user['vip_expired_at']) : time();
        $this->db->where('id', $user_id)->update('users', array(
            'is_vip'         => 1,
            'vip_expired_at' => date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days', $from)),
        ));
    }

    /**
     * Tính % hoàn thiện hồ sơ để ưu tiên hiển thị. Cùng công thức với
     * tk_ho_so_day_du() — gọi lại sau khi lưu sở thích để tính cả mục đó.
     */
    public function recalc_profile_score($id)
    {
        $u = $this->db->where('id', $id)->get('users')->row_array();
        if (!$u) return 0;
        $score = tk_ho_so_day_du($u)['phan_tram'];
        $this->db->where('id', $id)->update('users', array('profile_score' => $score));
        return $score;
    }

    public function stats()
    {
        return array(
            'total'      => (int) $this->db->where('deleted_at', null)->count_all_results('users'),
            'active'     => (int) $this->db->where('deleted_at', null)->where('status', 'active')->count_all_results('users'),
            'pending'    => (int) $this->db->where('deleted_at', null)->where('status', 'pending')->count_all_results('users'),
            'vip'        => (int) $this->db->where('deleted_at', null)->where('is_vip', 1)->count_all_results('users'),
            'today'      => (int) $this->db->where('DATE(created_at)', date('Y-m-d'))->count_all_results('users'),
            'online'     => (int) $this->db->where('last_active_at >', date('Y-m-d H:i:s', time() - 300))->count_all_results('users'),
        );
    }

    private function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
