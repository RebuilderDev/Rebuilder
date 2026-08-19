<?php if (!defined('_GNUBOARD_') || !defined('RB_BUSINESS_CONSOLE')) exit; ?>
<footer id="ft"><p>@ <?php echo rb_console_h(rb_console_config()['bc_name']); ?></p></footer></div></div>
<script src="<?php echo G5_ADMIN_URL; ?>/admin.js?ver=<?php echo G5_JS_VER; ?>"></script><script src="<?php echo G5_ADMIN_URL; ?>/js/rb.common.js?ver=<?php echo G5_JS_VER; ?>"></script>
<script>
(function($){
    // 관리자 공통 스크립트의 제출 보조 기능은 유지하되 관리자 전용
    // ajax.token.php를 호출하지 않고 콘솔 전용 토큰을 사용한다.
    window.get_ajax_token = function(){
        return <?php echo json_encode(rb_console_token()); ?>;
    };

    var $body = $('body.business-console');
    var $gnb = $('#gnb');
    var $container = $('#container');
    var $logo = $('#logo');
    var $button = $('#btn_gnb');
    var $darkToggle = $('#admDarkToggle');

    function setActiveGroup(groupId) {
        $('.gnb_li').each(function(){
            var active = String($(this).data('group-id')) === String(groupId);
            $(this).toggleClass('on', active).children('.btn_op').attr('aria-expanded', active ? 'true' : 'false');
        });
        $('.rb-console-menu-section').each(function(){
            var active = String($(this).data('group-id')) === String(groupId);
            $(this).toggleClass('is-active', active).attr('aria-hidden', active ? 'false' : 'true');
        });
    }

    function setCookie(name, value, days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + date.toUTCString() + ';path=/;SameSite=Lax';
    }

    function deleteCookie(name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax';
    }

    function toggleDarkMode() {
        var enabled = !$body.hasClass('adm-dark');
        $body.toggleClass('adm-dark', enabled);
        $darkToggle.attr('aria-pressed', enabled ? 'true' : 'false');
        if (enabled) setCookie('adm_dark', '1', 365);
        else deleteCookie('adm_dark');
        if (typeof window.rbConsoleApplyChartTheme === 'function') window.rbConsoleApplyChartTheme(enabled);
    }

    $button.off('click').attr({
        'aria-controls': 'gnb',
        'aria-expanded': $gnb.hasClass('gnb_small') ? 'false' : 'true'
    }).on('click', function(){
        if ($button.hasClass('btn_gnb_open')) deleteCookie('g5_business_console_btn_gnb');
        else setCookie('g5_business_console_btn_gnb', '1', 365);

        $container.toggleClass('container-small');
        $gnb.toggleClass('gnb_small');
        $logo.toggleClass('logo_small');
        $button.toggleClass('btn_gnb_open').attr('aria-expanded', $gnb.hasClass('gnb_small') ? 'false' : 'true');
    });
    $('.gnb_li>.rb-console-group-button').on('click',function(){
        setActiveGroup($(this).data('group-id'));
    });
    $darkToggle.on('click', toggleDarkMode).on('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleDarkMode(); }
    });

    (function initMemo(){
        var $memo = $('.rb-console-memo');
        if (!$memo.length) return;
        var $input = $memo.find('.rb-console-memo-input');
        var $list = $memo.find('.rb-console-memo-list');
        var endpoint = $memo.data('endpoint');
        var token = $memo.data('token');
        var busy = false;

        function renderList(list) {
            $list.empty();
            if (!list || !list.length) {
                $list.append($('<li>', {'class':'rb-memo-empty', text:'등록된 메모가 없습니다.'}));
                return;
            }
            $.each(list, function(_, item){
                var $item = $('<li>');
                $('<div>', {'class':'date', text:String(item.created_at || '').substring(0, 16)}).appendTo($item);
                $('<div>', {'class':'txt', text:String(item.content || '')}).appendTo($item);
                $('<button>', {'type':'button', 'class':'rb-x', 'data-id':item.id, 'aria-label':'메모 삭제', text:'×'}).appendTo($item);
                $list.append($item);
            });
        }

        function showError(message) {
            $list.empty().append($('<li>', {'class':'rb-memo-empty', text:message || '메모를 처리하지 못했습니다.'}));
        }

        function request(mode, data, done) {
            var payload = $.extend({}, data || {}, {mode:mode, rb_console_token:token});
            $.ajax({url:endpoint, type:'POST', dataType:'json', data:payload})
                .done(function(res){
                    if (res && res.result === 'ok') done(res.list || []);
                    else showError(res && res.message ? res.message : '메모를 처리하지 못했습니다.');
                })
                .fail(function(xhr){
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '메모를 처리하지 못했습니다.';
                    showError(message);
                })
                .always(function(){ busy = false; $input.prop('disabled', false); });
        }

        $input.on('focus', function(){ this.removeAttribute('readonly'); });
        $(window).on('pageshow', function(){ $input.val(''); });
        $input.on('keydown', function(e){
            if (e.which !== 13) return;
            e.preventDefault();
            var content = $.trim($input.val());
            if (!content || busy) return;
            busy = true;
            $input.prop('disabled', true);
            request('add', {content:content}, function(list){ $input.val(''); renderList(list); });
        });
        $list.on('click', '.rb-x', function(){
            if (busy) return;
            busy = true;
            request('delete', {id:$(this).data('id')}, renderList);
        });
        request('list', {}, renderList);
    })();
})(jQuery);
</script>
<?php run_event('tail_sub'); ?></body></html>
