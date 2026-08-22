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
                    <li><strong><span><?=$category->name?></span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="page_contact">
        <div class="container">
            <div class="row">
            <section class="main_container collection col-lg-12">
                <div class="category-products products">
                    <div class="sortPagiBar">
                        <div class="row">
                            <div class="col-xs-12 col-sm-12 col-md-12">
                            <div class="box-heading relative">
                                <h1 class="title-head margin-top-0"><?=$category->name?></h1>
                                <i class="fa fa-book" aria-hidden="true"></i>
                            </div>
                            </div>
                        </div>
                    </div>
                    <section class="products-view products-view-grid1">
                        <div class="row">
                            <?php foreach($products as $item) { ?>
                            <div class="col-xs-6 col-sm-4 col-md-3 col-lg-3">
                                <div class="product-box-h">
                                    <div class="product-thumbnail">
                                        <a class="image_link display_flex" href="/khoa-hoc/<?=$item['slug']?>" title="">
                                        <img class="" src="/<?=$item['image_url']?>" data-src="/<?=$item['image_url']?>" alt="<?=$item['name']?>">
                                        </a>
                                    </div>
                                    <div class="product-info a-left">
                                        <h3 class="product-name">
                                            <a class="height_name text2line" href="/khoa-hoc/<?=$item['slug']?>" title="<?=$item['name']?>"><?=$item['name']?></a>
                                        </h3>
                                        <div class="product-hides">
                                            <div class="product-hide">
                                            <div class="price-box clearfix">
                                                <div class="special-price">
                                                    <span class="price product-price"><?=number_format($item['price'])?> ₫</span>
                                                </div>
                                                <div class="special-price pull-right">
                                                    <span class="price product-price" style="color: #4b55b7"><?=date('d/m/Y', strtotime($item['created_at']))?></span>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </section>
                </div>
                <div class="text-center">
                </div>
                </section>
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