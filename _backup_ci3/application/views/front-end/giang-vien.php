<!DOCTYPE html>
<html lang="vi">
    <style>
        .bd1 {
            border: 1px solid #e6e6e6;
        }
        .m-card-3__body {
            text-align: center;
            padding: 12px 0 0;
        }

        .m-card-3__name {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .eff-img-room {
            overflow: hidden;
        }
        .gutter-15 [class^="col"] {
            padding: 0 8px 0 7px;
        }
        .gutter-15 {
            margin: 0 -8px 0 -7px;
        }
        .eff-img-room img {
            transition: all .3s;
        }
        .eff-img-room:hover img {
            transform: scale(1.05);
            transition: all .4s;
        }
        .w-100 {
            width: 100% !important;
        }
        .m-paging .pagination {
            justify-content: center;
            margin-bottom: 60px;
        }
        .m-paging .page-item.active .page-link, .m-paging .page-item.active .page-link:hover {
            color: #fff;
        }
    </style>
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
                    <li><strong><span>Giảng viên</span></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

<section class="id-section">
    <div class="container">
        <h4 class="id-section__heading">
            <span>HỘI ĐỒNG BAN GIẢNG HUẤN &amp; CHUYÊN GIA</span>
            <img class="id-section__heading-icon" src="https://pti.edu.vn/themes/pti.edu.vn/images/icon-book-blue.png" alt="" />
        </h4>
        <ul class="ls">
            <?php foreach($datas as $data) {?>
            <li class="mb30">
                <div class="m-teacher">
                    <div class="row gutter-15">
                        <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6 mb30">
                            <div class="m-card-3">
                                <a class="d-block bd1 eff-img-room" href="#">
                                    <img class="w-100" src="/<?=$data['image_url']?>" alt="<?=$data['name']?>" />
                                </a>
                                <div class="m-card-3__body">
                                    <h5 class="m-card-3__name"><?=$data['name']?></h5>
                                    <span><?=$data['title']?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-9 col-lg-8 col-sm-6 col-md-7 mb30">
                            <div class="m-teacher__block">
                                <h5 class="m-teacher__heading mb-2">Chuyên môn</h5>
                                <?=$data['specialties']?>
                            </div>
                            <div class="m-teacher__block">
                                <h5 class="m-teacher__heading mb-2">kinh-nghiệm</h5>
                                <div class="m-teacher__desc"><p><?=$data['experience']?></p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            <?php } ?>
        </ul>
        <nav class="m-paging">
            <ul class="pagination">
                <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link previous" href="<?= $previousPageUrl ?? '#' ?>">
                        <i class="fa fa-angle-left"></i>
                    </a>
                </li>
                <?php foreach ($pagesToShow as $p): ?>
                    <?php if ($p === '...'): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php else: ?>
                        <li class="dt-paging-button page-item <?= ($p == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= pageUrl2($p) ?>"><?= $p ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li class="dt-paging-button page-item <?= ($page == $totalPage) ? 'disabled' : '' ?>">
                    <a class="page-link next" href="<?= $nextPageUrl ?? '#' ?>">
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</section>
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