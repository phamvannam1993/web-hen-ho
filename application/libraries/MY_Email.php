<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mở rộng thư viện Email để cho phép bỏ kiểm tra chứng chỉ TLS của máy chủ SMTP.
 *
 * Bản gốc dùng fsockopen() nên luôn đòi chứng chỉ hợp lệ. Máy chủ mail tự
 * dựng (vd. mail.saigoncupid.com) hay dùng chứng chỉ tự ký, khi đó STARTTLS
 * báo "certificate verify failed" và không gửi được thư nào.
 *
 * Đặt MAIL_VERIFY_PEER=false trong .env để tắt kiểm tra. Mặc định vẫn kiểm tra.
 * Cách đúng lâu dài vẫn là cài chứng chỉ thật (Let's Encrypt) cho máy chủ mail.
 */
class MY_Email extends CI_Email
{
    /** Có kiểm tra chứng chỉ của máy chủ SMTP hay không */
    public $smtp_verify_peer = TRUE;

    protected function _smtp_connect()
    {
        if (is_resource($this->_smtp_connect))
        {
            return TRUE;
        }

        $ssl = ($this->smtp_crypto === 'ssl') ? 'ssl://' : '';

        $context = stream_context_create(array('ssl' => array(
            'verify_peer'       => (bool) $this->smtp_verify_peer,
            'verify_peer_name'  => (bool) $this->smtp_verify_peer,
            'allow_self_signed' => ! $this->smtp_verify_peer,
        )));

        $this->_smtp_connect = stream_socket_client($ssl.$this->smtp_host.':'.$this->smtp_port,
            $errno,
            $errstr,
            $this->smtp_timeout,
            STREAM_CLIENT_CONNECT,
            $context);

        if ( ! is_resource($this->_smtp_connect))
        {
            $this->_set_error_message('lang:email_smtp_error', $errno.' '.$errstr);
            return FALSE;
        }

        stream_set_timeout($this->_smtp_connect, $this->smtp_timeout);
        $this->_set_error_message($this->_get_smtp_data());

        if ($this->smtp_crypto === 'tls')
        {
            $this->_send_command('hello');
            $this->_send_command('starttls');

            // Cho phép thương lượng TLS 1.0 - 1.3
            $method = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT'))
            {
                $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (stream_socket_enable_crypto($this->_smtp_connect, TRUE, $method) !== TRUE)
            {
                $this->_set_error_message('lang:email_smtp_error', $this->_get_smtp_data());
                return FALSE;
            }
        }

        return $this->_send_command('hello');
    }
}
