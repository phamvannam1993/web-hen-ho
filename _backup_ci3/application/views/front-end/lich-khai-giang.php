<!DOCTYPE html>
<html lang="vi">
    <?php $this->load->view('includes/head')?>
    <link href="/assets/css/lich-khai-giang.css" rel="stylesheet" type="text/css" />
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
                    <li><strong><span>Lịch khai giảng</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
   <div class="row">
      <section class="right-content margin-bottom-50 col-md-8 ">
         <h2 class="cat_title_other" style="margin-top:0px">CHƯƠNG TRÌNH ĐÀO TẠO TẠI HÀ NỘI</h2>
         <section class="time-section" style="margin-bottom:30px">
            <?php foreach($categoriesHN as $categoryHN) { ?>
            <h3 class="time-section__heading">CHƯƠNG TRÌNH <?=$categoryHN['detail']['name']?></h3>
            <table class="timetable w-100">
               <thead>
                  <tr>
                     <th style="white-space: nowrap">Khóa học</th>
                     <th class="text-left" style="width: 115px;white-space: nowrap">Khai giảng</th>
                     <th class="text-center" style="width: 126px;white-space: nowrap">Học phí</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach($categoryHN['products'] as $product) { 
                     foreach($product['items'] as $item) { ?>
                  <tr>
                     <td>
                        <a href="/khoa-hoc/<?=$product['detail']['slug']?>" style="width: calc(100% - 230px)"><?=$product['detail']['name']?></a>
                     </td>
                     <td class="text-left" style="width: 115px;white-space: nowrap">
                        <?=$item['day']?>                              
                     </td>
                     <td class="text-center" style="width: 126px;white-space: nowrap"><?=number_format($product['detail']['price'])?> VNĐ</td>
                  </tr>
                  <?php } } ?>
               </tbody>
            </table>
            <?php } ?>
         </section>
         <h2 class="cat_title_other">CHƯƠNG TRÌNH ĐÀO TẠO TẠI TP HỒ CHÍ MINH</h2>
         <section class="time-section">
         <?php foreach($categoriesHCM as $categoryHCM) { ?>
            <h3 class="time-section__heading">CHƯƠNG TRÌNH <?=$categoryHCM['detail']['name']?></h3>
            <table class="timetable w-100">
               <thead>
                  <tr>
                     <th style="white-space: nowrap">Khóa học</th>
                     <th class="text-left" style="width: 115px;white-space: nowrap">Khai giảng</th>
                     <th class="text-center" style="width: 126px;white-space: nowrap">Học phí</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach($categoryHCM['products'] as $product) { 
                     foreach($product['items'] as $item) { ?>
                  <tr>
                     <td>
                        <a href="/khoa-hoc/<?=$product['detail']['slug']?>" style="width: calc(100% - 230px)"><?=$product['detail']['name']?></a>
                     </td>
                     <td class="text-left" style="width: 115px;white-space: nowrap">
                        <?=$item['day']?>                              
                     </td>
                     <td class="text-center" style="width: 126px;white-space: nowrap"><?=number_format($product['detail']['price'])?> VNĐ</td>
                  </tr>
                  <?php } } ?>
               </tbody>
            </table>
            <?php } ?>
         </section>
      </section>
      <aside class="blog_hai left left-content col-md-4 " style="padding-left:30px">
         <div class=" module_best_sale_product module_best_sale_product_1" style="margin-bottom:30px">
            <div class="title_module_">
               <h2 class="title"><a href="khoa-hoc-noi-bat" title="Có thể bạn thích">Khóa học nổi bật</a></h2>
            </div>
            <div class="sale_off_today">
               <div class="not-dqowl wrp_list_product">
                  <?php foreach($course_populars as $item) {?>
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
                  <?php } ?>
               </div>
            </div>
         </div>
         <div class="blog-aside aside-item blog-aside-article">
            <div>
               <div class="aside-title-article">
                  <h2 class="title-head"><span><a href="/tin-tuc">Tin tức mới nhất</a></span></h2>
               </div>
               <div class="aside-content-article">
                  <div class="blog-list blog-image-list">
                     <?php foreach($news as $new) { ?>
                     <div class="loop-blog">
                        <div class="thumb-left">
                           <a href="/tin-tuc/<?=$new['slug']?>">
                           <img src="/<?=$new['image_url']?>" data-src="/<?=$new['image_url']?>" style="max-width:100%;" class="lazy img-responsive" title="<?=$new['title']?>">
                           </a>
                        </div>
                        <div class="name-right">
                           <h3><a href="/tin-tuc/<?=$new['slug']?>" title="<?=$new['title']?>"><?=$new['title']?></a></h3>
                        </div>
                     </div>
                     <?php } ?>
                  </div>
               </div>
            </div>
         </div>
      </aside>
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