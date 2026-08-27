$(document).ready(function () {

    function rbGetAosConfigsSafe() {
        var cfgs = window.RB_AOS_CONFIGS || {};
        return {
            general: cfgs.general || { use: 0 }
        };
    }

    function rbHasAnyAosValue(cfg) {
        if (!cfg) return false;
        if (cfg.aos === null || cfg.aos === undefined) return false;
        if (String(cfg.aos).trim() === '') return false;
        return true;
    }

    function rbPickGeneralConfig() {
        var cfg = rbGetAosConfigsSafe().general;
        if (!cfg || cfg.use !== 1) return null;
        if (!rbHasAnyAosValue(cfg)) return null;
        return cfg;
    }

    function rbSetAttrIfMissing(el, name, val) {
        if (!el || el.nodeType !== 1) return;
        if (el.hasAttribute(name)) return;
        if (val === null || val === undefined) return;
        var s = String(val);
        if (s === '') return;
        el.setAttribute(name, s);
    }

    function rbApplyAosFillOnly(el) {
        var cfg = rbPickGeneralConfig();
        if (!cfg) return;

        var rect = el.getBoundingClientRect();
        var inViewport = rect.top < window.innerHeight && rect.bottom > 0;
        if (inViewport && !el.hasAttribute('data-aos')) return;


        rbSetAttrIfMissing(el, 'data-aos', cfg.aos);
        rbSetAttrIfMissing(el, 'data-aos-offset', cfg.offset);
        rbSetAttrIfMissing(el, 'data-aos-delay', cfg.delay);
        rbSetAttrIfMissing(el, 'data-aos-duration', cfg.duration);
        rbSetAttrIfMissing(el, 'data-aos-easing', cfg.easing);
        rbSetAttrIfMissing(el, 'data-aos-mirror', cfg.mirror);
        rbSetAttrIfMissing(el, 'data-aos-once', cfg.once);
        rbSetAttrIfMissing(el, 'data-aos-anchor-placement', cfg.anchorPlacement);
    }

    function rbApplyAosToTargets(scope) {
        var root = scope || document;
        var els = root.querySelectorAll('.rb_section_title, .rb_layout_box');
        if (!els || !els.length) return;
        els.forEach(function (el) {
            rbApplyAosFillOnly(el);
        });
    }

    function rbAosRefresh() {
        if (!rbPickGeneralConfig()) return;

        if (window.AOS && typeof AOS.refreshHard === 'function') AOS.refreshHard();
        else if (window.AOS && typeof AOS.refresh === 'function') AOS.refresh();
    }

    rbApplyAosToTargets(document);

    function finalizeLayoutReady() {
        if (typeof initializeAllSliders === "function") initializeAllSliders($(document));
        requestAnimationFrame(function () {
            document.documentElement.classList.remove('rb-swiper-boot');
            if (typeof refreshStandaloneSwipers === "function") refreshStandaloneSwipers($(document));
            if (typeof initializeCalendar === "function") initializeCalendar();
            rbAosRefresh();
            var root = document.querySelector('.flex_box');
            if (root) root.classList.add('is_ready');
        });
    }

    var layoutRoot = document.querySelector('.flex_box');
    var hasServerRenderedModules = layoutRoot && layoutRoot.querySelector(':scope > .rb_layout_box, :scope > .rb_section_box');
    if (hasServerRenderedModules) {
        packModulesIntoSectionsOnce();
        processFlexBoxesOnce($(layoutRoot), function () {
            finalizeLayoutReady();
        });
        return;
    }

    function getBasePathFromG5() {
        try {
            var u = new URL(typeof g5_url === 'string' ? g5_url : '/', window.location.origin);
            return (u.pathname || '/').replace(/\/+$/, '');
        } catch (e) {
            return '';
        }
    }

    function getPathRelativeToBase() {
        var base = getBasePathFromG5();
        var cur = window.location.pathname || '/';
        if (base && cur.indexOf(base) === 0) {
            cur = cur.slice(base.length);
            if (!cur.startsWith('/')) cur = '/' + cur;
        }
        return cur.replace(/^\/+/, '').replace(/\/{2,}/g, '/');
    }

    function getGETFromRewrite() {
        var path = getPathRelativeToBase();
        var qsParams = new URLSearchParams(window.location.search);
        var qsObj = Object.fromEntries(qsParams.entries());

        var rules = [
            {
                re: /^content\/([0-9a-zA-Z_]+)\/?$/,
                map: m => ({
                    co_id: m[1],
                    rewrite: '1'
                })
            },
            {
                re: /^content\/([^\/]+)\/$/,
                map: m => ({
                    co_seo_title: m[1],
                    rewrite: '1'
                })
            },
            {
                re: /^rss\/([0-9a-zA-Z_]+)\/?$/,
                map: m => ({
                    bo_table: m[1]
                })
            },
            {
                re: /^([0-9a-zA-Z_]+)\/write$/,
                map: m => ({
                    bo_table: m[1],
                    rewrite: '1'
                })
            },
            {
                re: /^([0-9a-zA-Z_]+)\/([0-9]+)\/?$/,
                map: m => ({
                    bo_table: m[1],
                    wr_id: m[2],
                    rewrite: '1'
                })
            },
            {
                re: /^([0-9a-zA-Z_]+)\/([^\/]+)\/$/,
                map: m => ({
                    bo_table: m[1],
                    wr_seo_title: m[2],
                    rewrite: '1'
                })
            },
            {
                re: /^([0-9a-zA-Z_]+)\/?$/,
                map: m => ({
                    bo_table: m[1],
                    rewrite: '1'
                })
            },
        ];

        var fromPath = {};
        for (var i = 0; i < rules.length; i++) {
            var m = path.match(rules[i].re);
            if (m) {
                fromPath = rules[i].map(m);
                break;
            }
        }
        return Object.assign({}, fromPath, qsObj);
    }

    function toQueryString(obj) {
        var usp = new URLSearchParams();
        Object.keys(obj || {}).forEach(function (k) {
            if (obj[k] !== undefined && obj[k] !== null) usp.append(k, obj[k]);
        });
        var s = usp.toString();
        return s ? ('?' + s) : '';
    }

    window._GET = getGETFromRewrite();

    function processFlexBoxesOnce($scope, callback) {
        var flexBoxes = $scope.find('.flex_box').addBack('.flex_box').filter(function () {
            var $box = $(this);
            if ($box.data('layout-loaded')) return false;
            if ($box.closest('.rb_section_box').length) return false;
            return true;
        });

        var layoutNumbers = [];
        var seq = 0;
        flexBoxes.each(function () {
            var $box = $(this);
            var lay = String($box.attr('data-layout') || '').trim();

            if (!lay) {
                seq += 1;
                lay = String(seq);
                $box.attr('data-layout', lay);
            }

            if (layoutNumbers.indexOf(lay) === -1) layoutNumbers.push(lay);
        });

        if (!layoutNumbers.length) {
            if (callback) callback();
            return;
        }

        var qs = toQueryString(getGETFromRewrite());

        $.ajax({
            url: g5_url + '/rb/rb.config/ajax.layout_set.php' + qs,
            method: 'POST',
            dataType: 'json',
            data: {
                layouts: layoutNumbers,
                is_index: is_index
            },
            success: function (res) {
                flexBoxes.each(function () {
                    var $box = $(this);
                    var lay = String($box.attr('data-layout') || '').trim();

                    if (res[lay] !== undefined) {
                        $box.html(res[lay]);

                        rbApplyAosToTargets($box[0]);

                        $box.data('layout-loaded', true);

                        if (typeof initializeAllSliders === "function") initializeAllSliders($box);
                        if (typeof refreshStandaloneSwipers === "function") refreshStandaloneSwipers($box);
                    }
                });

                packModulesIntoSectionsOnce();

                rbApplyAosToTargets(document);
                rbAosRefresh();

                if (callback) callback();
            },
            error: function () {
                console.error('Layout load failed');
                if (callback) callback();
            }
        });
    }

    function packModulesIntoSectionsOnce() {
        $('.rb_section_box').each(function () {
            var $sec = $(this);
            var secUid = String($sec.attr('data-sec-uid') || '').trim();
            if (!secUid) return;

            var $inner = $sec.children('.flex_box').first();
            if (!$inner.length) return;
            var layout = String($sec.attr('data-layout') || '').trim();

            var $cand = $('.rb_layout_box').filter(function () {
                var $m = $(this);
                var mUid = String($m.attr('data-sec-uid') || '').trim();
                var outside = ($m.closest('.rb_section_box').length === 0);
                return !!mUid && mUid === secUid && outside;
            });

            if ($cand.length) {
                $inner.append($cand);
            }

            var modules = $inner.children('.rb_layout_box').get();
            modules.sort(function (a, b) {
                var aOrder = parseInt(a.getAttribute('data-order-id') || '0', 10);
                var bOrder = parseInt(b.getAttribute('data-order-id') || '0', 10);
                return aOrder - bOrder;
            });

            $(modules).each(function () {
                if (layout) {
                    $(this).attr('data-layout', layout);
                }
                $inner.append(this);
            });
        });
    }

    processFlexBoxesOnce($('body'), function () {
        if ($('.flex_box').filter(function(){ return !$(this).data('layout-loaded'); }).length) {
            processFlexBoxesOnce($('body'), done);
        } else {
            done();
        }

        function done() {
            finalizeLayoutReady();
        }
    });
});


function initializeAllSliders($scope) {
    const $root = $scope && $scope.length ? $scope : $(document);

    $root.find('.rb_swiper').addBack('.rb_swiper').each(function () {
        const $slider = $(this);
        if ($slider.data('rb-swiper-initializing')) return;

        $slider.data('rb-swiper-initializing', true);
        const result = setupResponsiveSlider($slider);
        $slider.removeData('rb-swiper-initializing');

        if (result === true) {
            $slider.data('rb-swiper-queued', true).removeData('rb-swiper-retry-count');
            return;
        }

        $slider.removeData('rb-swiper-queued');

        // 라이브러리 로딩 순서가 늦은 경우에만 제한적으로 재시도합니다.
        if (result === false) {
            const retryCount = parseInt($slider.data('rb-swiper-retry-count'), 10) || 0;
            if (retryCount < 20) {
                $slider.data('rb-swiper-retry-count', retryCount + 1);
                window.setTimeout(function () {
                    initializeAllSliders($slider);
                }, 50);
            }
        }
    });
}

function refreshStandaloneSwipers($scope) {
    const $root = $scope && $scope.length ? $scope : $(document);
    $root.find('[class*="swiper-container-slide_display"]').addBack('[class*="swiper-container-slide_display"]').each(function () {
        if (!this.swiper) return;
        if (this.swiper.navigation && typeof this.swiper.navigation.update === 'function') {
            this.swiper.navigation.update();
        }
        if (typeof this.swiper.update === 'function') {
            this.swiper.update();
        }
    });
}

function rbModuleSwiperMode() {
    return window.innerWidth <= 1024 ? 'mo' : 'pc';
}

function rbModuleSwiperIsVisible(element) {
    if (!element) return false;
    return element.getClientRects().length > 0;
}

function setupResponsiveSlider($rb_slider, forceRebuild) {
    const sliderEl = $rb_slider && $rb_slider.length ? $rb_slider[0] : null;
    const innerEl = sliderEl ? sliderEl.querySelector('.rb_swiper_inner') : null;
    const wrapperEl = sliderEl ? sliderEl.querySelector('.rb-swiper-wrapper') : null;

    if (!sliderEl || !innerEl || !wrapperEl) return false;
    if (!rbModuleSwiperIsVisible(sliderEl)) return null;
    if (typeof Swiper !== 'function' || typeof window.rbBuildSwiperPages !== 'function') return false;

    const mode = rbModuleSwiperMode();
    const state = $rb_slider.data('rb-module-swiper-state') || null;

    if (!forceRebuild && state && state.mode === mode && state.instance && !state.instance.destroyed) {
        state.instance.update();
        $rb_slider
            .removeClass('rb-swiper-pending rb-swiper-pregrid rb-swiper-spinner-timeout')
            .addClass('rb-swiper-ready')
            .data('rb-swiper-queued', true);
        return true;
    }

    if (state && state.instance && !state.instance.destroyed) {
        state.instance.destroy(true, true);
    }

    if (innerEl.swiper && (!state || innerEl.swiper !== state.instance) && !innerEl.swiper.destroyed) {
        innerEl.swiper.destroy(true, true);
    }

    $rb_slider.removeData('rb-module-swiper-state').removeData('rb-swiper-instance');

    const pageInfo = window.rbBuildSwiperPages(sliderEl, {
        mode: mode,
        force: true,
        pregrid: false,
    });

    if (!pageInfo) {
        // 게시물이 없는 모듈은 no_data 영역을 그대로 노출합니다.
        if (!wrapperEl.querySelector('.rb_swiper_list')) {
            $rb_slider
                .removeClass('rb-swiper-pending rb-swiper-pregrid rb-swiper-spinner-timeout')
                .addClass('rb-swiper-ready')
                .data('rb-swiper-queued', true);
            return true;
        }
        return false;
    }

    function getNumber(name, fallback) {
        const value = parseInt($rb_slider.attr('data-' + name), 10);
        return Number.isFinite(value) ? value : fallback;
    }

    const manualSwipe = $rb_slider.attr('data-' + mode + '-swap') == '1';
    const speed = Math.max(0, getNumber(mode + '-speed', getNumber('speed', 400)));
    const nextEl = sliderEl.querySelector('.rb-swiper-next');
    const prevEl = sliderEl.querySelector('.rb-swiper-prev');

    const swiperOptions = {
        slidesPerView: 1,
        slidesPerGroup: 1,
        initialSlide: 0,
        spaceBetween: pageInfo.gap,
        resistanceRatio: 0,
        touchRatio: manualSwipe ? 1 : 0,
        allowTouchMove: manualSwipe,
        simulateTouch: manualSwipe,
        speed: speed,
        watchOverflow: true,
        roundLengths: true,
        autoHeight: false,
        autoplay: $rb_slider.attr('data-autoplay') == '1' ? {
            delay: getNumber('autoplay-time', 3000) || 3000,
            disableOnInteraction: false,
        } : false,
    };

    if (nextEl && prevEl) {
        swiperOptions.navigation = {
            nextEl: nextEl,
            prevEl: prevEl,
        };
    }

    const swiperInstance = new Swiper(innerEl, swiperOptions);

    $rb_slider
        .data('rb-module-swiper-state', {
            mode: mode,
            instance: swiperInstance,
            pageSize: pageInfo.pageSize,
            pageCount: pageInfo.pageCount,
        })
        .data('rb-swiper-instance', swiperInstance)
        .data('rb-swiper-queued', true)
        .removeClass('rb-swiper-pending rb-swiper-pregrid rb-swiper-spinner-timeout')
        .addClass('rb-swiper-ready');

    return true;
}


(function ($) {
    let resizeFrame = 0;

    $(window)
        .off('resize.rbModuleSwiperLayout')
        .on('resize.rbModuleSwiperLayout', function () {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(function () {
                $('.rb_swiper').each(function () {
                    const $slider = $(this);
                    if (!rbModuleSwiperIsVisible(this)) return;

                    const state = $slider.data('rb-module-swiper-state') || null;
                    const modeChanged = state && state.mode !== rbModuleSwiperMode();
                    setupResponsiveSlider($slider, !!modeChanged);
                });
            });
        });
})(jQuery);
