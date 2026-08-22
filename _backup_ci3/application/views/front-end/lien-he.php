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
                    <li><strong><span>Liên hệ</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="page_contact">
        <div class="container">
            <div class="row">
                <div class="select_maps sec_footer col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="aa">
                    <div class="page_cotact">
                        <h1 class="title_db"><span>Trường doanh nhân PTI</span></h1>
                    </div>
                    <div class="list-menu">
                        <div class="widget-db">
                            <div class="item">
                                <ul class="contact contact_x">
                                <b class="title_bold">Hà Nội:</b>
                                <li><i class="fa fa-map-marker"></i>
                                    <span class="txt_content_child"><?=$setting->address_hn_1?></span>
                                </li>
                                <li><i class="fa fa-map-marker"></i>
                                    <span class="txt_content_child"><?=$setting->address_hn_2?></span>
                                </li>
                                <li class="sdt">
                                    <i class="fa fa-mobile" aria-hidden="true" style="font-size: 18px;"></i>
                                    <span>Điện thoại:</span>
                                    <a href="tel:<?=$setting->phone_number?>"><?=$setting->phone_number?></a>
                                </li>
                                <li class="sdt">
                                    <i class="fa fa-envelope" aria-hidden="true"></i>
                                    <span>Email:</span>
                                    <a href="mailto:<?=$setting->email?>"><?=$setting->email?></a>
                                </li>
                                <b class="title_bold">TP. HCM:</b>
                                <li><i class="fa fa-map-marker"></i>
                                    <span class="txt_content_child"><?=$setting->address_hcm?></span>
                                </li>
                                <li class="sdt">
                                    <i class="fa fa-mobile" aria-hidden="true" style="font-size: 18px;"></i>
                                    <span>Điện thoại:</span>
                                    <a href="tel:<?=$setting->phone_number?>"><?=$setting->phone_number?></a>
                                </li>
                                <li class="sdt">
                                    <i class="fa fa-envelope" aria-hidden="true"></i>
                                    <span>Email:</span>
                                    <a href="mailto:<?=$setting->email?>"><?=$setting->email?></a>
                                </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 right_contact">
                    <div class="page-login page_cotact">
                    <h2 class="title-head-contact a-left"><span>Gửi liên hệ cho chúng tôi:</span></h2>
                    <div id="pagelogin">
                        <form id="contact" action="" method="post">
                            <?php if(isset($success)) { ?>
                                <div class="alert alert-success">
                                    <p>Bạn đã gửi liên hệ thành công</p>
                                </div>
                            <?php } ?>
                            <div class="form-signup clearfix">
                                <div class="row group_contact">
                                <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <input placeholder="Họ và tên" type="text" class="form-control  form-control-lg" required="" value="" name="contact[fullname]">
                                </fieldset>
                                <fieldset class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <input placeholder="Email" type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" required="" id="email1" class="form-control form-control-lg" value="" name="contact[email]">
                                </fieldset>
                                <fieldset class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">	
                                    <input placeholder="Số điện thoại" type="text" class="key_phone form-control form-control-comment form-control-lg" name="contact[phone_number]" required="">
                                </fieldset>
                                <fieldset class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <textarea placeholder="Nội dung" name="contact[note]" id="comment" class="form-control content-area form-control-lg" rows="5" required=""></textarea>
                                </fieldset>
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 margin-top-10">
                                    <button type="submit" class="btn btn-primary btn-lienhe">Gửi liên hệ</button>
                                </div>
                                </div>
                            </div>
                        </form>
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