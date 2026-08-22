<?php foreach($categories as $category) { ?>
<section style="margin-top: 30px;" class="awe-section-4" id="home_block_<?=$category['slug']?>">
<div class="section_fashion_hot section_base section_base_left">
    <div class="container">
        <div class="row">
            <div class="clearfix">
                <div class="col-md-12">
                <div class="border_bottom_title clearfix">
                </div>
                <div class="title_top_menu">
                    <h3><a href="/<?=$category['slug']?>"><?=$category['name']?></a></h3>
                </div>
                </div>
                <div class="col-md-12">
                <div class="content_sec clearfix ">
                    <div class="image_right">
                        <div class="prd_sec">
                            <div class="products owl-carousel owl-theme products-view-grid owl-loaded owl-drag">
                                <?php foreach($category['products'] as $item) {?>
                                <div class="item">
                                    <div class="product-box-h">
                                        <div class="product-thumbnail">
                                            <a class="image_link display_flex" href="/khoa-hoc/<?=$item['slug']?>" title="<?=$item['name']?>">
                                            <img class="lazy" src="/assets/images/loading.gif" data-src="/<?=$item['image_url']?>" alt="<?=$item['name']?>">
                                            </a>
                                        </div>
                                        <div class="product-info a-left">
                                            <h3 class="product-name"><a class="height_name text2line" href="/khoa-hoc/<?=$item['slug']?>" title="<?=$item['name']?>"><?=$item['name']?></a></h3>
                                            <div class="product-hides">
                                            <div class="product-hide">
                                                <div class="price-box clearfix">
                                                    <div class="special-price">
                                                        <span class="price product-price"><?=number_format($item['price'])?> ₫</span>
                                                    </div>
                                                    <div class="special-price pull-right hidden-xs">
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
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
</section>
<?php } ?>