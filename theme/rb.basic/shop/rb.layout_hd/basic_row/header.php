<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 레이아웃 폴더내 style.css 파일
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_SHOP_URL.'/rb.layout_hd/'.$rb_core['layout_hd_shop'].'/style.css?ver='.G5_TIME_YMDHIS.'">', 0);

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
                <ul class="tog_wrap mobile rb_mobile_menu_<?php echo rb_mobile_menu_position(); ?>">
                    <li>
                        <button type="button" alt="메뉴열기" id="tog_gnb_mobile">
                            <?php echo rb_mobile_menu_icon_svg(rb_mobile_menu_icon()); ?>
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
                        <a href="<?php echo G5_SHOP_URL ?>" alt="<?php echo $config['cf_title']; ?>">

                            <picture id="logo_img">

                                <source id="sourceSmall" srcset="<?php echo $rb_header_logo_mo_url ?>?ver=<?php echo G5_SERVER_TIME ?>" media="(max-width: 1024px)">
                                <source id="sourceLarge" srcset="<?php echo $rb_header_logo_pc_url ?>?ver=<?php echo G5_SERVER_TIME ?>" media="(min-width: 1025px)">
                                <img id="fallbackImage" src="<?php echo $rb_header_logo_pc_url ?>?ver=<?php echo G5_SERVER_TIME ?>" alt="<?php echo $config['cf_title']; ?>" class="responsive-image">

                            </picture>
                            <!--
                            <span class="font-B font-16">마켓</span>
                            -->
                        </a>

                    </li>
                </ul>
                <!-- } -->


                <!-- 퀵메뉴 { -->
                <ul class="snb_wrap">
                    <li class="qm_wrap">

                        <button type="button" alt="검색" class="mobile" onclick="location.href='<?php echo G5_SHOP_URL ?>/search.php';" style="padding-left:0px;" title="검색">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.49928 1.91687e-08C7.14387 0.000115492 5.80814 0.324364 4.60353 0.945694C3.39893 1.56702 2.36037 2.46742 1.57451 3.57175C0.788656 4.67609 0.278287 5.95235 0.0859852 7.29404C-0.106316 8.63574 0.0250263 10.004 0.469055 11.2846C0.913084 12.5652 1.65692 13.7211 2.63851 14.6557C3.6201 15.5904 4.81098 16.2768 6.11179 16.6576C7.4126 17.0384 8.78562 17.1026 10.1163 16.8449C11.447 16.5872 12.6967 16.015 13.7613 15.176L17.4133 18.828C17.6019 19.0102 17.8545 19.111 18.1167 19.1087C18.3789 19.1064 18.6297 19.0012 18.8151 18.8158C19.0005 18.6304 19.1057 18.3796 19.108 18.1174C19.1102 17.8552 19.0094 17.6026 18.8273 17.414L15.1753 13.762C16.1633 12.5086 16.7784 11.0024 16.9504 9.41573C17.1223 7.82905 16.8441 6.22602 16.1475 4.79009C15.4509 3.35417 14.3642 2.14336 13.0116 1.29623C11.659 0.449106 10.0952 -0.000107143 8.49928 1.91687e-08ZM1.99928 8.5C1.99928 6.77609 2.6841 5.12279 3.90308 3.90381C5.12207 2.68482 6.77537 2 8.49928 2C10.2232 2 11.8765 2.68482 13.0955 3.90381C14.3145 5.12279 14.9993 6.77609 14.9993 8.5C14.9993 10.2239 14.3145 11.8772 13.0955 13.0962C11.8765 14.3152 10.2232 15 8.49928 15C6.77537 15 5.12207 14.3152 3.90308 13.0962C2.6841 11.8772 1.99928 10.2239 1.99928 8.5Z" fill="#09244B"/>
                            </svg>
                        </button>




                        <a href="<?php echo G5_SHOP_URL ?>/cart.php" alt="장바구니" class="top_cart_svg pc" title="장바구니">

                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 17C5.89782 17 6.27936 17.158 6.56066 17.4393C6.84196 17.7206 7 18.1022 7 18.5C7 18.8978 6.84196 19.2794 6.56066 19.5607C6.27936 19.842 5.89782 20 5.5 20C5.10218 20 4.72064 19.842 4.43934 19.5607C4.15804 19.2794 4 18.8978 4 18.5C4 18.1022 4.15804 17.7206 4.43934 17.4393C4.72064 17.158 5.10218 17 5.5 17ZM15.5 17C15.8978 17 16.2794 17.158 16.5607 17.4393C16.842 17.7206 17 18.1022 17 18.5C17 18.8978 16.842 19.2794 16.5607 19.5607C16.2794 19.842 15.8978 20 15.5 20C15.1022 20 14.7206 19.842 14.4393 19.5607C14.158 19.2794 14 18.8978 14 18.5C14 18.1022 14.158 17.7206 14.4393 17.4393C14.7206 17.158 15.1022 17 15.5 17ZM1.138 0C1.89654 9.04185e-05 2.62689 0.287525 3.18203 0.804444C3.73717 1.32136 4.07589 2.02939 4.13 2.786L4.145 3H17.802C18.095 2.99996 18.3844 3.06429 18.6498 3.18844C18.9152 3.31259 19.15 3.49354 19.3378 3.71848C19.5255 3.94342 19.6615 4.20686 19.7362 4.49017C19.8109 4.77348 19.8224 5.06974 19.77 5.358L18.133 14.358C18.0492 14.8188 17.8062 15.2356 17.4466 15.5357C17.0869 15.8357 16.6334 16.0001 16.165 16H4.931C4.42514 16 3.93807 15.8083 3.56789 15.4636C3.1977 15.1188 2.97192 14.6466 2.936 14.142L2.136 2.929C2.11802 2.67645 2.00492 2.44012 1.81951 2.2677C1.6341 2.09528 1.39019 1.99961 1.137 2H1C0.734784 2 0.48043 1.89464 0.292893 1.70711C0.105357 1.51957 0 1.26522 0 1C0 0.734784 0.105357 0.48043 0.292893 0.292893C0.48043 0.105357 0.734784 0 1 0H1.138ZM17.802 5H4.288L4.931 14H16.165L17.802 5Z" fill="#09244B"/>
                            </svg>

                        </a>

                        <a href="<?php echo G5_SHOP_URL ?>/orderinquiry.php" alt="주문조회" class="pc" title="주문조회">


                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M13.586 0C14.1164 0.000113275 14.625 0.210901 15 0.586L17.414 3C17.7891 3.37499 17.9999 3.88361 18 4.414V16C18 16.7956 17.6839 17.5587 17.1213 18.1213C16.5587 18.6839 15.7956 19 15 19H3C2.20435 19 1.44129 18.6839 0.87868 18.1213C0.316071 17.5587 0 16.7956 0 16V4.414C0.000113275 3.88361 0.210901 3.37499 0.586 3L3 0.586C3.37499 0.210901 3.88361 0.000113275 4.414 0H13.586ZM16 6H2V16C2 16.2652 2.10536 16.5196 2.29289 16.7071C2.48043 16.8946 2.73478 17 3 17H15C15.2652 17 15.5196 16.8946 15.7071 16.7071C15.8946 16.5196 16 16.2652 16 16V6ZM12 8C12.2652 8 12.5196 8.10536 12.7071 8.29289C12.8946 8.48043 13 8.73478 13 9C13 10.0609 12.5786 11.0783 11.8284 11.8284C11.0783 12.5786 10.0609 13 9 13C7.93913 13 6.92172 12.5786 6.17157 11.8284C5.42143 11.0783 5 10.0609 5 9C5 8.73478 5.10536 8.48043 5.29289 8.29289C5.48043 8.10536 5.73478 8 6 8C6.26522 8 6.51957 8.10536 6.70711 8.29289C6.89464 8.48043 7 8.73478 7 9C6.99768 9.51898 7.19719 10.0185 7.55638 10.3932C7.91557 10.7678 8.40632 10.9881 8.92494 11.0075C9.44356 11.027 9.94945 10.8441 10.3357 10.4975C10.722 10.1509 10.9584 9.6677 10.995 9.15L11 9C11 8.73478 11.1054 8.48043 11.2929 8.29289C11.4804 8.10536 11.7348 8 12 8ZM13.586 2H4.414L2.414 4H15.586L13.586 2Z" fill="#09244B"/>
                            </svg>


                        </a>

                        <a href="<?php echo G5_SHOP_URL ?>/wishlist.php" alt="위시리스트" class="top_cart_svg pc" title="위시리스트">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.00037 0C10.0612 0 11.0787 0.421427 11.8288 1.17157C12.5789 1.92172 13.0004 2.93913 13.0004 4H15.0354C15.5536 3.99993 16.0515 4.20099 16.4244 4.56081C16.7973 4.92064 17.016 5.41114 17.0344 5.929L17.4624 17.929C17.4719 18.1974 17.4273 18.4649 17.3312 18.7157C17.2351 18.9665 17.0894 19.1953 16.903 19.3886C16.7165 19.5819 16.493 19.7356 16.2459 19.8407C15.9987 19.9457 15.7329 19.9999 15.4644 20H2.53637C2.26781 19.9999 2.00202 19.9457 1.75486 19.8407C1.5077 19.7356 1.28422 19.5819 1.09776 19.3886C0.911293 19.1953 0.765666 18.9665 0.669559 18.7157C0.573453 18.4649 0.528836 18.1974 0.53837 17.929L0.96637 5.929C0.984766 5.41114 1.20344 4.92064 1.57632 4.56081C1.9492 4.20099 2.44719 3.99993 2.96537 4H5.00037C5.00037 2.93913 5.4218 1.92172 6.17194 1.17157C6.92209 0.421427 7.9395 0 9.00037 0ZM5.00037 6H2.96537L2.53637 18H15.4644L15.0354 6H13.0004V7C13.0001 7.25488 12.9025 7.50003 12.7275 7.68537C12.5526 7.8707 12.3134 7.98224 12.059 7.99717C11.8045 8.01211 11.554 7.92933 11.3585 7.76574C11.1631 7.60215 11.0375 7.3701 11.0074 7.117L11.0004 7V6H7.00037V7C7.00009 7.25488 6.90249 7.50003 6.72752 7.68537C6.55255 7.8707 6.31342 7.98224 6.05898 7.99717C5.80453 8.01211 5.55399 7.92933 5.35854 7.76574C5.16308 7.60215 5.03747 7.3701 5.00737 7.117L5.00037 7V6ZM9.00037 2C8.49579 1.99984 8.0098 2.19041 7.63982 2.5335C7.26984 2.87659 7.04321 3.34684 7.00537 3.85L7.00037 4H11.0004C11.0004 3.46957 10.7897 2.96086 10.4146 2.58579C10.0395 2.21071 9.5308 2 9.00037 2Z" fill="#09244B"/>
                            </svg>
                        </a>

                        <a href="<?php echo G5_SHOP_URL ?>/couponzone.php" alt="쿠폰존" class="top_cart_svg pc" title="쿠폰존">

                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.85022 0.356702C9.25397 0.319966 9.66098 0.365447 10.0467 0.4904C10.4324 0.615352 10.7887 0.817183 11.0942 1.0837L11.2442 1.2237L19.0662 9.0467C19.6045 9.58512 19.918 10.3081 19.9431 11.069C19.9681 11.83 19.7029 12.572 19.2012 13.1447L19.0662 13.2887L13.4092 18.9457C12.8708 19.484 12.1478 19.7975 11.3869 19.8225C10.6259 19.8476 9.8839 19.5824 9.31122 19.0807L9.16722 18.9457L1.34322 11.1227C1.05669 10.8362 0.831178 10.4946 0.680278 10.1186C0.529377 9.74252 0.456219 9.3398 0.465217 8.9347L0.476218 8.7297L0.948217 3.5447C1.00882 2.87649 1.29167 2.24787 1.75157 1.75933C2.21147 1.27078 2.82187 0.950511 3.48522 0.849702L3.66422 0.828702L8.85022 0.356702ZM9.15822 2.3457L9.03122 2.3487L3.84622 2.8207C3.63358 2.8399 3.43266 2.92665 3.27288 3.06827C3.1131 3.20988 3.00283 3.39891 2.95822 3.6077L2.94122 3.7257L2.46922 8.9107C2.44649 9.16266 2.5201 9.41386 2.67522 9.6137L2.75822 9.7087L10.5812 17.5317C10.7534 17.7039 10.9825 17.8073 11.2255 17.8226C11.4686 17.8379 11.7088 17.764 11.9012 17.6147L11.9952 17.5317L17.6522 11.8747C17.8244 11.7025 17.9278 11.4734 17.9431 11.2304C17.9584 10.9874 17.8845 10.7471 17.7352 10.5547L17.6522 10.4607L9.82922 2.6377C9.65048 2.45909 9.41074 2.35476 9.15822 2.3457ZM5.63022 5.5107C5.90882 5.2321 6.23956 5.01111 6.60357 4.86033C6.96758 4.70955 7.35772 4.63195 7.75172 4.63195C8.14572 4.63195 8.53586 4.70955 8.89987 4.86033C9.26387 5.01111 9.59462 5.2321 9.87322 5.5107C10.1518 5.7893 10.3728 6.12005 10.5236 6.48405C10.6744 6.84806 10.752 7.2382 10.752 7.6322C10.752 8.0262 10.6744 8.41634 10.5236 8.78035C10.3728 9.14436 10.1518 9.4751 9.87322 9.7537C9.31056 10.3164 8.54743 10.6325 7.75172 10.6325C6.956 10.6325 6.19287 10.3164 5.63022 9.7537C5.06756 9.19105 4.75146 8.42792 4.75146 7.6322C4.75146 6.83648 5.06756 6.07336 5.63022 5.5107ZM8.45922 6.9247C8.36637 6.83179 8.25614 6.75808 8.13481 6.70777C8.01347 6.65746 7.88342 6.63155 7.75207 6.6315C7.62072 6.63145 7.49065 6.65728 7.36928 6.7075C7.24792 6.75772 7.13763 6.83136 7.04472 6.9242C6.95181 7.01705 6.87809 7.12728 6.82779 7.24861C6.77748 7.36995 6.75156 7.5 6.75151 7.63135C6.75147 7.7627 6.77729 7.89277 6.82752 8.01414C6.87774 8.1355 6.95137 8.24579 7.04422 8.3387C7.23173 8.52634 7.48609 8.63181 7.75136 8.6319C8.01663 8.632 8.27108 8.52671 8.45872 8.3392C8.64636 8.15169 8.75183 7.89733 8.75192 7.63206C8.75201 7.36679 8.64673 7.11234 8.45922 6.9247Z" fill="#09244B"/>
                            </svg>

                        </a>

                        <?php if($is_member) { ?>
                        <a href="<?php echo G5_BBS_URL ?>/memo.php" id="rb_memo_top_btn" alt="쪽지" onclick="win_memo(this.href); return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="18" viewBox="0 0 24 20"><path d="M20 4a2 2 0 0 1 1.995 1.85L22 6v12a2 2 0 0 1-1.85 1.995L20 20H4a2 2 0 0 1-1.995-1.85L2 18V6a2 2 0 0 1 1.85-1.995L4 4zm0 3.414-6.94 6.94a1.5 1.5 0 0 1-2.12 0L4 7.414V18h16zM18.586 6H5.414L12 12.586z"/></svg>
                            <?php if($memo_not_read > 0) { ?>
                            <span class="font-H"><?php echo $memo_not_read ?></span>
                            <?php } ?>
                        </a>

                        <?php if ($rb_notification_category_tabs) { ?>
                        <a href="#" id="notification_top_btn" alt="알림" aria-label="알림" aria-haspopup="true" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20"><path d="M5 9a7 7 0 0 1 14 0v3.764l1.822 3.644A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592L5 12.764zm5.268 9a2 2 0 0 0 3.464 0zM12 4a5 5 0 0 0-5 5v3.764a2 2 0 0 1-.211.894L5.619 16h12.763l-1.17-2.342a2.001 2.001 0 0 1-.212-.894V9a5 5 0 0 0-5-5"/></svg>
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

        $('#notification_top_btn').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            isNotificationBoxVisible = !isNotificationBoxVisible;
            if (isNotificationBoxVisible) {
                $notificationBox.show();
                $(this).addClass('notification_open').attr('aria-expanded', 'true');
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

                        <div class="cb"></div>
                    </li>

                    <li class="member_info_wrap">
                        <?php if($is_member) { ?>
                        <?php if(isset($config['cf_use_point']) && $config['cf_use_point'] == 1) { ?>
                        <a href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php" class="font-B notranslate"><?php echo $member['mb_nick'] ?></a>　<a href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank" class="win_point"><span class="font-H"><?php echo number_format($member['mb_point']); ?> P</span></a>
                        <?php } ?>
                        <?php } ?>
                    </li>
                    <li class="my_btn_wrap">
                        <?php if($is_member) { ?>
                            <button type="button" alt="로그아웃" class="btn_round" onclick="location.href='<?php echo G5_BBS_URL ?>/logout.php';">로그아웃</button>
                            <button type="button" alt="마이페이지" class="btn_round arr_bg font-B" onclick="location.href='<?php echo G5_SHOP_URL; ?>/mypage.php';">My</button>
                        <?php } else { ?>
                            <button type="button" alt="로그인" class="btn_round"  onclick="location.href='<?php echo G5_BBS_URL ?>/login.php';">로그인</button>
                            <button type="button" alt="회원가입" class="btn_round arr_bg font-B" onclick="location.href='<?php echo G5_BBS_URL ?>/register.php';">회원가입</button>
                        <?php } ?>
                    </li>

                    <div class="cb"></li>
                </ul>
                <!-- } -->

                <div class="mobile_cb"></div>

                <!-- 검색 { -->
                <ul class="search_top_wrap">

                    <form name="frmsearch1" action="<?php echo G5_SHOP_URL; ?>/search.php" onsubmit="return search_submit(this);">
                        <div class="search_top_wrap_inner">

                        <input type="text" value="<?php echo stripslashes(get_text(get_search_string($q))); ?>" name="q" class="font-B" placeholder="상품검색" required>
                        <button type="submit">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.49928 1.91687e-08C7.14387 0.000115492 5.80814 0.324364 4.60353 0.945694C3.39893 1.56702 2.36037 2.46742 1.57451 3.57175C0.788656 4.67609 0.278287 5.95235 0.0859852 7.29404C-0.106316 8.63574 0.0250263 10.004 0.469055 11.2846C0.913084 12.5652 1.65692 13.7211 2.63851 14.6557C3.6201 15.5904 4.81098 16.2768 6.11179 16.6576C7.4126 17.0384 8.78562 17.1026 10.1163 16.8449C11.447 16.5872 12.6967 16.015 13.7613 15.176L17.4133 18.828C17.6019 19.0102 17.8545 19.111 18.1167 19.1087C18.3789 19.1064 18.6297 19.0012 18.8151 18.8158C19.0005 18.6304 19.1057 18.3796 19.108 18.1174C19.1102 17.8552 19.0094 17.6026 18.8273 17.414L15.1753 13.762C16.1633 12.5086 16.7784 11.0024 16.9504 9.41573C17.1223 7.82905 16.8441 6.22602 16.1475 4.79009C15.4509 3.35417 14.3642 2.14336 13.0116 1.29623C11.659 0.449106 10.0952 -0.000107143 8.49928 1.91687e-08ZM1.99928 8.5C1.99928 6.77609 2.6841 5.12279 3.90308 3.90381C5.12207 2.68482 6.77537 2 8.49928 2C10.2232 2 11.8765 2.68482 13.0955 3.90381C14.3145 5.12279 14.9993 6.77609 14.9993 8.5C14.9993 10.2239 14.3145 11.8772 13.0955 13.0962C11.8765 14.3152 10.2232 15 8.49928 15C6.77537 15 5.12207 14.3152 3.90308 13.0962C2.6841 11.8772 1.99928 10.2239 1.99928 8.5Z" fill="#09244B"/>
                            </svg>
                        </button>
                        </div>
                    </form>

                        <script>
                        function search_submit(f) {
                            if (f.q.value.length < 2) {
                                alert("검색어는 두글자 이상 입력하십시오.");
                                f.q.select();
                                f.q.focus();
                                return false;
                            }
                            return true;
                        }
                        </script>
                </ul>
                <!-- } -->

                <div class="cb"></div>
            </div>

            <?php
            function get_mshop_category($ca_id, $len)
            {
                global $g5;

                $sql = " select ca_id, ca_name from {$g5['g5_shop_category_table']}
                            where ca_use = '1' ";
                if($ca_id)
                    $sql .= " and ca_id like '$ca_id%' ";
                $sql .= " and length(ca_id) = '$len' order by ca_order, ca_id ";

                return $sql;
            }

            $mshop_categories = get_shop_category_array(true);

            ?>

            <div class="rows_gnb_wrap">
                <div class="inner row_gnbs" style="width:<?php echo $tb_width_inner ?>; <?php echo $tb_width_padding ?>">
                    <nav id="cbp-hrmenu" class="cbp-hrmenu pc">
                        <ul>

                        <?php if (isset($rb_core['menu_shop']) && ($rb_core['menu_shop'] == 1 || $rb_core['menu_shop'] == 2)) { ?>

                            <?php
                            $mshop_ca_res1 = sql_query(get_mshop_category('', 2));
                            for($j=0; $mshop_ca_row1=sql_fetch_array($mshop_ca_res1); $j++) {
                            ?>
                                <li>
                                    <a href="<?php echo shop_category_url($mshop_ca_row1['ca_id']); ?>" class="font-B"><?php echo get_text($mshop_ca_row1['ca_name']); ?></a>
                                    <?php
                                    $mshop_ca_res2 = sql_query(get_mshop_category($mshop_ca_row1['ca_id'], 4));

                                    for($k=0; $mshop_ca_row2=sql_fetch_array($mshop_ca_res2); $k++) {
                                        if($k == 0)
                                            echo '<div class="cbp-hrsub"><div class="cbp-hrsub-inner"><div><!--<h4 class="font-B">그룹</h4>--><ul>'.PHP_EOL;

                                        echo '<li>';

                                        echo '<a href="'.shop_category_url($mshop_ca_row2['ca_id']).'">'.get_text($mshop_ca_row2['ca_name']).'</a>';

                                        // // 3차 카테고리
                                        $mshop_ca_res3 = sql_query(get_mshop_category($mshop_ca_row2['ca_id'], 6));
                                        $s = 0;
                                        while($mshop_ca_row3 = sql_fetch_array($mshop_ca_res3)) {

                                            if ($s == 0) {
                                                echo '<i><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></i><dl class="cbp-hrsub-3">'.PHP_EOL;
                                            }

                                            echo '<dd><a href="'.shop_category_url($mshop_ca_row3['ca_id']).'">'.get_text($mshop_ca_row3['ca_name']).'</a></dd>'.PHP_EOL;

                                            $s++;
                                        }

                                        if($s > 0) {
                                            echo '</dl>'.PHP_EOL;
                                        }

                                        echo '</li>'.PHP_EOL;
                                    }

                                    if($k > 0)
                                        echo '</ul></div></div></div>'.PHP_EOL;
                                    ?>
                                </li>
                            <?php } ?>

                            <?php if ($j == 0) { ?>
                                <li><a href="javascript:void(0);">등록된 카테고리가 없습니다.</a></li>
                            <?php } ?>

                        <?php } ?>



                        <?php if (isset($rb_core['menu_shop']) && ($rb_core['menu_shop'] == 2 || $rb_core['menu_shop'] == 0 || $rb_core['menu_shop'] == "")) { ?>

                            <?php
                            if(IS_MOBILE()) {
                                $menu_datas = rb_menu_db_3d(1, true);
                            } else {
                                $menu_datas = rb_menu_db_3d(0, true);
                            }

                            $gnb_zindex = 999;
                            $i = 0;

                            foreach ($menu_datas as $row) {
                                if (empty($row)) continue;

                                // 1차 메뉴 권한 체크
                                if (!$is_admin && isset($row['me_level']) && $row['me_level'] > 0) {
                                    if (isset($row['me_level_opt']) && $row['me_level_opt'] == 2) {
                                        if ($row['me_level'] != $member['mb_level']) continue;
                                    } else {
                                        if ($row['me_level'] > $member['mb_level']) continue;
                                    }
                                }
                            ?>
                                <li>
                                    <a href="<?php echo $row['me_link']; ?>" target="_<?php echo $row['me_target']; ?>" class="font-B"><?php echo $row['me_name'] ?></a>
                                    <?php
                                    $k = 0;

                                    foreach ((array)$row['sub'] as $row2) {
                                        if (empty($row2)) continue;

                                        // 2차 메뉴 권한 체크
                                        if (!$is_admin && isset($row2['me_level']) && $row2['me_level'] > 0) {
                                            if (isset($row2['me_level_opt']) && $row2['me_level_opt'] == 2) {
                                                if ($row2['me_level'] != $member['mb_level']) continue;
                                            } else {
                                                if ($row2['me_level'] > $member['mb_level']) continue;
                                            }
                                        }

                                        if ($k == 0)
                                            echo '<div class="cbp-hrsub"><div class="cbp-hrsub-inner"><div><!--<h4 class="font-B">그룹</h4>--><ul>' . PHP_EOL;

                                        echo '<li>';
                                        echo '<a href="'.$row2['me_link'].'" target="_'.$row2['me_target'].'">'.$row2['me_name'].'</a>';

                                        // // 3차 메뉴 출력
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

                                        $k++;
                                    }

                                    if ($k > 0)
                                        echo '</ul></div></div></div>' . PHP_EOL;
                                    ?>
                                </li>
                            <?php
                                $i++;
                            }

                            if ($i == 0) {
                            ?>
                                <li><a href="javascript:void(0);">메뉴 준비 중입니다.</a></li>
                            <?php } ?>

                        <?php } ?>



                            <li class="gnb_all_menu">
                                <a href="#" class="font-R">전체분류 보기</a>
                                <div class="cbp-hrsub">
                                    <div class="cbp-hrsub-inner">
                                        <?php
                                        $k = 0;
                                        foreach($mshop_categories as $cate1){
                                            if( empty($cate1) ) continue;

                                            $mshop_ca_row1 = $cate1['text'];
                                        ?>
                                        <div>
                                            <h4 class="font-B" onclick="location.href='<?php echo $mshop_ca_row1['url']; ?>';"><?php echo get_text($mshop_ca_row1['ca_name']); ?></h4>
                                            <?php
                                            $h=0;
                                            foreach($cate1 as $key=>$cate2){
                                                if( empty($cate2) || $key === 'text' ) continue;

                                                $mshop_ca_row2 = $cate2['text'];
                                                if($h == 0)
                                                    echo '<ul>'.PHP_EOL;
                                            ?>
                                                <li>
                                                    <a href="<?php echo $mshop_ca_row2['url']; ?>" class="<?php if($ca_id == $mshop_ca_row2['ca_id']) { ?>dp2_active<?php } ?>"><?php echo get_text($mshop_ca_row2['ca_name']); ?></a>
                                                    <?php
                                                    $s=0;
                                                    foreach($cate2 as $key=>$cate3){
                                                        if( empty($cate3) || $key === 'text' ) continue;

                                                        $mshop_ca_row3 = $cate3['text'];
                                                        if($s == 0)
                                                            echo '<dl>'.PHP_EOL;
                                                    ?>
                                                        <dd><a href="<?php echo $mshop_ca_row3['url']; ?>" class="font-R <?php if($ca_id == $mshop_ca_row3['ca_id']) { ?>dp3_active<?php } ?>"><?php echo get_text($mshop_ca_row3['ca_name']); ?></a></dd>
                                                    <?php
                                                        $s++;
                                                    }

                                                    if($s > 0)
                                                        echo '<dd class="dp3_none"><a href="javascript:void(0);" class="font-R"></a></dd></dl>'.PHP_EOL;
                                                    ?>
                                                </li>
                                            <?php
                                                $h++;
                                            }

                                            if($h > 0)
                                                echo '</ul>'.PHP_EOL;
                                            ?>
                                        </div>
                                        <?php
                                            $k++;
                                        }

                                        if($k == 0)
                                            echo '등록된 분류가 없습니다.'.PHP_EOL;
                                        ?>
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </nav>

                </div>
            </div>

        </div>
        <!-- } -->
    </header>
    <!-- } -->
