<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class User extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MUser', 'MCategory']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới người dùng';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/users/form';
        $data['id'] = $id;
        $users = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($users)) {
            $checkExist = $this->MUser->getOneBy(['username' => $users['username']]);
            if($checkExist && $checkExist->id != $id) {
                $dataError['username'] = 'Tên tài khoản không tồn tại.';
            }
            if(empty($dataError)) {
                $users['password_old'] = $users['password'];
                $users['password'] = md5($users['password']);
                if($id == 0) {
                    $users['created_at'] = date('Y-m-d H:i:s');
                }
                $users['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MUser->update($id, $users);
                    } else {
                        $this->MUser->insert($users);
                    }
                    redirect('/admin/users');
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $users;
        if(!empty($dataError)) {
            $data["dataForm"] = $users;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa người dùng';
                $users = $this->MUser->getOneByID($id);
                $data["dataForm"] = $users;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        try {
            $this->MUser->delete($id);
            redirect('/admin/users');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách người dùng";
       
        $total = $this->MUser->count_all(); // tổng số mục
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
        $users = $this->MUser->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['page_name'] = 'users';
        $data['datas'] = $users;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/users/index';
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
                'key' => 'fullname',
                'name' => 'Họ và tên',
                'type' => ''
            ],
            [
                'key' => 'username',
                'name' => 'Tên tài khoản',
                'type' => ''
            ],
        ];
    }

    public function getForm($data) {
        $items = $this->MCategory->get([]);
        return [
            [
                'title' => 'Họ và tên',
                'field' => 'fullname',
                'value' => isset($data['fullname']) ? $data['fullname'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Tên tài khoản',
                'field' => 'username',
                'value' => isset($data['username']) ? $data['username'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Mật khẩu',
                'field' => 'password',
                'value' => isset($data['password_old']) ? $data['password_old'] : '',
                'required' => true,
                'type' => 'password',
            ],
        ];
    }
}
