<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cấu hình gọi thoại; shared secret chỉ được dùng ở server. */
class Voice
{
    public function config($user_id)
    {
        $disabled = array('enabled' => false);
        $user_id = (int) $user_id;
        if (getenv('VOICE_ENABLED') !== '1' || $user_id <= 0) {
            return $disabled;
        }

        $url = trim((string) getenv('VOICE_TURN_URL'));
        $secret = (string) getenv('VOICE_TURN_SECRET');
        $valid = preg_match(
            '~\Aturns?:[a-z0-9.-]+(?::[0-9]{1,5})?(?:\?transport=(?:udp|tcp))?\z~i',
            $url
        );
        if (!$valid || $secret === '') {
            return $disabled;
        }

        $expires = time() + 3600;
        $username = $expires . ':' . $user_id;
        $credential = base64_encode(
            hash_hmac('sha1', $username, $secret, true)
        );

        return array(
            'enabled' => true,
            'expiresAt' => $expires,
            'iceTransportPolicy' => 'relay',
            'iceServers' => array(
                array(
                    'urls' => array($url),
                    'username' => $username,
                    'credential' => $credential,
                ),
            ),
        );
    }

    private function positive_id($value)
    {
        if (!is_int($value) && !is_string($value)) return 0;
        return (int) filter_var($value, FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1),
        ));
    }

    /**
     * Người còn lại của hội thoại nếu hai người được gọi cho nhau (cùng luật web và API ứng dụng):
     * hội thoại của tôi, cả hai còn hoạt động + đã xác thực, không chặn nhau, giới hạn VIP.
     */
    public function peer($conversation_id, $uid)
    {
        $ci = get_instance();
        $ci->load->model('m_interaction');
        $cid = $this->positive_id($conversation_id);
        if (!$cid || !$uid) return null;
        $conv = $ci->db->where('id', $cid)
            ->get('conversations')->row_array();
        if (!$conv) return null;
        $ids = array((int) $conv['user_low_id'],
            (int) $conv['user_high_id']);
        if (!in_array($uid, $ids, true) || $ids[0] === $ids[1]) {
            return null;
        }
        $peer_id = $ids[0] === $uid ? $ids[1] : $ids[0];
        $users = array();
        foreach (array($uid, $peer_id) as $id) {
            $user = $ci->db->where('id', $id)
                ->where('deleted_at', null)->where('status', 'active')
                ->get('users')->row_array();
            if (!$user || !$this->verified($user)) return null;
            $users[$id] = $user;
        }
        if ($ci->m_interaction->is_blocked($uid, $peer_id)) return null;
        foreach (array($uid => $peer_id, $peer_id => $uid) as $from => $to) {
            $pref = $ci->db->where('user_id', $to)
                ->get('user_preferences')->row_array();
            if (($pref['allow_message'] ?? 'all') === 'vip'
                && !member_has_vip($users[$from])) return null;
        }
        return array('id' => $peer_id,
            'name' => display_name($users[$peer_id]));
    }

    private function verified($user)
    {
        return setting('otp_register', '1') !== '1'
            || !empty($user['email_verified_at'])
            || empty($user['email'])
            || in_array($user['role'],
                array('super_admin', 'admin', 'moderator'), true);
    }
}
