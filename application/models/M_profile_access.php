<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class M_profile_access extends CI_Model
{
    public function allowed_target($owner, $viewer)
    {
        if (!$owner || !empty($owner['deleted_at']) || ($owner['status'] !== 'active' && (int)$viewer !== (int)$owner['id'])) throw new RuntimeException('Hồ sơ không còn khả dụng.',404);
        if ((int)$viewer !== (int)$owner['id']) {
            $pref=$this->db->where('user_id',$owner['id'])->get('user_preferences')->row_array();
            if (!$owner['avatar'] || !$owner['province_id'] || !$owner['birthday'] || $owner['birthday']>date('Y-m-d',strtotime('-18 years')) || !$pref || !$pref['seeking_gender'] || !$pref['purpose']) throw new RuntimeException('Hồ sơ đang ẩn hoặc chưa hoàn thiện.',404);
        }
        if ($viewer && $this->db->query('SELECT 1 FROM blocks WHERE (user_id=? AND blocked_id=?) OR (user_id=? AND blocked_id=?) LIMIT 1',array($viewer,$owner['id'],$owner['id'],$viewer))->row_array()) throw new RuntimeException('Không thể xem hồ sơ này.',403);
    }
    public function purchased($viewer,$owner)
    {
        return $viewer && (bool)$this->db->where('buyer_id',$viewer)->where('owner_id',$owner)->get('profile_purchases')->row_array();
    }
    public function can_detail($owner,$viewer)
    {
        if ($viewer && super_admin_unlimited($this->db->where('id',$viewer)->get('users')->row_array(), 'coins')) return true;
        return $viewer && ((int)$owner['id']===(int)$viewer || !$owner['charge_enabled'] || $this->purchased($viewer,$owner['id']));
    }
    public function contacts($owner,$viewer)
    {
        $this->allowed_target($owner,$viewer);
        if (!$this->can_detail($owner,$viewer)) throw new RuntimeException('Mở hồ sơ chi tiết trước khi xem liên hệ.',403);
        $user=$this->db->where('id',$viewer)->get('users')->row_array();
        $vip=member_has_vip($user);
        if ((int)$viewer!==(int)$owner['id'] && $owner['contact_vip_required'] && !$vip) throw new RuntimeException('Thông tin liên hệ chỉ dành cho VIP.',403);
        return array('phone'=>$owner['phone'],'zalo'=>$owner['phone'],'email'=>$owner['shared_email']);
    }
    public function quote($owner,$viewer)
    {
        $this->allowed_target($owner,$viewer);
        $paid=$this->purchased($viewer,$owner['id']);
        $self=(int)$viewer===(int)$owner['id'];
        $cost=$owner['charge_enabled'] && !$paid && !$self ? 20 : 0;
        $user=$viewer ? $this->db->where('id',$viewer)->get('users')->row_array() : null;
        $unlimited = super_admin_unlimited($user, 'coins');
        if ($unlimited) $cost = 0;
        return array('id'=>(int)$owner['id'],'slug'=>$owner['slug'],'name'=>display_name($owner),'avatar'=>avatar_url($owner['avatar'],$owner['gender']),
            'age'=>age_from($owner['birthday']),'province'=>$owner['province_name'] ?? '', 'job'=>$owner['job'],'bio'=>mb_substr(strip_tags((string)$owner['bio']),0,180),
            'charge_enabled'=>(bool)$owner['charge_enabled'],'contact_vip_required'=>(bool)$owner['contact_vip_required'],'purchased'=>(bool)$paid,'cost'=>$cost,
            'logged_in'=>(bool)$viewer,'unlimited_coins'=>$unlimited,'balance'=>$user ? (int)$user['coin_balance'] : 0,'transferable_balance'=>$user ? min((int)$user['coin_balance'],(int)$user['transferable_coin_balance']) : 0,
            'version'=>hash('sha256',$owner['id'].':'.$owner['charge_enabled'].':'.$owner['contact_vip_required']), 'url'=>site_url('user/'.$owner['slug']));
    }
    public function purchase($owner_id,$viewer,$version)
    {
        if (!$viewer) throw new RuntimeException('Vui lòng đăng nhập.',401);
        $this->db->trans_begin();
        try {
            $ids=array_unique(array((int)$viewer,(int)$owner_id)); sort($ids);
            $rows=$this->db->query('SELECT * FROM users WHERE id IN ('.implode(',',$ids).') ORDER BY id FOR UPDATE')->result_array();
            $users=array_column($rows,null,'id'); $buyer=$users[$viewer] ?? null; $owner=$users[$owner_id] ?? null;
            $this->allowed_target($owner,$viewer);
            if (!$buyer || $buyer['status']!=='active' || $buyer['deleted_at']) throw new RuntimeException('Tài khoản không có quyền thực hiện.',403);
            $quote=$this->quote($owner,$viewer);
            if (!hash_equals($quote['version'],(string)$version)) throw new RuntimeException('Điều kiện hồ sơ đã thay đổi. Vui lòng kiểm tra và xác nhận lại.',409);
            if ($quote['cost']) {
                if (min((int)$buyer['coin_balance'],(int)$buyer['transferable_coin_balance'])<20) throw new RuntimeException('Bạn cần 20 xu đủ điều kiện chuyển. Xu thưởng khóa không thể dùng để mở hồ sơ.',422);
                $this->db->where('id',$viewer)->update('users',array('coin_balance'=>(int)$buyer['coin_balance']-20,'transferable_coin_balance'=>min((int)$buyer['coin_balance'],(int)$buyer['transferable_coin_balance'])-20));
                $this->db->where('id',$owner_id)->update('users',array('coin_balance'=>(int)$owner['coin_balance']+20,'transferable_coin_balance'=>min((int)$owner['coin_balance'],(int)$owner['transferable_coin_balance'])+20));
                $this->db->insert('profile_purchases',array('buyer_id'=>$viewer,'owner_id'=>$owner_id,'amount'=>20));
                foreach (array(array($viewer,-20,(int)$buyer['coin_balance']-20,'spend',$owner_id),array($owner_id,20,(int)$owner['coin_balance']+20,'earn',$viewer)) as $t) {
                    $this->db->insert('coin_transactions',array('user_id'=>$t[0],'amount'=>$t[1],'transferable_amount'=>$t[1],'balance_after'=>$t[2],'reason'=>$t[3],'ref_type'=>'profile_transfer','ref_id'=>$t[4],'note'=>'Mở hồ sơ chi tiết một lần'));
                }
            }
            if (!$this->db->trans_status()) throw new RuntimeException('Giao dịch chưa thành công, chưa trừ xu.',503);
            $this->db->trans_commit(); return array('url'=>$quote['url'],'charged'=>$quote['cost']);
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }
}
