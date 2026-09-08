<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Quản lý danh mục nghề nghiệp dùng cho ô chọn nghề trong hồ sơ. */
class Jobs extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_job');
    }

    public function index()
    {
        $this->render('admin/jobs/index', array(
            'title' => 'Quản lý nghề nghiệp',
            'jobs'  => $this->m_job->admin_list(
                $this->input->get('q', true),
                $this->input->get('status')
            ),
        ));
    }

    public function edit($id = null)
    {
        $job = $id ? $this->m_job->find($id) : null;
        if ($id && !$job) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', 'Tên nghề', 'required|max_length[120]');

            $ten = trim((string) $this->input->post('name', true));
            if ($this->form_validation->run()) {
                // Tên nghề là khoá duy nhất; báo trước cho gọn thay vì để lỗi SQL
                if ($this->m_job->name_exists($ten, $id)) {
                    set_flash('danger', 'Nghề "' . $ten . '" đã có trong danh mục.');
                } else {
                    $new_id = $this->m_job->save(array(
                        'name'      => $ten,
                        'sort'      => (int) $this->input->post('sort'),
                        'is_active' => (int) (bool) $this->input->post('is_active'),
                    ), $id);

                    $this->log_action('save_job', 'jobs', $new_id);
                    set_flash('success', 'Đã lưu nghề nghiệp.');
                    redirect('admin/jobs');
                }
            }
        }

        $this->render('admin/jobs/edit', array(
            'title'   => $id ? 'Sửa nghề nghiệp' : 'Thêm nghề nghiệp',
            'j'       => $job,
            'members' => $id ? $this->m_job->member_count($id) : 0,
        ));
    }

    public function delete($id)
    {
        $count = $this->m_job->member_count($id);
        $this->m_job->remove($id);
        $this->log_action('delete_job', 'jobs', $id);

        set_flash('success', $count > 0
            ? 'Đã xoá nghề. ' . number_format($count) . ' hồ sơ chuyển sang bỏ trống nghề nghiệp.'
            : 'Đã xoá nghề nghiệp.');
        redirect('admin/jobs');
    }
}
