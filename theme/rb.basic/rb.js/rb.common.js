/* global $, jQuery */
(function (window, $) {
    'use strict';

    // A scrollbar can change the available width without a window resize.
    var viewport = document.documentElement;
    function rbSyncViewportWidth() {
        var width = viewport.clientWidth;
        var widthValue = width + 'px';
        if (viewport.style.getPropertyValue('--vw') === widthValue) return;
        viewport.style.setProperty('--vw', widthValue);
        viewport.style.setProperty('--scrollbar-width', (window.innerWidth - width) + 'px');
    }

    // Keep the member menu outside the clipping containers of builder modules.
    var moduleSideview = null;
    var restoringSideviewFocus = false;

    function rbModuleSideviewTrigger(target) {
        var trigger = target.closest && target.closest('.sv_wrap > .sv_member, .sv_wrap > .sv_guest');
        return trigger && trigger.closest('.rb_layout_box .rb-module-wrap') ? trigger : null;
    }

    function rbCloseModuleSideview(restoreFocus) {
        if (!moduleSideview) return;
        var state = moduleSideview;
        moduleSideview = null;
        state.menu.classList.remove('sv_on');
        if (state.style === null) state.menu.removeAttribute('style');
        else state.menu.setAttribute('style', state.style);
        if (state.marker.parentNode) state.marker.parentNode.replaceChild(state.menu, state.marker);
        state.layer.remove();

        if (restoreFocus === true && state.trigger.isConnected && document.activeElement !== state.trigger) {
            restoringSideviewFocus = true;
            state.trigger.focus({ preventScroll: true });
            state.menu.classList.remove('sv_on');
            restoringSideviewFocus = false;
        }
    }

    function rbOpenModuleSideview(trigger) {
        if (moduleSideview && moduleSideview.trigger === trigger) return;
        rbCloseModuleSideview();

        var menu = trigger.nextElementSibling;
        if (!menu || !menu.classList.contains('sv')) return;

        var layer = document.createElement('span');
        layer.className = 'sv_wrap rb-module-sideview';
        Object.assign(layer.style, { position: 'fixed', left: '0', top: '0', zIndex: '1000' });
        var marker = document.createComment('');
        menu.parentNode.insertBefore(marker, menu);
        moduleSideview = { trigger: trigger, menu: menu, marker: marker, layer: layer, style: menu.getAttribute('style') };
        document.body.appendChild(layer);
        layer.appendChild(menu);

        var gap = 8;
        Object.assign(menu.style, {
            display: 'block', position: 'relative', top: 'auto', left: 'auto', margin: '0', boxSizing: 'border-box',
            maxWidth: Math.max(0, viewport.clientWidth - gap * 2) + 'px',
            maxHeight: Math.max(0, viewport.clientHeight - gap * 2) + 'px'
        });
        if (menu.scrollHeight > menu.clientHeight || menu.scrollWidth > menu.clientWidth) {
            menu.style.overflow = 'auto';
        }

        var anchor = trigger.getBoundingClientRect();
        var size = menu.getBoundingClientRect();
        var top = anchor.bottom + gap;
        if (top + size.height > viewport.clientHeight - gap && anchor.top - size.height - gap >= gap) {
            top = anchor.top - size.height - gap;
        }
        layer.style.left = Math.max(gap, Math.min(anchor.left, viewport.clientWidth - size.width - gap)) + 'px';
        layer.style.top = Math.max(gap, Math.min(top, viewport.clientHeight - size.height - gap)) + 'px';
    }

    function rbHandleModuleSideview(event) {
        if (restoringSideviewFocus || (moduleSideview && moduleSideview.layer.contains(event.target))) return;

        var trigger = rbModuleSideviewTrigger(event.target);
        if (trigger) rbOpenModuleSideview(trigger);
        else rbCloseModuleSideview();
    }

    document.addEventListener('pointerdown', function (event) {
        if (moduleSideview && !moduleSideview.layer.contains(event.target) && !moduleSideview.trigger.contains(event.target)) {
            rbCloseModuleSideview();
        }
    });
    document.addEventListener('click', rbHandleModuleSideview);
    document.addEventListener('focusin', rbHandleModuleSideview);
    document.addEventListener('scroll', function (event) {
        if (moduleSideview && !moduleSideview.layer.contains(event.target)) rbCloseModuleSideview();
    }, true);
    window.addEventListener('resize', rbCloseModuleSideview);
    window.addEventListener('pagehide', rbCloseModuleSideview);

    document.addEventListener('keydown', function (event) {
        if (!moduleSideview) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            rbCloseModuleSideview(true);
        } else if (event.key === 'Tab') {
            var links = moduleSideview.menu.querySelectorAll('a[href]:not([tabindex="-1"])');
            if (!links.length) return;
            if (event.target === moduleSideview.trigger && !event.shiftKey) {
                event.preventDefault();
                links[0].focus();
            } else if (event.shiftKey && event.target === links[0]) {
                event.preventDefault();
                rbCloseModuleSideview(true);
            } else if (!event.shiftKey && event.target === links[links.length - 1]) {
                rbCloseModuleSideview(true);
            }
        }
    });

    window.addEventListener('load', rbSyncViewportWidth);
    if (typeof window.ResizeObserver === 'function') {
        new window.ResizeObserver(rbSyncViewportWidth).observe(viewport);
    }

    if (!$) return; // jQuery 없으면 그냥 리턴

    // 브라우저 Back으로 페이지가 복원될 때 팝업 쿠키 상태를 다시 반영합니다.
    function rbSyncPopupCookies() {
        $('.hd_pops[id^="hd_pops_"]').each(function () {
            var cookieName = this.id;
            var cookieValue = typeof window.get_cookie === 'function'
                ? window.get_cookie(cookieName)
                : document.cookie.split(';').some(function (cookie) {
                    return cookie.trim().indexOf(cookieName + '=') === 0;
                });

            if (cookieValue) {
                this.style.display = 'none';
            }
        });
    }

    window.addEventListener('pageshow', rbSyncPopupCookies);

    // -------- alert 오버라이드 --------
    window.alert = function (msg, callback) {
        // 기존 팝업 정리
        $('.rb-custom-alert-popup, .rb-custom-alert-popup-bg').remove();

        var rb_alert_svg =
            "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36' viewBox='0 0 24 24'><g fill='none'><path d='M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z'/><path fill='#09244BFF' d='m13.299 3.148 8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2m0-7a1 1 0 0 0-.993.883L11 9v4a1 1 0 0 0 1.993.117L13 13V9a1 1 0 0 0-1-1'/></g></svg>";

        var rb_alert_bg = $("<div class='rb-custom-alert-popup-bg'></div>");
        var rb_alert_popup = $(`
      <div class='rb-custom-alert-popup' role='dialog' aria-modal='true'>
        ${rb_alert_svg}
        <div class='rb-msg' style='margin-bottom:18px; white-space: pre-line;'></div>
        <button type='button' class='rb-alert-btn main_rb_bg font-R rb-btn-ok' autofocus>확인</button>
      </div>
    `);

        $('body').append(rb_alert_bg).append(rb_alert_popup);
        rb_alert_popup.find('.rb-msg').text(String(msg)); // 안전 주입(+ 줄바꿈 렌더)

        // 포커스
        setTimeout(function () {
            rb_alert_popup.find('.rb-btn-ok').trigger('focus');
        }, 10);

        function closeAlert() {
            rb_alert_bg.fadeOut(220, function () {
                rb_alert_bg.remove();
            });
            rb_alert_popup.fadeOut(220, function () {
                rb_alert_popup.remove();
            });
            $(document).off('keydown.rbAlert');
            if (typeof callback === 'function') callback();
        }

        rb_alert_popup.find('.rb-btn-ok').on('click', closeAlert);
        rb_alert_bg.on('click', closeAlert);
        $(document).on('keydown.rbAlert', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault(); // 기본 중지 이벤트 실행
                closeAlert();
            }
        });
    };

    // -------- rb_confirm: Promise<boolean> --------
    window.rb_confirm = function (msg) {
        return new Promise(function (resolve) {
            // 기존 팝업 정리
            $('.rb-custom-alert-popup, .rb-custom-alert-popup-bg').remove();

            var rb_alert_svg =
                "<svg xmlns='http://www.w3.org/2000/svg' width='36' height='36' viewBox='0 0 24 24'><g fill='none'><path d='M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z'/><path fill='#09244BFF' d='m13.299 3.148 8.634 14.954a1.5 1.5 0 0 1-1.299 2.25H3.366a1.5 1.5 0 0 1-1.299-2.25l8.634-14.954c.577-1 2.02-1 2.598 0M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2m0-7a1 1 0 0 0-.993.883L11 9v4a1 1 0 0 0 1.993.117L13 13V9a1 1 0 0 0-1-1'/></g></svg>";

            var rb_confirm_bg = $("<div class='rb-custom-alert-popup-bg'></div>");
            var rb_confirm_popup = $(`
        <div class='rb-custom-alert-popup' role='dialog' aria-modal='true'>
          ${rb_alert_svg}
          <div class='rb-msg' style='margin-bottom:18px; white-space: pre-line;'></div>
          <div style="display:flex; justify-content:center; gap:4px;">
            <button type='button' class='rb-alert-btn main_rb_bg font-R rb-btn-ok' autofocus>확인</button>
            <button type='button' class='rb-alert-btn font-R rb-btn-cancel'>취소</button>
          </div>
        </div>
      `);

            $('body').append(rb_confirm_bg).append(rb_confirm_popup);
            rb_confirm_popup.find('.rb-msg').text(String(msg)); // 안전 주입(+ 줄바꿈 렌더)

            function closeConfirm(result) {
                rb_confirm_bg.fadeOut(220, function () {
                    rb_confirm_bg.remove();
                });
                rb_confirm_popup.fadeOut(220, function () {
                    rb_confirm_popup.remove();
                });
                $(document).off('keydown.rbConfirm');
                resolve(!!result);
            }

            rb_confirm_popup.find('.rb-btn-ok').on('click', function () {
                closeConfirm(true);
            });
            rb_confirm_popup.find('.rb-btn-cancel').on('click', function () {
                closeConfirm(false);
            });
            rb_confirm_bg.on('click', function () {
                closeConfirm(false);
            });
            $(document).on('keydown.rbConfirm', function (e) {
                if (e.key === 'Escape') closeConfirm(false);
            });

            setTimeout(function () {
                rb_confirm_popup.find('.rb-btn-ok').trigger('focus');
            }, 10);
        });
    };

    // -------- rb_confirm_submit: Promise confirm + form submit --------
    window.rb_confirm_submit = function (form, msg) {
        if (!form) return false;

        if (form.getAttribute('data-rb-confirm-approved') === '1') {
            form.removeAttribute('data-rb-confirm-approved');
            return true;
        }

        window.rb_confirm(msg).then(function (ok) {
            if (!ok) return;

            form.setAttribute('data-rb-confirm-approved', '1');
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                HTMLFormElement.prototype.submit.call(form);
            }
        });

        return false;
    };

    // -------- del() 헬퍼 --------
    window.del = function (href) {
        if (!href) return;
        window.rb_confirm("삭제한 데이터는 복구할 수 없습니다.\n정말 삭제 하시겠습니까?")
            .then(function (ok) {
                if (ok) window.location.assign(href);
            });
    };

})(window, window.jQuery);
