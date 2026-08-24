<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Thư viện xác thực dùng chung cho cả frontend và admin.
 */
class Userauth
{
    /** @var CI_Controller */
    private $CI;
    private $cached_user = null;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('cookie'); // get_cookie/set_cookie cho chức năng ghi nhớ đăng nhập
    }

    public function attempt($identity, $password, $remember = false)
    {
        $user = $this->CI->db
            ->group_start()->where('email', $identity)->or_where('phone', $identity)->group_end()
            ->where('deleted_at', null)
            ->get('users')->row_array();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (in_array($user['status'], array('banned', 'locked'), true)) {
            return 'locked';
        }

        $this->login($user);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $this->CI->db->insert('user_tokens', array(
                'user_id'    => $user['id'],
                'type'       => 'remember',
                'token'      => $token,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            ));
            set_cookie('remember_token', $token, 60 * 60 * 24 * 30);
        }
        return true;
    }

    public function login(array $user)
    {
        $this->CI->session->set_userdata('user_id', (int) $user['id']);
        $this->cached_user = $user;
        $this->CI->db->where('id', $user['id'])->update('users', array(
            'last_active_at' => date('Y-m-d H:i:s'),
            'last_login_ip'  => $this->CI->input->ip_address(),
        ));
    }

    public function logout()
    {
        $token = get_cookie('remember_token');
        if ($token) {
            $this->CI->db->where('token', $token)->delete('user_tokens');
            delete_cookie('remember_token');
        }
        $this->CI->session->unset_userdata('user_id');
        $this->CI->session->sess_destroy();
        $this->cached_user = null;
    }

    public function check()
    {
        return $this->user() !== null;
    }

    public function id()
    {
        $user = $this->user();
        return $user ? (int) $user['id'] : null;
    }

    public function user()
    {
        if ($this->cached_user !== null) {
            return $this->cached_user;
        }
        $id = $this->CI->session->userdata('user_id');
        if (!$id) {
            $id = $this->from_remember_cookie();
        }
        if (!$id) {
            return null;
        }
        $user = $this->CI->db->where('id', $id)->where('deleted_at', null)->get('users')->row_array();
        $this->cached_user = $user ?: null;
        return $this->cached_user;
    }

    private function from_remember_cookie()
    {
        $token = get_cookie('remember_token');
        if (!$token) {
            return null;
        }
        $row = $this->CI->db->where('token', $token)->where('type', 'remember')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get('user_tokens')->row_array();
        if (!$row) {
            return null;
        }
        $this->CI->session->set_userdata('user_id', (int) $row['user_id']);
        return (int) $row['user_id'];
    }

    public function is_admin()
    {
        $user = $this->user();
        return $user && in_array($user['role'], array('admin', 'moderator'), true);
    }

    public function is_vip()
    {
        $user = $this->user();
        return $user && $user['is_vip'] && (!$user['vip_expired_at'] || $user['vip_expired_at'] > date('Y-m-d H:i:s'));
    }

    /** Cập nhật thời điểm hoạt động cuối, tối đa 1 lần / 5 phút. */
    public function touch_active()
    {
        $user = $this->user();
        if (!$user) {
            return;
        }
        // Ghi lại mỗi 60 giây. Ngưỡng cũ là 300 giây, đúng bằng cửa sổ tính online,
        // nên người đang duyệt vẫn có lúc bị coi là ngoại tuyến.
        if (!$user['last_active_at'] || strtotime($user['last_active_at']) < time() - 60) {
            $this->CI->db->where('id', $user['id'])
                ->update('users', array('last_active_at' => date('Y-m-d H:i:s')));
        }
    }
}
