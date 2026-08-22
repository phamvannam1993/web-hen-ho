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
}
