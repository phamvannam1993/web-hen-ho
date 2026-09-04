<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_province extends CI_Model
{
    private static $cache = null;

    public function all()
    {
        if (self::$cache === null) {
            self::$cache = $this->db->order_by('sort')->order_by('name')->get('provinces')->result_array();
        }
        return self::$cache;
    }

    public function by_slug($slug)
    {
        return $this->db->where('slug', $slug)->get('provinces')->row_array();
    }

    /** Danh sách tỉnh kèm số tin đang hiển thị, dùng cho khối "khu vực nổi bật". */
    public function with_post_count($limit = 12)
    {
        return $this->db->query(
            "SELECT p.*, COUNT(po.id) AS post_count
               FROM provinces p
          LEFT JOIN posts po ON po.province_id = p.id AND po.status = 'approved' AND po.deleted_at IS NULL
           GROUP BY p.id
           ORDER BY post_count DESC, p.sort ASC
              LIMIT ?", array((int) $limit)
        )->result_array();
    }

    public function find($id)
    {
        return $this->db->where('id', (int) $id)->get('provinces')->row_array();
    }

    /** Danh sách tỉnh kèm số thành viên đang hoạt động, dùng cho trang khu vực. */
    /**
     * Số thành viên từng tỉnh, hiện ở khối "Khu vực nổi bật".
     * Phải đếm đúng những người thật sự hiện ra ngoài — tức hồ sơ đã khai đủ —
     * nếu không con số bên ngoài sẽ không khớp với danh sách trong trang tỉnh.
     */
    public function with_member_count()
    {
        $this->load->model('m_user');
        $du = $this->m_user->dieu_kien_ho_so_du('u');

        return $this->db->query(
            "SELECT p.*, COUNT(u.id) AS member_count
               FROM provinces p
          LEFT JOIN users u ON u.province_id = p.id
                           AND u.status = 'active' AND u.role = 'member' AND u.deleted_at IS NULL
                           AND $du
           GROUP BY p.id
           ORDER BY member_count DESC, p.sort ASC, p.name ASC"
        )->result_array();
    }

    /* ------------------------- Dùng cho khu quản trị ------------------------- */

    public function admin_list($keyword = null, $region = null)
    {
        $this->db->select('p.*,
            (SELECT COUNT(*) FROM users u
              WHERE u.province_id = p.id AND u.deleted_at IS NULL) AS member_count')
            ->from('provinces p');

        if ($keyword) {
            $this->db->group_start()->like('p.name', $keyword)->or_like('p.slug', $keyword)->group_end();
        }
        if ($region) {
            $this->db->where('p.region', $region);
        }
        return $this->db->order_by('p.sort')->order_by('p.name')->get()->result_array();
    }

    public function save(array $data, $id = null)
    {
        $data['slug'] = unique_slug('provinces', $data['slug'] ?: $data['name'], $id);

        if ($id) {
            $this->db->where('id', (int) $id)->update('provinces', $data);
            self::$cache = null;
            return (int) $id;
        }
        $this->db->insert('provinces', $data);
        self::$cache = null;
        return (int) $this->db->insert_id();
    }

    /** Số thành viên đang gắn với tỉnh này, để cảnh báo trước khi xoá. */
    public function member_count($id)
    {
        return (int) $this->db->where('province_id', (int) $id)
            ->where('deleted_at', null)->count_all_results('users');
    }

    public function remove($id)
    {
        // Khoá ngoại đặt ON DELETE SET NULL nên thành viên không bị mất,
        // chỉ chuyển sang trạng thái chưa rõ khu vực.
        $this->db->where('id', (int) $id)->delete('provinces');
        self::$cache = null;
    }
}
