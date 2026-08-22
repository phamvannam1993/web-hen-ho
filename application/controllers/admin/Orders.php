<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends Admin_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_billing');
    }

    public function index($page = 1)
    {
        $filters = array_filter(array(
            'keyword' => $this->input->get('q', true),
            'status'  => $this->input->get('status'),
        ));
        $page  = max(1, (int) $page);
        $total = $this->m_billing->admin_orders_count($filters);

        $this->render('admin/orders/index', array(
            'title'      => 'Đơn nạp xu / VIP',
            'orders'     => $this->m_billing->admin_orders($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'revenue'    => $this->m_billing->revenue_stats(),
            'pagination' => pagination_links('admin/orders', $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    /** Xác nhận thanh toán: cộng xu hoặc kích hoạt VIP tương ứng với gói. */
    public function set_status($id, $status)
    {
        if (!in_array($status, array('paid', 'failed', 'refunded', 'canceled', 'pending'), true)) {
            show_404();
        }
        $this->m_billing->set_order_status($id, $status);
        $this->log_action('order_status:' . $status, 'orders', $id);
        set_flash('success', 'Đã cập nhật đơn hàng.');
        redirect($this->input->server('HTTP_REFERER') ?: 'admin/orders');
    }
}
