<!DOCTYPE html>
<html lang="vi">
    <?php $this->load->view('includes/head')?>
    <link href="/assets/css/dao-tao-inhouse.css" rel="stylesheet" type="text/css" />
    <body>
    <?php $this->load->view('includes/mySidenav')?>
      <!-- End -->
      <?php $this->load->view('includes/header')?>
      <div id="menu-overlay" class=""></div>
  
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
                    <li><strong><span>Đăng ký khóa học</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="page_contact">
        <div class="container">
            <div class="row">
                <div class="details-product">
                    <div class="col-lg-8 col-md-12 col-sm-12 col-xs-12">
                        <div class="rows">
                            <div class="product-detail-left product-images col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="row">
                                <h5 class="m-registration__title">Đăng ký khoá học</h5>
                                <div class="m-registration__text" style="margin-bottom: 30px;">Những thông tin Quý vị cung cấp dưới đây sẽ được PTI sử dụng để cấp CHỨNG NHẬN và viết HÓA ĐƠN. Chính vì vậy, kính đề nghị Quý vị cung cấp thông tin một cách đầy đủ, chính xác.
                                    <br>
                                    <br>Thông tin liên quan đến việc đăng ký - Phí ưu đãi sẽ được áp dụng cho học viên chuyển phí trước ngày khai giảng ít nhất 15 ngày
                                    <br>
                                    <br>Học viên sẽ không được hủy lớp hay hoàn phí sau khi đã đóng phí. Tuy nhiên, nếu học viên có yêu cầu dời lớp, vui lòng thông báo cho PTI trước ngày khai giảng ít nhất 3 ngày.
                                    <br>
                                    <br>Học viên sẽ không được cấp chứng nhận tốt nghiệp nếu vắng mặt hơn 30% tổng số buổi học của toàn bộ khóa học
                                </div>
                                <form id="contact" action="" method="post">
                                    <?php if(isset($success)) { ?>
                                    <div class="alert alert-success">
                                        <p>Bạn đã đăng ký thành công</p>
                                    </div>
                                    <?php } ?>
                                    <div class="form-signup clearfix">
                                        <div class="row group_contact">
                                            <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                            <input type="hidden" required="" value="1" name="Order[course]">
                                            <input placeholder="Họ và tên" type="text" class="form-control  form-control-lg" required="" value="" name="order[fullname]">
                                            </fieldset>
                                            <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">  
                                            <input placeholder="Số điện thoại" type="text" class="key_phone form-control form-control-comment form-control-lg" name="order[phone_number]" required="">
                                            </fieldset>
                                            <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                            <select class="field__input field__input--select form-control filter-dropdown" name="order[address_id]" id="billingProvince" required="">
                                                <option value="">--- Địa điểm ---</option>
                                                <option value="1">Hà Nội</option>
                                                <option value="2">TP. HCM</option>
                                            </select>
                                            </fieldset>
                                            <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                            <input placeholder="Email" type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" required="" id="email1" class="form-control form-control-lg" value="" name="order[email]">
                                            </fieldset>
                                            <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                            <input class="m-registration__input m-registration__input--disabled" name="name_course" style="font-weight: bold;" type="text" value="<?=$product['name']?>" required="" readonly="">
                                            </fieldset>
                                            <fieldset class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                            <textarea placeholder="Thêm thông tin" name="order[note]" id="comment" class="form-control content-area form-control-lg" rows="5"></textarea>
                                            </fieldset>
                                        </div>
                                    </div>
                                    <div class="m-registration__group">
                                        <label class="m-registration__label"></label>
                                        <div class="m-registration__btnwrap">
                                            <button class="as-course__btn eff-btn-filter" name="btn_course" type="submit">Đăng ký khoá học</button>
                                        </div>
                                    </div>
                                </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 hidden-xs hidden-sm hidden-md">
                        <div class="right_module margin-bottom-50">
                            <div class="module_service_details">
                                <div class="wrap_module_service module_best_sale_product">
                                <div class="title_module_">
                                    <h2 class="title"><?=$product['name']?></h2>
                                </div>
                                <div class="item_service" style="padding-bottom: 20px;">
                                    <div class="wrap_item_">
                                        <div class="content_service">
                                            <p style="margin-bottom: 15px;font-size: 16px;">Học phí : <span style="color: #b71d21;"><?=number_format($product['price_old'])?> ₫</span></p>
                                            <p style="font-size: 16px;">Phí ưu đãi : <span style="color: #002cff"><?=number_format($product['price'])?> ₫</span></p>
                                        </div>
                                        <ul style="list-style: circle;padding-left: 15px;margin-bottom: 15px;">
                                            <li>Học viên đăng ký trước khai giảng 15 ngày</li>
                                            <li>Đăng ký từ 3 học viên</li>
                                            <li>Là khách hàng thân thiết của PTI</li>
                                        </ul>
                                        <div style="font-size: 15px;font-weight: 500;color: red;margin: 5px 0 2px 0;">Email: <?=$setting->email?></div>
                                        <div style="font-size: 15px;font-weight: 500;color: red;margin: 2px 0;">Hotline: <?=$setting->phone_number?></div>
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
      </script>
   </body>
</html>