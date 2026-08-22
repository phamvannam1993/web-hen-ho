<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Gói dịch vụ, đơn nạp, lịch sử xu. */
class M_billing extends CI_Model
{
    /* --------------------- Gói --------------------- */

    public function packages($type = null, $only_active = true)
    {
        if ($type) $this->db->where('type', $type);
        if ($only_active) $this->db->where('is_active', 1);
        return $this->db->order_by('sort')->order_by('price')->get('packages')->result_array();
    }

    public function find_package($id)
    {
        return $this->db->where('id', $id)->get('packages')->row_array();
    }

    public function save_package(array $data, $id = null)
    {
        if ($id) {
            $this->db->where('id', $id)->update('packages', $data);
            return $id;
        }
        $this->db->insert('packages', $data);
        return $this->db->insert_id();
    }

    public function remove_package($id)
    {
        $this->db->where('id', $id)->delete('packages');
    }

    /* --------------------- Đơn hàng --------------------- */

    /** Tạo đơn nạp ở trạng thái chờ, admin hoặc cổng thanh toán xác nhận sau. */
    public function create_order($user_id, $package_id, $method = 'bank')
    {
        $package = $this->find_package($package_id);
        if (!$package || !$package['is_active']) {
            return null;
        }
        $code = 'HH' . date('ymd') . strtoupper(substr(md5(uniqid('', true)), 0, 6));
        $this->db->insert('orders', array(
            'code'       => $code,
            'user_id'    => $user_id,
            'package_id' => $package_id,
            'amount'     => $package['price'],
            'method'     => $method,
            'status'     => 'pending',
        ));
        return $this->db->where('id', $this->db->insert_id())->get('orders')->row_array();
    }

    /** Xác nhận đã thanh toán: cộng xu hoặc kích hoạt VIP. */
    public function mark_paid($order_id)
    {
        $order = $this->db->where('id', $order_id)->get('orders')->row_array();
        if (!$order || $order['status'] === 'paid') {
            return false;
        }
        $package = $order['package_id'] ? $this->find_package($order['package_id']) : null;

        $this->load->model(array('m_user', 'm_notification'));
        $this->db->where('id', $order_id)->update('orders', array(
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ));

        if ($package && $package['type'] === 'coin') {
            $coins = (int) $package['coin_amount'] + (int) $package['bonus_coin'];
            $this->m_user->adjust_coin($order['user_id'], $coins, 'nap', 'order', $order_id,
                'Nạp gói ' . $package['name']);
            $this->m_notification->push($order['user_id'], 'system', 'Nạp xu thành công',
                'Bạn được cộng ' . $coins . ' xu.', site_url('tai-khoan/nap-xu'));
        } elseif ($package && $package['type'] === 'vip') {
            $this->m_user->grant_vip($order['user_id'], (int) $package['duration_days']);
            $this->m_notification->push($order['user_id'], 'system', 'Kích hoạt VIP thành công',
                'Gói ' . $package['name'] . ' đã được kích hoạt.', site_url('tai-khoan'));
        }
        return true;
    }

    public function set_order_status($order_id, $status)
    {
        if ($status === 'paid') {
            return $this->mark_paid($order_id);
        }
        $this->db->where('id', $order_id)->update('orders', array('status' => $status));
        return true;
    }

    public function orders_of($user_id, $limit = 20)
    {
        return $this->db->select('o.*, p.name AS package_name')
            ->from('orders o')->join('packages p', 'p.id = o.package_id', 'left')
            ->where('o.user_id', $user_id)->order_by('o.id', 'DESC')->limit($limit)->get()->result_array();
    }

    public function admin_orders(array $f, $limit, $offset)
    {
        $this->order_filter($f);
        return $this->db->select('o.*, u.display_name, u.email, p.name AS package_name')
            ->order_by('o.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function admin_orders_count(array $f)
    {
        $this->order_filter($f);
        return $this->db->count_all_results();
    }

    private function order_filter(array $f)
    {
        $this->db->from('orders o')
            ->join('users u', 'u.id = o.user_id')
            ->join('packages p', 'p.id = o.package_id', 'left');
        if (!empty($f['status']))  $this->db->where('o.status', $f['status']);
        if (!empty($f['keyword'])) {
            $this->db->group_start()
                ->like('o.code', $f['keyword'])->or_like('u.display_name', $f['keyword'])
                ->group_end();
        }
    }

    public function coin_history($user_id, $limit = 50)
    {
        return $this->db->where('user_id', $user_id)->order_by('id', 'DESC')
            ->limit($limit)->get('coin_transactions')->result_array();
    }

    public function revenue_stats()
    {
        return array(
            'today' => (int) $this->db->select_sum('amount')->where('status', 'paid')
                ->where('DATE(paid_at)', date('Y-m-d'))->get('orders')->row('amount'),
            'month' => (int) $this->db->select_sum('amount')->where('status', 'paid')
                ->where('paid_at >=', date('Y-m-01 00:00:00'))->get('orders')->row('amount'),
            'total' => (int) $this->db->select_sum('amount')->where('status', 'paid')
                ->get('orders')->row('amount'),
            'pending' => (int) $this->db->where('status', 'pending')->count_all_results('orders'),
        );
    }
}
