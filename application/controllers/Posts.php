<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Posts extends MY_Controller
{
    private $per_page = 12;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('m_post', 'm_access_code', 'm_article'));
        $this->require_posts_enabled();
    }

    /** Lọc lấy từ query string, dùng chung cho mọi trang danh sách. */
    private function filters()
    {
        return array_filter(array(
            'keyword'     => $this->input->get('q', true),
            'province_id' => $this->input->get('province_id'),
            'gender'      => $this->input->get('gender'),
            'purpose'     => $this->input->get('purpose'),
            'age_min'     => $this->input->get('age_min'),
            'age_max'     => $this->input->get('age_max'),
            'sort'        => $this->input->get('sort'),
        ));
    }

    private function show_list($title, $filters, $base_url, $page, $extra = array())
    {
        $page   = max(1, (int) $page);
        $offset = ($page - 1) * $this->per_page;
        $total  = $this->m_post->count_listing($filters);

        $this->render('posts/index', array_merge(array(
            'title'      => $title,
            'posts'      => $this->m_post->listing($filters, $this->per_page, $offset),
            'total'      => $total,
            'pagination' => pagination_links($base_url, $page, $total, $this->per_page, $this->input->get()),
            'filters'    => $filters,
        ), $extra));
    }

    public function index($page = 1)
    {
        $this->show_list('Tin đăng mới nhất', $this->filters(), 'tin-dang', $page);
    }

    public function category($slug, $page = 1)
    {
        $cat = $this->m_category->by_slug($slug, 'post');
        if (!$cat) {
            show_404();
        }
        // gộp cả danh mục con để trang danh mục cha vẫn có tin
        $ids = array($cat['id']);
        foreach ($this->m_category->all('post') as $c) {
            if ((int) $c['parent_id'] === (int) $cat['id']) {
                $ids[] = $c['id'];
            }
        }
        $filters = $this->filters();
        $filters['category_ids'] = $ids;

        $this->show_list($cat['name'], $filters, 'danh-muc/' . $slug, $page, array(
            'meta_desc' => $cat['seo_desc'] ?: excerpt($cat['description'], 160),
            'category'  => $cat,
        ));
    }

    public function province($slug, $page = 1)
    {
        $province = $this->m_province->by_slug($slug);
        if (!$province) {
            show_404();
        }
        $filters = $this->filters();
        $filters['province_id'] = $province['id'];

        $this->show_list('Hẹn hò tại ' . $province['name'], $filters, 'khu-vuc/' . $slug, $page);
    }

    public function search()
    {
        $this->show_list('Kết quả tìm kiếm', $this->filters(), 'tim-kiem', $this->input->get('page') ?: 1);
    }

    public function detail($slug)
    {
        $post = $this->m_post->by_slug($slug);
        if (!$post || $post['status'] !== 'approved') {
            // chủ tin và quản trị vẫn xem được tin chưa duyệt
            $me = $this->auth->user();
            $can = $me && ((int) $me['id'] === (int) ($post['user_id'] ?? 0) || $this->auth->is_admin());
            if (!$post || !$can) {
                show_404();
            }
        }

        $this->m_post->increase_view($post['id']);
        $unlocked = $this->m_post->has_unlocked($post['id'], $this->auth->id())
            || ($this->auth->id() && (int) $this->auth->id() === (int) $post['user_id']);

        $this->render('posts/detail', array(
            'title'     => $post['title'],
            'meta_desc' => excerpt($post['content'], 160),
            'post'      => $post,
            'images'    => $this->m_post->images($post['id']),
            'related'   => $this->m_post->related($post, 6),
            'comments'  => $this->db->select('c.*, u.display_name, u.avatar, u.gender, u.slug AS user_slug')
                ->from('post_comments c')->join('users u', 'u.id = c.user_id')
                ->where('c.post_id', $post['id'])->where('c.status', 'approved')
                ->order_by('c.id', 'ASC')->get()->result_array(),
            'unlocked'  => $unlocked,
            'prev'      => $this->db->select('title, slug')->where('id <', $post['id'])
                ->where('status', 'approved')->where('deleted_at', null)
                ->order_by('id', 'DESC')->limit(1)->get('posts')->row_array(),
            'next'      => $this->db->select('title, slug')->where('id >', $post['id'])
                ->where('status', 'approved')->where('deleted_at', null)
                ->order_by('id', 'ASC')->limit(1)->get('posts')->row_array(),
            'quick_links' => $this->m_category->all('post'),
        ));
    }

    /** Bấm "Lấy pass" ở trang chi tiết: cấp mã mở số điện thoại. */
    public function get_pass($post_id)
    {
        if (!$this->auth->check()) {
            return $this->json(array('ok' => false, 'message' => 'Vui lòng đăng nhập để lấy pass.'), 401);
        }
        $post = $this->m_post->find($post_id);
        if (!$post) {
            return $this->json(array('ok' => false, 'message' => 'Tin không tồn tại.'), 404);
        }
        $cost = $this->auth->is_vip() ? 0 : (int) ($post['contact_cost'] ?: setting('unlock_cost', 20));
        $result = $this->m_access_code->issue('contact', $this->auth->id(), $post['id'], $cost, 60);

        return $this->json($result);
    }

    /** Nhập pass để hiện số điện thoại. */
    public function reveal($post_id)
    {
        $post = $this->m_post->find($post_id);
        if (!$post) {
            return $this->json(array('ok' => false, 'message' => 'Tin không tồn tại.'), 404);
        }
        $row = $this->m_access_code->verify($this->input->post('code'), 'contact', $post['id']);
        if (!$row) {
            return $this->json(array('ok' => false, 'message' => 'Mã không đúng hoặc đã hết hạn.'));
        }
        $this->m_access_code->consume($row['id']);

        // ghi nhận đã mở khoá để lần sau không cần nhập lại
        if ($this->auth->check() && !$this->m_post->has_unlocked($post['id'], $this->auth->id())) {
            $this->db->insert('post_contact_unlocks', array(
                'post_id'    => $post['id'],
                'user_id'    => $this->auth->id(),
                'coin_spent' => (int) $row['coin_spent'],
            ));
        }

        return $this->json(array(
            'ok'      => true,
            'contact' => $post['contact_value'],
            'type'    => $post['contact_type'],
        ));
    }
}
