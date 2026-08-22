<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_category extends CI_Model
{
    public function all($type = null, $only_active = true)
    {
        if ($type) {
            $this->db->where('type', $type);
        }
        if ($only_active) {
            $this->db->where('is_active', 1);
        }
        return $this->db->order_by('sort')->order_by('name')->get('categories')->result_array();
    }

    /** Danh mục cha kèm mảng con. */
    public function tree($type = 'post')
    {
        $rows = $this->all($type);
        $tree = array();
        foreach ($rows as $row) {
            if (!$row['parent_id']) {
                $row['children'] = array();
                $tree[$row['id']] = $row;
            }
        }
        foreach ($rows as $row) {
            if ($row['parent_id'] && isset($tree[$row['parent_id']])) {
                $tree[$row['parent_id']]['children'][] = $row;
            }
        }
        return array_values($tree);
    }

    public function by_slug($slug, $type = 'post')
    {
        return $this->db->where('slug', $slug)->where('type', $type)->get('categories')->row_array();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get('categories')->row_array();
    }

    public function save($data, $id = null)
    {
        $data['slug'] = unique_slug('categories', $data['slug'] ?: $data['name'], $id);
        if ($id) {
            $this->db->where('id', $id)->update('categories', $data);
            return $id;
        }
        $this->db->insert('categories', $data);
        return $this->db->insert_id();
    }

    public function remove($id)
    {
        $this->db->where('id', $id)->delete('categories');
    }
}
