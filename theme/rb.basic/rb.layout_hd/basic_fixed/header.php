<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 레이아웃 폴더내 style.css 파일
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/rb.layout_hd/'.$rb_core['layout_hd'].'/style.css?ver='.G5_TIME_YMDHIS.'">', 0);

$rb_notification_unread_count = 0;
$rb_notification_action_token = '';
$rb_notification_category_tabs = array();
if ($is_member && function_exists('rb_notification_table_ready') && rb_notification_table_ready()) {
    $rb_notification_unread_count = rb_notification_unread_count($member['mb_id']);
    $rb_notification_action_token = rb_notification_action_token();
    $rb_notification_category_tabs = array('all' => '전체') + rb_notification_visible_categories();
}

?>
    <!--
    <header id="header">내용</header>
    <header>는 반드시 포함해주세요.
    -->

    <!-- 헤더 { -->
    <header id="header">

        <!-- GNB { -->
        <div class="gnb_wrap">

            <div class="inner" style="width:<?php echo $tb_width_inner ?>; <?php echo $tb_width_padding ?>">

                <!-- 토글메뉴 { -->
                <ul class="tog_wrap mobile">
                    <li>
                        <button type="button" alt="메뉴열기" id="tog_gnb_mobile">
                            <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 14C17.2549 14.0003 17.5 14.0979 17.6854 14.2728C17.8707 14.4478 17.9822 14.687 17.9972 14.9414C18.0121 15.1958 17.9293 15.4464 17.7657 15.6418C17.6021 15.8373 17.3701 15.9629 17.117 15.993L17 16H1C0.74512 15.9997 0.499968 15.9021 0.314632 15.7272C0.129296 15.5522 0.017765 15.313 0.00282788 15.0586C-0.0121092 14.8042 0.0706746 14.5536 0.234265 14.3582C0.397855 14.1627 0.629904 14.0371 0.883 14.007L1 14H17ZM17 7C17.2652 7 17.5196 7.10536 17.7071 7.29289C17.8946 7.48043 18 7.73478 18 8C18 8.26522 17.8946 8.51957 17.7071 8.70711C17.5196 8.89464 17.2652 9 17 9H1C0.734784 9 0.48043 8.89464 0.292893 8.70711C0.105357 8.51957 0 8.26522 0 8C0 7.73478 0.105357 7.48043 0.292893 7.29289C0.48043 7.10536 0.734784 7 1 7H17ZM17 0C17.2652 0 17.5196 0.105357 17.7071 0.292893C17.8946 0.48043 18 0.734784 18 1C18 1.26522 17.8946 1.51957 17.7071 1.70711C17.5196 1.89464 17.2652 2 17 2H1C0.734784 2 0.48043 1.89464 0.292893 1.70711C0.105357 1.51957 0 1.26522 0 1C0 0.734784 0.105357 0.48043 0.292893 0.292893C0.48043 0.105357 0.734784 0 1 0H17Z" fill="#09244B"/>
                            </svg>
                        </button>

                        <script>
                            $(document).ready(function() {
                                $('#tog_gnb_mobile').click(function() {
                                    $('#cbp-hrmenu-btm').addClass('active');
                                    $('#m_gnb_close_btn').addClass('active');
                                    $('main').addClass('moves');
                                    $('header').addClass('moves');
                                });
                            });
                        </script>
                    </li>
                </ul>
                <!-- } -->

                <!-- 로고 { -->
                <ul class="logo_wrap">
                    <li>
                        <a href="<?php echo G5_URL ?>" alt="<?php echo $config['cf_title']; ?>">

                            <picture id="logo_img">

                                <?php if (!empty($rb_builder['bu_logo_mo']) && !empty($rb_builder['bu_logo_mo_w'])) { ?>
                                    <source id="sourceSmall" srcset="<?php echo G5_URL ?>/data/logos/mo?ver=<?php echo G5_SERVER_TIME ?>" media="(max-width: 1024px)">
                                <?php } else { ?>
                                    <source id="sourceSmall" srcset="<?php echo G5_THEME_URL ?>/rb.img/logos/mo.png?ver=<?php echo G5_SERVER_TIME ?>" media="(max-width: 1024px)">
                                <?php } ?>

                                <?php if (!empty($rb_builder['bu_logo_pc']) && !empty($rb_builder['bu_logo_pc_w'])) { ?>
                                    <source id="sourceLarge" srcset="<?php echo G5_URL ?>/data/logos/pc?ver=<?php echo G5_SERVER_TIME ?>" media="(min-width: 1025px)">
                                <?php } else { ?>
                                    <source id="sourceLarge" srcset="<?php echo G5_THEME_URL ?>/rb.img/logos/pc.png?ver=<?php echo G5_SERVER_TIME ?>" media="(max-width: 1024px)">
                                <?php } ?>

                                <?php if (!empty($rb_builder['bu_logo_pc']) && !empty($rb_builder['bu_logo_pc_w'])) { ?>
                                    <img id="fallbackImage" src="<?php echo G5_URL ?>/data/logos/pc?ver=<?php echo G5_SERVER_TIME ?>" alt="<?php echo $config['cf_title']; ?>" class="responsive-image">
                                <?php } else { ?>
                                    <img id="fallbackImage" src="<?php echo G5_THEME_URL ?>/rb.img/logos/pc.png?ver=<?php echo G5_SERVER_TIME ?>" alt="<?php echo $config['cf_title']; ?>" class="responsive-image">
                                <?php } ?>

                            </picture>

                        </a>

                    </li>
                </ul>
                <!-- } -->

                <!-- 검색 { -->
                <ul class="search_top_wrap">
                    <form name="fsearchbox" method="get" action="<?php echo G5_BBS_URL ?>/search.php" onsubmit="return fsearchbox_submit(this);">
                        <div class="search_top_wrap_inner">
                        <input type="hidden" name="sfl" value="wr_subject||wr_content">
                        <input type="hidden" name="sop" value="and">

                        <input type="text" value="" name="stx" class="font-B" placeholder="통합검색" maxlength="20">
                        <button type="submit">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.49928 1.91687e-08C7.14387 0.000115492 5.80814 0.324364 4.60353 0.945694C3.39893 1.56702 2.36037 2.46742 1.57451 3.57175C0.788656 4.67609 0.278287 5.95235 0.0859852 7.29404C-0.106316 8.63574 0.0250263 10.004 0.469055 11.2846C0.913084 12.5652 1.65692 13.7211 2.63851 14.6557C3.6201 15.5904 4.81098 16.2768 6.11179 16.6576C7.4126 17.0384 8.78562 17.1026 10.1163 16.8449C11.447 16.5872 12.6967 16.015 13.7613 15.176L17.4133 18.828C17.6019 19.0102 17.8545 19.111 18.1167 19.1087C18.3789 19.1064 18.6297 19.0012 18.8151 18.8158C19.0005 18.6304 19.1057 18.3796 19.108 18.1174C19.1102 17.8552 19.0094 17.6026 18.8273 17.414L15.1753 13.762C16.1633 12.5086 16.7784 11.0024 16.9504 9.41573C17.1223 7.82905 16.8441 6.22602 16.1475 4.79009C15.4509 3.35417 14.3642 2.14336 13.0116 1.29623C11.659 0.449106 10.0952 -0.000107143 8.49928 1.91687e-08ZM1.99928 8.5C1.99928 6.77609 2.6841 5.12279 3.90308 3.90381C5.12207 2.68482 6.77537 2 8.49928 2C10.2232 2 11.8765 2.68482 13.0955 3.90381C14.3145 5.12279 14.9993 6.77609 14.9993 8.5C14.9993 10.2239 14.3145 11.8772 13.0955 13.0962C11.8765 14.3152 10.2232 15 8.49928 15C6.77537 15 5.12207 14.3152 3.90308 13.0962C2.6841 11.8772 1.99928 10.2239 1.99928 8.5Z" fill="#09244B"/>
                            </svg>
                        </button>
                        </div>
                    </form>

                        <script>
                            function fsearchbox_submit(f) //검색
                            {
                                var stx = f.stx.value.trim();
                                if (stx.length < 2) {
                                    alert("검색어는 두글자 이상 입력해주세요.");
                                    f.stx.select();
                                    f.stx.focus();
                                    return false;
                                }

                                // 검색에 많은 부하가 걸리는 경우 이 주석을 제거하세요.
                                var cnt = 0;
                                for (var i = 0; i < stx.length; i++) {
                                    if (stx.charAt(i) == ' ')
                                        cnt++;
                                }

                                if (cnt > 1) {
                                    alert("빠른 검색을 위해 공백은 한번만 입력할 수 있어요.");
                                    f.stx.select();
                                    f.stx.focus();
                                    return false;
                                }
                                f.stx.value = stx;

                                return true;
                            }


                        </script>
                </ul>
                <!-- } -->


                <!-- 퀵메뉴 { -->
                <ul class="snb_wrap">


                    <li class="member_info_wrap">
                        <?php if($is_member) { ?>
                        <a href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php" class="font-B notranslate"><?php echo $member['mb_nick'] ?> <font class="font-R">님</font></a>　<a href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank" class="win_point"><span class="font-H"><?php echo number_format($member['mb_point']); ?> P</span></a>
                        <?php } else { ?>
                        <a href="<?php echo G5_BBS_URL ?>/login.php?url=<?php echo urlencode(getCurrentUrl()); ?>" class="font-R"><font>로그인 후 이용해주세요.</font></a>
                        <?php } ?>
                    </li>
                    <li class="qm_wrap">

                        <?php if($is_member) { ?>
                        <a href="<?php echo G5_BBS_URL ?>/memo.php" id="rb_memo_top_btn" alt="쪽지" onclick="win_memo(this.href); return false;">
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g id="mail_fill" fill='none'><path d='M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z'/><path fill='#09244BFF' d='m2.068 5.482 8.875 8.876a1.5 1.5 0 0 0 2.008.103l.114-.103 8.869-8.87c.029.11.048.222.058.337L22 6v12a2 2 0 0 1-1.85 1.995L20 20H4a2 2 0 0 1-1.995-1.85L2 18V6c0-.12.01-.236.03-.35zM20 4c.121 0 .24.01.355.031l.17.039-8.52 8.52-8.523-8.522c.11-.03.224-.05.34-.06L4 4z'/></g></svg>
                            <?php if($memo_not_read > 0) { ?>
                            <span class="font-H"><?php echo $memo_not_read ?></span>
                            <?php } ?>
                        </a>

                        <?php if ($rb_notification_category_tabs) { ?>
                        <a href="#" id="notification_top_btn" alt="알림" aria-label="알림" aria-haspopup="true" aria-expanded="false">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.9999 2C10.1434 2 8.36294 2.7375 7.05018 4.05025C5.73743 5.36301 4.99993 7.14348 4.99993 9V12.528C5.00008 12.6831 4.96413 12.8362 4.89493 12.975L3.17793 16.408C3.09406 16.5757 3.05445 16.7621 3.06288 16.9494C3.07131 17.1368 3.12748 17.3188 3.22608 17.4783C3.32467 17.6379 3.4624 17.7695 3.6262 17.8608C3.78999 17.9521 3.97441 18 4.16193 18H19.8379C20.0255 18 20.2099 17.9521 20.3737 17.8608C20.5375 17.7695 20.6752 17.6379 20.7738 17.4783C20.8724 17.3188 20.9286 17.1368 20.937 16.9494C20.9454 16.7621 20.9058 16.5757 20.8219 16.408L19.1059 12.975C19.0364 12.8362 19.0001 12.6832 18.9999 12.528V9C18.9999 7.14348 18.2624 5.36301 16.9497 4.05025C15.6369 2.7375 13.8564 2 11.9999 2ZM11.9999 21C11.3793 21.0002 10.7739 20.8079 10.2671 20.4498C9.76025 20.0916 9.37694 19.5851 9.16993 19H14.8299C14.6229 19.5851 14.2396 20.0916 13.7328 20.4498C13.226 20.8079 12.6205 21.0002 11.9999 21Z" fill="#09244B"/>
                            </svg>
                            <span class="font-H" id="notification_unread_badge"<?php echo $rb_notification_unread_count > 0 ? '' : ' style="display:none"'; ?>><?php echo $rb_notification_unread_count; ?></span>
                        </a>

                        <div id="notification_box_wrap" data-endpoint="<?php echo G5_URL; ?>/rb/rb.mod/alarm/get-events.php" data-action-token="<?php echo get_text($rb_notification_action_token); ?>">
                            <div class="rb_notification_tabs" role="tablist" aria-label="알림 구분">
                                <?php foreach ($rb_notification_category_tabs as $rb_notification_category => $rb_notification_category_label) { ?>
                                <button type="button" class="<?php echo $rb_notification_category === 'all' ? 'active' : ''; ?>" data-category="<?php echo get_text($rb_notification_category); ?>" role="tab" aria-selected="<?php echo $rb_notification_category === 'all' ? 'true' : 'false'; ?>"><?php echo get_text($rb_notification_category_label); ?></button>
                                <?php } ?>
                            </div>
                            <div class="rb_notification_body">
                                <div class="rb_notification_list"><div class="rb_notification_loading" role="status" aria-label="알림을 불러오는 중"><span aria-hidden="true"></span></div></div>
                                <div class="rb_notification_view" style="display:none">
                                    <div class="rb_notification_view_header">
                                        <button type="button" class="rb_notification_back">← 목록</button>
                                        <span class="rb_notification_view_date"></span>
                                    </div>
                                    <div class="rb_notification_view_inner">
                                        <div class="rb_notification_view_content"></div>
                                        <div class="rb_notification_view_links"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="rb_notification_footer">
                                <span>알림 보관일수는 최장 180일입니다.</span>
                                <button type="button" class="rb_notification_delete_all">전체삭제</button>
                            </div>
                        </div>
                        <script>
(function ($) {
    $(function () {
        var $notificationBox = $('#notification_box_wrap');
        if (!$notificationBox.length) return;

        var isNotificationBoxVisible = false;
        var notificationCategory = 'all';
        var notificationEndpoint = $notificationBox.attr('data-endpoint');
        var notificationActionToken = $notificationBox.attr('data-action-token');
        var notificationIconPaths = {
            board: 'm10.756 6.17 7.07 7.071-7.173 7.174a2 2 0 0 1-1.238.578L9.239 21H4.006a1.01 1.01 0 0 1-1.004-.9l-.006-.11v-5.233a2 2 0 0 1 .467-1.284l.12-.13 7.173-7.174Zm3.14-3.14a2 2 0 0 1 2.701-.117l.127.117 4.243 4.243a2 2 0 0 1 .117 2.7l-.117.128-1.726 1.726-7.07-7.071z',
            shop: 'M10.464 3.282a2 2 0 0 1 2.964-.12l.108.12L17.468 8h2.985a1.49 1.49 0 0 1 1.484 1.655l-.092.766-.1.74-.082.554-.095.595-.108.625-.122.648-.136.661c-.072.333-.149.667-.232.999a21.018 21.018 0 0 1-.832 2.583l-.221.54-.214.488-.202.434-.094.194-.249.49c-.32.61-.924.97-1.563 1.022l-.16.006H6.555a1.929 1.929 0 0 1-1.71-1.008l-.232-.45-.18-.37a20.09 20.09 0 0 1-.095-.205l-.2-.449a21.536 21.536 0 0 1-1.108-3.276 32.366 32.366 0 0 1-.156-.654l-.142-.648-.127-.634-.112-.613-.1-.587-.087-.554-.074-.513-.09-.683-.066-.556a39.802 39.802 0 0 1-.017-.153 1.488 1.488 0 0 1 1.348-1.64L3.543 8h2.989zm-.503 9.44a1 1 0 0 0-1.96.326l.013.116.5 3 .025.114a1 1 0 0 0 1.96-.326l-.013-.116-.5-3zm5.203-.708a1 1 0 0 0-1.125.708l-.025.114-.5 3a1 1 0 0 0 1.947.442l.025-.114.5-3a1 1 0 0 0-.822-1.15M12 4.562 9.135 8h5.73z',
            subscribe: 'M16 14a5 5 0 0 1 4.995 4.783L21 19v1a2 2 0 0 1-1.85 1.995L19 22H5a2 2 0 0 1-1.995-1.85L3 20v-1a5 5 0 0 1 4.783-4.995L8 14zM12 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10',
            notice: 'M5 9a7 7 0 0 1 14 0v3.764l1.822 3.644A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592L5 12.764zm5.268 9a2 2 0 0 0 3.464 0zM12 4a5 5 0 0 0-5 5v3.764a2 2 0 0 1-.211.894L5.619 16h12.763l-1.17-2.342a2.001 2.001 0 0 1-.212-.894V9a5 5 0 0 0-5-5',
            other: 'M7 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m0 10a4 4 0 1 0 0 8 4 4 0 0 0 0-8m6-6a4 4 0 1 1 8 0 4 4 0 0 1-8 0m4 6a4 4 0 1 0 0 8 4 4 0 0 0 0-8'
        };

        function closeNotificationBox() {
            $notificationBox.hide();
            $('#notification_top_btn').removeClass('notification_open').attr('aria-expanded', 'false');
            isNotificationBoxVisible = false;
        }

        window.rb_notification_update_badge = function (count) {
            count = parseInt(count, 10) || 0;
            var $badge = $('#notification_unread_badge');
            $badge.text(count);
            count > 0 ? $badge.show() : $badge.hide();
            $('#rb_my_notification_btn').text(count + '개');
        };

        function showNotificationMessage(message) {
            $('.rb_notification_list').empty().append(
                $('<div>', {'class': 'rb_notification_empty'}).text(message)
            );
        }

        function showNotificationLoading() {
            $('.rb_notification_list').empty().append(
                $('<div>', {
                    'class': 'rb_notification_loading',
                    'role': 'status',
                    'aria-label': '알림을 불러오는 중'
                }).append($('<span>', {'aria-hidden': 'true'}))
            );
        }

        function renderNotificationList(items) {
            var $list = $('.rb_notification_list').empty();
            $('.rb_notification_view').hide();
            $list.show();

            if (!items || !items.length) {
                showNotificationMessage('도착한 알림이 없습니다.');
                return;
            }

            $.each(items, function (index, item) {
                var content = String(item.content || item.title || '')
                    .replace(/<br\s*\/?>/gi, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
                var $row = $('<div>', {
                    'class': 'rb_notification_item' + (parseInt(item.is_read, 10) ? ' is_read' : ''),
                    'data-notification-id': item.id
                });
                var $open = $('<button>', {
                    type: 'button',
                    'class': 'rb_notification_item_open',
                    'data-notification-id': item.id
                });
                var iconCategory = notificationIconPaths[item.category] ? item.category : 'other';
                var $categoryIcon = $('<span>', {
                    'class': 'rb_notification_category_icon rb_notification_category_' + iconCategory,
                    'aria-hidden': 'true'
                });
                var categorySvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                var categoryPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                categorySvg.setAttribute('width', '24');
                categorySvg.setAttribute('height', '24');
                categorySvg.setAttribute('viewBox', '0 0 24 24');
                categoryPath.setAttribute('d', notificationIconPaths[iconCategory]);
                categorySvg.appendChild(categoryPath);
                $categoryIcon.append(categorySvg);
                var $meta = $('<span>', {'class': 'rb_notification_item_meta'})
                    .append($('<b>').text(item.category_label || '기타'))
                    .append($('<em>').text(item.created_at || ''));
                var $text = $('<span>', {'class': 'rb_notification_item_text'})
                    .append($meta)
                    .append($('<span>', {'class': 'rb_notification_item_content'}).text(content));

                $open.append($categoryIcon).append($text);
                $row.append($open).append(
                    $('<button>', {
                        type: 'button',
                        'class': 'rb_notification_delete',
                        'data-notification-id': item.id,
                        'aria-label': '알림 삭제',
                        title: '삭제'
                    }).text('×')
                );
                $list.append($row);
            });
        }

        function loadNotificationList(category) {
            notificationCategory = category || 'all';
            showNotificationLoading();
            $('.rb_notification_view').hide();
            $('.rb_notification_list').show();

            $.ajax({
                type: 'POST',
                url: notificationEndpoint,
                dataType: 'json',
                cache: false,
                data: {act: 'notification_list', category: notificationCategory},
                success: function (result) {
                    if (result.msg !== 'SUCCESS') {
                        showNotificationMessage('알림을 불러오지 못했습니다.');
                        return;
                    }
                    rb_notification_update_badge(result.unread_count);
                    renderNotificationList(result.items);
                },
                error: function () {
                    showNotificationMessage('알림을 불러오지 못했습니다.');
                }
            });
        }

        function renderNotificationView(item) {
            var rawContent = String(item.content || item.title || '');
            var notificationLinks = [];

            function addNotificationLink(value) {
                value = String(value || '').trim();
                if (!value) return;
                if (/^www\./i.test(value)) value = 'https://' + value;

                var safeUrl = typeof rb_alarm_url === 'function' ? rb_alarm_url(value) : '';
                if (safeUrl && $.inArray(safeUrl, notificationLinks) === -1) {
                    notificationLinks.push(safeUrl);
                }
            }

            var parser = new DOMParser();
            var parsedContent = parser.parseFromString(
                rawContent.replace(/<br\s*\/?>/gi, '\n'),
                'text/html'
            );
            var unsafeNodes = parsedContent.querySelectorAll('script, style');
            for (var unsafeIndex = unsafeNodes.length - 1; unsafeIndex >= 0; unsafeIndex--) {
                unsafeNodes[unsafeIndex].parentNode.removeChild(unsafeNodes[unsafeIndex]);
            }
            var contentAnchors = parsedContent.querySelectorAll('a[href]');
            for (var anchorIndex = contentAnchors.length - 1; anchorIndex >= 0; anchorIndex--) {
                addNotificationLink(contentAnchors[anchorIndex].getAttribute('href'));
                contentAnchors[anchorIndex].parentNode.removeChild(contentAnchors[anchorIndex]);
            }

            var contentText = parsedContent.body ? parsedContent.body.textContent || '' : rawContent;
            contentText = contentText.replace(/(?:https?:\/\/|www\.)[^\s<>"']+/gi, function (match) {
                var linkValue = match.replace(/[),.!?]+$/, '');
                var suffix = match.substring(linkValue.length);
                addNotificationLink(linkValue);
                return suffix;
            });
            contentText = contentText
                .replace(/\r/g, '')
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n[ \t]+/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();

            addNotificationLink(item.url);

            $('.rb_notification_view_date').text(item.created_at || '');
            $('.rb_notification_view_content').text(contentText || item.title || '');

            var $links = $('.rb_notification_view_links').empty();
            $.each(notificationLinks, function (index, linkUrl) {
                $links.append($('<a>', {href: linkUrl}).text('링크열기'));
            });

            $('.rb_notification_list').hide();
            $('.rb_notification_view').show();
            $('.rb_notification_body').scrollTop(0);
        }

        function openNotificationView(notificationId) {
            notificationId = parseInt(notificationId, 10) || 0;
            if (!notificationId) return;

            $.ajax({
                type: 'POST',
                url: notificationEndpoint,
                dataType: 'json',
                cache: false,
                data: {
                    act: 'notification_view',
                    notification_id: notificationId,
                    action_token: notificationActionToken
                },
                success: function (result) {
                    if (result.msg === 'INVALID_TOKEN') {
                        alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                        return;
                    }
                    if (result.msg !== 'SUCCESS' || !result.item) {
                        $('.rb_notification_view').hide();
                        $('.rb_notification_list').show();
                        showNotificationMessage('알림을 찾을 수 없습니다.');
                        return;
                    }
                    $('.rb_notification_item[data-notification-id="' + notificationId + '"]').addClass('is_read');
                    rb_notification_update_badge(result.unread_count);
                    renderNotificationView(result.item);
                },
                error: function () {
                    $('.rb_notification_view').hide();
                    $('.rb_notification_list').show();
                    showNotificationMessage('알림을 불러오지 못했습니다.');
                }
            });
        }

        window.rb_notification_open_view = function (notificationId) {
            notificationId = parseInt(notificationId, 10) || 0;
            if (!notificationId) return false;

            isNotificationBoxVisible = true;
            $notificationBox.show();
            $('#notification_top_btn').addClass('notification_open').attr('aria-expanded', 'true');
            $('.rb_notification_view').hide();
            $('.rb_notification_list').show();
            showNotificationLoading();
            openNotificationView(notificationId);
            return false;
        };

        $('#notification_top_btn, #rb_my_notification_btn').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (this.id === 'rb_my_notification_btn') {
                $('#rb_my_ovray').removeClass('is_open');
                $('#rb_my_panel').removeClass('is_open').attr('aria-hidden', 'true');
            }

            isNotificationBoxVisible = !isNotificationBoxVisible;
            if (isNotificationBoxVisible) {
                $notificationBox.show();
                $('#notification_top_btn').addClass('notification_open').attr('aria-expanded', 'true');
                loadNotificationList(notificationCategory);
            } else {
                closeNotificationBox();
            }
        });

        $(document).on('click.rbRowNotification', function () {
            if (isNotificationBoxVisible) closeNotificationBox();
        });

        $notificationBox.on('click', function (event) {
            event.stopPropagation();
        });

        $('.rb_notification_tabs').on('click', 'button', function () {
            $('.rb_notification_tabs button').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            loadNotificationList($(this).attr('data-category'));
        });

        $('.rb_notification_list').on('click', '.rb_notification_item_open', function () {
            var notificationId = parseInt($(this).attr('data-notification-id'), 10) || 0;
            if (notificationId) openNotificationView(notificationId);
        });

        $('.rb_notification_list').on('click', '.rb_notification_delete', function (event) {
            event.stopPropagation();
            var notificationId = parseInt($(this).attr('data-notification-id'), 10) || 0;
            if (!notificationId) return;

            $.ajax({
                type: 'POST',
                url: notificationEndpoint,
                dataType: 'json',
                cache: false,
                data: {
                    act: 'notification_delete',
                    notification_id: notificationId,
                    action_token: notificationActionToken
                },
                success: function (result) {
                    if (result.msg === 'INVALID_TOKEN') {
                        alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                        return;
                    }
                    if (result.msg === 'SUCCESS') {
                        rb_notification_update_badge(result.unread_count);
                        loadNotificationList(notificationCategory);
                    }
                }
            });
        });

        $('.rb_notification_delete_all').on('click', function () {
            rb_confirm('알림을 모두 삭제하시겠습니까?\n삭제된 데이터는 복구가 되지 않습니다.').then(function (confirmed) {
                if (!confirmed) return;

                $.ajax({
                    type: 'POST',
                    url: notificationEndpoint,
                    dataType: 'json',
                    cache: false,
                    data: {
                        act: 'notification_delete_all',
                        action_token: notificationActionToken
                    },
                    success: function (result) {
                        if (result.msg === 'INVALID_TOKEN') {
                            alert('페이지를 새로고침한 후 다시 이용해 주세요.');
                            return;
                        }
                        if (result.msg === 'SUCCESS') {
                            rb_notification_update_badge(result.unread_count);
                            $('.rb_notification_view').hide();
                            $('.rb_notification_list').show();
                            loadNotificationList(notificationCategory);
                            return;
                        }
                        alert('알림을 삭제하지 못했습니다.');
                    },
                    error: function () {
                        alert('알림을 삭제하지 못했습니다.');
                    }
                });
            });
        });

        $('.rb_notification_back').on('click', function () {
            $('.rb_notification_view').hide();
            $('.rb_notification_list').show();
            $('.rb_notification_body').scrollTop(0);
        });
    });
})(jQuery);
                        </script>
                        <?php } ?>
                        <?php } ?>


                    </li>
                    <li class="my_btn_wrap">
                        <?php if($is_member) { ?>
                            <button type="button" alt="내 정보" class="btn_round arr_bg" id="rb_my_ovray">내 정보</button>
                            <div id="rb_my_panel" class="rb_my_panel" aria-hidden="true">

                                <?php
                                $mb_nick_p = get_sideview($member['mb_id'], get_text($member['mb_nick']), $member['mb_email'], $member['mb_homepage'], $member['mb_open']);

                                // 회원가입후 몇일째인지? + 1 은 당일을 포함한다는 뜻
                                $sql_p = " select (TO_DAYS('".G5_TIME_YMDHIS."') - TO_DAYS('{$member['mb_datetime']}') + 1) as days ";
                                $row_p = sql_fetch($sql_p);
                                $mb_reg_after_p = $row_p['days'];

                                $mb_homepage_p = set_http(get_text(clean_xss_tags($member['mb_homepage'])));
                                $mb_profile_p = $member['mb_profile'] ? conv_content($member['mb_profile'],0) : '소개 내용이 없습니다.';
                                ?>


                                <div class="rb_my_panel_row">
                                    <ul class="rb_my_p_ul1">
                                        <?php echo get_member_profile_img($member['mb_id']); ?>
                                    </ul>
                                    <ul class="rb_my_p_ul2">
                                        <li class="font-B font-14 mt-3"><?php echo $member['mb_nick'] ?></li>
                                        <li class="font-R font-12 color-999 mt-3"><?php echo $member['mb_level'] ?>Lv　@<?php echo $member['mb_id'] ?></li>
                                    </ul>
                                </div>
                                <div class="rb_my_panel_row my_p_flex mt-10">
                                    <button type="button" class="rb_my_p_btn" onclick="location.href='<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $member['mb_id'] ?>';">미니홈</button>
                                    <button type="button" class="rb_my_p_btn" onclick="location.href='<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php';">정보수정</button>
                                </div>

                                <div class="rb_my_panel_row mt-10">
                                    <button type="button" class="rb_my_p_btn rb_my_p_btn_w" onclick="location.href='<?php echo G5_BBS_URL ?>/logout.php';">로그아웃</button>
                                </div>

                                <div class="rb_my_panel_row mt-20">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        새 쪽지
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_BBS_URL ?>/memo.php" alt="새 쪽지" onclick="win_memo(this.href); return false;" class="font-B font-12"><?php echo $memo_not_read ?>개</a>
                                    </ul>
                                </div>

                                <?php if ($rb_notification_category_tabs) { ?>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        새 알림
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="#" id="rb_my_notification_btn" alt="새 알림" class="font-B font-12"><?php echo $rb_notification_unread_count ?>개</a>
                                    </ul>
                                </div>
                                <?php } ?>


                                <?php if(isset($config['cf_use_point']) && $config['cf_use_point'] == 1) { ?>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        포인트
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank" alt="포인트" class="win_point font-B font-12"><?php echo number_format($member['mb_point']); ?> P</a>
                                    </ul>
                                </div>
                                <?php } ?>

                                <?php if(isset($pnt_c['pnt_add_use']) && $pnt_c['pnt_add_use'] == 1) { ?>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        <?php echo $pnt_c_name ?>
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL; ?>/rb/point_c.php" target="_blank" alt="<?php echo $pnt_c_name ?>" class="win_point font-B font-12"><?php echo number_format($member['rb_point']); ?> <?php echo $pnt_c_name_st ?></a>
                                    </ul>
                                </div>
                                <?php } ?>

                                <div class="rb_my_panel_line"></div>


                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        게시물
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL ?>/rb/new.php?mb_id=<?php echo $member['mb_id'] ?>&view=w" alt="게시물" class="font-B font-12">
                                        <?php echo wr_cnt($member['mb_id'], 'w') ?>건
                                        </a>
                                    </ul>
                                </div>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        댓글
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL ?>/rb/new.php?mb_id=<?php echo $member['mb_id'] ?>&view=c" alt="댓글" class="font-B font-12">
                                        <?php echo wr_cnt($member['mb_id'], 'c') ?>건
                                        </a>
                                    </ul>
                                </div>

                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        스크랩
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_BBS_URL ?>/scrap.php" target="_blank" alt="스크랩" class="win_scrap font-B font-12">
                                        <?php echo rb_get_scrap_count($member['mb_id']); ?>건
                                        </a>
                                    </ul>
                                </div>

                                <?php if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { // 구독 사용시 ?>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        구독자
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $member['mb_id'] ?>&ca=fw" alt="구독자" class="font-B font-12">
                                        <?php
                                        $cnt = trim(str_replace('구독', '', (string)sb_cnt($member['mb_id'])));
                                        echo ($cnt === '' ? '0명' : $cnt);
                                        ?>
                                        </a>
                                    </ul>
                                </div>
                                <?php } ?>

                                <div class="rb_my_panel_line"></div>


                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        가입일수
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $member['mb_id'] ?>" alt="가입일수" class="font-B font-12"><?php echo "+".number_format($mb_reg_after_p)."일";  ?></a>
                                    </ul>
                                </div>





                            </div>

                            <script>
                            (function(){
                                var btn = document.getElementById('rb_my_ovray');
                                var panel = document.getElementById('rb_my_panel');
                                if (!btn || !panel) return;

                                function positionPanel(){
                                    var r = btn.getBoundingClientRect();

                                    // // 패널을 잠깐 보이게 해서 width 측정(깜빡임 방지)
                                    var prevDisplay = panel.style.display;
                                    var prevVis = panel.style.visibility;

                                    panel.style.visibility = 'hidden';
                                    panel.style.display = 'block';

                                    var pw = panel.offsetWidth;

                                    panel.style.display = prevDisplay;
                                    panel.style.visibility = prevVis;

                                    // // fixed는 viewport 기준 좌표 사용 (scrollX/scrollY 쓰지않음)
                                    var top = r.bottom + 8;
                                    var left = r.right - pw;

                                    panel.style.top = top + 'px';
                                    panel.style.left = left + 'px';
                                }

                                function openPanel(){
                                    btn.classList.add('is_open');
                                    panel.classList.add('is_open');
                                    panel.setAttribute('aria-hidden', 'false');
                                    positionPanel();
                                }

                                function closePanel(){
                                    btn.classList.remove('is_open');
                                    panel.classList.remove('is_open');
                                    panel.setAttribute('aria-hidden', 'true');
                                }

                                function togglePanel(){
                                    if (panel.classList.contains('is_open')) closePanel();
                                    else openPanel();
                                }

                                btn.addEventListener('click', function(ev){
                                    ev.preventDefault();
                                    ev.stopPropagation();
                                    togglePanel();
                                });

                                panel.addEventListener('click', function(ev){
                                    ev.stopPropagation();
                                });

                                document.addEventListener('click', function(){
                                    closePanel();
                                });

                                document.addEventListener('keydown', function(ev){
                                    if (ev.key === 'Escape') closePanel();
                                });

                                // // 리사이즈 때만 재배치
                                window.addEventListener('resize', function(){
                                    if (panel.classList.contains('is_open')) positionPanel();
                                });

                                // // 스크롤에는 재계산하지 않음(= top값 고정)
                                // window.addEventListener('scroll', function(){}, {passive:true});
                            })();
                            </script>
                        <?php } else { ?>
                            <button type="button" alt="로그인" class="btn_round"  onclick="location.href='<?php echo G5_BBS_URL ?>/login.php?url=<?php echo urlencode(getCurrentUrl()); ?>';">로그인</button>
                        <?php } ?>
                    </li>

                </ul>
                <!-- } -->

            </div>



            <div class="rows_gnb_wrap" style="width:<?php echo $tb_width_inner ?>; <?php echo $tb_width_padding ?>">
                <div class="inner row_gnbs">
                    <nav id="cbp-hrmenu" class="cbp-hrmenu swiper-container swiper-container-gnb">
                        <ul class="swiper-wrapper swiper-wrapper-gnb">
                        <?php
                        if(IS_MOBILE()) {
                            $menu_datas = rb_menu_db_3d(1, true);
                        } else {
                            $menu_datas = rb_menu_db_3d(0, true);
                        }

                        $gnb_zindex = 999;
                        $i = 0;

                        foreach($menu_datas as $row) {
                            if(empty($row)) continue;

                            // 1차 메뉴 권한 체크
                            if (!$is_admin && isset($row['me_level']) && $row['me_level'] > 0) {
                                if (isset($row['me_level_opt']) && $row['me_level_opt'] == 2) {
                                    if ($row['me_level'] != $member['mb_level']) continue;
                                } else {
                                    if ($row['me_level'] > $member['mb_level']) continue;
                                }
                            }
                        ?>
                            <li class="swiper-slide swiper-slide-gnb">
                                <a href="<?php echo $row['me_link']; ?>" target="_<?php echo $row['me_target']; ?>" class="font-B"><?php echo $row['me_name'] ?></a>
                                <?php
                                $k = 0;

                                foreach((array)$row['sub'] as $row2) {
                                    if(empty($row2)) continue;

                                    // 2차 메뉴 권한 체크
                                    if (!$is_admin && isset($row2['me_level']) && $row2['me_level'] > 0) {
                                        if (isset($row2['me_level_opt']) && $row2['me_level_opt'] == 2) {
                                            if ($row2['me_level'] != $member['mb_level']) continue;
                                        } else {
                                            if ($row2['me_level'] > $member['mb_level']) continue;
                                        }
                                    }

                                    if($k == 0)
                                        echo '<div class="cbp-hrsub"><div class="cbp-hrsub-inner"><div><ul>'.PHP_EOL;

                                    // // 2차 출력 시작
                                    echo '<li>';

                                    echo '<a href="'.$row2['me_link'].'" target="_'.$row2['me_target'].'">'.$row2['me_name'].'</a>';

                                    // // 3차가 있으면 하위 ul 추가
                                    $j = 0;
                                    if (!empty($row2['sub']) && is_array($row2['sub'])) {

                                        foreach ((array)$row2['sub'] as $row3) {
                                            if (empty($row3)) continue;

                                            // 3차 메뉴 권한 체크
                                            if (!$is_admin && isset($row3['me_level']) && $row3['me_level'] > 0) {
                                                if (isset($row3['me_level_opt']) && $row3['me_level_opt'] == 2) {
                                                    if ($row3['me_level'] != $member['mb_level']) continue;
                                                } else {
                                                    if ($row3['me_level'] > $member['mb_level']) continue;
                                                }
                                            }

                                            if ($j == 0) {
                                                echo '<i><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></i><dl class="cbp-hrsub-3">'.PHP_EOL;
                                            }

                                            echo '<dd><a href="'.$row3['me_link'].'" target="_'.$row3['me_target'].'">'.$row3['me_name'].'</a></dd>'.PHP_EOL;

                                            $j++;
                                        }

                                        if ($j > 0) {
                                            echo '</dl>'.PHP_EOL;
                                        }
                                    }

                                    echo '</li>'.PHP_EOL;
                                    // // 2차 출력 끝

                                    $k++;
                                }

                                if($k > 0)
                                    echo '</ul></div></div></div>'.PHP_EOL;
                                ?>
                            </li>
                        <?php
                            $i++;
                        }

                        if ($i == 0) {
                        ?>
                            <li><a href="javascript:void(0);">메뉴 준비 중입니다.</a></li>
                        <?php } ?>
                        </ul>
                    </nav>

                    <script>

                            var swiper = new Swiper('.swiper-container-gnb', {
                                slidesPerView: 'auto',
                                observer: true,
                                observeParents: true,
                                touchRatio: 0,
                                spaceBetween: 30,

                                breakpoints: {
                                    10: {
                                      spaceBetween: 20,
                                      touchRatio: 1,
                                    },
                                    768: {
                                      spaceBetween: 25,
                                      touchRatio: 1,
                                    },
                                    1024: {
                                      spaceBetween: 30,
                                      touchRatio: 0,
                                    },
                                }
                            });

                    </script>

                    <script>
                    var didScroll;
                    var lastScrollTop = 0;
                    var delta = 5;
                    var navbarHeight = $('header').outerHeight();

                    $(window).on('scroll', function () {
                        didScroll = true;
                    });

                    setInterval(function () {
                        if (didScroll) {
                            hasScrolled();
                            didScroll = false;
                        }
                    }, 10);

                    function hasScrolled() {
                        var st = $(window).scrollTop();

                        // 미세 스크롤 무시
                        if (Math.abs(lastScrollTop - st) <= delta) return;

                        // 아래로 스크롤: 클래스 추가 (기존 로직 유지)
                        if (st > lastScrollTop && st > navbarHeight) {
                            $('header').addClass('gnb_up');
                        }
                        // 위로 스크롤: 한번이라도 올리면 즉시 제거
                        else if (st < lastScrollTop) {
                            $('header').removeClass('gnb_up');
                        }

                        lastScrollTop = st;
                    }
                    </script>


                </div>

                <div class="snb_q_wrap">
                    <?php if(defined('G5_COMMUNITY_USE') == false || G5_COMMUNITY_USE) { ?>
                        <?php if (defined('G5_USE_SHOP') && G5_USE_SHOP) { ?>
                        <a href="<?php echo G5_SHOP_URL ?>/">마켓</a>
                        <?php } ?>
                    <?php } ?>

                    <a href="<?php echo G5_BBS_URL ?>/qalist.php">1:1 문의</a>
                    <a href="<?php echo G5_BBS_URL ?>/faq.php">FAQ</a>
                    <a href="<?php echo G5_URL ?>/rb/new.php">새글</a>
                </div>

            </div>

        </div>
        <!-- } -->
    </header>
    <!-- } -->
