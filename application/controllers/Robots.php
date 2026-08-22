<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sinh robots.txt theo cấu hình, thay cho file tĩnh.
 *
 * Khi bật "Chặn Google lập chỉ mục" trong Quản trị -> Cấu hình,
 * toàn bộ website bị cấm thu thập. Khi tắt thì chỉ chặn các khu vực
 * riêng tư như trang quản trị, tài khoản cá nhân, tệp nội bộ.
 */
class Robots extends CI_Controller
{
    public function index()
    {
        $this->load->model('m_setting');
        $settings = $this->m_setting->all();
        $noindex  = ($settings['site_noindex'] ?? '1') === '1';

        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_output(robots_content($noindex));
    }
}
