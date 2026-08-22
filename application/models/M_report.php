<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_report extends CI_Model
{
    public function create($reporter_id, $target_type, $target_id, $reason, $note = null)
    {
        $this->db->insert('reports', array(
            'reporter_id' => $reporter_id,
            'target_type' => $target_type,
            'target_id'   => $target_id,
            'reason'      => $reason,
            'note'        => $note,
        ));
        return $this->db->insert_id();
    }

    public function admin_list($status, $limit, $offset)
    {
        if ($status) $this->db->where('r.status', $status);
        return $this->db->select('r.*, u.display_name AS reporter_name')
            ->from('reports r')->join('users u', 'u.id = r.reporter_id', 'left')
            ->order_by('r.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    public function admin_count($status)
    {
        if ($status) $this->db->where('status', $status);
        return $this->db->count_all_results('reports');
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get('reports')->row_array();
    }

    public function resolve($id, $status, $admin_id)
    {
        $this->db->where('id', $id)->update('reports', array(
            'status'     => $status,
            'handled_by' => $admin_id,
            'handled_at' => date('Y-m-d H:i:s'),
        ));
    }

    public function new_count()
    {
        return (int) $this->db->where('status', 'new')->count_all_results('reports');
    }
}
