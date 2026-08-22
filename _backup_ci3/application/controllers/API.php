<?php
error_reporting(1);
defined('BASEPATH') or exit('No direct script access allowed');
class API extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MAdmin', 'MSetting', 'MExpertProduct', 'MPartner', 'MSlider', 'MProduct', 'MExpert', 'MCategory', 'MNew', 'MInhouse', 'MIntroduce', 'MOpeningSchedule', 'MOrder', 'MContact']);
        $this->load->database();
        $this->load->helper(array('form', 'url'));
        $this->load->helper(['url', 'func_helper', 'images']);
        $this->load->library(['session', 'pagination311', 'upload']);
    }

    public function cloneKhoaHoc() {
        $product = $this->MProduct->get();
        foreach($product as $item) {
            $this->MProduct->update($item['id'], ['slug' => createSlug($item['name'])]);
        }
        $url = "https://khoahocpti.com.vn/lich-khai-giang";
        $response = file_get_contents($url, true);
        $resArr = explode('<section class="right-content margin-bottom-50 col-md-8 ">', $response);
        $resArr2 = explode('<aside class="blog_hai left left-content col-md-4 " style="padding-left:30px">', $resArr[1]);
        $position_items = $resArr2[0];
        $html = $position_items;
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        $data = [];
        $headings = $xpath->query('//section[contains(@class,"time-section")]/h3');
        $tables = $xpath->query('//section[contains(@class,"time-section")]/table');
        
        foreach ($headings as $index => $heading) {
            $category = trim($heading->nodeValue);
            $table = $tables->item($index);
            $rows = $xpath->query('.//tbody/tr', $table);
        
            foreach ($rows as $row) {
                $cells = $xpath->query('./td', $row);
        
                if ($cells->length === 3) {
                    $courseNode = $cells->item(0)->getElementsByTagName("a")->item(0);
                    $course = trim($courseNode->nodeValue);
                    $url = $courseNode->getAttribute("href");
        
                    $start_date = trim($cells->item(1)->nodeValue);
                    $tuition = trim($cells->item(2)->nodeValue);
        
                    $data[] = [
                        'category' => $category,
                        'course' => $course,
                        'url' => $url,
                        'start_date' => $start_date,
                        'tuition' => $tuition
                    ];
                }
            }
        }
        foreach($data as $item) {
            $category = $this->MCategory->getOneBy(['title' => $item['category']]);
            if(!empty($category)) {
                $category_id = $category->id;
                $dataSave = [
                    'category_id' => $category_id,
                    'name' => $item['course'],
                    'address_id' => 1,
                    'slug' => $item['url'],
                    'price' => 0,
                    'price_old' => 0
                ];
                $product = $this->MProduct->getOneBy(['slug' => $dataSave['slug']]);
                if(empty($product)) {
                    $proId = $this->MProduct->insert($dataSave);
                } else {
                    $this->MProduct->update($product->id, $dataSave);
                }
                $product = $this->MProduct->getOneBy(['name' => $dataSave['name']]);
                $this->getDetailProduct($proId);
            }
        }
    }

    public function getDetailProduct($id) {
        $product = $this->MProduct->getOneBy(['id' => $id]);
        $url = "https://khoahocpti.com.vn". $product->slug;
        $response = file_get_contents($url, true);

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($response);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $paras = $xpath->query('//div[@class="content_service"]/p');

        $price = 0;
        $priceOld = 0;
        foreach ($paras as $p) {
            $label = trim($p->childNodes[0]->nodeValue);
            $valueRaw = $xpath->query('.//span', $p)->item(0)?->textContent;

            // Chỉ giữ số (loại bỏ ký tự ₫, dấu phẩy)
            $number = (int) str_replace([',', '₫', ' '], '', $valueRaw);

            if (str_contains($label, 'Học phí')) {
                $priceOld = $number;
            } elseif (str_contains($label, 'Phí ưu đãi')) {
                $price = $number;
            }
        }
        $img = $dom->getElementById('img_01');
        $src = $img ? $img->getAttribute('src') : null;
        $imageUrl = "https://khoahocpti.com.vn".$src;
        $nameImage = 'khoa_hoc_'.$id;
        $imageURL = $this->uploadFileByUrl($imageUrl, $nameImage);
        $resArr = explode('<div class="product-tab e-tabs">', $response);
        $resArr2 = explode('<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">', $resArr[1]);
        $position_items = $resArr2[0];
        libxml_use_internal_errors(true);

        $html = $position_items;
        
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        // Lấy tất cả div có id bắt đầu bằng "tab-" và class chứa "tab-content"
        $tabDivs = $xpath->query('//div[contains(@class, "tab-content") and starts-with(@id, "tab-")]');
        
        $tabs = [];
        
        foreach ($tabDivs as $div) {
            $id = $div->getAttribute("id");
        
            // Lấy innerHTML của div (gồm cả nội dung bên trong div.rte)
            $innerHTML = "";
            foreach ($div->childNodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
        
            $tabs[$id] = trim($innerHTML);
        }
        $giangVien = $tabs["tab-4"];
        $this->tachGiangVien($giangVien, $product->id);
        $lichKhaiGiang = $tabs["tab-3"];
        $this->tachKhaiGiang($lichKhaiGiang, $product->id);
        $this->MProduct->update($product->id, [
            'image_url' => $imageURL,
            'introduce' =>  $tabs["tab-1"],
            'content' =>  $tabs["tab-2"],
            'price' => $price,
            'price_old' => $priceOld
        ]);
    }

    public function tachGiangVien($html, $id) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // bỏ qua lỗi HTML không đúng chuẩn
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $teachers = [];

        foreach ($xpath->query('//div[contains(@class, "info_teacher")]') as $div) {
            $img = $xpath->query('.//img', $div)->item(0)?->getAttribute('src');
            $name = $xpath->query('.//span//b', $div)->item(0)?->textContent;
            $position = $xpath->query('.//div[contains(@class, "position")]', $div)->item(0)?->textContent;

            $teacher = [
                'image' => $img,
                'name' => trim($name),
                'position' => trim($position),
            ];
            $check = $this->MExpert->getOneBy(['name' => $teacher['name']]);
            $expertId = 0;
            if(empty($check)) {
                $dataSave = [
                    'name' => trim($name),
                    'position' => trim($position),
                    'image_url' => $this->uploadFileByUrl( $teacher['image'])
                ];
                $expertId = $this->MExpert->insert($dataSave);
            } else {
                $expertId = $check->id;
            }
            $checkPEx = $this->MExpertProduct->getOneBy(['product_id' => $id, 'expert_id' => $expertId]);
            if(empty($checkPEx)) {
                $this->MExpertProduct->insert(['product_id' => $id, 'expert_id' => $expertId]);
            } else {
                $this->MExpertProduct->update($checkPEx->id, ['product_id' => $id, 'expert_id' => $expertId]);
            }
        }
        return $teachers;
    }

    public function tachKhaiGiang($html, $id) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        // Tìm tất cả các hàng trong tbody
        $rows = $xpath->query('//table//tbody//tr');

        $data = [];
        for ($i = 0; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $class = $row->getAttribute("class");

            // Nếu là dòng chính (chứa thông tin khóa học)
            if (strpos($class, 'No_remove') !== false) {
                $cols = $row->getElementsByTagName('td');

                $course = trim($cols->item(0)->nodeValue);
                $duration = trim($cols->item(1)->nodeValue);
                $start_date = trim($cols->item(2)->nodeValue);
                $day = trim($cols->item(3)->nodeValue);
                $time_raw = trim($cols->item(4)->textContent);
                $time_parts = [];
                
                preg_match_all('/(Sáng|Chiều|Tối):\s*([0-9h:\s\-]+)/u', $time_raw, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $label = $match[1]; // Sáng / Chiều / Tối
                    $time_range = trim($match[2]);
                    $time_parts[$label] = $time_range;
                }

                // Dòng kế tiếp là địa điểm học
                $location = "";
                if (($i + 1) < $rows->length) {
                    $nextRow = $rows->item($i + 1);
                    if (strpos($nextRow->getAttribute("class"), 'wrapper') !== false) {
                        $locationDiv = $xpath->query('.//div[contains(@class,"show-maps")]', $nextRow);
                        if ($locationDiv->length > 0) {
                            $location = trim($locationDiv->item(0)->textContent);
                        }
                        $i++; // bỏ qua dòng địa điểm vì đã xử lý
                    }
                }

                $address_id = stripos($location, 'Hà Nội') !== false ? 1 : 2;

                $item = [
                    'duration' => $duration,
                    'day' => $start_date,
                    'day_week' => $day,
                    'morning_time' => isset($time_parts['Sáng']) ? $time_parts['Sáng'] : '',
                    'afternoon_time' => isset($time_parts['Chiều']) ? $time_parts['Chiều'] : '',
                    'evening_time' => isset($time_parts['Tối']) ? $time_parts['Tối'] : '',
                    'address' => $location,
                    'address_id' => $address_id,
                    'product_id' => $id
                ];
                $checkEixst = $this->MOpeningSchedule->getOneBy($item);
                if(empty($checkEixst)) {
                    $this->MOpeningSchedule->insert($item);
                }
            }
        }
        return $data;
    }

    public function uploadFileByUrl($url, $name = '') {
        if(empty($url)) {
            return "";
        }
        $dataImage = file_get_contents($url); // Dùng @ để tránh warning

        if ($dataImage === false) {
            return false; // Tải thất bại
        }
    
        // Lấy đuôi file từ URL (jpg, png...)
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) $ext = 'jpg'; // fallback
        $filename = uniqid() . '.' . $ext;
        if($name) {
            $filename = $name. '.' . $ext;
        }
        $uploadDir = 'assets/uploads/';
        $filepath = $uploadDir . $filename;
    
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
    
        file_put_contents($filepath, $dataImage);
    
        return $filepath; // hoặc return full URL nếu cần dùng bên ngoài
    }

    public function insertDoiTac() {
        $logos = [
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/vikolink.png",
            "link" => "http://vikolink.com.vn/home-page"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/royal.png",
            "link" => "https://www.royalselangor.com/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/aee.png",
            "link" => "https://www.aaltoee.com/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/ecam.png",
            "link" => "https://www.ecam-epmi.fr/anglais/ecam-epmi/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/asian-japan.png",
            "link" => "https://www.asean.or.jp/ja/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/aeu.png",
            "link" => "https://www.aeu.edu.my/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-quoc/coca-cola.png",
            "link" => "https://www.cocacolavietnam.com/"
          ],
          [
            "image" => "https://pti.edu.vn/uploads/doi tac/toan-cau/big-wave.png",
            "link" => "#"
          ]
        ];
     
        
        foreach($logos as $logo) {
            $image_url = $logo['image'];
            $link = $logo['link'];
            $type = 2;
            $filename = basename($image_url);

            $uploadDir = 'assets/images/doi-tac/';
            $filepath = $uploadDir . $filename;
        
            // Tạo thư mục nếu chưa tồn tại
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $dataImage = file_get_contents($image_url); // Dùng @ để tránh warning
            file_put_contents($filepath, $dataImage);
            $dataSave = [
                'image_url' => $filepath,
                'type_id' => $type,
                'link' => $link
            ];
           
            $check = $this->MPartner->getOneBy(['type_id' => $type, 'image_url' => $filepath]);
            if(!$check) {
                $this->MPartner->insert($dataSave);
            } else {
                $this->MPartner->update($check->id, $dataSave);
            }
        }
    }

    public function cloneGiangVien() {
        for($i = 1; $i <= 15; $i++) {
        $url = "https://pti.edu.vn/chuyen-gia?page=$i";
        $response = file_get_contents($url, true);
       
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($response, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Lấy danh sách tất cả các thẻ li.mb30
        $lis = $xpath->query('//li[contains(@class, "mb30")]');


        foreach ($lis as $li) {
            // Tạo XPath riêng cho mỗi <li>
            $liXpath = new DOMXPath($li->ownerDocument);

            $doc = new DOMDocument();
            $doc->loadHTML('<?xml encoding="UTF-8">' . $response);

            $xpath = new DOMXPath($doc);

            $name = $liXpath->query('.//h5[contains(@class, "m-card-3__name")]', $li)->item(0)?->textContent ?? '';
            $node = $liXpath->query('//div[contains(@class, "m-teacher__desc")]', $li)->item(0);
            $html = $node ? $liXpath->document->saveHTML($node) : '';
              
            // specialties (danh sách <li> trong mô tả chuyên môn)
            $specialty_nodes = $liXpath->query('.//h5[contains(text(), "Chuyên môn")]/following-sibling::div//li', $li);
            $specialties = [];
            foreach ($specialty_nodes as $sp) {
                $specialties[] = trim($sp->textContent);
            }

            $data = [
                'specialties' => $html,
            ];
            $checkPEx = $this->MExpert->getOneBy(['name' => trim($name)]);
            $this->MExpert->update($checkPEx->id, $data);
        }
    }
    }
}
