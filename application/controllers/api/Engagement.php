<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Ai đã thích bạn, ai đã xem hồ sơ, chuỗi hoạt động — mục 2.5, 3.5, 4.5. */
class Engagement extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_interaction', 'm_streak'));
    }

    /**
     * Cắt danh sách theo quyền: bản thường chỉ thấy rõ 2 người đầu.
     * Người bị che không trả về tên và đường dẫn hồ sơ — chặn ngay từ máy chủ
     * chứ không chỉ làm mờ ở giao diện, nếu không ai đọc JSON cũng thấy hết.
     */
    private function che_bot(array $list, $la_vip)
    {
        $ra = array();
        foreach ($list as $i => $u) {
            if ($la_vip || $i < 2) {
                $ra[] = array('locked' => false) + $this->ho_so($u);
                continue;
            }
            $ra[] = array('locked' => true, 'id' => null, 'name' => null, 'slug' => null,
                          'avatar' => null, 'age' => null, 'province' => null);
        }
        return $ra;
    }

    /** GET /api/who-liked-me */
    public function who_liked_me()
    {
        if (!$this->can_auth()) {
            return;
        }
        $la_vip = (bool) $this->me['is_vip'];

        return $this->ok(array(
            'total'  => $this->m_interaction->liked_me_count($this->me['id']),
            'is_vip' => $la_vip,
            'items'  => $this->che_bot($this->m_interaction->liked_me($this->me['id'], 60), $la_vip),
        ));
    }

    /** GET /api/who-liked-me/count */
    public function who_liked_me_count()
    {
        if (!$this->can_auth()) {
            return;
        }
        return $this->ok(array('total' => $this->m_interaction->liked_me_count($this->me['id'])));
    }

    /** POST /api/who-liked-me/{id}/like|skip */
    public function respond($id = null, $act = 'like')
    {
        if (!$this->can_auth()) {
            return;
        }
        if (!$this->me['is_vip'] && $act === 'like') {
            return $this->loi('Tính năng thả tim lại dành cho thành viên VIP.', 403,
                array('need_vip' => true));
        }

        $kq = $this->m_interaction->respond_like($this->me['id'], (int) $id,
            $act === 'like' ? 'accept' : 'skip');

        return $kq['ok']
            ? $this->ok(array('matched' => $kq['matched'], 'message' => $kq['message']))
            : $this->loi($kq['message'], 409);
    }

    /** GET /api/profile-viewers */
    public function viewers()
    {
        if (!$this->can_auth()) {
            return;
        }
        $la_vip = (bool) $this->me['is_vip'];
        $list   = $this->m_interaction->viewers($this->me['id'], 60);

        $items = $this->che_bot($list, $la_vip);
        // VIP mới được biết thời điểm xem
        foreach ($items as $i => &$it) {
            if (empty($it['locked']) && $la_vip && isset($list[$i]['viewed_at'])) {
                $it['viewed_ago'] = time_ago($list[$i]['viewed_at']);
            }
        }
        unset($it);

        return $this->ok(array(
            'total'  => $this->m_interaction->viewer_count($this->me['id']),
            'is_vip' => $la_vip,
            'items'  => $items,
        ));
    }

    /** GET /api/profile-viewers/count */
    public function viewers_count()
    {
        if (!$this->can_auth()) {
            return;
        }
        return $this->ok(array('total' => $this->m_interaction->viewer_count($this->me['id'])));
    }

    /** GET /api/streak */
    public function streak()
    {
        if (!$this->can_auth()) {
            return;
        }
        $s = $this->m_streak->get($this->me['id']);

        return $this->ok(array(
            'current'  => (int) $s['current_streak'],
            'longest'  => (int) $s['longest_streak'],
            'freezes'  => (int) $s['streak_freeze_count'],
            'boost'    => $this->m_streak->boost($this->me['id']),
            'next'     => $this->m_streak->moc_ke_tiep((int) $s['current_streak']),
            'badges'   => $this->m_streak->badges($this->me['id']),
        ));
    }

    /** POST /api/streak/freeze */
    public function streak_freeze()
    {
        if (!$this->can_auth()) {
            return;
        }
        $kq = $this->body('buy')
            ? $this->m_streak->mua_freeze($this->me['id'])
            : $this->m_streak->dung_freeze($this->me['id']);

        return $kq['ok'] ? $this->ok(array('message' => $kq['message'])) : $this->loi($kq['message'], 409);
    }
}
