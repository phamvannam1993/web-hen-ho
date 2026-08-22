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
        $this->db->where('id', $id)->update('users', $data);
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
        $this->db->select('u.*, p.name AS province_name');

        switch ($filters['sort'] ?? 'active') {
            case 'new':   $this->db->order_by('u.created_at', 'DESC'); break;
            case 'vip':   $this->db->order_by('u.is_vip', 'DESC')->order_by('u.last_active_at', 'DESC'); break;
            default:      $this->db->order_by('u.last_active_at', 'DESC');
        }
        return $this->db->limit($limit, $offset)->get()->result_array();
    }

    public function count_search(array $filters)
    {
        $this->build_search($filters);
        return $this->db->count_all_results();
    }

    private function build_search(array $f)
    {
        $this->db->from('users u')->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('u.status', 'active')->where('u.deleted_at', null)
            ->where('u.role', 'member');

        if (!empty($f['gender']))      $this->db->where('u.gender', $f['gender']);
        if (!empty($f['province_id'])) $this->db->where('u.province_id', (int) $f['province_id']);
        if (!empty($f['vip']))         $this->db->where('u.is_vip', 1);
        if (!empty($f['online']))      $this->db->where('u.last_active_at >', date('Y-m-d H:i:s', time() - 300));
        if (!empty($f['age_min']))     $this->db->where('u.birthday <=', date('Y-m-d', strtotime('-' . (int) $f['age_min'] . ' years')));
        if (!empty($f['age_max']))     $this->db->where('u.birthday >=', date('Y-m-d', strtotime('-' . ((int) $f['age_max'] + 1) . ' years')));
        if (!empty($f['keyword'])) {
            $this->db->group_start()
                ->like('u.display_name', $f['keyword'])
                ->or_like('u.nickname', $f['keyword'])
                ->or_like('u.bio', $f['keyword'])
                ->group_end();
        }
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
    public function suggestions($user, $limit = 12, $offset = 0)
    {
        $me   = (int) $user['id'];
        $pref = $this->db->where('user_id', $me)->get('user_preferences')->row_array();

        $seeking = $pref['seeking_gender'] ?? 'all';
        $age_min = (int) ($pref['age_min'] ?? 18);
        $age_max = (int) ($pref['age_max'] ?? 60);
        $purpose = $pref['purpose'] ?? 'hen_ho';
        $my_age  = age_from($user['birthday']) ?: 0;

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
                   ) AS match_score
              FROM users u
         LEFT JOIN provinces p  ON p.id = u.province_id
         LEFT JOIN user_preferences pr ON pr.user_id = u.id
             WHERE u.id <> ?
               AND u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
               AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
               AND u.id NOT IN (SELECT user_id     FROM blocks WHERE blocked_id = ?)
               AND u.id NOT IN (
                     SELECT target_id FROM likes
                      WHERE user_id = ? AND target_type = 'user'
                   )
               AND u.id NOT IN (SELECT passed_id FROM user_passes WHERE user_id = ?)
          ORDER BY match_score DESC, u.last_active_at DESC
             LIMIT ? OFFSET ?";

        return $this->db->query($sql, array(
            $seeking, $seeking, $age_min, $age_max, $my_age,
            (int) $user['province_id'], $purpose, $me,
            $me, $me, $me, $me, $me, (int) $limit, (int) $offset,
        ))->result_array();
    }

    /** Đếm số hồ sơ còn có thể gợi ý (dùng cho trang khám phá). */
    public function count_suggestions($user)
    {
        $me = (int) $user['id'];
        return (int) $this->db->query(
            "SELECT COUNT(*) c FROM users u
              WHERE u.id <> ? AND u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
                AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
                AND u.id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
                AND u.id NOT IN (SELECT target_id FROM likes WHERE user_id = ? AND target_type = 'user')
                AND u.id NOT IN (SELECT passed_id FROM user_passes WHERE user_id = ?)",
            array($me, $me, $me, $me, $me)
        )->row('c');
    }

    /** Nhóm thành viên theo mục đích hẹn hò, dùng dựng khối trên trang chủ. */
    public function by_purpose($purpose, $limit = 8)
    {
        return $this->db->select('u.*, p.name AS province_name')
            ->from('users u')
            ->join('provinces p', 'p.id = u.province_id', 'left')
            ->join('user_preferences pr', 'pr.user_id = u.id')
            ->where('pr.purpose', $purpose)
            ->where('u.status', 'active')->where('u.role', 'member')->where('u.deleted_at', null)
            ->order_by('u.is_vip', 'DESC')->order_by('u.last_active_at', 'DESC')
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

    /** Tính % hoàn thiện hồ sơ để ưu tiên hiển thị. */
    public function recalc_profile_score($id)
    {
        $u = $this->db->where('id', $id)->get('users')->row_array();
        if (!$u) return 0;
        $fields = array('avatar', 'bio', 'birthday', 'province_id', 'job', 'height_cm', 'marital_status', 'education');
        $filled = 0;
        foreach ($fields as $f) {
            if (!empty($u[$f])) $filled++;
        }
        $score = (int) round($filled / count($fields) * 100);
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
