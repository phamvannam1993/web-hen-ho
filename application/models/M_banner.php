<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_banner extends CI_Model
{
    public function by_position($position)
    {
        return $this->db->where('position', $position)->where('is_active', 1)
            ->order_by('sort')->get('banners')->result_array();
    }

    public function all()
    {
        return $this->db->order_by('position')->order_by('sort')->get('banners')->result_array();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get('banners')->row_array();
    }

    public function save(array $data, $id = null)
    {
        if ($id) {
            $this->db->where('id', $id)->update('banners', $data);
            return $id;
        }
        $this->db->insert('banners', $data);
        return $this->db->insert_id();
    }

    public function remove($id)
    {
        $this->db->where('id', $id)->delete('banners');
    }
}
