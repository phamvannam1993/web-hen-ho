<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_notification extends CI_Model
{
    private $has_actor_column;

    private function supports_actor()
    {
        if ($this->has_actor_column === null) {
            $this->has_actor_column = $this->db->field_exists('actor_user_id', 'notifications');
        }
        return $this->has_actor_column;
    }

    private function actor_title($type, $name, $fallback)
    {
        if ($type === 'like') return $name . ' đã thích bạn';
        if ($type === 'match') return 'Bạn và ' . $name . ' đã ghép đôi';
        if ($type === 'message') return $name . ' đã gửi tin nhắn cho bạn';
        return $fallback;
    }

    public function push($user_id, $type, $title, $body = null, $url = null, $actor_user_id = null)
    {
        if ($actor_user_id) {
            $actor = $this->db->select('id, display_name, nickname')->where('id', $actor_user_id)
                ->get('users')->row_array();
            if ($actor) $title = $this->actor_title($type, display_name($actor), $title);
        }
        $row = array(
            'user_id' => $user_id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'url'     => $url,
        );
        if ($this->supports_actor()) $row['actor_user_id'] = $actor_user_id;
        $this->db->insert('notifications', $row);
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
        $this->db->select('n.*')->from('notifications n');
        if ($this->supports_actor()) {
            $this->db->select('u.id AS actor_id, u.display_name AS actor_display_name, u.nickname AS actor_nickname, u.avatar AS actor_avatar, u.gender AS actor_gender')
                ->join('users u', 'u.id = n.actor_user_id', 'left');
        }
        $rows = $this->db->where('n.user_id', $user_id)->order_by('n.id', 'DESC')
            ->limit($limit)->get()->result_array();
        foreach ($rows as &$row) {
            $row['actor'] = null;
            if (!empty($row['actor_id'])) {
                $name = display_name(array('display_name' => $row['actor_display_name'], 'nickname' => $row['actor_nickname']));
                $row['actor'] = array(
                    'id' => (int) $row['actor_id'], 'name' => $name,
                    'avatar' => avatar_url($row['actor_avatar'], $row['actor_gender']),
                );
                $row['title'] = $this->actor_title($row['type'], $name, $row['title']);
                if ($row['type'] === 'match') {
                    $row['url'] = site_url('tai-khoan/tin-nhan') . '?to=' . (int) $row['actor_id'];
                }
            }
        }
        unset($row);
        return $rows;
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
