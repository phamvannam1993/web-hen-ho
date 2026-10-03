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
 * Vẽ thẳng SVG thay vì nạp một thư viện icon: khu này chỉ dùng vài chục hình,
 * mà thêm một tệp font/JS nữa thì mỗi trang phải tải thêm vài chục KB. Nét vẽ
 * chép nguyên từ bộ Lucide (giấy phép ISC) mà bản thiết kế đang dùng, nên hai
 * bên giống hệt nhau. Khoá bên trái là tên ngắn dùng trong view.
 *
 * Trả về chuỗi rỗng khi không có tên đó — thiếu một hình thì giao diện vẫn
 * dựng, không làm vỡ cả trang.
 */
function tk_icon($ten, $lop = 'tk-ic')
{
    static $hinh = array(
        'dashboard'     => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'user'          => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'images'        => '<path d="m22 11-1.296-1.296a2.4 2.4 0 0 0-3.408 0L11 16"/><path d="M4 8a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2"/><circle cx="13" cy="7" r="1" fill="currentColor"/><rect x="8" y="2" width="14" height="14" rx="2"/>',
        'match'         => '<path d="M19.414 14.414C21 12.828 22 11.5 22 9.5a5.5 5.5 0 0 0-9.591-3.676.6.6 0 0 1-.818.001A5.5 5.5 0 0 0 2 9.5c0 2.3 1.5 4 3 5.5l5.535 5.362a2 2 0 0 0 2.879.052 2.12 2.12 0 0 0-.004-3 2.124 2.124 0 1 0 3-3 2.124 2.124 0 0 0 3.004 0 2 2 0 0 0 0-2.828l-1.881-1.882a2.41 2.41 0 0 0-3.409 0l-1.71 1.71a2 2 0 0 1-2.828 0 2 2 0 0 1 0-2.828l2.823-2.762"/>',
        'heart'         => '<path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/>',
        'eye'           => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'       => '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>',
        'message'       => '<path d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719"/>',
        'flame'         => '<path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>',
        'sparkles'      => '<path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/>',
        'bell'          => '<path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>',
        'mail'          => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/>',
        'mail-x'        => '<path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h9"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="m17 17 4 4"/><path d="m21 17-4 4"/>',
        'wallet'        => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'key'           => '<path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/>',
        'coins'         => '<path d="M13.744 17.736a6 6 0 1 1-7.48-7.48"/><path d="M15 6h1v4"/><path d="m6.134 14.768.866-.5 2 3.464"/><circle cx="16" cy="8" r="6"/>',
        'crown'         => '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>',
        'file'          => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'check'         => '<path d="M20 6 9 17l-5-5"/>',
        'check-check'   => '<path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/>',
        'pin'           => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'briefcase'     => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
        'x'             => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'arrow'         => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-left'    => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'inbox'         => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'menu'          => '<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/>',
        'panel-close'   => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/>',
        'panel-open'    => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/>',
        'camera'        => '<path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/>',
        'save'          => '<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>',
        'lock'          => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'search'        => '<path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>',
        'send'          => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>',
        'star'          => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',
        'trash'         => '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'trophy'        => '<path d="M10 14.66v1.626a2 2 0 0 1-.976 1.696A5 5 0 0 0 7 21.978"/><path d="M14 14.66v1.626a2 2 0 0 0 .976 1.696A5 5 0 0 1 17 21.978"/><path d="M18 9h1.5a1 1 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M6 9a6 6 0 0 0 12 0V3a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1z"/><path d="M6 9H4.5a1 1 0 0 1 0-5H6"/>',
        'upload'        => '<path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>',
        'snowflake'     => '<path d="m10 20-1.25-2.5L6 18"/><path d="M10 4 8.75 6.5 6 6"/><path d="m14 20 1.25-2.5L18 18"/><path d="m14 4 1.25 2.5L18 6"/><path d="m17 21-3-6h-4"/><path d="m17 3-3 6 1.5 3"/><path d="M2 12h6.5L10 9"/><path d="m20 10-1.5 2 1.5 2"/><path d="M22 12h-6.5L14 15"/><path d="m4 10 1.5 2L4 14"/><path d="m7 21 3-6-1.5-3"/><path d="m7 3 3 6h4"/>',
        'shield'        => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'shield-alert'  => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'alert'         => '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>',
        'ban'           => '<circle cx="12" cy="12" r="10"/><path d="M4.929 4.929 19.07 19.071"/>',
        'flag'          => '<path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528"/>',
        'clock'         => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'card'          => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
        'bank'          => '<path d="M10 18v-7"/><path d="M11.12 2.198a2 2 0 0 1 1.76.006l7.866 3.847c.476.233.31.949-.22.949H3.474c-.53 0-.695-.716-.22-.949z"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="M3 22h18"/><path d="M6 18v-7"/>',
        'grip'          => '<circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>',
        'more'          => '<circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>',
        'plus'          => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'home'          => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'logout'        => '<path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
        'image'         => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'gift'          => '<path d="M12 7v14"/><path d="M20 11v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8"/><path d="M7.5 7a1 1 0 0 1 0-5A4.8 8 0 0 1 12 7a4.8 8 0 0 1 4.5-5 1 1 0 0 1 0 5"/><rect x="3" y="7" width="18" height="4" rx="1"/>',
        'calendar'      => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'edit'          => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
        'refresh'       => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'zap'           => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'phone'         => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"/>',
        'smile'         => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
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
 * Cột `users.profile_score` chỉ được ghi lại khi lưu hồ sơ nên hay lệch; tính
 * tại chỗ thì Tổng quan, trang Hồ sơ và banner nhắc nói cùng một con số.
 * `M_user::recalc_profile_score()` cũng gọi hàm này, nên hai bên không lệch.
 *
 * Trọng số theo mức ảnh hưởng tới việc được thích lại: ảnh 30, sở thích 30,
 * giới thiệu 20, khu vực 20. Sở thích nằm ở bảng riêng — truyền sẵn số lượng
 * vào $so_so_thich để khỏi truy vấn lại, bỏ trống thì hàm tự đếm.
 *
 * Trả về mảng: `phan_tram`, `thieu` (mã mục → nhãn tiếng Việt).
 */
function tk_ho_so_day_du(array $u, $so_so_thich = null)
{
    if ($so_so_thich === null) {
        $CI =& get_instance();
        $so_so_thich = empty($u['id']) ? 0
            : (int) $CI->db->where('user_id', (int) $u['id'])->count_all_results('user_interests');
    }

    $muc = array(
        // mã => array(nhãn, trọng số, đã có chưa)
        'avatar'      => array('Ảnh đại diện',        30, !empty($u['avatar'])),
        'bio'         => array('Giới thiệu bản thân', 20, trim((string) ($u['bio'] ?? '')) !== ''),
        'province_id' => array('Khu vực',             20, !empty($u['province_id'])),
        'interests'   => array('Sở thích',            30, $so_so_thich > 0),
    );

    $diem  = 0;
    $thieu = array();
    foreach ($muc as $k => $m) {
        if ($m[2]) {
            $diem += $m[1];
        } else {
            $thieu[$k] = $m[0];
        }
    }
    return array('phan_tram' => $diem, 'thieu' => $thieu);
}

/** Biểu tượng (tên trong tk_icon) cho từng loại thông báo. */
function tk_noti_icon($type)
{
    $map = array(
        'like' => 'heart', 'match' => 'match', 'message' => 'message', 'comment' => 'message',
        'view' => 'eye', 'post_approved' => 'check', 'post_rejected' => 'alert',
        'coin' => 'coins', 'vip' => 'crown', 'streak' => 'flame',
    );
    return $map[$type] ?? 'bell';
}

/** Lời chào theo giờ trong ngày: "Chào buổi sáng" / "chiều" / "tối". */
function tk_loi_chao()
{
    $h = (int) date('G');
    return $h < 11 ? 'Chào buổi sáng' : ($h < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');
}
