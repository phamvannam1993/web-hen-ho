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
$route['email/huy/(:any)']        = 'email/unsubscribe/$1';
$route['email/mo/(:num)']         = 'email/open/$1';
$route['email/bam/(:num)']        = 'email/click/$1';
$route['tai-khoan/email']         = 'account/email_prefs';
$route['tai-khoan/ai-thich-ban']  = 'account/who_liked_me';
$route['tai-khoan/ai-xem-ho-so'] = 'account/profile_viewers';
$route['tai-khoan/chuoi']        = 'account/streak';
$route['tai-khoan/goi-y']        = 'account/daily_history';
$route['ajax/goi-y-hom-nay']     = 'ajax/daily_match';

/* ---------------- API cho ứng dụng di động ----------------
   Xác thực bằng header: Authorization: Bearer <token> */
$route['api/auth/login']                  = 'api/auth/login';
$route['api/auth/logout']                 = 'api/auth/logout';
$route['api/auth/me']                     = 'api/auth/me';

$route['api/daily-match/today']           = 'api/daily_match/today';
$route['api/daily-match/history']         = 'api/daily_match/history';
$route['api/daily-match/(:num)/like']     = 'api/daily_match/like/$1';
$route['api/daily-match/(:num)/skip']     = 'api/daily_match/skip/$1';

$route['api/who-liked-me']                = 'api/engagement/who_liked_me';
$route['api/who-liked-me/count']          = 'api/engagement/who_liked_me_count';
$route['api/who-liked-me/(:num)/like']    = 'api/engagement/respond/$1/like';
$route['api/who-liked-me/(:num)/skip']    = 'api/engagement/respond/$1/skip';

$route['api/profile-viewers']             = 'api/engagement/viewers';
$route['api/profile-viewers/count']       = 'api/engagement/viewers_count';

$route['api/streak']                      = 'api/engagement/streak';
$route['api/streak/freeze']               = 'api/engagement/streak_freeze';

$route['api/auth/register']               = 'api/auth/register';
$route['api/auth/forgot']                 = 'api/auth/forgot';
$route['api/auth/resend-verify']          = 'api/auth/resend_verify';
$route['api/auth/password']               = 'api/auth/password';

$route['api/meta']                        = 'api/meta/index';
$route['api/home']                        = 'api/home/index';

$route['api/discover']                    = 'api/discover/index';
$route['api/discover/undo']               = 'api/discover/undo';
$route['api/discover/(:num)/like']        = 'api/discover/like/$1';
$route['api/discover/(:num)/pass']        = 'api/discover/pass/$1';

$route['api/members']                     = 'api/members/index';
$route['api/members/(:num)/like']         = 'api/members/like/$1';
$route['api/members/(:num)/contact']      = 'api/members/contact/$1';
$route['api/members/(:num)/comment']      = 'api/members/comment/$1';
$route['api/members/(:num)/block']        = 'api/members/block/$1';
$route['api/members/(:num)/unblock']      = 'api/members/unblock/$1';
$route['api/members/(:any)/preview'] = 'api/members/preview/$1';
$route['api/members/(:any)/purchase'] = 'api/members/purchase/$1';
$route['api/members/(:any)']              = 'api/members/show/$1';
$route['api/comments/(:num)/delete']      = 'api/members/delete_comment/$1';
$route['api/report']                      = 'api/members/report';

$route['api/account']                     = 'api/account/index';
$route['api/account/profile']             = 'api/account/profile';
$route['api/account/avatar']              = 'api/account/avatar';
$route['api/account/onboarding']          = 'api/account/onboarding';
$route['api/account/photos']              = 'api/account/photos';
$route['api/account/photos/(:num)/delete'] = 'api/account/delete_photo/$1';
$route['api/account/likes']               = 'api/account/likes';
$route['api/account/likes/seen']          = 'api/account/likes_seen';
$route['api/account/cover']               = 'api/account/cover';
$route['api/account/location']            = 'api/account/location';
$route['api/online-members']              = 'api/chat/online_members';
$route['api/account/notifications']       = 'api/account/notifications';
$route['api/account/notifications/read']  = 'api/account/notifications_read';
$route['api/account/wallet']              = 'api/account/wallet';
$route['api/account/email-prefs']         = 'api/account/email_prefs';

$route['api/conversations']               = 'api/chat/conversations';
$route['api/conversations/open/(:num)']   = 'api/chat/open/$1';
$route['api/conversations/(:num)']        = 'api/chat/messages/$1';
$route['api/messages']                    = 'api/chat/send';
$route['api/room']                        = 'api/chat/room';
$route['api/room/send']                   = 'api/chat/room_send';
$route['api/realtime']                    = 'api/chat/realtime';
$route['api/realtime/read']               = 'api/chat/realtime_read';
$route['xac-thuc']                = 'auth/verify';
$route['xac-thuc/gui-lai']        = 'auth/resend';
$route['xac-thuc-email/(:any)']  = 'auth/verify_link/$1';
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
$route['hen-ho/vi-tri']                   = 'dating/location';
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
$route['user/(:any)/preview'] = 'members/preview/$1';
$route['user/(:any)/purchase'] = 'members/purchase/$1';
$route['user/(:any)'] = 'members/profile/$1';
$route['profile/(:any)/preview'] = 'members/preview/$1';
$route['profile/(:any)/purchase'] = 'members/purchase/$1';
$route['profile/(:any)/lay-pass']             = 'members/get_pass/$1';
$route['profile/(:any)/mo-lien-he']           = 'members/reveal/$1';
$route['profile/(:any)/binh-luan']            = 'members/comment/$1';
$route['profile/(:any)/xoa-binh-luan/(:num)'] = 'members/delete_comment/$1/$2';
$route['profile/(:any)']           = 'members/profile/$1';

// Đường dẫn hồ sơ cũ: chuyển hướng 301 sang /profile/... để không mất link đã chia sẻ
$route['thanh-vien/(:any)']       = 'members/legacy_profile/$1';

$route['tai-khoan']               = 'account/index';
$route['tai-khoan/ho-so']         = 'account/profile';
$route['tai-khoan/kiem-tra-email'] = 'account/check_profile_email';
$route['tai-khoan/bat-dau']       = 'account/onboarding';
$route['tai-khoan/anh']           = 'account/photos';
$route['tai-khoan/tin-dang']      = 'account/posts';
$route['tai-khoan/sua-tin/(:num)']= 'account/edit_post/$1';
$route['tai-khoan/xoa-tin/(:num)']= 'account/delete_post/$1';
$route['tai-khoan/xoa-anh/(:num)']= 'account/delete_photo/$1';
$route['tai-khoan/quan-tam']      = 'account/likes';
$route['messenger']      = 'account/messages';
$route['messenger/(:num)'] = 'account/messages/$1';
$route['tai-khoan/thong-bao']     = 'account/notifications';
$route['tai-khoan/nap-xu']        = 'account/wallet';
$route['tai-khoan/doi-mat-khau']  = 'account/password';

$route['tin-tuc']                 = 'blog/index';
$route['tin-tuc/trang/(:num)']    = 'blog/index/$1';
$route['tin-tuc/(:any)']          = 'blog/detail/$1';
$route['lien-he'] = 'pages/contact_redirect';
$route['trang/lien-he'] = 'pages/contact_redirect';
$route['trang/contact-us'] = 'pages/contact_redirect';
$route['trang/(:any)']            = 'pages/view/$1';
$route['(bao-mat|an-toan|contact-us)'] = 'pages/policy/$1';

/* AJAX */
$route['clubs'] = 'groups/index';
$route['clubs/random-anonymous'] = 'groups/random_anonymous';
$route['clubs/([a-z0-9-]+)'] = 'groups/detail/$1';
$route['nhom-media/(:num)'] = 'groups/media/$1';
$route['ajax/nhom/(:num)/(:any)'] = 'groups/api/$1/$2';
$route['ajax/nhom-panel/(:num)'] = 'groups/panel/$1';
$route['internal/chat-groups/authorize'] = 'group_events/authorize';
$route['internal/chat-groups/flush'] = 'group_events/flush';
$route['internal/chat-groups/legacy'] = 'group_events/legacy';
$route['ajax/like']            = 'ajax/like';
$route['ajax/tra-loi-thich']   = 'ajax/respond_like';
$route['ajax/thong-bao']       = 'ajax/notifications';
$route['ajax/thong-bao/doc-het'] = 'ajax/notifications_read';
$route['ajax/unlock/(:num)']   = 'ajax/unlock_contact/$1';
$route['ajax/send-message']    = 'ajax/send_message';
$route['ajax/tin-nhan/(:num)'] = 'ajax/poll_messages/$1';
$route['ajax/hoi-thoai']       = 'ajax/conversations';
$route['ajax/phong-chat']      = 'ajax/room_messages';
$route['ajax/online-members']  = 'ajax/online_members';
$route['ajax/phong-chat/gui']  = 'ajax/room_send';
$route['ajax/mo-chat/(:num)']  = 'ajax/open_conversation/$1';
$route['ajax/goi-y-tai-khoan'] = 'ajax/account_suggestion';
$route['ajax/so-quan-tam'] = 'ajax/interest_count';
$route['ajax/quan-tam/da-xem'] = 'ajax/interest_seen';
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
$route['admin/(users|posts|photos|articles|orders|reports|codes)/trang/(:num)'] = 'admin/$1/index/$2';

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
