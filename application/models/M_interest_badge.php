<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Unseen entries in the four interest tabs, persisted per account and event version. */
class M_interest_badge extends CI_Model
{
    private $seen_ready;

    private function ensure_seen_table()
    {
        if ($this->seen_ready !== null) return $this->seen_ready;
        if ($this->db->table_exists('account_interest_seen')) return $this->seen_ready = true;
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $created = $this->db->query(file_get_contents(__DIR__ . '/../../database/interest_seen.sql'));
        } finally {
            $this->db->db_debug = $debug;
        }
        unset($this->db->data_cache['table_names']);
        return $this->seen_ready = (bool) $created;
    }
    public function tabs()
    {
        return array('thich-ban', 'ghep-doi', 'da-xem', 'ban-thich');
    }

    private function sources($user_id)
    {
        $id = (int) $user_id;
        $blocked = "NOT EXISTS (SELECT 1 FROM blocks b WHERE
            (b.user_id = $id AND b.blocked_id = u.id) OR (b.blocked_id = $id AND b.user_id = u.id))";
        return array(
            'thich-ban' => "SELECT l.id AS item_id, CAST(l.id AS CHAR) AS version FROM likes l
                JOIN users u ON u.id = l.user_id WHERE l.target_id = $id AND l.target_type = 'user'
                AND l.status = 'pending' AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND u.deleted_at IS NULL AND $blocked",
            'ghep-doi' => "SELECT m.id AS item_id, CAST(m.id AS CHAR) AS version FROM matches m
                JOIN users u ON u.id = IF(m.user_low_id = $id, m.user_high_id, m.user_low_id)
                WHERE $id IN (m.user_low_id, m.user_high_id) AND u.deleted_at IS NULL",
            'da-xem' => "SELECT v.viewer_id AS item_id, CONCAT(v.viewed_at, ':', v.view_count) AS version
                FROM profile_views v JOIN users u ON u.id = v.viewer_id WHERE v.owner_id = $id
                AND v.viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.deleted_at IS NULL AND $blocked",
            'ban-thich' => "SELECT l.id AS item_id, CAST(l.id AS CHAR) AS version FROM likes l
                JOIN users u ON u.id = l.target_id WHERE l.user_id = $id AND l.target_type = 'user'
                AND l.status <> 'matched' AND u.deleted_at IS NULL",
        );
    }

    public function counts($user_id, $unread_only = true)
    {
        $counts = array_fill_keys($this->tabs(), 0);
        if ($unread_only && !$this->ensure_seen_table()) return $counts;
        $parts = array();
        foreach ($this->sources($user_id) as $tab => $source) {
            $parts[] = "SELECT '$tab' AS tab, COUNT(*) AS unread FROM ($source) events" . ($unread_only ? "
                LEFT JOIN account_interest_seen seen ON seen.user_id = " . (int) $user_id . "
                AND seen.tab = '$tab' AND seen.item_id = events.item_id
                AND CAST(seen.version AS BINARY) = CAST(events.version AS BINARY)
                WHERE seen.item_id IS NULL" : '');
        }
        foreach ($this->db->query(implode(' UNION ALL ', $parts))->result_array() as $row) {
            $counts[$row['tab']] = (int) $row['unread'];
        }
        return $counts;
    }

    public function mark_seen($user_id, $tab)
    {
        if (!in_array($tab, $this->tabs(), true) || !$this->ensure_seen_table()) return false;
        $source = $this->sources($user_id)[$tab];
        $this->db->query("INSERT INTO account_interest_seen (user_id, tab, item_id, version)
            SELECT " . (int) $user_id . ", '$tab', events.item_id, events.version FROM ($source) events
            ON DUPLICATE KEY UPDATE version = VALUES(version)");
        $types = array('thich-ban' => 'like', 'ghep-doi' => 'match', 'da-xem' => 'view');
        if (isset($types[$tab])) {
            $this->db->where('user_id', (int) $user_id)->where('type', $types[$tab])->where('read_at', null)
                ->update('notifications', array('read_at' => date('Y-m-d H:i:s')));
        }
        return true;
    }

    public function menu_counts($user_id)
    {
        $tabs = $this->counts($user_id);
        $this->load->model(array('m_interaction', 'm_notification'));
        $messages = $this->m_interaction->unread_count($user_id);
        $other_notifications = (int) $this->db->where('user_id', (int) $user_id)->where('read_at', null)
            ->where_not_in('type', array('like', 'match', 'view', 'message'))->count_all_results('notifications');
        return array('interest' => array_sum($tabs), 'liked' => $tabs['thich-ban'],
            'viewers' => $tabs['da-xem'], 'interest_tabs' => $tabs,
            'total' => array_sum($tabs) + $messages + $other_notifications,
            'msg' => $messages,
            'noti' => $this->m_notification->unread_count($user_id));
    }
}
