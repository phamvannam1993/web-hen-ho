<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nhóm chat riêng trong hộp thư — bản API (token) của controllers/Inbox_groups.php cho ứng dụng.
 * Cùng model M_inbox_group nên quyền thành viên, chặn, chống gửi trùng y như web.
 * Thân POST nhận JSON hoặc form; không cần action_token vì đã xác thực bằng Bearer token.
 */
class Inbox_groups extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Cache-Control: private, no-store');
        $this->load->model('m_inbox_group');
    }

    /** Kiểm tra token + phương thức rồi chạy; lỗi model → {ok:false, message} cùng mã như web. */
    private function chay($method, $fn)
    {
        if (!$this->can_auth()) return;
        if ($this->input->method() !== $method) return $this->loi('Phương thức không hợp lệ.', 405);
        try {
            return $this->ok($fn((int) $this->me['id']));
        } catch (InvalidArgumentException $e) {
            return $this->loi($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(403, 409, 503), true) ? $e->getCode() : 500;
            return $this->loi($code === 500 ? 'Chưa xử lý được nhóm. Vui lòng thử lại.' : $e->getMessage(), $code);
        } catch (Throwable $e) {
            return $this->loi('Chưa xử lý được nhóm. Vui lòng thử lại.', 500);
        }
    }

    /** Danh sách id người dùng từ JSON [1,2] hoặc chuỗi "1,2". */
    private function ids($key = 'user_ids')
    {
        $v = $this->body($key);
        if (is_string($v)) $v = array_values(array_filter(array_map('trim', explode(',', $v)), 'strlen'));
        return is_array($v) ? array_values($v) : $v;
    }

    /** GET /api/inbox-groups?before= — nhóm của tôi (20 nhóm/lần). */
    public function mine()
    {
        return $this->chay('get', function ($uid) {
            return $this->m_inbox_group->listing($uid, $this->input->get('before') ?? '0');
        });
    }

    /** GET /api/inbox-groups/common/{user_id} — nhóm chung với một người. */
    public function common($peer_id = 0)
    {
        return $this->chay('get', function ($uid) use ($peer_id) {
            return $this->m_inbox_group->listing($uid, $this->input->get('before') ?? '0', (string) $peer_id);
        });
    }

    /** GET /api/inbox-groups/users?q=&after=&group_id= — tìm người để thêm vào nhóm. */
    public function users()
    {
        return $this->chay('get', function ($uid) {
            return $this->m_inbox_group->search_users($uid, $this->input->get('q'),
                $this->input->get('after') ?? '0', $this->input->get('group_id'));
        });
    }

    /** POST /api/inbox-groups {name, user_ids} */
    public function create()
    {
        return $this->chay('post', function ($uid) {
            return array('group' => $this->m_inbox_group->create($uid, $this->body('name'), $this->ids()));
        });
    }

    /** POST /api/inbox-groups/{id}/add {user_ids} */
    public function add($id = 0)
    {
        return $this->chay('post', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->add((string) $id, $uid, $this->ids()));
        });
    }

    /** POST /api/inbox-groups/{id}/rename {name} — chỉ người tạo nhóm. */
    public function rename($id = 0)
    {
        return $this->chay('post', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->rename((string) $id, $uid, $this->body('name')));
        });
    }

    /** POST /api/inbox-groups/{id}/remove-member {user_id} — chỉ người tạo nhóm. */
    public function remove_member($id = 0)
    {
        return $this->chay('post', function ($uid) use ($id) {
            $target = $this->body('user_id');
            return array('group' => $this->m_inbox_group->remove_member((string) $id, $uid, is_int($target) ? (string) $target : $target));
        });
    }

    /** GET /api/inbox-groups/{id} — tên, người tạo, thành viên. */
    public function detail($id = 0)
    {
        return $this->chay('get', function ($uid) use ($id) {
            return array('group' => $this->m_inbox_group->detail((string) $id, $uid));
        });
    }

    /** GET /api/inbox-groups/{id}/messages?before= | ?after= — 30 tin/lần. */
    public function messages($id = 0)
    {
        return $this->chay('get', function ($uid) use ($id) {
            return $this->m_inbox_group->messages((string) $id, $uid,
                $this->input->get('before') ?? '0', $this->input->get('after'));
        });
    }

    /** POST /api/inbox-groups/{id}/send {content, client_message_id} — client_message_id 16–64 ký tự [A-Za-z0-9_-]. */
    public function send($id = 0)
    {
        return $this->chay('post', function ($uid) use ($id) {
            if (!$this->auth->da_xac_thuc()) throw new RuntimeException('Xác thực tài khoản trước khi nhắn tin.', 403);
            return array('message' => $this->m_inbox_group->send((string) $id, $uid,
                $this->body('content'), $this->body('client_message_id')));
        });
    }

    /** POST /api/inbox-groups/{id}/read {message_id} */
    public function read($id = 0)
    {
        return $this->chay('post', function ($uid) use ($id) {
            $mid = $this->body('message_id');
            return $this->m_inbox_group->read((string) $id, $uid, is_int($mid) ? (string) $mid : $mid);
        });
    }
}
