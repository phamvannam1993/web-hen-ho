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
                    <li><strong><span>Tầm nhìn và xứ mệnh</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 category-products" style="margin-bottom: 30px;">
                <div class="box-heading relative">
                    <h1 class="title-head margin-top-0" style="padding-bottom: 20px;font-size: 25px;">Tầm nhìn và sứ mệnh</h1>
                    <i class="fa fa-book" aria-hidden="true"></i>
                </div>
            </div>
            <div class="select_maps sec_footer col-sm-12 col-xs-12" style="text-align: center;">
                <?php if(isset($introduce['image_url'])) { ?>
                    <p>
                        <img alt="" src="/<?=$introduce['image_url']?>" style="width:70%">
                    </p>
                <?php } ?>
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