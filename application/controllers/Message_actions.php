<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message_actions extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_conversation_pin');
        $this->output->set_header('Cache-Control: private, no-store');
    }

    public function pin($conversation_id = 0)
    {
        if (!$this->auth->check()) {
            return $this->reply(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        }
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok' => false, 'message' => 'Tài khoản không có quyền thao tác.'), 403);
        }
        $method = $this->input->method();
        if (!in_array($method, array('get', 'post'), true)) {
            return $this->reply(array('ok' => false, 'message' => 'Yêu cầu GET hoặc POST.'), 405);
        }
        try {
            $user_id = (int) $user['id'];
            if ($method === 'get') {
                $state = $this->m_conversation_pin->state($conversation_id, $user_id);
                $token = $this->session->userdata('private_message_action_token');
                if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
                    $token = bin2hex(random_bytes(32));
                    $this->session->set_userdata('private_message_action_token', $token);
                }
                return $this->reply(array('ok' => true, 'action_token' => $token) + $state);
            }
            $expected = $this->session->userdata('private_message_action_token');
            $given = $this->input->post('action_token');
            if (!is_string($expected) || !is_string($given)
                || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, $given)) {
                return $this->reply(array('ok' => false, 'message' => 'Phiên đã thay đổi. Hãy tải lại trang.'), 403);
            }
            $action = $this->input->post('action');
            $message_id = $this->input->post('message_id');
            if (!in_array($action, array('pin', 'unpin'), true) || !is_string($message_id)
                || filter_var($message_id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
                return $this->reply(array('ok' => false, 'message' => 'Thao tác hoặc ID tin nhắn không hợp lệ.'), 422);
            }
            $state = $this->m_conversation_pin->save($conversation_id, $user_id, $message_id, $action === 'pin');
            return $this->reply(array('ok' => true) + $state);
        } catch (InvalidArgumentException $e) {
            return $this->reply(array('ok' => false, 'message' => $e->getMessage()), 422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(403, 404, 503), true) ? $e->getCode() : 500;
            return $this->reply(array('ok' => false,
                'message' => $code === 500 ? 'Chưa xử lý được thao tác. Vui lòng thử lại.' : $e->getMessage()), $code);
        } catch (Throwable $e) {
            return $this->reply(array('ok' => false, 'message' => 'Chưa xử lý được thao tác. Vui lòng thử lại.'), 500);
        }
    }

    public function recall($conversation_id = 0)
    {
        if (!$this->auth->check()) {
            return $this->reply(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        }
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok' => false, 'message' => 'Tài khoản không có quyền thao tác.'), 403);
        }
        $method = $this->input->method();
        if (!in_array($method, array('get', 'post'), true)) {
            return $this->reply(array('ok' => false, 'message' => 'Yêu cầu GET hoặc POST.'), 405);
        }
        try {
            $id = filter_var($conversation_id, FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1)));
            if ($id === false) {
                throw new InvalidArgumentException('ID hội thoại không hợp lệ.', 422);
            }
            if ($method === 'post') {
                $expected = $this->session->userdata('private_message_action_token');
                $given = $this->input->post('action_token');
                if (!is_string($expected) || !is_string($given)
                    || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, $given)) {
                    return $this->reply(array('ok' => false, 'message' => 'Phiên đã thay đổi. Hãy tải lại trang.'), 403);
                }
                $this->load->model('m_interaction');
                $result = $this->m_interaction->recall_message($id, (int) $user['id'], $this->input->post('message_id'));
                return $this->reply(array('ok' => true) + $result);
            }
            $query = $this->db->query(
                'SELECT id FROM conversations WHERE id = ? AND ? IN (user_low_id, user_high_id)',
                array($id, (int) $user['id'])
            );
            if (!$query) throw new RuntimeException('Không tải được trạng thái thu hồi.', 503);
            if (!$query->row_array()) {
                throw new RuntimeException('Không có quyền truy cập hội thoại này.', 403);
            }
            $this->load->model('m_interaction');
            $cutoff = $this->m_interaction->conversation_cutoff($id, (int) $user['id']);
            $raw_ids = $this->input->get('ids');
            if ($raw_ids !== null && (!is_string($raw_ids) || strlen($raw_ids) > 2100)) {
                throw new InvalidArgumentException('Danh sách tin nhắn không hợp lệ.', 422);
            }
            $ids = $raw_ids === null || $raw_ids === '' ? array() : explode(',', $raw_ids);
            if (count($ids) > 100) {
                throw new InvalidArgumentException('Chỉ kiểm tra tối đa 100 tin nhắn mỗi lần.', 422);
            }
            foreach ($ids as &$message_id) {
                $message_id = filter_var($message_id, FILTER_VALIDATE_INT,
                    array('options' => array('min_range' => 1)));
                if ($message_id === false) {
                    throw new InvalidArgumentException('ID tin nhắn không hợp lệ.', 422);
                }
            }
            unset($message_id);
            $recalled_ids = array();
            if ($ids) {
                $query = $this->db->select('id')->where('conversation_id', $id)
                    ->where('id >', $cutoff)
                    ->where_in('id', $ids)->where('deleted_at IS NOT NULL', null, false)->get('messages');
                if (!$query) throw new RuntimeException('Không tải được trạng thái thu hồi.', 503);
                foreach ($query->result_array() as $row) $recalled_ids[] = (int) $row['id'];
            }
            $token = $this->session->userdata('private_message_action_token');
            if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
                $token = bin2hex(random_bytes(32));
                $this->session->set_userdata('private_message_action_token', $token);
            }
            return $this->reply(array('ok' => true, 'action_token' => $token, 'recalled_ids' => $recalled_ids,
                'deleted_through_message_id' => $cutoff));
        } catch (InvalidArgumentException $e) {
            return $this->reply(array('ok' => false, 'message' => $e->getMessage()), 422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(403, 404, 503), true) ? $e->getCode() : 500;
            return $this->reply(array('ok' => false,
                'message' => $code === 500 ? 'Chưa xử lý được thao tác thu hồi.' : $e->getMessage()), $code);
        } catch (Throwable $e) {
            return $this->reply(array('ok' => false, 'message' => 'Chưa xử lý được thao tác thu hồi.'), 500);
        }
    }

    public function notifications($conversation_id = 0)
    {
        if (!$this->auth->check()) return $this->reply(array('ok'=>false,'message'=>'Vui lòng đăng nhập.'),401);
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok'=>false,'message'=>'Tài khoản không có quyền thao tác.'),403);
        }
        $method = $this->input->method();
        if (!in_array($method,array('get','post'),true)) return $this->reply(array('ok'=>false,'message'=>'Yêu cầu GET hoặc POST.'),405);
        try {
            $this->load->model('m_conversation_notification');
            if ($method === 'post') {
                $token = $this->session->userdata('private_message_action_token'); $given = $this->input->post('action_token');
                if (!is_string($token) || !is_string($given) || !preg_match('/^[a-f0-9]{64}$/D',$token) || !hash_equals($token,$given)) {
                    return $this->reply(array('ok'=>false,'message'=>'Phiên đã thay đổi. Hãy tải lại trang.'),403);
                }
                $enabled = $this->input->post('notifications_enabled');
                if (!in_array($enabled,array('0','1'),true)) throw new InvalidArgumentException('Trạng thái thông báo không hợp lệ.',422);
                $scope=$this->input->post('mute_scope'); $duration=$this->input->post('mute_duration');
                if ($scope !== null || $duration !== null) {
                    if (!is_string($duration) || !in_array($duration,array('0','900','3600','28800','86400'),true)) {
                        throw new InvalidArgumentException('Thời hạn tắt thông báo không hợp lệ.',422);
                    }
                    $state=$this->m_conversation_notification->configure($conversation_id,(int)$user['id'],$scope,(int)$duration,$enabled === '1');
                } else {
                    $state = $this->m_conversation_notification->save($conversation_id,(int)$user['id'],$enabled === '1');
                }
                try {
                    $this->load->library('realtime');
                    $this->realtime->publish((int)$user['id'],array('t'=>'chat.notification-preference',
                        'conversationId'=>$state['id'],'notifications_enabled'=>$state['notifications_enabled'])
                        + array_intersect_key($state,array_flip(array('calls_notifications_enabled','muted','message_muted_until','calls_muted_until','server_time'))));
                } catch (Throwable $e) { /* Cài đặt đã lưu; giao diện sẽ đọc lại qua GET. */ }
                return $this->reply(array('ok'=>true)+$state);
            }
            $state = $this->m_conversation_notification->state($conversation_id,(int)$user['id']);
            $token = $this->session->userdata('private_message_action_token');
            if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D',$token)) {
                $token = bin2hex(random_bytes(32)); $this->session->set_userdata('private_message_action_token',$token);
            }
            return $this->reply(array('ok'=>true,'action_token'=>$token)+$state);
        } catch (InvalidArgumentException $e) {
            return $this->reply(array('ok'=>false,'message'=>$e->getMessage()),422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(),array(403,503),true) ? $e->getCode() : 500;
            return $this->reply(array('ok'=>false,'message'=>$code === 500 ? 'Không cập nhật được thông báo.' : $e->getMessage()),$code);
        } catch (Throwable $e) {
            return $this->reply(array('ok'=>false,'message'=>'Không cập nhật được thông báo.'),500);
        }
    }

    public function delete_conversation($conversation_id = 0)
    {
        if (!$this->auth->check()) return $this->reply(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok' => false, 'message' => 'Tài khoản không có quyền thao tác.'), 403);
        }
        if ($this->input->method() !== 'post') return $this->reply(array('ok' => false, 'message' => 'Yêu cầu POST.'), 405);
        $expected = $this->session->userdata('private_message_action_token');
        $given = $this->input->post('action_token');
        if (!is_string($expected) || !is_string($given)
            || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, $given)) {
            return $this->reply(array('ok' => false, 'message' => 'Phiên đã thay đổi. Hãy tải lại trang.'), 403);
        }
        try {
            $this->load->model('m_interaction');
            $result = $this->m_interaction->delete_conversation($conversation_id, (int) $user['id']);
            $this->load->library('realtime');
            $this->realtime->publish((int) $user['id'], array('t' => 'chat.deleted',
                'conversationId' => $result['id'], 'deleted_through_message_id' => $result['deleted_through_message_id']));
            return $this->reply(array('ok' => true) + $result);
        } catch (InvalidArgumentException $e) {
            return $this->reply(array('ok' => false, 'message' => $e->getMessage()), 422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(403, 404, 503), true) ? $e->getCode() : 500;
            return $this->reply(array('ok' => false, 'message' => $code === 500 ? 'Không xóa được cuộc trò chuyện.' : $e->getMessage()), $code);
        } catch (Throwable $e) {
            return $this->reply(array('ok' => false, 'message' => 'Không xóa được cuộc trò chuyện.'), 500);
        }
    }

    public function block($conversation_id = 0)
    {
        if (!$this->auth->check()) {
            return $this->reply(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        }
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok' => false, 'message' => 'Tài khoản không có quyền thao tác.'), 403);
        }
        if ($this->input->method() !== 'post') {
            return $this->reply(array('ok' => false, 'message' => 'Yêu cầu POST.'), 405);
        }
        $expected = $this->session->userdata('private_message_action_token');
        $given = $this->input->post('action_token');
        if (!is_string($expected) || !is_string($given)
            || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, $given)) {
            return $this->reply(array('ok' => false, 'message' => 'Phiên đã thay đổi. Hãy tải lại trang.'), 403);
        }
        $id = filter_var($conversation_id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        $action = $this->input->post('action');
        if ($id === false || !in_array($action, array('block', 'unblock'), true)) {
            return $this->reply(array('ok' => false, 'message' => 'Thao tác không hợp lệ.'), 422);
        }
        try {
            $me = (int) $user['id'];
            $conv = $this->db->select('id, user_low_id, user_high_id')->where('id', $id)
                ->get('conversations')->row_array();
            if (!$conv || !in_array($me, array((int) $conv['user_low_id'], (int) $conv['user_high_id']), true)) {
                return $this->reply(array('ok' => false, 'message' => 'Không có quyền xem hội thoại này.'), 403);
            }
            $other = (int) $conv['user_low_id'] === $me ? (int) $conv['user_high_id'] : (int) $conv['user_low_id'];
            if ($other <= 0 || $other === $me) {
                return $this->reply(array('ok' => false, 'message' => 'Thao tác không hợp lệ.'), 422);
            }
            $this->load->model('m_interaction');
            $ok = $action === 'block' ? $this->m_interaction->block($me, $other) : $this->m_interaction->unblock($me, $other);
            if (!$ok) return $this->reply(array('ok' => false, 'message' => 'Chưa lưu được thay đổi. Vui lòng thử lại.'), 503);
            set_flash('success', $action === 'block' ? 'Đã chặn người này.' : 'Đã bỏ chặn phía bạn.');
            return redirect('messenger/' . $id);
        } catch (Throwable $e) {
            return $this->reply(array('ok' => false, 'message' => 'Chưa xử lý được thao tác. Vui lòng thử lại.'), 500);
        }
    }

    private function reply($payload, $code = 200)
    {
        return $this->output->set_status_header($code)->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
