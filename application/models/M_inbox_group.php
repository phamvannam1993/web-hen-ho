<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_inbox_group extends CI_Model
{
    private function id($value, $zero = false)
    {
        if ((!is_string($value) && !is_int($value)) || ($id = filter_var($value,
            FILTER_VALIDATE_INT, array('options' => array('min_range' => $zero ? 0 : 1)))) === false) {
            throw new InvalidArgumentException('ID không hợp lệ.');
        }
        return $id;
    }

    private function run($sql, array $params = array())
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try { $result = $this->db->query($sql, $params); }
        finally { $this->db->db_debug = $debug; }
        if ($result === false) throw new RuntimeException('Chưa truy cập được dữ liệu nhóm.', 503);
        return $result;
    }

    private function ready($user_id)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $ready = $this->db->table_exists('inbox_groups') && $this->db->table_exists('inbox_group_members')
                && $this->db->table_exists('inbox_group_messages');
        } finally { $this->db->db_debug = $debug; }
        if (!$ready) throw new RuntimeException('Nhóm inbox chưa sẵn sàng trên hệ thống.', 503);
        $user_id = $this->id($user_id);
        $user = $this->run('SELECT id FROM users WHERE id=? AND status=? AND deleted_at IS NULL', array($user_id, 'active'))->row_array();
        if (!$user) throw new RuntimeException('Tài khoản không có quyền truy cập nhóm.', 403);
        return $user_id;
    }

    private function group($id, $user_id, $lock = false)
    {
        $id = $this->id($id);
        $group = $this->run('SELECT id, name, owner_id, status, created_at FROM inbox_groups WHERE id=?'
            . ($lock ? ' FOR UPDATE' : ''), array($id))->row_array();
        $member = $this->run('SELECT user_id FROM inbox_group_members WHERE group_id=? AND user_id=? AND left_at IS NULL', array($id, $user_id))->row_array();
        if (!$group || $group['status'] !== 'active' || !$member) {
            throw new RuntimeException('Không có quyền truy cập nhóm này.', 403);
        }
        return $group;
    }

    private function ids($values)
    {
        if (!is_array($values) || array_values($values) !== $values || !$values || count($values) > 50) {
            throw new InvalidArgumentException('Chọn từ 1 đến 50 người mỗi lần.');
        }
        $ids = array_unique(array_map(function ($value) { return $this->id($value); }, $values));
        sort($ids, SORT_NUMERIC);
        return array_values($ids);
    }

    private function targets(array $new_ids, array $existing_ids)
    {
        if (!$new_ids) return;
        $placeholders = implode(',', array_fill(0, count($new_ids), '?'));
        $rows = $this->run('SELECT id FROM users WHERE id IN (' . $placeholders . ')
            AND status=? AND deleted_at IS NULL ORDER BY id FOR UPDATE', array_merge($new_ids, array('active')))->result_array();
        if (count($rows) !== count($new_ids)) throw new InvalidArgumentException('Có thành viên không còn khả dụng.');
        $all = array_values(array_unique(array_merge($existing_ids, $new_ids)));
        $all_placeholders = implode(',', array_fill(0, count($all), '?'));
        $blocked = $this->run('SELECT user_id FROM blocks WHERE
            (user_id IN (' . $placeholders . ') AND blocked_id IN (' . $all_placeholders . '))
            OR (blocked_id IN (' . $placeholders . ') AND user_id IN (' . $all_placeholders . ')) LIMIT 1',
            array_merge($new_ids, $all, $new_ids, $all))->row_array();
        if ($blocked) throw new RuntimeException('Không thể thêm do có quan hệ chặn giữa các thành viên.', 403);
    }

    private function transaction($fn)
    {
        if (!$this->db->trans_begin()) throw new RuntimeException('Chưa bắt đầu được thao tác.', 503);
        try {
            $result = $fn();
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Chưa lưu được thay đổi nhóm.', 503);
            }
            return $result;
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }

    public function create($user_id, $name, $values)
    {
        $user_id = $this->ready($user_id);
        $name = is_string($name) ? trim($name) : '';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || preg_match('/^[\s\p{Cf}]*$/u', $name)) {
            throw new InvalidArgumentException('Tên nhóm cần từ 2 đến 120 ký tự.');
        }
        $ids = array_values(array_diff($this->ids($values), array($user_id)));
        if (!$ids) throw new InvalidArgumentException('Chọn ít nhất một người khác để tạo nhóm.');
        return $this->transaction(function () use ($user_id, $name, $ids) {
            $members = array_merge(array($user_id), $ids); sort($members, SORT_NUMERIC);
            $this->targets($members, array());
            $this->run('INSERT INTO inbox_groups (name, owner_id) VALUES (?, ?)', array($name, $user_id));
            $id = (int) $this->db->insert_id();
            foreach ($members as $member) $this->run('INSERT INTO inbox_group_members (group_id, user_id) VALUES (?, ?)', array($id, $member));
            return array('id' => $id, 'name' => $name, 'owner_id' => $user_id, 'member_count' => count($members));
        });
    }

    public function add($group_id, $user_id, $values)
    {
        $user_id = $this->ready($user_id);
        $ids = $this->ids($values);
        return $this->transaction(function () use ($group_id, $user_id, $ids) {
            $group = $this->group($group_id, $user_id, true);
            $actor = $this->run('SELECT id FROM users WHERE id=? AND status=? AND deleted_at IS NULL FOR UPDATE', array($user_id, 'active'))->row_array();
            if (!$actor) throw new RuntimeException('Tài khoản không có quyền thêm người.', 403);
            $existing = array_map('intval', array_column($this->run('SELECT user_id FROM inbox_group_members WHERE group_id=? AND left_at IS NULL', array($group['id']))->result_array(), 'user_id'));
            $new_ids = array_values(array_diff($ids, $existing));
            $this->targets($new_ids, $existing);
            foreach ($new_ids as $id) {
                $member = $this->run('SELECT user_id FROM inbox_group_members WHERE group_id=? AND user_id=?', array($group['id'], $id))->row_array();
                if ($member) {
                    $this->run('UPDATE inbox_group_members SET left_at=NULL, joined_at=CURRENT_TIMESTAMP, last_read_message_id=0 WHERE group_id=? AND user_id=?', array($group['id'], $id));
                } else $this->run('INSERT INTO inbox_group_members (group_id, user_id) VALUES (?, ?)', array($group['id'], $id));
            }
            return array('id' => (int) $group['id'], 'added_count' => count($new_ids));
        });
    }

    public function listing($user_id, $cursor = '0', $peer_id = null)
    {
        $user_id = $this->ready($user_id);
        $cursor = $this->id($cursor, true);
        $params = array($user_id);
        $sql = 'SELECT g.id, g.name, g.owner_id, g.created_at,
            (SELECT COUNT(*) FROM inbox_group_members x WHERE x.group_id=g.id AND x.left_at IS NULL) AS member_count
            FROM inbox_groups g JOIN inbox_group_members m ON m.group_id=g.id
            WHERE m.user_id=? AND m.left_at IS NULL AND g.status=\'active\'';
        if ($peer_id !== null) {
            $peer_id = $this->id($peer_id);
            $sql .= ' AND EXISTS (SELECT 1 FROM inbox_group_members p WHERE p.group_id=g.id AND p.user_id=? AND p.left_at IS NULL)';
            $params[] = $peer_id;
        }
        if ($cursor) { $sql .= ' AND g.id<?'; $params[] = $cursor; }
        $rows = $this->run($sql . ' ORDER BY g.id DESC LIMIT 21', $params)->result_array();
        $has_more = count($rows) > 20;
        if ($has_more) array_pop($rows);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id']; $row['owner_id'] = (int) $row['owner_id']; $row['member_count'] = (int) $row['member_count'];
            $member = $this->run('SELECT last_read_message_id FROM inbox_group_members WHERE group_id=? AND user_id=?', array($row['id'], $user_id))->row_array();
            $last = $this->visible($row['id'], $user_id, 'x.id, x.content, x.created_at', ' ORDER BY x.id DESC LIMIT 1')->row_array();
            $unread = $this->visible($row['id'], $user_id, 'COUNT(*) AS total', ' AND x.id>? AND x.sender_id<>?', array((int) $member['last_read_message_id'], $user_id))->row_array();
            $row['last_content'] = $last ? $last['content'] : '';
            $row['last_at'] = $last ? $last['created_at'] : null;
            $row['unread'] = (int) $unread['total'];
        }
        unset($row);
        return array('groups' => $rows, 'has_more' => $has_more, 'next_before' => $rows ? $rows[count($rows) - 1]['id'] : 0);
    }

    public function search_users($user_id, $query, $cursor = '0', $group_id = null)
    {
        $user_id = $this->ready($user_id);
        $query = is_string($query) ? trim($query) : '';
        $cursor = $this->id($cursor, true);
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) throw new InvalidArgumentException('Nhập từ 2 đến 100 ký tự để tìm người.');
        if ($group_id !== null) $group_id = (int) $this->group($group_id, $user_id)['id'];
        $pattern = '%' . $this->db->escape_like_str($query) . '%';
        $params = array('active', $user_id, $pattern, $user_id, $user_id);
        $sql = 'SELECT u.id, u.display_name, u.nickname, u.avatar, u.gender FROM users u
            WHERE u.status=? AND u.deleted_at IS NULL AND u.id<>?
            AND COALESCE(NULLIF(TRIM(u.nickname),\'\'),u.display_name) LIKE ? ESCAPE \'!\'
            AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id=? AND b.blocked_id=u.id) OR (b.blocked_id=? AND b.user_id=u.id))';
        if ($group_id !== null) {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM inbox_group_members m WHERE m.group_id=? AND m.user_id=u.id AND m.left_at IS NULL)
                AND NOT EXISTS (SELECT 1 FROM blocks b JOIN inbox_group_members m ON m.group_id=? AND m.left_at IS NULL
                WHERE (b.user_id=u.id AND b.blocked_id=m.user_id) OR (b.blocked_id=u.id AND b.user_id=m.user_id))';
            $params[] = $group_id; $params[] = $group_id;
        }
        if ($cursor) { $sql .= ' AND u.id>?'; $params[] = $cursor; }
        $rows = $this->run($sql . ' ORDER BY u.id ASC LIMIT 21', $params)->result_array();
        $has_more = count($rows) > 20;
        if ($has_more) array_pop($rows);
        $users = array_map(function ($row) {
            return array('id' => (int) $row['id'], 'name' => display_name($row), 'avatar' => avatar_url($row['avatar'], $row['gender']));
        }, $rows);
        return array('users' => $users, 'has_more' => $has_more, 'next_after' => $users ? $users[count($users) - 1]['id'] : 0);
    }

    /** One visibility rule for history, previews and unread after a block. */
    private function visible($group_id, $user_id, $select, $suffix = '', array $params = array())
    {
        return $this->run('SELECT ' . $select . ' FROM inbox_group_messages x JOIN users u ON u.id=x.sender_id
            WHERE x.group_id=? AND x.deleted_at IS NULL AND u.status=\'active\' AND u.deleted_at IS NULL
            AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id=? AND b.blocked_id=x.sender_id)
                OR (b.blocked_id=? AND b.user_id=x.sender_id))' . $suffix,
            array_merge(array($group_id, $user_id, $user_id), $params));
    }

    private function message($row, $user_id)
    {
        return array('id' => (int) $row['id'], 'mine' => (int) $row['sender_id'] === $user_id,
            'sender_id' => (int) $row['sender_id'], 'sender_name' => display_name($row),
            'content' => $row['content'], 'created_at' => $row['created_at']);
    }

    public function rename($group_id, $user_id, $name)
    {
        $user_id = $this->ready($user_id);
        $name = is_string($name) ? trim($name) : '';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || preg_match('/^[\s\p{Cf}]*$/u', $name)) {
            throw new InvalidArgumentException('Tên nhóm cần từ 2 đến 120 ký tự.');
        }
        return $this->transaction(function () use ($group_id, $user_id, $name) {
            $group = $this->group($group_id, $user_id, true);
            if ((int) $group['owner_id'] !== $user_id) throw new RuntimeException('Chỉ người tạo nhóm được đổi tên nhóm.', 403);
            $this->run('UPDATE inbox_groups SET name=? WHERE id=?', array($name, $group['id']));
            return $this->detail($group['id'], $user_id);
        });
    }

    public function remove_member($group_id, $user_id, $target_id)
    {
        $user_id = $this->ready($user_id); $target_id = $this->id($target_id);
        return $this->transaction(function () use ($group_id, $user_id, $target_id) {
            $group = $this->group($group_id, $user_id, true);
            if ((int) $group['owner_id'] !== $user_id) throw new RuntimeException('Chỉ người tạo nhóm được xoá thành viên.', 403);
            if ($target_id === (int) $group['owner_id']) throw new InvalidArgumentException('Không thể xoá người tạo nhóm.');
            $member = $this->run('SELECT user_id FROM inbox_group_members WHERE group_id=? AND user_id=? AND left_at IS NULL', array($group['id'], $target_id))->row_array();
            if (!$member) throw new RuntimeException('Người này không còn trong nhóm.', 409);
            $this->run('UPDATE inbox_group_members SET left_at=? WHERE group_id=? AND user_id=? AND left_at IS NULL', array(date('Y-m-d H:i:s'), $group['id'], $target_id));
            return $this->detail($group['id'], $user_id);
        });
    }

    public function detail($group_id, $user_id)
    {
        $user_id = $this->ready($user_id);
        $group = $this->group($group_id, $user_id);
        $members = $this->run('SELECT u.id, u.display_name, u.nickname, u.avatar, u.gender
            FROM inbox_group_members m JOIN users u ON u.id=m.user_id
            WHERE m.group_id=? AND m.left_at IS NULL AND u.deleted_at IS NULL ORDER BY u.id', array($group['id']))->result_array();
        $group['id'] = (int) $group['id']; $group['owner_id'] = (int) $group['owner_id'];
        $group['can_manage'] = $group['owner_id'] === $user_id;
        $group['members'] = array_map(function ($row) {
            return array('id' => (int) $row['id'], 'name' => display_name($row), 'avatar' => avatar_url($row['avatar'], $row['gender']));
        }, $members);
        return $group;
    }

    public function messages($group_id, $user_id, $before = '0', $after = null)
    {
        $user_id = $this->ready($user_id); $group = $this->group($group_id, $user_id);
        $forward = $after !== null;
        $before = $this->id($before, true); $after = $forward ? $this->id($after, true) : 0;
        if ($before && $forward) throw new InvalidArgumentException('Chỉ chọn một mốc tin nhắn.');
        $suffix = ''; $params = array();
        if ($before) { $suffix = ' AND x.id<?'; $params[] = $before; }
        elseif ($forward) { $suffix = ' AND x.id>?'; $params[] = $after; }
        $rows = $this->visible($group['id'], $user_id, 'x.*, u.display_name, u.nickname',
            $suffix . ' ORDER BY x.id ' . ($forward ? 'ASC' : 'DESC') . ' LIMIT 31', $params)->result_array();
        $more = count($rows) > 30;
        if ($more) array_pop($rows);
        if (!$forward) $rows = array_reverse($rows);
        return array('messages' => array_map(function ($row) use ($user_id) { return $this->message($row, $user_id); }, $rows),
            'has_more' => $more, 'next_before' => $rows ? (int) $rows[0]['id'] : 0);
    }

    public function send($group_id, $user_id, $content, $client_id)
    {
        $user_id = $this->ready($user_id);
        $content = is_string($content) ? trim($content) : '';
        if ($content === '' || mb_strlen($content) > 2000 || preg_match('/^[\s\p{Cf}]*$/u', $content)) {
            throw new InvalidArgumentException('Tin nhắn cần từ 1 đến 2000 ký tự.');
        }
        if (!is_string($client_id) || !preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $client_id)) {
            throw new InvalidArgumentException('Mã gửi tin nhắn không hợp lệ.');
        }
        if (!$this->db->field_exists('client_message_id', 'inbox_group_messages')) {
            throw new RuntimeException('Cần cập nhật dữ liệu nhóm trước khi gửi tin.', 503);
        }
        return $this->transaction(function () use ($group_id, $user_id, $content, $client_id) {
            $group = $this->group($group_id, $user_id, true);
            $actor = $this->run('SELECT id FROM users WHERE id=? AND status=? AND deleted_at IS NULL FOR UPDATE', array($user_id, 'active'))->row_array();
            if (!$actor) throw new RuntimeException('Tài khoản không có quyền gửi tin.', 403);
            $blocked = $this->run('SELECT b.user_id FROM blocks b JOIN inbox_group_members m ON m.group_id=? AND m.left_at IS NULL
                WHERE (b.user_id=? AND b.blocked_id=m.user_id) OR (b.blocked_id=? AND b.user_id=m.user_id) LIMIT 1', array($group['id'], $user_id, $user_id))->row_array();
            if ($blocked) throw new RuntimeException('Không gửi được do có quan hệ chặn với thành viên trong nhóm.', 403);
            $existing = $this->run('SELECT x.*, u.display_name, u.nickname FROM inbox_group_messages x JOIN users u ON u.id=x.sender_id
                WHERE x.group_id=? AND x.sender_id=? AND x.client_message_id=?', array($group['id'], $user_id, $client_id))->row_array();
            if ($existing) {
                if ($existing['content'] !== $content || $existing['deleted_at'] !== null) throw new RuntimeException('Mã gửi đã được dùng cho tin nhắn khác.', 409);
                return $this->message($existing, $user_id);
            }
            $this->run('INSERT INTO inbox_group_messages (group_id, sender_id, type, content, client_message_id) VALUES (?, ?, ?, ?, ?)', array($group['id'], $user_id, 'text', $content, $client_id));
            $id = (int) $this->db->insert_id();
            $row = $this->visible($group['id'], $user_id, 'x.*, u.display_name, u.nickname', ' AND x.id=?', array($id))->row_array();
            if (!$row) throw new RuntimeException('Chưa lưu được tin nhắn.', 503);
            return $this->message($row, $user_id);
        });
    }

    public function read($group_id, $user_id, $message_id)
    {
        $user_id = $this->ready($user_id); $message_id = $this->id($message_id);
        return $this->transaction(function () use ($group_id, $user_id, $message_id) {
            $group = $this->group($group_id, $user_id, true);
            if (!$this->visible($group['id'], $user_id, 'x.id', ' AND x.id=?', array($message_id))->row_array()) {
                throw new InvalidArgumentException('Không tìm thấy tin nhắn trong nhóm.');
            }
            $this->run('UPDATE inbox_group_members SET last_read_message_id=CASE WHEN last_read_message_id<? THEN ? ELSE last_read_message_id END
                WHERE group_id=? AND user_id=? AND left_at IS NULL', array($message_id, $message_id, $group['id'], $user_id));
            return array('read_through' => $message_id);
        });
    }

}
