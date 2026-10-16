<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Multi-room state. actor_key and guest secrets never leave the server. */
class M_chat_group extends CI_Model
{
    public function group($id) { return $this->db->where('id', (int) $id)->get('chat_groups')->row_array(); }

    public function actor($create = false)
    {
        $user = $this->auth->user();
        if ($user) return array('key' => 'u:' . $user['id'], 'user' => $user);
        $token = (string) $this->input->cookie('sc_chat_guest', false);
        $guest = preg_match('/^[a-f0-9]{64}$/D', $token)
            ? $this->db->where('token_hash', hash('sha256', $token))->get('chat_guests')->row_array() : null;
        if (!$guest && $create) {
            $token = bin2hex(random_bytes(32));
            $this->db->insert('chat_guests', array('token_hash' => hash('sha256', $token)));
            $guest = array('id' => $this->db->insert_id());
            set_cookie(array('name' => 'sc_chat_guest', 'value' => $token, 'expire' => 2592000,
                'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax'));
        }
        return $guest ? array('key' => 'g:' . $guest['id'], 'user' => null) : null;
    }

    public function person($group_id, $actor, $create = false)
    {
        if (!$actor) return null;
        $p = $this->db->where('group_id', $group_id)->where('actor_key', $actor['key'])->get('chat_group_people')->row_array();
        if (!$p && $create) {
            $names = array('Mây Tím', 'Nắng Mai', 'Gió Nhẹ', 'Sao Nhỏ', 'Lá Xanh', 'Trăng Non');
            for ($attempt = 0; $attempt < 10 && !$p; $attempt++) {
            $name = $names[random_int(0, count($names) - 1)] . ' ' . random_int(1000, 9999);
            $public = bin2hex(random_bytes(16));
            $this->db->query('INSERT IGNORE INTO chat_group_people (group_id,actor_key,public_id,nickname,user_id,is_following) VALUES (?,?,?,?,?,?)',
                array($group_id, $actor['key'], $public, $name, $actor['user']['id'] ?? null, (int) $group_id === 1 ? 1 : 0));
            $p = $this->person($group_id, $actor);
            }
            if (!$p) throw new RuntimeException('Chưa cấp được nickname. Hãy thử lại.', 503);
        }
        return $p;
    }

    public function moderator($group_id)
    {
        return $this->moderator_as($group_id, $this->auth->user());
    }

    private function moderator_as($group_id, $user)
    {
        if (!$user || $user['status'] !== 'active') return false;
        if ($user['role'] === 'admin') return true;
        return (bool) $this->db->where('group_id', $group_id)->where('user_id', $user['id'])->count_all_results('chat_group_moderators');
    }

    public function access($group, $actor, $send = false)
    {
        if (!$group) throw new RuntimeException('Không tìm thấy nhóm.', 404);
        $p = $this->person($group['id'], $actor);
        if ($p && $p['is_banned']) throw new RuntimeException('Bạn không có quyền truy cập nhóm này.', 403);
        if ($group['status'] === 'closed' && !$this->moderator_as($group['id'], $actor['user'] ?? null)) throw new RuntimeException('Nhóm đã đóng.', 403);
        if ($actor && $actor['user'] && ($actor['user']['status'] !== 'active' || !empty($actor['user']['deleted_at']))) {
            throw new RuntimeException('Tài khoản hiện không có quyền trò chuyện.', 403);
        }
        if (!$send) return $p;
        if ($group['status'] !== 'active') throw new RuntimeException('Nhóm hiện chỉ cho phép đọc.', 403);
        if (!$actor || (!$actor['user'] && $group['identity_mode'] !== 'guest')) throw new RuntimeException('Đăng nhập để gửi tin trong nhóm này.', 401);
        if ($actor['user'] && !$this->auth->da_xac_thuc()) throw new RuntimeException('Bạn cần xác thực tài khoản trước khi nhắn tin.', 403);
        if ($actor['user'] && !in_array($actor['user']['role'], array('admin', 'moderator'), true)) {
            $birthday = $actor['user']['birthday'] ?? '';
            if (!$birthday || $birthday > date('Y-m-d', strtotime('-18 years'))) throw new RuntimeException('Bạn cần đủ 18 tuổi để trò chuyện.', 403);
        }
        if ($p && $p['muted_until'] && strtotime($p['muted_until']) > time()) throw new RuntimeException('Bạn đang bị tạm ngừng gửi tin.', 403);
        return $p;
    }

    public function catalog($q = '', $mode = '', $category = '', $province = 0, $page = 1)
    {
        $this->db->where_in('status', array('active', 'readonly'));
        if ($q !== '') $this->db->group_start()->like('name', mb_substr($q, 0, 100))->or_like('description', mb_substr($q, 0, 100))->group_end();
        if (in_array($mode, array('public', 'guest'), true)) $this->db->where('identity_mode', $mode);
        if ($category !== '') $this->db->where('category', $category);
        if ($province) $this->db->where('province_id', $province);
        return $this->db->order_by('sort_order', 'DESC')->order_by('last_activity_at', 'DESC')->order_by('id', 'DESC')
            ->limit(21, (max(1, $page) - 1) * 20)->get('chat_groups')->result_array();
    }

    private function blocked_keys($actor)
    {
        if (!$actor) return array();
        $keys = array_column($this->db->select('blocked_actor_key')->where('actor_key', $actor['key'])->get('chat_group_blocks')->result_array(), 'blocked_actor_key');
        if ($actor['user']) {
            foreach ($this->db->where('user_id', $actor['user']['id'])->get('blocks')->result_array() as $b) $keys[] = 'u:' . $b['blocked_id'];
        }
        return array_unique($keys);
    }

    private function rows($group_id, $actor)
    {
        $blocked = $this->blocked_keys($actor);
        $this->db->select('r.*, p.nickname AS guest_name, p.public_id AS person_id, u.display_name, u.nickname, u.avatar, u.gender, u.slug')
            ->from('room_messages r')->join('chat_group_people p', 'p.public_id = r.sender_public_id', 'left')
            ->join('users u', 'u.id = r.user_id', 'left')->where('r.room_id', $group_id)->where('r.deleted_at', null);
        if ($blocked) {
            $this->db->group_start()->where('r.actor_key', null)->or_where_not_in('r.actor_key', $blocked)->group_end();
            $ids = array();
            foreach ($blocked as $key) if (substr($key, 0, 2) === 'u:') $ids[] = (int) substr($key, 2);
            if ($ids) $this->db->group_start()->where('r.user_id', null)->or_where_not_in('r.user_id', $ids)->group_end();
        }
    }

    public function serialize($r, $actor)
    {
        $key = $r['actor_key'] ?: ($r['user_id'] ? 'u:' . $r['user_id'] : '');
        $member = !empty($r['user_id']);
        return array('id' => (int) $r['id'], 'mine' => $actor && $key === $actor['key'],
            'sender' => array('id' => $r['person_id'] ?: null, 'name' => $member ? display_name($r) : ($r['guest_name'] ?: 'Khách ẩn danh'),
                'avatar' => $member ? avatar_url($r['avatar'], $r['gender']) : chat_group_guest_avatar_url($r['person_id']),
                'url' => $member && $r['slug'] ? site_url('profile/' . $r['slug']) : null, 'guest' => !$member),
            'type' => $r['type'], 'content' => $r['type'] === 'image' && preg_match('/^[a-f0-9]{48}\.webp$/D', $r['content'])
                ? site_url('nhom-media/' . $r['id']) : ($r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content']),
            'reply_to_id' => $r['reply_to_id'] ? (int) $r['reply_to_id'] : null, 'created_at' => $r['created_at']);
    }

    public function messages($group, $actor, $before = 0, $after = 0)
    {
        $this->access($group, $actor);
        $this->rows($group['id'], $actor);
        if ($before) $this->db->where('r.id <', $before);
        elseif ($after) $this->db->where('r.id >', $after);
        $rows = $this->db->order_by('r.id', $after && !$before ? 'ASC' : 'DESC')->limit(30)->get()->result_array();
        if (!$after || $before) $rows = array_reverse($rows);
        return array_map(function ($r) use ($actor) { return $this->serialize($r, $actor); }, $rows);
    }

    public function visible_message($group, $actor, $id)
    {
        $this->access($group, $actor);
        $this->rows($group['id'], $actor);
        return $this->db->where('r.id', (int) $id)->get()->row_array();
    }

    public function removed($group, $actor, $known, $cursor)
    {
        $this->access($group, $actor);
        $removed = array();
        $senders = array();
        if ($known) {
            $this->rows($group['id'], $actor);
            $visibleRows = $this->db->where_in('r.id', $known)->get()->result_array();
            $visible = array_column($visibleRows, 'id');
            foreach ($visibleRows as $r) if ($r['person_id']) $senders[$r['person_id']] = $this->serialize($r, $actor)['sender'];
            $removed = array_values(array_diff($known, $visible));
        }
        $events = $this->db->where('group_id', $group['id'])->where('action', 'delete')->where('id >', $cursor)
            ->order_by('id')->limit(200)->get('chat_group_logs')->result_array();
        foreach ($events as $e) { $removed[] = (int) $e['target']; $cursor = (int) $e['id']; }
        return array('removed' => array_values(array_unique(array_map('intval', $removed))), 'events_cursor' => $cursor, 'senders' => array_values($senders));
    }

    public function summary($group, $actor)
    {
        $this->access($group, $actor);
        $p = $this->person($group['id'], $actor);
        $this->rows($group['id'], $actor);
        $last = $this->db->order_by('r.id', 'DESC')->limit(1)->get()->row_array();
        $unread = 0;
        if ($p && $p['is_following'] && !$p['is_hidden']) {
            $this->rows($group['id'], $actor);
            $this->db->where('r.id >', $p['last_read_message_id']);
            $this->db->group_start()->where('r.actor_key', null)->or_where('r.actor_key !=', $actor['key'])->group_end();
            if ($actor['user']) $this->db->group_start()->where('r.user_id', null)->or_where('r.user_id !=', $actor['user']['id'])->group_end();
            $unread = (int) $this->db->count_all_results();
        }
        return array('id' => (int) $group['id'], 'name' => $group['name'], 'url' => site_url('nhom/' . $group['slug']),
            'mode' => $group['identity_mode'], 'status' => $group['status'], 'rules' => $group['rules'],
            'avatar' => $group['avatar'] ? base_url($group['avatar']) : base_url('assets/images/' . ($group['identity_mode'] === 'guest' ? 'chat-guest.svg' : 'chat-community.svg')),
            'online' => (int) $this->db->where('group_id', $group['id'])->where('is_banned', 0)
                ->where('last_active_at >', date('Y-m-d H:i:s', time() - 45))->count_all_results('chat_group_people'),
            'following' => $p && (bool) $p['is_following'] && !$p['is_hidden'], 'notifications' => !$p || (bool) $p['notifications_enabled'],
            'unread' => $unread, 'last' => $last ? ($last['type'] === 'image' ? 'Hình ảnh' : mb_substr($last['content'], 0, 100)) : '',
            'pinned' => $group['pinned_message_id'] && ($pin = $this->visible_message($group, $actor, $group['pinned_message_id'])) ? $this->serialize($pin, $actor) : null,
            'moderator' => $this->moderator($group['id']));
    }

    public function online($group, $actor)
    {
        $this->access($group, $actor);
        $keys = $this->blocked_keys($actor);
        $this->db->select('p.public_id,p.nickname AS guest_name,p.user_id,u.display_name,u.nickname,u.avatar,u.gender,u.slug')
            ->from('chat_group_people p')->join('users u', 'u.id = p.user_id', 'left')->where('p.group_id', $group['id'])
            ->where('p.is_banned', 0)->where('p.last_active_at >', date('Y-m-d H:i:s', time() - 45));
        if ($keys) $this->db->where_not_in('p.actor_key', $keys);
        return array_map(function ($p) {
            return array('id' => $p['public_id'], 'name' => $p['user_id'] ? display_name($p) : $p['guest_name'],
                'avatar' => $p['user_id'] ? avatar_url($p['avatar'], $p['gender']) : chat_group_guest_avatar_url($p['public_id']),
                'url' => $p['user_id'] && $p['slug'] ? site_url('profile/' . $p['slug']) : null);
        }, $this->db->order_by('p.id', 'DESC')->limit(100)->get()->result_array());
    }

    /** Rate buckets locked by INSERT/UPDATE inside the send transaction. */
    private function rate($actor)
    {
        $bucket = (int) floor(time() / 60);
        $keys = array(array('actor:' . $actor['key'], $actor['user'] ? 15 : 8));
        if (!$actor['user']) $keys[] = array('ip:' . $this->input->ip_address(), 30);
        foreach ($keys as $item) {
            $key = hash_hmac('sha256', $item[0], $this->config->item('encryption_key'));
            $this->db->query('INSERT INTO chat_group_rate(rate_key,bucket,hits) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hits=hits+1', array($key, $bucket));
            $r = $this->db->query('SELECT hits FROM chat_group_rate WHERE rate_key=? AND bucket=? FOR UPDATE', array($key, $bucket))->row_array();
            if ((int) $r['hits'] > $item[1]) throw new RuntimeException('Bạn nhắn hơi nhanh, nghỉ một chút nhé.', 429);
        }
    }

    public function changed($group_id, $message_id = null) { $this->db->insert('chat_group_outbox', array('group_id' => $group_id, 'message_id' => $message_id)); }

    public function send($group, $actor, $content, $client_id, $reply_id = 0, $type = 'text')
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/D', $client_id)) throw new InvalidArgumentException('Mã gửi tin không hợp lệ.');
        $this->access($group, $actor, true);
        if ($type === 'text') $content = chat_group_content($content);
        elseif ($type !== 'image' || !$group['allow_images'] || !$actor['user']) throw new RuntimeException('Không được gửi ảnh trong nhóm này.', 403);
        $p = $this->person($group['id'], $actor, true);
        $this->db->trans_begin();
        try {
            // Serialize sends/state changes for this room; recheck permissions under the same lock.
            $locked = $this->db->query('SELECT * FROM chat_groups WHERE id=? FOR UPDATE', array($group['id']))->row_array();
            $p = $this->access($locked, $actor, true) ?: $p;
            $existing = $this->db->where('room_id', $group['id'])->where('actor_key', $actor['key'])
                ->where('client_message_id', $client_id)->get('room_messages')->row_array();
            if ($existing) {
                if ($existing['content'] !== $content || $existing['type'] !== $type || (int) $existing['reply_to_id'] !== (int) $reply_id) throw new RuntimeException('Mã gửi đã được dùng cho nội dung khác.', 409);
                $id = $existing['id'];
            } else {
                if ($reply_id && !$this->visible_message($locked, $actor, $reply_id)) throw new RuntimeException('Tin được trả lời không còn hiển thị trong nhóm.', 422);
                $this->rate($actor);
                $this->db->insert('room_messages', array('room_id' => $group['id'], 'user_id' => $actor['user']['id'] ?? null,
                    'actor_key' => $actor['key'], 'sender_public_id' => $p['public_id'], 'type' => $type,
                    'content' => $content, 'client_message_id' => $client_id, 'reply_to_id' => $reply_id ?: null));
                $id = $this->db->insert_id();
                $this->db->where('id', $group['id'])->update('chat_groups', array('last_activity_at' => date('Y-m-d H:i:s')));
                $follow = array('is_following' => 1, 'is_hidden' => 0, 'has_posted' => 1);
                if (!$p['is_following']) $this->db->query('UPDATE chat_group_people SET last_read_message_id=GREATEST(last_read_message_id,?) WHERE id=?', array($id, $p['id']));
                $this->db->where('id', $p['id'])->update('chat_group_people', $follow);
                $this->changed($group['id'], $id);
            }
            if (!$this->db->trans_status()) throw new RuntimeException('Chưa lưu được tin nhắn. Hãy thử lại.', 503);
            $this->db->trans_commit();
            $row = $this->visible_message($group, $actor, $id);
            return $row ? $this->serialize($row, $actor) : array('id' => (int) $id, 'deleted' => true);
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }

    public function action($group, $actor, $action, $input)
    {
        if ($action === 'presence' && empty($input['active'])) {
            // A departing tab must not clear presence held by another active tab.
            return;
        }
        $this->access($group, $actor);
        if (!$actor) throw new RuntimeException('Hãy mở nhóm trước khi thao tác.', 401);
        $p = $this->person($group['id'], $actor, true);
        if ($action === 'follow') {
            $last = (int) $this->db->select_max('id')->where('room_id', $group['id'])->get('room_messages')->row('id');
            $update = array('is_following' => 1, 'is_hidden' => 0);
            if (!$p['is_following']) $this->db->query('UPDATE chat_group_people SET last_read_message_id=GREATEST(last_read_message_id,?) WHERE id=?', array($last, $p['id']));
        } elseif ($action === 'unfollow' || $action === 'hide') $update = array('is_following' => 0, 'is_hidden' => 1, 'last_active_at' => null);
        elseif ($action === 'notifications') $update = array('notifications_enabled' => !empty($input['enabled']) ? 1 : 0);
        elseif ($action === 'presence') $update = array('last_active_at' => !empty($input['active']) ? date('Y-m-d H:i:s') : null);
        elseif ($action === 'read') {
            $id = max(0, (int) ($input['message_id'] ?? 0));
            if ($id && !$this->visible_message($group, $actor, $id)) throw new RuntimeException('Mốc đọc không hợp lệ.', 422);
            $this->db->query('UPDATE chat_group_people SET last_read_message_id=GREATEST(last_read_message_id,?) WHERE id=?', array($id, $p['id']));
            return;
        } elseif ($action === 'block') {
            $target = $this->db->where('group_id', $group['id'])->where('public_id', (string) ($input['target'] ?? ''))->get('chat_group_people')->row_array();
            if (!$target || $target['actor_key'] === $actor['key']) throw new RuntimeException('Không thể chặn người này.', 422);
            $this->db->query('INSERT IGNORE INTO chat_group_blocks(actor_key,blocked_actor_key) VALUES (?,?)', array($actor['key'], $target['actor_key']));
            return;
        } elseif ($action === 'report') {
            $id = (int) ($input['message_id'] ?? 0);
            if (!$this->visible_message($group, $actor, $id)) throw new RuntimeException('Không tìm thấy tin nhắn.', 404);
            $reason = trim((string) ($input['reason'] ?? ''));
            if ($reason === '' || mb_strlen($reason) > 500) throw new InvalidArgumentException('Hãy nhập lý do tối đa 500 ký tự.');
            $this->db->query('INSERT INTO chat_group_reports(group_id,message_id,actor_key,reason) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE reason=VALUES(reason)', array($group['id'], $id, $actor['key'], $reason));
            return;
        } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
        $this->db->where('id', $p['id'])->update('chat_group_people', $update);
    }

    public function moderate($group, $action, $target, $reason)
    {
        if (!$this->moderator($group['id'])) throw new RuntimeException('Bạn không có quyền điều hành nhóm này.', 403);
        if (!$reason || mb_strlen($reason) > 500) throw new InvalidArgumentException('Hãy nhập lý do tối đa 500 ký tự.');
        $this->db->trans_begin();
        try {
            $this->db->query('SELECT id FROM chat_groups WHERE id=? FOR UPDATE', array($group['id']));
            if (in_array($action, array('pin', 'unpin', 'delete'), true)) {
                $r = $this->db->where('room_id', $group['id'])->where('id', (int) $target)->where('deleted_at', null)->get('room_messages')->row_array();
                if (!$r) throw new RuntimeException('Không tìm thấy tin nhắn.', 404);
                if ($action === 'delete') {
                    $this->db->where('id', $r['id'])->update('room_messages', array('deleted_at' => date('Y-m-d H:i:s'), 'deleted_by' => $this->auth->id()));
                    $this->db->where('id', $group['id'])->where('pinned_message_id', $r['id'])->update('chat_groups', array('pinned_message_id' => null));
                } else $this->db->where('id', $group['id'])->update('chat_groups', array('pinned_message_id' => $action === 'pin' ? $r['id'] : null));
            } elseif ($action === 'reset-name') {
                $p = $this->db->where('group_id', $group['id'])->where('public_id', $target)->get('chat_group_people')->row_array();
                if (!$p || $p['user_id']) throw new RuntimeException('Chỉ đặt lại nickname của khách ẩn danh.', 422);
                do { $name = 'Khách ' . bin2hex(random_bytes(4)); }
                while ($this->db->where('group_id',$group['id'])->where('nickname',$name)->count_all_results('chat_group_people'));
                $this->db->where('id',$p['id'])->update('chat_group_people',array('nickname'=>$name));
            } elseif (in_array($action, array('mute', 'unmute', 'ban', 'unban'), true)) {
                $p = $this->db->where('group_id', $group['id'])->where('public_id', $target)->get('chat_group_people')->row_array();
                if (!$p) throw new RuntimeException('Không tìm thấy người trò chuyện.', 404);
                $update = in_array($action, array('ban', 'unban'), true) ? array('is_banned' => $action === 'ban' ? 1 : 0, 'last_active_at' => null)
                    : array('muted_until' => $action === 'mute' ? date('Y-m-d H:i:s', time() + 3600) : null);
                $this->db->where('id', $p['id'])->update('chat_group_people', $update);
            } else throw new InvalidArgumentException('Thao tác không hợp lệ.');
            $this->db->insert('chat_group_logs', array('group_id' => $group['id'], 'actor_id' => $this->auth->id(), 'action' => $action, 'target' => $target, 'reason' => $reason));
            $this->changed($group['id']);
            if (!$this->db->trans_status()) throw new RuntimeException('Chưa lưu được thao tác.', 503);
            $this->db->trans_commit();
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }

    public function saved($actor)
    {
        if (!$actor) return array();
        $default = $this->group(1);
        if ($default && in_array($default['status'], array('active', 'readonly'), true)) {
            $this->person(1, $actor, true);
            // Explicit unfollow/hide sets is_hidden; preserve that choice on every visit.
            $this->db->where('group_id', 1)->where('actor_key', $actor['key'])
                ->where('is_following', 0)->where('is_hidden', 0)->where('is_banned', 0)
                ->update('chat_group_people', array('is_following' => 1));
        }
        $groups = $this->db->select('g.*')->from('chat_groups g')->join('chat_group_people p', 'p.group_id=g.id')
            ->where('p.actor_key', $actor['key'])->where('p.is_following', 1)->where('p.is_hidden', 0)->where('p.is_banned', 0)
            ->where_in('g.status', array('active', 'readonly'))->order_by('g.last_activity_at', 'DESC')->get()->result_array();
        return array_map(function ($g) use ($actor) { return $this->summary($g, $actor); }, $groups);
    }

    public function legacy_recipients($ids, $sender_id)
    {
        if (!$ids) return array();
        $default = $this->group(1);
        if (!$default || $default['status'] === 'closed') return array();
        $allowed = array_column($this->db->where_in('id', $ids)->where('status', 'active')->where('deleted_at', null)->get('users')->result_array(), 'id');
        $banned = array_column($this->db->where('group_id', 1)->where('is_banned', 1)->where_in('user_id', $ids)->get('chat_group_people')->result_array(), 'user_id');
        $blocked = $sender_id ? array_column($this->db->where('blocked_id', $sender_id)->where_in('user_id', $ids)->get('blocks')->result_array(), 'user_id') : array();
        if ($sender_id) {
            $byGuestFeature = $this->db->where('blocked_actor_key', 'u:' . $sender_id)->get('chat_group_blocks')->result_array();
            foreach ($byGuestFeature as $r) if (substr($r['actor_key'], 0, 2) === 'u:') $blocked[] = (int) substr($r['actor_key'], 2);
        }
        return array_values(array_map('intval', array_diff($allowed, $banned, $blocked)));
    }
}
