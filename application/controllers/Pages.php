<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Trang tĩnh: nội quy, điều khoản, liên hệ... */
class Pages extends MY_Controller
{
    public function policy($slug)
    {
        if (!in_array($slug, array('bao-mat', 'an-toan', 'lien-he'), true)) show_404();
        $titles = array('bao-mat' => 'Chính sách bảo mật', 'an-toan' => 'Hướng dẫn hẹn hò an toàn', 'lien-he' => 'Liên hệ Saigon Cupid');
        $this->render('pages/policy', array('title' => $titles[$slug], 'policy_slug' => $slug,
            'meta_desc' => $slug === 'lien-he' ? 'Thông tin liên hệ và hỗ trợ thành viên Saigon Cupid.' : $titles[$slug] . ' dành cho thành viên Saigon Cupid: bảo vệ thông tin cá nhân và kết nối có trách nhiệm.'));
    }

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
