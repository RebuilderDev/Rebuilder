(function($) {
    $.fn.rb_carousel = function(rb_userOptions) {
        var rb_defaults = {
            speed: 600,
            autoRollingTime: 6000,
            type: 'fade'
        };
        $.extend(rb_defaults, rb_userOptions);

        return this.each(function() {
            var rb_container = $(this);
            var rb_imageWrapper = rb_container.find('.rb_carousel_img');
            var rb_slides = rb_imageWrapper.find('li');
            var rb_btnPrev = rb_container.find('.rb_carousel_btn_prev');
            var rb_btnNext = rb_container.find('.rb_carousel_btn_next');
            var rb_slideCount = rb_slides.length;

            rb_container.css('position', 'relative');

            var rb_btnWrapper = rb_container.find('.rb_carousel_btn');
            if (rb_btnWrapper.find('li').length == 0) {
                for (var i = 0; i < rb_slideCount; i++) {
                    rb_btnWrapper.append('<li>' + (i + 1) + '</li>')
                }
            };

            var rb_paginationBtns = rb_container.find('.rb_carousel_btn li');
            var rb_currentIndex = 0;
            var rb_prevIndex = 0;
            var rb_speed = rb_defaults.speed;
            var rb_autoRollingTime = rb_defaults.autoRollingTime;
            var rb_direction = "next";
            var rb_userInteracted = 0;
            var rb_transitionType = rb_defaults.type;
            var rb_easing = rb_defaults.easing;
            var rb_canAnimate = 1;
            var rb_autoRollingInterval;

            if (rb_transitionType == 'slide') {
                rb_slides.eq(0).nextAll().css({
                    'left': '100%',
                    'display': 'block'
                })
            }

            setTimeout(function() {
                rb_slides.eq(0).addClass('on motion')
            }, 100);

            if (rb_slideCount == 1) {
                rb_btnPrev.hide();
                rb_btnNext.hide();
                rb_btnWrapper.hide();
                return
            }

            rb_container.bind('mouseenter', function() {
                rb_container.addClass('on')
            }).bind('mouseleave', function() {
                rb_container.removeClass('on')
            });

            rb_paginationBtns.eq(rb_currentIndex).addClass('on');

            rb_paginationBtns.bind('click', function() {
                var rb_clickedIndex = rb_paginationBtns.index($(this));
                if (rb_clickedIndex > rb_currentIndex) {
                    rb_direction = "next"
                } else {
                    rb_direction = "prev"
                };
                if (rb_currentIndex != rb_clickedIndex) {
                    rb_currentIndex = rb_clickedIndex;
                    rb_changeSlide()
                };
                rb_userInteracted = 1
            });

            rb_btnPrev.bind('click', function() {
                if (rb_canAnimate) {
                    rb_direction = "prev";
                    rb_currentIndex--;
                    if (rb_currentIndex < 0) {
                        rb_currentIndex = rb_slideCount - 1
                    };
                    rb_userInteracted = 1;
                    rb_canAnimate = 0;
                    rb_changeSlide()
                }
            });

            rb_btnNext.bind('click', function() {
                if (rb_canAnimate) {
                    rb_direction = "next";
                    rb_currentIndex++;
                    if (rb_currentIndex > rb_slideCount - 1) {
                        rb_currentIndex = 0
                    };
                    rb_userInteracted = 1;
                    rb_canAnimate = 0;
                    rb_changeSlide()
                }
            });

            $.easing.easeInOutExpo = function(rb_x, rb_t, rb_b, rb_c, rb_d) {
                if (rb_t == 0) {
                    return rb_b
                }
                if (rb_t == rb_d) {
                    return rb_b + rb_c
                }
                if ((rb_t /= rb_d / 2) < 1) {
                    return rb_c / 2 * Math.pow(2, 10 * (rb_t - 1)) + rb_b
                }
                return rb_c / 2 * (-Math.pow(2, -10 * --rb_t) + 2) + rb_b
            };

            function rb_changeSlide() {
                var rb_currentSlide = rb_slides.eq(rb_currentIndex);
                var rb_previousSlide = rb_slides.eq(rb_prevIndex);

                if (rb_transitionType == "slide") {
                    if (rb_direction == "next") {
                        rb_currentSlide.css('left', '100%').stop().animate({
                            'left': '0'
                        }, rb_speed, 'easeInOutExpo');
                        rb_previousSlide.css('left', '0').stop().animate({
                            'left': '-100%'
                        }, rb_speed, 'easeInOutExpo', function() {
                            rb_canAnimate = 1
                        })
                    } else {
                        rb_currentSlide.css('left', '-100%').stop().animate({
                            'left': '0'
                        }, rb_speed, 'easeInOutExpo');
                        rb_previousSlide.css('left', '0').stop().animate({
                            'left': '100%'
                        }, rb_speed, 'easeInOutExpo', function() {
                            rb_canAnimate = 1
                        })
                    }
                } else if (rb_transitionType == "fade") {
                    rb_currentSlide.stop(true, true).fadeIn(rb_speed);
                    setTimeout(function() {
                        rb_previousSlide.stop(true, true).fadeOut(rb_speed)
                    }, 100);
                    rb_canAnimate = 1
                } else {
                    rb_canAnimate = 1
                }

                rb_slides.removeClass('on');
                rb_currentSlide.addClass('on');
                rb_slides.removeClass('motion');
                setTimeout(function() {
                    rb_currentSlide.addClass('motion')
                }, 100);
                rb_paginationBtns.removeClass('on');
                rb_paginationBtns.eq(rb_currentIndex).addClass('on');
                rb_prevIndex = rb_currentIndex
            };

            function rb_autoRolling() {
                if (rb_userInteracted == 0) {
                    rb_direction = "next";
                    rb_currentIndex++;
                    if (rb_currentIndex >= rb_slideCount) {
                        rb_currentIndex = 0
                    };
                    rb_changeSlide()
                };
                rb_userInteracted = 0
            };

            rb_autoRollingInterval = setInterval(rb_autoRolling, rb_autoRollingTime)
        })
    }
})(jQuery);
