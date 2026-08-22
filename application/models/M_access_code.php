<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mã bảo mật ("pass") cho đăng ký / đăng nhập / xem số điện thoại. */
class M_access_code extends CI_Model
{
    /** Sinh mã mới. $cost > 0 sẽ trừ xu của $user_id. */
    public function issue($purpose, $user_id = null, $post_id = null, $cost = 0, $ttl_minutes = 30)
    {
        if ($cost > 0 && $user_id) {
            $this->load->model('m_user');
            if (!$this->m_user->adjust_coin($user_id, -$cost, 'unlock_contact', 'post', $post_id)) {
                return array('ok' => false, 'message' => 'Số dư xu không đủ, vui lòng nạp thêm.');
            }
        }

        $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        $this->db->insert('access_codes', array(
            'code'       => $code,
            'purpose'    => $purpose,
            'user_id'    => $user_id,
            'post_id'    => $post_id,
            'session_id' => $user_id ? null : session_id(),
            'coin_spent' => $cost,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . (int) $ttl_minutes . ' minutes')),
        ));
        return array('ok' => true, 'code' => $code, 'cost' => $cost);
    }

    /** Kiểm tra mã hợp lệ, chưa dùng hết và đúng mục đích. */
    public function verify($code, $purpose, $post_id = null)
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return false;
        }
        $this->db->where('code', $code)->where('purpose', $purpose)
            ->where('used_count < max_uses', null, false)
            ->group_start()->where('expires_at >', date('Y-m-d H:i:s'))->or_where('expires_at', null)->group_end();
        if ($post_id) {
            $this->db->group_start()->where('post_id', $post_id)->or_where('post_id', null)->group_end();
        }
        return $this->db->get('access_codes')->row_array();
    }

    public function consume($id)
    {
        $this->db->set('used_count', 'used_count + 1', false)->where('id', $id)->update('access_codes');
    }

    /* ------------------ Admin ------------------ */

    public function admin_list($purpose, $limit, $offset)
    {
        if ($purpose) $this->db->where('a.purpose', $purpose);
        return $this->db->select('a.*, u.display_name')
            ->from('access_codes a')->join('users u', 'u.id = a.user_id', 'left')
            ->order_by('a.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function admin_count($purpose)
    {
        if ($purpose) $this->db->where('purpose', $purpose);
        return $this->db->count_all_results('access_codes');
    }

    /** Admin phát mã dùng chung (nhiều lượt dùng). */
    public function create_shared($purpose, $max_uses = 100, $days = 30)
    {
        $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        $this->db->insert('access_codes', array(
            'code'       => $code,
            'purpose'    => $purpose,
            'max_uses'   => $max_uses,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days')),
        ));
        return $code;
    }

    public function remove($id)
    {
        $this->db->where('id', $id)->delete('access_codes');
    }
}
