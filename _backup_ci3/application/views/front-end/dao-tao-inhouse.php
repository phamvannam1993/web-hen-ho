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
                    <li><strong><span>Đào tạo Inhouse</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 category-products" style="margin-bottom: 30px;">
                <div class="box-heading relative">
                    <h1 class="title-head margin-top-0" style="padding-bottom: 20px;font-size: 25px;">Đào tạo Inhouse</h1>
                    <i class="fa fa-book" aria-hidden="true"></i>
                </div>
            </div>
            <section class="id-section">
                <div class="container">
                    <ul class="nav nav-pills m-tabs-4 inhouse" role="tablist">
                    <?php foreach($inhouses as $key => $item) { ?>
                        <li class="nav-item <?=$key == 0 ? 'active' : ''?>" style="flex-grow: 1;width: inherit;box-shadow: 0 0 0 1px #e6e6e6;">
                            <a class="nav-link <?=$key == 0 ? 'active show' : ''?>" href="#item-<?=$key?>" data-toggle="pill" role="tab" style="box-shadow:none;display: flex;align-items: center;height: 100%;" aria-selected="true"><?=$item['title']?></a>
                        </li>
                    <?php } ?>
                    </ul>
                    <div class="tab-content mb30 ">
                        <?php foreach($inhouses as $key => $item) { ?>
                        <div class="tab-pane fade <?=$key == 0 ? 'active' : ''?> in" id="item-<?=$key?>" role="tabpanel" style="flex-wrap: nowrap">
                            <div class="m-training-text">
                                <?=$item['content']?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <h4 style="font-size: 16px;text-transform: uppercase;margin-bottom: 30px;">Chương trình đào tạo inhouse</h4>
                    <ul class="list_course">
                    <li>
                        <span class="sp1"></span>
                        <span class="sp2">Chiến lược phát triển nguồn Nhân lực</span>
                    </li>
                    <li>
                        <span class="sp1"></span>
                        <span class="sp2">Ứng dụng mô hình 5S</span>
                    </li>
                    <li>
                        <span class="sp1"></span>
                        <span class="sp2">Chiến lược và Kế hoạch sản xuất</span>
                    </li>
                    <li>
                        <span class="sp1"></span>
                        <span class="sp2">Đánh giá nhân sự trong sản xuất</span>
                    </li>
                    </ul>
                </div>
            </section>
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