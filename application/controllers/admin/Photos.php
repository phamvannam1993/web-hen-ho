<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Photos extends Admin_Controller
{
    private $per_page = 20;

    private function filters()
    {
        $status = $this->input->get('status');
        if (!in_array($status, array('all', 'pending', 'approved', 'rejected'), true)) {
            $status = 'pending';
        }
        return array('status' => $status, 'q' => trim((string) $this->input->get('q', true)));
    }

    private function photo_query(array $filters)
    {
        $this->db->from('user_photos ph')->join('users u', 'u.id = ph.user_id')
            ->where('u.deleted_at', null);
        if ($filters['status'] !== 'all') {
            $this->db->where('ph.status', $filters['status']);
        }
        if ($filters['q'] !== '') {
            $this->db->group_start()->like('u.display_name', $filters['q'])
                ->or_like('u.email', $filters['q'])->group_end();
        }
    }

    public function index($page = 1)
    {
        $filters = $this->filters();
        $page = max(1, (int) $page);
        $this->photo_query($filters);
        $total = $this->db->count_all_results();
        $this->photo_query($filters);
        $photos = $this->db->select('ph.*, u.display_name, u.nickname, u.email')
            ->order_by('ph.created_at', 'DESC')->order_by('ph.id', 'DESC')
            ->limit($this->per_page, ($page - 1) * $this->per_page)->get()->result_array();
        $this->render('admin/photos/index', array(
            'title' => 'Duyệt ảnh thành viên', 'photos' => $photos, 'total' => $total,
            'filters' => $filters,
            'pagination' => pagination_links('admin/photos', $page, $total, $this->per_page, $filters),
        ));
    }

    public function moderate($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Chỉ chấp nhận yêu cầu POST.', 405);
            return;
        }
        $status = $this->input->post('status');
        if (!in_array($status, array('approved', 'rejected'), true)) {
            show_error('Trạng thái duyệt ảnh không hợp lệ.', 400);
            return;
        }
        $photo = $this->db->select('ph.id')->from('user_photos ph')
            ->join('users u', 'u.id = ph.user_id')->where('u.deleted_at', null)
            ->where('ph.id', (int) $id)->get()->row_array();
        if (!$photo) {
            show_404();
            return;
        }
        if (!$this->db->where('id', (int) $id)->update('user_photos', array('status' => $status))) {
            set_flash('danger', 'Không cập nhật được trạng thái ảnh. Vui lòng thử lại.');
        } else {
            $this->log_action('moderate_photo:' . $status, 'user_photos', (int) $id);
            set_flash('success', $status === 'approved' ? 'Đã duyệt ảnh.' : 'Đã từ chối ảnh.');
        }
        $filters = $this->filters();
        $page = max(1, (int) $this->input->get('page'));
        redirect('admin/photos' . ($page > 1 ? '/trang/' . $page : '') . '?' . http_build_query($filters));
    }
}
