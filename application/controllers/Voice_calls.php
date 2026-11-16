<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Voice_calls extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('realtime', 'voice'));
        $this->load->model('m_interaction');
        $this->output->set_content_type('application/json')
            ->set_header('Cache-Control: private, no-store');
    }

    /** Cấu hình microphone/WebRTC cho tài khoản trong session. */
    public function config($conversation_id = 0)
    {
        if ($this->input->method() !== 'get') {
            return $this->reply(array('ok' => false), 405);
        }
        if (!$this->auth->check()) {
            return $this->reply(array('ok' => false), 401);
        }
        $uid = (int) $this->auth->id();
        $config = $this->voice->config($uid);
        if (!$this->realtime->enabled() || !$config['enabled']) {
            return $this->reply(array('ok' => false), 503);
        }
        $peer = $this->peer($conversation_id, $uid);
        if (!$peer) {
            return $this->reply(array('ok' => false), 403);
        }
        return $this->reply(array(
            'ok' => true,
            'conversationId' => (int) $conversation_id,
            'peer' => $peer,
            'rtc' => $config,
        ));
    }

    /** Chỉ Node được gọi; uid phải lấy từ WebSocket đã xác thực. */
    public function authorize()
    {
        if ($this->input->method() !== 'post') {
            return $this->reply(array('ok' => false), 405);
        }
        if (!$this->realtime->la_may_chu(
            $this->input->get_request_header('X-Realtime-Secret', false)
        )) {
            return $this->reply(array('ok' => false), 403);
        }
        $body = json_decode($this->input->raw_input_stream, true);
        if (!is_array($body)) {
            return $this->reply(array('ok' => false), 400);
        }
        $uid = $this->positive_id($body['uid'] ?? null);
        $cid = $this->positive_id($body['conversationId'] ?? null);
        $config = $this->voice->config($uid);
        if (!$this->realtime->enabled() || !$config['enabled']) {
            return $this->reply(array('ok' => false), 503);
        }
        $peer = $this->peer($cid, $uid);
        if (!$peer) {
            return $this->reply(array('ok' => false), 403);
        }
        $calls_enabled=false;
        try {
            $this->load->model('m_conversation_notification');
            $calls_enabled=$this->m_conversation_notification->enabled($cid,$uid,'calls');
        } catch (Throwable $e) { /* Không báo cuộc gọi khi chưa đọc được tùy chọn. */ }
        return $this->reply(array('ok'=>true,'peer'=>$peer,'calls_notifications_enabled'=>$calls_enabled));
    }

    private function positive_id($value)
    {
        if (!is_int($value) && !is_string($value)) return 0;
        return (int) filter_var($value, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));
    }

    private function peer($conversation_id, $uid)
    {
        $cid = $this->positive_id($conversation_id);
        if (!$cid || !$uid) return null;
        $conv = $this->db->where('id', $cid)
            ->get('conversations')->row_array();
        if (!$conv) return null;
        $ids = array((int) $conv['user_low_id'],
            (int) $conv['user_high_id']);
        if (!in_array($uid, $ids, true) || $ids[0] === $ids[1]) {
            return null;
        }
        $peer_id = $ids[0] === $uid ? $ids[1] : $ids[0];
        $users = array();
        foreach (array($uid, $peer_id) as $id) {
            $user = $this->db->where('id', $id)
                ->where('deleted_at', null)->where('status', 'active')
                ->get('users')->row_array();
            if (!$user || !$this->verified($user)) return null;
            $users[$id] = $user;
        }
        if ($this->m_interaction->is_blocked($uid, $peer_id)) return null;
        foreach (array($uid => $peer_id, $peer_id => $uid) as $from => $to) {
            $pref = $this->db->where('user_id', $to)
                ->get('user_preferences')->row_array();
            if (($pref['allow_message'] ?? 'all') === 'vip'
                && !member_has_vip($users[$from])) return null;
        }
        return array('id' => $peer_id,
            'name' => display_name($users[$peer_id]));
    }

    private function verified($user)
    {
        return setting('otp_register', '1') !== '1'
            || !empty($user['email_verified_at'])
            || empty($user['email'])
            || in_array($user['role'],
                array('super_admin', 'admin', 'moderator'), true);
    }

    private function reply($data, $status = 200)
    {
        return $this->output->set_status_header($status)
            ->set_output(json_encode($data));
    }
}
