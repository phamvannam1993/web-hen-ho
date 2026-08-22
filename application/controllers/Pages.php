<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Trang tĩnh: nội quy, điều khoản, liên hệ... */
class Pages extends MY_Controller
{
    public function view($slug)
    {
        $page = $this->db->where('slug', $slug)->where('is_active', 1)->get('pages')->row_array();
        if (!$page) {
            show_404();
        }

        $this->render('pages/view', array(
            'title'      => $page['title'],
            'meta_desc'  => excerpt($page['content'], 160),
            'page'       => $page,
            'other_pages' => $this->db->select('title, slug')->where('is_active', 1)
                ->where('id !=', $page['id'])->order_by('id')->get('pages')->result_array(),
        ));
    }
}
