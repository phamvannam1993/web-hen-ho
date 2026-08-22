<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Category extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model('MCategory');
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới loại khóa học';
        $data['text_button'] = 'Lưu';
        $data['content'] = '/admin/pages/categories/form';
        $data['id'] = $id;
        $category = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($category)) {
            if(empty($category['name'])) {
                $dataError['name'] = 'Tên loại khóa học không được để trống';
            } else {
                $checkExist = $this->MCategory->getOneBy(['name' => $category['name']]);
                if($checkExist && $checkExist->id != $id) {
                    $dataError['name'] = 'Tên loại khóa học đã tồn tại';
                }
            }
            if(empty($dataError)) {
                $category['slug'] = createSlug($category['name']);
                if($id == 0) {
                    $category['created_at'] = date('Y-m-d H:i:s');
                }
                $category['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MCategory->update($id, $category);
                    } else {
                        $this->MCategory->insert($category);
                    }
                    redirect('/admin/category');
                } catch(Exception $ex) {
                echo $ex;
                die;
                }
            }
        }
        $data["dataForm"] = $category;
        if(!empty($dataError)) {
            $data["dataForm"] = $category;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa khóa học';
                $category= $this->MCategory->getOneByID($id);
                $data["dataForm"] = $category;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();
        try {
            $this->MCategory->delete($id);
            redirect('/admin/category');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách loại khóa học";
       
        $total = $this->MCategory->count_all(); // tổng số mục
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
        $categories = $this->MCategory->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['page_name'] = 'category';
        $data['datas'] = $categories;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/categories/index';
        $this->load->view('admin/layouts/app', $data);
    }

    public function listField() {
        return [
            [
                'key' => 'id',
                'name' => 'ID'
            ],
            [
                'key' => 'name',
                'name' => 'Tên loại khóa học'
            ],
            [
                'key' => 'title',
                'name' => 'Tiêu đề'
            ],
        ];
    }

    public function getForm($data) {
        return [
            [
                'title' => 'Tên loại khóa học',
                'field' => 'name',
                'value' => isset($data['name']) ? $data['name'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Tiêu đề',
                'field' => 'title',
                'value' => isset($data['title']) ? $data['title'] : '',
                'required' => true,
                'type' => 'text',
            ]
        ];
    }
}
