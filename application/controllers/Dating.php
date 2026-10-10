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
                'title'   => 'Cộng đồng tìm kiếm đối tượng hẹn hò nghiêm túc',
                'desc'    => 'Cộng đồng hẹn hò và kết đôi uy tín. Tìm bạn đời, bạn gái, bạn trai '
                           . 'nghiêm túc, ly hôn hay Việt kiều nhanh chóng. Đăng ký kết nối an toàn ngay!',
                'heading' => 'Tìm kiếm đối tượng hẹn hò và bạn bè',
            ),
            'nam' => array(
                'label'   => 'Tìm bạn trai',
                'filters' => array('gender' => 'male'),
                'title'   => 'Tìm Bạn Trai Hẹn Hò Nghiêm Túc',
                'desc'    => 'Danh sách bạn trai độc thân đang tìm người yêu nghiêm túc. '
                           . 'Xem hồ sơ, kết nối và trò chuyện an toàn ngay hôm nay.',
                'heading' => 'Tìm bạn trai hẹn hò nghiêm túc',
            ),
            'nu' => array(
                'label'   => 'Tìm bạn gái',
                'filters' => array('gender' => 'female'),
                'title'   => 'Tìm Bạn Gái Hẹn Hò Nghiêm Túc',
                'desc'    => 'Danh sách bạn gái độc thân đang tìm người yêu nghiêm túc. '
                           . 'Xem hồ sơ, kết nối và trò chuyện an toàn ngay hôm nay.',
                'heading' => 'Tìm bạn gái hẹn hò nghiêm túc',
            ),
            'gay' => array(
                'label'   => 'Gay',
                'filters' => array('gender' => 'male', 'seeking' => 'male'),
                'title'   => 'Tìm Bạn Gay Hẹn Hò Nghiêm Túc',
                'desc'    => 'Cộng đồng hẹn hò dành cho người đồng tính nam, kết bạn và '
                           . 'tìm mối quan hệ nghiêm túc trong môi trường tôn trọng, an toàn.',
                'heading' => 'Tìm bạn gay hẹn hò nghiêm túc',
            ),
            'les' => array(
                'label'   => 'Les',
                'filters' => array('gender' => 'female', 'seeking' => 'female'),
                'title'   => 'Tìm Bạn Les Hẹn Hò Nghiêm Túc',
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
        if (!in_array($sort, array('nearby', 'new', 'active', 'verified'), true)) {
            $sort = 'nearby';
        }

        $origin = null;
        $me = $this->data['user'];
        $origin_province = (int) $this->input->get('origin_province');
        if ((int) $this->input->get('province_id')) $origin_province = (int) $this->input->get('province_id');
        if (!$origin_province) $origin_province = (int) ($me['province_id'] ?? 0);
        if (!$origin_province) $origin_province = (int) $this->input->get('province_id');
            if ($this->m_user->location_ready() && $me && !empty($me['location_updated_at'])
                && strtotime($me['location_updated_at']) >= time() - 30 * 86400
                && is_numeric($me['lat']) && is_numeric($me['lng'])
                && abs($me['lat']) <= 90 && abs($me['lng']) <= 180) {
                $origin = array('lat' => $me['lat'], 'lng' => $me['lng'], 'real' => true, 'province_id' => (int) ($me['province_id'] ?? 0));
            } else {
                foreach ($this->data['provinces'] as $province) {
                    $coordinates = $this->m_user->province_coordinates($province);
                    if ((int) $province['id'] === $origin_province && $coordinates) {
                        $origin = array('lat' => $coordinates[0], 'lng' => $coordinates[1], 'real' => false, 'province_id' => $origin_province);
                        break;
                    }
                }
            }
        $distance_max = (int) $this->input->get('distance_max');
        $guest_location = $this->session->userdata('dating_guest_location');
        if (!$me && is_array($guest_location) && ($guest_location['updated_at'] ?? 0) >= time() - 86400) {
            $origin = array('lat' => $guest_location['lat'], 'lng' => $guest_location['lng'], 'real' => true);
        }
        if (!in_array($distance_max, array(10, 25, 50, 100, 200, 500), true)) $distance_max = 0;
        $filters = array_merge($current['filters'], array('sort' => $sort, 'distance_origin' => $origin, 'distance_max' => $distance_max));
        $gender = $this->input->get('gender');
        if (in_array($gender, array('male', 'female'), true)) $filters['gender'] = $gender;
        $marital = $this->input->get('marital');
        if (in_array($marital, array('doc_than', 'ly_hon', 'goa', 'phuc_tap'), true)) $filters['marital'] = $marital;
        foreach (array('age_min', 'age_max') as $key) {
            $age = (int) $this->input->get($key);
            if ($age >= 18 && $age <= 80) $filters[$key] = $age;
        }
        if (isset($filters['age_min'], $filters['age_max']) && $filters['age_min'] > $filters['age_max']) {
            list($filters['age_min'], $filters['age_max']) = array($filters['age_max'], $filters['age_min']);
        }
        $keyword = $this->input->get('q');
        if (is_string($keyword)) $filters['keyword'] = mb_substr(trim($keyword), 0, 100);
        $token = $this->session->userdata('dating_location_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('dating_location_token', $token);
        }
        if ($this->input->get('province_id')) {
            $filters['province_id'] = $this->input->get('province_id');
        }

        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);
        $base  = 'hen-ho' . ($tab ? '/' . $tab : '');

        $this->render('dating/index', array(
            'allow_index'    => true,
            'title'      => $current['title'],
            'meta_desc'  => $current['desc'],
            'heading'    => $current['heading'],
            'filters'    => $filters,
            'hero_members' => $this->m_user->dating_hero_members(),
            'tabs'       => $tabs,
            'tab'        => $tab,
            'sort'       => $sort,
            'distance_origin' => $origin,
            'origin_province' => $origin_province,
            'distance_max' => $distance_max,
            'location_token' => $token,
            'location_ready' => $this->m_user->location_ready(),
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'base_url'   => $base,
            'pagination' => pagination_links($base, $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    public function location()
    {
        $this->output->set_header('Cache-Control: private, no-store');
        if ($this->input->method() !== 'post') return $this->json(array('ok' => false), 405);
        $token = $this->input->post('location_token');
        $expected = $this->session->userdata('dating_location_token');
        if (!is_string($token) || !$expected || !hash_equals($expected, $token)) return $this->json(array('ok' => false), 403);
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        if (!is_numeric($lat) || !is_numeric($lng) || !is_finite((float) $lat) || !is_finite((float) $lng)
            || abs((float) $lat) > 90 || abs((float) $lng) > 180) return $this->json(array('ok' => false), 422);
        if (!$this->auth->check()) {
            $this->session->set_userdata('dating_guest_location', array('lat' => (float) $lat, 'lng' => (float) $lng, 'updated_at' => time()));
            return $this->json(array('ok' => true));
        }
        if (!$this->m_user->location_ready()) return $this->json(array('ok' => false), 503);
        $saved = $this->db->where('id', $this->auth->id())->update('users', array(
            'lat' => (float) $lat, 'lng' => (float) $lng, 'location_updated_at' => date('Y-m-d H:i:s'),
        ));
        return $this->json(array('ok' => (bool) $saved), $saved ? 200 : 500);
    }
}
