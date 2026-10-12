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
                'members'   => $stats['active'],
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
        );

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
                    'match_score' => min(100, (int) $u['match_score']),
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
}
