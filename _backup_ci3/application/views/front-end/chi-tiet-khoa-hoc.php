<!DOCTYPE html>
<html lang="vi">
    <?php $this->load->view('includes/head')?>
    <link href="/assets/css/dao-tao-inhouse.css" rel="stylesheet" type="text/css" />
    <body>
    <?php $this->load->view('includes/mySidenav')?>
      <!-- End -->
      <?php $this->load->view('includes/header')?>
      <div id="menu-overlay" class=""></div>
    <style>
        .info_teacher {
            display: inline-block;
        }
    </style>
      <section class="bread-crumb">
        <span class="crumb-border"></span>
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="breadcrumb">
                    <li class="home">
                        <a href="/"><span>Trang chủ</span></a>                       
                        <span class="mr_lr">&nbsp;<i class="fa fa-angle-right"></i>&nbsp;</span>
                    </li>
                    <li><a href="/tin-tuc"><span>Tin tức</span></a>
                    <span class="mr_lr">&nbsp;<i class="fa fa-angle-right"></i>&nbsp;</span>
                    </li>
                    <li><strong><span><?=$product->name?></span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="page_contact">
        <div class="container">
            <div class="row">
                <div class="details-product">
                    <div class="col-xs-12 col-sm-12 col-md-12 category-products" style="margin-bottom: 30px;">
                        <div class="box-heading relative">
                            <h1 class="title-head margin-top-0" style="padding-bottom: 20px;font-size: 25px;"><?=$product->name?></h1>
                            <i class="fa fa-book" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
                        <div class="rows">
                            <div class="product-detail-left product-images col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="row">
                                    <div class="col_large_default large-image">
                                        <div class="zoomWrapper">
                                            <img id="img_01" class="img-responsive" alt="<?=$product->name?>" src="/<?=$product->image_url?>" style="position: absolute;">
                                        </div>     
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab_h">
                            <div class="col-xs-12 col-lg-12 col-sm-12 col-md-12 no-padding">
                                <!-- Nav tabs -->
                                <div class="product-tab e-tabs">
                                    <ul class="tabs tabs-title clearfix">
                                        <li class="tab-link current" data-tab="tab-1">
                                            <h3><span>Giới thiệu</span></h3>
                                        </li>
                                        <li class="tab-link" data-tab="tab-2">
                                            <h3><span>Nội dung</span></h3>
                                        </li>
                                        <li class="tab-link" data-tab="tab-3">
                                            <h3><span>Lịch khai giảng</span></h3>
                                        </li>
                                        <li class="tab-link" data-tab="tab-4">
                                            <h3><span>Chuyên gia</span></h3>
                                        </li>
                                    </ul>
                                    <div id="tab-1" class="tab-content">
                                        <div class="rte">
                                            <?=$product->introduce?>                                
                                        </div>
                                    </div>
                                    <div id="tab-2" class="tab-content" style="display: none;">
                                        <div class="rte" style="padding: 30px 10px 20px 10px;">
                                            <?=$product->content?> 
                                        </div>
                                    </div>
                                    <div id="tab-3" class="tab-content" style="display: none;">
                                        <div class="rte opening_full" style="padding: 30px 10px 20px 10px;">
                                            <div class="rte opening_full" style="padding: 30px 10px 20px 10px;">
                                                <p></p>
                                                <p></p>
                                                <p></p>
                                                <?php if(!empty($openingScheduleHN)) { ?>
                                                <p style="text-align: left;"><b>Lịch khai giảng Hà Nội :</b></p>
                                                <table class="" border="1" cellpadding="1" cellspacing="0" style="width: 727.594px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255); border: rgb(230, 230, 230);">
                                                    <thead>
                                                        <tr style="padding: 15px 20px; color: rgb(255, 182, 6); text-transform: uppercase; font-weight: bold;">
                                                            <td style="text-align: center; width: 211.706px;"><span style="color: rgb(13, 72, 127);">Khóa học</span></td>
                                                            <td style="text-align: center; width: 100.013px;"><span style="color: rgb(13, 72, 127);">THỜI LƯỢNG</span></td>
                                                            <td style="text-align: center; width: 107.644px;"><span style="color: rgb(13, 72, 127);">NGÀY KHAI GIẢNG</span></td>
                                                            <td style="text-align: center; width: 107.812px;"><span style="color: rgb(13, 72, 127);">NGÀY/THỨ</span></td>
                                                            <td style="text-align: center; width: 120.656px;"><span style="color: rgb(13, 72, 127);">Thời gian</span></td>
                                                            <td style="text-align: center; width: 78.5625px;"><span style="color: rgb(13, 72, 127);">Địa điểm học</span></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($openingScheduleHN as $item) { ?>
                                                        <tr class="row_tbl No_remove">
                                                            <td style="text-align: center;"><?=$product->name?></td>
                                                            <td style="text-align: center;"><?=$item['duration']?></td>
                                                            <td style="text-align: center;"><?=date('d/m/Y', strtotime($item['day']))?></td>
                                                            <td style="text-align: center;"><?=$item['day_week']?></td>
                                                            <td style="text-align: center;">
                                                            <p style="margin-bottom: 0px; text-wrap-mode: nowrap;">
                                                                <?php if($item['morning_time']) { ?>
                                                                    Sáng: <?=$item['morning_time']?><br>
                                                                <?php } ?>
                                                                <?php if($item['afternoon_time']) { ?>
                                                                    Chiều: <?=$item['afternoon_time']?><br>
                                                                <?php } ?>
                                                                <?php if($item['evening_time']) { ?>
                                                                    Tối: <?=$item['evening_time']?><br>
                                                                <?php } ?>
                                                            </p>
                                                            </td>
                                                            <td class="maps" style="text-align: center;"><br></td>
                                                        </tr>
                                                        <tr class="wrapper">
                                                            <td colspan="7" style="border: unset;">
                                                            <div class="show-maps map-4" data-id="4" style="color: rgb(223, 6, 6); text-align: center; padding: 10px;"><?=$item['address']?></div>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                                <br>
                                                <p></p>
                                                <?php } ?>
                                                <?php if(!empty($openingScheduleHCM)) { ?>
                                                <p style="text-align: left;"><b>Lịch khai giảng Hồ Chí Minh :</b></p>
                                                <table class="" border="1" cellpadding="1" cellspacing="0" style="width: 727.594px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255); border: rgb(230, 230, 230);">
                                                    <thead>
                                                        <tr style="padding: 15px 20px; color: rgb(255, 182, 6); text-transform: uppercase; font-weight: bold;">
                                                            <td style="text-align: center; width: 211.706px;"><span style="color: rgb(13, 72, 127);">Khóa học</span></td>
                                                            <td style="text-align: center; width: 100.013px;"><span style="color: rgb(13, 72, 127);">THỜI LƯỢNG</span></td>
                                                            <td style="text-align: center; width: 107.644px;"><span style="color: rgb(13, 72, 127);">NGÀY KHAI GIẢNG</span></td>
                                                            <td style="text-align: center; width: 107.812px;"><span style="color: rgb(13, 72, 127);">NGÀY/THỨ</span></td>
                                                            <td style="text-align: center; width: 120.656px;"><span style="color: rgb(13, 72, 127);">Thời gian</span></td>
                                                            <td style="text-align: center; width: 78.5625px;"><span style="color: rgb(13, 72, 127);">Địa điểm học</span></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($openingScheduleHCM as $item) { ?>
                                                        <tr class="row_tbl No_remove">
                                                            <td style="text-align: center;"><?=$product->name?></td>
                                                            <td style="text-align: center;"><?=$item['duration']?></td>
                                                            <td style="text-align: center;"><?=strtotime($item['day'])?></td>
                                                            <td style="text-align: center;"><?=$item['day_week']?></td>
                                                            <td style="text-align: center;">
                                                            <p style="margin-bottom: 0px; text-wrap-mode: nowrap;">
                                                                <?php if($item['morning_time']) { ?>
                                                                    Sáng: <?=$item['morning_time']?><br>
                                                                <?php } ?>
                                                                <?php if($item['afternoon_time']) { ?>
                                                                    Chiều: <?=$item['afternoon_time']?><br>
                                                                <?php } ?>
                                                                <?php if($item['evening_time']) { ?>
                                                                    Tối: <?=$item['evening_time']?><br>
                                                                <?php } ?>
                                                            </p>
                                                            </td>
                                                            <td class="maps" style="text-align: center;"><br></td>
                                                        </tr>
                                                        <tr class="wrapper">
                                                            <td colspan="7" style="border: unset;">
                                                            <div class="show-maps map-4" data-id="4" style="color: rgb(223, 6, 6); text-align: center; padding: 10px;"><?=$item['address']?></div>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                                <br>
                                                <p></p>
                                                <?php } ?>
                                                <section class="m-training-detail" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;">
                                                    <div class="tab-content mb30" style="margin-bottom: 30px;">
                                                        <div class="tab-pane fade active show" id="m-training-detail-tab-3" role="tabpanel" style="border: 1px solid rgb(230, 230, 230);"></div>
                                                    </div>
                                                </section>
                                                <br>
                                                <p></p>
                                                <p></p>
                                            </div>                       
                                        </div>
                                    </div>
                                    <div id="tab-4" class="tab-content" style="display: none;">
                                        <div class="rte">
                                            <?php if(!empty($experts)) { foreach($experts as $item) {  ?> 
                                                <div class="info_teacher" style="width: 150px; padding: 5px; margin: 10px; font-family: Arial, Helvetica, sans-serif;">
                                                    <font color="#007bff"><span style="border-width: 1px; border-color: rgba(51, 51, 51, 0.2); border-image: initial; margin: 10px auto;">
                                                    <img src="/<?= $item->image_url?>" class="w-100" alt="" style="border-style: none; width: 138px; display: block; height: 140px; border-radius: 50%;"></span></font>
                                                    <span style="display: block; margin-top: 5px; text-transform: uppercase; white-space: nowrap; text-align: center; font-size: 12px; margin-bottom: 0.25rem !important;"><b>
                                                        <?= $item->name ?>
                                                    </b></span>
                                                    <div class="position text-center"><?= $item->position?></div>
                                                </div>
                                             <?php }} ?>                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                        <div class="right_module margin-bottom-50">
                            <div class="module_service_details">
                                <div class="wrap_module_service module_best_sale_product">
                                    <div class="title_module_">
                                        <h2 class="title">Thông tin khóa khọc</h2>
                                    </div>
                                    <div class="item_service">
                                        <div class="wrap_item_">
                                            <div class="content_service">
                                                <p style="margin-bottom: 15px;font-size: 16px;">Học phí : <span style="color: #b71d21;"><?=number_format($product->price_old)?> ₫</span></p>
                                                <p style="font-size: 16px;">Phí ưu đãi : <span style="color: #002cff"><?=number_format($product->price)?> ₫</span></p>
                                            </div>
                                            <ul style="list-style: circle;padding-left: 15px;margin-bottom: 15px;">
                                                <li>Học viên đăng ký trước khai giảng 15 ngày</li>
                                                <li>Đăng ký từ 3 học viên</li>
                                                <li>Là khách hàng thân thiết của PTI</li>
                                            </ul>
                                            <div style="font-size: 15px;font-weight: 500;color: red;margin: 5px 0 2px 0;">Email: <?=$setting->email?></div>
                                            <div style="font-size: 15px;font-weight: 500;color: red;margin: 2px 0;">Hotline: <?=$setting->phone_number?>7</div>
                                            <a class="as-course__btn eff-btn-filter" href="/register/<?=$product->id?>">Đăng ký khoá học</a>
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class=" module_best_sale_product module_best_sale_product_1">
                                    <div class="title_module_">
                                        <h2 class="title"><a href="khoa-hoc-noi-bat" title="Có thể bạn thích">Khóa học nổi bật</a></h2>
                                    </div>
                                    <div class="sale_off_today">
                                        <div class="not-dqowl wrp_list_product">
                                        <?php if(!empty($course_populars)) { foreach($course_populars as $item) {?>
                                            <div class="item_small">
                                                <div class="product-mini-item clearfix on-sale">
                                                    <a href="/khoa-hoc/<?=$item['slug']?>" class="product-img">
                                                    <img class="" src="/<?=$item['image_url']?>" data-src="/<?=$item['image_url']?>" alt="<?=$item['name']?>">
                                                    </a>
                                                    <div class="product-info">
                                                    <h3><a href="/khoa-hoc/<?=$item['slug']?>" title="<?=$item['name']?>" class="product-name text3line"><?=$item['name']?></a></h3>
                                                    <div class="price-box">
                                                        <span class="price"><span class="price product-price"><?=number_format($item['price'])?> ₫</span> </span>
                                                    </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php }} ?>                                   
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

      <script>
         var $root = $('html, body');
         $('.scroll_menu a').click(function() {
             var location = $($.attr(this, 'href')).offset().top - 150;
             $root.animate({
                 scrollTop: location
             }, 500);
             return false;
         });
         
         $(window).load(function() {
         
             $(this).scroll(function() {
                 var height = $(window).scrollTop();
                 if (height >= 400) {
                     $('.scroll_menu').addClass('visible');
                 } else {
                     $('.scroll_menu').removeClass('visible');
                 }
             });
         });
      </script>
      <?php $this->load->view('includes/footer')?>
      <script src="/assets/js/owl.carousel.min.js" type="text/javascript"></script>
      <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
      <div class='jas-sale-pop flex pf middle-xs'></div>
      <style>
         .owl-dot span{width: 0 !important;}
      </style>
      <script src="/assets/js/jquery.js"></script>
      <script src="/assets/js/yii.js"></script>
      <script src="/assets/js/yii.activeForm.js"></script>
      <script>jQuery(function ($) {
         jQuery('#mc-embedded-subscribe-form').yiiActiveForm([], []);
         });
         $(".tab-link").click(function() {
            $(".tab-link").removeClass('current')
            $(this).addClass('current')
            var id = $(this).attr('data-tab')
            $(".tab-content").hide()
            $('#'+id).show()
         })
      </script>
   </body>
</html>