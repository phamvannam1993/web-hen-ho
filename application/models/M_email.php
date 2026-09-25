<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hệ thống email: hàng đợi, giới hạn chống spam, cài đặt của từng người.
 *
 * Mọi email đều đi qua bảng email_queue chứ không gửi thẳng. Bảng này vừa là
 * hàng đợi (chờ tới giờ, thử lại khi hỏng) vừa là nhật ký (đã gửi lúc nào, có
 * mở không, có bấm không) — một chỗ để tra cứu và thống kê.
 *
 * Bản đặc tả nêu Redis + BullMQ + SES, nhưng dự án này chạy CodeIgniter và gửi
 * thư qua SMTP, nên hàng đợi đặt trong cơ sở dữ liệu và một tiến trình chạy
 * bằng cron đóng vai trò worker. Hành vi bên ngoài giống hệt.
 */
class M_email extends CI_Model
{
    /** Trần chống spam: tối đa bao nhiêu thư một người một ngày. */
    const TOI_DA_MOI_NGAY = 2;

    /** Gửi hỏng bao nhiêu lần thì ngừng gửi cho địa chỉ đó. */
    const TOI_DA_HONG = 3;

    /** Thử lại mấy lần, cách nhau bao nhiêu phút. */
    const TOI_DA_THU_LAI = 3;
    const PHUT_THU_LAI   = 5;

    /** Loại thư nào chịu trần 2 thư/ngày. Thư giao dịch thì không. */
    private $mien_tran = array('welcome', 'notify_match', 'new_message');

    /** Loại thư ứng với công tắc nào trong bảng cài đặt. */
    private $cong_tac = array(
        'welcome'       => 'welcome',
        'new_message'   => 'new_message',
        'notify_match'  => 'notification',
        'notify_like'   => 'notification',
        'notify_view'   => 'notification',
        'match_suggest' => 'match_suggest',
        're_engage'     => 're_engage',
    );

    /* ===================== Cài đặt của người dùng ===================== */

    /** Lấy cài đặt, chưa có thì tạo dòng mặc định kèm mã huỷ đăng ký. */
    public function prefs($user_id)
    {
        $user_id = (int) $user_id;
        $row = $this->db->where('user_id', $user_id)->get('email_prefs')->row_array();
        if ($row) {
            return $row;
        }

        $this->db->insert('email_prefs', array(
            'user_id' => $user_id,
            'token'   => sha1($user_id . '-' . uniqid('', true) . '-' . random_int(0, PHP_INT_MAX)),
        ));
        return $this->db->where('user_id', $user_id)->get('email_prefs')->row_array();
    }

    public function by_token($token)
    {
        return $this->db->where('token', (string) $token)->get('email_prefs')->row_array();
    }

    public function save_prefs($user_id, array $data)
    {
        $this->prefs($user_id);   // bảo đảm đã có dòng
        $this->db->where('user_id', (int) $user_id)->update('email_prefs', $data);
    }

    /** Huỷ nhận toàn bộ email. */
    public function unsubscribe_all($user_id)
    {
        $this->save_prefs($user_id, array(
            'welcome' => 0, 'new_message' => 0, 'notification' => 0,
            'match_suggest' => 0, 're_engage' => 0,
            'disabled_at' => date('Y-m-d H:i:s'),
        ));
    }

    /** Nhận lại email (bỏ trạng thái đã huỷ). */
    public function resubscribe($user_id)
    {
        $this->save_prefs($user_id, array('disabled_at' => null, 'bounce_count' => 0));
    }

    /* ===================== Đưa vào hàng đợi ===================== */

    /**
     * Xếp một email vào hàng đợi.
     *
     * @param int    $user_id  người nhận
     * @param string $type     loại thư, xem cột type
     * @param string $subject  tiêu đề
     * @param string $view     tên view trong application/views/emails/
     * @param array  $payload  dữ liệu cho view
     * @param array  $opts     delay_minutes, related_id
     * @return int|false id trong hàng đợi, hoặc false nếu bị chặn
     */
    public function enqueue($user_id, $type, $subject, $view, array $payload = array(), array $opts = array())
    {
        $user = $this->db->select('id, email, display_name, nickname, deleted_at, status')
            ->where('id', (int) $user_id)->get('users')->row_array();

        if (!$user || $user['deleted_at'] || empty($user['email'])) {
            return false;
        }
        if (in_array($user['status'], array('banned', 'locked'), true)) {
            return false;
        }
        if (!$this->duoc_gui($user_id, $type)) {
            return false;
        }

        $delay = (int) ($opts['delay_minutes'] ?? 0);
        $this->db->insert('email_queue', array(
            'user_id'    => (int) $user_id,
            'type'       => $type,
            'to_email'   => $user['email'],
            'subject'    => $subject,
            'variant'    => $opts['variant'] ?? null,
            'view'       => $view,
            'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'related_id' => $opts['related_id'] ?? null,
            'send_after' => date('Y-m-d H:i:s', time() + $delay * 60),
        ));
        return (int) $this->db->insert_id();
    }

    /**
     * Người này có được nhận loại thư này không.
     *
     * Kiểm tra: đã huỷ đăng ký chưa, có tắt riêng loại này không, địa chỉ có
     * bị đánh dấu hỏng không, và trần 2 thư/ngày.
     */
    public function duoc_gui($user_id, $type)
    {
        $p = $this->prefs($user_id);
        if (!$p || $p['disabled_at']) {
            return false;
        }
        if ((int) $p['bounce_count'] >= self::TOI_DA_HONG) {
            return false;
        }

        $cot = $this->cong_tac[$type] ?? null;
        if ($cot && empty($p[$cot])) {
            return false;
        }

        if (!in_array($type, $this->mien_tran, true) && $this->so_thu_hom_nay($user_id) >= self::TOI_DA_MOI_NGAY) {
            return false;
        }
        return true;
    }

    /** Đã gửi bao nhiêu thư cho người này trong hôm nay. */
    public function so_thu_hom_nay($user_id)
    {
        return (int) $this->db->where('user_id', (int) $user_id)
            ->where('status', 'sent')
            ->where('sent_at >=', date('Y-m-d 00:00:00'))
            ->count_all_results('email_queue');
    }

    /** Lần gửi gần nhất của một loại thư, trả về timestamp hoặc null. */
    public function lan_gui_cuoi($user_id, $type)
    {
        $row = $this->db->select('sent_at')->where('user_id', (int) $user_id)
            ->where('type', $type)->where('status', 'sent')
            ->order_by('sent_at', 'DESC')->limit(1)
            ->get('email_queue')->row_array();
        return $row ? strtotime($row['sent_at']) : null;
    }

    /**
     * Đã gửi thư loại này cho đối tượng này trong N giờ qua chưa.
     * Dùng cho luật "1 thư / 1 cuộc hội thoại / 1 giờ" và "không gợi ý lại
     * cùng một người trong 7 ngày".
     */
    public function da_gui_gan_day($user_id, $type, $related_id, $trong_gio)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->where('type', $type)
            ->where('related_id', (int) $related_id)
            ->where_in('status', array('sent', 'pending'))
            ->where('created_at >=', date('Y-m-d H:i:s', time() - $trong_gio * 3600))
            ->count_all_results('email_queue') > 0;
    }

    /* ===================== Worker ===================== */

    /** Các thư tới giờ gửi. */
    public function den_han($limit = 50)
    {
        return $this->db->where('status', 'pending')
            ->where('send_after <=', date('Y-m-d H:i:s'))
            ->order_by('id', 'ASC')->limit($limit)
            ->get('email_queue')->result_array();
    }

    public function danh_dau_gui($id)
    {
        $this->db->where('id', (int) $id)->update('email_queue', array(
            'status'  => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'error'   => null,
        ));
    }

    /** Bỏ qua (điều kiện không còn đúng lúc tới giờ gửi), có ghi lý do. */
    public function danh_dau_bo_qua($id, $ly_do)
    {
        $this->db->where('id', (int) $id)->update('email_queue', array(
            'status' => 'skipped',
            'error'  => mb_substr($ly_do, 0, 255),
        ));
    }

    /**
     * Gửi hỏng: thử lại tối đa 3 lần, mỗi lần cách 5 phút. Hết lượt thì đánh
     * dấu hỏng hẳn và cộng bounce cho người nhận.
     */
    public function danh_dau_hong($id, $user_id, $attempts, $loi)
    {
        $attempts = (int) $attempts + 1;

        if ($attempts < self::TOI_DA_THU_LAI) {
            $this->db->where('id', (int) $id)->update('email_queue', array(
                'attempts'   => $attempts,
                'error'      => mb_substr($loi, 0, 255),
                'send_after' => date('Y-m-d H:i:s', time() + self::PHUT_THU_LAI * 60),
            ));
            return;
        }

        $this->db->where('id', (int) $id)->update('email_queue', array(
            'status'   => 'failed',
            'attempts' => $attempts,
            'error'    => mb_substr($loi, 0, 255),
        ));

        // Hỏng hết lượt thì tính là một lần bounce của địa chỉ đó
        $this->db->where('user_id', (int) $user_id)
            ->set('bounce_count', 'bounce_count + 1', false)
            ->update('email_prefs');

        $p = $this->prefs($user_id);
        if ((int) $p['bounce_count'] >= self::TOI_DA_HONG && !$p['disabled_at']) {
            $this->save_prefs($user_id, array('disabled_at' => date('Y-m-d H:i:s')));
            log_message('error', 'Email: ngừng gửi cho user ' . $user_id . ' vì hỏng quá nhiều lần.');
        }
    }

    /* ===================== Theo dõi mở / bấm ===================== */

    public function ghi_mo($id)
    {
        $this->db->where('id', (int) $id)->where('opened_at', null)
            ->update('email_queue', array('opened_at' => date('Y-m-d H:i:s')));
    }

    public function ghi_bam($id)
    {
        $this->db->where('id', (int) $id)
            ->update('email_queue', array('clicked_at' => date('Y-m-d H:i:s')));
        $this->ghi_mo($id);   // bấm được thì chắc chắn đã mở
    }

    /* ===================== Thử nghiệm A/B tiêu đề ===================== */

    /**
     * Chọn một trong hai cách viết tiêu đề cho người này.
     *
     * Chia nhánh theo id người dùng chứ không bốc ngẫu nhiên: cùng một người
     * luôn rơi vào cùng một nhánh, nên số liệu so sánh mới sạch và người dùng
     * không thấy tiêu đề đổi kiểu mỗi lần.
     *
     * @param array $tieu_de array('A' => '...', 'B' => '...')
     * @return array array($tieu_de_da_chon, 'A'|'B')
     */
    public function chon_nhanh($user_id, array $tieu_de)
    {
        $nhanh = ((int) $user_id % 2 === 0) ? 'A' : 'B';
        return array($tieu_de[$nhanh], $nhanh);
    }

    /** So sánh hai nhánh: gửi bao nhiêu, mở bao nhiêu, bấm bao nhiêu. */
    public function thong_ke_ab($ngay = 30)
    {
        return $this->db->query(
            "SELECT type, variant,
                    COUNT(*) AS da_gui,
                    SUM(opened_at IS NOT NULL)  AS da_mo,
                    SUM(clicked_at IS NOT NULL) AS da_bam
               FROM email_queue
              WHERE variant IS NOT NULL AND status = 'sent' AND sent_at >= ?
           GROUP BY type, variant
           ORDER BY type, variant",
            array(date('Y-m-d H:i:s', strtotime('-' . (int) $ngay . ' days')))
        )->result_array();
    }

    /** Số liệu cho trang quản trị. */
    public function thong_ke($ngay = 30)
    {
        return $this->db->query(
            "SELECT type,
                    COUNT(*) AS tong,
                    SUM(status = 'sent')    AS da_gui,
                    SUM(status = 'failed')  AS hong,
                    SUM(status = 'skipped') AS bo_qua,
                    SUM(opened_at IS NOT NULL)  AS da_mo,
                    SUM(clicked_at IS NOT NULL) AS da_bam
               FROM email_queue
              WHERE created_at >= ?
           GROUP BY type
           ORDER BY tong DESC",
            array(date('Y-m-d H:i:s', strtotime('-' . (int) $ngay . ' days')))
        )->result_array();
    }
}
