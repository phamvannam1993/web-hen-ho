<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khám phá (vuốt hồ sơ) — bản API của controllers/Discover.php.
 * Khách chưa đăng nhập vẫn xem được, gửi ?view=male|female|gay|les để chọn nhóm.
 */
class Discover extends Api_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_daily'));
    }

    /** GET /api/discover?view=&province_id=&age_min=&age_max=&page= */
    public function index()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $view = $this->input->get('view');
        if (!in_array($view, array('male', 'female', 'gay', 'les'), true)) {
            $view = null;
        }
        $filters = array_filter(array(
            'province_id' => (int) $this->input->get('province_id'),
            'age_min'     => (int) $this->input->get('age_min'),
            'age_max'     => (int) $this->input->get('age_max'),
        ));

        // Người đã thích / bỏ qua tự rơi khỏi bộ bài, nên trang sau chỉ cần
        // lệch theo số thẻ khách đã xem (khách không ghi gì vào DB)
        $offset = $this->me ? 0 : ($this->trang() - 1) * $this->per_page;

        $items = array();
        foreach ($this->m_user->deck($this->me, $view, $filters, $this->per_page, $offset) as $c) {
            $items[] = $this->ho_so_chi_tiet($c);
        }

        $daily = null;
        if ($this->me) {
            $d = $this->m_daily->today($this->me['id']) ?: $this->m_daily->tao_cho($this->me['id']);
            if ($d) {
                $daily = array(
                    'id'         => (int) $d['id'],
                    'status'     => $d['status'],
                    'score'      => min(99, 60 + (int) $d['score'] * 2),
                    'expires_at' => date('c', strtotime($d['expires_at'])),
                    'profile'    => $this->ho_so($d, true),
                );
            }
        }

        return $this->ok(array(
            'items'     => $items,
            'remaining' => $this->m_user->count_deck($this->me, $view, $filters),
            'daily'     => $daily,
            'guest'     => !$this->me,
        ));
    }

    /** POST /api/discover/{id}/like — thích (hoặc bỏ thích nếu đã thích). */
    public function like($target_id = 0)
    {
        if (!$this->can_auth() || !$this->can_xac_thuc('thả tim')) {
            return;
        }
        $target_id = (int) $target_id;
        if ($target_id === (int) $this->me['id']) {
            return $this->loi('Không thể tự thích hồ sơ của mình.', 422);
        }
        $doi = $this->m_user->find($target_id);
        if (!$doi) {
            return $this->loi('Không tìm thấy thành viên.', 404);
        }

        $kq = $this->m_interaction->toggle_like($this->me['id'], 'user', $target_id);
        if ($this->loi_tha_tim($kq)) {
            return;
        }

        return $this->ok(array(
            'liked'     => (bool) $kq['liked'],
            'matched'   => (bool) $kq['matched'],
            'partner'   => $kq['matched'] ? $this->ho_so($doi) : null,
            'me_avatar' => avatar_url($this->me['avatar'], $this->me['gender']),
            // Thích mà hồ sơ chưa có ảnh: ứng dụng nên nhắc thêm ảnh
            'nudge_photo' => $kq['liked'] && !$kq['matched'] && empty($this->me['avatar']),
            'message'   => $kq['matched']
                ? 'Ghép đôi thành công! Hai bạn đã thích nhau.'
                : ($kq['liked'] ? 'Đã gửi lượt thích.' : 'Đã bỏ thích.'),
        ));
    }

    /** POST /api/discover/{id}/pass — bỏ qua, lần sau không gợi ý lại. */
    public function pass($target_id = 0)
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->db->replace('user_passes', array(
            'user_id'   => $this->me['id'],
            'passed_id' => (int) $target_id,
        ));
        return $this->ok();
    }

    /** POST /api/discover/undo — hoàn tác lượt bỏ qua gần nhất, trả lại hồ sơ đó. */
    public function undo()
    {
        if (!$this->can_auth()) {
            return;
        }
        $cuoi = $this->db->where('user_id', $this->me['id'])
            ->order_by('created_at', 'DESC')->limit(1)
            ->get('user_passes')->row_array();
        if (!$cuoi) {
            return $this->loi('Chưa có hồ sơ nào để xem lại.', 404);
        }

        $this->db->where('user_id', $this->me['id'])->where('passed_id', $cuoi['passed_id'])->delete('user_passes');
        $u = $this->m_user->find($cuoi['passed_id']);

        return $u ? $this->ok(array('profile' => $this->ho_so_chi_tiet($u)))
                  : $this->loi('Hồ sơ này không còn nữa.', 404);
    }
}
