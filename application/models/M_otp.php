<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Link xác thực email gửi sau khi đăng ký (bấm một chạm, không phải nhập mã).
 *
 * Token lưu trong bảng user_tokens nhưng KHÔNG lưu nguyên văn — chỉ lưu bản băm.
 * Ai đọc được cơ sở dữ liệu cũng không xác thực hộ người khác được.
 */
class M_otp extends CI_Model
{
    /** Loại token cho từng mục đích. */
    const MUC_DICH = array(
        'register' => 'verify_email',
        'login'    => 'otp',
    );

    const GIAY_CHO = 60;   // phải chờ bao lâu mới được gửi lại

    private function loai($muc_dich)
    {
        return self::MUC_DICH[$muc_dich] ?? self::MUC_DICH['login'];
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

    /* ============ Link xác thực email một chạm ============ */

    const GIO_SONG_LINK = 48;   // link trong thư sống bao lâu

    /** Băm token của link — tra theo bản băm nên không cần biết user_id trước. */
    private function bam_link($token)
    {
        return hash('sha256', 'link|' . $token . '|' . config_item('encryption_key'));
    }

    /**
     * Sinh link xác thực email mới (huỷ link/mã cũ cùng loại).
     * Trả về token nguyên văn để ghép vào URL — DB chỉ giữ bản băm.
     */
    public function tao_link($user_id)
    {
        $this->huy($user_id, 'register');

        $token = bin2hex(random_bytes(24));
        $this->db->insert('user_tokens', array(
            'user_id'    => (int) $user_id,
            'type'       => $this->loai('register'),
            'token'      => $this->bam_link($token),
            'expires_at' => date('Y-m-d H:i:s', time() + self::GIO_SONG_LINK * 3600),
        ));
        return $token;
    }

    /**
     * Dùng link: đúng và còn hạn thì đánh dấu đã dùng, trả về user_id;
     * sai hoặc hết hạn trả về null.
     */
    public function dung_link($token)
    {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) $token));
        if ($token === '') {
            return null;
        }
        $hang = $this->db->where('token', $this->bam_link($token))
            ->where('type', $this->loai('register'))
            ->where('used_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('user_tokens')->row_array();
        if (!$hang) {
            return null;
        }
        $this->db->where('id', $hang['id'])
            ->update('user_tokens', array('used_at' => date('Y-m-d H:i:s')));
        return (int) $hang['user_id'];
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
