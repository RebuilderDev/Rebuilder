/* Rebuilder 2.2.7.8 - 메인 캐러셀 / 내장 Swiper 5.4.3 */
(function($, window) {
    'use strict';

    /* ===== 사용자 수정 영역 =====
     * effect, speed, autoplay.delay의 null은 테마 설정 패널 값을 사용합니다.
     * 파일에서 값을 지정하면 패널 값보다 우선합니다.
     * 예: effect: 'slide', speed: 800, autoplay: {enabled: true, delay: 5000}
     * 자동재생 끄기: autoplay: false / 터치 끄기: allowTouchMove: false
     * Swiper 5.4.3 옵션을 이 객체에 추가할 수 있습니다. on 이벤트도 추가 가능합니다.
     */
    var RB_CAROUSEL_SWIPER_OPTIONS = {
        effect: null,                       // null / 'fade' / 'slide'
        speed: null,                        // 전환 시간(ms). null은 패널 값
        autoplay: {
            enabled: true,
            delay: null,                    // 자동재생 간격(ms). null은 패널 값
            disableOnInteraction: false,    // 조작 후에도 자동재생 유지
            waitForTransition: true
        },
        loop: true,
        slidesPerView: 1,
        spaceBetween: 0,
        allowTouchMove: true,
        simulateTouch: true,                // PC 마우스 드래그
        grabCursor: false,
        threshold: 10,
        touchStartPreventDefault: false,
        preventClicks: true,
        preventClicksPropagation: true,
        watchOverflow: true,
        fadeEffect: {crossFade: true},
        navigation: {},                    // false로 지정하면 이전/다음 버튼 숨김
        pagination: {clickable: true}       // false로 지정하면 페이지 표시 숨김
    };
    /* ===== 사용자 수정 영역 끝 ===== */

    function rememberStyle(element) {
        var style = element.getAttribute('style');
        return function() {
            if (style === null) element.removeAttribute('style');
            else element.setAttribute('style', style);
        };
    }

    $.fn.rb_carousel = function(userOptions) {
        return this.each(function() {
            var container = this;
            var $container = $(container);
            var previous = $container.data('rbCarouselSwiper');
            if (previous) previous.destroy();
            if (userOptions === 'destroy') return;

            var legacy = $.extend({type: 'fade', speed: 600, autoRollingTime: 6000}, userOptions);
            var $wrapper = $container.children('.rb_carousel_img').first();
            var $slides = $wrapper.children('li');
            var $prev = $container.children('.rb_carousel_btn_prev');
            var $next = $container.children('.rb_carousel_btn_next');
            var $pages = $container.children('.rb_carousel_btn');
            var count = $slides.length;
            if (!$wrapper.length) return;

            // 라이브러리가 없는 구형 사용자 테마에서는 첫 장을 정상적으로 표시한다.
            if (typeof window.Swiper !== 'function' || count === 0) {
                $slides.removeClass('on motion').first().addClass('on motion');
                $prev.add($next).add($pages).hide();
                return;
            }

            var options = $.extend(true, {}, RB_CAROUSEL_SWIPER_OPTIONS, legacy.swiperOptions || {});
            if (options.effect === null) options.effect = legacy.type;
            if (options.speed === null) options.speed = legacy.speed;
            if (options.autoplay === true) options.autoplay = {enabled: true, delay: null};
            if (options.autoplay && options.autoplay.delay === null) options.autoplay.delay = legacy.autoRollingTime;
            if (count === 1) {
                options.loop = false;
                options.autoplay = false;
                options.allowTouchMove = false;
            }

            // reset.css의 공용 Swiper 규칙이 캐러셀에 적용되지 않도록 구조 클래스는 고정한다.
            $.extend(options, {
                containerModifierClass: 'rb-carousel-',
                wrapperClass: 'rb_carousel_img',
                slideClass: 'rb-carousel-slide',
                slideBlankClass: 'rb-carousel-slide-blank',
                slideActiveClass: 'rb-carousel-slide-active',
                slideDuplicateActiveClass: 'rb-carousel-slide-duplicate-active',
                slideVisibleClass: 'rb-carousel-slide-visible',
                slideDuplicateClass: 'rb-carousel-slide-duplicate',
                slideNextClass: 'rb-carousel-slide-next',
                slideDuplicateNextClass: 'rb-carousel-slide-duplicate-next',
                slidePrevClass: 'rb-carousel-slide-prev',
                slideDuplicatePrevClass: 'rb-carousel-slide-duplicate-prev'
            });
            if (options.a11y !== false) {
                options.a11y = $.extend({}, options.a11y, {notificationClass: 'rb-carousel-notification'});
            }

            // 각 캐러셀의 요소를 직접 지정하여 다른 배너나 메뉴의 Swiper와 분리한다.
            var navigation = count > 1 && options.navigation !== false;
            var pagination = count > 1 && options.pagination !== false;
            $prev.add($next).toggle(navigation);
            $pages.empty().toggle(pagination);
            options.navigation = navigation ? $.extend({}, options.navigation, {
                prevEl: $prev[0] || null, nextEl: $next[0] || null,
                disabledClass: 'rb-carousel-button-disabled',
                hiddenClass: 'rb-carousel-button-hidden',
                lockClass: 'rb-carousel-button-lock'
            }) : false;
            options.pagination = pagination ? $.extend({
                bulletClass: 'rb-carousel-page',
                bulletActiveClass: 'on',
                renderBullet: function(index, className) {
                    return '<li class="' + className + '">' + (index + 1) + '</li>';
                }
            }, options.pagination, {
                el: $pages[0] || null,
                bulletClass: 'rb-carousel-page',
                bulletActiveClass: 'on',
                modifierClass: 'rb-carousel-pagination-',
                currentClass: 'rb-carousel-pagination-current',
                totalClass: 'rb-carousel-pagination-total',
                hiddenClass: 'rb-carousel-pagination-hidden',
                progressbarFillClass: 'rb-carousel-pagination-progressbar-fill',
                progressbarOppositeClass: 'rb-carousel-pagination-progressbar-opposite',
                clickableClass: 'rb-carousel-pagination-clickable',
                lockClass: 'rb-carousel-pagination-lock'
            }) : false;

            // 재초기화 때 높이와 Mobile 설정 CSS 변수가 지워지지 않도록 원래 style을 복원한다.
            var restoreStyles = [rememberStyle(container), rememberStyle($wrapper[0])];
            $slides.each(function() { restoreStyles.push(rememberStyle(this)); });
            var timer = null;
            var dragged = false;
            var suppressClickUntil = 0;
            var lastIndex = null;
            var swiper;

            function updateMotion(instance) {
                if (instance.realIndex === lastIndex) return;
                lastIndex = instance.realIndex;
                window.clearTimeout(timer);
                var $all = $wrapper.children('li');
                $all.removeClass('on motion');
                // loop 복제본도 같은 문구/배경 효과를 적용한다.
                var $active = $all.filter(function() {
                    var index = this.getAttribute('data-swiper-slide-index');
                    return index !== null ? Number(index) === instance.realIndex : $slides.index(this) === instance.realIndex;
                }).addClass('on');
                timer = window.setTimeout(function() { $active.addClass('motion'); }, 100);
            }

            function preventDraggedClick(event) {
                if (event.detail !== 0 && Date.now() < suppressClickUntil) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }

            var events = options.on || {};
            function withUserEvent(name, handler) {
                var userHandler = events[name];
                events[name] = function() {
                    handler.apply(this, arguments);
                    if (typeof userHandler === 'function') userHandler.apply(this, arguments);
                };
            }
            withUserEvent('init', function() { updateMotion(this); });
            withUserEvent('slideChange', function() { updateMotion(this); });
            withUserEvent('sliderFirstMove', function() { dragged = true; });
            withUserEvent('touchEnd', function() {
                if (dragged) suppressClickUntil = Date.now() + 500;
                dragged = false;
            });
            withUserEvent('destroy', function() { window.clearTimeout(timer); });
            options.on = events;

            $container.addClass('rb-carousel-swiper');
            $slides.addClass(options.slideClass);
            $wrapper[0].addEventListener('click', preventDraggedClick, true);
            swiper = new window.Swiper(container, options);

            $container.data('rbCarouselSwiper', {
                destroy: function() {
                    window.clearTimeout(timer);
                    $wrapper[0].removeEventListener('click', preventDraggedClick, true);
                    if (!swiper.destroyed) swiper.destroy(true, true);
                    restoreStyles.forEach(function(restore) { restore(); });
                    $container.removeClass('rb-carousel-swiper').removeData('rbCarouselSwiper');
                    $slides.removeClass(options.slideClass + ' on motion');
                    $pages.empty();
                }
            });
        });
    };
})(jQuery, window);
