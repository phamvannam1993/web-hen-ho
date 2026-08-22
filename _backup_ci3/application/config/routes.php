<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Home/index';

$route['api/export-db'] = 'Database/exportDB';

$route['admin/dashboard'] = 'Admin/dashboard';
$route['admin/login'] = 'Admin/login';
$route['admin/logout'] = 'Admin/logout';

$route['lich-khai-giang'] = 'Home/lichKhaiGiang';
$route['dao-tao-inhouse'] = 'Home/daoTaoInhouse';
$route['tin-tuc'] = 'Home/tintuc';
$route['lien-he'] = 'Home/lienHe';
$route['tam-nhin-va-su-menh'] = 'Home/tamNhinSumenh';
$route['gia-tri-cot-loi'] = 'Home/giaTriCotLoi';
$route['so-do-to-chuc'] = 'Home/soDoToChuc';
$route['giang-vien'] = 'Home/giangVien';


$route['admin/category/form'] = 'Category/form';
$route['admin/category/form/(:any)'] = 'Category/form/$1';
$route['admin/category/delete/(:any)'] = 'Category/delete/$1';
$route['admin/category'] = 'Category/index';

$route['admin/product/form'] = 'Product/form';
$route['admin/product/form/(:any)'] = 'Product/form/$1';
$route['admin/product/delete/(:any)'] = 'Product/delete/$1';
$route['admin/product'] = 'Product/index';

$route['admin/setting/form'] = 'Setting/form';
$route['admin/setting/form/(:any)'] = 'Setting/form/$1';
$route['admin/setting/delete/(:any)'] = 'Setting/delete/$1';
$route['admin/setting'] = 'Setting/form';

$route['admin/new/form'] = 'News/form';
$route['admin/new/form/(:any)'] = 'News/form/$1';
$route['admin/new/delete/(:any)'] = 'News/delete/$1';
$route['admin/new'] = 'News/index';

$route['admin/inhouse/form'] = 'Inhouse/form';
$route['admin/inhouse/form/(:any)'] = 'Inhouse/form/$1';
$route['admin/inhouse/delete/(:any)'] = 'Inhouse/delete/$1';
$route['admin/inhouse'] = 'Inhouse/index';

$route['admin/experts/form'] = 'Expert/form';
$route['admin/experts/form/(:any)'] = 'Expert/form/$1';
$route['admin/experts/delete/(:any)'] = 'Expert/delete/$1';
$route['admin/experts'] = 'Expert/index';

$route['admin/introduces/form'] = 'Introduce/form';
$route['admin/introduces/form/(:any)'] = 'Introduce/form/$1';
$route['admin/introduces/delete/(:any)'] = 'Introduce/delete/$1';
$route['admin/introduces'] = 'Introduce/index';

$route['admin/sliders/form'] = 'Slider/form';
$route['admin/sliders/form/(:any)'] = 'Slider/form/$1';
$route['admin/sliders/delete/(:any)'] = 'Slider/delete/$1';
$route['admin/sliders'] = 'Slider/index';

$route['admin/users/form'] = 'User/form';
$route['admin/users/form/(:any)'] = 'User/form/$1';
$route['admin/users/delete/(:any)'] = 'User/delete/$1';
$route['admin/users'] = 'User/index';

$route['admin/partners/form'] = 'Partner/form';
$route['admin/partners/form/(:any)'] = 'Partner/form/$1';
$route['admin/partners/delete/(:any)'] = 'Partner/delete/$1';
$route['admin/partners'] = 'Partner/index';

$route['admin/opening-schedules/form'] = 'OpeningSchedule/form';
$route['admin/opening-schedules/form/(:any)'] = 'OpeningSchedule/form/$1';
$route['admin/opening-schedules/delete/(:any)'] = 'OpeningSchedule/delete/$1';
$route['admin/opening-schedules'] = 'OpeningSchedule/index';

$route['admin/orders'] = 'Order/index';
$route['admin/orders/delete/(:any)'] = 'order/delete/$1';

$route['admin/contacts'] = 'Contact/index';
$route['admin/contacts/delete/(:any)'] = 'Contact/delete/$1';

$route['search/(:any)'] = 'Home/timKiem/$1';
$route['tin-tuc/(:any)'] = 'Home/chitietTinTuc/$1';
$route['khoa-hoc/(:any)'] = 'Home/chiTietKhoaHoc/$1';
$route['register/(:any)'] = 'Home/register/$1';
$route['(:any)'] = 'Home/chiTietLoaiKhoaHoc/$1';

$route['api/cloneGiangVien'] = 'API/cloneGiangVien';

// $route['api/clone-khoa-hoc'] = 'API/cloneKhoaHoc';