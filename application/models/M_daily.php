<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gợi ý mỗi ngày: mỗi người mỗi ngày đúng một hồ sơ, có hạn 24 giờ.
 *
 * Chọn xong thì chốt vào bảng daily_matches chứ không tính lại mỗi lần mở
 * trang — người dùng phải thấy đúng một người suốt cả ngày thì mới có cảm giác
 * "cơ hội có hạn", và số liệu thống kê mới đối chiếu được.
 */
class M_daily extends CI_Model
{
    /** Lần gọi tao_cho() gần nhất có thực sự chốt gợi ý mới hay không. */
    public $vua_tao = false;

    /** Hạn dùng: tới 8h sáng hôm sau. */
    private function han_dung()
    {
        return date('Y-m-d 08:00:00', strtotime('+1 day'));
    }

    /** Gợi ý của hôm nay, kèm hồ sơ người được gợi ý. */
    public function today($user_id)
    {
        $row = $this->db->select('d.*, u.display_name, u.nickname, u.slug, u.avatar, u.gender,
                                  u.birthday, u.bio, u.job, u.height_cm, u.marital_status,
                                  u.last_active_at, p.name AS province_name,
                                  (SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR "|")
                                     FROM user_interests ui JOIN interests i ON i.id = ui.interest_id
                                    WHERE ui.user_id = u.id) AS interest_names')
            ->from('daily_matches d')
            ->join('users u', 'u.id = d.match_user_id')
            ->join('provinces p', 'p.id = u.province_id', 'left')
            ->where('d.user_id', (int) $user_id)
            ->where('d.match_date', date('Y-m-d'))
            ->where('u.deleted_at', null)
            ->get()->row_array();

        if (!$row) {
            return null;
        }

        // Quá hạn mà cron chưa kịp quét thì đánh dấu ngay khi đọc
        if ($row['status'] === 'pending' && strtotime($row['expires_at']) <= time()) {
            $this->db->where('id', $row['id'])->update('daily_matches', array('status' => 'expired'));
            $row['status'] = 'expired';
        }
        return $row;
    }

    /**
     * Chọn người phù hợp nhất rồi chốt cho hôm nay.
     * Trả về mảng gợi ý, hoặc null nếu không tìm được ai.
     */
    public function tao_cho($user_id)
    {
        $user_id = (int) $user_id;
        $u = $this->db->where('id', $user_id)->get('users')->row_array();
        if (!$u) {
            return null;
        }

        // Đã có gợi ý hôm nay thì trả về luôn, không chốt lại
        $da_co = $this->db->where('user_id', $user_id)->where('match_date', date('Y-m-d'))
            ->count_all_results('daily_matches') > 0;
        if ($da_co) {
            $this->vua_tao = false;
            return $this->today($user_id);
        }

        $chon = $this->tim_ung_vien($u);
        if (!$chon) {
            return null;
        }

        $this->db->insert('daily_matches', array(
            'user_id'       => $user_id,
            'match_user_id' => (int) $chon['id'],
            'match_date'    => date('Y-m-d'),
            'score'         => (int) $chon['diem'],
            'expires_at'    => $this->han_dung(),
        ));
        $this->vua_tao = true;
        return $this->today($user_id);
    }

    /**
     * Thang điểm đúng mục 1.3 của đặc tả:
     * cùng tỉnh +10, tuổi lệch ≤3 +5, hoạt động trong 24h +5, có ảnh +3,
     * bio từ 50 ký tự +2, mỗi sở thích trùng +1. Hoà điểm thì bốc ngẫu nhiên.
     */
    private function tim_ung_vien(array $u)
    {
        $pref = $this->db->where('user_id', $u['id'])->get('user_preferences')->row_array();
        $muon = $pref['seeking_gender'] ?? 'all';
        $tuoi = age_from($u['birthday']) ?: 0;

        $rows = $this->db->query(
            "SELECT u2.id,
                    (  IF(u2.province_id IS NOT NULL AND u2.province_id = ?, 10, 0)
                     + IF(ABS(TIMESTAMPDIFF(YEAR, u2.birthday, CURDATE()) - ?) <= 3, 5, 0)
                     + IF(u2.last_active_at > NOW() - INTERVAL 1 DAY, 5, 0)
                     + IF(u2.avatar IS NOT NULL AND u2.avatar <> '', 3, 0)
                     + IF(CHAR_LENGTH(COALESCE(u2.bio, '')) >= 50, 2, 0)
                     + (SELECT COUNT(*) FROM user_interests a
                          JOIN user_interests b ON b.interest_id = a.interest_id AND b.user_id = u2.id
                         WHERE a.user_id = ?)
                    ) AS diem
               FROM users u2
              WHERE u2.id <> ?
                AND u2.status = 'active' AND u2.role = 'member' AND u2.deleted_at IS NULL
                AND (? = 'all' OR u2.gender = ?)
                AND u2.last_active_at >= ?
                -- Đặc tả yêu cầu hồ sơ phải có ít nhất một ảnh
                AND (u2.avatar IS NOT NULL AND u2.avatar <> '')
                AND u2.id NOT IN (SELECT target_id FROM likes
                                   WHERE user_id = ? AND target_type = 'user')
                AND u2.id NOT IN (SELECT passed_id FROM user_passes WHERE user_id = ?)
                AND u2.id NOT IN (SELECT IF(user_low_id = ?, user_high_id, user_low_id)
                                    FROM matches WHERE ? IN (user_low_id, user_high_id))
                AND u2.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
                AND u2.id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
                -- Đã từng được gợi ý rồi thì không gợi lại
                AND u2.id NOT IN (SELECT match_user_id FROM daily_matches WHERE user_id = ?)
           ORDER BY diem DESC, RAND()
              LIMIT 1",
            array(
                (int) $u['province_id'], $tuoi, $u['id'], $u['id'],
                $muon, $muon,
                date('Y-m-d H:i:s', strtotime('-7 days')),
                $u['id'], $u['id'], $u['id'], $u['id'], $u['id'], $u['id'], $u['id'],
            )
        )->result_array();

        return $rows ? $rows[0] : null;
    }

    /**
     * Người dùng bấm Thích hoặc Bỏ qua.
     * Trả về ['ok'=>bool, 'matched'=>bool, 'message'=>string].
     */
    public function tra_loi($user_id, $id, $hanh_dong)
    {
        $row = $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
            ->get('daily_matches')->row_array();

        if (!$row) {
            return array('ok' => false, 'matched' => false, 'message' => 'Không tìm thấy gợi ý này.');
        }
        if ($row['status'] !== 'pending') {
            return array('ok' => false, 'matched' => false, 'message' => 'Gợi ý này đã được trả lời hoặc đã hết hạn.');
        }
        if (strtotime($row['expires_at']) <= time()) {
            $this->db->where('id', $row['id'])->update('daily_matches', array('status' => 'expired'));
            return array('ok' => false, 'matched' => false,
                'message' => 'Gợi ý đã hết hạn. Quay lại lúc 8h sáng mai nhé!');
        }

        $this->load->model('m_interaction');

        if ($hanh_dong === 'skip') {
            // Bỏ qua thì ghi luôn vào user_passes để không bao giờ gợi lại
            $this->db->replace('user_passes', array(
                'user_id' => (int) $user_id, 'passed_id' => (int) $row['match_user_id'],
            ));
            $this->db->where('id', $row['id'])->update('daily_matches', array('status' => 'skipped'));
            return array('ok' => true, 'matched' => false, 'message' => 'Đã bỏ qua. Hẹn gặp ngày mai!');
        }

        $kq = $this->m_interaction->toggle_like($user_id, 'user', $row['match_user_id']);
        $this->db->where('id', $row['id'])->update('daily_matches', array(
            'status' => !empty($kq['matched']) ? 'matched' : 'liked',
        ));

        return array(
            'ok'      => true,
            'matched' => !empty($kq['matched']),
            'message' => !empty($kq['matched'])
                ? 'Ghép đôi thành công! Hai bạn có thể nhắn tin cho nhau.'
                : 'Đã gửi lượt thích. Chờ người ấy trả lời nhé!',
        );
    }

    /** Đánh dấu hết hạn cho các gợi ý quá giờ (cron gọi). */
    public function het_han()
    {
        $this->db->where('status', 'pending')
            ->where('expires_at <=', date('Y-m-d H:i:s'))
            ->update('daily_matches', array('status' => 'expired'));
        return $this->db->affected_rows();
    }

    /** Lịch sử gợi ý đã nhận. */
    public function history($user_id, $limit = 30)
    {
        return $this->db->select('d.*, u.display_name, u.nickname, u.slug, u.avatar, u.gender, u.birthday')
            ->from('daily_matches d')->join('users u', 'u.id = d.match_user_id')
            ->where('d.user_id', (int) $user_id)->where('u.deleted_at', null)
            ->order_by('d.match_date', 'DESC')->limit($limit)
            ->get()->result_array();
    }
}
