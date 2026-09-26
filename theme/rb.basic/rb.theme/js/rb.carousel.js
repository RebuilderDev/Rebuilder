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
            var rb_touch = null;
            var rb_suppressClickUntil = 0;

            if (rb_transitionType == 'slide') {
                rb_slides.eq(0).nextAll().css({
                    'left': '100%',
                    'display': 'block'
                })
            }

            setTimeout(function() {
                rb_slides.eq(rb_currentIndex).addClass('on motion')
            }, 100);

            if (rb_slideCount <= 1) {
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
                if (!rb_canAnimate) return;
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
                rb_moveSlide('prev', true)
            });

            rb_btnNext.bind('click', function() {
                rb_moveSlide('next', true)
            });

            function rb_moveSlide(direction, interacted) {
                if (!rb_canAnimate) return;
                rb_direction = direction;
                rb_currentIndex = (rb_currentIndex + (direction === 'next' ? 1 : -1) + rb_slideCount) % rb_slideCount;
                if (interacted) rb_userInteracted = 1;
                rb_changeSlide()
            }

            // 세로 스크롤과 확대는 브라우저에 맡기고, 한 손가락의 좌우 이동만 처리한다.
            var rb_touchArea = rb_imageWrapper[0];
            rb_touchArea.style.touchAction = 'pan-y pinch-zoom';
            function rb_findTouch(touches, id) {
                for (var i = 0; i < touches.length; i++) {
                    if (touches[i].identifier === id) return touches[i];
                }
                return null;
            }
            rb_touchArea.addEventListener('touchstart', function(event) {
                rb_touch = null;
                if (event.touches.length !== 1 || !rb_canAnimate) return;
                var touch = event.touches[0];
                rb_touch = {id: touch.identifier, x: touch.clientX, y: touch.clientY, axis: ''};
                rb_suppressClickUntil = 0;
                rb_userInteracted = 1;
            }, {passive: true});
            rb_touchArea.addEventListener('touchmove', function(event) {
                if (!rb_touch) return;
                if (event.touches.length !== 1) { rb_touch = null; return; }
                var touch = rb_findTouch(event.touches, rb_touch.id);
                if (!touch) { rb_touch = null; return; }
                var dx = Math.abs(touch.clientX - rb_touch.x);
                var dy = Math.abs(touch.clientY - rb_touch.y);
                if (!rb_touch.axis && Math.max(dx, dy) >= 10) rb_touch.axis = dx > dy * 1.2 ? 'x' : 'y';
                if (rb_touch.axis === 'x' && event.cancelable) event.preventDefault();
            }, {passive: false});
            rb_touchArea.addEventListener('touchend', function(event) {
                if (!rb_touch) return;
                var gesture = rb_touch;
                rb_touch = null;
                if (event.touches.length) return;
                var touch = rb_findTouch(event.changedTouches, gesture.id);
                if (!touch) return;
                var dx = touch.clientX - gesture.x;
                var dy = touch.clientY - gesture.y;
                var horizontal = gesture.axis === 'x' || (!gesture.axis && Math.abs(dx) > Math.abs(dy) * 1.2 && Math.abs(dx) >= 10);
                if (!horizontal) return;
                // 스와이프가 끝난 뒤 합성되는 클릭으로 배너 링크가 열리는 것을 막는다.
                rb_suppressClickUntil = Date.now() + 500;
                var threshold = Math.max(40, Math.min(80, rb_touchArea.clientWidth * 0.08));
                if (Math.abs(dx) >= threshold) rb_moveSlide(dx < 0 ? 'next' : 'prev', true);
            }, {passive: true});
            rb_touchArea.addEventListener('touchcancel', function() {
                rb_touch = null;
            }, {passive: true});
            rb_touchArea.addEventListener('click', function(event) {
                if (Date.now() < rb_suppressClickUntil && event.detail !== 0) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);

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
                rb_canAnimate = 0;
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
                        rb_previousSlide.stop(true, true).fadeOut(rb_speed, function() {
                            rb_canAnimate = 1
                        })
                    }, 100);
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
                if (rb_touch || !rb_canAnimate) return;
                if (rb_userInteracted == 0) {
                    rb_moveSlide('next', false)
                };
                rb_userInteracted = 0
            };

            rb_autoRollingInterval = setInterval(rb_autoRolling, rb_autoRollingTime)
        })
    }
})(jQuery);
