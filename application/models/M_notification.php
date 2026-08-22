<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_notification extends CI_Model
{
    public function push($user_id, $type, $title, $body = null, $url = null)
    {
        $this->db->insert('notifications', array(
            'user_id' => $user_id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'url'     => $url,
        ));
    }

    /** Gửi thông báo hàng loạt (dùng ở admin). */
    public function broadcast(array $user_ids, $title, $body = null, $url = null)
    {
        $rows = array();
        foreach ($user_ids as $id) {
            $rows[] = array('user_id' => $id, 'type' => 'system', 'title' => $title, 'body' => $body, 'url' => $url);
        }
        if ($rows) {
            $this->db->insert_batch('notifications', $rows);
        }
    }

    public function for_user($user_id, $limit = 30)
    {
        return $this->db->where('user_id', $user_id)->order_by('id', 'DESC')
            ->limit($limit)->get('notifications')->result_array();
    }

    public function unread_count($user_id)
    {
        return (int) $this->db->where('user_id', $user_id)->where('read_at', null)
            ->count_all_results('notifications');
    }

    public function mark_all_read($user_id)
    {
        $this->db->where('user_id', $user_id)->where('read_at', null)
            ->update('notifications', array('read_at' => date('Y-m-d H:i:s')));
    }
}
