<!DOCTYPE html>
<html lang="vi">
    <?php $this->load->view('includes/head')?>
    <script src="/assets/js/owl.carousel.min.js" type="text/javascript"></script>
    <body>
    <?php $this->load->view('includes/mySidenav')?>
      <!-- End -->
      <?php $this->load->view('includes/header')?>
      <div id="menu-overlay" class=""></div>
      <section class="awe-section-1" id="home_block_1">
         <div class="home-slider owl-carousel owl-theme">
            <?php foreach($sliders as $slider) { ?>
            <div class="item">
               <a href="" class="clearfix">
               <img src="/<?=$slider['image_url']?>" alt="<?=$slider['title']?>" style="width: 100%;">
               </a>
            </div>
            <?php } ?>
         </div>
      </section>
      
      <?php $this->load->view('front-end/home/block_pho_bien')?>
      <?php $this->load->view('front-end/home/block_category')?>
      <?php $this->load->view('includes/doi_tac')?>
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
      <!-- Bizweb javascript -->
      <!-- Plugin JS -->

      <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
      <div class='jas-sale-pop flex pf middle-xs'></div>
      <script type="text/javascript">
         $('.owl-deal').owlCarousel({
             loop:true,
             margin:30,
             nav:true,
             responsive:{
                 0:{
                     items:2
                 },
                 600:{
                     items:3
                 },
                 1000:{
                     items:4
                 }
             }
         });
         
         $('.home-slider').owlCarousel({
             loop:true,
             margin:10,
             autoplay:true,
             smartSpeed: 2000,
             nav:true,
             items:1,
         });
         
         $('.products-view-grid').owlCarousel({
             loop:true,
             margin:30,
             nav:true,
             responsive:{
                 0:{
                     items:2
                 },
                 600:{
                     items:3
                 },
                 1000:{
                     items:4
                 }
             }
         });
         
         $('.products-view-grid-luachon').owlCarousel({
             loop:true,
             margin:30,
             nav:true,
             responsive:{
                 0:{
                     items:1
                 },
                 600:{
                     items:2
                 },
                 1000:{
                     items:4
                 }
             }
         });
         
         $('.brand_content').owlCarousel({
             loop:true,
             margin:10,
             nav:true,
             responsive:{
                 0:{
                     items:2
                 },
                 600:{
                     items:4
                 },
                 1000:{
                     items:6
                 }
             }
         });
         
         $('#thumbs_list_quickview').owlCarousel({
             loop:true,
             margin:10,
             nav:true,
             items: 3,
         });
         
         $('#gallery_02').owlCarousel({
             loop:true,
             margin:10,
             nav:true,
             items: 3,
         });
         
 
         $("#gallery_02 img").click(function(){
             var img = $(this).attr('src');
             $('#img_01').attr('src', img);
         });
         
         function formatNumber(num) {
             return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.')
         };
         
         $(".e-tabs:not(.not-dqtab)").each( function(){
             $(this).find('.tabs-title li:first-child').addClass('current');
             $(this).find('.tab-content').first().addClass('current');
         
             $(this).find('.tabs-title li').click(function(){
                 var tab_id = $(this).attr('data-tab');
                 var url = $(this).attr('data-url');
                 $(this).closest('.e-tabs').find('.tab-viewall').attr('href',url);
         
                 $(this).closest('.e-tabs').find('.tabs-title li').removeClass('current');
                 $(this).closest('.e-tabs').find('.tab-content').removeClass('current');
         
                 $(this).addClass('current');
                 $(this).closest('.e-tabs').find("#"+tab_id).addClass('current');
             });    
         });
         
         $('.btn--view-more').on('click', function(e){
             e.preventDefault();
             var $this = $(this);
             $this.parents('#tab-1').find('.product-well').toggleClass('expanded');
             $(this).toggleClass('active');
             return false;
         });
         
         function sortby(value){
             var url = window.location.href;
             if(url.indexOf('?') == '-1'){
                 window.location.href = '?sort=' + value;
             }else{
                 if(url.indexOf('sort') == '-1'){
                     window.location.href = url + '&sort=' + value;
                 }else{
                     const params = new URLSearchParams(window.location.search);
                     var sort = params.get('sort');
                     url = url.replace(sort, value);
                     window.location.href = url;
                 }
             }
         };
          
         $(".swatch-element").click(function(){
             var price = $(this).attr('data-price');
             $('#_price').val(price);
             $('.product-price').html(price);
         });
         
         $('.psQttUp').on('click', function(e) {
             var id = $(this).attr('data-id');
             var quantity = parseInt($(this).attr('data-quantity'));
             var quantity = quantity + 1;
             window.location.href = window.location.href + '?id=' + id + '&quantity=' + quantity;
         });
         
         $('.psQttDown').on('click', function(e) {
             var id = $(this).attr('data-id');
             var quantity = parseInt($(this).attr('data-quantity'));
             var quantity = quantity - 1;
             if(quantity == 0){
                 window.location.href = '/' + 'remove-id-cart/' + id;
             }else{
                 window.location.href = window.location.href + '?id=' + id + '&quantity=' + quantity;
             }
         });
         
         $(".xem_nhanh").click(function(){
             var id = $(this).attr('data-id');
             var name = $(this).attr('data-name');
             var price = $(this).attr('data-price');
             var price_old = $(this).attr('data-price-old');
             if(price_old != 0){
                 var price_old = price_old + '₫';
             }
             var img = '/assets/images/product/'+$(this).attr('data-img');
             var img_thumb = $(this).attr('data-img_thumb');
             var trademark = $(this).attr('data-trademark'); 
             var description = $(this).attr('data-description');
             
             $('#product-featured-image-quickview').attr('src', img);
             $('#product-modal h3 a').html(name);
             $('.vendor_').html('<span>Thương hiệu: </span>'+trademark);
             $('#price_modal').html(formatNumber(price)+'₫');
             $('#price_old_modal').html(formatNumber(price_old));
             $('.product-description div').html(description);
             $('#modal_id').val(id);
             $('#modal_price').val(price);
             $('#quick-view-product').modal('show');
             
         });
         
         $(".order_fast").click(function(){
             var id = $(this).attr('data-id');
             var name = $(this).attr('data-name');
             var img = '/assets/images/product/'+$(this).attr('data-img');
             
             $('#order-fast-img').attr('src', img);
             $('#order-fast-name').html(name);
             $('#order_fast_id').val(id);
             $('#quick-view-product').modal('show');
         });
         
         $(document).ready(function($) {
             if ($(window).width() >= 768) {
                //  SalesPop();
             }
         });
         
         jQuery("#nav li.level0 li").mouseover(function(){
             if(jQuery(window).width() >= 740){
                 jQuery(this).children('ul').css({top:0,left:"158px"});
                 var offset = jQuery(this).offset();
                 if(offset && (jQuery(window).width() < offset.left+300)){
                     jQuery(this).children('ul').removeClass("right-sub");
                     jQuery(this).children('ul').addClass("left-sub");
                     jQuery(this).children('ul').css({top:0,left:"-158px"});
                 } else {
                     jQuery(this).children('ul').removeClass("left-sub");
                     jQuery(this).children('ul').addClass("right-sub");
                 }
                 jQuery(this).children('ul').fadeIn(100);
             }
         }).mouseleave(function(){
             if(jQuery(window).width() >= 740){
                 jQuery(this).children('ul').fadeOut(100);
             }
         });
         
         $('.menu-bar-h').click(function(e){
             e.stopPropagation();
             $('.menu_mobile').toggleClass('open_sidebar_menu');
             $('.opacity_menu').toggleClass('open_opacity');
         });
         $('.opacity_menu').click(function(e){
             $('.menu_mobile').removeClass('open_sidebar_menu');
             $('.opacity_menu').removeClass('open_opacity');
         });
         $('.ct-mobile li a.parent').click(function() {
             $(this).closest('li').find('> .sub-menu').slideToggle("fast");
             $(this).closest('li').find('i').toggleClass('show_open hide_close');
             return false;              
         });
         
         document.addEventListener("DOMContentLoaded", function() {
             var lazyloadImages;    
             if ("IntersectionObserver" in window) {
                 lazyloadImages = document.querySelectorAll(".lazy");
                 var imageObserver = new IntersectionObserver(function(entries, observer) {
                     entries.forEach(function(entry) {
                         if (entry.isIntersecting) {
                             var image = entry.target;
                             image.src = image.dataset.src;
                             image.classList.remove("lazy");
                             imageObserver.unobserve(image);
                         }
                     });
                 });
                 lazyloadImages.forEach(function(image) {
                     imageObserver.observe(image);
                 });	
             }else{  
                 var lazyloadThrottleTimeout;
                 lazyloadImages = document.querySelectorAll(".lazy");
                 function lazyload () {
                     if(lazyloadThrottleTimeout) {
                         clearTimeout(lazyloadThrottleTimeout);
                     }
                     lazyloadThrottleTimeout = setTimeout(function() {
                         var scrollTop = window.pageYOffset;
                         lazyloadImages.forEach(function(img) {
                             if(img.offsetTop < (window.innerHeight + scrollTop)) {
                                 img.src = img.dataset.src;
                                 img.classList.remove('lazy');
                             }
                         });
                         if(lazyloadImages.length == 0) { 
                             document.removeEventListener("scroll", lazyload);
                             window.removeEventListener("resize", lazyload);
                             window.removeEventListener("orientationChange", lazyload);
                         }
                     }, 20);
                 }
                 document.addEventListener("scroll", lazyload);
                 window.addEventListener("resize", lazyload);
                 window.addEventListener("orientationChange", lazyload);
             }
         });
         
         function fisherYates(myArray) {
             var i = myArray.length,
                 j, temp;
             if (i === 0) return false;
             while (--i) {
                 j = Math.floor(Math.random() * (i + 1));
                 temp = myArray[i];
                 myArray[i] = myArray[j];
                 myArray[j] = temp;
             }
         }
        
        //  fisherYates(collection);
         
         function SalesPop() {
             if ($('.jas-sale-pop').length < 0)
                 return;
             setInterval(function() {
                 $('.jas-sale-pop').fadeIn(function() {
                     $(this).removeClass('slideUp');
                 }).delay(30000).fadeIn(function() {
                     var randomTime = ['20 phút', '21 phút', '22 phút', '23 phút', '24 phút', '25 phút', '26 phút', '27 phút', '28 phút', '29 phút', '30 phút', '31 phút', '32 phút', '33 phút', '34 phút', '35 phút', '36 phút', '37 phút', '38 phút', '39 phút', '40 phút', '41 phút', '42 phút', '43 phút', '44 phút', '45 phút', '46 phút', '47 phút', '48 phút', '49 phút', '50 phút', '51 phút', '52 phút', '53 phút', '54 phút', '55 phút', '56 phút', '57 phút', '58 phút', '59 phút', ],
                         randomTimeAgo = Math.floor(Math.random() * randomTime.length),
                         randomProduct = Math.floor(Math.random() * collection.length),
                         randomShowP = collection[randomProduct],
                         TimeAgo = randomTime[randomTimeAgo];
                     $(".jas-sale-pop").html(randomShowP);
                     $('.jas-sale-pop-timeago').text('Một khách hàng vừa đăng ký học cách đây ' + TimeAgo);
                     $(this).addClass('slideUp');
                     $('.pe-7s-close').on('click', function() {
                         $('.jas-sale-pop').remove();
                     });
                 }).delay(30000);
             }, 30000);
         }
      </script>
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