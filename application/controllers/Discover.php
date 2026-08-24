<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khám phá: lướt từng hồ sơ được gợi ý theo mức tương hợp,
 * chọn "Thích" hoặc "Bỏ qua". Thích lẫn nhau sẽ tạo ghép đôi và mở khung chat.
 */
class Discover extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_interaction'));
    }

    private $per_page = 20;

    public function index($page = 1)
    {
        $me     = $this->auth->user();
        $page   = max(1, (int) $page);
        $offset = ($page - 1) * $this->per_page;

        if ($me) {
            // Đã đăng nhập: gợi ý theo hồ sơ và tiêu chí của chính họ
            $total = $this->m_user->count_suggestions($me);
            $data  = array(
                'candidates' => $this->m_user->suggestions($me, $this->per_page, $offset),
                'remaining'  => $total,
                'matches'    => $this->m_interaction->matches($me['id'], 6),
            );
        } else {
            // Khách: xem thành viên hoạt động gần đây, chưa tính được độ tương hợp
            $total = $this->m_user->count_search(array());
            $data  = array(
                'candidates' => $this->m_user->search(array('sort' => 'active'), $this->per_page, $offset),
                'remaining'  => $total,
                'matches'    => array(),
            );
        }

        $data['pagination'] = pagination_links('kham-pha', $page, $total, $this->per_page);

        $this->render('discover/index', array_merge(array('title' => 'Khám phá'), $data));
    }

    /** Bắt buộc đăng nhập với các hành động ghi dữ liệu. */
    private function require_login()
    {
        if (!$this->auth->check()) {
            $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập để thực hiện.'), 401);
            return false;
        }
        return true;
    }

    /** Thích một hồ sơ. Trả JSON để giao diện chuyển sang hồ sơ kế tiếp. */
    public function like($target_id)
    {
        if (!$this->require_login()) {
            return;
        }
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
        ));
    }

    /**
     * Bỏ qua một hồ sơ: ghi lại để lần sau không gợi ý nữa.
     * Dùng chính bảng blocks với cờ mềm để không phải thêm bảng mới.
     */
    public function pass($target_id)
    {
        if (!$this->require_login()) {
            return;
        }
        $me = $this->auth->user();
        $this->db->replace('user_passes', array(
            'user_id'   => $me['id'],
            'passed_id' => (int) $target_id,
        ));

        return $this->json(array('ok' => true));
    }

}
