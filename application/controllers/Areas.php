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

    /** Đường dẫn cũ /khu-vuc/{slug}: chuyển hướng vĩnh viễn sang /{slug}. */
    public function legacy_province($slug, $page = 1)
    {
        $url = $slug . ($page > 1 ? '/trang/' . (int) $page : '');
        redirect($url, 'location', 301);
    }

    /** /khu-vuc/{slug} — thành viên thuộc một tỉnh. */
    /** Dựng trang nội dung khi slug gốc trỏ tới bảng pages chứ không phải tỉnh. */
    private function pages_view($trang)
    {
        $this->render('pages/view', array(
            'title'       => $trang['title'],
            'meta_desc'   => excerpt($trang['content'], 160),
            'page'        => $trang,
            'other_pages' => $this->db->select('title, slug')->where('is_active', 1)
                ->where('id !=', $trang['id'])->order_by('id')->get('pages')->result_array(),
        ));
    }

    public function province($slug, $page = 1)
    {
        $province = $this->m_province->by_slug($slug);
        if (!$province) {
            // Đường dẫn gốc dùng chung cho tỉnh thành và trang nội dung:
            // không phải tỉnh thì thử tìm trang nội dung trước khi báo 404.
            $trang = $this->db->where('slug', $slug)->where('is_active', 1)
                ->get('pages')->row_array();
            if ($trang) {
                return $this->pages_view($trang);
            }
            show_404();
        }

        // Dùng chung giao diện lọc với trang tìm kiếm, riêng tỉnh cố định theo đường dẫn
        $filters = array_filter(array(
            'keyword'    => $this->input->get('q', true),
            'gender'     => $this->input->get('gender'),
            'age_min'    => $this->input->get('age_min'),
            'age_max'    => $this->input->get('age_max'),
            'height_min' => $this->input->get('height_min'),
            'height_max' => $this->input->get('height_max'),
            'marital'    => $this->input->get('marital'),
            'education'  => $this->input->get('education'),
            'smoking'    => $this->input->get('smoking'),
            'drinking'   => $this->input->get('drinking'),
            'interests'  => $this->input->get('interests'),
            'online'     => $this->input->get('online'),
            'vip'        => $this->input->get('vip'),
            'sort'       => $this->input->get('sort'),
        ));
        if ($this->input->get('has_children') !== null && $this->input->get('has_children') !== '') {
            $filters['has_children'] = $this->input->get('has_children');
        }
        $filters['province_id'] = $province['id'];

        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);

        $this->render('members/index', array(
            'title'      => 'Thành viên tại ' . $province['name'],
            'meta_desc'  => 'Danh sách thành viên đang tìm bạn hẹn hò tại ' . $province['name'] . '.',
            'keyword'    => $this->input->get('q', true),
            'filters'    => $filters,
            'base_url'   => $province['slug'],
            'province'   => $province,
            'view_mode'  => $this->input->get('view') === 'list' ? 'list' : 'grid',
            'interests'  => $this->db->order_by('name')->get('interests')->result_array(),
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links($province['slug'], $page, $total,
                $this->per_page, $this->input->get()),
        ));
    }
}
