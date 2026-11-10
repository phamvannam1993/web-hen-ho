<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_conversation_notification extends CI_Model
{
    private function id($value)
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, array('options'=>array('min_range'=>1)));
        if ($id === false) throw new InvalidArgumentException('ID không hợp lệ.',422);
        return $id;
    }

    private function run($sql, array $params)
    {
        $query = $this->db->query($sql,$params);
        if (!$query) throw new RuntimeException('Không truy cập được cài đặt thông báo.',503);
        return $query;
    }

    protected function now() { return time(); }

    public function options_available()
    {
        if (!$this->db->table_exists('conversation_notification_preferences')) return false;
        foreach (array('message_muted_until','calls_notifications_enabled','calls_muted_until') as $field) {
            if (!$this->db->field_exists($field,'conversation_notification_preferences')) return false;
        }
        return true;
    }

    private function preferences($conversation_id,$user_id)
    {
        $conversation_id = $this->id($conversation_id); $user_id = $this->id($user_id);
        if (!$this->db->table_exists('conversation_notification_preferences')) return array();
        $fields = $this->options_available() ? 'notifications_enabled, message_muted_until, calls_notifications_enabled, calls_muted_until' : 'notifications_enabled';
        return $this->run('SELECT '.$fields.' FROM conversation_notification_preferences
            WHERE conversation_id = ? AND user_id = ?',array($conversation_id,$user_id))->row_array() ?: array();
    }

    private function effective(array $row,$kind)
    {
        $flag=$kind === 'calls' ? 'calls_notifications_enabled' : 'notifications_enabled';
        $until=$kind === 'calls' ? 'calls_muted_until' : 'message_muted_until';
        return !isset($row[$flag]) || (bool)$row[$flag] || (!empty($row[$until]) && (int)$row[$until] <= $this->now());
    }

    public function enabled($conversation_id,$user_id,$kind='messages')
    {
        if (!in_array($kind,array('messages','calls'),true)) throw new InvalidArgumentException('Loại thông báo không hợp lệ.',422);
        return $this->effective($this->preferences($conversation_id,$user_id),$kind);
    }

    public function state($conversation_id, $user_id)
    {
        $conversation_id = $this->id($conversation_id); $user_id = $this->id($user_id);
        $row = $this->run('SELECT id FROM conversations WHERE id = ? AND ? IN (user_low_id, user_high_id)',
            array($conversation_id,$user_id))->row_array();
        if (!$row) throw new RuntimeException('Không có quyền truy cập hội thoại này.',403);
        $prefs=$this->preferences($conversation_id,$user_id);
        $messages=$this->effective($prefs,'messages'); $calls=$this->effective($prefs,'calls');
        return array('id'=>$conversation_id,'notifications_enabled'=>$messages,'calls_notifications_enabled'=>$calls,
            'muted'=>!$messages || !$calls,'message_muted_until'=>$messages ? null : ($prefs['message_muted_until'] ?? null),
            'calls_muted_until'=>$calls ? null : ($prefs['calls_muted_until'] ?? null),'server_time'=>$this->now(),
            'mute_options_available'=>$this->options_available(),
            'notifications_available'=>$this->db->table_exists('conversation_notification_preferences'));
    }

    public function configure($conversation_id,$user_id,$scope,$duration,$enable=false)
    {
        $conversation_id=$this->id($conversation_id); $user_id=$this->id($user_id);
        if (!in_array($scope,array('messages','calls','both'),true) || !is_bool($enable)) throw new InvalidArgumentException('Loại thông báo không hợp lệ.',422);
        if (!is_int($duration) || !in_array($duration,array(0,900,3600,28800,86400),true)) throw new InvalidArgumentException('Thời hạn tắt thông báo không hợp lệ.',422);
        if (!$this->options_available()) throw new RuntimeException('Cần cập nhật cơ sở dữ liệu để chọn loại và thời hạn tắt thông báo.',503);
        $until=!$enable && $duration ? $this->now()+$duration : null;
        $columns=array('conversation_id','user_id'); $params=array($conversation_id,$user_id); $updates=array();
        foreach (array('messages','calls') as $kind) {
            if ($scope !== 'both' && $scope !== $kind) continue;
            $flag=$kind === 'messages' ? 'notifications_enabled' : 'calls_notifications_enabled';
            $deadline=$kind === 'messages' ? 'message_muted_until' : 'calls_muted_until';
            $columns[]=$flag; $params[]=$enable ? 1 : 0; $columns[]=$deadline; $params[]=$until;
            $updates[]=$flag.' = VALUES('.$flag.')'; $updates[]=$deadline.' = VALUES('.$deadline.')';
        }
        if (!$this->db->trans_begin()) throw new RuntimeException('Không bắt đầu được thao tác.',503);
        try {
            $row=$this->run('SELECT id FROM conversations WHERE id = ? AND ? IN (user_low_id, user_high_id) FOR UPDATE',array($conversation_id,$user_id))->row_array();
            if (!$row) throw new RuntimeException('Không có quyền truy cập hội thoại này.',403);
            $this->run('INSERT INTO conversation_notification_preferences ('.implode(', ',$columns).') VALUES ('
                .implode(', ',array_fill(0,count($columns),'?')).') ON DUPLICATE KEY UPDATE '.implode(', ',$updates).', updated_at = CURRENT_TIMESTAMP',$params);
            $state=$this->state($conversation_id,$user_id);
            if (!$this->db->trans_status() || !$this->db->trans_commit()) throw new RuntimeException('Không lưu được cài đặt thông báo.',503);
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
        return $state;
    }

    public function save($conversation_id, $user_id, $enabled)
    {
        $conversation_id = $this->id($conversation_id); $user_id = $this->id($user_id);
        if (!is_bool($enabled)) throw new InvalidArgumentException('Trạng thái thông báo không hợp lệ.',422);
        if ($this->options_available()) return $this->configure($conversation_id,$user_id,'messages',0,$enabled);
        if (!$this->db->table_exists('conversation_notification_preferences')) {
            throw new RuntimeException('Tùy chọn thông báo chưa sẵn sàng. Cần cập nhật cơ sở dữ liệu.',503);
        }
        if (!$this->db->trans_begin()) throw new RuntimeException('Không bắt đầu được thao tác.',503);
        try {
            $row = $this->run('SELECT id FROM conversations WHERE id = ? AND ? IN (user_low_id, user_high_id) FOR UPDATE',
                array($conversation_id,$user_id))->row_array();
            if (!$row) throw new RuntimeException('Không có quyền truy cập hội thoại này.',403);
            $this->run('INSERT INTO conversation_notification_preferences (conversation_id, user_id, notifications_enabled)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE notifications_enabled = VALUES(notifications_enabled),
                updated_at = CURRENT_TIMESTAMP',array($conversation_id,$user_id,$enabled ? 1 : 0));
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Không lưu được cài đặt thông báo.',503);
            }
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
        return array('id'=>$conversation_id,'notifications_enabled'=>$enabled,'notifications_available'=>true);
    }
}
