<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Chỉ nhận kết quả do máy chủ báo hiệu tính toán, không nhận nội dung từ người dùng. */
class M_call_message extends CI_Model
{
    public function save_result(array $data)
    {
        $call_id = $data['callId'] ?? null;
        $cid = $data['conversationId'] ?? null;
        $uid = $data['uid'] ?? null;
        $peer = $data['peerId'] ?? null;
        $seconds = $data['durationSeconds'] ?? null;
        $outcome = $data['outcome'] ?? null;
        if (!is_string($call_id) || !preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $call_id)
            || !is_int($cid) || $cid <= 0 || !is_int($uid) || $uid <= 0
            || !is_int($peer) || $peer <= 0 || $uid === $peer
            || !is_int($seconds) || $seconds < 0 || $seconds > 3000
            || !isset($data['video']) || !is_bool($data['video'])
            || !in_array($outcome, array('completed','missed','declined','busy','failed'), true)
            || ($outcome !== 'completed' && $seconds !== 0)) {
            throw new InvalidArgumentException('Kết quả cuộc gọi không hợp lệ.', 422);
        }
        $label = $data['video'] ? 'Cuộc gọi video' : 'Cuộc gọi thoại';
        $suffix = array('missed' => 'nhỡ', 'declined' => 'đã bị từ chối',
            'busy' => 'không thành công · Máy bận', 'failed' => 'không kết nối được');
        $content = $outcome === 'completed'
            ? $label . ' · ' . intdiv($seconds, 60) . ' phút ' . ($seconds % 60) . ' giây'
            : $label . ' ' . $suffix[$outcome];
        $run = function ($sql, $params) {
            $query = $this->db->query($sql, $params);
            if ($query === false) throw new RuntimeException('Không lưu được cuộc gọi.', 503);
            return $query;
        };
        if (!$this->db->trans_begin()) throw new RuntimeException('Không lưu được cuộc gọi.', 503);
        try {
            // Serialize with sends/recalls/history deletion, then deduplicate retries.
            $conv = $run('SELECT id, user_low_id, user_high_id FROM conversations WHERE id = ? FOR UPDATE', array($cid))->row_array();
            if (!$conv || min($uid, $peer) !== (int) $conv['user_low_id']
                || max($uid, $peer) !== (int) $conv['user_high_id']) {
                throw new RuntimeException('Hội thoại không hợp lệ.', 403);
            }
            $existing = $run('SELECT * FROM messages WHERE call_id = ?', array(strtolower($call_id)))->row_array();
            if ($existing && ((int) $existing['conversation_id'] !== $cid || (int) $existing['sender_id'] !== $uid)) {
                throw new RuntimeException('Mã cuộc gọi đã được sử dụng.', 409);
            }
            $row = $existing;
            if (!$existing) {
                if (!$this->db->insert('messages', array('conversation_id'=>$cid, 'sender_id'=>$uid,
                    'call_id'=>strtolower($call_id), 'type'=>'system', 'content'=>$content))) {
                    throw new RuntimeException('Không lưu được cuộc gọi.', 503);
                }
                $id = (int) $this->db->insert_id();
                $run('UPDATE conversations SET
                    last_message_at = CASE WHEN COALESCE(last_message_id, 0) <= ? THEN CURRENT_TIMESTAMP ELSE last_message_at END,
                    last_message_id = CASE WHEN COALESCE(last_message_id, 0) <= ? THEN ? ELSE last_message_id END WHERE id = ?',
                    array($id, $id, $id, $cid));
                $row = $run('SELECT * FROM messages WHERE id = ?', array($id))->row_array();
            }
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Không lưu được cuộc gọi.', 503);
            }
            return array('row'=>$row, 'created'=>!$existing);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }
}
