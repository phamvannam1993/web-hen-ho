<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lớp bọc quanh thư viện Email của CodeIgniter.
 *
 * Gom việc dựng người gửi, tiêu đề và bố cục thư về một chỗ, để nơi gọi
 * chỉ cần quan tâm nội dung. Mọi lỗi gửi đều được ghi vào log thay vì
 * làm hỏng luồng của người dùng.
 */
class Mailer
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('email');
    }

    /** Đã khai báo đủ thông tin để gửi chưa. */
    public function enabled()
    {
        return getenv('MAIL_HOST') !== false && getenv('MAIL_HOST') !== '';
    }

    /**
     * Gửi một email dạng HTML.
     *
     * @param string $to      địa chỉ nhận
     * @param string $subject tiêu đề
     * @param string $view    tên view nội dung, nằm trong application/views/emails/
     * @param array  $data    dữ liệu truyền cho view
     * @return bool
     */
    public function send($to, $subject, $view, $data = array())
    {
        $from_address = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME');
        $from_name    = trim((string) getenv('MAIL_FROM_NAME'), '"\'') ?: setting('site_name', 'HenHo24');

        if (!$from_address) {
            log_message('error', 'Mailer: chưa cấu hình MAIL_FROM_ADDRESS trong .env');
            return false;
        }

        // Nội dung được bọc trong khung thư dùng chung
        $data['site_name'] = setting('site_name', 'HenHo24');
        $body = $this->CI->load->view('emails/layout', array(
            'site_name' => $data['site_name'],
            'content'   => $this->CI->load->view('emails/' . $view, $data, true),
        ), true);

        $this->CI->email->clear(true);
        $this->CI->email->from($from_address, $from_name);
        $this->CI->email->to($to);
        $this->CI->email->subject($subject);
        $this->CI->email->message($body);
        $this->CI->email->set_alt_message(trim(strip_tags($body)));

        if ($this->CI->email->send(false)) {
            return true;
        }

        log_message('error', 'Mailer: gửi thất bại tới ' . $to . ' — '
            . strip_tags($this->CI->email->print_debugger(array('headers'))));
        return false;
    }
}
