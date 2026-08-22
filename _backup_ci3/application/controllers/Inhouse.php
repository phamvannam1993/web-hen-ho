<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Inhouse extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MInhouse', 'MCategory']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới đào tạo inhouse';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/inhouse/form';
        $data['id'] = $id;
        $inhouse = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($inhouse)) {
            if(empty($inhouse['title'])) {
                $dataError['title'] = 'Tiêu đề không được để trống';
            } else {
                $checkExist = $this->MInhouse->getOneBy(['title' => $inhouse['title']]);
                if($checkExist && $checkExist->id != $id) {
                    $dataError['title'] = 'Tiêu đề đã tồn tại';
                }
            }
            if(empty($inhouse['content'])) {
                $dataError['content'] = 'Nội dung không được để trống';
            } 
            if(empty($dataError)) {
                if($id == 0) {
                    $inhouse['created_at'] = date('Y-m-d H:i:s');
                }
                $inhouse['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MInhouse->update($id, $inhouse);
                    } else {
                        $this->MInhouse->insert($inhouse);
                    }
                    redirect('/admin/inhouse');
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $inhouse;
        if(!empty($dataError)) {
            $data["dataForm"] = $inhouse;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa tin tức';
                $inhouse = $this->MInhouse->getOneByID($id);
                $data["dataForm"] = $inhouse;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        try {
            $this->MInhouse->delete($id);
            redirect('/admin/inhouse');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách đào tạo inhouse";
       
        $total = $this->MInhouse->count_all(); // tổng số mục
        $page = $this->input->get('page') ? $this->input->get('page') : 1; 
        $perPage = $this->input->get('limit') ? $this->input->get('limit') : 10; // số mục mỗi trang
        $totalPage = ceil($total / $perPage);

        // Tính vị trí mục đầu và cuối trên trang hiện tại
        $firstItem = ($page - 1) * $perPage + 1;
        $lastItem = min($firstItem + $perPage - 1, $total);

        // Tạo URL phân trang
        function pageUrl($p) {
            return '?page=' . $p;
        }
        $offset = ($page - 1) * $perPage;
        $inhouse = $this->MInhouse->get(['limit' => $perPage, 'offset' => $offset]);
        $previousPageUrl = $page > 1 ? pageUrl($page - 1) : null;
        $nextPageUrl = $page < $totalPage ? pageUrl($page + 1) : null;

        // Tạo danh sách trang sẽ hiển thị
        $pagesToShow = [];

        if ($totalPage <= 5) {
            // Nếu <= 5 trang thì hiển thị hết
            for ($i = 1; $i <= $totalPage; $i++) {
                $pagesToShow[] = $i;
            }
        } else {
            // Luôn hiển thị trang 1
            $pagesToShow[] = 1;

            // Các trang ở giữa
            $start = max(2, $page - 1);
            $end = min($totalPage - 1, $page + 1);

            if ($start > 2) {
                $pagesToShow[] = '...';
            }

            for ($i = $start; $i <= $end; $i++) {
                $pagesToShow[] = $i;
            }

            if ($end < $totalPage - 1) {
                $pagesToShow[] = '...';
            }
            // Luôn hiển thị trang cuối
            $pagesToShow[] = $totalPage;
        }
        $data['page_name'] = 'inhouse';
        $data['datas'] = $inhouse;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/inhouse/index';
        $this->load->view('admin/layouts/app', $data);
    }

    public function listField() {
        return [
            [
                'key' => 'id',
                'name' => 'ID',
                'type' => ''
            ],
            [
                'key' => 'title',
                'name' => 'Tiêu đề',
                'type' => ''
            ],
            [
                'key' => 'content',
                'name' => 'Nội dung',
                'type' => ''
            ],
        ];
    }

    public function getForm($data) {
        $items = $this->MCategory->get([]);
        return [
            [
                'title' => 'Tiêu đề',
                'field' => 'title',
                'value' => isset($data['title']) ? $data['title'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Nội dung',
                'field' => 'content',
                'value' => isset($data['content']) ? $data['content'] : '',
                'required' => true,
                'type' => 'textarea',
            ],
        ];
    }
}
