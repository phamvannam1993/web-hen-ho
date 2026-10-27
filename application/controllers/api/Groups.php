<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Clubs Chat's (nhóm chat) cho ứng dụng di động — cùng luật với Groups.php của web (M_chat_group), xác thực bằng
 * Bearer token thay cho session + CSRF. Chỉ thành viên đăng nhập: ứng dụng không có cookie khách sc_chat_guest,
 * nên trong nhóm ẩn danh thành viên trò chuyện bằng tên giả (chọn qua action nickname), như trên web.
 *
 *   GET  /api/groups                       {groups: [Summary]}  — mọi nhóm xem được (sticky của web)
 *   GET  /api/groups/saved                 {groups: [Summary]}  — nhóm đang theo dõi
 *   GET  /api/groups/random                {group: Summary}     — một nhóm ẩn danh ngẫu nhiên vào được
 *   GET  /api/groups/{id}                  {group: Summary}
 *   GET  /api/groups/{id}/messages         ?before_id|after_id&known_ids&events_cursor → như web
 *   GET  /api/groups/{id}/online|profile|profile-quote
 *   POST /api/groups/{id}/open|send|image|nickname|follow|unfollow|hide|notifications|read|presence|block|report|react|moderate|profile-unlock
 *   GET  /api/groups/media/{message_id}    ảnh nhóm (image/webp) — thay /nhom-media/{id} vốn chỉ đọc session
 */
class Groups extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('chat_group');
        $this->load->library('realtime');
        $this->output->set_header('Cache-Control: private, no-store');
    }

    /** Bật tính năng + đăng nhập; trả actor hoặc null (đã gửi lỗi). */
    private function bat_dau()
    {
        if (!chat_groups_enabled()) {
            $this->loi('Nhóm chat chưa được bật.', 404);
            return null;
        }
        if (!$this->can_auth()) return null;
        $this->load->model('m_chat_group');
        return $this->m_chat_group->actor();
    }

    /** Lỗi của model → {ok:false, message} đúng mã như web (Groups::respond). */
    private function chay($fn)
    {
        try {
            return $this->ok($this->doi_anh($fn()));
        } catch (InvalidArgumentException $e) {
            return $this->loi($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), array(401, 403, 404, 405, 409, 422, 429, 503), true) ? $e->getCode() : 500;
            return $this->loi($e->getMessage(), $code);
        }
    }

    /** Ảnh nhóm: /nhom-media/{id} (cần session) → /api/groups/media/{id} (đọc token). */
    private function doi_anh($data)
    {
        $tu = site_url('nhom-media/');
        $sang = site_url('api/groups/media/');
        array_walk_recursive($data, function (&$v, $k) use ($tu, $sang) {
            if ($k === 'content' && is_string($v) && strpos($v, $tu) === 0) $v = $sang . substr($v, strlen($tu));
        });
        return $data;
    }

    private function phai_post()
    {
        if ($this->input->method() !== 'post') throw new RuntimeException('Yêu cầu POST.', 405);
    }

    /** GET /api/groups */
    public function index()
    {
        if (!($actor = $this->bat_dau())) return;
        return $this->chay(function () use ($actor) {
            return array('groups' => $this->m_chat_group->sticky($actor));
        });
    }

    /** GET /api/groups/saved */
    public function saved()
    {
        if (!($actor = $this->bat_dau())) return;
        return $this->chay(function () use ($actor) {
            return array('groups' => $this->m_chat_group->saved($actor));
        });
    }

    /** GET /api/groups/random — như /clubs/random-anonymous nhưng trả nhóm thay vì chuyển trang. */
    public function random()
    {
        if (!($actor = $this->bat_dau())) return;
        return $this->chay(function () use ($actor) {
            $groups = $this->db->where('identity_mode', 'guest')->where('status', 'active')->get('chat_groups')->result_array();
            shuffle($groups);
            foreach ($groups as $group) {
                try { $this->m_chat_group->access($group, $actor); }
                catch (RuntimeException $e) { continue; }
                return array('group' => $this->m_chat_group->summary($group, $actor));
            }
            throw new RuntimeException('Hiện chưa có nhóm ẩn danh đang hoạt động phù hợp. Bạn có thể khám phá các nhóm khác.', 404);
        });
    }

    /** GET /api/groups/{id} */
    public function show($id = 0)
    {
        if (!($actor = $this->bat_dau())) return;
        return $this->chay(function () use ($id, $actor) {
            return array('group' => $this->m_chat_group->summary($this->m_chat_group->group($id), $actor));
        });
    }

    /** /api/groups/{id}/{action} — cùng tập thao tác với ajax/nhom/{id}/{action} của web. */
    public function act($id = 0, $action = 'messages')
    {
        if (!($actor = $this->bat_dau())) return;
        return $this->chay(function () use ($id, $action, $actor) {
            $m = $this->m_chat_group;
            $group = $m->group($id);
            $b = $this->body();
            if ($action === 'messages' || $action === 'online') {
                $m->access($group, $actor);
                if ($action === 'online') return array('people' => $m->online($group, $actor));
                $known = array_slice(array_values(array_filter(array_map('intval', explode(',', (string) $this->input->get('known_ids'))))), 0, 200);
                $changes = $m->removed($group, $actor, $known, max(0, (int) $this->input->get('events_cursor')));
                return array('messages' => $m->messages($group, $actor, max(0, (int) $this->input->get('before_id')), max(0, (int) $this->input->get('after_id'))),
                    'group' => $m->summary($group, $actor)) + $changes;
            }
            if ($action === 'profile') return array('profile' => $m->anonymous_profile($group, $actor, (string) $this->input->get('person')));
            if ($action === 'profile-quote') return $m->profile_quote($group, $actor, (string) $this->input->get('person'));

            $this->phai_post();
            if ($action === 'open') {
                $m->access($group, $actor);
                $p = $m->person($id, $actor, true);
                $an = $group['identity_mode'] === 'guest';
                return array('identity' => array('id' => $p['public_id'], 'needs_nickname' => $an && !$p['nickname_chosen'],
                    'name' => $an ? $p['nickname'] : display_name($actor['user']),
                    'avatar' => $an ? chat_group_guest_avatar_url($p['public_id']) : avatar_url($actor['user']['avatar'], $actor['user']['gender']),
                    'guest' => $an),
                    'ticket' => $this->realtime->group_ticket($id, $p['public_id']), 'ws_url' => $this->realtime->url(),
                    'group' => $m->summary($group, $actor));
            }
            if ($action === 'nickname') { $m->choose_nickname($group, $actor, (string) ($b['nickname'] ?? '')); return array(); }
            if ($action === 'send') {
                $m->access($group, $actor, true);
                return array('message' => $m->send($group, $actor, (string) ($b['content'] ?? ''),
                    (string) ($b['client_message_id'] ?? ''), (int) ($b['reply_to_id'] ?? 0)));
            }
            if ($action === 'image') return $this->anh($group, $actor);
            if ($action === 'profile-unlock') return $m->unlock_profile($group, $actor, (string) ($b['person'] ?? ''), (string) ($b['version'] ?? ''));
            if ($action === 'moderate') {
                $m->moderate($group, (string) ($b['action'] ?? ''), (string) ($b['target'] ?? ''), trim((string) ($b['reason'] ?? '')));
                return array();
            }
            $m->action($group, $actor, $action, is_array($b) ? $b : array());
            return array();
        });
    }

    /** Ảnh gửi vào nhóm (multipart "image") — cùng kiểm tra, mã hoá lại WebP và tên tệp như Groups::image() của web. */
    private function anh($group, $actor)
    {
        $m = $this->m_chat_group;
        $m->access($group, $actor, true);
        if (!$m->allows_images($group)) throw new RuntimeException('Nhóm này không cho phép bạn gửi ảnh.', 403);
        $f = $_FILES['image'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5242880 || !is_uploaded_file($f['tmp_name'])) throw new InvalidArgumentException('Ảnh không hợp lệ hoặc vượt quá 5 MB.');
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true) || $info[0] * $info[1] > 16000000) throw new InvalidArgumentException('Chỉ nhận ảnh JPEG, PNG, WebP tối đa 16 megapixel.');
        if (!function_exists('imagewebp')) throw new RuntimeException('Máy chủ chưa hỗ trợ xử lý ảnh.', 503);
        $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
        if (!$img) throw new InvalidArgumentException('Không đọc được ảnh.');
        $dir = FCPATH . 'writable/chat-media';
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Chưa lưu được ảnh.', 503);
        $key = (string) $this->input->post('client_message_id');
        if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/D', $key)) throw new InvalidArgumentException('Mã gửi tin không hợp lệ.');
        $name = substr(hash_hmac('sha256', $group['id'] . ':' . $actor['key'] . ':' . $key, $this->config->item('encryption_key')), 0, 48) . '.webp';
        if (!file_exists($dir . '/' . $name) && !imagewebp($img, $dir . '/' . $name, 85)) throw new RuntimeException('Chưa lưu được ảnh.', 503);
        unset($img);
        return array('message' => $m->send($group, $actor, $name, $key, (int) $this->input->post('reply_to_id'), 'image', (string) $this->input->post('content')));
    }

    /** GET /api/groups/media/{message_id} — ảnh nhóm cho người xem được tin đó (Bearer token). */
    public function media($id = 0)
    {
        if (!($actor = $this->bat_dau())) return;
        $m = $this->m_chat_group;
        $r = $this->db->where('id', (int) $id)->get('room_messages')->row_array();
        try {
            $r = $r && $r['type'] === 'image' ? $m->visible_message($m->group($r['room_id']), $actor, $id) : null;
        } catch (RuntimeException $e) {
            $r = null;
        }
        $path = $r && preg_match('/^[a-f0-9]{48}\.webp$/D', $r['content']) ? FCPATH . 'writable/chat-media/' . $r['content'] : '';
        if (!$path || !is_file($path)) return $this->loi('Không tìm thấy ảnh.', 404);
        $this->output->set_header('X-Content-Type-Options: nosniff')->set_content_type('image/webp')->set_output(file_get_contents($path));
    }
}
