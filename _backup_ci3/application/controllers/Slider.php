<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Slider extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MSlider', 'MCategory']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới slider';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/sliders/form';
        $data['id'] = $id;
        $sliders = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($sliders)) {
            if(empty($sliders['title'])) {
                $dataError['title'] = 'Tiêu đề không được để trống';
            } else {
                $checkExist = $this->MSlider->getOneBy(['title' => $sliders['title']]);
                if($checkExist && $checkExist->id != $id) {
                    $dataError['title'] = 'Tiêu đề đã tồn tại';
                }
            }
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
                $sliders['image_url'] = $filepath;
                if($this->input->post("image_old")) {
                    if(file_exists($this->input->post("image_old"))) {
                        unlink($this->input->post("image_old"));
                    }
                }
            }
            if(empty($dataError)) {
                if($id == 0) {
                    $sliders['created_at'] = date('Y-m-d H:i:s');
                }
                $sliders['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MSlider->update($id, $sliders);
                    } else {
                        $this->MSlider->insert($sliders);
                    }
                    redirect('/admin/sliders');
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $sliders;
        if(!empty($dataError)) {
            $data["dataForm"] = $sliders;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa slider';
                $sliders = $this->MSlider->getOneByID($id);
                $data["dataForm"] = $sliders;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        try {
            $this->MSlider->delete($id);
            redirect('/admin/sliders');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách slider";
       
        $total = $this->MSlider->count_all(); // tổng số mục
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
        $sliders = $this->MSlider->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['page_name'] = 'sliders';
        $data['datas'] = $sliders;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/sliders/index';
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
                'key' => 'image_url',
                'name' => 'Ảnh',
                'type' => 'image'
            ],
        ];
    }

    public function getForm($data) {
        return [
            [
                'title' => 'Tiêu đề',
                'field' => 'title',
                'value' => isset($data['title']) ? $data['title'] : '',
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
