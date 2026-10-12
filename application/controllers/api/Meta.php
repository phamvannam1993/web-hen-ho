<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Danh mục dùng chung cho ứng dụng: tỉnh thành, sở thích, nghề nghiệp, nhãn
 * của các trường chọn. Ít đổi nên ứng dụng tải một lần lúc mở rồi giữ lại.
 */
class Meta extends Api_Controller
{
    /** GET /api/meta */
    public function index()
    {
        $this->load->model(array('m_province', 'm_job', 'm_setting'));
        $s = $this->m_setting->all();

        $tinh = array();
        foreach ($this->m_province->all() as $p) {
            $tinh[] = array('id' => (int) $p['id'], 'name' => $p['name']);
        }
        $so_thich = array();
        foreach ($this->db->order_by('name')->get('interests')->result_array() as $i) {
            $so_thich[] = array('id' => (int) $i['id'], 'name' => $i['name']);
        }

        return $this->ok(array(
            'site' => array(
                'name'          => $s['site_name'] ?? 'Saigon Cupid',
                'url'           => base_url(),
                'hotline'       => $s['hotline'] ?? null,
                'contact_email' => $s['contact_email'] ?? null,
                'unlock_cost'   => (int) ($s['unlock_cost'] ?? 20),
                'bank_info'     => $s['bank_info'] ?? null,
                'enable_posts'  => !empty($s['enable_posts']),
                // Ảnh nền khối mở đầu trang chủ — cùng cấu hình với web
                'hero_image'    => base_url(ltrim(($s['home_hero_image'] ?? '') ?: 'assets/images/banner.png', '/')),
            ),
            'provinces' => $tinh,
            'interests' => $so_thich,
            'jobs'      => array_values($this->m_job->names()),
            // Nhãn hiển thị — trùng với các mảng trong views/discover/_card.php
            'labels' => array(
                'gender'    => array('male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'),
                'seeking'   => array('male' => 'Nam', 'female' => 'Nữ', 'all' => 'Tất cả'),
                'purpose'   => array('ket_ban' => 'Kết bạn', 'hen_ho' => 'Hẹn hò',
                                     'nghiem_tuc' => 'Tìm hiểu nghiêm túc', 'ket_hon' => 'Tiến tới hôn nhân'),
                'marital'   => array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp'),
                'education' => array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
                                     'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học'),
                'frequency' => array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên'),
                'allow_message' => array('all' => 'Mọi người đã ghép đôi', 'vip' => 'Chỉ thành viên VIP'),
                // Trùng với Confide::topics()
                'confide_topic' => array(
                    'lang_nghe'  => 'Cần người lắng nghe',
                    'tro_chuyen' => 'Trò chuyện phiếm',
                    'cong_viec'  => 'Chia sẻ công việc',
                    'gia_dinh'   => 'Chuyện gia đình',
                    'tinh_cam'   => 'Chuyện tình cảm',
                    'dem_khuya'  => 'Trò chuyện đêm khuya',
                ),
                // Trùng với ENUM reports.reason
                'report_reason' => array(
                    'lua_dao' => 'Lừa đảo', 'mao_danh' => 'Mạo danh người khác',
                    'noi_dung_xau' => 'Nội dung không phù hợp', 'spam' => 'Spam / quảng cáo',
                    'khac' => 'Lý do khác',
                ),
            ),
        ));
    }
}
