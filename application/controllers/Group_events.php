<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Server-to-server only. No content or account mapping is returned. */
class Group_events extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('chat_group');
        $this->load->library('realtime');
        if ($this->input->method() !== 'post' || !$this->realtime->la_may_chu($this->input->get_request_header('X-Realtime-Secret', false))) show_error('Forbidden', 403);
        $this->load->model('m_chat_group');
        $this->output->set_content_type('application/json')->set_header('Cache-Control: no-store');
    }

    public function authorize()
    {
        if (!chat_groups_enabled()) return $this->output->set_status_header(403)->set_output('{"ok":false}');
        $body = json_decode($this->input->raw_input_stream, true) ?: array();
        $data = $this->realtime->verify_group_ticket($body['ticket'] ?? '');
        if (!$data) return $this->output->set_status_header(403)->set_output('{"ok":false}');
        $p = $this->db->where('group_id', (int) $data['group'])->where('public_id', $data['identity'])->get('chat_group_people')->row_array();
        if (!$p) return $this->output->set_status_header(403)->set_output('{"ok":false}');
        $user = $p['user_id'] ? $this->db->where('id', $p['user_id'])->get('users')->row_array() : null;
        if ($p['user_id'] && !$user) return $this->output->set_status_header(403)->set_output('{"ok":false}');
        try { $this->m_chat_group->access($this->m_chat_group->group($p['group_id']), array('key' => $p['actor_key'], 'user' => $user)); }
        catch (RuntimeException $e) { return $this->output->set_status_header(403)->set_output('{"ok":false}'); }
        return $this->output->set_output(json_encode(array('ok' => true, 'group' => (int) $p['group_id'])));
    }

    public function flush()
    {
        if (!chat_groups_enabled() || !function_exists('curl_init')) return $this->output->set_output('{"ok":true,"sent":0}');
        $lock = $this->db->query("SELECT GET_LOCK('sc.chat-group-outbox',0) AS acquired")->row_array();
        if (empty($lock['acquired'])) return $this->output->set_output('{"ok":true,"sent":0}');
        try {
            $rows = $this->db->where('sent_at', null)->order_by('id')->limit(100)->get('chat_group_outbox')->result_array();
            if (!$rows) return $this->output->set_output('{"ok":true,"sent":0}');
            $items = array();
            foreach (array_unique(array_column($rows, 'group_id')) as $gid) $items[] = array('to' => 'all', 'event' => array('t' => 'group.changed', 'groupId' => (int) $gid));
            // Deliver new default-room messages to old web/mobile clients as well.
            foreach ($rows as $row) if ((int) $row['group_id'] === 1 && $row['message_id']) {
                $message = $this->db->select('r.*,u.display_name,u.nickname,u.avatar,u.gender,u.slug')->from('room_messages r')
                    ->join('users u','u.id=r.user_id')->where('r.room_id',1)->where('r.id',$row['message_id'])->where('r.deleted_at',null)->get()->row_array();
                if (!$message) continue;
                $payload = $this->realtime->room_payload($message);
                $items[] = array('to'=>'all','except'=>(int)$message['user_id'],'event'=>array('t'=>'room.message','message'=>$payload));
                $payload['mine'] = true;
                $items[] = array('to'=>(int)$message['user_id'],'event'=>array('t'=>'room.message','message'=>$payload));
            }
            $url = getenv('REALTIME_HTTP_URL') ?: setting('realtime_http_url', 'http://127.0.0.1:3001');
            $secret = getenv('REALTIME_SECRET') ?: setting('realtime_secret', '');
            $ch = curl_init(rtrim($url, '/') . '/publish');
            curl_setopt_array($ch, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(array('items'=>$items)),
                CURLOPT_HTTPHEADER => array('Content-Type: application/json','X-Realtime-Secret: ' . $secret), CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT_MS => 500, CURLOPT_TIMEOUT_MS => 2000));
            curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            $sent = 0;
            if ($status >= 200 && $status < 300) {
                $this->db->where_in('id', array_column($rows, 'id'))->update('chat_group_outbox', array('sent_at' => date('Y-m-d H:i:s')));
                $sent = count($rows);
            }
            return $this->output->set_output(json_encode(array('ok' => true, 'sent' => $sent)));
        } finally { $this->db->query("SELECT RELEASE_LOCK('sc.chat-group-outbox')"); }
    }

    public function legacy()
    {
        $body = json_decode($this->input->raw_input_stream, true) ?: array();
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $body['ids'] ?? array())))), 0, 1000);
        $allowed = chat_group_schema_ready() ? $this->m_chat_group->legacy_recipients($ids, (int) ($body['sender'] ?? 0)) : $ids;
        return $this->output->set_output(json_encode(array('ok'=>true,'ids'=>$allowed)));
    }
}
