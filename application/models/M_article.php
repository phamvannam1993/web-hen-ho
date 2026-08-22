<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tin tức / cẩm nang hẹn hò. */
class M_article extends CI_Model
{
    public function published($limit = 10, $offset = 0, $category_id = null)
    {
        $this->db->select('a.*, c.name AS category_name, c.slug AS category_slug')
            ->from('articles a')->join('categories c', 'c.id = a.category_id', 'left')
            ->where('a.status', 'published')->where('a.published_at <=', date('Y-m-d H:i:s'));
        if ($category_id) $this->db->where('a.category_id', (int) $category_id);
        return $this->db->order_by('a.published_at', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function count_published($category_id = null)
    {
        $this->db->from('articles')->where('status', 'published')
            ->where('published_at <=', date('Y-m-d H:i:s'));
        if ($category_id) $this->db->where('category_id', (int) $category_id);
        return $this->db->count_all_results();
    }

    public function by_slug($slug)
    {
        return $this->db->select('a.*, c.name AS category_name, c.slug AS category_slug')
            ->from('articles a')->join('categories c', 'c.id = a.category_id', 'left')
            ->where('a.slug', $slug)->get()->row_array();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get('articles')->row_array();
    }

    public function increase_view($id)
    {
        $this->db->set('view_count', 'view_count + 1', false)->where('id', $id)->update('articles');
    }

    public function admin_list($keyword, $limit, $offset)
    {
        if ($keyword) $this->db->like('title', $keyword);
        return $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get('articles')->result_array();
    }

    public function admin_count($keyword)
    {
        if ($keyword) $this->db->like('title', $keyword);
        return $this->db->count_all_results('articles');
    }

    public function save(array $data, $id = null)
    {
        $data['slug'] = unique_slug('articles', $data['slug'] ?: $data['title'], $id);
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        if ($id) {
            $this->db->where('id', $id)->update('articles', $data);
            return $id;
        }
        $this->db->insert('articles', $data);
        return $this->db->insert_id();
    }

    public function remove($id)
    {
        $this->db->where('id', $id)->delete('articles');
    }
}
