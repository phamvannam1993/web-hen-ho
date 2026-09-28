<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Gợi ý mỗi ngày — mục 1.6 của đặc tả. */
class Daily_match extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_daily');
    }

    /** GET /api/daily-match/today */
    public function today()
    {
        if (!$this->can_auth()) {
            return;
        }
        // Chưa có thì chốt luôn, để người mới cũng thấy ngay trong ngày đầu
        $d = $this->m_daily->today($this->me['id']) ?: $this->m_daily->tao_cho($this->me['id']);

        if (!$d) {
            return $this->ok(array('match' => null, 'message' => 'Hôm nay chưa có ai phù hợp.'));
        }

        return $this->ok(array('match' => array(
            'id'         => (int) $d['id'],
            'status'     => $d['status'],
            'score'      => min(99, 60 + (int) $d['score'] * 2),
            'expires_at' => date('c', strtotime($d['expires_at'])),
            'profile'    => $this->ho_so($d, true),
        )));
    }

    /** POST /api/daily-match/{id}/like */
    public function like($id = null)
    {
        return $this->tra_loi($id, 'like');
    }

    /** POST /api/daily-match/{id}/skip */
    public function skip($id = null)
    {
        return $this->tra_loi($id, 'skip');
    }

    private function tra_loi($id, $act)
    {
        if (!$this->can_auth()) {
            return;
        }
        $kq = $this->m_daily->tra_loi($this->me['id'], (int) $id, $act);

        return $kq['ok']
            ? $this->ok(array('matched' => $kq['matched'], 'message' => $kq['message']))
            : $this->loi($kq['message'], 409);
    }

    /** GET /api/daily-match/history */
    public function history()
    {
        if (!$this->can_auth()) {
            return;
        }
        $ra = array();
        foreach ($this->m_daily->history($this->me['id'], 60) as $d) {
            $ra[] = array(
                'id'      => (int) $d['id'],
                'date'    => $d['match_date'],
                'status'  => $d['status'],
                'profile' => $this->ho_so($d),
            );
        }
        return $this->ok(array('items' => $ra));
    }
}
