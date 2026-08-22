<?php

function pagination($base_url, $total_row, $per_page, $segment = '')
{
	$CI = &get_instance();
	$CI->load->library("pagination");
	$config['base_url'] = $base_url;
	$config['total_rows'] = $total_row;
	$config['per_page'] = $per_page;
	$config["use_page_numbers"] = true;
	$config["reuse_query_string"] = true;
	$config["prefix"] = '/';
	if ($segment > 0) {
		$config['uri_segment'] = 3;
	}
	// full tag pagination
	$config["full_tag_open"] = " <nav class='list_pagination' >
        <ul class='pagination'>";
	$config["full_tag_close"] = "</ul>
        </nav>";
	// first link
	$config["first_link"] = "&lt&lt";
	$config["first_tag_open"] = "<li class='page-item update-page-item'>";
	$config["first_tag_close"] = "</li>";

	// last link
	$config["last_link"] = "&gt&gt";
	$config["last_tag_open"] = "<li class='page-item update-page-item'>";
	$config["last_tag_close"] = "</li>";

	// next link
	$config["next_link"] = "&gt";
	$config["next_tag_open"] = "<li class='page-item update-page-item'>";
	$config["next_tag_close"] = "</li>";

	// pre link
	$config["prev_link"] = "&lt";
	$config["prev_tag_open"] = "<li class='page-item update-page-item'>";
	$config["prev_tag_close"] = "</li>";

	// cur link
	$config["cur_tag_open"] = "<li class='page-item update-page-item active_pagin'><a class='page-link'>";
	$config["cur_tag_close"] = "</a></li>";
	$config['num_links'] = 1;

	$config["num_tag_open"] = "<li class='page-item update-page-item'>";
	$config["num_tag_close"] = "</li>";
	$config['attributes'] = array('class' => 'page-link');
	$CI->pagination->initialize($config);
}

function admin()
{
	if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0) {
		return true;
	} else {
		if(base_url() == "http://localhost:8005/") {
			return true;
		}
		return false;
	}
}

function is_admin()
{
	if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0) {
		return true;
	} else {
		if(base_url() == "http://localhost:8005/") {
			return true;
		}
		return redirect("admin/login");
	}
}

function check_admin()
{
	if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0 && $_SESSION['admin']['type'] == 1) {
		return 1; // admin
	} else if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0 && $_SESSION['admin']['type'] == 2) {
		return 2; // biên tập
	} else if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] > 0 && $_SESSION['admin']['type'] == 3) {
		return 3; // cộng tác viên
	}
}

function chuyen_muc($cate = null)
{
	$CI = &get_instance();
	$CI->load->database();

	$CI->db->select('*');
	if ($cate != null) {
		$CI->db->where($cate);
	}
	$array = $CI->db->get('blog_categories')->result_array();
	return $array;
}


function tag($tag = null)
{
	$CI = &get_instance();
	$CI->load->database();

	$CI->db->select('*');
	if ($tag != null) {
		$CI->db->where($tag);
	}
	$array = $CI->db->get('tags')->result_array();
	return $array;
}
function replaceTitles($title)
{
	$title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');
	$title = remove_accent($title);
	$title = str_replace('/', '', $title);
	$title = preg_replace('/[^\00-\255]+/u', '', $title);

	if (preg_match("/[\p{Han}]/simu", $title)) {
		$title = str_replace(' ', '-', $title);
	} else {
		$arr_str = array("&lt;", "&gt;", "/", " / ", "\\", "&apos;", "&quot;", "&amp;", "lt;", "gt;", "apos;", "quot;", "amp;", "&lt", "&gt", "&apos", "&quot", "&amp", "&#34;", "&#39;", "&#38;", "&#60;", "&#62;");

		$title = str_replace($arr_str, " ", $title);
		$title = preg_replace('/\p{P}|\p{S}/u', ' ', $title);
		$title = preg_replace('/[^0-9a-zA-Z\s]+/', ' ', $title);

		//Remove double space
		$array = array(
			'    ' => ' ',
			'   ' => ' ',
			'  ' => ' ',
		);
		$title = trim(strtr($title, $array));
		$title = str_replace(" ", "-", $title);
		$title = urlencode($title);
		// remove cac ky tu dac biet sau khi urlencode
		$array_apter = array("%0D%0A", "%", "&");
		$title = str_replace($array_apter, "-", $title);
		$title = strtolower($title);
	}
	return $title;
}

function remove_accent($mystring)
{
	$marTViet = array(
		"à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ","ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ","ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ","ờ","ớ","ợ","ở","ỡ","ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ","ỳ","ý","ỵ","ỷ","ỹ","đ","À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă","Ằ","Ắ","Ặ","Ẳ","Ẵ","È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ","Ì","Í","Ị","Ỉ","Ĩ","Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ","Ờ","Ớ","Ợ","Ở","Ỡ","Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ","Ỳ","Ý","Ỵ","Ỷ","Ỹ","Đ","'"
	);

	$marKoDau = array(
		"a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","e","e","e","e","e","e","e","e","e","e","e","i","i","i","i","i","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","o","u","u","u","u","u","u","u","u","u","u","u","y","y","y","y","y","d","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","A","E","E","E","E","E","E","E","E","E","E","E","I","I","I","I","I","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","U","U","U","U","U","U","U","U","U","U","U","Y","Y","Y","Y","Y","D",""
	);

	return str_replace($marTViet, $marKoDau, $mystring);
}

function replace_date($time)
{
	$weekday = date("l", $time);
	$weekday = strtolower($weekday);
	switch ($weekday) {
		case 'monday':
			$weekday = 'Thứ hai';
			break;
		case 'tuesday':
			$weekday = 'Thứ ba';
			break;
		case 'wednesday':
			$weekday = 'Thứ tư';
			break;
		case 'thursday':
			$weekday = 'Thứ năm';
			break;
		case 'friday':
			$weekday = 'Thứ sáu';
			break;
		case 'saturday':
			$weekday = 'Thứ bảy';
			break;
		default:
			$weekday = 'Chủ nhật';
			break;
	}
	return $weekday . ', ' . date('d/m/Y', $time);
}

function createSlug($str) {
	$str = trim(mb_strtolower($str));
		$str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
		$str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
		$str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
		$str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
		$str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
		$str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
		$str = preg_replace('/(đ)/', 'd', $str);
		$str = preg_replace('/[^a-z0-9-\s]/', '', $str);
		$str = preg_replace('/([\s]+)/', '-', $str);
	return $str;
}


function full_path() {
    $s = &$_SERVER;
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    $uri = $protocol . '://' . $host . $s['REQUEST_URI'];
    $segments = explode('?', $uri, 2);
    $url = $segments[0];
    return $url;
}

function extractTagsFromContent($htmlContent) {
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent);
	libxml_clear_errors();

	$tags = [];
	$headings = ['h2'];
	$texts = [];
	foreach ($headings as $heading) {
		foreach ($dom->getElementsByTagName($heading) as $node) {
			$text = trim($node->textContent);
			if (!empty($text)) {
				$id = strtolower(str_replace(' ', '-', $text)); // Tạo ID duy nhất
				if(!in_array($texts, $texts)) {
					$tags[] = [
						'id' => $id,
						'title' => $text,
					];
					$texts[] = $text;
				}
			}
		}
	}

	return $tags;
}    

function slug($str) {
	$str = trim(mb_strtolower($str)); // Chuyển thành chữ thường

	// Danh sách ký tự có dấu và không dấu
	$unicode = [
		'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
		'd' => 'đ',
		'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
		'i' => 'í|ì|ỉ|ĩ|ị',
		'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
		'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
		'y' => 'ý|ỳ|ỷ|ỹ|ỵ'
	];

	foreach ($unicode as $nonAccent => $accented) {
		$str = preg_replace("/($accented)/i", $nonAccent, $str);
	}

	$str = preg_replace('/[^a-z0-9-\s]/', '', $str); // Xóa ký tự đặc biệt
	$str = preg_replace('/[\s]+/', '-', $str); // Thay khoảng trắng bằng dấu "-"
	$str = trim($str, '-'); // Xóa dấu "-" thừa ở đầu và cuối

	return $str;
}