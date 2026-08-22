<footer class="footer" style="padding-top: 30px;">
    <div class="site-footer">
        <div class="top-footer" style="border-bottom: 1px solid #ccc;">
            <div class="container">
               
            </div>
        </div>
        <div class="top-footer">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="widget-ft first">
                            <h4 class="title-menu">
                                <a role="button" class="collapsed" data-toggle="collapse" aria-expanded="false" data-target="#collapseListMenu01" aria-controls="collapseListMenu01">
                                    Giới thiệu <i class="fa fa-plus" aria-hidden="true"></i>
                                </a>
                            </h4>
                            <div class="collapse" id="collapseListMenu01">
                                <ul class="list-menu">
                                    <li class="li_menu"><a href="/tam-nhin-va-su-menh">Tầm nhìn &amp; sứ mệnh</a></li>
                                    <li class="li_menu"><a href="/gia-tri-cot-loi">Giá trị cốt lõi</a></li>
                                    <li class="li_menu"><a href="/hinh-anh-khoa-hoc">Hình ảnh khóa học</a></li>
                                    <li class="li_menu"><a href="/giang-vien">Giảng viên</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="widget-ft">
                            <h4 class="title-menu">
                                <a role="button" class="collapsed" data-toggle="collapse" aria-expanded="false" data-target="#collapseListMenu02" aria-controls="collapseListMenu02">
                                    Khóa học
                                </a>
                            </h4>
                            <div class="collapse time_work" id="collapseListMenu02">
                                <ul class="list-menu">
                                    <?php foreach($categories as $item) { ?>
                                    <li class="li_menu"><a href="/<?=$item['slug']?>"><?=$item['name']?></a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="widget-ft last">
                            <h4 class="title-menu">
                                <a>
                                    TỔ CHỨC GIÁO DỤC ĐÀO TẠO PTI
                                </a>
                            </h4>
                            <div>
                                <div class="list-menu">
                                    <div class="widget-db" style="margin-top: 0px;">
                                        <div class="item">
                                            <ul class="contact contact_x">
                                                <li>
                                                    <span class="txt_content_child">
                                                        <b style="display: inline-block; margin-bottom: 5px;">Hà Nội:</b><br />
                                                        <span style="vertical-align: text-bottom;"><i class="fa fa-map-marker" aria-hidden="true" style="margin-right: 0px; line-height: 30px;"></i> </span>
                                                        <?=isset($setting->address_hn_1) ? $setting->address_hn_1 : 'Tầng 14, Tháp B, Tòa nhà Sông Đà, Đường Phạm Hùng, Q.Nam Từ Liêm, Hà Nội'?><br />
                                                        <span style="display: inline-block; margin-top: 5px; vertical-align: text-bottom;">
                                                            <i class="fa fa-map-marker" aria-hidden="true" style="margin-right: 0px; line-height: 30px;"></i>  <?=isset($setting->address_hn_2) ? $setting->address_hn_2 : 'Tầng 2, Tòa N01-T1, KĐT Đoàn Ngoại Giao, P.Xuân Tảo, Q.Bắc Từ Liêm, Hà Nội'?>
                                                        </span>
                                                    </span>
                                                </li>
                                                <li style="margin-top: 10px;">
                                                    <span class="txt_content_child">
                                                        <b style="display: inline-block; margin-bottom: 5px;">TP.HCM:</b><br />
                                                        <span style="vertical-align: text-bottom;"><i class="fa fa-map-marker" aria-hidden="true" style="margin-right: 0px; line-height: 30px;"></i> </span>
                                                        <?=isset($setting->address_hcm) ? $setting->address_hcm : '106/4 Trường Chinh, Khu Phố 6, Phường Tân Hưng Thuận, Quận 12, TP.HCM'?>
                                                    </span>
                                                </li>
                                                <li class="sdt" style="margin: 10px 0px;">
                                                    <span><i class="fa fa-phone-square" aria-hidden="true"></i> </span>
                                                    <a href="tel: <?=isset($setting->phone_number) ? $setting->phone_number : '0984.672.297'?>"> <?=isset($setting->phone_number) ? $setting->phone_number : '0984.672.297'?></a>
                                                </li>
                                                <li class="sdt">
                                                    <span><i class="fa fa-envelope" aria-hidden="true" style="font-size: 15px;"></i> </span>
                                                    <a href="mailto:<?=isset($setting->email) ? $setting->email : 'khoahocpti07@gmail.com'?>"> <?=isset($setting->email) ? $setting->email : 'khoahocpti07@gmail.com'?></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .item_policy {
                display: flex;
                border-left: 1px solid #ccc;
                height: 80px;
                line-height: 80px;
                text-align: center;
                justify-content: center;
            }
            .item_policy i {
                margin: auto 13px;
                font-size: 24px;
            }
        </style>
        <div class="border-bottom-1px"></div>
        <div class="mid-footer">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-5">
                        <div>
                            <h4 class="title-menu2 icon_none_first">
                                <a>Đăng ký nhận tin khóa học</a>
                            </h4>
                            <ul class="contact contact_mail">
                                <li>
                                    <!-- <form id="mc-embedded-subscribe-form" action="email/index" method="post"> -->
                                        <input type="email" value="" placeholder="Nhập email của bạn" name="Email[email]" id="mail" />
                                        <button class="btn btn-primary subscribe" name="subscribe" id="subscribe">Đăng ký</button>
                                    <!-- </form> -->
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4 pay_footer hidden">
                        <h4 class="title-menu2 icon_none_first">
                            <a>Phương thức thanh toán</a>
                        </h4>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 social_footer">
                        <h4 class="title-menu2 icon_none_first icon_title_last">
                            <a>Kết nối với chúng tôi</a>
                        </h4>
                        <div class="social_footer">
                            <ul class="follow_option">
                                <li>
                                    <a href="<?=isset($setting->fb_link) ? $setting->fb_link : 'https://www.facebook.com/khoahocpticeo'?>" target="_blank" title="Theo dõi Facebook <?=isset($setting->title_bottom) ? $setting->title_bottom : 'Khóa học PTI'?>"><i class="fa fa-facebook"></i></a>
                                </li>
                                <li>
                                    <a href="#" target="_blank" title="Theo dõi Youtube Khóa học PTI"><i class="fa fa-youtube-play"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-footer-bottom copyright clearfix">
            <div class="container">
                <div class="inner clearfix">
                    <div class="row tablet">
                        <div id="copyright" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 a-center fot_copyright">
                            <span class="wsp">
                                <span class="mobile">© Bản quyền thuộc về <b> <?=isset($setting->title_bottom) ? $setting->title_bottom : 'Khóa học PTI'?></b></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<div class="contact-group" style="bottom: 180px;">
    <div class="button-action-group">
        <a href="https://zalo.me/<?=isset($setting->phone_number) ? $setting->phone_number : ''?>" target="_blank"><img src="/assets/images/zalo.svg" alt="Zalo" /></a>
    </div>
</div>
<div class="contact-group">
    <div class="button-action-group">
        <a href="tel:<?=isset($setting->phone_number) ? $setting->phone_number : ''?>"><i class="fa fa-phone"></i></a>
    </div>
</div>
<script>
   function search() {
        var key = xoa_dau($('#Keyword').val());
        if(key.length>2){
            window.location.href = '/search/'+key;
        }else{
            alert('Nội dung quá ngắn yêu cầu nhập thêm');
        }
    };
         
    $('#Keyword').on('keydown', function(e) {
        if (e.which == 13) {
            search();
        }
    });
    
    $(".icon-fallback-text").click(function(){
        search();
    });

    function xoa_dau(str) {
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
        str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
        str = str.replace(/đ/g, "d");
        str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g, "A");
        str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g, "E");
        str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g, "I");
        str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g, "O");
        str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g, "U");
        str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g, "Y");
        str = str.replace(/Đ/g, "D");
        str = str.replace(/\s+/g, "-");
        return str;
    }

</script>
<style>
.custom-free-shipping {
        margin-top: 25px;
        display: inline-block;
        width: 100%;
    }
    .custom-free-shipping .icon img {
        max-width: 100%;
    }
    .custom-free-shipping .info {
        text-align: center;
        margin-top: 20px;
        line-height: 16px;
    }
    .custom-free-shipping .info a {
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: none;
        color: #707070;
    }
    .custom-free-shipping .info p {
        font-size: 12px;
        color: #707070;
    }
    .product-box-h .product-info{
        border: 1px solid #ccc;
        border-top:0px ;
        padding: 0px 15px 20px;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
    }
    .product-box-h .product-info .product-name a{
        height: 45px;
    }
    .box-heading{
        text-align: center;
    }
    .category-products .box-heading::before {
        content: '';
        display: block;
        width: 104px;
        height: 1px;
        background: linear-gradient(to right, transparent, #0d487f);
        position: absolute;
        bottom: 0;
        right: 50%;
        margin-right: 19px;
    }
    .category-products .box-heading::after{
        content: '';
        display: block;
        width: 104px;
        height: 1px;
        background: linear-gradient(to right, transparent, #0d487f);
        position: absolute;
        bottom: 0;
        right: auto;
        left: 50%;
        margin-right: 0;
        margin-left: 19px;
        transform: rotateY(180deg);
    }
    .category-products .box-heading .fa{
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #0d487f;
    }
    .as-course__btn {
        display: block;
        background-color: #0d487f;
        border-radius: 5px;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        line-height: 24px;
        padding: 8px 10px;
        color: #fff;
        margin: 20px 0px;
    }
    .module_best_sale_product_1 .title_module_{
        border-bottom: 1px solid #e5e5e5;
        border-top-right-radius: 3px;
        border-top-left-radius: 3px;
        background: #eb3e32;
    }
    .module_best_sale_product_1 .title_module_ .title {
    font-size: 24px;
    line-height: 40px;
    text-transform: uppercase;
    color: #fff;
    padding: 3px 22px;
    margin: 0px;
    font-weight: 400;
    font-family: 'UTM';
}
.module_best_sale_product_1 .title_module_ .title a {
    text-decoration: none;
    color: #fff;
}
.contact-group {
    position: fixed;
    z-index: 999999;
    right: 25px;
    bottom: 100px;
}
.contact-group .button-action-group a {
    color: #fff;
    font-size: 20px;
    font-weight: bold;
    border-radius: 30px;
    letter-spacing: 1px;
    -webkit-animation: fadeup 1s cubic-bezier(0.24,0,.38,1);
    animation: fadeup 1s cubic-bezier(0.24,0,.38,1);
    -webkit-animation-fill-mode: forwards;
    animation-fill-mode: forwards;
}
.contact-group .button-action-group a i {
    width: 60px;
    height: 60px;
    background: rgba(243,113,33,.8);
    line-height: 48px;
    text-align: center;
    border-radius: 50%;
    box-shadow: 2px 0 7px -2px #00000078;
    position: relative;
    font-size: 48px;
}
.contact-group .button-action-group a img {
    width: 60px;
    height: 60px;
    background: #0068FF;
    line-height: 48px;
    text-align: center;
    border-radius: 50%;
    box-shadow: 2px 0 7px -2px #00000078;
    position: relative;
    font-size: 48px;
    padding: 10px;
    object-fit: contain;
}
.contact-group .button-action-group a i:after {
    content: '';
    position: absolute;
    width: 50px;
    height: 50px;
    left: 4px;
    top: 4px;
    border-radius: 50%;
    border-width: 1px;
    border-left-color: #f1f1f1;
    border-style: solid;
    border-right-color: #f1f1f1;
    border-top-color: transparent;
    border-bottom-color: transparent;
    -webkit-animation: rotate 1s cubic-bezier(0.24,0,.38,1) infinite;
    animation: rotate 1s cubic-bezier(0.24,0,.38,1) infinite;
}
.contact-group .button-action-group a i:before {
    font-size: 30px;
}
@media(max-width:767px) {
    .product-box-h .product-info .product-name a {
        font-size: 13px;
    }
    .custom-free-shipping{
        margin-top: 15px;
    }
    .custom-free-shipping .info a{
        font-size: 12px;
    }
    .heading_hotdeal:before, .heading_hotdeal:after{
        top: 5px;
    }
    .heading_hotdeal h2{
        font-size: 28px;
    }
    .owl-carousel .owl-nav .owl-prev, .owl-carousel .owl-nav .owl-next{
        display: none;
    }
    .home-slider .item a img{
        min-height: 130px;
    }
}
</style>