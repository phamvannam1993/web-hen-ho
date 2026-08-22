<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller
{
    public function index()
    {
        $this->load->model(array('m_user', 'm_post', 'm_billing', 'm_report'));

        // Biểu đồ 14 ngày gần nhất: thành viên mới và tin đăng mới
        $chart = array();
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $chart[] = array(
                'day'   => date('d/m', strtotime($day)),
                'users' => (int) $this->db->where('DATE(created_at)', $day)->count_all_results('users'),
                'posts' => (int) $this->db->where('DATE(created_at)', $day)->count_all_results('posts'),
            );
        }

        $this->render('admin/dashboard', array(
            'title'          => 'Tổng quan',
            'user_stats'     => $this->m_user->stats(),
            'post_stats'     => $this->m_post->stats(),
            'revenue'        => $this->m_billing->revenue_stats(),
            'new_reports'    => $this->m_report->new_count(),
            'chart'          => $chart,
            'pending_posts'  => $this->m_post->admin_list(array('status' => 'pending'), 8, 0),
            'recent_users'   => $this->m_user->admin_list(array(), 8, 0),
            'recent_orders'  => $this->m_billing->admin_orders(array(), 8, 0),
        ));
    }
}
