<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_groups extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('chat_group');
        if (!chat_group_schema_ready()) show_error('Cần chạy migration nhóm chat trước.', 503);
        $this->load->model('m_chat_group');
    }

    private function token()
    {
        $t = $this->session->userdata('chat_group_csrf');
        if (!$t) { $t = bin2hex(random_bytes(32)); $this->session->set_userdata('chat_group_csrf', $t); }
        return $t;
    }

    private function admin_only()
    {
        if ($this->auth->user()['role'] !== 'admin') show_error('Chỉ quản trị viên được thay đổi cấu hình nhóm.', 403);
    }

    public function index()
    {
        $this->admin_only();
        $this->render('admin/chat_groups/index', array('title' => 'Quản lý nhóm chat',
            'groups' => $this->db->order_by('sort_order', 'DESC')->order_by('id', 'DESC')->get('chat_groups')->result_array(),
            'reports' => $this->db->select('r.*,g.name')->from('chat_group_reports r')->join('chat_groups g', 'g.id=r.group_id')->where('r.status', 'open')->order_by('r.id', 'DESC')->limit(100)->get()->result_array(),
            'logs' => $this->db->order_by('id', 'DESC')->limit(100)->get('chat_group_logs')->result_array(),
            'chat_csrf' => $this->token()));
    }

    public function edit($id = 0)
    {
        $this->admin_only();
        $g = $id ? $this->m_chat_group->group($id) : null;
        if ($id && !$g) show_404();
        $error = '';
        if ($this->input->method() === 'post') {
            if (!hash_equals($this->token(), (string) $this->input->post('chat_csrf'))) show_error('Phiên không hợp lệ.', 403);
            $name = trim((string) $this->input->post('name'));
            $slug = $g ? $g['slug'] : trim((string) $this->input->post('slug'));
            $mode = (string) $this->input->post('identity_mode');
            $status = (string) $this->input->post('status');
            $description = trim((string) $this->input->post('description'));
            $rules = trim((string) $this->input->post('rules'));
            $moderators = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', trim((string) $this->input->post('moderators')))))));
            if (!$name || mb_strlen($name) > 120 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 100) $error = 'Tên tối đa 120 ký tự, slug chỉ gồm chữ thường không dấu, số và gạch ngang.';
            elseif (!in_array($mode, array('public','guest'), true) || !in_array($status, array('active','readonly','closed'), true)) $error = 'Chế độ hoặc trạng thái không hợp lệ.';
            elseif (mb_strlen($description) > 2000 || mb_strlen($rules) > 4000) $error = 'Mô tả tối đa 2.000 ký tự; nội quy tối đa 4.000 ký tự.';
            elseif (!$g && $this->db->where('slug', $slug)->count_all_results('chat_groups')) $error = 'Slug đã tồn tại.';
            elseif ((int) $id === 1 && $mode !== 'public') $error = 'Phòng mặc định giữ chế độ công khai để tương thích chat cũ.';
            if (!$error && $moderators) {
                $valid = $this->db->where_in('id', $moderators)->where_in('role', array('admin','moderator'))->where('status', 'active')->where('deleted_at', null)->count_all_results('users');
                if ($valid !== count($moderators)) $error = 'Người điều hành phải là tài khoản admin/moderator đang hoạt động.';
            }
            $avatar = $g['avatar'] ?? null;
            if (!$error && !empty($_FILES['avatar']['name'])) {
                $f = $_FILES['avatar']; $info = $f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name']) ? @getimagesize($f['tmp_name']) : false;
                if (!$info || $f['size'] > 2097152 || $info[0] * $info[1] > 4000000 || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) $error = 'Avatar cần là PNG/WebP, tối đa 2 MB và 4 megapixel.';
                elseif (!function_exists('imagewebp')) $error = 'Máy chủ chưa hỗ trợ xử lý ảnh.';
                else {
                    $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
                    $dir = FCPATH . 'uploads/group-icons';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $avatar = 'uploads/group-icons/' . bin2hex(random_bytes(16)) . '.webp';
                    if (!$img || !imagewebp($img, FCPATH . $avatar, 85)) $error = 'Không lưu được avatar.';
                    if ($img) imagedestroy($img);
                }
            }
            if (!$error) {
                $this->db->trans_begin();
                if ($g) $this->db->query('SELECT id FROM chat_groups WHERE id=? FOR UPDATE', array($id));
                if ($g && $mode !== $g['identity_mode'] && $this->db->where('room_id', $id)->count_all_results('room_messages')) {
                    $this->db->trans_rollback(); $error = 'Không đổi chế độ danh tính sau khi nhóm đã có tin. Hãy tạo nhóm mới.';
                } else {
                    $data = array('name' => $name, 'slug' => $slug, 'description' => $description, 'rules' => $rules, 'avatar' => $avatar,
                        'identity_mode' => $mode, 'status' => $status, 'allow_images' => $this->input->post('allow_images') ? 1 : 0,
                        'category' => mb_substr(trim((string) $this->input->post('category')), 0, 80),
                        'province_id' => (int) $this->input->post('province_id') ?: null, 'sort_order' => (int) $this->input->post('sort_order'));
                    if ($g) $this->db->where('id', $id)->update('chat_groups', $data);
                    else { $this->db->insert('chat_groups', $data); $id = $this->db->insert_id(); }
                    $this->db->where('group_id', $id)->delete('chat_group_moderators');
                    foreach ($moderators as $uid) $this->db->insert('chat_group_moderators', array('group_id' => $id, 'user_id' => $uid));
                    $this->m_chat_group->changed($id);
                    $this->db->insert('chat_group_logs', array('group_id' => $id, 'actor_id' => $this->auth->id(), 'action' => 'save_group', 'target' => $slug, 'reason' => 'Lưu cấu hình nhóm'));
                    if ($this->db->trans_status()) { $this->db->trans_commit(); set_flash('success', 'Đã lưu nhóm chat.'); redirect('admin/chat_groups'); }
                    $this->db->trans_rollback(); $error = 'Không lưu được nhóm. Hãy kiểm tra slug và thử lại.';
                }
            }
        }
        $this->render('admin/chat_groups/edit', array('title' => $g ? 'Sửa nhóm chat' : 'Tạo nhóm chat', 'g' => $g, 'error' => $error,
            'chat_csrf' => $this->token(), 'moderator_ids' => $g ? implode(',', array_column($this->db->where('group_id', $id)->get('chat_group_moderators')->result_array(), 'user_id')) : ''));
    }

    public function resolve($id)
    {
        $this->admin_only();
        if ($this->input->method() !== 'post' || !hash_equals($this->token(), (string) $this->input->post('chat_csrf'))) show_error('Yêu cầu không hợp lệ.', 403);
        $r = $this->db->where('id', (int) $id)->get('chat_group_reports')->row_array();
        if (!$r) show_404();
        $this->db->trans_start();
        $this->db->where('id', $r['id'])->update('chat_group_reports', array('status' => 'resolved'));
        $this->db->insert('chat_group_logs', array('group_id' => $r['group_id'], 'actor_id' => $this->auth->id(), 'action' => 'resolve_report', 'target' => (string) $r['id'], 'reason' => 'Đã xử lý báo cáo'));
        $this->db->trans_complete();
        redirect('admin/chat_groups');
    }
}
