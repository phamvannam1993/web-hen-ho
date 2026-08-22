<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'home';
$route['404_override'] = 'home/not_found';
$route['translate_uri_dashes'] = FALSE;

/* ------------------------------------------------------------------
 | FRONTEND
 * ---------------------------------------------------------------- */
$route['dang-ky']                 = 'auth/register';
$route['dang-nhap']               = 'auth/login';
$route['dang-xuat']               = 'auth/logout';
$route['lay-pass/(:any)']         = 'auth/get_code/$1';
$route['quen-mat-khau']           = 'auth/forgot';
$route['dat-lai-mat-khau/(:any)'] = 'auth/reset/$1';

$route['tin-dang']                       = 'posts/index';
$route['tin-dang/trang/(:num)']          = 'posts/index/$1';
$route['danh-muc/(:any)/trang/(:num)']   = 'posts/category/$1/$2';
$route['danh-muc/(:any)']                = 'posts/category/$1';
$route['khu-vuc/(:any)/trang/(:num)']    = 'posts/province/$1/$2';
$route['khu-vuc/(:any)']                 = 'posts/province/$1';
$route['tim-kiem']                       = 'posts/search';
$route['tin-dang/lay-pass/(:num)']       = 'posts/get_pass/$1';
$route['tin-dang/mo-lien-he/(:num)']     = 'posts/reveal/$1';
$route['tin/(:any)']                     = 'posts/detail/$1';
$route['dang-tin']                       = 'account/create_post';

$route['kham-pha']                = 'discover/index';
$route['kham-pha/thich/(:num)']   = 'discover/like/$1';
$route['kham-pha/bo-qua/(:num)']  = 'discover/pass/$1';

$route['thanh-vien']              = 'members/index';
$route['thanh-vien/trang/(:num)'] = 'members/index/$1';
$route['thanh-vien/(:any)/lay-pass']             = 'members/get_pass/$1';
$route['thanh-vien/(:any)/mo-lien-he']           = 'members/reveal/$1';
$route['thanh-vien/(:any)/binh-luan']            = 'members/comment/$1';
$route['thanh-vien/(:any)/xoa-binh-luan/(:num)'] = 'members/delete_comment/$1/$2';
$route['thanh-vien/(:any)']       = 'members/profile/$1';

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
$route['admin']                 = 'admin/dashboard/index';
$route['admin/dang-nhap']       = 'admin/auth/login';
$route['admin/dang-xuat']       = 'admin/auth/logout';
