<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Members extends MY_Controller
{
    private $per_page = 16;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_user', 'm_post', 'm_interaction', 'm_user_comment'));
    }

    public function index($page = 1)
    {
        $this->show_list('Thành viên', 'thanh-vien', $page);
    }

    /**
     * Ô tìm kiếm ở trang chủ đổ về đây. Trước kia đường dẫn này tìm trong tin đăng,
     * nhưng từ khi chuyển sang mô hình hồ sơ thì phải tìm trong thành viên.
     */
    public function search($page = 1)
    {
        $this->show_list('Kết quả tìm kiếm', 'tim-kiem', $page);
    }

    /** Phần dùng chung của trang danh sách và trang kết quả tìm kiếm. */
    private function show_list($title, $base_url, $page)
    {
        $filters = array_filter(array(
            'keyword'     => $this->input->get('q', true),
            'gender'      => $this->input->get('gender'),
            'province_id' => $this->input->get('province_id'),
            'age_min'     => $this->input->get('age_min'),
            'age_max'     => $this->input->get('age_max'),
            'online'      => $this->input->get('online'),
            'vip'         => $this->input->get('vip'),
            'sort'        => $this->input->get('sort'),
        ));
        $page  = max(1, (int) $page);
        $total = $this->m_user->count_search($filters);

        $this->render('members/index', array(
            'title'      => $title,
            'keyword'    => $this->input->get('q', true),
            'members'    => $this->m_user->search($filters, $this->per_page, ($page - 1) * $this->per_page),
            'total'      => $total,
            'pagination' => pagination_links($base_url, $page, $total, $this->per_page, $this->input->get()),
        ));
    }

    /** Đường dẫn hồ sơ cũ /thanh-vien/{slug}: chuyển hướng vĩnh viễn sang /profile/{slug}. */
    public function legacy_profile($slug)
    {
        redirect('profile/' . $slug, 'location', 301);
    }

    public function profile($slug)
    {
        $member = $this->m_user->by_slug($slug);
        if (!$member || $member['status'] === 'banned') {
            show_404();
        }

        $me = $this->auth->user();
        if ($me && $this->m_interaction->is_blocked($me['id'], $member['id'])) {
            set_flash('warning', 'Bạn không thể xem trang cá nhân này.');
            redirect('thanh-vien');
        }
        $this->auth->touch_active();

        $this->render('members/profile', array(
            'title'      => $member['display_name'],
            'meta_desc'  => excerpt($member['bio'], 160),
            'm'          => $member,
            'photos'     => $this->db->where('user_id', $member['id'])->where('status', 'approved')
                ->order_by('sort')->get('user_photos')->result_array(),
            'posts'      => $this->m_post->by_user($member['id'], 'approved', 6),
            'interests'  => $this->db->select('i.name')->from('user_interests ui')
                ->join('interests i', 'i.id = ui.interest_id')
                ->where('ui.user_id', $member['id'])->get()->result_array(),
            'comments'   => $this->m_user_comment->for_profile($member['id']),
            'liked'      => $me ? $this->m_interaction->has_liked($me['id'], 'user', $member['id']) : false,
            'like_count' => $this->m_interaction->count_likes('user', $member['id']),
            'matched'    => $me ? $this->m_interaction->is_matched($me['id'], $member['id']) : false,
            // Liên kết nhanh dẫn sang các khu vực, thay cho danh mục tin đăng đã ngưng
            'quick_links' => $this->m_province->all(),
        ));
    }

    /** Bấm "Lấy pass" để mở số điện thoại của thành viên. */
    public function get_pass($slug)
    {
        if (!$this->auth->check()) {
            return $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập để lấy pass.'), 401);
        }
        $member = $this->m_user->by_slug($slug);
        if (!$member) {
            return $this->json(array('ok' => false, 'message' => 'Không tìm thấy thành viên.'), 404);
        }
        $this->load->model('m_access_code');
        $cost = $this->auth->is_vip() ? 0 : (int) setting('unlock_cost', 20);

        return $this->json($this->m_access_code->issue('contact', $this->auth->id(), null, $cost, 60));
    }

    /** Nhập pass để hiện số điện thoại. */
    public function reveal($slug)
    {
        $member = $this->m_user->by_slug($slug);
        if (!$member) {
            return $this->json(array('ok' => false, 'message' => 'Không tìm thấy thành viên.'), 404);
        }
        $this->load->model('m_access_code');
        $row = $this->m_access_code->verify($this->input->post('code'), 'contact');
        if (!$row) {
            return $this->json(array('ok' => false, 'message' => 'Mã không đúng hoặc đã hết hạn.'));
        }
        $this->m_access_code->consume($row['id']);

        return $this->json(array(
            'ok'      => true,
            'contact' => $member['phone'] ?: 'Thành viên chưa cập nhật số điện thoại',
        ));
    }

    /** Gửi bình luận lên trang cá nhân. */
    public function comment($slug)
    {
        if (!$this->auth->check()) {
            set_flash('warning', 'Vui lòng đăng nhập để bình luận.');
            redirect('dang-nhap');
        }
        $member = $this->m_user->by_slug($slug);
        if (!$member) {
            show_404();
        }

        $content = trim((string) $this->input->post('content', true));
        $image   = $this->upload_comment_image();

        if ($content === '' && !$image) {
            set_flash('danger', 'Hãy nhập nội dung hoặc chọn ảnh để bình luận.');
        } else {
            $this->m_user_comment->create($member['id'], $this->auth->id(), $content,
                $this->input->post('parent_id'), $image);
            set_flash('success', 'Đã gửi bình luận.');
        }
        redirect('profile/' . $slug . '#binh-luan');
    }

    /** Tải ảnh đính kèm bình luận, trả về đường dẫn tương đối hoặc null. */
    private function upload_comment_image()
    {
        if (empty($_FILES['image']['name'])) {
            return null;
        }
        $dir = FCPATH . 'uploads/comments/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size'      => 5120,
            'encrypt_name'  => true,
        ));
        if (!$this->upload->do_upload('image')) {
            set_flash('warning', strip_tags($this->upload->display_errors()));
            return null;
        }
        $data = $this->upload->data();
        return 'uploads/comments/' . date('Y/m') . '/' . $data['file_name'];
    }

    public function delete_comment($slug, $id)
    {
        if ($this->auth->check()) {
            $this->m_user_comment->delete_own($id, $this->auth->id());
        }
        redirect('profile/' . $slug . '#binh-luan');
    }
}
