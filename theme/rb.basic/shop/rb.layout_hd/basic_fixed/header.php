<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 레이아웃 폴더내 style.css 파일
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_SHOP_URL.'/rb.layout_hd/'.$rb_core['layout_hd_shop'].'/style.css?ver='.G5_TIME_YMDHIS.'">', 0);

?>
    <!--
    <header id="header">내용</header>
    <header>는 반드시 포함해주세요.
    -->

    <!-- 헤더 { -->
    <header id="header">

        <!-- GNB { -->
        <div class="gnb_wrap">
            <div class="shop_type_list">
                <div class="inner" style="width:<?php echo $tb_width_inner ?>; <?php echo $tb_width_padding ?>">
                    <a href="<?php echo G5_SHOP_URL ?>/listtype.php?type=1">히트</a>
                    <a href="<?php echo G5_SHOP_URL ?>/listtype.php?type=2">추천</a>
                    <a href="<?php echo G5_SHOP_URL ?>/listtype.php?type=3">신규</a>
                    <a href="<?php echo G5_SHOP_URL ?>/listtype.php?type=4">인기</a>
                    <a href="<?php echo G5_SHOP_URL ?>/listtype.php?type=5">할인</a>
                    <a href="<?php echo G5_SHOP_URL ?>/couponzone.php" class="font-B main_color rb_cp_gap">쿠폰존</a>

                    <a href="<?php echo G5_SHOP_URL ?>/itemuselist.php" class="font-B">후기</a>
                </div>
            </div>
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
                        <a href="<?php echo G5_SHOP_URL ?>" alt="<?php echo $config['cf_title']; ?>">

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
                                alert("검색어는 두글자 이상 입력하세요.");
                                f.q.select();
                                f.q.focus();
                                return false;
                            }
                            return true;
                        }
                        </script>
                </ul>

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
                        <a href="<?php echo G5_BBS_URL ?>/memo.php" alt="쪽지" onclick="win_memo(this.href); return false;">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.9999 2C10.1434 2 8.36294 2.7375 7.05018 4.05025C5.73743 5.36301 4.99993 7.14348 4.99993 9V12.528C5.00008 12.6831 4.96413 12.8362 4.89493 12.975L3.17793 16.408C3.09406 16.5757 3.05445 16.7621 3.06288 16.9494C3.07131 17.1368 3.12748 17.3188 3.22608 17.4783C3.32467 17.6379 3.4624 17.7695 3.6262 17.8608C3.78999 17.9521 3.97441 18 4.16193 18H19.8379C20.0255 18 20.2099 17.9521 20.3737 17.8608C20.5375 17.7695 20.6752 17.6379 20.7738 17.4783C20.8724 17.3188 20.9286 17.1368 20.937 16.9494C20.9454 16.7621 20.9058 16.5757 20.8219 16.408L19.1059 12.975C19.0364 12.8362 19.0001 12.6832 18.9999 12.528V9C18.9999 7.14348 18.2624 5.36301 16.9497 4.05025C15.6369 2.7375 13.8564 2 11.9999 2ZM11.9999 21C11.3793 21.0002 10.7739 20.8079 10.2671 20.4498C9.76025 20.0916 9.37694 19.5851 9.16993 19H14.8299C14.6229 19.5851 14.2396 20.0916 13.7328 20.4498C13.226 20.8079 12.6205 21.0002 11.9999 21Z" fill="#09244B"/>
                            </svg>

                            <?php if($memo_not_read > 0) { ?>
                            <span class="font-H"><?php echo $memo_not_read ?></span>
                            <?php } ?>
                        </a>
                        <?php } ?>

                        <a href="<?php echo G5_SHOP_URL ?>/cart.php" alt="장바구니">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 20C9.26522 20 9.51957 20.1054 9.70711 20.2929C9.89465 20.4804 10 20.7348 10 21C10 21.2652 9.89465 21.5196 9.70711 21.7071C9.51957 21.8946 9.26522 22 9 22C8.73479 22 8.48043 21.8946 8.2929 21.7071C8.10536 21.5196 8 21.2652 8 21C8 20.7348 8.10536 20.4804 8.2929 20.2929C8.48043 20.1054 8.73479 20 9 20ZM16 20C16.2652 20 16.5196 20.1054 16.7071 20.2929C16.8946 20.4804 17 20.7348 17 21C17 21.2652 16.8946 21.5196 16.7071 21.7071C16.5196 21.8946 16.2652 22 16 22C15.7348 22 15.4804 21.8946 15.2929 21.7071C15.1054 21.5196 15 21.2652 15 21C15 20.7348 15.1054 20.4804 15.2929 20.2929C15.4804 20.1054 15.7348 20 16 20ZM2.2 2.9C2.34609 2.70518 2.55825 2.57035 2.79668 2.52083C3.0351 2.4713 3.28341 2.51048 3.495 2.631L3.6 2.701L5.308 3.981C5.59867 4.1991 5.82442 4.49226 5.961 4.829L6.021 5H18.867C19.1381 4.99993 19.4064 5.05498 19.6556 5.16181C19.9047 5.26863 20.1296 5.425 20.3165 5.62141C20.5033 5.81783 20.6483 6.05018 20.7426 6.30436C20.8369 6.55854 20.8786 6.82923 20.865 7.1L20.852 7.248L20.395 10.903C20.2579 12.0029 19.7591 13.0261 18.9771 13.8117C18.1951 14.5973 17.1743 15.1008 16.075 15.243L15.849 15.266L8.536 15.876L8.796 17H17.5C17.7549 17.0003 18 17.0979 18.1854 17.2728C18.3707 17.4478 18.4822 17.687 18.4972 17.9414C18.5121 18.1958 18.4293 18.4464 18.2657 18.6418C18.1021 18.8373 17.8701 18.9629 17.617 18.993L17.5 19H8.796C8.37161 19.0001 7.95819 18.8651 7.61555 18.6147C7.27291 18.3643 7.01881 18.0114 6.89 17.607L6.847 17.45L4.107 5.58L2.4 4.3C2.18783 4.14087 2.04756 3.90397 2.01005 3.64142C1.97255 3.37887 2.04087 3.11217 2.2 2.9Z" fill="#09244B"/>
                            </svg>
                            <?php if(get_boxcart_datas_count() > 0) { ?>
                            <span class="font-H"><?php echo get_boxcart_datas_count() ?></span>
                            <?php } ?>

                        </a>

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
                                    <button type="button" class="rb_my_p_btn" onclick="location.href='<?php echo G5_SHOP_URL ?>/mypage.php';">마이페이지</button>
                                    <button type="button" class="rb_my_p_btn" onclick="location.href='<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php';">정보수정</button>
                                </div>

                                <div class="rb_my_panel_row mt-10">
                                    <button type="button" class="rb_my_p_btn rb_my_p_btn_w" onclick="location.href='<?php echo G5_BBS_URL ?>/logout.php';">로그아웃</button>
                                </div>

                                <div class="rb_my_panel_row mt-20">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        새 메세지
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_BBS_URL ?>/memo.php" alt="새메세지" onclick="win_memo(this.href); return false;" class="font-B font-12"><?php echo $memo_not_read ?>개</a>
                                    </ul>
                                </div>


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
                                        <a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $member['mb_id'] ?>&ca=bbs" alt="게시물" class="font-B font-12">
                                        <?php echo wr_cnt($member['mb_id'], 'w') ?>건
                                        </a>
                                    </ul>
                                </div>
                                <div class="rb_my_panel_row mt-10">
                                    <ul class="rb_my_p_ul1 flex_l color-888 font-12">
                                        댓글
                                    </ul>
                                    <ul class="rb_my_p_ul2 flex_r">
                                        <a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $member['mb_id'] ?>&ca=co" alt="댓글" class="font-B font-12">
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
                                        <?php echo trim(str_replace('구독', '', sb_cnt($member['mb_id']))); ?>
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

            <div class="rows_gnb_wrap" style="width:<?php echo $tb_width_inner ?>; <?php echo $tb_width_padding ?>">
                <div class="inner row_gnbs">
                    <nav id="cbp-hrmenu" class="cbp-hrmenu swiper-container swiper-container-gnb">
                        <ul class="swiper-wrapper swiper-wrapper-gnb">
                            <?php if (isset($rb_core['menu_shop']) && ($rb_core['menu_shop'] == 1 || $rb_core['menu_shop'] == 2)) { ?>

                            <?php
                            $mshop_ca_res1 = sql_query(get_mshop_category('', 2));
                            for($j=0; $mshop_ca_row1=sql_fetch_array($mshop_ca_res1); $j++) {
                            ?>
                                <li class="swiper-slide swiper-slide-gnb">
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
                                <li class="swiper-slide swiper-slide-gnb">
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

                    <script>

                            var swiper = new Swiper('.swiper-container-gnb', {
                                slidesPerView: 'auto',
                                observer: true,
                                observeParents: true,
                                touchRatio: 0,
                                spaceBetween: 45,

                                breakpoints: {
                                    10: {
                                      spaceBetween: 20,
                                      touchRatio: 1,
                                    },
                                    768: {
                                      spaceBetween: 30,
                                      touchRatio: 1,
                                    },
                                    1024: {
                                      spaceBetween: 45,
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


            </div>

        </div>
        <!-- } -->
    </header>
    <!-- } -->
