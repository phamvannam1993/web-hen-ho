<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Trang Tâm sự: nơi tìm người trò chuyện, chia sẻ — khác trang Hẹn hò ở chỗ
 * nhấn vào việc lắng nghe nhau chứ không phải tìm người yêu.
 *
 * /tam-su, /tam-su/nam, /tam-su/nu, /tam-su/gay, /tam-su/les, /tam-su/tuoi-gia
 */
class Confide extends MY_Controller
{
    private $per_page = 24;

    /** Nhãn chủ đề tâm sự, dùng chung cho thẻ và bộ lọc. */
    public static function topics()
    {
        return array(
            'lang_nghe'  => 'Cần người lắng nghe',
            'tro_chuyen' => 'Trò chuyện phiếm',
            'cong_viec'  => 'Chia sẻ công việc',
            'gia_dinh'   => 'Chuyện gia đình',
            'tinh_cam'   => 'Chuyện tình cảm',
            'dem_khuya'  => 'Trò chuyện đêm khuya',
        );
    }

    private function tabs()
    {
        $site = $this->data['settings']['site_name'] ?? 'Saigon Cupid';

        return array(
            '' => array(
                'label'   => 'Tất cả',
                'filters' => array(),
                'title'   => 'Tìm Bạn Tâm Sự, Trò Chuyện & Chia Sẻ - ' . $site,
                'desc'    => 'Cộng đồng kết bạn tâm sự chân thành, kín đáo. Tìm bạn nam, nữ, LGBT '
                           . 'trò chuyện đêm khuya, chia sẻ niềm vui nỗi buồn hoàn toàn an toàn và '
                           . 'tôn trọng riêng tư.',
                'heading' => 'Tìm bạn tâm sự & chia sẻ chân thành tại ' . $site,
            ),
            'nam' => array(
                'label'   => 'Tìm bạn nam',
                'filters' => array('gender' => 'male'),
                'title'   => 'Tìm Bạn Nam Tâm Sự, Trò Chuyện - ' . $site,
                'desc'    => 'Kết bạn với những người bạn nam sẵn sàng lắng nghe và chia sẻ. '
                           . 'Trò chuyện chân thành, kín đáo, tôn trọng riêng tư.',
                'heading' => 'Tìm bạn nam tâm sự',
            ),
            'nu' => array(
                'label'   => 'Tìm bạn nữ',
                'filters' => array('gender' => 'female'),
                'title'   => 'Tìm Bạn Nữ Tâm Sự, Trò Chuyện - ' . $site,
                'desc'    => 'Kết bạn với những người bạn nữ sẵn sàng lắng nghe và chia sẻ. '
                           . 'Trò chuyện chân thành, kín đáo, tôn trọng riêng tư.',
                'heading' => 'Tìm bạn nữ tâm sự',
            ),
            'gay' => array(
                'label'   => 'Tâm sự Gay',
                'filters' => array('gender' => 'male', 'seeking' => 'male'),
                'title'   => 'Tâm Sự Gay - Kết Bạn & Chia Sẻ - ' . $site,
                'desc'    => 'Không gian trò chuyện dành cho cộng đồng đồng tính nam: lắng nghe, '
                           . 'chia sẻ và tôn trọng lẫn nhau.',
                'heading' => 'Tâm sự cùng cộng đồng gay',
            ),
            'les' => array(
                'label'   => 'Les',
                'filters' => array('gender' => 'female', 'seeking' => 'female'),
                'title'   => 'Tâm Sự Les - Kết Bạn & Chia Sẻ - ' . $site,
                'desc'    => 'Không gian trò chuyện dành cho cộng đồng đồng tính nữ: lắng nghe, '
                           . 'chia sẻ và tôn trọng lẫn nhau.',
                'heading' => 'Tâm sự cùng cộng đồng les',
            ),
            'tuoi-gia' => array(
                'label'   => 'Tuổi già',
                'filters' => array('age_min' => 50),
                'title'   => 'Tâm Sự Tuổi Già - Bạn Già Trò Chuyện - ' . $site,
                'desc'    => 'Nơi các cô chú, ông bà tìm bạn trò chuyện, chia sẻ chuyện đời, '
                           . 'chuyện con cháu và vơi đi những ngày dài.',
                'heading' => 'Tâm sự tuổi già',
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

        $sort = $this->input->get('sort');
        if (!in_array($sort, array('new', 'active', 'listened'), true)) {
            $sort = 'active';
        }

        $filters = array_merge($current['filters'], array('sort' => $sort));
        // Lọc thêm theo chủ đề tâm sự nếu người dùng chọn
        if ($this->input->get('topic') && array_key_exists($this->input->get('topic'), self::topics())) {
            $filters['topic'] = $this->input->get('topic');
        }

        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);
        $base  = 'tam-su' . ($tab ? '/' . $tab : '');

        $this->render('confide/index', array(
            'title'      => $current['title'],
            'meta_desc'  => $current['desc'],
            'heading'    => $current['heading'],
            'tabs'       => $tabs,
            'tab'        => $tab,
            'sort'       => $sort,
            'topics'     => self::topics(),
            'topic'      => $filters['topic'] ?? '',
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'base_url'   => $base,
            'pagination' => pagination_links($base, $page, $total, $this->per_page, $this->input->get()),
        ));
    }
}
