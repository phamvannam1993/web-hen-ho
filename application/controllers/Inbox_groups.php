<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inbox_groups extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Robots-Tag: noindex, nofollow');
        $this->load->model('m_inbox_group');
    }

    private function respond($method, $fn)
    {
        if (!$this->auth->check()) return $this->reply(array('ok' => false, 'message' => 'Vui lòng đăng nhập.'), 401);
        $user = $this->auth->user();
        if (!$user || $user['status'] !== 'active' || !empty($user['deleted_at'])) {
            return $this->reply(array('ok' => false, 'message' => 'Tài khoản không có quyền truy cập nhóm.'), 403);
        }
        if ($this->input->method() !== $method) return $this->reply(array('ok' => false, 'message' => 'Phương thức không hợp lệ.'), 405);
        if ($method === 'post') {
            $expected = $this->session->userdata('inbox_group_action_token');
            $given = $this->input->post('action_token');
            if (!is_string($expected) || !is_string($given) || !preg_match('/^[a-f0-9]{64}$/D', $expected) || !hash_equals($expected, $given)) {
                return $this->reply(array('ok' => false, 'message' => 'Phiên đã thay đổi. Hãy tải lại trang.'), 403);
            }
        }
        try { return $this->reply(array('ok' => true) + $fn((int) $user['id'])); }
        catch (InvalidArgumentException $e) { return $this->reply(array('ok' => false, 'message' => $e->getMessage()), 422); }
        catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(403, 409, 503), true) ? $e->getCode() : 500;
            return $this->reply(array('ok' => false, 'message' => $code === 500 ? 'Chưa xử lý được nhóm. Vui lòng thử lại.' : $e->getMessage()), $code);
        }
        catch (Throwable $e) { return $this->reply(array('ok' => false, 'message' => 'Chưa xử lý được nhóm. Vui lòng thử lại.'), 500); }
    }

    public function mine()
    {
        return $this->respond('get', function ($user_id) {
            $result = $this->m_inbox_group->listing($user_id, $this->input->get('before') ?? '0');
            $token = $this->session->userdata('inbox_group_action_token');
            if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
                $token = bin2hex(random_bytes(32)); $this->session->set_userdata('inbox_group_action_token', $token);
            }
            return $result + array('action_token' => $token);
        });
    }

    public function common($peer_id = 0)
    {
        return $this->respond('get', function ($user_id) use ($peer_id) {
            return $this->m_inbox_group->listing($user_id, $this->input->get('before') ?? '0', $peer_id);
        });
    }

    public function users()
    {
        return $this->respond('get', function ($user_id) {
            return $this->m_inbox_group->search_users($user_id, $this->input->get('q'),
                $this->input->get('after') ?? '0', $this->input->get('group_id'));
        });
    }

    public function create()
    {
        return $this->respond('post', function ($user_id) {
            return array('group' => $this->m_inbox_group->create($user_id, $this->input->post('name'), $this->input->post('user_ids')));
        });
    }

    public function add($group_id = 0)
    {
        return $this->respond('post', function ($user_id) use ($group_id) {
            return array('group' => $this->m_inbox_group->add($group_id, $user_id, $this->input->post('user_ids')));
        });
    }

    public function rename($id = 0)
    {
        return $this->respond('post', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->rename($id, $uid, $this->input->post('name')));
        });
    }

    public function remove_member($id = 0)
    {
        return $this->respond('post', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->remove_member($id, $uid, $this->input->post('user_id')));
        });
    }

    public function detail($id = 0)
    {
        return $this->respond('get', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->detail($id, $uid));
        });
    }

    public function messages($id = 0)
    {
        return $this->respond('get', function ($uid) use ($id) {
            return $this->m_inbox_group->messages($id, $uid, $this->input->get('before') ?? '0', $this->input->get('after'));
        });
    }

    public function send($id = 0)
    {
        return $this->respond('post', function ($uid) use ($id) {
            if (!$this->auth->da_xac_thuc()) throw new RuntimeException('Xác thực tài khoản trước khi nhắn tin.', 403);
            return array('message' => $this->m_inbox_group->send($id, $uid, $this->input->post('content'), $this->input->post('client_message_id')));
        });
    }

    public function read($id = 0)
    {
        return $this->respond('post', function ($uid) use ($id) {
            return $this->m_inbox_group->read($id, $uid, $this->input->post('message_id'));
        });
    }

    private function reply($data, $code = 200)
    {
        return $this->output->set_status_header($code)->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
