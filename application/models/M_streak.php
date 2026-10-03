<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chuỗi ngày hoạt động liên tiếp.
 *
 * Đếm theo NGÀY chứ không theo lần vào: đăng nhập mười lần trong ngày vẫn chỉ
 * tính một. Mốc thưởng chỉ trao đúng một lần nhờ khoá duy nhất trên bảng
 * streak_badges, nên chạy lại hay bấm lại cũng không phát trùng xu.
 */
class M_streak extends CI_Model
{
    /** Các mốc: số ngày => [mã, tên, xu thưởng, phần trăm tăng hiển thị]. */
    const MOC = array(
        3   => array('khoi_dau',   'Khởi đầu',   0,   0),
        7   => array('kien_tri',   'Kiên trì',   10,  0),
        14  => array('ben_bi',     'Bền bỉ',     20,  10),
        30  => array('thanh_tam',  'Thành tâm',  50,  20),
        60  => array('chung_thuy', 'Chung thuỷ', 100, 30),
        100 => array('huyen_thoai','Huyền thoại',200, 50),
    );

    public function get($user_id)
    {
        $user_id = (int) $user_id;
        $row = $this->db->where('user_id', $user_id)->get('user_streaks')->row_array();
        if ($row) {
            return $row;
        }
        $this->db->insert('user_streaks', array('user_id' => $user_id));
        return $this->db->where('user_id', $user_id)->get('user_streaks')->row_array();
    }

    /**
     * Ghi nhận một ngày hoạt động. Gọi mỗi lần vào trang, chạy nhiều lần trong
     * ngày cũng chỉ tính một.
     *
     * Trả về ['streak'=>int, 'moc_moi'=>array|null] — mốc_moi khác null nghĩa
     * là vừa đạt mốc mới, để giao diện chúc mừng.
     */
    public function cham_cong($user_id)
    {
        $s       = $this->get($user_id);
        $hom_nay = date('Y-m-d');
        $hom_qua = date('Y-m-d', strtotime('-1 day'));

        if ($s['last_active_date'] === $hom_nay) {
            return array('streak' => (int) $s['current_streak'], 'moc_moi' => null);
        }

        if ($s['last_active_date'] === $hom_qua) {
            $moi = (int) $s['current_streak'] + 1;          // nối tiếp
        } elseif ($s['last_active_date'] === null) {
            $moi = 1;                                        // lần đầu
        } else {
            $moi = 1;                                        // đứt chuỗi, bắt đầu lại
        }

        $this->db->where('user_id', (int) $user_id)->update('user_streaks', array(
            'current_streak'   => $moi,
            'longest_streak'   => max($moi, (int) $s['longest_streak']),
            'last_active_date' => $hom_nay,
        ));

        return array('streak' => $moi, 'moc_moi' => $this->trao_moc($user_id, $moi));
    }

    /**
     * Trao huy hiệu và thưởng nếu vừa chạm mốc.
     * Khoá duy nhất trên bảng bảo đảm mỗi mốc chỉ trao một lần.
     */
    private function trao_moc($user_id, $streak)
    {
        if (!isset(self::MOC[$streak])) {
            return null;
        }
        list($ma, $ten, $xu, $hien_thi) = self::MOC[$streak];

        $da_co = $this->db->where('user_id', (int) $user_id)->where('badge_code', $ma)
            ->count_all_results('streak_badges') > 0;
        if ($da_co) {
            return null;
        }

        $this->db->insert('streak_badges', array('user_id' => (int) $user_id, 'badge_code' => $ma));

        if ($xu > 0) {
            $this->load->model('m_user');
            $this->m_user->adjust_coin($user_id, $xu, 'bonus', 'streak', null,
                'Thưởng chuỗi ' . $streak . ' ngày — huy hiệu ' . $ten);
        }

        $this->load->model('m_notification');
        $this->m_notification->push($user_id, 'system', 'Đạt huy hiệu ' . $ten . '!',
            'Bạn đã hoạt động ' . $streak . ' ngày liên tiếp'
                . ($xu > 0 ? ', được thưởng ' . $xu . ' xu.' : '.'),
            site_url('tai-khoan'));

        return array('ngay' => $streak, 'ma' => $ma, 'ten' => $ten, 'xu' => $xu, 'hien_thi' => $hien_thi);
    }

    /** Huy hiệu đã đạt, kèm thông tin mốc. */
    public function badges($user_id)
    {
        $rows = $this->db->where('user_id', (int) $user_id)
            ->order_by('achieved_at', 'ASC')->get('streak_badges')->result_array();

        $ra = array();
        foreach ($rows as $r) {
            foreach (self::MOC as $ngay => $m) {
                if ($m[0] === $r['badge_code']) {
                    $ra[] = array('ngay' => $ngay, 'ma' => $m[0], 'ten' => $m[1],
                                  'xu' => $m[2], 'hien_thi' => $m[3], 'luc' => $r['achieved_at']);
                    break;
                }
            }
        }
        return $ra;
    }

    /** Mốc kế tiếp để hiện "còn N ngày nữa". */
    public function moc_ke_tiep($streak)
    {
        foreach (self::MOC as $ngay => $m) {
            if ($ngay > $streak) {
                return array('ngay' => $ngay, 'ten' => $m[1], 'xu' => $m[2], 'con' => $ngay - $streak);
            }
        }
        return null;
    }

    /** Mức tăng hiển thị đang được hưởng, lấy theo huy hiệu cao nhất. */
    public function boost($user_id)
    {
        $max = 0;
        foreach ($this->badges($user_id) as $b) {
            $max = max($max, (int) $b['hien_thi']);
        }
        return $max;
    }

    /**
     * Dùng một lượt giữ chuỗi: coi như hôm qua có hoạt động.
     * Chỉ cứu được khi chuỗi vừa đứt đúng một ngày.
     */
    public function dung_freeze($user_id)
    {
        $s = $this->get($user_id);
        if ((int) $s['streak_freeze_count'] < 1) {
            return array('ok' => false, 'message' => 'Bạn không còn lượt giữ chuỗi nào.');
        }

        $hom_qua = date('Y-m-d', strtotime('-1 day'));
        if ($s['last_active_date'] >= $hom_qua) {
            return array('ok' => false, 'message' => 'Chuỗi của bạn vẫn đang liền mạch, chưa cần dùng.');
        }
        if ($s['last_active_date'] < date('Y-m-d', strtotime('-2 days'))) {
            return array('ok' => false, 'message' => 'Chuỗi đã đứt quá lâu, lượt giữ chuỗi không cứu được.');
        }

        $this->db->where('user_id', (int) $user_id)->update('user_streaks', array(
            'last_active_date'    => $hom_qua,
            'streak_freeze_count' => (int) $s['streak_freeze_count'] - 1,
        ));
        return array('ok' => true, 'message' => 'Đã giữ lại chuỗi của bạn.');
    }

    /** Mua thêm lượt giữ chuỗi bằng xu. */
    public function mua_freeze($user_id, $gia = 30)
    {
        $this->load->model('m_user');
        if (!$this->m_user->adjust_coin($user_id, -$gia, 'spend', 'streak_freeze', null, 'Mua lượt giữ chuỗi')) {
            return array('ok' => false, 'message' => 'Không đủ xu. Bạn cần ' . $gia . ' xu.');
        }
        $this->get($user_id);
        $this->db->where('user_id', (int) $user_id)
            ->set('streak_freeze_count', 'streak_freeze_count + 1', false)
            ->update('user_streaks');

        return array('ok' => true, 'message' => 'Đã mua 1 lượt giữ chuỗi.');
    }
}
