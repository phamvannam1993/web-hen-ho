<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Admin_Controller
{
    private $per_page = 20;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_report', 'm_post', 'm_user'));
    }

    public function index($page = 1)
    {
        $status = $this->input->get('status');
        $page   = max(1, (int) $page);
        $total  = $this->m_report->admin_count($status);
        $rows   = $this->m_report->admin_list($status, $this->per_page, ($page - 1) * $this->per_page);

        // nạp tên đối tượng bị báo cáo để hiển thị cho dễ hiểu
        foreach ($rows as &$r) {
            if ($r['target_type'] === 'post') {
                $p = $this->m_post->find($r['target_id']);
                $r['target_label'] = $p ? $p['title'] : 'Tin đã xoá';
                $r['target_link']  = $p ? site_url('admin/posts/edit/' . $p['id']) : null;
            } elseif ($r['target_type'] === 'user') {
                $u = $this->m_user->find($r['target_id']);
                $r['target_label'] = $u ? $u['display_name'] : 'Thành viên đã xoá';
                $r['target_link']  = $u ? site_url('admin/users/view/' . $u['id']) : null;
            } else {
                $r['target_label'] = ucfirst($r['target_type']) . ' #' . $r['target_id'];
                $r['target_link']  = null;
            }
        }
        unset($r);

        $this->render('admin/reports/index', array(
            'title'      => 'Báo cáo vi phạm',
            'reports'    => $rows,
            'total'      => $total,
            'pagination' => pagination_links('admin/reports', $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    public function resolve($id, $status)
    {
        if (!in_array($status, array('reviewing', 'resolved', 'dismissed'), true)) {
            show_404();
        }
        $this->m_report->resolve($id, $status, $this->auth->id());
        $this->log_action('resolve_report:' . $status, 'reports', $id);
        set_flash('success', 'Đã cập nhật báo cáo.');
        redirect($this->input->server('HTTP_REFERER') ?: 'admin/reports');
    }

    /** Xử lý nhanh: ẩn tin hoặc khoá tài khoản bị báo cáo. */
    public function act($id, $action)
    {
        $report = $this->m_report->find($id);
        if (!$report) {
            show_404();
        }
        if ($action === 'hide_post' && $report['target_type'] === 'post') {
            $this->m_post->moderate($report['target_id'], 'hidden', 'Vi phạm nội quy');
        } elseif ($action === 'ban_user' && $report['target_type'] === 'user') {
            $this->m_user->set_status($report['target_id'], 'banned');
        }
        $this->m_report->resolve($id, 'resolved', $this->auth->id());
        $this->log_action('report_act:' . $action, 'reports', $id);
        set_flash('success', 'Đã xử lý báo cáo.');
        redirect('admin/reports');
    }
}
