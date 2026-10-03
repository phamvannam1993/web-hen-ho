<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Huỷ đăng ký và đo lượt mở / lượt bấm.
 *
 * Mọi thứ ở đây phải dùng được khi CHƯA đăng nhập — người nhận thư bấm từ hộp
 * thư, không thể bắt họ đăng nhập rồi mới cho huỷ (CAN-SPAM và Nghị định
 * 13/2023/NĐ-CP đều yêu cầu huỷ được ngay).
 */
class Email extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_email');
    }

    /** Huỷ nhận thư bằng mã trong chân thư. */
    public function unsubscribe($token = null)
    {
        $p = $token ? $this->m_email->by_token($token) : null;
        if (!$p) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $loai = $this->input->post('loai');

            if ($loai === 'tat_ca') {
                $this->m_email->unsubscribe_all($p['user_id']);
                set_flash('success', 'Đã huỷ nhận toàn bộ email. Bạn vẫn dùng website bình thường.');
            } elseif (array_key_exists($loai, $this->cac_loai())) {
                $this->m_email->save_prefs($p['user_id'], array($loai => 0));
                set_flash('success', 'Đã tắt loại email này.');
            }
            redirect('email/huy/' . $token);
        }

        $this->render('email/unsubscribe', array(
            'title' => 'Huỷ nhận email',
            'p'     => $this->m_email->prefs($p['user_id']),
            'token' => $token,
            'loai'  => $this->cac_loai(),
        ));
    }

    /** Ảnh 1x1 trong suốt: tải được nghĩa là thư đã mở. */
    public function open($id = null)
    {
        if ($id) {
            $this->m_email->ghi_mo((int) $id);
        }

        // GIF 1x1 trong suốt, trả thẳng ra chứ không cần file trên đĩa
        $this->output
            ->set_content_type('image/gif', '')   // ảnh nhị phân, không kèm charset
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
            ->set_output(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
    }

    /** Ghi nhận lượt bấm rồi chuyển tiếp tới đích. */
    public function click($id = null)
    {
        $den = (string) $this->input->get('u');

        if ($id) {
            $this->m_email->ghi_bam((int) $id);
        }

        // Chỉ chuyển tiếp trong nội bộ site, tránh bị lợi dụng làm bàn đạp
        // chuyển hướng sang trang lừa đảo bên ngoài.
        if ($den === '' || strpos($den, base_url()) !== 0) {
            redirect('/');
        }
        redirect($den);
    }

    private function cac_loai()
    {
        return array(
            'new_message'   => 'Có tin nhắn mới',
            'notification'  => 'Ghép đôi, lượt thích, lượt xem hồ sơ',
            'match_suggest' => 'Gợi ý người phù hợp',
            're_engage'     => 'Nhắc quay lại khi lâu không vào',
        );
    }
}
