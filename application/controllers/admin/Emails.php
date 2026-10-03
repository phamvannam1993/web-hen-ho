<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Theo dõi hệ thống email: đã gửi bao nhiêu, mở bao nhiêu, nhánh nào ăn hơn. */
class Emails extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_email');
    }

    public function index()
    {
        $ngay = (int) ($this->input->get('ngay') ?: 30);
        $ngay = in_array($ngay, array(7, 30, 90), true) ? $ngay : 30;

        $this->render('admin/emails/index', array(
            'title'   => 'Email',
            'ngay'    => $ngay,
            'theo_loai' => $this->m_email->thong_ke($ngay),
            'ab'      => $this->m_email->thong_ke_ab($ngay),
            'hang_doi' => $this->db->query(
                "SELECT status, COUNT(*) AS so FROM email_queue GROUP BY status"
            )->result_array(),
            'gan_day' => $this->db->select('id, type, to_email, subject, status, attempts, error, sent_at, opened_at, clicked_at, created_at')
                ->order_by('id', 'DESC')->limit(30)->get('email_queue')->result_array(),
        ));
    }
}
