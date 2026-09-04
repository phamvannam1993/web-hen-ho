<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Trang tĩnh: nội quy, điều khoản, liên hệ... */
class Pages extends MY_Controller
{
    /**
     * Đường dẫn cũ /trang/{slug} nay chuyển hẳn sang /{slug}.
     * Dùng mã 301 để công cụ tìm kiếm dời thứ hạng sang địa chỉ mới, không mất SEO.
     */
    public function view($slug)
    {
        $page = $this->db->where('slug', $slug)->where('is_active', 1)->get('pages')->row_array();
        if (!$page) {
            show_404();
        }
        redirect($page['slug'], 'location', 301);
    }
}
