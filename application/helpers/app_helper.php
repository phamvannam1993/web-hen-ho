<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('e')) {
    /** Escape HTML. */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    function slugify($text)
    {
        $map = array(
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'e' => 'éèẻẽẹêếềểễệ', 'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ', 'u' => 'úùủũụưứừửữự', 'y' => 'ýỳỷỹỵ', 'd' => 'đ',
        );
        $text = mb_strtolower(trim($text), 'UTF-8');
        foreach ($map as $latin => $chars) {
            $text = preg_replace('/[' . $chars . ']/u', $latin, $text);
        }
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('unique_slug')) {
    /** Sinh slug không trùng trong bảng chỉ định. */
    function unique_slug($table, $text, $ignore_id = null)
    {
        $CI =& get_instance();
        $base = slugify($text) ?: 'item';
        $slug = $base;
        $i = 1;
        while (true) {
            $CI->db->where('slug', $slug);
            if ($ignore_id) {
                $CI->db->where('id !=', $ignore_id);
            }
            if ($CI->db->count_all_results($table) === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }
}

if (!function_exists('set_flash')) {
    function set_flash($type, $message)
    {
        get_instance()->session->set_flashdata('flash', array('type' => $type, 'message' => $message));
    }
}

if (!function_exists('age_from')) {
    function age_from($birthday)
    {
        if (!$birthday || $birthday === '0000-00-00') {
            return null;
        }
        return (new DateTime($birthday))->diff(new DateTime())->y;
    }
}

if (!function_exists('gender_label')) {
    function gender_label($gender)
    {
        $map = array('male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', 'all' => 'Tất cả');
        return $map[$gender] ?? 'Khác';
    }
}

if (!function_exists('purpose_label')) {
    function purpose_label($purpose)
    {
        $map = array(
            'ket_ban'    => 'Kết bạn',
            'hen_ho'     => 'Hẹn hò',
            'nghiem_tuc' => 'Tìm hiểu nghiêm túc',
            'ket_hon'    => 'Tiến tới hôn nhân',
        );
        return $map[$purpose] ?? $purpose;
    }
}

if (!function_exists('status_label')) {
    function status_label($status)
    {
        $map = array(
            'pending'  => array('Chờ duyệt', 'warning'),
            'approved' => array('Đã duyệt', 'success'),
            'rejected' => array('Từ chối', 'danger'),
            'expired'  => array('Hết hạn', 'secondary'),
            'hidden'   => array('Đã ẩn', 'secondary'),
            'draft'    => array('Nháp', 'secondary'),
            'active'   => array('Hoạt động', 'success'),
            'locked'   => array('Tạm khoá', 'warning'),
            'banned'   => array('Cấm', 'danger'),
            'paid'     => array('Đã thanh toán', 'success'),
            'failed'   => array('Thất bại', 'danger'),
            'new'      => array('Mới', 'danger'),
            'resolved' => array('Đã xử lý', 'success'),
        );
        $item = $map[$status] ?? array($status, 'secondary');
        return '<span class="badge bg-' . $item[1] . '">' . e($item[0]) . '</span>';
    }
}

if (!function_exists('time_ago')) {
    function time_ago($datetime)
    {
        if (!$datetime) {
            return 'chưa rõ';
        }
        $diff = time() - strtotime($datetime);
        if ($diff < 60)     return 'vừa xong';
        if ($diff < 3600)   return floor($diff / 60) . ' phút trước';
        if ($diff < 86400)  return floor($diff / 3600) . ' giờ trước';
        if ($diff < 2592000) return floor($diff / 86400) . ' ngày trước';
        return date('d/m/Y', strtotime($datetime));
    }
}

if (!function_exists('is_online')) {
    function is_online($last_active_at)
    {
        return $last_active_at && strtotime($last_active_at) > time() - 300;
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url($path, $gender = 'other')
    {
        if ($path) {
            return base_url(ltrim($path, '/'));
        }
        $file = $gender === 'female' ? 'avatar-female.svg' : ($gender === 'male' ? 'avatar-male.svg' : 'avatar-other.svg');
        return base_url('assets/site/img/' . $file);
    }
}

if (!function_exists('money')) {
    function money($amount)
    {
        return number_format((float) $amount, 0, ',', '.') . 'đ';
    }
}

if (!function_exists('mask_contact')) {
    /** Che bớt thông tin liên hệ khi chưa mở khoá. */
    function mask_contact($value)
    {
        $value = (string) $value;
        $len = mb_strlen($value);
        if ($len <= 3) {
            return str_repeat('*', max($len, 3));
        }
        return mb_substr($value, 0, 3) . str_repeat('*', max($len - 3, 3));
    }
}

if (!function_exists('excerpt')) {
    function excerpt($text, $limit = 160)
    {
        $text = trim(strip_tags((string) $text));
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '…' : $text;
    }
}

if (!function_exists('setting')) {
    function setting($key, $default = '')
    {
        $CI =& get_instance();
        $CI->load->model('m_setting');
        $all = $CI->m_setting->all();
        return $all[$key] ?? $default;
    }
}

if (!function_exists('pagination_links')) {
    /** Phân trang đơn giản: trả về HTML. */
    function pagination_links($base_url, $page, $total, $per_page, $query = array())
    {
        $pages = (int) ceil($total / max($per_page, 1));
        if ($pages <= 1) {
            return '';
        }
        $qs = $query ? '?' . http_build_query($query) : '';
        $link = function ($p) use ($base_url, $qs) {
            return site_url($base_url . ($p > 1 ? '/trang/' . $p : '')) . $qs;
        };
        $html = '<nav><ul class="pagination">';
        $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $link(max($page - 1, 1)) . '">‹</a></li>';
        $start = max(1, $page - 2);
        $end   = min($pages, $start + 4);
        for ($p = $start; $p <= $end; $p++) {
            $html .= '<li class="page-item ' . ($p === $page ? 'active' : '') . '"><a class="page-link" href="' . $link($p) . '">' . $p . '</a></li>';
        }
        $html .= '<li class="page-item ' . ($page >= $pages ? 'disabled' : '') . '"><a class="page-link" href="' . $link(min($page + 1, $pages)) . '">›</a></li>';
        return $html . '</ul></nav>';
    }
}

if (!function_exists('display_name')) {
    /**
     * Tên hiển thị công khai của thành viên: ưu tiên biệt danh,
     * không có thì dùng tên đầy đủ. Dùng ở mọi nơi hiển thị ra ngoài.
     */
    function display_name($user)
    {
        $nick = trim((string) ($user['nickname'] ?? ''));
        $ten  = $nick !== '' ? $nick : ($user['display_name'] ?? '');

        // Viết hoa chữ cái đầu mỗi từ, giữ nguyên phần còn lại để những tên
        // vốn viết hoa toàn bộ (NAM, TP...) không bị hạ xuống chữ thường.
        $tu = preg_split('/(\s+)/u', trim($ten), -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($tu as $i => $t) {
            if ($t === '' || preg_match('/^\s+$/u', $t)) continue;
            $tu[$i] = mb_strtoupper(mb_substr($t, 0, 1, 'UTF-8'), 'UTF-8')
                    . mb_substr($t, 1, null, 'UTF-8');
        }
        return implode('', $tu);
    }
}

if (!function_exists('robots_content')) {
    /**
     * Sinh nội dung robots.txt.
     *
     * @param bool $noindex TRUE = chặn toàn bộ website khỏi công cụ tìm kiếm
     */
    function robots_content($noindex)
    {
        $lines = array('User-agent: *');

        if ($noindex) {
            $lines[] = 'Disallow: /';
            return implode("\n", $lines) . "\n";
        }

        // Cho phép lập chỉ mục, nhưng giấu các khu vực riêng tư
        foreach (array(
            '/admin', '/tai-khoan', '/dang-nhap', '/dang-ky', '/quen-mat-khau',
            '/dat-lai-mat-khau', '/lay-pass', '/ajax',
            '/application', '/system', '/writable', '/database',
        ) as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . base_url('sitemap.xml');

        return implode("\n", $lines) . "\n";
    }
}


if (!function_exists('mask_email')) {
    /** Che bớt địa chỉ email khi hiển thị: nguyenvana@gmail.com -> ngu***@gmail.com */
    function mask_email($email)
    {
        $email = (string) $email;
        $at = strpos($email, '@');
        if ($at === false) {
            return $email;
        }
        $name   = substr($email, 0, $at);
        $domain = substr($email, $at);
        $keep   = min(3, max(1, (int) floor(mb_strlen($name) / 2)));

        return mb_substr($name, 0, $keep) . str_repeat('*', 3) . $domain;
    }
}

if (!function_exists('zodiac')) {
    /** Cung hoàng đạo theo ngày sinh, dùng trên thẻ Khám phá. */
    function zodiac($birthday)
    {
        if (!$birthday) {
            return '';
        }
        $t = strtotime($birthday);
        $md = (int) date('nd', $t);   // tháng*100 + ngày

        $cung = array(
            array(120, 'Ma Kết'), array(218, 'Bảo Bình'), array(320, 'Song Ngư'),
            array(419, 'Bạch Dương'), array(520, 'Kim Ngưu'), array(620, 'Song Tử'),
            array(722, 'Cự Giải'), array(822, 'Sư Tử'), array(922, 'Xử Nữ'),
            array(1022, 'Thiên Bình'), array(1121, 'Bọ Cạp'), array(1221, 'Nhân Mã'),
            array(1231, 'Ma Kết'),
        );
        foreach ($cung as $c) {
            if ($md <= $c[0]) {
                return $c[1];
            }
        }
        return '';
    }
}

if (!function_exists('chuan_hoa_dien_thoai')) {
    /**
     * Đưa số điện thoại về dạng chuẩn 10 chữ số bắt đầu bằng 0.
     * Chấp nhận người dùng gõ có dấu cách, dấu chấm, gạch ngang, +84 hay 84.
     * Trả về chuỗi rỗng nếu không phải số di động Việt Nam hợp lệ.
     *
     * Đầu số di động đang dùng: 03, 05, 07, 08, 09.
     */
    function chuan_hoa_dien_thoai($so)
    {
        $so = preg_replace('/[\s.\-()]/', '', (string) $so);

        if (strpos($so, '+84') === 0) {
            $so = '0' . substr($so, 3);
        } elseif (strpos($so, '84') === 0 && strlen($so) === 11) {
            $so = '0' . substr($so, 2);
        }

        return preg_match('/^0[35789][0-9]{8}$/', $so) ? $so : '';
    }
}

if (!function_exists('dong_bo_mui_gio_db')) {
    /**
     * Bắt MySQL dùng đúng múi giờ mà PHP đang chạy.
     *
     * Trong mã nguồn có chỗ ghi thời gian bằng PHP (date('Y-m-d H:i:s')), có
     * chỗ để MySQL tự điền (DEFAULT CURRENT_TIMESTAMP). Nếu hai bên lệch múi
     * giờ thì cùng một thời điểm ra hai con số khác nhau, kéo theo:
     *   - tin vừa gửi bị ghi là "7 giờ trước"
     *   - thứ tự hội thoại sắp sai
     *   - hạn mã OTP và thời gian chờ gửi lại tính sai
     *
     * Gửi đúng độ lệch hiện tại (tự đúng cả khi có giờ mùa hè) thay vì ghi
     * cứng '+07:00', để máy chủ đặt múi giờ nào cũng chạy đúng.
     */
    function dong_bo_mui_gio_db($CI)
    {
        if (!isset($CI->db)) {
            return;
        }
        $lech = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
        try {
            $CI->db->query('SET time_zone = ' . $CI->db->escape($lech));
        } catch (Exception $e) {
            // Máy chủ không cho đổi thì thôi, chỉ ghi log chứ không làm sập trang
            log_message('error', 'Không đặt được time_zone cho MySQL: ' . $e->getMessage());
        }
    }
}

/**
 * Biểu tượng nét (stroke) cho khu Tài khoản.
 *
 * Vẽ thẳng SVG thay vì nạp một thư viện icon: khu này chỉ dùng chừng 20 hình,
 * mà thêm một tệp font/JS nữa thì mỗi trang phải tải thêm vài chục KB cho vài
 * hình. Hình lấy theo bộ Lucide (giấy phép ISC) để trùng với bản thiết kế.
 *
 * Trả về chuỗi rỗng khi không có tên đó — thiếu một hình thì giao diện vẫn
 * dựng, không làm vỡ cả trang.
 */
function tk_icon($ten, $lop = 'tk-ic')
{
    static $hinh = array(
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'images'    => '<rect x="3" y="3" width="14" height="14" rx="2"/><circle cx="8" cy="8" r="1.5"/><path d="m3 14 4-4 4 4 3-3 3 3"/><path d="M21 7v12a2 2 0 0 1-2 2H7"/>',
        'match'     => '<path d="M11 14 9.5 12.5a2.1 2.1 0 0 1 3-3l.5.5.5-.5a2.1 2.1 0 0 1 3 3L15 14"/><path d="M20 12a8 8 0 1 1-8-8"/>',
        'heart'     => '<path d="M19 14c1.5-1.5 2-3.2 2-4.7A4.3 4.3 0 0 0 16.7 5c-1.6 0-2.9.9-3.7 2-0.8-1.1-2.1-2-3.7-2A4.3 4.3 0 0 0 5 9.3C5 12 8 15 12 19c2-2 5-4.2 7-5Z"/>',
        'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'message'   => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9.9 9.9 0 0 1-4-.8L3 21l1.9-4.7A8.4 8.4 0 0 1 12 3.5a8.4 8.4 0 0 1 9 8Z"/>',
        'flame'     => '<path d="M12 22c4 0 7-2.8 7-6.5 0-4-3-5.5-3-9.5-2 1-3 2.5-3 4.5C11 8 9.5 6 8 4.5 8 8 5 9.5 5 15.5 5 19.2 8 22 12 22Z"/>',
        'sparkles'  => '<path d="m12 3 1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9Z"/><path d="m18.5 15.5.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9Z"/>',
        'bell'      => '<path d="M18 8a6 6 0 1 0-12 0c0 6-3 7-3 7h18s-3-1-3-7"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'wallet'    => '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><circle cx="17" cy="13" r="1.4"/>',
        'key'       => '<circle cx="7.5" cy="15.5" r="3.5"/><path d="m10 13 8-8 3 3-2 2-2-2-1.5 1.5 2 2L15 14"/>',
        'coins'     => '<circle cx="9" cy="8" r="5"/><path d="M14.7 4.2a5 5 0 0 1 0 15.6"/><path d="M9 3v10"/>',
        'crown'     => '<path d="m3 7 4 4 5-7 5 7 4-4-2 12H5Z"/>',
        'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/>',
        'check'     => '<path d="m4 12 5 5L20 6"/>',
        'pin'       => '<path d="M20 10c0 5.5-8 12-8 12S4 15.5 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'arrow'     => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'inbox'     => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6Z"/>',
    );
    if (!isset($hinh[$ten])) {
        return '';
    }
    return '<svg class="' . $lop . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $hinh[$ten] . '</svg>';
}

/**
 * Phần trăm hoàn thiện hồ sơ, tính TẠI CHỖ từ dữ liệu người dùng.
 *
 * Cột `users.profile_score` chỉ được ghi lại khi người dùng bấm lưu hồ sơ, nên
 * dữ liệu cũ hay lệch: đã gặp hồ sơ khai đủ 8/8 trường mà cột vẫn ghi 76%,
 * khiến trang Tổng quan hiện "76% · Đã đầy đủ" tự mâu thuẫn, còn trang Hồ sơ
 * lại nói một con số khác. Tính tại chỗ thì mọi nơi nói cùng một chuyện.
 *
 * Tám trường dưới đây PHẢI trùng với `M_user::recalc_profile_score()`.
 * Trả về mảng: `phan_tram`, `thieu` (mã trường → nhãn tiếng Việt).
 */
function tk_ho_so_day_du(array $u)
{
    $truong = array(
        'avatar'         => 'Ảnh đại diện',
        'bio'            => 'Giới thiệu bản thân',
        'birthday'       => 'Ngày sinh',
        'province_id'    => 'Tỉnh/thành',
        'job'            => 'Nghề nghiệp',
        'height_cm'      => 'Chiều cao',
        'marital_status' => 'Tình trạng hôn nhân',
        'education'      => 'Học vấn',
    );
    $thieu = array();
    foreach ($truong as $k => $nhan) {
        if (empty($u[$k])) $thieu[$k] = $nhan;
    }
    return array(
        'phan_tram' => (int) round((count($truong) - count($thieu)) / count($truong) * 100),
        'thieu'     => $thieu,
    );
}
