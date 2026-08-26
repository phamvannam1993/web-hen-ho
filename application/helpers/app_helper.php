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
        return $nick !== '' ? $nick : ($user['display_name'] ?? '');
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
            '/dat-lai-mat-khau', '/lay-pass', '/ajax', '/kham-pha',
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
