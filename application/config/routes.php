<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'home';
$route['404_override'] = 'home/not_found';
$route['translate_uri_dashes'] = FALSE;

/* ------------------------------------------------------------------
 | FRONTEND
 * ---------------------------------------------------------------- */
$route['robots.txt']              = 'robots/index';
$route['dang-ky']                 = 'auth/register';
$route['dang-nhap']               = 'auth/login';
$route['dang-xuat']               = 'auth/logout';
$route['quen-mat-khau']           = 'auth/forgot';
$route['dat-lai-mat-khau/(:any)'] = 'auth/reset/$1';

$route['tin-dang']                       = 'posts/index';
$route['tin-dang/trang/(:num)']          = 'posts/index/$1';
$route['danh-muc/(:any)/trang/(:num)']   = 'posts/category/$1/$2';
$route['danh-muc/(:any)']                = 'posts/category/$1';
$route['khu-vuc']                        = 'areas/index';
// Đường dẫn khu vực cũ /khu-vuc/{tỉnh}: chuyển hướng 301 sang /{tỉnh}
$route['khu-vuc/(:any)/trang/(:num)']    = 'areas/legacy_province/$1/$2';
$route['khu-vuc/(:any)']                 = 'areas/legacy_province/$1';
$route['tim-kiem']                       = 'members/search';
$route['tim-kiem/trang/(:num)']          = 'members/search/$1';
$route['tin-dang/lay-pass/(:num)']       = 'posts/get_pass/$1';
$route['tin-dang/mo-lien-he/(:num)']     = 'posts/reveal/$1';
$route['tin/(:any)']                     = 'posts/detail/$1';
$route['dang-tin']                       = 'account/create_post';

$route['tam-su']                                    = 'confide/index';
$route['tam-su/trang/(:num)']                       = 'confide/index//$1';
$route['tam-su/(nam|nu|gay|les)']                   = 'confide/index/$1';
$route['tam-su/(nam|nu|gay|les)/trang/(:num)']      = 'confide/index/$1/$2';

$route['hen-ho']                          = 'dating/index';
$route['hen-ho/trang/(:num)']             = 'dating/index//$1';
$route['hen-ho/(nam|nu|gay|les)']         = 'dating/index/$1';
$route['hen-ho/(nam|nu|gay|les)/trang/(:num)'] = 'dating/index/$1/$2';

$route['swipe-match']                = 'discover/index';
$route['swipe-match/trang/(:num)']   = 'discover/index/$1';
$route['swipe-match/thich/(:num)']   = 'discover/like/$1';
$route['swipe-match/bo-qua/(:num)']  = 'discover/pass/$1';
$route['swipe-match/xem-lai']        = 'discover/undo';

$route['thanh-vien']              = 'members/index';
$route['thanh-vien/trang/(:num)'] = 'members/index/$1';
$route['profile/(:any)/lay-pass']             = 'members/get_pass/$1';
$route['profile/(:any)/mo-lien-he']           = 'members/reveal/$1';
$route['profile/(:any)/binh-luan']            = 'members/comment/$1';
$route['profile/(:any)/xoa-binh-luan/(:num)'] = 'members/delete_comment/$1/$2';
$route['profile/(:any)']           = 'members/profile/$1';

// Đường dẫn hồ sơ cũ: chuyển hướng 301 sang /profile/... để không mất link đã chia sẻ
$route['thanh-vien/(:any)']       = 'members/legacy_profile/$1';

$route['tai-khoan']               = 'account/index';
$route['tai-khoan/ho-so']         = 'account/profile';
$route['tai-khoan/anh']           = 'account/photos';
$route['tai-khoan/tin-dang']      = 'account/posts';
$route['tai-khoan/sua-tin/(:num)']= 'account/edit_post/$1';
$route['tai-khoan/xoa-tin/(:num)']= 'account/delete_post/$1';
$route['tai-khoan/xoa-anh/(:num)']= 'account/delete_photo/$1';
$route['tai-khoan/quan-tam']      = 'account/likes';
$route['tai-khoan/tin-nhan']      = 'account/messages';
$route['tai-khoan/tin-nhan/(:num)'] = 'account/messages/$1';
$route['tai-khoan/thong-bao']     = 'account/notifications';
$route['tai-khoan/nap-xu']        = 'account/wallet';
$route['tai-khoan/doi-mat-khau']  = 'account/password';

$route['tin-tuc']                 = 'blog/index';
$route['tin-tuc/trang/(:num)']    = 'blog/index/$1';
$route['tin-tuc/(:any)']          = 'blog/detail/$1';
$route['trang/(:any)']            = 'pages/view/$1';

/* AJAX */
$route['ajax/like']            = 'ajax/like';
$route['ajax/unlock/(:num)']   = 'ajax/unlock_contact/$1';
$route['ajax/send-message']    = 'ajax/send_message';
$route['ajax/tin-nhan/(:num)'] = 'ajax/poll_messages/$1';
$route['ajax/hoi-thoai']       = 'ajax/conversations';
$route['ajax/phong-chat']      = 'ajax/room_messages';
$route['ajax/phong-chat/gui']  = 'ajax/room_send';
$route['ajax/mo-chat/(:num)']  = 'ajax/open_conversation/$1';
$route['ajax/report']          = 'ajax/report';

/* ------------------------------------------------------------------
 | ADMIN
 * ---------------------------------------------------------------- */
// Sitemap sinh động: phải đứng trước quy tắc bắt-tất-cả tỉnh thành ở cuối tệp
$route['sitemap\.xml']          = 'sitemap/index';
$route['sitemap-pages\.xml']    = 'sitemap/pages';
$route['sitemap-dating\.xml']   = 'sitemap/dating';
$route['sitemap-tamsu\.xml']    = 'sitemap/tamsu';
$route['sitemap-khuvuc\.xml']   = 'sitemap/khuvuc';
$route['sitemap-posts\.xml']    = 'sitemap/posts';

// Phân trang khu quản trị: /admin/{muc}/trang/{n}
$route['admin/(users|posts|articles|orders|reports|codes)/trang/(:num)'] = 'admin/$1/index/$2';

$route['admin']                 = 'admin/dashboard/index';
$route['admin/dang-nhap']       = 'admin/auth/login';
$route['admin/dang-xuat']       = 'admin/auth/logout';

/* ------------------------------------------------------------------
 | TỈNH/THÀNH Ở GỐC — /ha-noi, /tp-ho-chi-minh ...
 |
 | Phải nằm CUỐI CÙNG: CodeIgniter so khớp theo thứ tự khai báo, nên mọi
 | đường dẫn phía trên vẫn được ưu tiên. Chỉ nhận một đoạn gồm chữ thường,
 | số và gạch ngang; slug không phải tỉnh sẽ ra trang 404.
 * ---------------------------------------------------------------- */
$route['([a-z0-9-]+)/trang/(:num)'] = 'areas/province/$1/$2';
$route['([a-z0-9-]+)']              = 'areas/province/$1';
