<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class ExpertProduct extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MOpeningSchedule', 'MCategory', 'MProduct']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
        if (!admin()) { 
            redirect('admin/login');
        }
    }

    public function form($id = 0) {
        $data['title'] = 'Thêm mới lịch khai giảng';
        $data['text_button'] = $id > 0 ? 'Cập nhật' : 'Lưu';
        $data['content'] = '/admin/pages/opening-schedules/form';
        $data['id'] = $id;
        $data['product_id'] = $this->input->get("product_id");
        $data['address_id'] = $this->input->get("address_id");
        if(!$data['product_id'] || !$data['address_id']) {
            redirect('/admin/product');
        }
        $openingSchedule = $this->input->post("dataForm");
        $dataError = [];
        if(!empty($openingSchedule)) {
            if(empty($dataError)) {
                if($id == 0) {
                    $openingSchedule['created_at'] = date('Y-m-d H:i:s');
                }
                $openingSchedule['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if($id > 0) {
                        $this->MOpeningSchedule->update($id, $openingSchedule);
                    } else {
                        $this->MOpeningSchedule->insert($openingSchedule);
                    }
                    redirect('/admin/opening-schedules?product_id='.$data['product_id'].'&address_id='.$data['address_id']);
                } catch(Exception $ex) {
                   echo $ex;
                   die;
                }
            }
        }
        $data["dataForm"] = $openingSchedule;
        if(!empty($dataError)) {
            $data["dataForm"] = $openingSchedule;
            $data["dataError"] = $dataError;
        } else {
            if($id > 0) {
                $data['title'] = 'Sửa lịch khai giảng';
                $openingSchedule = $this->MOpeningSchedule->getOneByID($id);
                $data["dataForm"] = $openingSchedule;
            } 
        }
        $data['forms'] = $this->getForm($data["dataForm"]);
        $this->load->view('admin/layouts/app', $data);
    }

    public function delete($id) {
        // is_admin();  
        $data['product_id'] = $this->input->get("product_id");
        $data['address_id'] = $this->input->get("address_id");
        try {
            $this->MOpeningSchedule->delete($id);
            redirect('/admin/opening-schedules?product_id='. $data['product_id'].'&address_id='.$data['address_id']);
        } catch(\Exception $ex) {
            echo json_encode(["success" => false, "message" => "Xóa thất bại"]);
        }
    }

    public function index() {
        // is_admin();
        $data['title'] = "Danh sách lịch khai giảng";
        $data['product_id'] = $this->input->get("product_id");
        $data['address_id'] = $this->input->get("address_id");
        $total = $this->MOpeningSchedule->count_all(['product_id' => $data['product_id'], 'address_id' =>  $data['address_id']]); // tổng số mục
        $page = $this->input->get('page') ? $this->input->get('page') : 1; 
        $perPage = $this->input->get('limit') ? $this->input->get('limit') : 10; // số mục mỗi trang
        $totalPage = ceil($total / $perPage);

        // Tính vị trí mục đầu và cuối trên trang hiện tại
        $firstItem = $total > 0 ?  ($page - 1) * $perPage + 1 : 0;
        $lastItem = min($firstItem + $perPage - 1, $total);

        // Tạo URL phân trang
        function pageUrl($p) {
            return '?page=' . $p;
        }
        $offset = ($page - 1) * $perPage;
        $openingShedules = $this->MOpeningSchedule->get(['limit' => $perPage, 'offset' => $offset, 'product_id' => $data['product_id'], 'address_id' =>  $data['address_id']]);
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
        $data['page_name'] = 'opening-schedules';
        $data['datas'] = $openingShedules;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['fields'] = $this->listField();
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $data['content'] = '/admin/pages/opening-schedules/index';
        $this->load->view('admin/layouts/app', $data);
    }

    public function listField() {
        $address = $this->MProduct->getAddress();
        $products = $this->MProduct->get();
        return [
            [
                'key' => 'id',
                'name' => 'ID',
                'type' => ''
            ],
            [
                'key' => 'product_id',
                'name' => 'Khóa học',
                'type' => 'option',
                'items' => $products
            ],
            [
                'key' => 'address_id',
                'name' => 'Địa chỉ',
                'type' => 'option',
                'items' => $address
            ],
            [
                'key' => 'duration',
                'name' => 'Thời lượng',
                'type' => ''
            ],
            [
                'key' => 'day',
                'name' => 'Ngày khai giảng',
                'type' => ''
            ],
            [
                'key' => 'day_week',
                'name' => 'Ngày thứ',
                'type' => ''
            ],
            [
                'key' => 'address',
                'name' => 'Địa điểm học',
                'type' => ''
            ],
            [
                'key' => 'morning_time',
                'name' => 'Thời gian buổi sáng',
                'type' => ''
            ],
            [
                'key' => 'afternoon_time',
                'name' => 'Thời gian buổi chiều',
                'type' => ''
            ],
            [
                'key' => 'evening_time',
                'name' => 'Thời gian buổi tối',
                'type' => ''
            ],
        ];
    }

    public function getForm($data) {
        $items = $this->MCategory->get([]);
        return [
            [
                'title' => 'Thời lượng',
                'field' => 'duration',
                'value' => isset($data['duration']) ? $data['duration'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Ngày khai giảng',
                'field' => 'day',
                'value' => isset($data['day']) ? $data['day'] : '',
                'required' => true,
                'type' => 'date',
            ],
            [
                'title' => 'Ngày/thứ',
                'field' => 'day_week',
                'value' => isset($data['day_week']) ? $data['day_week'] : '',
                'required' => true,
                'type' => 'text',
            ],
            [
                'title' => 'Thời gian buổi sáng',
                'field' => 'morning_time',
                'value' => isset($data['morning_time']) ? $data['morning_time'] : '',
                'required' => false,
                'type' => 'text',
            ],
            [
                'title' => 'Thời gian buổi chiều',
                'field' => 'afternoon_time',
                'value' => isset($data['afternoon_time']) ? $data['afternoon_time'] : '',
                'required' => false,
                'type' => 'text',
            ],
            [
                'title' => 'Thời gian buổi tối',
                'field' => 'evening_time',
                'value' => isset($data['evening_time']) ? $data['evening_time'] : '',
                'required' => false,
                'type' => 'text',
            ],
            [
                'title' => 'Địa điểm học',
                'field' => 'address',
                'value' => isset($data['address']) ? $data['address'] : '',
                'required' => false,
                'type' => 'text',
            ],
        ];
    }
}
