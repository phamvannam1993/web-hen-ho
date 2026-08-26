<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khám phá: vuốt từng hồ sơ để thích hoặc bỏ qua.
 *
 * Danh sách được lọc nghiêm ngặt theo cặp (giới tính, nhu cầu) của người xem —
 * xem M_user::deck(). Khách chưa đăng nhập tự chọn nhóm muốn xem, lựa chọn đó
 * lưu vào cookie để lần sau khỏi hỏi lại.
 */
class Discover extends MY_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_interaction'));
        $this->load->helper('cookie');
    }

    public function index()
    {
        $me = $this->auth->user();

        // Khách: nhóm muốn xem lấy từ query hoặc cookie đã lưu
        $view = null;
        if (!$me) {
            $hop_le = array('male', 'female', 'gay', 'les');
            $chon   = $this->input->get('xem');
            if (in_array($chon, $hop_le, true)) {
                set_cookie('discover_view', $chon, 60 * 60 * 24 * 30);
                $view = $chon;
            } else {
                $luu  = get_cookie('discover_view');
                $view = in_array($luu, $hop_le, true) ? $luu : null;
            }
        }

        $filters = array_filter(array(
            'province_id' => $this->input->get('province_id'),
            'age_min'     => $this->input->get('age_min'),
            'age_max'     => $this->input->get('age_max'),
        ));

        $data = array(
            'meta_title' => 'Khám Phá & Ghép Đôi Nhanh - ' . ($this->data['settings']['site_name'] ?? 'Saigon Cupid'),
            'meta_desc'  => 'Trải nghiệm và khám phá hàng ngàn hồ sơ thành viên nổi bật. '
                          . 'Thích hoặc bỏ qua để tìm kiếm nửa kia phù hợp ngay lập tức!',
            'title'      => 'Khám phá',
            'candidates' => $this->m_user->deck($me, $view, $filters, $this->per_page),
            'remaining'  => $this->m_user->count_deck($me, $view, $filters),
            'matches'    => $me ? $this->m_interaction->matches($me['id'], 6) : array(),
            'view'       => $view,
            'need_pick'  => !$me && !$view,          // khách chưa chọn nhóm -> hỏi ngay
            'filters'    => $filters,
        );

        $this->render('discover/index', $data);
    }

    /** Bắt buộc đăng nhập với các hành động ghi dữ liệu. */
    private function require_login()
    {
        if (!$this->auth->check()) {
            $this->json(array('ok' => false, 'need_login' => true,
                'message' => 'Vui lòng đăng nhập để thực hiện.'), 401);
            return false;
        }
        return true;
    }

    /** Thích một hồ sơ. */
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
        $doi    = $this->m_user->find($target_id);

        return $this->json(array(
            'ok'      => true,
            'liked'   => (bool) $result['liked'],
            'matched' => $result['matched'],
            // Dữ liệu để dựng hộp chúc mừng khi hai bên cùng thích
            'partner' => $result['matched'] ? array(
                'name'   => display_name($doi),
                'avatar' => avatar_url($doi['avatar'], $doi['gender']),
                'slug'   => $doi['slug'],
            ) : null,
            'me_avatar' => avatar_url($me['avatar'], $me['gender']),
            'message' => $result['matched']
                ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
                : ($result['liked'] ? 'Đã gửi lượt thích.' : 'Đã bỏ thích.'),
        ));
    }

    /** Bỏ qua một hồ sơ, lần sau không gợi ý lại. */
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

    /** Hoàn tác lượt bỏ qua gần nhất. */
    public function undo()
    {
        if (!$this->require_login()) {
            return;
        }
        $me   = $this->auth->user();
        $cuoi = $this->db->where('user_id', $me['id'])
            ->order_by('created_at', 'DESC')->limit(1)
            ->get('user_passes')->row_array();

        if (!$cuoi) {
            return $this->json(array('ok' => false, 'message' => 'Chưa có hồ sơ nào để xem lại.'));
        }

        $this->db->where('user_id', $me['id'])->where('passed_id', $cuoi['passed_id'])->delete('user_passes');
        $ho_so = $this->m_user->find($cuoi['passed_id']);

        return $this->json(array(
            'ok'   => true,
            'html' => $this->load->view('discover/_card',
                array('c' => $ho_so, 'user' => $me), true),
        ));
    }
}
