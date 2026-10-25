<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_profile_frame extends CI_Model
{
    public function owned($user_id)
    {
        if (!$this->db->table_exists('user_profile_frames')) return array();
        return array_column($this->db->where('user_id', $user_id)->get('user_profile_frames')->result_array(), 'frame_key');
    }

    public function equip($user_id, $frame, $purchase = false)
    {
        $prices = profile_frame_prices();
        if (!array_key_exists($frame, $prices)) throw new RuntimeException('Khung không hợp lệ.');
        $price = $prices[$frame];
        if (!$this->db->field_exists('profile_frame', 'users') || ($price && !$this->db->table_exists('user_profile_frames'))) {
            throw new RuntimeException('Kho khung chưa sẵn sàng. Vui lòng thử lại sau.');
        }
        $this->db->trans_begin();
        try {
            // Serialize purchases per wallet, including repeated submissions.
            $wallet = $this->db->query('SELECT * FROM users WHERE id=? FOR UPDATE', array($user_id))->row_array();
            if (!$wallet) throw new RuntimeException('Không tìm thấy tài khoản.');
            $owned = !$price || in_array($frame, $this->owned($user_id), true);
            if (!$owned) {
                if (!$purchase) throw new RuntimeException('Bạn cần mua khung này trước khi sử dụng.');
                $price = super_admin_unlimited($wallet, 'coins') ? 0 : $price;
                if ((int) $wallet['coin_balance'] < $price) throw new RuntimeException('Bạn chưa đủ xu để mua khung này.');
                $balance = (int) $wallet['coin_balance'] - $price;
                $transferable = min($balance, (int) $wallet['transferable_coin_balance']);
                $this->db->where('id', $user_id)->update('users', array('coin_balance'=>$balance, 'transferable_coin_balance'=>$transferable));
                $this->db->insert('user_profile_frames', array('user_id'=>$user_id, 'frame_key'=>$frame, 'price_paid'=>$price));
                $this->db->insert('coin_transactions', array('user_id'=>$user_id, 'amount'=>-$price, 'balance_after'=>$balance,
                    'transferable_amount'=>$transferable-(int) $wallet['transferable_coin_balance'], 'reason'=>'profile_frame',
                    'ref_type'=>'profile_frame', 'note'=>'Mua khung ' . profile_frame_catalog()[$frame] . ' (' . $frame . ')'));
            }
            $this->db->where('id', $user_id)->update('users', array('profile_frame'=>$frame));
            if (!$this->db->trans_status()) throw new RuntimeException('Chưa lưu được khung. Vui lòng thử lại.');
            $this->db->trans_commit();
            return !$owned;
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }
}
