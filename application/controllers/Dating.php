<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang Hẹn hò: gom hồ sơ theo nhu cầu tìm kiếm, chia thành các tab con
 * /hen-ho, /hen-ho/nam, /hen-ho/nu, /hen-ho/gay, /hen-ho/les.
 *
 * Khách chưa đăng nhập vẫn xem được danh sách, chỉ khi thích hoặc nhắn tin
 * mới cần đăng nhập.
 */
class Dating extends MY_Controller
{
    private $per_page = 24;

    /**
     * Cấu hình từng tab: nhãn hiển thị, bộ lọc áp dụng, và phần thẻ tiêu đề
     * cùng mô tả dành cho công cụ tìm kiếm.
     */
    private function tabs()
    {
        $site = $this->data['settings']['site_name'] ?? 'Saigon Cupid';

        return array(
            '' => array(
                'label'   => 'Tất cả',
                'filters' => array(),
                'title'   => 'Hẹn Hò & Tìm Bạn Đời Nghiêm Túc Tại ' . $site,
                'desc'    => 'Cộng đồng hẹn hò và kết đôi uy tín. Tìm bạn đời, bạn gái, bạn trai '
                           . 'nghiêm túc, ly hôn hay Việt kiều nhanh chóng. Đăng ký kết nối an toàn ngay!',
                'heading' => 'Hẹn hò & tìm bạn đời nghiêm túc',
            ),
            'nam' => array(
                'label'   => 'Tìm bạn trai',
                'filters' => array('gender' => 'male'),
                'title'   => 'Tìm Bạn Trai Hẹn Hò Nghiêm Túc Tại ' . $site,
                'desc'    => 'Danh sách bạn trai độc thân đang tìm người yêu nghiêm túc. '
                           . 'Xem hồ sơ, kết nối và trò chuyện an toàn ngay hôm nay.',
                'heading' => 'Tìm bạn trai hẹn hò nghiêm túc',
            ),
            'nu' => array(
                'label'   => 'Tìm bạn gái',
                'filters' => array('gender' => 'female'),
                'title'   => 'Tìm Bạn Gái Hẹn Hò Nghiêm Túc Tại ' . $site,
                'desc'    => 'Danh sách bạn gái độc thân đang tìm người yêu nghiêm túc. '
                           . 'Xem hồ sơ, kết nối và trò chuyện an toàn ngay hôm nay.',
                'heading' => 'Tìm bạn gái hẹn hò nghiêm túc',
            ),
            'gay' => array(
                'label'   => 'Gay',
                'filters' => array('gender' => 'male', 'seeking' => 'male'),
                'title'   => 'Tìm Bạn Gay Hẹn Hò Nghiêm Túc Tại ' . $site,
                'desc'    => 'Cộng đồng hẹn hò dành cho người đồng tính nam, kết bạn và '
                           . 'tìm mối quan hệ nghiêm túc trong môi trường tôn trọng, an toàn.',
                'heading' => 'Tìm bạn gay hẹn hò nghiêm túc',
            ),
            'les' => array(
                'label'   => 'Les',
                'filters' => array('gender' => 'female', 'seeking' => 'female'),
                'title'   => 'Tìm Bạn Les Hẹn Hò Nghiêm Túc Tại ' . $site,
                'desc'    => 'Cộng đồng hẹn hò dành cho người đồng tính nữ, kết bạn và '
                           . 'tìm mối quan hệ nghiêm túc trong môi trường tôn trọng, an toàn.',
                'heading' => 'Tìm bạn les hẹn hò nghiêm túc',
            ),
        );
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_user');
    }

    public function index($tab = '', $page = 1)
    {
        $tabs = $this->tabs();
        if (!array_key_exists($tab, $tabs)) {
            show_404();
        }
        $current = $tabs[$tab];

        // Sắp xếp: mới tham gia / vừa online / đã xác thực
        $sort = $this->input->get('sort');
        if (!in_array($sort, array('new', 'active', 'verified'), true)) {
            $sort = 'active';
        }

        $filters = array_merge($current['filters'], array('sort' => $sort));
        if ($this->input->get('province_id')) {
            $filters['province_id'] = $this->input->get('province_id');
        }

        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);
        $base  = 'hen-ho' . ($tab ? '/' . $tab : '');

        $this->render('dating/index', array(
            'title'      => $current['title'],
            'meta_desc'  => $current['desc'],
            'heading'    => $current['heading'],
            'tabs'       => $tabs,
            'tab'        => $tab,
            'sort'       => $sort,
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'base_url'   => $base,
            'pagination' => pagination_links($base, $page, $total, $this->per_page, $this->input->get()),
        ));
    }
}
