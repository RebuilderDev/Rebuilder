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
        if ($slider.data('rb-swiper-queued')) return;
        $slider.data('rb-swiper-queued', true);
        setupResponsiveSlider($slider);
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
    const sliderEl = $rb_slider[0];
    const innerEl = sliderEl ? sliderEl.querySelector('.rb_swiper_inner') : null;
    const wrapperEl = sliderEl ? sliderEl.querySelector('.rb-swiper-wrapper') : null;
    const nextEl = sliderEl ? sliderEl.querySelector('.rb-swiper-next') : null;
    const prevEl = sliderEl ? sliderEl.querySelector('.rb-swiper-prev') : null;

    if (!innerEl || !wrapperEl || typeof Swiper !== 'function') return;

    function getNumber(name, fallback) {
        const value = parseInt($rb_slider.data(name), 10);
        return Number.isFinite(value) ? value : fallback;
    }

    function prepareDirectSlides() {
        sliderEl.classList.remove('rb-swiper-pregrid');
        wrapperEl.style.removeProperty('display');
        wrapperEl.style.removeProperty('grid-template-columns');
        wrapperEl.style.removeProperty('grid-template-rows');
        wrapperEl.style.removeProperty('grid-auto-flow');
        wrapperEl.style.removeProperty('column-gap');
        wrapperEl.style.removeProperty('row-gap');
        wrapperEl.style.removeProperty('transform');
        wrapperEl.style.removeProperty('transition');

        wrapperEl.querySelectorAll('.rb-swiper-grid-blank').forEach(function (el) {
            el.remove();
        });

        wrapperEl.querySelectorAll('.swiper-slide-duplicate').forEach(function (el) {
            el.remove();
        });

        // 이전 버전에서 생성한 페이지 단위 슬라이드가 남아 있으면 한 번만 원상 복구합니다.
        Array.from(wrapperEl.children).forEach(function (child) {
            if (!child.classList || !child.classList.contains('rb-swiper-slide')) return;
            while (child.firstChild) {
                wrapperEl.insertBefore(child.firstChild, child);
            }
            child.remove();
        });

        Array.from(wrapperEl.children).forEach(function (listEl) {
            if (!listEl.classList || !listEl.classList.contains('rb_swiper_list')) return;
            listEl.classList.add('swiper-slide');
            listEl.style.removeProperty('display');
            listEl.style.removeProperty('width');
            listEl.style.removeProperty('min-width');
            listEl.style.removeProperty('margin-right');
            listEl.style.removeProperty('margin-top');
            listEl.style.removeProperty('order');
            listEl.style.removeProperty('-webkit-order');
            listEl.style.removeProperty('-ms-flex-order');
            listEl.style.removeProperty('-moz-box-ordinal-group');
            listEl.style.removeProperty('-webkit-box-ordinal-group');
        });
    }

    function getRealSlides() {
        return Array.from(wrapperEl.children).filter(function (el) {
            return el.classList && el.classList.contains('rb_swiper_list');
        });
    }

    function getEffectiveRows(rows, cols, slideCount) {
        const safeRows = Math.max(1, parseInt(rows, 10) || 1);
        const safeCols = Math.max(1, parseInt(cols, 10) || 1);
        const safeCount = Math.max(0, parseInt(slideCount, 10) || 0);

        if (!safeCount) return 1;
        return Math.max(1, Math.min(safeRows, Math.ceil(safeCount / safeCols)));
    }

    function syncGridBlanks(rows, cols) {
        wrapperEl.querySelectorAll('.rb-swiper-grid-blank').forEach(function (el) {
            el.remove();
        });

        const realSlides = getRealSlides();
        const effectiveRows = getEffectiveRows(rows, cols, realSlides.length);
        const pageSize = Math.max(1, effectiveRows * cols);
        const remainder = realSlides.length % pageSize;
        const blankCount = remainder === 0 ? 0 : pageSize - remainder;

        if (!blankCount) return;

        const fragment = document.createDocumentFragment();
        for (let i = 0; i < blankCount; i++) {
            const blank = document.createElement('div');
            blank.className = 'rb-swiper-grid-blank swiper-slide swiper-slide-invisible-blank';
            blank.setAttribute('aria-hidden', 'true');
            blank.setAttribute('tabindex', '-1');
            fragment.appendChild(blank);
        }
        wrapperEl.appendChild(fragment);
    }

    function syncSingleColumnOrder(rows, cols, gap) {
        const slides = Array.from(wrapperEl.children).filter(function (el) {
            return el.classList && el.classList.contains('swiper-slide');
        });

        slides.forEach(function (slide) {
            slide.style.removeProperty('order');
            slide.style.removeProperty('-webkit-order');
            slide.style.removeProperty('-ms-flex-order');
            slide.style.removeProperty('-moz-box-ordinal-group');
            slide.style.removeProperty('-webkit-box-ordinal-group');
        });

        if (cols !== 1 || rows <= 1 || !slides.length) return;

        const pageCount = Math.max(1, Math.ceil(slides.length / rows));
        slides.forEach(function (slide, index) {
            const page = Math.floor(index / rows);
            const row = index % rows;
            const order = row * pageCount + page;
            slide.style.order = order;
            slide.style.webkitOrder = order;
            slide.style.msFlexOrder = order;
            slide.style.marginTop = row === 0 || gap <= 0 ? '' : gap + 'px';
        });
    }

    function syncColumnLayout(instance) {
        if (!instance || instance.destroyed) return;

        const rows = Math.max(1, parseInt(instance.params.slidesPerColumn, 10) || 1);
        instance.params.slidesPerColumnFill = 'row';
        innerEl.classList.toggle('swiper-container-multirow', rows > 1);
        innerEl.classList.remove('swiper-container-multirow-column');
    }

    if (swiperInstance && !swiperInstance.destroyed) {
        syncColumnLayout(swiperInstance);
        const currentRows = Math.max(1, parseInt(swiperInstance.params.slidesPerColumn, 10) || 1);
        const currentCols = Math.max(1, parseInt(swiperInstance.params.slidesPerView, 10) || 1);
        syncGridBlanks(currentRows, currentCols);
        const currentGap = Math.max(0, parseFloat(swiperInstance.params.spaceBetween) || 0);
        syncSingleColumnOrder(currentRows, currentCols, currentGap);
        swiperInstance.update();
        syncSingleColumnOrder(currentRows, currentCols, currentGap);
        $rb_slider.removeClass('rb-swiper-pending').addClass('rb-swiper-ready');
        return;
    }

    prepareDirectSlides();

    const moCols = Math.max(1, getNumber('mo-w', 1));
    const moRows = getEffectiveRows(getNumber('mo-h', 1), moCols, getRealSlides().length);
    const moGap = Math.max(0, getNumber('mo-gap', 0));
    const moSpeed = Math.max(0, getNumber('mo-speed', getNumber('speed', 400)));
    const pcCols = Math.max(1, getNumber('pc-w', 1));
    const pcRows = getEffectiveRows(getNumber('pc-h', 1), pcCols, getRealSlides().length);
    const pcGap = Math.max(0, getNumber('pc-gap', 0));
    const pcSpeed = Math.max(0, getNumber('pc-speed', getNumber('speed', 400)));

    const initialRows = window.innerWidth <= 1024 ? moRows : pcRows;
    const initialCols = window.innerWidth <= 1024 ? moCols : pcCols;
    syncGridBlanks(initialRows, initialCols);
    syncSingleColumnOrder(initialRows, initialCols, window.innerWidth <= 1024 ? moGap : pcGap);

    swiperInstance = new Swiper(innerEl, {
        slidesPerView: moCols,
        slidesPerColumn: moRows,
        slidesPerColumnFill: 'row',
        slidesPerGroup: moCols,
        initialSlide: 0,
        spaceBetween: moGap,
        resistanceRatio: 0,
        touchRatio: $rb_slider.data('mo-swap') == 1 ? 1 : 0,
        speed: moSpeed,
        watchOverflow: true,
        autoplay: $rb_slider.data('autoplay') == 1 ? {
            delay: getNumber('autoplay-time', 3000) || 3000,
            disableOnInteraction: false,
        } : false,
        navigation: {
            nextEl: nextEl,
            prevEl: prevEl,
        },
        breakpoints: {
            1025: {
                slidesPerView: pcCols,
                slidesPerColumn: pcRows,
                slidesPerColumnFill: 'row',
                slidesPerGroup: pcCols,
                spaceBetween: pcGap,
                touchRatio: $rb_slider.data('pc-swap') == 1 ? 1 : 0,
                speed: pcSpeed,
            },
        },
        on: {
            init: function () {
                syncColumnLayout(this);
            },
            breakpoint: function () {
                const instance = this;
                window.requestAnimationFrame(function () {
                    if (!instance || instance.destroyed) return;
                    syncColumnLayout(instance);
                    const activeRows = Math.max(1, parseInt(instance.params.slidesPerColumn, 10) || 1);
                    const activeCols = Math.max(1, parseInt(instance.params.slidesPerView, 10) || 1);
                    const activeGap = Math.max(0, parseFloat(instance.params.spaceBetween) || 0);
                    syncGridBlanks(activeRows, activeCols);
                    syncSingleColumnOrder(activeRows, activeCols, activeGap);
                    instance.update();
                    syncSingleColumnOrder(activeRows, activeCols, activeGap);
                    instance.slideTo(0, 0, false);
                });
            },
        },
    });

    syncColumnLayout(swiperInstance);
    syncSingleColumnOrder(initialRows, initialCols, window.innerWidth <= 1024 ? moGap : pcGap);
    $rb_slider.removeClass('rb-swiper-pending').addClass('rb-swiper-ready');
    $rb_slider.data('rb-swiper-instance', swiperInstance);
    $rb_slider.removeData('rb-swiper-mode');
}
