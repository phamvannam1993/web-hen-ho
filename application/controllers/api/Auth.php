<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Đăng nhập / đăng xuất cho ứng dụng di động. */
class Auth extends Api_Controller
{
    /** POST /api/auth/login  {identity, password, device} */
    public function login()
    {
        $identity = trim((string) $this->body('identity'));
        $password = (string) $this->body('password');

        if ($identity === '' || $password === '') {
            return $this->loi('Thiếu email/SĐT hoặc mật khẩu.', 422);
        }

        $u = $this->auth->kiem_mat_khau($identity, $password);
        if ($u === 'locked') {
            return $this->loi('Tài khoản đang bị khoá.', 403);
        }
        if (!$u) {
            return $this->loi('Email/SĐT hoặc mật khẩu không đúng.', 401);
        }

        // Chưa xác thực email thì chưa cấp token, giống hệt luồng web
        if (setting('otp_register', '1') === '1' && empty($u['email_verified_at']) && !empty($u['email'])) {
            return $this->loi('Email chưa được xác thực. Vui lòng xác thực trước khi đăng nhập.', 403,
                array('need_verify' => true));
        }

        // Token gốc chỉ xuất hiện đúng một lần ở đây, DB chỉ giữ bản băm
        $token = bin2hex(random_bytes(32));
        $this->db->insert('api_tokens', array(
            'user_id'    => $u['id'],
            'token_hash' => hash('sha256', $token),
            'device'     => mb_substr((string) $this->body('device', ''), 0, 120) ?: null,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
        ));

        return $this->ok(array(
            'token'      => $token,
            'expires_at' => date('c', strtotime('+90 days')),
            'user'       => $this->ho_so($u, true),
        ));
    }

    /** POST /api/auth/logout — thu hồi token đang dùng. */
    public function logout()
    {
        if (!$this->can_auth()) {
            return;
        }
        $h = $this->input->get_request_header('Authorization', true);
        preg_match('/^Bearer\s+(.+)$/i', trim((string) $h), $m);

        $this->db->where('token_hash', hash('sha256', trim($m[1])))
            ->update('api_tokens', array('revoked_at' => date('Y-m-d H:i:s')));

        return $this->ok(array('message' => 'Đã đăng xuất.'));
    }

    /** GET /api/auth/me */
    public function me()
    {
        if (!$this->can_auth()) {
            return;
        }
        $this->load->model('m_streak');
        $s = $this->m_streak->get($this->me['id']);

        return $this->ok(array(
            'user'   => $this->ho_so($this->me, true),
            'coins'  => (int) $this->me['coin_balance'],
            'is_vip' => (bool) $this->me['is_vip'],
            'streak' => (int) $s['current_streak'],
        ));
    }
}
