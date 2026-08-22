<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{
    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/userguide3/general/urls.html
     */
    protected $setting;
    protected $categories;
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model(['MAdmin', 'MSetting', 'MSlider', 'MPartner', 'MProduct', 'MExpert', 'MExpertProduct', 'MCategory', 'MNew', 'MInhouse', 'MIntroduce', 'MOpeningSchedule', 'MOrder', 'MContact']);
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->setting = $this->MSetting->getOne();
        $this->categories = $this->MCategory->get([]);
    }

    public function index(){
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $data['title'] = isset($this->setting->title_home) ? $this->setting->title_home : '';
        $data['description'] =  isset($this->setting->des_home) ? $this->setting->des_home : '';
        $data['course_populars'] = $this->MProduct->get(['is_popular' => 1]);
        $data['sliders'] = $this->MSlider->get();
        $categories = $this->MCategory->get([]);
        $categorieArr = [];
        foreach($categories as $item) {
            $item['products'] = $this->MProduct->get(['category_id' => $item['id']]);
            $categorieArr[] = $item;
        }
        $data['partners'] = $this->MPartner->get();
        $data['categories'] = $categorieArr;
        $this->load->view('index',$data);
    }

    public function lichKhaiGiang(){
        $data['course_populars'] = $this->MProduct->get(['is_popular' => 1]);
        $data['news'] = $this->MNew->get(['limit' => 20, 'offset' => 0]);
        $data['title'] = "Lịch khai giảng";
        $productHN = [];
        $productHCM = [];
        $categoriesHN = [];
        $categoriesHCM = [];
        $openingScheduleHN = $this->MOpeningSchedule->get(['address_id' => 1]);
        foreach($openingScheduleHN as $item) {
            $product = $this->MProduct->getOneByID($item['product_id']);
            if($product) {
                $productHN[$item['product_id']]['detail'] = $product;
                $productHN[$item['product_id']]['items'][] = $item;
            }
        }
        $openingScheduleHCM = $this->MOpeningSchedule->get(['address_id' => 2]);
        foreach($openingScheduleHCM as $item) {
            $product = $this->MProduct->getOneByID($item['product_id']);
            if($product) {
                $productHCM[$item['product_id']]['detail'] = $product;
                $productHCM[$item['product_id']]['items'][] = $item;
            }
        }
        if(!empty($productHN)) {
            foreach($productHN as $key => $item) {
                $detail = $item['detail'];
                if($detail) {
                    $category = $this->MCategory->getOneByID($detail['category_id']);
                    $categoriesHN[$category['id']]['detail'] = $category;
                    $categoriesHN[$category['id']]['products'][$key] = $item;
                }
            }
        }
        if(!empty($productHCM)) {
            foreach($productHCM as $key => $item) {
                $detail = $item['detail'];
                if($detail) {
                    $category = $this->MCategory->getOneByID($detail['category_id']);
                    $categoriesHCM[$category['id']]['detail'] = $category;
                    $categoriesHCM[$category['id']]['products'][$key] = $item;
                }
            }
        }
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $data['categoriesHN'] = $categoriesHN;
        $data['categoriesHCM'] = $categoriesHCM;
        $this->load->view('front-end/lich-khai-giang',$data);
    }

    public function daoTaoInhouse(){
        $data['setting'] = $this->setting;
        $data['title'] = "Đào tạo inhouse";
        $data['categories'] = $this->categories;
        $data['inhouses'] = $this->MInhouse->get([]);
        $this->load->view('front-end/dao-tao-inhouse',$data);
    }

    public function chiTietKhoaHoc($slug){
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $data['product'] = $this->MProduct->getOneBy(['slug' => $slug]);
        if(!$data['product']) {
            redirect('/');
        }
        $data['title'] =  $data['product']->name;
        $expertProduct = $this->MExpertProduct->get(['product_id' => $data['product']->id]);
        $experts = [];
        foreach($expertProduct as $item) {
            $expert = $this->MExpert->getOneBy(['id' => $item['expert_id']]);
            if(!empty( $expert)) {
                $experts[] = $expert;
            }
        }
        $data['experts'] = $experts;
        $data['course_populars'] = $this->MProduct->get(['is_popular' => 1]);
        $data['openingScheduleHN'] = $this->MOpeningSchedule->get(['address_id' => 1, 'product_id' =>  $data['product']->id]);
        $data['openingScheduleHCM'] = $this->MOpeningSchedule->get(['address_id' => 2, 'product_id' =>  $data['product']->id]);
        $this->load->view('front-end/chi-tiet-khoa-hoc', $data);
    }
    
    public function tintuc(){
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $data['title'] = "Danh sách tin tức";
       
        $total = $this->MNew->count_all(); // tổng số mục
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
        $news = $this->MNew->get(['limit' => $perPage, 'offset' => $offset]);
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
        $data['dataNews'] = $this->MNew->get(['limit' => 5, 'offset' => 0]);
        $data['datas'] = $news;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;
        $this->load->view('front-end/tin-tuc',$data);
    }

    public function lienHe(){
        $data['setting'] = $this->setting;
        $data['title'] =  "Liên hệ";
        $data['categories'] = $this->categories;
        $req = $this->input->post();
        if($req) {
            $contact = $req['contact'];
            $data['success'] = true;
            $this->MContact->insert($contact);
            $this->load->view('front-end/lien-he',$data);
        } else {
            $this->load->view('front-end/lien-he',$data);
        } 
        
    }
    
    public function tamNhinSuMenh(){
        $data['setting'] = $this->setting;
        $data['title'] =  "Tầm nhìn xứ mệnh";
        $data['categories'] = $this->categories;
        $data['introduce'] = $this->MIntroduce->getOneByID(1);
        $this->load->view('front-end/tam-nhin-su-menh',$data);
    }

    public function giaTriCotLoi(){
        $data['setting'] = $this->setting;
        $data['title'] =  "Giá trị cốt lõi";
        $data['categories'] = $this->categories;
        $data['introduce'] = $this->MIntroduce->getOneByID(2);
        $this->load->view('front-end/gia-tri-cot-loi',$data);
    }

    public function chitietTinTuc($slug) {
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $newDetail = $this->MNew->getOneBy(['slug' => $slug]);
        if(!$newDetail) {
            redirect('/tin-tuc');
        }
        $data['title'] =  $newDetail->title;
        $data['newDetail'] = $newDetail;
        $this->load->view('front-end/chi-tiet-tin-tuc',$data);
    }

    public function soDoToChuc() {
        $data['setting'] = $this->setting;
        $data['title'] = "Sơ đồ tổ chức";
        $data['categories'] = $this->categories;
        $data['introduce'] = $this->MIntroduce->getOneByID(3);
        $this->load->view('front-end/so-do-to-chuc',$data);
    }

    public function giangVien(){
        $data['setting'] = $this->setting;
        $data['title'] = "Giảng viên";
        $data['categories'] = $this->categories;

        $total = $this->MExpert->count_all(); // tổng số mục
        $page = $this->input->get('page') ? $this->input->get('page') : 1; 
        $perPage = $this->input->get('limit') ? $this->input->get('limit') : 4; // số mục mỗi trang
        $totalPage = ceil($total / $perPage);

        // Tính vị trí mục đầu và cuối trên trang hiện tại
        $firstItem = ($page - 1) * $perPage + 1;
        $lastItem = min($firstItem + $perPage - 1, $total);
        
        // Tạo URL phân trang
        function pageUrl2($p) {
            return '?page=' . $p;
        }

        $offset = ($page - 1) * $perPage;
        $news = $this->MExpert->get(['limit' => $perPage, 'offset' => $offset]);
        $previousPageUrl = $page > 1 ? pageUrl2($page - 1) : null;
        $nextPageUrl = $page < $totalPage ? pageUrl2($page + 1) : null;

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
        $data['datas'] = $news;
        $data['lastItem'] = $lastItem;
        $data['page'] = $page;
        $data['pagesToShow'] = $pagesToShow; 
        $data['perPage'] = $perPage;
        $data['totalPage'] = $totalPage;
        $data['firstItem'] = $firstItem;
        $data['nextPageUrl'] = $nextPageUrl;
        $data['previousPageUrl'] = $previousPageUrl;

        $this->load->view('front-end/giang-vien',$data);
    }

    public function chiTietLoaiKhoaHoc($slug){
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $category = $this->MCategory->getOneBy(['slug' => $slug]);
        if(!$category) {
            redirect('/');
        }
        $products = $this->MProduct->get(['category_id' => $category->id]);
        $data['title'] = $category->name;
        $data['products'] = $products;
        $data['category'] = $category;
        $this->load->view('front-end/chi-tiet-loai-khoa-hoc',$data);
    }

    public function timKiem($search){
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        $data['title'] = "Thông tin tìm kiếm khóa học ".$search;
        $products = $this->MProduct->get(['search' => $search]);
        $data['products'] = $products;
        $this->load->view('front-end/tim-kiem',$data);
    }

    public function register($id) {
        $req = $this->input->post();
        $product = $this->MProduct->getOneByID($id);
        if(!$product) {
            redirect('/');
        }
        $data['product'] = $product;
        $data['setting'] = $this->setting;
        $data['categories'] = $this->categories;
        if($req) {
            $order = $req['order'];
            $order['product_id'] = $id;
            $order['status'] = 'PENDING';
            $data['success'] = true;
            $this->MOrder->insert($order);
            $this->load->view('front-end/dang-ky-khoa-hoc',$data);
        } else {
            $this->load->view('front-end/dang-ky-khoa-hoc',$data);
        } 
    }
}