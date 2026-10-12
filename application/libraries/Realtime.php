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

    /* ==================== Đẩy sự kiện sang máy chủ Node ==================== */

    /**
     * Địa chỉ HTTP nội bộ của máy chủ Node. Mặc định suy từ địa chỉ WebSocket
     * (ws://host:3001/ws -> http://host:3001); máy chủ thật nên đặt
     * REALTIME_HTTP_URL=http://127.0.0.1:3001 để PHP gọi thẳng, không qua proxy.
     */
    private function http_url()
    {
        $url = getenv('REALTIME_HTTP_URL') ?: setting('realtime_http_url', '');
        if ($url === '') {
            $p = parse_url($this->url());
            if (empty($p['host'])) {
                return '';
            }
            $url = (($p['scheme'] ?? 'ws') === 'wss' ? 'https' : 'http') . '://' . $p['host']
                 . (isset($p['port']) ? ':' . $p['port'] : '');
        }
        return rtrim($url, '/');
    }

    /** Tin xếp hàng trong yêu cầu này, gửi một lần ở cuối (xem phat()). */
    private $hang = array();

    /**
     * Đẩy sự kiện tới người dùng: $to là id người nhận hoặc 'all'.
     * Chỉ xếp hàng — gửi thật ở phat(), gọi tự động khi PHP kết thúc yêu cầu.
     */
    public function publish($to, array $event, $except = null)
    {
        if (!$this->enabled()) {
            return;
        }
        if (!$this->hang) {
            register_shutdown_function(array($this, 'phat'));
        }
        $this->hang[] = array('to' => $to, 'except' => $except, 'event' => $event);
    }

    /**
     * Gửi hàng đợi sang Node. Máy chủ Node tắt thì bỏ qua trong im lặng: web và
     * ứng dụng vẫn tự hỏi tin mới theo chu kỳ, chỉ là không tức thời.
     */
    public function phat()
    {
        $items = $this->hang;
        $this->hang = array();
        $url = $this->http_url();
        if (!$items || $url === '' || !function_exists('curl_init')) {
            return;
        }
        // Trả trang cho người dùng trước rồi mới gọi Node, khỏi bắt họ chờ
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        $ch = curl_init($url . '/publish');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'X-Realtime-Secret: ' . $this->secret()),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 400,
            CURLOPT_TIMEOUT_MS     => 1500,
        ));
        curl_exec($ch);
        curl_close($ch);
    }

    /** Có đúng secret của máy chủ Node không (cho endpoint Node gọi ngược về PHP). */
    public function la_may_chu($secret)
    {
        return $this->secret() !== '' && is_string($secret) && hash_equals($this->secret(), $secret);
    }

    /**
     * Tin nhắn riêng mới: gửi cho người nhận và cả các thiết bị khác của người
     * gửi. Định dạng tin giống hệt ajax/tin-nhan và /api/conversations/{id}.
     */
    public function tin_rieng($conversation_id, array $row, $sender_id, $receiver_id)
    {
        foreach (array($receiver_id => false, $sender_id => true) as $uid => $cua_toi) {
            $this->publish((int) $uid, array(
                't'              => 'chat.message',
                'conversationId' => (int) $conversation_id,
                'message'        => $this->tin($row, $cua_toi) + array('seen' => false, 'sender_id' => (int) $sender_id),
            ));
        }
    }

    /** Tin mới ở phòng chat chung — $row kèm display_name, nickname, avatar, gender, slug. */
    public function tin_phong(array $row)
    {
        $tin = $this->tin($row, false) + array(
            'name'    => display_name($row),
            'avatar'  => avatar_url($row['avatar'] ?? null, $row['gender'] ?? 'other'),
            'slug'    => $row['slug'] ?? null,
            'user_id' => (int) $row['user_id'],
            'user'    => array(
                'id'     => (int) $row['user_id'],
                'name'   => display_name($row),
                'slug'   => $row['slug'] ?? null,
                'avatar' => avatar_url($row['avatar'] ?? null, $row['gender'] ?? 'other'),
            ),
        );
        $this->publish('all', array('t' => 'room.message', 'message' => $tin), (int) $row['user_id']);
        $tin['mine'] = true;
        $this->publish((int) $row['user_id'], array('t' => 'room.message', 'message' => $tin));
    }

    /** Đẩy tin phòng chung theo id vừa ghi (Ajax::room_send và api Chat::room_send). */
    public function phong_moi($id)
    {
        if (!$this->enabled()) {
            return;
        }
        $row = $this->CI->db->select('r.id, r.user_id, r.type, r.content, r.created_at,
                                      u.display_name, u.nickname, u.avatar, u.gender, u.slug')
            ->from('room_messages r')->join('users u', 'u.id = r.user_id')
            ->where('r.id', (int) $id)->get()->row_array();
        if ($row) {
            $this->tin_phong($row);
        }
    }

    /** Người đọc vừa xem tin của $other_id trong hội thoại: báo để hiện "Đã xem". */
    public function da_doc($conversation_id, $other_id)
    {
        $this->publish((int) $other_id, array('t' => 'chat.seen', 'conversationId' => (int) $conversation_id));
    }

    private function tin(array $r, $cua_toi)
    {
        $ngay = date('Y-m-d', strtotime($r['created_at']));
        $nhan = $ngay === date('Y-m-d') ? 'Hôm nay'
              : ($ngay === date('Y-m-d', strtotime('-1 day')) ? 'Hôm qua' : date('d/m/Y', strtotime($r['created_at'])));
        return array(
            'id'      => (int) $r['id'],
            'mine'    => (bool) $cua_toi,
            'type'    => $r['type'],
            'content' => $r['type'] === 'image' ? base_url(ltrim($r['content'], '/')) : $r['content'],
            'time'    => date('H:i', strtotime($r['created_at'])),
            'day'     => $nhan,
        );
    }
}
