<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Admin_Controller
{
    /** Các khoá cấu hình được phép sửa, kèm nhãn và nhóm. */
    private $fields = array(
        'general'    => array(
            'site_name'   => 'Tên website',
            'site_slogan' => 'Khẩu hiệu (hiển thị ở đầu trang)',
            'site_desc'   => 'Mô tả website (SEO)',
        ),
        'contact'    => array(
            'hotline'       => 'Hotline',
            'contact_email' => 'Email hỗ trợ',
        ),
        'moderation' => array(
            'auto_approve_user' => 'Tự động kích hoạt thành viên mới (1/0)',
            'auto_approve_post' => 'Tự động duyệt tin đăng (1/0)',
            'post_expire_days'  => 'Số ngày tin hết hạn',
        ),
        'coin'       => array(
            'unlock_cost'       => 'Xu để mở liên hệ',
            'signup_bonus_coin' => 'Xu tặng khi đăng ký',
        ),
        'security'   => array(
            'require_code_register' => 'Bắt buộc mã bảo mật khi đăng ký (1/0)',
            'require_code_login'    => 'Bắt buộc mã bảo mật khi đăng nhập (1/0)',
        ),
        'payment'    => array(
            'bank_info' => 'Thông tin chuyển khoản',
        ),
    );

    public function index()
    {
        if ($this->input->method() === 'post') {
            foreach ($this->fields as $group => $keys) {
                foreach ($keys as $key => $label) {
                    $this->m_setting->set($key, $this->input->post($key, true), $group);
                }
            }
            $this->log_action('update_settings', 'settings', null);
            set_flash('success', 'Đã lưu cấu hình.');
            redirect('admin/settings');
        }

        $this->render('admin/settings/index', array(
            'title'  => 'Cấu hình hệ thống',
            'fields' => $this->fields,
            'values' => $this->m_setting->all(),
        ));
    }

    /** Nhật ký thao tác quản trị. */
    public function logs()
    {
        $this->render('admin/settings/logs', array(
            'title' => 'Nhật ký quản trị',
            'logs'  => $this->db->select('l.*, u.display_name')
                ->from('admin_logs l')->join('users u', 'u.id = l.admin_id', 'left')
                ->order_by('l.id', 'DESC')->limit(200)->get()->result_array(),
        ));
    }
}
