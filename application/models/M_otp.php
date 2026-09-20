<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mã OTP gửi qua email cho hai việc: xác thực khi đăng ký và bước hai khi
 * đăng nhập.
 *
 * Mã lưu trong bảng user_tokens nhưng KHÔNG lưu nguyên văn — chỉ lưu bản băm.
 * Ai đọc được cơ sở dữ liệu cũng không đăng nhập hộ người khác được.
 */
class M_otp extends CI_Model
{
    /** Loại token cho từng mục đích. */
    const MUC_DICH = array(
        'register' => 'verify_email',
        'login'    => 'otp',
    );

    const SO_CHU_SO  = 6;
    const PHUT_SONG  = 10;   // mã sống bao lâu
    const GIAY_CHO   = 60;   // phải chờ bao lâu mới được gửi lại
    const TOI_DA_SAI = 5;    // sai quá số này thì huỷ mã

    private function loai($muc_dich)
    {
        return self::MUC_DICH[$muc_dich] ?? self::MUC_DICH['login'];
    }

    /** Băm mã kèm id người dùng để hai người trùng mã vẫn ra hai chuỗi khác nhau. */
    private function bam($ma, $user_id)
    {
        return hash('sha256', $ma . '|' . (int) $user_id . '|' . config_item('encryption_key'));
    }

    /** Mã còn hiệu lực gần nhất của một người, dùng để tính thời gian chờ gửi lại. */
    private function hien_hanh($user_id, $muc_dich)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->where('type', $this->loai($muc_dich))
            ->where('used_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->order_by('id', 'DESC')->limit(1)
            ->get('user_tokens')->row_array();
    }

    /**
     * Còn phải chờ bao nhiêu giây nữa mới được gửi mã mới.
     * Trả về 0 nghĩa là gửi được ngay.
     */
    public function con_cho($user_id, $muc_dich)
    {
        $ma = $this->hien_hanh($user_id, $muc_dich);
        if (!$ma) {
            return 0;
        }
        $da_qua = time() - strtotime($ma['created_at']);
        return max(0, self::GIAY_CHO - $da_qua);
    }

    /**
     * Sinh mã mới và huỷ mọi mã cũ cùng mục đích.
     * Trả về mã nguyên văn để gửi email — chỉ chỗ này biết nó.
     */
    public function tao($user_id, $muc_dich)
    {
        $this->huy($user_id, $muc_dich);

        // random_int cho số ngẫu nhiên đủ an toàn, không dùng rand()
        $ma = str_pad((string) random_int(0, 999999), self::SO_CHU_SO, '0', STR_PAD_LEFT);

        $this->db->insert('user_tokens', array(
            'user_id'    => (int) $user_id,
            'type'       => $this->loai($muc_dich),
            'token'      => $this->bam($ma, $user_id),
            'expires_at' => date('Y-m-d H:i:s', time() + self::PHUT_SONG * 60),
        ));

        return $ma;
    }

    /**
     * Đối chiếu mã người dùng nhập.
     * Trả về ['ok' => bool, 'message' => string].
     */
    public function kiem_tra($user_id, $muc_dich, $ma_nhap)
    {
        $ma_nhap = preg_replace('/\D+/', '', (string) $ma_nhap);
        $hang    = $this->hien_hanh($user_id, $muc_dich);

        if (!$hang) {
            return array('ok' => false, 'message' => 'Mã đã hết hạn. Bấm "Gửi lại mã" để nhận mã mới.');
        }
        if ((int) $hang['attempts'] >= self::TOI_DA_SAI) {
            $this->huy($user_id, $muc_dich);
            return array('ok' => false, 'message' => 'Bạn đã nhập sai quá nhiều lần. Hãy yêu cầu mã mới.');
        }
        if (!hash_equals($hang['token'], $this->bam($ma_nhap, $user_id))) {
            $this->db->where('id', $hang['id'])
                ->set('attempts', 'attempts + 1', false)->update('user_tokens');

            $con_lai = self::TOI_DA_SAI - ((int) $hang['attempts'] + 1);
            return array('ok' => false, 'message' => $con_lai > 0
                ? 'Mã không đúng. Bạn còn ' . $con_lai . ' lần thử.'
                : 'Mã không đúng và bạn đã hết lượt thử. Hãy yêu cầu mã mới.');
        }

        // Đúng: đánh dấu đã dùng để không ai xài lại được mã này
        $this->db->where('id', $hang['id'])
            ->update('user_tokens', array('used_at' => date('Y-m-d H:i:s')));

        return array('ok' => true, 'message' => '');
    }

    /** Vô hiệu mọi mã chưa dùng của một người cho một mục đích. */
    public function huy($user_id, $muc_dich)
    {
        $this->db->where('user_id', (int) $user_id)
            ->where('type', $this->loai($muc_dich))
            ->where('used_at', null)
            ->update('user_tokens', array('used_at' => date('Y-m-d H:i:s')));
    }
}
