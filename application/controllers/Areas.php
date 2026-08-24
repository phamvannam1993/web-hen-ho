<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Khu vực: liệt kê thành viên theo tỉnh/thành.
 * Khách chưa đăng nhập vẫn xem được, giống trang danh sách thành viên.
 */
class Areas extends MY_Controller
{
    private $per_page = 16;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_user');
    }

    /** /khu-vuc — tất cả tỉnh/thành kèm số thành viên. */
    public function index()
    {
        $this->render('areas/index', array(
            'title'     => 'Thành viên theo khu vực',
            'meta_desc' => 'Tìm người hẹn hò, kết bạn theo từng tỉnh thành trên cả nước.',
            'list'      => $this->m_province->with_member_count(),
        ));
    }

    /** /khu-vuc/{slug} — thành viên thuộc một tỉnh. */
    public function province($slug, $page = 1)
    {
        $province = $this->m_province->by_slug($slug);
        if (!$province) {
            show_404();
        }

        // Giữ nguyên các bộ lọc phụ trên thanh lọc, riêng tỉnh thì cố định theo đường dẫn
        $filters = array_filter(array(
            'keyword' => $this->input->get('q', true),
            'gender'  => $this->input->get('gender'),
            'age_min' => $this->input->get('age_min'),
            'age_max' => $this->input->get('age_max'),
            'online'  => $this->input->get('online'),
            'sort'    => $this->input->get('sort'),
        ));
        $filters['province_id'] = $province['id'];

        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);

        $this->render('areas/province', array(
            'title'      => 'Thành viên tại ' . $province['name'],
            'meta_desc'  => 'Danh sách thành viên đang tìm bạn hẹn hò tại ' . $province['name'] . '.',
            'province'   => $province,
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links('khu-vuc/' . $province['slug'], $page, $total,
                $this->per_page, $this->input->get()),
        ));
    }
}
