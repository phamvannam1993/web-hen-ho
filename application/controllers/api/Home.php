<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Trang chủ — bản API của controllers/Home.php. */
class Home extends Api_Controller
{
    /** GET /api/home */
    public function index()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $this->load->model(array('m_interaction', 'm_daily', 'm_province'));

        $stats = $this->m_user->stats();
        $ra = array(
            'stats' => array(
                // dải thống kê web dùng tổng thành viên đăng ký (home/index.php: $stats['total'])
                'members'   => $stats['total'],
                'online'    => $stats['online'],
                'couples'   => (int) $this->db->count_all('matches'),
                'provinces' => count($this->m_province->all()),
            ),
            'daily'          => null,
            'suggestions'    => array(),
            'liked_me_total' => 0,
            'liked_me'       => array(),
            // Thành viên mới hoạt động — khối chung cho cả khách
            'featured'       => array(),
            // Trang chủ mobile mới (web 20/11, home/_mobile.php): 20 hồ sơ, 5 đầu là dải "Cùng phong cách",
            // còn lại là danh sách "Phù hợp với bạn"; khách cũng có (thành viên đang hoạt động). Tải thêm: GET api/home/more
            'members'          => array(),
            'members_has_more' => false,
        );
        list($ra['members'], $ra['members_has_more']) = $this->trang_chu_mobile(0, 20);

        foreach ($this->m_user->search(array('sort' => 'active'), 10) as $u) {
            $ra['featured'][] = $this->ho_so($u, true);
        }

        if ($this->me) {
            $id = $this->me['id'];
            // Gợi ý hôm nay: chưa có thì chốt luôn, giống web
            $d = $this->m_daily->today($id) ?: $this->m_daily->tao_cho($id);
            if ($d) {
                $ra['daily'] = array(
                    'id'         => (int) $d['id'],
                    'status'     => $d['status'],
                    'score'      => min(99, 60 + (int) $d['score'] * 2),
                    'expires_at' => date('c', strtotime($d['expires_at'])),
                    'profile'    => $this->ho_so($d, true),
                );
            }

            // Đổi nút "Thả tim" thành "Nhắn tin" với người đã ghép đôi
            $da_ghep = array();
            foreach ($this->m_interaction->matches($id, 200) as $u) {
                $da_ghep[(int) $u['id']] = true;
            }
            foreach ($this->m_user->suggestions($this->me, 10) as $u) {
                $ra['suggestions'][] = $this->ho_so($u, true) + array(
                    'matched'     => isset($da_ghep[(int) $u['id']]),
                    // điểm gốc (thang ~120): app quy đổi % như web (_suggestion.php: max(60, min(99, score*100/120)));
                    // trước đây kẹp 100 ở đây nên app tối đa chỉ hiện 83%
                    'match_score' => (int) $u['match_score'],
                );
            }
            $ra['liked_me_total'] = $this->m_interaction->liked_me_count($id);
            // Khối "Có N người đã thích bạn" của trang chủ web hiện rõ 5 người đầu
            $ra['liked_me'] = array_map(function ($u) {
                return $this->ho_so($u);
            }, $this->m_interaction->liked_me($id, 5));
        }

        return $this->ok($ra);
    }

    /** GET /api/home/more?offset=N — giống ajax/goi-y-trang-chu của web: 12 hồ sơ mỗi lần */
    public function more()
    {
        if (!$this->thu_auth()) {
            return;
        }
        $offset = max(0, min(100000, (int) $this->input->get('offset')));
        list($members, $has_more) = $this->trang_chu_mobile($offset, 12);
        return $this->ok(array('members' => $members, 'has_more' => $has_more, 'next_offset' => $offset + count($members)));
    }

    /** Hồ sơ trang chủ mobile: đăng nhập thì gợi ý theo tiêu chí, khách thì thành viên đang hoạt động (như web) */
    private function trang_chu_mobile($offset, $limit)
    {
        $this->load->model('m_interaction');
        $rows = $this->me
            ? $this->m_user->suggestions($this->me, $limit + 1, $offset)
            : $this->m_user->search(array('sort' => 'active'), $limit + 1, $offset);
        $has_more = count($rows) > $limit;
        $da_ghep = array();
        if ($this->me) {
            foreach ($this->m_interaction->matches($this->me['id'], 200) as $u) {
                $da_ghep[(int) $u['id']] = true;
            }
        }
        $ra = array();
        foreach (array_slice($rows, 0, $limit) as $u) {
            $p = $this->ho_so($u, true) + array('matched' => isset($da_ghep[(int) $u['id']]));
            if (isset($u['match_score'])) {
                $p['match_score'] = (int) $u['match_score'];
            }
            $ra[] = $p;
        }
        return array($ra, $has_more);
    }
}
