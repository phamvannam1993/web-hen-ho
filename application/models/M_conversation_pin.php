<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_conversation_pin extends CI_Model
{
    private function positive_id($value)
    {
        $id = filter_var($value, FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1)));
        if ($id === false) {
            throw new InvalidArgumentException('ID không hợp lệ.', 422);
        }
        return $id;
    }

    private function run($sql, array $params)
    {
        $result = $this->db->query($sql, $params);
        if ($result === false) {
            throw new RuntimeException('Không truy cập được dữ liệu ghim.', 503);
        }
        return $result;
    }

    private function conversation($conversation_id, $user_id, $lock = false)
    {
        $conversation_id = $this->positive_id($conversation_id);
        $user_id = $this->positive_id($user_id);
        $row = $this->run(
            'SELECT id, user_low_id, user_high_id FROM conversations WHERE id = ?'
                . ($lock ? ' FOR UPDATE' : ''),
            array($conversation_id)
        )->row_array();
        if (!$row || !in_array($user_id,
            array((int) $row['user_low_id'], (int) $row['user_high_id']), true)) {
            throw new RuntimeException('Không có quyền truy cập hội thoại này.', 403);
        }
        return $row;
    }

    public function state($conversation_id, $user_id)
    {
        $conversation = $this->conversation($conversation_id, $user_id);
        if (!$this->db->table_exists('conversation_pins')) {
            return array('pins_available' => false, 'pinned' => null);
        }
        $pin = $this->run(
            'SELECT m.id, m.conversation_id, m.sender_id, m.type,
                    m.content, m.created_at, p.pinned_by_user_id, p.updated_at
             FROM conversation_pins p
             JOIN messages m ON m.id = p.message_id
                 AND m.conversation_id = p.conversation_id
             WHERE p.conversation_id = ? AND m.deleted_at IS NULL',
            array((int) $conversation['id'])
        )->row_array();
        return array('pins_available' => true, 'pinned' => $pin ?: null);
    }

    public function save($conversation_id, $user_id, $message_id, $pin)
    {
        $conversation = $this->conversation($conversation_id, $user_id);
        $conversation_id = (int) $conversation['id'];
        $user_id = $this->positive_id($user_id);
        $message_id = $this->positive_id($message_id);
        if (!is_bool($pin)) {
            throw new InvalidArgumentException('Trạng thái ghim không hợp lệ.', 422);
        }
        if (!$this->db->table_exists('conversation_pins')) {
            throw new RuntimeException('Tính năng ghim chưa sẵn sàng.', 503);
        }
        if (!$this->db->trans_begin()) {
            throw new RuntimeException('Không bắt đầu được thao tác ghim.', 503);
        }
        try {
            $this->conversation($conversation_id, $user_id, true);
            if ($pin) {
                $message = $this->run(
                    'SELECT id FROM messages
                     WHERE id = ? AND conversation_id = ?
                         AND deleted_at IS NULL FOR UPDATE',
                    array($message_id, $conversation_id)
                )->row_array();
                if (!$message) {
                    throw new RuntimeException('Không tìm thấy tin nhắn.', 404);
                }
                $this->run(
                    'INSERT INTO conversation_pins
                         (conversation_id, message_id, pinned_by_user_id)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                         message_id = VALUES(message_id),
                         pinned_by_user_id = VALUES(pinned_by_user_id),
                         updated_at = CURRENT_TIMESTAMP',
                    array($conversation_id, $message_id, $user_id)
                );
            } else {
                $this->run(
                    'DELETE FROM conversation_pins
                     WHERE conversation_id = ? AND message_id = ?',
                    array($conversation_id, $message_id)
                );
            }
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Không lưu được thay đổi ghim.', 503);
            }
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
        return $this->state($conversation_id, $user_id);
    }
}
