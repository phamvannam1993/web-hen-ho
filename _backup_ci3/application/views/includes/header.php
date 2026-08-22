<header class="header">
    <div class="topbar" style="background: #f6f6f6 !important;">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-7 hidden-sm hidden-xs">
                    <ul class="topbar_left hidden-sm hidden-xs">
                        <li>
                            <?=isset($setting->title_top) ? $setting->title_top : ' Chào mừng bạn đến với tổ chức giáo dục đào tạo PTI !'?>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6 col-md-5 col-sm-12 d-list col-xs-12 a-right topbar_right">
                    <div class="list-inline a-center f-right" style="font-size: 15px;">
                        <a href="#" class="hidden-xs hidden-sm hidden-md" style="color: #999;"><i class="fa fa-envelope" aria-hidden="true"></i></a>
                        <a href="#" class="hidden-xs hidden-sm hidden-md" style="margin: 0px 5px; color: #1877f2;"><i class="fa fa-facebook-square" aria-hidden="true"></i></a>
                        <a href="#" class="hidden-xs hidden-sm hidden-md" style="color: #f30;"><i class="fa fa-youtube-square" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mid-header wid_100 f-left" style="background: #fff !important; padding: 10px 0px;">
        <div class="container">
            <div class="row">
                <div class="content_header">
                    <div class="header-main">
                        <div class="menu-bar-h nav-mobile-button hidden-md hidden-lg">
                            <a href="javascript:void(0)" style="color: #4b55b7;"><i class="fa fa-bars" aria-hidden="true"></i></a>
                        </div>
                        <div class="col-lg-5 col-md-5">
                            <div class="logo">
                                <a href="/" class="logo-wrapper">
                                    <img src="/assets/images/logo-khoahocpti.png" alt="logo" style="max-height: 75px;" />
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-xs-12 col-sm-12">
                            <div class="header-left">
                                <div class="header_search">
                                    <div class="input-group search-bar">
                                        <input type="text" name="query" value="" id="Keyword" autocomplete="off" placeholder="Tìm kiếm khóa học..." class="input-group-field auto-search" />
                                        <span class="input-group-btn">
                                            <button type="submit" class="btn icon-fallback-text">
                                                <span class="fa fa-search"></span>
                                            </button>
                                        </span>
                                    </div>
                                    <div id="search_suggestion">
                                        <div id="search_top">
                                            <div id="product_results"></div>
                                            <div id="article_results"></div>
                                        </div>
                                        <div id="search_bottom">
                                            <a class="show_more" href="#">Hiển thị tất cả kết quả cho "<span></span>"</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-xs-12 no-padding-left">
                            <div class="header-right">
                                <div class="hotline_dathang hidden-sm hidden-xs f-right">
                                    <div class="content_hotline" style="float: right;">
                                        <a href="tel:<?=isset($setting->phone_number) ? $setting->phone_number : '0984.672.297'?>"> <?=isset($setting->phone_number) ? $setting->phone_number : '0984.672.297'?></a>
                                        <span>Hotline tư vấn</span>
                                    </div>
                                    <div class="icon_hotline" style="float: right;">
                                        <a href="tel:<?=isset($setting->phone_number) ? $setting->phone_number : '0984.672.297'?>" style="color: #f30;"><i class="fa fa-phone" aria-hidden="true"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="wrap_main hidden-xs hidden-sm">
        <div class="container">
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 col-mega hidden-sm hidden-xs">
                    <div class="menu_mega">
                        <div class="title_menu">
                            <span class="title uppercase-text" style="margin-left: 25px;font-weight: bold;">Khóa học</span>
                            <span class="nav_button"><span><i class="fa fa-bars" aria-hidden="true"></i></span></span>
                        </div>
                        <div class="list_menu_header menu_all_site col-lg-3 col-md-3">
                            <ul class="ul_menu site-nav-vetical">
                                <?php foreach($categories as $item) { ?>
                                    <li class="nav_item lv1 li_check">
                                        <a href="/<?=$item['slug']?>" title="<?=$item['name']?>"><?=$item['name']?> </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
                    <div class="bg-header-nav hidden-xs hidden-sm">
                        <div>
                            <div class="row row-noGutter-2">
                                <nav class="header-nav">
                                    <ul class="item_big">
                                        <li class="nav-item">
                                            <a class="a-img" href="/lich-khai-giang">
                                                <span class="uppercase-text">Lịch khai giảng</span>
                                                <span class="label_">
                                                    <i class="label"></i>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="a-img" href="/">
                                                <span class="uppercase-text">Giới thiệu</span>
                                                <i class="fa fa-caret-down"></i>
                                                <span class="label_">
                                                    <i class="label"></i>
                                                </span>
                                            </a>
                                            <ul class="item_small">
                                                <li>
                                                    <a href="/tam-nhin-va-su-menh" title="Tầm nhìn và sứ mệnh">Tầm nhìn và sứ mệnh </a>
                                                </li>
                                                <li>
                                                    <a href="/gia-tri-cot-loi" title="Giá trị cốt lõi">Giá trị cốt lõi </a>
                                                </li>
                                                <li>
                                                    <a href="/so-do-to-chuc" title="Sơ đồ tổ chức">Sơ đồ tổ chức </a>
                                                </li>
                                                <li>
                                                    <a href="/giang-vien" title="Giảng viên">Giảng viên </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="nav-item">
                                            <a class="a-img" href="#">
                                                <span class="uppercase-text">Khóa học online</span><i class="fa fa-caret-down"></i>
                                                <span class="label_">
                                                    <i class="label hot">hot</i>
                                                </span>
                                            </a>
                                            <ul class="item_small">
                                                <li>
                                                    <a href="/" title="" class="uppercase-text">Đào tạo online PEO </a>
                                                </li>
                                                <li>
                                                    <a href="#" title="" class="uppercase-text">Đào tạo online UNIVN </a>
                                                </li>
                                                <li>
                                                    <a href="#" title="" class="uppercase-text">Đào tạo CEOONLINE </a>
                                                </li>
                                            </ul>
                                        </li>
                                        <li class="nav-item">
                                            <a class="a-img" href="/dao-tao-inhouse">
                                                <span class="uppercase-text">Đào tạo inhouse</span>
                                                <span class="label_">
                                                    <i class="label"></i>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="a-img" href="/tin-tuc">
                                                <span class="uppercase-text">Tin tức</span>
                                                <span class="label_">
                                                    <i class="label new">mới</i>
                                                </span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="a-img" href="/lien-he">
                                                <span class="uppercase-text">Liên hệ</span>
                                                <span class="label_">
                                                    <i class="label"></i>
                                                </span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<style>
     .uppercase-text {
        text-transform: uppercase;
    }
</style>