<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class Product extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MProduct', 'MCategory', 'MExpert']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới khóa học';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/products/form';
        $data['id'] = $id;
        $product = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($product)) {
            $product['slug'] = createSlug($product['name']);
            if(empty($product['name'])) {
                $dataError['name'] = 'Tên khóa học không được để trống';
            } else {
                $checkExist = $this->MProduct->getOneBy(['name' => $product['name']]);
                if($checkExist && $checkExist->id != $id) {
                    $dataError['name'] = 'Tên khóa học đã tồn tại';
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
                $product['image_url'] = $filepath;
                if($this->input->post("image_old")) {
                    if(file_exists($this->input->post("image_old"))) {
                        unlink($this->input->post("image_old"));
                    }
                }
            }
            if(empty($dataError)) {
                if($id == 0) {
                    $product['created_at'] = date('Y-m-d H:i:s');
                }
                $product['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MProduct->update($id, $product);
                    } else {
                        $this->MProduct->insert($product);
                    }
                    redirect('/admin/product');
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $product;
        if(!empty($dataError)) {
            $data["dataForm"] = $product;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa khóa học';
                $product = $this->MProduct->getOneByID($id);
                $data["dataForm"] = $product;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        try {
            $this->MProduct->delete($id);
            redirect('/admin/product');
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách khóa học";
       
        $total = $this->MProduct->count_all(); // tổng số mục
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
        $categories = $this->MProduct->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['page_name'] = 'product';
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
        $data['content'] = '/admin/pages/products/index';
        $this->load->view('admin/layouts/app', $data);
    }

    public function listField() {
        $categories = $this->MCategory->get([]);
        $populars = $this->MProduct->getPopular();
        $address = $this->MProduct->getAddress();
        return [
            [
                'key' => 'id',
                'name' => 'ID',
                'type' => ''
            ],
            [
                'key' => 'name',
                'name' => 'Tên khóa học',
                'type' => ''
            ],
            [
                'key' => 'category_id',
                'name' => 'Tên loại khóa học',
                'type' => 'option',
                'items' => $categories,
            ],
            [
                'key' => 'image_url',
                'name' => 'Ảnh khóa học',
                'type' => 'image'
            ],
            [
                'key' => 'price_old',
                'name' => 'Giá cũ',
                'type' => ''
            ],
            [
                'key' => 'price',
                'name' => 'Giá mới',
                'type' => ''
            ],
            [
                'key' => 'address_id',
                'name' => 'Địa chỉ',
                'type' => 'option',
                'items' => $address,
            ],
            [
                'key' => 'is_popular',
                'name' => 'Khóa học phổ biến',
                'type' => 'option',
                'items' => $populars,
            ],
        ];
    }

    public function getForm($data) {
        $items = $this->MExpert->get([]);
        $categories = $this->MCategory->get([]);
        $populars = $this->MProduct->getPopular();
        $address = $this->MProduct->getAddress();
        return [
            [
                'title' => 'Tên khóa học',
                'field' => 'name',
                'value' => isset($data['name']) ? $data['name'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Loại khóa học',
                'field' => 'category_id',
                'value' => isset($data['category_id']) ? $data['category_id'] : 0,
                'required' => true,
                'items' => $categories,
                'type' => 'option',
            ],
            [
                'title' => 'Khóa học phổ biến',
                'field' => 'is_popular',
                'value' => isset($data['is_popular']) ? $data['is_popular'] : '',
                'required' => true,
                'items' => $populars,
                'type' => 'option',
            ],
            [
                'title' => 'Ảnh khóa học',
                'field' => 'image_url',
                'value' => isset($data['image_url']) ? $data['image_url'] : '',
                'required' => true,
                'type' => 'file',
            ],
            [
                'title' => 'Giá cũ',
                'field' => 'price_old',
                'value' => isset($data['price_old']) ? $data['price_old'] : 0,
                'required' => true,
                'type' => 'number',
            ],
            [
                'title' => 'Giá mới',
                'field' => 'price',
                'value' => isset($data['price']) ? $data['price'] : 0,
                'required' => true,
                'type' => 'number',
            ],
            [
                'title' => 'Địa chỉ',
                'field' => 'address_id',
                'value' => isset($data['address_id']) ? $data['address_id'] : '',
                'required' => true,
                'items' => $address,
                'type' => 'option',
            ],
            [
                'title' => 'Giới thiệu',
                'field' => 'introduce',
                'value' => isset($data['introduce']) ? $data['introduce'] : '',
                'required' => true,
                'type' => 'textarea',
            ],
            [
                'title' => 'Nội dung',
                'field' => 'content',
                'value' => isset($data['content']) ? $data['content'] : '',
                'required' => true,
                'type' => 'textarea',
            ],
            [
                'title' => 'Chuyên gia',
                'field' => 'expert_ids',
                'value' => isset($data['expert_ids']) ? $data['expert_ids'] : [],
                'required' => true,
                'type' => 'multi-option',
                'items' => $items,
            ],
        ];
    }

}
