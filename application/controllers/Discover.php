<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khám phá: lướt từng hồ sơ được gợi ý theo mức tương hợp,
 * chọn "Thích" hoặc "Bỏ qua". Thích lẫn nhau sẽ tạo ghép đôi và mở khung chat.
 */
class Discover extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_interaction'));
    }

    public function index()
    {
        $me = $this->auth->user();

        $this->render('discover/index', array(
            'title'      => 'Khám phá',
            'candidates' => $this->m_user->suggestions($me, 10),
            'remaining'  => $this->m_user->count_suggestions($me),
            'matches'    => $this->m_interaction->matches($me['id'], 6),
        ));
    }

    /** Thích một hồ sơ. Trả JSON để giao diện chuyển sang hồ sơ kế tiếp. */
    public function like($target_id)
    {
        $me = $this->auth->user();
        if ((int) $target_id === (int) $me['id']) {
            return $this->json(array('ok' => false, 'message' => 'Không thể tự thích hồ sơ của mình.'));
        }

        $result = $this->m_interaction->toggle_like($me['id'], 'user', $target_id);
        return $this->json(array(
            'ok'      => true,
            'matched' => $result['matched'],
            'message' => $result['matched']
                ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
                : 'Đã gửi lượt thích.',
            'next'    => $this->next_card($me),
        ));
    }

    /**
     * Bỏ qua một hồ sơ: ghi lại để lần sau không gợi ý nữa.
     * Dùng chính bảng blocks với cờ mềm để không phải thêm bảng mới.
     */
    public function pass($target_id)
    {
        $me = $this->auth->user();
        $this->db->replace('user_passes', array(
            'user_id'   => $me['id'],
            'passed_id' => (int) $target_id,
        ));

        return $this->json(array(
            'ok'   => true,
            'next' => $this->next_card($me),
        ));
    }

    /** Lấy hồ sơ kế tiếp để nạp thêm vào cuối danh sách đang lướt. */
    private function next_card($me)
    {
        $list = $this->m_user->suggestions($me, 1, 9);
        if (empty($list)) {
            return null;
        }
        return $this->load->view('discover/_card', array('c' => $list[0]), true);
    }
}
