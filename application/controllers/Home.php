<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_interaction'));
    }

    public function index()
    {
        $data = array(
            'allow_index'    => true,
            // Tiêu đề riêng cho trang chủ, sửa được ở Quản trị -> Cấu hình
            'meta_title'     => 'Saigon Cupid - Cộng đồng tìm kiếm đối tượng hẹn hò và bạn bè',
            'stats'          => $this->m_user->stats(),
            // Con số cho dải thống kê cuối trang
            'couple_count'   => (int) $this->db->count_all('matches'),
            'province_count' => count($this->data['provinces']),
            'liked_me'       => array(),
            'liked_me_total' => 0,
            'suggestions'    => array(),
            'matched_ids'    => array(),
        );

        // Người đã đăng nhập thấy thêm hai khối riêng: ai đã thích mình và
        // gợi ý ghép đôi tính theo hồ sơ của chính họ.
        if ($this->auth->check()) {
            $me = $this->auth->user();
            $data['suggestions']    = $this->m_user->suggestions($me, 10);
            $data['liked_me']       = $this->m_interaction->liked_me($me['id'], 5);
            $data['liked_me_total'] = $this->m_interaction->liked_me_count($me['id']);

            // Đánh dấu ai trong danh sách gợi ý đã ghép đôi với mình, để đổi
            // nút "Thả tim" thành "Nhắn tin". Lấy một lần rồi tra bằng mảng,
            // thay vì hỏi cơ sở dữ liệu cho từng dòng.
            foreach ($this->m_interaction->matches($me['id'], 200) as $u) {
                $data['matched_ids'][] = (int) $u['id'];
            }
        }

        $this->render('home/index', $data);
    }

    public function not_found()
    {
        $this->output->set_status_header(404);
        $this->render('errors/not_found', array('title' => 'Không tìm thấy trang'));
    }
}
