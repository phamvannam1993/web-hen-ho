<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cầu nối tới máy chủ WebSocket (dự án Node.js ở thư mục websoket).
 *
 * PHP không giữ kết nối WebSocket, nó chỉ cấp cho trình duyệt một token
 * ngắn hạn có chữ ký. Node xác minh chữ ký bằng cùng chuỗi bí mật rồi mới
 * cho phép kết nối, nên không cần chia sẻ session giữa hai hệ thống.
 */
class Realtime
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Chuỗi bí mật dùng chung, đặt trong .env hoặc bảng settings. */
    private function secret()
    {
        return getenv('REALTIME_SECRET') ?: setting('realtime_secret', '');
    }

    /** Địa chỉ WebSocket cho trình duyệt, ví dụ ws://localhost:3001/ws */
    public function url()
    {
        return getenv('REALTIME_WS_URL') ?: setting('realtime_ws_url', '');
    }

    /** Realtime chỉ bật khi đã cấu hình đủ cả URL lẫn bí mật. */
    public function enabled()
    {
        return $this->url() !== '' && $this->secret() !== '';
    }

    /**
     * Cấp token cho người dùng hiện tại.
     * Định dạng: base64url(payload).base64url(hmac) — khớp với src/auth.js bên Node.
     *
     * @param int $ttl số giây token còn hiệu lực
     */
    public function token($user_id, $ttl = 3600)
    {
        $secret = $this->secret();
        if (!$user_id || $secret === '') {
            return null;
        }

        $payload = $this->base64url(json_encode(array(
            'uid' => (int) $user_id,
            'exp' => time() + (int) $ttl,
        )));

        $signature = $this->base64url(hash_hmac('sha256', $payload, $secret, true));

        return $payload . '.' . $signature;
    }

    private function base64url($binary)
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
