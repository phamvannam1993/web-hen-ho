$(document).ready(function () {

    $('.table-responsive').on('click', '.maps.js-show', function(e){
     e.preventDefault();
       var mapId =  $(this).data('id');
        // $('.show-maps').not('.map-'+mapId).slideUp();
        $('.show-maps.map-'+mapId).slideToggle();
       
    });
    //select date


    //remove tag empty

    //fixed

    //rating
    randomCode();
    if ($(".code_security").length > 0) {
        $(".code_security").on("cut copy paste", function (e) {
            e.preventDefault();
        });
    }
    if ($(".check_bot").length > 0) {
        $(".check_bot").on("cut copy paste", function (e) {
            e.preventDefault();
        });
        $(".check_bot").hover(function () {
            return false;
        });
    }

    $('#star_rating span').click(function () {
        $('#star_rating span').removeClass('active_star');
        var location = $(this).index();
        $('.m-respond-form .star').attr('value', location + 1);
        // alert(location);
        for (var i = 0; i <= location; i++) {
            $("#star_rating span").eq(i).addClass('active_star');
        }
    });
//get like

    $(".btn_like").click(function () {
        var id, str_id;
        id = $(this).attr('data-href');
        str_id = "#like_" + id;
        var num_like = $('.display_like_' + id).attr('value');
        $(str_id + " " + ".check").attr('value', id);
        if ($(str_id).hasClass('m-comment__item--link')) {
            num_like = --num_like;
            if (num_like)
                $('.display_like_' + id).attr('value', num_like);
            else
                $('.display_like_' + id).attr('value', '');
        } else {
            if (num_like == "") {
                $('.display_like_' + id).attr('value', "1");
            } else {
                num_like = ++num_like;
                $('.display_like_' + id).attr('value', num_like);
            }
        }
        $(str_id).toggleClass('m-comment__item--link');
        // alert(str_id);
        var url = "?mod=course&act=detail"; // the script where you handle the form input.

        $.ajax({
            type: "POST",
            url: url,
            data: $(str_id).serialize(), // serializes the form's elements.
            success: function (data) {
                // alert(data); // show response from the php script.
            }
        });

    });
    if ($('.js-gallery').length) {
        galleryHandle();
    }
    appendSlider('id', {
        loop: false,
        items: 1,
        nav: false,
        dots: true,
        autoplay: true,
        autoplayTimeout: 6000,
        smartSpeed: 800
    });
    appendSlider('intro', {
        loop: false,
        nav: false,
        dots: false,
        margin: 30,
        responsive: {
            0: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });
    appendSlider('intro-2', {
        loop: false,
        nav: false,
        dots: false,
        margin: 30,
        responsive: {
            0: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            }
        }
    });
    appendSlider('intro-3', {
        loop: false,
        nav: false,
        dots: false,
        margin: 30,
        autoplay: true,
        autoplayTimeout: 3000,
        smartSpeed: 800,
        responsive: {
            0: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            }
        }
    });
    appendSlider('member', {
        loop: true,
        nav: false,
        dots: false,
        margin: 30,
        autoplay: true,
        autoplayTimeout: 3000,
        smartSpeed: 1000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1
            },
            768: {
                items: 2
            },
            992: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });
    appendSlider('partner', {
        loop: true,
        nav: false,
        dots: false,
        margin: 30,
        autoplay: true,
        autoplayTimeout: 2000,
        smartSpeed: 1000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 2
            },
            575: {
                items: 3
            },
            768: {
                items: 4
            },
            992: {
                items: 5
            },
            1200: {
                items: 6
            }
        }
    });

    var testimonialSlider = $('.js-testimonial-slider');
    testimonialSlider.owlCarousel({
        loop: false,
        items: 1,
        dots: true,
        margin: 30,
        autoplay: true,
        autoplayTimeout: 5000,
        smartSpeed: 900
    });

    $('.js-datepicker').datepicker();

    if ($('#js-deadline').length) {
        const deadline = $('#js-deadline').data('deadline');
        initialClock('js-deadline', deadline);
    }
    $(".click_me").click(function () {
        $(".click_me").toggleClass("show");
    });
});

function initialClock(id, endtime) {
    var clock = document.getElementById(id);
    if (lang_code == 'vn') {
        var day = "NgĂ y";
        var hour = "Giá»";
        var minute = "PhĂºt";
        var second = "GiĂ¢y";
    }
    if (lang_code == 'en') {
        var day = "Day";
        var hour = "Hour";
        var minute = "Minute";
        var second = "Seconds";
    }

    if (!clock) {
        return;
    }
    var timeinterval = setInterval(function () {
        var t = getTimeRemaining(endtime);
        clock.innerHTML = `
<div class="id-time">
<div class="id-time__group">
    <div class="id-time__cell">${Math.floor(t.days / 10)}</div>
    <div class="id-time__cell">${t.days % 10}</div>
    <div class="id-time__text">${day}</div>
</div>
<div class="id-time__dots">
    <span></span>
    <span></span>
</div>
<div class="id-time__group">
    <div class="id-time__cell">${Math.floor(t.hours / 10)}</div>
    <div class="id-time__cell">${t.hours % 10}</div>
    <div class="id-time__text">` + hour + `</div>
</div>
<div class="id-time__dots">
    <span></span>
    <span></span>
</div>
<div class="id-time__group">
    <div class="id-time__cell">${Math.floor(t.minutes / 10)}</div>
    <div class="id-time__cell">${t.minutes % 10}</div>
    <div class="id-time__text">${minute}</div>
</div>
<div class="id-time__dots">
    <span></span>
    <span></span>
</div>
<div class="id-time__group">
    <div class="id-time__cell">${Math.floor(t.seconds / 10)}</div>
    <div class="id-time__cell">${t.seconds % 10}</div>
    <div class="id-time__text">${second}</div>
</div>
</div>
    `;
        if (t.total <= 0) {
            clearInterval(timeinterval);
        }
    }, 1000);
}

function getTimeRemaining(endtime) {
    var t = Date.parse(endtime) - Date.parse(new Date());
    if (t < 0) return {
        'total': 0,
        'days': 0,
        'hours': 0,
        'minutes': 0,
        'seconds': 0
    };
    var seconds = Math.floor(t / 1000 % 60);
    var minutes = Math.floor(t / 1000 / 60 % 60);
    var hours = Math.floor(t / (1000 * 60 * 60) % 24);
    var days = Math.floor(t / (1000 * 60 * 60 * 24));
    if (days > 99) {
        days = 99;
    }
    return {
        'total': t,
        'days': days,
        'hours': hours,
        'minutes': minutes,
        'seconds': seconds
    };
}

// owl carousel appending function
const appendSlider = (sliderName = '', option = {}) => {
    const slider = $('.js-' + sliderName + '-slider');
    if (slider.length) {
        slider.owlCarousel(option);
        $('.js-' + sliderName + '-slider-prev').on('click', function () {
            $(this).siblings('.js-' + sliderName + '-slider').trigger('prev.owl');
        });
        $('.js-' + sliderName + '-slider-next').on('click', function () {
            $(this).siblings('.js-' + sliderName + '-slider').trigger('next.owl');
        });
    }
};

const galleryHandle = function () {
    var gallery = $('.js-gallery');
    var galleryItem = $('.js-gallery-item');
    var length = galleryItem.length;
    var permission = true;
    var header = $('.js-header');
    var footer = $('.js-footer');
    footer.addClass('is-hide').hide();
    galleryItem.eq(0).addClass('active up-scroll');
    gallery.on('mousewheel', function (e) {
        if (!permission) {
            return;
        }
        permission = false;
        if (e.originalEvent.deltaY > 0) {
            // scroll up
            nextItem(header, footer, length);
        } else {
            // scroll down
            prevItem(header, footer, length);
        }
        setTimeout(function () {
            permission = true;
        }, 500);
    });
    $('.js-gallery-prev').on('click', function () {
        prevItem();
    });
    $('.js-gallery-next').on('click', function () {
        nextItem();
    });
};

const nextItem = function () {
    var galleryItem = $('.js-gallery-item');
    var length = galleryItem.length;
    var header = $('.js-header');
    var footer = $('.js-footer');
    if (galleryItem.eq(0).hasClass('active') && !header.hasClass('is-hide')) {
        header.addClass('is-hide').slideUp(600);
    } else if (galleryItem.eq(length - 1).hasClass('active') && footer.hasClass('is-hide')) {
        footer.removeClass('is-hide').slideDown(600);
    } else {
        var current = 0;
        galleryItem.each(function (index) {
            if ($(this).hasClass('active')) {
                current = index;
            }
        });
        if (current < galleryItem.length - 1) {
            galleryItem.eq(current).removeClass('active up-scroll').addClass('down-scroll');
            galleryItem.eq(current + 1).addClass('active');
        }
    }
};
const prevItem = function () {
    var galleryItem = $('.js-gallery-item');
    var length = galleryItem.length;
    var header = $('.js-header');
    var footer = $('.js-footer');
    if (galleryItem.eq(length - 1).hasClass('active') && !footer.hasClass('is-hide')) {
        footer.addClass('is-hide').slideUp(600);
    } else if (galleryItem.eq(0).hasClass('active') && header.hasClass('is-hide')) {
        header.removeClass('is-hide').slideDown(600);
    } else {
        var current = 0;
        galleryItem.each(function (index) {
            if ($(this).hasClass('active')) {
                current = index;
            }
        });
        if (current > 0) {
            galleryItem.eq(current).removeClass('active');
            galleryItem.eq(current - 1).removeClass('down-scroll').addClass('active up-scroll');
        }
    }
};

function selected() {
    var v = $('#select_me option:selected').val();
    $("#select_me2" + v).prop("selected", true);
}

function selected2() {
    var v = $('#select_me2 option:selected').val();
    $("#select_me" + v).prop("selected", true);
}

function saveInfo() {
    var url = vncms_url + "?mod=home&act=ajaxRegister";
    var html_er = "";
    var name = "";
    var sex = "";
    var phone = "";
    var email = "";
    if ($("input[name='name']").val())
        name = $("input[name='name']").val();

    if ($("input[name='phone']").val())
        phone = $("input[name='phone']").val();
    if ($("input[name='email']").val())
        email = $("input[name='email']").val();
    if (name == "")
        html_er = html_er + "<p>TĂªn khĂ´ng Ä‘Æ°á»£c trá»‘ng</p>";

    if (phone == "")
        html_er = html_er + "<p>Sá»‘ Ä‘iá»‡n thoáº¡i khĂ´ng Ä‘Æ°á»£c trá»‘ng</p>";
    if (email == "")
        html_er = html_er + "<p>Email khĂ´ng Ä‘Æ°á»£c trá»‘ng</p>";
    if (html_er) {
        html_er = "<p class='title'>Lá»—i ÄÄƒng kĂ­ :</p>" + html_er;
        $(".wp_error").html(html_er);
        $('html,body').animate({
                scrollTop: $(".wp_error").offset().top
            },
            'slow');
        randomCode();
    } else {
        $(".wp_error").html("");
        $(".wp_loading").addClass("show");
        $.ajax({
            url: url,
            type: "POST",
            data: $("#form_register").serialize(),// serializes the form's elements.
            // dataType: "json",
            success: function (data) {
                randomCode();
                if (data != 1 && data != "") {
                    $(".wp_loading").removeClass("show");
                    $(".wp_error").html(data);
                    $('html,body').animate({
                            scrollTop: $(".wp_error").offset().top
                        },
                        'slow');
                    return false;
                }
                if (data == 1) {
                    $(".wp_loading").removeClass("show");
                    $(".wp_sc").removeClass("d-none");
                    $("#form_register").addClass("d-none");
                    $('html,body').animate({
                            scrollTop: $(".wp_sc").offset().top
                        },
                        'fast');
                }

            },
        });
    }


}

function statusVideo(status) {
    var url = vncms_url + "?mod=home&sub=ajax&act=saveStatusVideo";
    $.ajax({
        url: url,
        type: "POST",
        data: {'status': status},// serializes the form's elements.
        // dataType: "json",
        success: function (data) {
            // if (status == 'close')
            //     $('.js-id-video-close').click();
            // if (status == 'open')
            //     $('.js-id-video-btn').click();
        },
    });
}

$(window).on("load", function () {
    if ($('#adver_sidebar').length > 0) {
        var offsetTop = $("#adver_sidebar").offset().top;
        offsetTop -= 15;
        $(this).on("scroll", function () {
            if ($(this).outerWidth() < 992 || !offsetTop) {
                return;
            }

            var banner = $("#adver_sidebar");
            var scrollTop = $(this).scrollTop();
            var aside = $(".m-aside");
            var asideHeight = aside.outerHeight();
            var asideParentHeight = aside.parent().outerHeight();

            if (scrollTop >= offsetTop) {
                var margin = scrollTop - offsetTop;
                var currentMargin = banner.css("marginTop");
                if ((asideParentHeight - asideHeight - margin + parseInt(currentMargin)) > 0) {
                    $(banner).css("marginTop", margin);
                }
            }
        });
    }
});

function randomCode(dfStrLen = "") {
    var maxStr = "";
    if (dfStrLen == "") {
        maxStr = 8;
    } else
        maxStr = dfStrLen;
    if ($(".check_bot").length > 0)
        $(".check_bot").val("");
    if ($(".code_security").length > 0) {
        var str_random = '';
        var char = 'ZXCVBNMASDFGHJKLQWERTYUIOPzxcvbnmasdfghjklqwertyuiop0123456789';
        var lenChar = char.length;
        var index = 0;
        for (var i = 0; i < maxStr; i++) {
            index = Math.floor(Math.random() * lenChar) + 0;
            str_random += char.substr(index, 1);
        }
        $(".code_security").val(str_random);
    }
}