<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Database extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model('MCategory');
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
    }

    public function exportDB() {
        $this->load->dbutil();
        $this->load->dbutil();
        $this->load->helper('file');
        $this->load->helper('download');
        $db_format=array('format'=>'zip','filename'=>'honkailab.sql');
        $backup=& $this->dbutil->backup($db_format);
        $dbname='backup-on-'.date('Y-m-d').'.zip';
        $save='assets/db/'.$dbname;
        write_file($save,$backup);
        force_download($dbname,$backup);    
    }

    public function exportDB2() {
        $this->load->dbutil();
        $this->load->dbutil();
        $this->load->helper('file');
        // Export chỉ một bảng (thay your_table_name bằng bảng của bạn)
        $prefs = array(
            'tables'    => array('partners'), // Xuất bảng cụ thể
            'format'    => 'txt', // Định dạng text SQL
            'add_drop'  => TRUE, // Thêm câu lệnh DROP TABLE IF EXISTS
            'add_insert'=> TRUE, // Thêm câu INSERT INTO
            'newline'   => "\n"  // Xuống dòng
        );

        $backup = $this->dbutil->backup($prefs); // Tạo backup SQL

        // Tạo file SQL và tải xuống
        $filename = 'partners.sql';
        $this->load->helper('download');
        force_download($filename, $backup);
    }
}
