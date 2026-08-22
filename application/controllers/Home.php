<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_article'));
    }

    public function index()
    {
        // Trang chủ dựng từ hồ sơ thành viên: mỗi khối là một nhóm người dùng
        // theo mục đích hẹn hò, thay vì danh sách tin đăng.
        $purposes = array(
            'hen_ho'     => 'Đang tìm người hẹn hò',
            'nghiem_tuc' => 'Muốn tìm hiểu nghiêm túc',
            'ket_hon'    => 'Sẵn sàng tiến tới hôn nhân',
            'ket_ban'    => 'Tìm bạn tâm sự',
        );

        $sections = array();
        foreach ($purposes as $key => $label) {
            $members = $this->m_user->by_purpose($key, 8);
            if ($members) {
                $sections[] = array('key' => $key, 'label' => $label, 'members' => $members);
            }
        }

        $data = array(
            'sections'       => $sections,
            'online_members' => $this->m_user->search(array('online' => 1, 'sort' => 'active'), 8)
                ?: $this->m_user->search(array('sort' => 'active'), 8),
            'new_members'    => $this->m_user->search(array('sort' => 'new'), 8),
            'top_provinces'  => $this->m_province->with_post_count(12),
            'articles'       => $this->m_article->published(4),
            'stats'          => $this->m_user->stats(),
        );

        // Người đã đăng nhập thấy gợi ý ghép đôi tính theo hồ sơ của chính họ
        if ($this->auth->check()) {
            $data['suggestions'] = $this->m_user->suggestions($this->auth->user(), 8);
        }

        $this->render('home/index', $data);
    }

    public function not_found()
    {
        $this->output->set_status_header(404);
        $this->render('errors/not_found', array('title' => 'Không tìm thấy trang'));
    }
}
