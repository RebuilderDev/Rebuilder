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
        document.documentElement.classList.remove('rb-swiper-boot');
        requestAnimationFrame(function () {
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
    const immediate = [];
    const deferred = [];

    $root.find('.rb_swiper').addBack('.rb_swiper').each(function () {
        const $slider = $(this);
        if ($slider.data('rb-swiper-queued')) return;
        $slider.data('rb-swiper-queued', true);

        if (rbIsNearViewport(this, 0)) immediate.push(this);
        else deferred.push(this);
    });

    immediate.forEach(function (el) {
        setupResponsiveSlider($(el));
    });

    queueDeferredSliders(deferred);
}

function rbIsNearViewport(el, threshold) {
    if (!el || typeof el.getBoundingClientRect !== 'function') return true;
    const rect = el.getBoundingClientRect();
    const extra = typeof threshold === 'number' ? threshold : 0;
    const viewH = window.innerHeight || document.documentElement.clientHeight || 0;
    return rect.bottom >= -extra && rect.top <= viewH + extra;
}

function queueDeferredSliders(sliders) {
    if (!sliders || !sliders.length) return;

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                observer.unobserve(entry.target);
                window.requestAnimationFrame(function () {
                    setupResponsiveSlider($(entry.target));
                });
            });
        }, {
            rootMargin: '100px 0px 100px 0px'
        });

        sliders.forEach(function (el) {
            observer.observe(el);
        });
        return;
    }

    sliders.forEach(function (el, index) {
        window.setTimeout(function () {
            setupResponsiveSlider($(el));
        }, 60 * (index + 1));
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

function setupResponsiveSlider($rb_slider) {
    let swiperInstance = $rb_slider.data('rb-swiper-instance') || null;
    let currentMode = $rb_slider.data('rb-swiper-mode') || '';
    const sliderEl = $rb_slider[0];
    const innerEl = sliderEl ? sliderEl.querySelector('.rb_swiper_inner') : null;
    const nextEl = sliderEl ? sliderEl.querySelector('.rb-swiper-next') : null;
    const prevEl = sliderEl ? sliderEl.querySelector('.rb-swiper-prev') : null;

    function initSlider(mode) {
        const isMobile = mode === 'mo';
        const rows = parseInt($rb_slider.data(isMobile ? 'mo-h' : 'pc-h'), 10) || 1;
        const cols = parseInt($rb_slider.data(isMobile ? 'mo-w' : 'pc-w'), 10) || 1;
        const gap = parseInt($rb_slider.data(isMobile ? 'mo-gap' : 'pc-gap'), 10) || 0;
        const swap = $rb_slider.data(isMobile ? 'mo-swap' : 'pc-swap') == 1;
        const slidesPerView = rows * cols;

        const speedData = $rb_slider.data(isMobile ? 'mo-speed' : 'pc-speed');
        const speed = speedData !== undefined && speedData !== null && speedData !== ''
            ? parseInt(speedData, 10)
            : (parseInt($rb_slider.data('speed'), 10) || 400);

        $rb_slider.removeClass('rb-swiper-ready').addClass('rb-swiper-pending');
        configureSlides($rb_slider, slidesPerView, cols, gap);

        if (swiperInstance) {
            swiperInstance.destroy(true, true);
        }

        swiperInstance = new Swiper(innerEl, {
            slidesPerView: 1,
            initialSlide: 0,
            spaceBetween: gap,
            resistanceRatio: 0,
            touchRatio: swap ? 1 : 0,
            speed: speed,
            autoplay: $rb_slider.data('autoplay') == 1 ? {
                delay: parseInt($rb_slider.data('autoplay-time'), 10) || 3000,
                disableOnInteraction: false,
            } : false,
            navigation: {
                nextEl: nextEl,
                prevEl: prevEl,
            },
        });

        swiperInstance.update();
        $rb_slider.removeClass('rb-swiper-pending').addClass('rb-swiper-ready');

        $rb_slider.data('rb-swiper-instance', swiperInstance);
        $rb_slider.data('rb-swiper-mode', mode);
    }

    function configureSlides($rb_slider, view, cols, gap) {
        const wrapperEl = sliderEl ? sliderEl.querySelector('.rb-swiper-wrapper') : null;
        if (!wrapperEl) return;

        wrapperEl.querySelectorAll('.swiper-slide-duplicate').forEach(function (el) {
            el.remove();
        });

        Array.from(wrapperEl.children).forEach(function (child) {
            if (!child.classList || !child.classList.contains('rb-swiper-slide')) return;
            while (child.firstChild) {
                wrapperEl.insertBefore(child.firstChild, child);
            }
            child.remove();
        });

        const widthPercentage = `calc(${100 / cols}% - ${(gap * (cols - 1)) / cols}px)`;
        const lists = Array.from(wrapperEl.querySelectorAll('.rb_swiper_list'));
        if (!lists.length) return;

        lists.forEach(function (listEl, index) {
            listEl.style.width = widthPercentage;
            listEl.style.marginRight = ((index + 1) % cols === 0) ? '0' : '';
        });

        for (let start = 0; start < lists.length; start += view) {
            const slideEl = document.createElement('div');
            slideEl.className = 'rb-swiper-slide swiper-slide';
            slideEl.style.gap = `${gap}px`;
            wrapperEl.insertBefore(slideEl, lists[start]);

            for (let idx = start; idx < Math.min(start + view, lists.length); idx++) {
                slideEl.appendChild(lists[idx]);
            }
        }
    }

    function checkModeAndInit() {
        const winWidth = window.innerWidth;
        const mode = winWidth <= 1024 ? 'mo' : 'pc';

        if (currentMode !== mode) {
            currentMode = mode;
            initSlider(mode);
        }
    }

    if (!$rb_slider.data('rb-swiper-bound')) {
        window.__rbSwiperUid = (window.__rbSwiperUid || 0) + 1;
        const resizeNamespace = '.rbSwiper' + window.__rbSwiperUid;
        $(window).on('resize' + resizeNamespace + ' orientationchange' + resizeNamespace, checkModeAndInit);
        $rb_slider.data('rb-swiper-bound', true);
        $rb_slider.data('rb-swiper-resize-ns', resizeNamespace);
    }

    checkModeAndInit();
}

