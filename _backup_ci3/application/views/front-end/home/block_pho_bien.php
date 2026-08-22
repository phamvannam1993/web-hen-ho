<section class="awe-section-2" id="home_block_2" style="margin-top:30px;display: none;">
    <aside class="adv_bottom">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                <div class="adv_bottom_inner">
                <figure class="img_effect img_1">
                    <a href="#" title="Banner 1"><img class="img-responsive center-base" src="/assets/images/bnpti2.jpg" alt="Banner 1"></a>
                </figure>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                <div class="adv_bottom_inner">
                <figure class="img_effect img_100">
                    <a href="#" title="Banner 2"><img class="img-responsive center-base" src="/assets/images/khoa-hoc-pti-ceo-toan-dien.png" alt="Banner 2"></a>
                </figure>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                <div class="row">
                <div class="col-lg-12 margin-style">
                    <div class="img_effect img_100">
                        <figure class="effect-apollo">
                            <a href="#" title="Banner 3">
                            <img class="img-responsive center-base" src="/assets/images/cfo-chinh.jpg" alt="Banner 3">
                            </a>
                        </figure>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="img_effect img_100">
                        <figure class="effect-apollo">
                            <a href="#" title="Banner 3"><img class="img-responsive center-base" src="/assets/images/CEO-01.png" alt="Banner 3"></a>
                        </figure>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    </aside>
</section>
<section class="awe-section-3" id="home_block_3">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading_hotdeal">
                <h2 class="title-head">
                    <span class="color_main">Khóa học</span> phổ biến nhất
                </h2>
                </div>
                <div class="border_wrap">
                <div class="owl_product_comback">
                    <div class="owl-carousel owl-theme owl-deal">
                        <?php foreach($course_populars as $course_popular) {?>
                        <div class="item">
                            <div class="product-box-h">
                            <div class="product-thumbnail">
                                <a class="image_link display_flex" href="/khoa-hoc/<?=$course_popular['slug']?>" title="<?=$course_popular['name']?>">
                                <img class="lazy" src="/<?=$course_popular['image_url']?>" data-src="<?=$course_popular['image_url']?>" alt="<?=$course_popular['name']?>">
                                </a>
                            </div>
                            <div class="product-info a-left">
                                <h3 class="product-name">
                                    <a class="height_name text2line" href="/khoa-hoc/<?=$course_popular['slug']?>" title=""><?=$course_popular['name']?></a>
                                </h3>
                                <div class="product-hides">
                                    <div class="product-hide">
                                        <div class="price-box clearfix">
                                        <div class="special-price">
                                            <span class="price product-price"><?=number_format($course_popular['price'])?> ₫</span>
                                        </div>
                                        <div class="special-price pull-right hidden-xs">
                                            <span class="price product-price" style="color: #4b55b7"><?=date('d/m/Y', strtotime($course_popular['created_at']))?></span>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>