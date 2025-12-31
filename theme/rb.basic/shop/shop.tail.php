<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

if (G5_IS_MOBILE) {
    include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
    return;
}

$admin = get_admin("super");

?>

       <?php if (!defined("_INDEX_")) { ?>
            <?php if(isset($bo_table) && $bo_table) { ?>
                <div class="rb_bo_btm flex_box rb_sub_module" data-layout="rb_bo_btm_shop_<?php echo $bo_table ?>"></div>
            <?php } ?>
            <?php if(isset($co_id) && $co_id) { ?>
                <div class="rb_co flex_box" data-layout="rb_co_btm_shop_<?php echo $co_id ?>"></div>
            <?php } ?>
            <?php if(isset($_GET['ca_id']) && $_GET['ca_id']) { ?>
                <div class="rb_ca_btm flex_box rb_sub_module" data-layout="rb_ca_btm_shop_<?php echo $_GET['ca_id'] ?>"></div>
            <?php } ?>
            <?php if(isset($_GET['ev_id']) && $_GET['ev_id']) { ?>
                <div class="rb_ev_btm flex_box rb_sub_module" data-layout="rb_ev_btm_shop_<?php echo $_GET['ev_id'] ?>"></div>
            <?php } ?>
            <?php if(isset($it_id) && $it_id) { ?>
                <div class="rb_it_btm flex_box rb_sub_module" data-layout="rb_it_btm_shop_<?php echo $it_id ?>"></div>
            <?php } ?>
            <?php if(isset($fr_id) && $fr_id) { ?>
                <div class="rb_fr_btm flex_box rb_sub_module" data-layout="rb_fr_btm_shop_<?php echo $fr_id ?>"></div>
            <?php } ?>
        <?php } ?>

        <?php if (!defined('_INDEX_') && !$sidebar_hidden) { ?>
            <?php if (!empty($side_float_shop)) { ?>
            </div>
            <?php } ?>

            <?php if (isset($rb_core['sidemenu_shop']) && $rb_core['sidemenu_shop'] == "left" || isset($rb_core['sidemenu_shop']) && $rb_core['sidemenu_shop'] == "right") { ?>
            <div id="rb_sidemenu_shop" class="rb_sidemenu_shop rb_sidemenu_shop_<?php echo isset($rb_core['sidemenu_shop']) ? $rb_core['sidemenu_shop'] : ''; ?> <?php if (isset($rb_core['sidemenu_hide_shop']) && $rb_core['sidemenu_hide_shop'] == "1") { ?>pc<?php } ?>" style="width:<?php echo isset($rb_core['sidemenu_width_shop']) ? $rb_core['sidemenu_width_shop'] : '200'; ?>px; <?php if (isset($rb_core['sidemenu_shop']) && $rb_core['sidemenu_shop'] == "left") { ?>padding-right:<?php echo isset($rb_core['sidemenu_padding_shop']) ? $rb_core['sidemenu_padding_shop'] : '0'; ?>px;<?php } else if (isset($rb_core['sidemenu_shop']) && $rb_core['sidemenu_shop'] == "right") { ?>padding-left:<?php echo isset($rb_core['sidemenu_padding_shop']) ? $rb_core['sidemenu_padding_shop'] : '0'; ?>px;<?php } ?>"><div class="flex_box" data-layout="rb_sidemenu_shop"></div></div>
            <?php } ?>

            <div class="cb"></div>
        <?php } ?>

       </section>
    </div>


    <?php

    if (isset($rb_core['layout_ft_shop']) && $rb_core['layout_ft_shop'] == "") {
        echo "<div class='no_data' style='padding:30px 0 !important; margin-top:0px; border:0px !important; background-color:#f9f9f9;'><span class='no_data_section_ul1 font-B color-000'>선택된 푸터 레이아웃이 없습니다.</span><br>환경설정 패널에서 먼저 푸터 레이아웃을 설정해주세요.</div>";
    } else if (isset($rb_core['layout_ft_shop'])) {
        // 레이아웃 인클루드
        include_once(G5_THEME_SHOP_PATH . '/rb.layout_ft/' . $rb_core['layout_ft_shop'] . '/footer.php');
    } else {
        echo "<div class='no_data' style='padding:30px 0 !important; margin-top:0px; border:0px !important; background-color:#f9f9f9;'><span class='no_data_section_ul1 font-B color-000'>푸터 레이아웃 설정이 올바르지 않습니다.</span><br>환경설정 패널에서 먼저 푸터 레이아웃을 설정해주세요.</div>";
    }

    ?>




                <!-- 전체메뉴 { -->
                <nav id="cbp-hrmenu-btm" class="cbp-hrmenu cbp-hrmenu-btm mobile">

                    <div class="user_prof_bg">
                        <?php if($is_member) { ?>
                            <li class="user_prof_bg_info font-B"><?php echo $member['mb_nick'] ?></li>
                            <li class="user_prof_bg_info font-B"><span><?php echo $member['mb_level'] ?> Lv</span> <a href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank" class="win_point font-B"><span><?php echo number_format($member['mb_point']); ?> P</span></a></li>
                        <?php } else { ?>
                            <li class="user_prof_bg_info font-B">Guest</li>
                        <?php } ?>
                    </div>

                    <div class="user_prof">
                        <?php if($is_member) { ?>
                            <a href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php" class="font-B"><?php echo get_member_profile_img($member['mb_id']); ?></a>
                        <?php } else { ?>
                            <?php echo get_member_profile_img($member['mb_id']); ?>
                        <?php } ?>
                    </div>

                    <div class="user_prof_btns">
                        <li>
                            <?php if($is_member) { ?>
                                <button type="button" alt="로그아웃" class="btn_round" onclick="location.href='<?php echo G5_BBS_URL ?>/logout.php';">로그아웃</button>
                                <button type="button" alt="마이페이지" class="btn_round arr_bg font-B" onclick="location.href='<?php echo G5_SHOP_URL; ?>/mypage.php';">My</button>
                            <?php } else { ?>
                                <button type="button" alt="로그인" class="btn_round" onclick="location.href='<?php echo G5_BBS_URL ?>/login.php';">로그인</button>
                                <button type="button" alt="회원가입" class="btn_round arr_bg font-B" onclick="location.href='<?php echo G5_BBS_URL ?>/register.php';">회원가입</button>
                            <?php } ?>
                        </li>
                    </div>

                    <ul>

                        <?php if ((isset($rb_core['menu_shop']) && $rb_core['menu_shop'] == 1) || (isset($rb_core['menu_shop']) && $rb_core['menu_shop'] == 2)) { ?>

                            <?php
                            // // 1차 카테고리
                            $mshop_ca_res1 = sql_query(get_mshop_category('', 2));
                            for($y=0; $mshop_ca_row1=sql_fetch_array($mshop_ca_res1); $y++) {

                                // // 2차 유무
                                $tmp_res2 = sql_query(get_mshop_category($mshop_ca_row1['ca_id'], 4));
                                $has_2d = (sql_num_rows($tmp_res2) > 0);
                                sql_free_result($tmp_res2);

                                $add_arr = $has_2d ? 'add_arr_svg' : '';
                                $add_arr_btn = $has_2d ? '<button type="button" class="add_arr_btn" aria-label="서브메뉴 열기"></button>' : '';
                            ?>
                                <li class="<?php echo $add_arr; ?>">
                                    <a href="<?php echo shop_category_url($mshop_ca_row1['ca_id']); ?>" class="font-B"><?php echo get_text($mshop_ca_row1['ca_name']); ?></a>
                                    <?php echo $add_arr_btn; ?>

                                    <?php
                                    // // 2차 카테고리 출력
                                    $mshop_ca_res2 = sql_query(get_mshop_category($mshop_ca_row1['ca_id'], 4));
                                    $u = 0;

                                    while($mshop_ca_row2 = sql_fetch_array($mshop_ca_res2)) {

                                        if($u == 0) {
                                            echo '<div class="cbp-hrsub"><div class="cbp-hrsub-inner"><div><ul>'.PHP_EOL;
                                        }

                                        // // 3차 유무 체크
                                        $tmp_res3 = sql_query(get_mshop_category($mshop_ca_row2['ca_id'], 6));
                                        $has_3d = (sql_num_rows($tmp_res3) > 0);
                                        sql_free_result($tmp_res3);

                                        echo '<li class="rb-btm-2d'.($has_3d ? ' rb-has-3d' : '').'">';
                                        echo '<a href="'.shop_category_url($mshop_ca_row2['ca_id']).'">'.get_text($mshop_ca_row2['ca_name']).'</a>';

                                        // // 3차 있으면 버튼 + ul
                                        if ($has_3d) {
                                            echo '<button type="button" class="rb-btm-3d-toggle" aria-label="3차 메뉴 열기"></button>';
                                            echo '<ul class="cbp-hrsub-3">'.PHP_EOL;

                                            $mshop_ca_res3 = sql_query(get_mshop_category($mshop_ca_row2['ca_id'], 6));
                                            while($mshop_ca_row3 = sql_fetch_array($mshop_ca_res3)) {
                                                echo '<li><a href="'.shop_category_url($mshop_ca_row3['ca_id']).'">'.get_text($mshop_ca_row3['ca_name']).'</a></li>'.PHP_EOL;
                                            }
                                            sql_free_result($mshop_ca_res3);

                                            echo '</ul>'.PHP_EOL;
                                        }

                                        echo '</li>'.PHP_EOL;

                                        $u++;
                                    }
                                    sql_free_result($mshop_ca_res2);

                                    if($u > 0) {
                                        echo '</ul></div></div></div>'.PHP_EOL;
                                    }
                                    ?>
                                </li>
                            <?php } ?>

                        <?php } ?>

                        <?php if ((isset($rb_core['menu_shop']) && $rb_core['menu_shop'] == 2) || (isset($rb_core['menu_shop']) && $rb_core['menu_shop'] == 0) || (isset($rb_core['menu_shop']) && $rb_core['menu_shop'] == "")) { ?>

                            <?php
                            // // 기존 get_menu_db 대신 3차 지원 함수 사용
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

                                $has_sub2 = (isset($row['sub']) && is_array($row['sub']) && count($row['sub']) > 0);
                                $add_arr = $has_sub2 ? 'add_arr_svg' : '';
                                $add_arr_btn = $has_sub2 ? '<button type="button" class="add_arr_btn" aria-label="서브메뉴 열기"></button>' : '';
                            ?>
                                <li class="<?php echo $add_arr; ?>">
                                    <a href="<?php echo $row['me_link']; ?>" target="_<?php echo $row['me_target']; ?>" class="font-B"><?php echo $row['me_name']; ?></a>
                                    <?php echo $add_arr_btn; ?>

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

                                        if ($k == 0) {
                                            echo '<div class="cbp-hrsub"><div class="cbp-hrsub-inner"><div><ul>' . PHP_EOL;
                                        }

                                        // // 3차 유무(출력될 것 기준)
                                        $has_3d = (!empty($row2['sub']) && is_array($row2['sub']) && count($row2['sub']) > 0);

                                        echo '<li class="rb-btm-2d'.($has_3d ? ' rb-has-3d' : '').'">';
                                        echo '<a href="'.$row2['me_link'].'" target="_'.$row2['me_target'].'">'.$row2['me_name'].'</a>';

                                        if ($has_3d) {
                                            echo '<button type="button" class="rb-btm-3d-toggle" aria-label="3차 메뉴 열기"></button>';
                                            echo '<ul class="cbp-hrsub-3">' . PHP_EOL;

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

                                                echo '<li><a href="'.$row3['me_link'].'" target="_'.$row3['me_target'].'">'.$row3['me_name'].'</a></li>' . PHP_EOL;
                                            }

                                            echo '</ul>' . PHP_EOL;
                                        }

                                        echo '</li>' . PHP_EOL;

                                        $k++;
                                    }

                                    if ($k > 0) {
                                        echo '</ul></div></div></div>' . PHP_EOL;
                                    }
                                    ?>
                                </li>
                            <?php
                                $i++;
                            }
                            ?>

                        <?php } ?>

                    </ul>
                </nav>
                <!-- } -->

                <script>
                (function () {
                    // // 캡처 단계에서 3차 토글 먼저 처리 (기존 btm 스크립트 간섭 차단)
                    document.addEventListener('click', function (e) {
                        var btn = e.target.closest('#cbp-hrmenu-btm .rb-btm-3d-toggle');
                        if (!btn) return;

                        e.preventDefault();
                        e.stopPropagation();

                        var li = btn.closest('li.rb-btm-2d') || btn.closest('li');
                        if (!li) return;

                        var panel = li.querySelector(':scope > .cbp-hrsub-3') || li.querySelector('.cbp-hrsub-3');
                        if (!panel) return;

                        // // 토글
                        var isOpen = panel.style.display === 'block' || panel.offsetParent !== null;
                        if (isOpen) {
                            panel.style.display = 'none';
                            li.classList.remove('rb-3d-open');
                        } else {
                            // // 형제 3차 닫기(원하면 제거 가능)
                            var sibs = li.parentElement ? li.parentElement.children : [];
                            for (var i = 0; i < sibs.length; i++) {
                                sibs[i].classList.remove('rb-3d-open');
                                var p = sibs[i].querySelector('.cbp-hrsub-3');
                                if (p) p.style.display = 'none';
                            }

                            panel.style.display = 'block';
                            li.classList.add('rb-3d-open');
                        }
                    }, true); // true = capture
                })();
                </script>



<button type="button" id="m_gnb_close_btn" class="mobile">
    <img src="<?php echo G5_URL ?>/rb/rb.config/image/icon_close.svg">
</button>

<script>
    $(document).ready(function() {
        $('#m_gnb_close_btn').click(function() {
            $('#cbp-hrmenu-btm').removeClass('active');
            $('#m_gnb_close_btn').removeClass('active');
            $('main').removeClass('moves');
            $('header').removeClass('moves');
        });
    });
</script>


<script src="<?php echo G5_THEME_URL ?>/rb.js/cbpHorizontalMenu.min.js"></script>
<script>
    $(function() {
        cbpHorizontalMenu.init();
        cbpHorizontalMenu_btm.init();
    });
</script>
<!-- } -->

<!-- 캘린더 옵션 { -->
<script>
    $.datepicker.setDefaults({
        closeText: "닫기",
        prevText: "이전달",
        nextText: "다음달",
        currentText: "오늘",
        monthNames: ["1월", "2월", "3월", "4월", "5월", "6월",
            "7월", "8월", "9월", "10월", "11월", "12월"
        ],
        monthNamesShort: ["1월", "2월", "3월", "4월", "5월", "6월",
            "7월", "8월", "9월", "10월", "11월", "12월"
        ],
        dayNames: ["일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일"],
        dayNamesShort: ["일", "월", "화", "수", "목", "금", "토"],
        dayNamesMin: ["일", "월", "화", "수", "목", "금", "토"],
        weekHeader: "주",
        dateFormat: "yy-mm-dd",
        firstDay: 0,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: "년"
    })

    $(".datepicker_inp").datepicker({
        //minDate: 0
    })
</script>

<link rel="stylesheet" href="<?php echo G5_THEME_URL ?>/rb.css/datepicker.css" />
<!-- } -->


<?php
    //리빌드세팅
    if($is_admin) {
        include_once(G5_PATH.'/rb/rb.config/right.php'); //환경설정
    }

    // HOOK 추가, (tail.php 가 로드되는 페이지에서만 / 쪽지, 로그인 등의 모듈 페이지에서는 실행 되지않게 하기위함.)
    // 관련 HOOK : add_event('tail_sub', 'aaa');
    $rb_hook_tail = "true";

?>

<?php
$sec = get_microtime() - $begin_time;
$file = $_SERVER['SCRIPT_NAME'];

if ($config['cf_analytics']) {
    echo $config['cf_analytics'];
}

if ($rb_aos_exists) {
    echo '<script>AOS.init();</script>';
}
?>

<script src="<?php echo G5_JS_URL; ?>/sns.js"></script>
<!-- } 하단 끝 -->

<style>
    @media all and (max-width:1024px) {
        .chat_open_btn {bottom:90px !important;}
    }
</style>

<?php
include_once(G5_THEME_PATH.'/tail.sub.php');
