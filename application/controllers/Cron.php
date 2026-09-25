<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Các việc chạy nền của hệ thống email.
 *
 * Chỉ chạy được từ dòng lệnh (php index.php cron ...), không gọi qua trình
 * duyệt được — tránh ai đó bấm URL để ép gửi thư hàng loạt.
 *
 * Lịch chạy đề nghị (giờ Việt Nam, GMT+7):
 *   * * * * *  php index.php cron worker      # gửi thư trong hàng đợi, mỗi phút
 *   0 8 * * *  php index.php cron goi_y       # gợi ý ghép đôi, 8h sáng
 *   0 10 * * * php index.php cron keo_lai     # kéo người vắng lâu, 10h sáng
 *   0 20 * * * php index.php cron gom_thong_bao  # gom lượt thích/xem, 20h
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
        dong_bo_mui_gio_db($this);
        $this->load->model(array('m_email', 'm_user', 'm_interaction'));
        $this->load->library(array('mailer', 'emailer'));
    }

    private function noi($chu)
    {
        echo date('H:i:s') . '  ' . $chu . PHP_EOL;
    }

    /* ===================== Gửi thư trong hàng đợi ===================== */

    public function worker($limit = 50)
    {
        $cho = $this->m_email->den_han((int) $limit);
        if (!$cho) {
            return;   // im lặng khi không có gì, tránh rác log cron
        }

        // Chưa khai báo SMTP thì dừng hẳn, KHÔNG đụng vào hàng đợi. Nếu cứ thử
        // gửi, mọi thư đều hỏng rồi bị tính là bounce, và chỉ sau ba lượt là
        // toàn bộ người dùng bị đánh dấu ngừng nhận email — hỏng dữ liệu thật
        // chỉ vì một lỗi cấu hình.
        if (!$this->mailer->enabled() || !getenv('MAIL_FROM_ADDRESS')) {
            $this->noi('worker: chưa cấu hình MAIL_* trong .env, tạm dừng (' . count($cho) . ' thư vẫn nằm chờ)');
            return;
        }

        $gui = $bo = $hong = 0;
        foreach ($cho as $thu) {
            $payload = json_decode($thu['payload'], true) ?: array();

            // Tới giờ gửi mới kiểm tra lại điều kiện: hoàn cảnh có thể đã đổi
            $ly_do = $this->con_hop_le($thu);
            if ($ly_do !== true) {
                $this->m_email->danh_dau_bo_qua($thu['id'], $ly_do);
                $bo++;
                continue;
            }

            // Mọi nút bấm đi vòng qua bộ đếm để biết thư nào thực sự kéo được
            // người quay lại. Chỉ bọc các khoá là đường dẫn nội bộ.
            foreach (array('link', 'link_like') as $khoa) {
                if (!empty($payload[$khoa])) {
                    $payload[$khoa] = site_url('email/bam/' . (int) $thu['id'])
                        . '?u=' . urlencode($payload[$khoa]);
                }
            }

            $p = $this->m_email->prefs($thu['user_id']);
            $payload['ly_do']        = 'Bạn nhận thư này vì đang dùng tài khoản tại '
                . setting('site_name', 'Saigon Cupid') . '.';
            $payload['link_cai_dat'] = site_url('tai-khoan/email');
            $payload['link_huy']     = site_url('email/huy/' . $p['token']);
            $payload['link_mo']      = site_url('email/mo/' . (int) $thu['id']);

            if ($this->mailer->send($thu['to_email'], $thu['subject'], $thu['view'], $payload)) {
                $this->m_email->danh_dau_gui($thu['id']);
                $gui++;
            } else {
                $this->m_email->danh_dau_hong($thu['id'], $thu['user_id'], $thu['attempts'], 'SMTP gửi thất bại');
                $hong++;
            }
        }

        $this->noi("worker: gửi $gui, bỏ qua $bo, hỏng $hong");
    }

    /**
     * Điều kiện gửi được kiểm lại ngay trước lúc gửi.
     * Trả về true, hoặc một chuỗi lý do bỏ qua.
     */
    private function con_hop_le(array $thu)
    {
        if (!$this->m_email->duoc_gui($thu['user_id'], $thu['type'])) {
            return 'Người nhận đã tắt loại thư này hoặc đã chạm trần thư trong ngày';
        }

        if ($thu['type'] === 'new_message' && $thu['related_id']) {
            $u = $this->db->select('last_active_at')->where('id', $thu['user_id'])
                ->get('users')->row_array();

            // Đang online thì họ thấy tin trong ứng dụng rồi, khỏi gửi thư
            if ($u && is_online($u['last_active_at'])) {
                return 'Người nhận đang online';
            }

            $chua_doc = $this->db->where('conversation_id', (int) $thu['related_id'])
                ->where('sender_id !=', (int) $thu['user_id'])
                ->where('read_at', null)->where('deleted_at', null)
                ->count_all_results('messages');
            if ($chua_doc === 0) {
                return 'Người nhận đã đọc tin rồi';
            }
        }

        return true;
    }

    /* ===================== Gợi ý ghép đôi ===================== */

    public function goi_y()
    {
        $ung_vien = $this->db->query(
            "SELECT u.* FROM users u
               JOIN email_prefs p ON p.user_id = u.id
              WHERE u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
                AND u.email IS NOT NULL AND u.email <> ''
                AND p.match_suggest = 1 AND p.disabled_at IS NULL
                AND u.last_active_at >= ?   -- còn hoạt động trong 30 ngày
                AND u.last_active_at <  ?   -- nhưng không online trong 24h qua",
            array(date('Y-m-d H:i:s', strtotime('-30 days')),
                  date('Y-m-d H:i:s', strtotime('-24 hours')))
        )->result_array();

        $gui = 0;
        foreach ($ung_vien as $u) {
            $p = $this->m_email->prefs($u['id']);

            // Tôn trọng tần suất mỗi người tự chọn (mặc định 2 ngày)
            $lan_cuoi = $this->m_email->lan_gui_cuoi($u['id'], 'match_suggest');
            if ($lan_cuoi && $lan_cuoi > time() - (int) $p['match_every_days'] * 86400) {
                continue;
            }

            $goi_y = $this->tim_nguoi_hop($u);
            if (!$goi_y) {
                continue;
            }

            // Không gợi ý lại cùng một người trong vòng 7 ngày
            if ($this->m_email->da_gui_gan_day($u['id'], 'match_suggest', $goi_y['id'], 24 * 7)) {
                continue;
            }

            if ($this->emailer->match_suggest($u['id'], $goi_y, $goi_y['diem_phan_tram'])) {
                $gui++;
            }
        }

        $this->noi("gợi ý ghép đôi: xếp hàng $gui thư / " . count($ung_vien) . ' ứng viên');
    }

    /**
     * Chọn một người phù hợp nhất để gợi ý.
     * Thang điểm theo đặc tả: cùng tỉnh +10, tuổi lệch ≤3 +5, mới hoạt động +5,
     * có ảnh +3.
     */
    private function tim_nguoi_hop(array $u)
    {
        $pref = $this->db->where('user_id', $u['id'])->get('user_preferences')->row_array();
        $muon = $pref['seeking_gender'] ?? 'all';
        $tuoi = age_from($u['birthday']) ?: 0;

        $rows = $this->db->query(
            "SELECT u2.*, p.name AS province_name,
                    (SELECT GROUP_CONCAT(i.name ORDER BY i.name SEPARATOR '|')
                       FROM user_interests ui JOIN interests i ON i.id = ui.interest_id
                      WHERE ui.user_id = u2.id) AS interest_names,
                    (  IF(u2.province_id IS NOT NULL AND u2.province_id = ?, 10, 0)
                     + IF(ABS(TIMESTAMPDIFF(YEAR, u2.birthday, CURDATE()) - ?) <= 3, 5, 0)
                     + IF(u2.last_active_at > NOW() - INTERVAL 1 DAY, 5, 0)
                     + IF(u2.avatar IS NOT NULL AND u2.avatar <> '', 3, 0)
                    ) AS diem
               FROM users u2
          LEFT JOIN provinces p ON p.id = u2.province_id
              WHERE u2.id <> ?
                AND u2.status = 'active' AND u2.role = 'member' AND u2.deleted_at IS NULL
                AND (? = 'all' OR u2.gender = ?)
                AND u2.last_active_at >= ?
                AND u2.id NOT IN (SELECT target_id FROM likes
                                   WHERE user_id = ? AND target_type = 'user')
                AND u2.id NOT IN (SELECT IF(user_low_id = ?, user_high_id, user_low_id)
                                    FROM matches WHERE ? IN (user_low_id, user_high_id))
                AND u2.id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
                AND u2.id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
           ORDER BY diem DESC, u2.last_active_at DESC
              LIMIT 1",
            array(
                (int) $u['province_id'], $tuoi, $u['id'],
                $muon, $muon,
                date('Y-m-d H:i:s', strtotime('-7 days')),
                $u['id'], $u['id'], $u['id'], $u['id'], $u['id'],
            )
        )->result_array();

        if (!$rows) {
            return null;
        }
        $m = $rows[0];
        // Tổng điểm tối đa là 23, quy về phần trăm cho dễ đọc, sàn 70%
        $m['diem_phan_tram'] = max(70, min(99, (int) round((int) $m['diem'] * 100 / 23)));
        return $m;
    }

    /* ===================== Gom thông báo cuối ngày ===================== */

    public function gom_thong_bao()
    {
        $tu = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $rows = $this->db->query(
            "SELECT l.target_id AS user_id, COUNT(*) AS so
               FROM likes l
               JOIN users u ON u.id = l.target_id
               JOIN email_prefs p ON p.user_id = u.id
              WHERE l.target_type = 'user' AND l.status = 'pending' AND l.created_at >= ?
                AND u.status = 'active' AND u.deleted_at IS NULL
                AND p.notification = 1 AND p.disabled_at IS NULL
           GROUP BY l.target_id",
            array($tu)
        )->result_array();

        $gui = 0;
        foreach ($rows as $r) {
            $avatars = array();
            $ds = $this->db->select('u.avatar, u.gender')->from('likes l')
                ->join('users u', 'u.id = l.user_id')
                ->where('l.target_type', 'user')->where('l.target_id', $r['user_id'])
                ->where('l.status', 'pending')->where('l.created_at >=', $tu)
                ->limit(5)->get()->result_array();
            foreach ($ds as $a) {
                $avatars[] = avatar_url($a['avatar'], $a['gender']);
            }

            $u = $this->m_user->find($r['user_id']);
            if ($this->emailer->batch_likes($r['user_id'], $r['so'], $avatars, !empty($u['is_vip']))) {
                $gui++;
            }
        }

        $this->noi("gom lượt thích: xếp hàng $gui thư");
        $this->gom_luot_xem($tu);
    }

    /** Gom lượt xem hồ sơ trong ngày thành một thư. */
    private function gom_luot_xem($tu)
    {
        $rows = $this->db->query(
            "SELECT v.owner_id AS user_id, COUNT(*) AS so
               FROM profile_views v
               JOIN users u ON u.id = v.owner_id
               JOIN users w ON w.id = v.viewer_id AND w.deleted_at IS NULL
               JOIN email_prefs p ON p.user_id = u.id
              WHERE v.viewed_at >= ?
                AND u.status = 'active' AND u.deleted_at IS NULL
                AND p.notification = 1 AND p.disabled_at IS NULL
           GROUP BY v.owner_id",
            array($tu)
        )->result_array();

        $gui = 0;
        foreach ($rows as $r) {
            if ($this->emailer->batch_views($r['user_id'], $r['so'])) {
                $gui++;
            }
        }
        $this->noi("gom lượt xem hồ sơ: xếp hàng $gui thư");
    }

    /* ===================== Kéo người vắng lâu quay lại ===================== */

    public function keo_lai()
    {
        $rows = $this->db->query(
            "SELECT u.* FROM users u
               JOIN email_prefs p ON p.user_id = u.id
              WHERE u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
                AND u.email IS NOT NULL AND u.email <> ''
                AND p.re_engage = 1 AND p.disabled_at IS NULL
                AND u.last_active_at < ?",
            array(date('Y-m-d H:i:s', strtotime('-7 days')))
        )->result_array();

        $gui = 0;
        foreach ($rows as $u) {
            // Mỗi người tối đa một thư loại này mỗi tuần
            $lan_cuoi = $this->m_email->lan_gui_cuoi($u['id'], 're_engage');
            if ($lan_cuoi && $lan_cuoi > time() - 7 * 86400) {
                continue;
            }

            $ds = $this->m_interaction->liked_me($u['id'], 3);
            $avatars = array();
            foreach ($ds as $a) {
                $avatars[] = avatar_url($a['avatar'], $a['gender']);
            }

            if ($this->emailer->re_engage($u['id'], $this->m_interaction->liked_me_count($u['id']), $avatars)) {
                $gui++;
            }
        }

        $this->noi("kéo lại người vắng: xếp hàng $gui thư");
    }
}
