<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chỗ duy nhất biết cách dựng từng loại email của hệ thống.
 *
 * Nơi gọi chỉ cần nói "có tin nhắn mới" hay "vừa ghép đôi", lớp này lo tiêu đề,
 * dữ liệu cho mẫu thư và các luật chống spam. Thư không gửi ngay mà xếp vào
 * hàng đợi; tiến trình chạy bằng cron mới thực sự gửi đi.
 */
class Emailer
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('m_email', 'm_user'));
    }

    private function ten($user)
    {
        // Không có tên thì gọi là "bạn" theo quy tắc dự phòng trong đặc tả
        $t = trim((string) (is_array($user) ? display_name($user) : $user));
        return $t !== '' ? $t : 'bạn';
    }

    /* ===================== Các loại email ===================== */

    /** Chào mừng người mới, gửi sau 1 phút. */
    public function welcome($user_id)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u) { return false; }

        return $this->CI->m_email->enqueue(
            $user_id, 'welcome',
            'Chào mừng ' . $this->ten($u) . ' đến với ' . setting('site_name', 'Saigon Cupid'),
            'welcome',
            array('name' => $this->ten($u), 'link' => site_url('swipe-match')),
            array('delay_minutes' => 1)
        );
    }

    /** Mỗi tin nhắn tạo một email thông báo, không chứa nội dung tin nhắn. */
    public function new_message($receiver_id, $sender, $conversation_id)
    {
        return $this->CI->m_email->enqueue(
            $receiver_id, 'new_message',
            $this->ten($sender) . ' đã nhắn tin cho bạn',
            'new_message',
            array(
                'sender'  => $this->ten($sender),
                'avatar'  => avatar_url($sender['avatar'] ?? null, $sender['gender'] ?? 'other'),
                'link'    => site_url('tai-khoan/tin-nhan/' . (int) $conversation_id),
            ),
            array('related_id' => $conversation_id)
        );
    }

    /** Ghép đôi thành công — gửi ngay cho cả hai. */
    public function matched($a_id, $b_id)
    {
        $a = $this->CI->m_user->find($a_id);
        $b = $this->CI->m_user->find($b_id);
        if (!$a || !$b) { return false; }

        foreach (array(array($a, $b), array($b, $a)) as $cap) {
            list($nguoi, $doi) = $cap;
            $this->CI->m_email->enqueue(
                $nguoi['id'], 'notify_match',
                $this->ten($nguoi) . ' và ' . $this->ten($doi) . ' đã match!',
                'notify_match',
                array(
                    'name'         => $this->ten($nguoi),
                    'match_name'   => $this->ten($doi),
                    'my_avatar'    => avatar_url($nguoi['avatar'], $nguoi['gender']),
                    'match_avatar' => avatar_url($doi['avatar'], $doi['gender']),
                    'link'         => site_url('tai-khoan/tin-nhan'),
                ),
                array('related_id' => $doi['id'])
            );
        }
        return true;
    }

    /** Gom lượt thích trong ngày thành một thư, chạy lúc 20h. */
    public function batch_likes($user_id, $so, array $avatars, $la_vip = false)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u || $so < 1) { return false; }

        return $this->CI->m_email->enqueue(
            $user_id, 'notify_like',
            $this->ten($u) . ', bạn có ' . (int) $so . ' người mới thích hồ sơ!',
            'notify_like',
            array(
                'name'    => $this->ten($u),
                'so'      => (int) $so,
                'avatars' => $avatars,
                'mo_anh'  => (bool) $la_vip,
                'link'    => site_url('tai-khoan/quan-tam'),
            )
        );
    }

    /** Gom lượt xem hồ sơ trong ngày. */
    public function batch_views($user_id, $so)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u || $so < 1) { return false; }

        return $this->CI->m_email->enqueue(
            $user_id, 'notify_view',
            (int) $so . ' người đã xem hồ sơ bạn',
            'notify_view',
            array('name' => $this->ten($u), 'so' => (int) $so, 'link' => site_url('tai-khoan/quan-tam'))
        );
    }

    /** Gợi ý ghép đôi định kỳ. */
    public function match_suggest($user_id, array $match, $score)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u) { return false; }

        // Thiếu trường nào thì bỏ hẳn phần đó trong thư
        $meta = array_filter(array(
            $match['province_name'] ?? null,
            $match['job'] ?? null,
        ));
        $tags = !empty($match['interest_names'])
            ? array_slice(explode('|', $match['interest_names']), 0, 5)
            : array();

        // Hai cách viết tiêu đề, so xem cách nào được mở nhiều hơn
        list($tieu_de, $nhanh) = $this->CI->m_email->chon_nhanh($user_id, array(
            'A' => $this->ten($u) . ', người phù hợp với bạn hôm nay là ' . $this->ten($match),
            'B' => 'Có thể bạn sẽ thích ' . $this->ten($match) . ' — độ phù hợp ' . (int) $score . '%',
        ));

        return $this->CI->m_email->enqueue(
            $user_id, 'match_suggest',
            $tieu_de,
            'match_suggest',
            array(
                'name'         => $this->ten($u),
                'match_name'   => $this->ten($match),
                'match_avatar' => avatar_url($match['avatar'], $match['gender']),
                'age'          => age_from($match['birthday'] ?? null),
                'meta'         => array_values($meta),
                'tags'         => $tags,
                'bio'          => !empty($match['bio']) ? mb_substr(trim($match['bio']), 0, 150) : '',
                'score'        => (int) $score,
                'link'         => site_url('profile/' . $match['slug']),
                'link_like'    => site_url('swipe-match'),
            ),
            array('related_id' => $match['id'], 'variant' => $nhanh)
        );
    }

    /**
     * Nhắc hoàn thiện hồ sơ — gửi sau 1 ngày (lần 1) và 3 ngày (lần 2) kể từ
     * khi đăng ký, nếu hồ sơ vẫn chưa đủ để hiện công khai. Nói lợi ích, không
     * nói nghĩa vụ.
     *
     * @param array $thieu nhãn các mục còn thiếu (M_user::thieu_thong_tin)
     */
    public function profile_nudge($user_id, $lan, array $thieu)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u || !$thieu) { return false; }

        $tieu_de = $lan <= 1
            ? $this->ten($u) . ' ơi, thêm một tấm ảnh để mọi người thấy bạn nhé'
            : 'Hồ sơ của ' . $this->ten($u) . ' vẫn đang bị ẩn — chỉ cần 1 phút';

        return $this->CI->m_email->enqueue(
            $user_id, 'profile_nudge',
            $tieu_de,
            'profile_nudge',
            array(
                'name'  => $this->ten($u),
                'lan'   => (int) $lan,
                'thieu' => array_values($thieu),
                'link'  => site_url('tai-khoan/bat-dau'),
            )
        );
    }

    /** Kéo người đã lâu không vào quay lại. */
    public function re_engage($user_id, $so_thich, array $avatars)
    {
        $u = $this->CI->m_user->find($user_id);
        if (!$u) { return false; }

        list($tieu_de, $nhanh) = $this->CI->m_email->chon_nhanh($user_id, array(
            'A' => $so_thich > 0
                ? $this->ten($u) . ' ơi, có ' . (int) $so_thich . ' người mới thích bạn!'
                : $this->ten($u) . ' ơi, có người đang chờ bạn quay lại!',
            'B' => $so_thich > 0
                ? 'Bạn đang bỏ lỡ ' . (int) $so_thich . ' người quan tâm mình'
                : 'Lâu rồi không gặp — có gì mới cho bạn đây',
        ));

        return $this->CI->m_email->enqueue(
            $user_id, 're_engage',
            $tieu_de,
            're_engage',
            array(
                'name'    => $this->ten($u),
                'so'      => (int) $so_thich,
                'avatars' => $avatars,
                'link'    => site_url('swipe-match'),
            ),
            array('variant' => $nhanh)
        );
    }
}
