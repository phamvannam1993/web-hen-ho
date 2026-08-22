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
                    <li><strong><span>Tin tức</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container">
   <div class="row">
      <section class="right-content margin-bottom-50 col-md-9 ">
         <section class="list-blogs blog-main">
            <div class="row">
               <?php foreach($datas as $item) { ?>
               <div class="col-sm-12 blog_xxx">
                  <article class="blog-item">
                     <div class="row">
                        <div class="col-lg-4">
                           <div class="blog-item-thumbnail">
                              <a href="/tin-tuc/<?=$item['slug']?>">
                              <img src="/<?=$item['image_url']?>" style="max-width:100%;" class="img-responsive" title="" alt="">
                              </a>
                           </div>
                        </div>
                        <div class="col-lg-8">
                           <div class="blog-item-info">
                              <h3 class="blog-item-name">
                                 <a href="/tin-tuc/<?=$item['slug']?>"><?=$item['title']?></a>
                              </h3>
                              <div class="date">
                                 <i class="fa fa-clock-o"></i>
                                 <div class="news_home_content_short_time">
                                    <?=date('d-m-Y', strtotime($item['created_at']))?>                                        
                                 </div>
                                 <span class="cmt_count_blog">
                                 <i class="fa fa-user" aria-hidden="true"></i>Đăng bởi Admin
                                 </span>
                              </div>
                              <p class="blog-item-summary"></p>
                           </div>
                        </div>
                     </div>
                  </article>
               </div>
               <?php } ?>
               <div class="text-center">
                  <ul class="pagination">
                     <li class="prev <?= ($page == 1) ? 'disabled' : '' ?>">
                           <a class="page-link previous" href="<?= $previousPageUrl ?? '#' ?>">
                              <span>«</span>
                           </a>
                     </li>
                     <?php foreach ($pagesToShow as $p): ?>
                           <?php if ($p === '...'): ?>
                              <li class="page-item disabled"><span class="page-link">...</span></li>
                           <?php else: ?>
                              <li class=" <?= ($p == $page) ? 'active' : '' ?>">
                                 <a class="page-link" href="<?= pageUrl($p) ?>"><?= $p ?></a>
                              </li>
                           <?php endif; ?>
                     <?php endforeach; ?>
                     <li class="next <?= ($page == $totalPage) ? 'disabled' : '' ?>">
                           <a class="page-link next" href="<?= $nextPageUrl ?? '#' ?>">
                              »
                           </a>
                     </li>
                  </ul>
               </div>
            </div>
         </section>
         <div class="row">
            <div class="col-xs-12 text-left">
            </div>
         </div>
      </section>
      <aside class="blog_hai left left-content col-md-3 ">
         <div class="blog-aside aside-item blog-aside-article">
            <div>
               <div class="aside-title-article">
                  <h2 class="title-head"><span><a href="/tin-tuc">Tin tức mới nhất</a></span></h2>
               </div>
               <div class="aside-content-article">
                  <div class="blog-list blog-image-list">
                  <?php foreach($dataNews as $item) { ?>
                     <div class="loop-blog">
                        <div class="thumb-left">
                           <a href="/tin-tuc/<?=$item['slug']?>">
                           <img src="/<?=$item['image_url']?>" data-src="/<?=$item['image_url']?>" style="max-width:100%;" class="img-responsive" title="<?=$item['title']?>" alt="<?=$item['title']?>">
                           </a>
                        </div>
                        <div class="name-right">
                           <h3><a href="/tin-tuc/<?=$item['slug']?>" title="<?=$item['title']?>"><?=$item['title']?></a></h3>
                        </div>
                     </div>
                  </div>
                  <?php } ?>
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