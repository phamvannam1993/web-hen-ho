<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Expert extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MExpert', 'MCategory']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới chuyên gia';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/experts/form';
        $data['id'] = $id;
        $experts = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($experts)) {
            if(empty($experts['name'])) {
                $dataError['name'] = 'Họ tên không được để trống';
            } else {
                $checkExist = $this->MExpert->getOneBy(['name' => $experts['name']]);
                if($checkExist && $checkExist->id != $id) {
                    $dataError['name'] = 'Tiêu đề đã tồn tại';
                }
            }
            $experts['slug'] = createSlug($experts['name']);
            if($this->input->post("image_url")) {
                $base64Image = $this->input->post("image_url");
                // Tách định dạng và dữ liệu
                list($type, $dataImage) = explode(';', $base64Image);
                list(, $dataImage) = explode(',', $dataImage);

                $dataImage = base64_decode($dataImage);
                // Xác định phần mở rộng ảnh từ định dạng MIME
                $ext = '';
                if (strpos($type, 'image/jpeg') !== false) {
                    $ext = 'jpg';
                } elseif (strpos($type, 'image/png') !== false) {
                    $ext = 'png';
                } elseif (strpos($type, 'image/gif') !== false) {
                    $ext = 'gif';
                } 
                $filename = uniqid() . '.' . $ext;
                $filepath = 'assets/uploads/' . $filename;  // Đảm bảo thư mục 'writable/uploads/' tồn tại
                file_put_contents($filepath, $dataImage);
                $experts['image_url'] = $filepath;
                if($this->input->post("image_old")) {
                    if(file_exists($this->input->post("image_old"))) {
                        unlink($this->input->post("image_old"));
                    }
                }
            }
            if(empty($dataError)) {
                if($id == 0) {
                    $experts['created_at'] = date('Y-m-d H:i:s');
                }
                $experts['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MExpert->update($id, $experts);
                    } else {
                        $this->MExpert->insert($experts);
                    }
                    redirect('/admin/experts');
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $experts;
        if(!empty($dataError)) {
            $data["dataForm"] = $experts;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa tin tức';
                $experts = $this->MExpert->getOneByID($id);
                $data["dataForm"] = $experts;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        try {
            $this->MExpert->delete($id);
            redirect('/admin/experts');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách giới thiệu";
       
        $total = $this->MExpert->count_all(); // tổng số mục
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
        $experts = $this->MExpert->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['page_name'] = 'experts';
        $data['datas'] = $experts;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/experts/index';
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
                'key' => 'name',
                'name' => 'Họ và tên',
                'type' => ''
            ],
            [
                'key' => 'image_url',
                'name' => 'Ảnh',
                'type' => 'image'
            ],
            [
                'key' => 'position',
                'name' => 'Vị trí',
                'type' => ''
            ],
            [
                'key' => 'experience',
                'name' => 'Kinh nghiệm',
                'type' => ''
            ],
            
        ];
    }

    public function getForm($data) {
        return [
            [
                'title' => 'Họ và tên',
                'field' => 'name',
                'value' => isset($data['name']) ? $data['name'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Vị trí',
                'field' => 'postion',
                'value' => isset($data['postion']) ? $data['postion'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Ảnh',
                'field' => 'image_url',
                'value' => isset($data['image_url']) ? $data['image_url'] : '',
                'required' => true,
                'type' => 'file',
            ],
        ];
    }
}
