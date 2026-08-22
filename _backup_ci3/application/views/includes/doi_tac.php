<div class="partner-box">
  <h4 class="id-section__heading">
        <span>ĐỐI TÁC &amp; THÂN HỮU</span>
        <img class="id-section__heading-icon" src="/assets/images/doi-tac/icon-book-blue.png" alt="">
    </h4>
  <div class="tabs">
    <button class="tab active" data-tab="domestic">Đối tác trong nước</button>
    <button class="tab" data-tab="international">Đối tác quốc tế</button>
  </div>

  <div class="slider-container">
  <!-- Nút điều hướng trái -->
  <button class="m-slider__prev m-partner__prev js-partner-slider-prev">
    <img src="/assets/images/doi-tac/icon-angle-left-orange.png" alt="Prev" />
  </button>

  <!-- Slider trong nước -->
  <div class="owl-carousel partner-slider domestic active">
    <?php foreach($partners as $item) { if($item['type_id'] == 1) { ?>
      <div class="partner-item"><a href="<?=$item['link']?>"><img src="/<?=$item['image_url']?>" alt=""></a></div>
   <?php } } ?>
  </div>

  <!-- Slider quốc tế -->
  <div class="owl-carousel partner-slider international">
    <?php foreach($partners as $item) { if($item['type_id'] == 2) { ?>
        <div class="partner-item"><a href="<?=$item['link']?>"><img src="/<?=$item['image_url']?>" alt=""></a></div>
    <?php } } ?>
  </div>
  <!-- Nút điều hướng phải -->
  <button class="m-slider__next m-partner__next js-partner-slider-next">
    <img src="/assets/images/doi-tac/icon-angle-left-orange.png" alt="Next" style="transform: rotate(180deg);" />
  </button>
</div>
</div>


<script>

const $domesticSlider = $(".partner-slider.domestic").owlCarousel({
    loop: true,
    margin: 20,
    nav: false,
    dots: false,
    autoplay: true,
    autoplayTimeout: 3000,
    responsive: {
      0: { items: 2 },
      600: { items: 4 },
      1000: { items: 6 }
    }
  });

  const $intlSlider = $(".partner-slider.international").owlCarousel({
    loop: true,
    margin: 20,
    nav: false,
    dots: false,
    autoplay: true,
    autoplayTimeout: 3000,
    responsive: {
      0: { items: 2 },
      600: { items: 4 },
      1000: { items: 6 }
    }
  });

  // Tab chuyển đổi
  $(".tab").click(function () {
    let tab = $(this).data("tab");
    $(".tab").removeClass("active");
    $(this).addClass("active");

    $(".partner-slider").removeClass("active").hide();
    $(".partner-slider." + tab).addClass("active").show();
  });

  // Điều hướng nút tùy chỉnh
  $(".js-partner-slider-prev").click(function () {
    if ($(".partner-slider.domestic").hasClass("active")) {
      $domesticSlider.trigger("prev.owl.carousel");
    } else {
      $intlSlider.trigger("prev.owl.carousel");
    }
  });

  $(".js-partner-slider-next").click(function () {
    if ($(".partner-slider.domestic").hasClass("active")) {
      $domesticSlider.trigger("next.owl.carousel");
    } else {
      $intlSlider.trigger("next.owl.carousel");
    }
  });

  $(".partner-slider.international").hide(); // ẩn mặc định
</script>
