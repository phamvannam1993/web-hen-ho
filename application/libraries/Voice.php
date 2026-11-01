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
}
