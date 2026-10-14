<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Groups extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('chat_group', 'cookie'));
        if (!chat_groups_enabled()) show_404();
        $this->load->model('m_chat_group');
    }

    private function csrf()
    {
        $token = $this->session->userdata('chat_group_csrf');
        if (!$token) { $token = bin2hex(random_bytes(32)); $this->session->set_userdata('chat_group_csrf', $token); }
        return $token;
    }

    private function guard()
    {
        if ($this->input->method() !== 'post') throw new RuntimeException('Yêu cầu POST.', 405);
        $given = (string) $this->input->get_request_header('X-Chat-CSRF', false);
        if (!$given) $given = (string) $this->input->post('chat_csrf');
        if (!hash_equals($this->csrf(), $given)) throw new RuntimeException('Phiên đã thay đổi. Hãy tải lại trang.', 403);
    }

    private function respond($fn)
    {
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Robots-Tag: noindex, nofollow');
        try { return $this->json(array('ok' => true) + $fn()); }
        catch (InvalidArgumentException $e) { return $this->json(array('ok' => false, 'message' => $e->getMessage()), 422); }
        catch (RuntimeException $e) { return $this->json(array('ok' => false, 'message' => $e->getMessage()), in_array($e->getCode(), array(401,403,404,405,409,422,429,503), true) ? $e->getCode() : 500); }
    }

    public function index()
    {
        $q = trim((string) $this->input->get('q'));
        $mode = (string) $this->input->get('mode');
        $category = (string) $this->input->get('category');
        $province = (int) $this->input->get('province');
        $page = min(10000, max(1, (int) $this->input->get('page')));
        $groups = $this->m_chat_group->catalog($q, $mode, $category, $province, $page);
        $more = count($groups) > 20;
        $groups = array_slice($groups, 0, 20);
        // Public landing counts only; never include chat previews in discovery HTML.
        foreach ($groups as &$g) {
            $g['online'] = (int) $this->db->where('group_id', $g['id'])->where('is_banned', 0)
                ->where('last_active_at >', date('Y-m-d H:i:s', time() - 45))->count_all_results('chat_group_people');
        }
        unset($g);
        $this->render('groups/index', compact('q','mode','category','province','page','groups','more') + array('title' => 'Khám phá nhóm chat', 'meta_desc' => 'Tìm nhóm trò chuyện và làm quen trên Saigon Cupid.'));
    }

    public function detail($slug)
    {
        $group = $this->db->where('slug', $slug)->get('chat_groups')->row_array();
        if (!$group) show_404();
        $this->render('groups/detail', array('title' => $group['name'], 'meta_desc' => mb_substr($group['description'], 0, 160),
            'group' => $group, 'chat_csrf' => $this->csrf(), 'seo_noindex' => $group['status'] === 'closed'));
    }

    public function api($id = 0, $action = 'messages')
    {
        return $this->respond(function () use ($id, $action) {
            if ($action === 'saved') return array('groups' => $this->m_chat_group->saved($this->m_chat_group->actor()));
            $group = $this->m_chat_group->group($id);
            $actor = $this->m_chat_group->actor();
            if ($action === 'messages' || $action === 'online') {
                if ($this->input->method() !== 'get') throw new RuntimeException('Yêu cầu GET.', 405);
                $this->m_chat_group->access($group, $actor);
                if ($action === 'online') return array('people' => $this->m_chat_group->online($group, $actor));
                $known = array_slice(array_values(array_filter(array_map('intval', explode(',', (string) $this->input->get('known_ids'))))), 0, 200);
                $changes = $this->m_chat_group->removed($group, $actor, $known, max(0, (int) $this->input->get('events_cursor')));
                return array('messages' => $this->m_chat_group->messages($group, $actor, max(0, (int) $this->input->get('before_id')), max(0, (int) $this->input->get('after_id'))),
                    'group' => $this->m_chat_group->summary($group, $actor)) + $changes;
            }
            $this->guard();
            // Create guest identity only on explicit open, not a background GET.
            if ($action === 'open') {
                $this->m_chat_group->access($group, $actor);
                $actor = $this->m_chat_group->actor(true);
                $p = $this->m_chat_group->person($id, $actor, true);
                return array('identity' => array('id' => $p['public_id'], 'name' => $actor['user'] ? display_name($actor['user']) : $p['nickname'], 'guest' => !$actor['user']),
                    'ticket' => $this->realtime->group_ticket($id, $p['public_id']), 'ws_url' => $this->realtime->url());
            }
            if ($action === 'send') {
                $this->m_chat_group->access($group, $actor, true);
                if (!$actor['user'] && $this->input->post('guest_consent') !== '1') throw new RuntimeException('Hãy xác nhận quy định chat dành cho khách.', 422);
                return array('message' => $this->m_chat_group->send($group, $actor, (string) $this->input->post('content'),
                    (string) $this->input->post('client_message_id'), (int) $this->input->post('reply_to_id')));
            }
            if ($action === 'image') return $this->image($group, $actor);
            if ($action === 'moderate') {
                $this->m_chat_group->moderate($group, (string) $this->input->post('action'), (string) $this->input->post('target'), trim((string) $this->input->post('reason')));
                return array();
            }
            $this->m_chat_group->action($group, $actor, $action, $this->input->post(null, false) ?: array());
            return array();
        });
    }

    private function image($group, $actor)
    {
        $this->m_chat_group->access($group, $actor, true);
        if (!$group['allow_images'] || !$actor['user']) throw new RuntimeException('Nhóm này không cho phép bạn gửi ảnh.', 403);
        $f = $_FILES['image'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5242880 || !is_uploaded_file($f['tmp_name'])) throw new InvalidArgumentException('Ảnh không hợp lệ hoặc vượt quá 5 MB.');
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP), true) || $info[0] * $info[1] > 16000000) throw new InvalidArgumentException('Chỉ nhận ảnh JPEG, PNG, WebP tối đa 16 megapixel.');
        if (!function_exists('imagewebp')) throw new RuntimeException('Máy chủ chưa hỗ trợ xử lý ảnh.', 503);
        $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
        if (!$img) throw new InvalidArgumentException('Không đọc được ảnh.');
        $dir = FCPATH . 'writable/chat-media';
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Chưa lưu được ảnh.', 503);
        $key = (string) $this->input->post('client_message_id');
        if (!preg_match('/^[a-zA-Z0-9_-]{16,80}$/D', $key)) throw new InvalidArgumentException('Mã gửi tin không hợp lệ.');
        // Deterministic per sender/retry, opaque to recipients. Re-encoding removes metadata.
        $name = substr(hash_hmac('sha256', $group['id'] . ':' . $actor['key'] . ':' . $key, $this->config->item('encryption_key')), 0, 48) . '.webp';
        if (!file_exists($dir . '/' . $name) && !imagewebp($img, $dir . '/' . $name, 85)) throw new RuntimeException('Chưa lưu được ảnh.', 503);
        imagedestroy($img);
        return array('message' => $this->m_chat_group->send($group, $actor, $name, $key, (int) $this->input->post('reply_to_id'), 'image'));
    }

    public function media($id)
    {
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Robots-Tag: noindex, nofollow')->set_header('X-Content-Type-Options: nosniff');
        $r = $this->db->where('id', (int) $id)->get('room_messages')->row_array();
        if (!$r || $r['type'] !== 'image') show_404();
        try { $r = $this->m_chat_group->visible_message($this->m_chat_group->group($r['room_id']), $this->m_chat_group->actor(), $id); }
        catch (RuntimeException $e) { show_404(); }
        if (!$r || !preg_match('/^[a-f0-9]{48}\.webp$/D', $r['content'])) show_404();
        $path = FCPATH . 'writable/chat-media/' . $r['content'];
        if (!is_file($path)) show_404();
        $this->output->set_content_type('image/webp')->set_output(file_get_contents($path));
    }

    public function panel($id)
    {
        $group = $this->m_chat_group->group($id);
        try { $this->m_chat_group->access($group, $this->m_chat_group->actor()); }
        catch (RuntimeException $e) { show_error($e->getMessage(), $e->getCode()); }
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Robots-Tag: noindex, nofollow');
        $this->load->view('groups/panel', array('group' => $group, 'chat_csrf' => $this->csrf()));
    }
}
