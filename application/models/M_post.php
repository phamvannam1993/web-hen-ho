<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_post extends CI_Model
{
    /** Cột chung khi lấy tin kèm thông tin người đăng. */
    private function base_select()
    {
        return 'p.*, u.display_name, u.slug AS user_slug, u.avatar, u.is_vip, u.last_active_at,
                c.name AS category_name, c.slug AS category_slug,
                pr.name AS province_name, pr.slug AS province_slug';
    }

    private function base_from()
    {
        $this->db->from('posts p')
            ->join('users u', 'u.id = p.user_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('provinces pr', 'pr.id = p.province_id', 'left');
    }

    /**
     * Lọc tin công khai.
     * $f: category_id, province_id, gender, seeking, purpose, keyword, age_min, age_max, featured
     */
    public function listing(array $f, $limit = 12, $offset = 0)
    {
        $this->build($f);
        $this->db->select($this->base_select());
        switch ($f['sort'] ?? 'new') {
            case 'view': $this->db->order_by('p.view_count', 'DESC'); break;
            case 'like': $this->db->order_by('p.like_count', 'DESC'); break;
            default:
                $this->db->order_by('p.is_featured', 'DESC')->order_by('p.published_at', 'DESC');
        }
        return $this->db->limit($limit, $offset)->get()->result_array();
    }

    public function count_listing(array $f)
    {
        $this->build($f);
        return $this->db->count_all_results();
    }

    private function build(array $f)
    {
        $this->base_from();
        $this->db->where('p.status', 'approved')
            ->where('p.deleted_at', null)
            ->group_start()->where('p.expired_at >', date('Y-m-d H:i:s'))->or_where('p.expired_at', null)->group_end();

        if (!empty($f['category_id'])) $this->db->where('p.category_id', (int) $f['category_id']);
        // lọc theo cả danh mục cha lẫn các danh mục con
        if (!empty($f['category_ids'])) $this->db->where_in('p.category_id', array_map('intval', $f['category_ids']));
        if (!empty($f['province_id'])) $this->db->where('p.province_id', (int) $f['province_id']);
        if (!empty($f['gender']))      $this->db->where('p.gender', $f['gender']);
        if (!empty($f['seeking']))     $this->db->where('p.seeking', $f['seeking']);
        if (!empty($f['purpose']))     $this->db->where('p.purpose', $f['purpose']);
        if (!empty($f['featured']))    $this->db->where('p.is_featured', 1);
        if (!empty($f['age_min']))     $this->db->where('p.age >=', (int) $f['age_min']);
        if (!empty($f['age_max']))     $this->db->where('p.age <=', (int) $f['age_max']);
        if (!empty($f['keyword'])) {
            $this->db->group_start()
                ->like('p.title', $f['keyword'])
                ->or_like('p.content', $f['keyword'])
                ->group_end();
        }
    }

    public function by_slug($slug)
    {
        $this->base_from();
        return $this->db->select($this->base_select() . ', u.bio AS user_bio, u.birthday AS user_birthday')
            ->where('p.slug', $slug)->where('p.deleted_at', null)->get()->row_array();
    }

    public function find($id)
    {
        $this->base_from();
        return $this->db->select($this->base_select())->where('p.id', (int) $id)->get()->row_array();
    }

    public function images($post_id)
    {
        return $this->db->where('post_id', $post_id)->order_by('sort')->get('post_images')->result_array();
    }

    /** Tin liên quan cùng danh mục / khu vực. */
    public function related($post, $limit = 6)
    {
        $this->base_from();
        return $this->db->select($this->base_select())
            ->where('p.status', 'approved')->where('p.deleted_at', null)
            ->where('p.id !=', $post['id'])
            ->group_start()
                ->where('p.category_id', $post['category_id'])
                ->or_where('p.province_id', $post['province_id'])
            ->group_end()
            ->order_by('p.published_at', 'DESC')->limit($limit)->get()->result_array();
    }

    public function by_user($user_id, $status = null, $limit = 20, $offset = 0)
    {
        $this->base_from();
        $this->db->select($this->base_select())->where('p.user_id', $user_id)->where('p.deleted_at', null);
        if ($status) $this->db->where('p.status', $status);
        return $this->db->order_by('p.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function create(array $data)
    {
        $auto = setting('auto_approve_post', '0') === '1';
        $days = (int) setting('post_expire_days', 30);
        $data['slug']         = unique_slug('posts', $data['title']);
        $data['status']       = $auto ? 'approved' : 'pending';
        $data['published_at'] = $auto ? date('Y-m-d H:i:s') : null;
        $data['expired_at']   = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
        $this->db->insert('posts', $data);
        return $this->db->insert_id();
    }

    public function update_post($id, array $data)
    {
        $this->db->where('id', $id)->update('posts', $data);
    }

    public function add_images($post_id, array $paths)
    {
        foreach (array_values($paths) as $i => $path) {
            $this->db->insert('post_images', array('post_id' => $post_id, 'path' => $path, 'sort' => $i));
        }
    }

    public function delete_image($image_id, $post_id)
    {
        $this->db->where('id', $image_id)->where('post_id', $post_id)->delete('post_images');
    }

    public function soft_delete($id, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id) $this->db->where('user_id', $user_id);
        $this->db->update('posts', array('deleted_at' => date('Y-m-d H:i:s')));
    }

    public function increase_view($id)
    {
        $this->db->set('view_count', 'view_count + 1', false)->where('id', $id)->update('posts');
    }

    /** Duyệt / từ chối tin, kèm thông báo cho người đăng. */
    public function moderate($id, $status, $reason = null)
    {
        $post = $this->find($id);
        if (!$post) return false;

        $data = array('status' => $status, 'reject_reason' => $reason);
        if ($status === 'approved' && !$post['published_at']) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $id)->update('posts', $data);

        $this->load->model('m_notification');
        if ($status === 'approved') {
            $this->m_notification->push($post['user_id'], 'post_approved', 'Tin đăng đã được duyệt',
                $post['title'], site_url('tin/' . $post['slug']));
        } elseif ($status === 'rejected') {
            $this->m_notification->push($post['user_id'], 'post_rejected', 'Tin đăng bị từ chối',
                $reason ?: $post['title'], site_url('tai-khoan/tin-dang'));
        }
        return true;
    }

    public function set_featured($id, $featured, $days = 7)
    {
        $this->db->where('id', $id)->update('posts', array(
            'is_featured'    => $featured ? 1 : 0,
            'featured_until' => $featured ? date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days')) : null,
        ));
    }

    /** Kiểm tra người dùng đã mở khoá liên hệ của tin chưa. */
    public function has_unlocked($post_id, $user_id)
    {
        if (!$user_id) return false;
        return $this->db->where('post_id', $post_id)->where('user_id', $user_id)
            ->count_all_results('post_contact_unlocks') > 0;
    }

    /** Trừ xu và mở khoá liên hệ. Trả về mảng kết quả. */
    public function unlock_contact($post_id, $user_id)
    {
        $post = $this->find($post_id);
        if (!$post) {
            return array('ok' => false, 'message' => 'Tin không tồn tại.');
        }
        if ((int) $post['user_id'] === (int) $user_id || $this->has_unlocked($post_id, $user_id)) {
            return array('ok' => true, 'contact' => $post['contact_value'], 'type' => $post['contact_type']);
        }

        $this->load->model('m_user');
        $cost = (int) ($post['contact_cost'] ?: setting('unlock_cost', 20));
        if ($this->auth->is_vip()) {
            $cost = 0;
        }
        if ($cost > 0 && !$this->m_user->adjust_coin($user_id, -$cost, 'unlock_contact', 'post', $post_id)) {
            return array('ok' => false, 'message' => 'Số dư xu không đủ. Vui lòng nạp thêm.');
        }
        $this->db->insert('post_contact_unlocks', array(
            'post_id' => $post_id, 'user_id' => $user_id, 'coin_spent' => $cost,
        ));
        return array('ok' => true, 'contact' => $post['contact_value'], 'type' => $post['contact_type'], 'cost' => $cost);
    }

    /* ---------------- Admin ---------------- */

    public function admin_list(array $f, $limit, $offset)
    {
        $this->admin_filter($f);
        return $this->db->select($this->base_select())->order_by('p.id', 'DESC')
            ->limit($limit, $offset)->get()->result_array();
    }

    public function admin_count(array $f)
    {
        $this->admin_filter($f);
        return $this->db->count_all_results();
    }

    private function admin_filter(array $f)
    {
        $this->base_from();
        $this->db->where('p.deleted_at', null);
        if (!empty($f['status']))      $this->db->where('p.status', $f['status']);
        if (!empty($f['category_id'])) $this->db->where('p.category_id', (int) $f['category_id']);
        if (!empty($f['province_id'])) $this->db->where('p.province_id', (int) $f['province_id']);
        if (!empty($f['keyword'])) {
            $this->db->group_start()
                ->like('p.title', $f['keyword'])->or_like('u.display_name', $f['keyword'])
                ->group_end();
        }
    }

    public function stats()
    {
        return array(
            'total'    => (int) $this->db->where('deleted_at', null)->count_all_results('posts'),
            'pending'  => (int) $this->db->where('deleted_at', null)->where('status', 'pending')->count_all_results('posts'),
            'approved' => (int) $this->db->where('deleted_at', null)->where('status', 'approved')->count_all_results('posts'),
            'today'    => (int) $this->db->where('DATE(created_at)', date('Y-m-d'))->count_all_results('posts'),
        );
    }

    /** Đánh dấu hết hạn cho tin quá ngày, gọi từ cron hoặc trang chủ. */
    public function expire_old()
    {
        $this->db->where('status', 'approved')
            ->where('expired_at <', date('Y-m-d H:i:s'))
            ->update('posts', array('status' => 'expired'));
    }
}
