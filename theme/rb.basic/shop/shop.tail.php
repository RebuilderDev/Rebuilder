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


                    <div class="rb_btm_search_pn">
                       <ul class="fixed_rb_btm_s">
                           <form name="frmsearch2" action="<?php echo G5_SHOP_URL; ?>/search.php" onsubmit="return search_submit2(this);">


                           <li class="rb_btm_search_pn_li1">
                               <button type="button" class="rb_bs_back_btn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_75_6)">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.29303 12.707C8.10556 12.5195 8.00024 12.2652 8.00024 12C8.00024 11.7348 8.10556 11.4805 8.29303 11.293L13.95 5.63601C14.0423 5.5405 14.1526 5.46431 14.2746 5.41191C14.3966 5.3595 14.5278 5.33191 14.6606 5.33076C14.7934 5.3296 14.9251 5.3549 15.048 5.40519C15.1709 5.45547 15.2825 5.52972 15.3764 5.62361C15.4703 5.71751 15.5446 5.82916 15.5949 5.95205C15.6451 6.07495 15.6704 6.20663 15.6693 6.33941C15.6681 6.47219 15.6405 6.60341 15.5881 6.72541C15.5357 6.84742 15.4595 6.95776 15.364 7.05001L10.414 12L15.364 16.95C15.5462 17.1386 15.647 17.3912 15.6447 17.6534C15.6424 17.9156 15.5373 18.1664 15.3518 18.3518C15.1664 18.5372 14.9156 18.6424 14.6534 18.6447C14.3912 18.647 14.1386 18.5462 13.95 18.364L8.29303 12.707Z" fill="#09244B"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_75_6">
                                <rect width="24" height="24" fill="white"/>
                                </clipPath>
                                </defs>
                                </svg>
                               </button>
                           </li>
                           <li class="rb_btm_search_pn_li2">
                               <input type="text" name="q" maxlength="20" class="rb_bs_inp font-B" placeholder="검색어를 입력하세요." required>
                           </li>
                           <li class="rb_btm_search_pn_li3">
                                <button type="submit" class="rb_bs_se_btn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_75_10)">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 2C9.1446 2.00012 7.80887 2.32436 6.60427 2.94569C5.39966 3.56702 4.3611 4.46742 3.57525 5.57175C2.78939 6.67609 2.27902 7.95235 2.08672 9.29404C1.89442 10.6357 2.02576 12.004 2.46979 13.2846C2.91382 14.5652 3.65766 15.7211 4.63925 16.6557C5.62084 17.5904 6.81171 18.2768 8.11252 18.6576C9.41333 19.0384 10.7864 19.1026 12.117 18.8449C13.4477 18.5872 14.6975 18.015 15.762 17.176L19.414 20.828C19.6026 21.0102 19.8552 21.111 20.1174 21.1087C20.3796 21.1064 20.6304 21.0012 20.8158 20.8158C21.0012 20.6304 21.1064 20.3796 21.1087 20.1174C21.111 19.8552 21.0102 19.6026 20.828 19.414L17.176 15.762C18.164 14.5086 18.7792 13.0024 18.9511 11.4157C19.123 9.82905 18.8448 8.22602 18.1482 6.79009C17.4517 5.35417 16.3649 4.14336 15.0123 3.29623C13.6597 2.44911 12.096 1.99989 10.5 2ZM4.00001 10.5C4.00001 8.77609 4.68483 7.12279 5.90382 5.90381C7.1228 4.68482 8.7761 4 10.5 4C12.2239 4 13.8772 4.68482 15.0962 5.90381C16.3152 7.12279 17 8.77609 17 10.5C17 12.2239 16.3152 13.8772 15.0962 15.0962C13.8772 16.3152 12.2239 17 10.5 17C8.7761 17 7.1228 16.3152 5.90382 15.0962C4.68483 13.8772 4.00001 12.2239 4.00001 10.5Z" fill="#09244B"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_75_10">
                                <rect width="24" height="24" fill="white"/>
                                </clipPath>
                                </defs>
                                </svg>
                                </button>
                           </li>
                           </form>

                           <script>
                               function fsearchbox_submit2(f) //검색
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
                       <br><br><br><br>
                       <ul class="rb_btm_skey">
                           <h6 class="font-B">인기 검색어</h6>
                           <li>
                           <?php echo popular("theme/rb.basic", 10); // 인기검색어  ?>
                           </li>
                       </ul>


                       <ul class="rb_btm_sbbs">
                            <h6 class="font-B">요즘 뜨는 상품</h6>

                            <?php
                            $thumb_width  = 200;
                            $thumb_height = 200;

                            $limit_free = 5;

                            // // 상품 테이블
                            $item_table = $g5['g5_shop_item_table'];

                            // // 조회수 높은 상품 TOP N
                            $sql_item = "
                                SELECT
                                    it_id,
                                    it_name,
                                    it_hit,
                                    it_time,
                                    it_price,
                                    ca_id,
                                    ca_id2,
                                    ca_id3
                                FROM {$item_table}
                                WHERE it_use = 1
                                ORDER BY it_hit DESC, it_id DESC
                                LIMIT {$limit_free}
                            ";
                            $res_item = sql_query($sql_item);

                            $item_list = array();
                            for ($i=0; $row = sql_fetch_array($res_item); $i++) {
                                $item_list[] = $row;
                            }
                            ?>



                            <div class="hub_latest">

                                <div class="rb_swiper"
                                    id="rb_swiper_hub_2"
                                    data-pc-w="1"
                                    data-pc-h="5"
                                    data-mo-w="1"
                                    data-mo-h="5"
                                    data-pc-gap="30"
                                    data-mo-gap="20"
                                    data-autoplay="0"
                                    data-autoplay-time="0"
                                    data-pc-swap="0"
                                    data-mo-swap="0"
                                >

                                    <div class="rb_swiper_inner">
                                        <div class="rb-swiper-wrapper swiper-wrapper">

                                            <?php
                                            for ($i=0; $i < count($item_list); $i++) {
                                                $rows = $item_list[$i];

                                                $it_id   = (string)$rows['it_id'];
                                                $hrefs   = shop_item_url($it_id);

                                                // // 썸네일 (영카트 기본 함수 사용)
                                                // // get_it_image()는 상품 이미지 태그를 만들어줌
                                                $img_tag = get_it_image($it_id, $thumb_width, $thumb_height, '', '', $rows['it_name']);

                                                // // 카테고리명(없으면 빈값)
                                                $ca_name = '';
                                                $ca_id = '';
                                                if (!empty($rows['ca_id'])) $ca_id = (string)$rows['ca_id'];
                                                else if (!empty($rows['ca_id2'])) $ca_id = (string)$rows['ca_id2'];
                                                else if (!empty($rows['ca_id3'])) $ca_id = (string)$rows['ca_id3'];

                                                if ($ca_id !== '') {
                                                    $ca_row = sql_fetch("SELECT ca_name FROM {$g5['g5_shop_category_table']} WHERE ca_id = '{$ca_id}' LIMIT 1");
                                                    $ca_name = (string)($ca_row['ca_name'] ?? '');
                                                }

                                                $it_hit  = (int)($rows['it_hit'] ?? 0);
                                                $it_time = (string)($rows['it_time'] ?? '');
                                                $it_price = (int)($rows['it_price'] ?? 0);
                                            ?>

                                            <div class="rb_swiper_list rb_swiper_hub_1_wrap rb_swiper_hub_top_skin">
                                                <ul class="rb_swiper_hub_1_wrap_ul2">
                                                    <li><a href="<?php echo $hrefs; ?>"><?php echo $img_tag; ?></a></li>
                                                </ul>

                                                <ul class="rb_swiper_hub_1_wrap_ul1">
                                                    <li class="rb_swiper_hub_1_wrap_i">
                                                        <span><?php echo $ca_name; ?></span>
                                                    </li>

                                                    <li class="rb_swiper_hub_1_wrap_t cut2 mt-10">
                                                        <a href="<?php echo $hrefs; ?>" class="font-16"><?php echo $rows['it_name']; ?></a>
                                                    </li>

                                                    <li class="rb_swiper_hub_1_wrap_i mt-5">
                                                        <span class="font-B font-14 main_color">
                                                            <?php echo number_format($it_price); ?>원
                                                        </span>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php } ?>

                                            </div>

                                            <?php if (count($item_list) == 0) { ?>
                                              <div>
                                                <ul>
                                                  <li class="no_data">상품이 없어요.</li>
                                                </ul>
                                              </div>
                                            <?php } ?>
                                          </div>


                                </div>


                            </div>



                       </ul>
                   </div>

                   <button type="button" id="m_gnb_close_btn" class="mobile">
                        <img src="<?php echo G5_URL ?>/rb/rb.config/image/icon_close.svg">
                    </button>


                    <button type="button" class="rb_btm_search_btn">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.49928 1.91687e-08C7.14387 0.000115492 5.80814 0.324364 4.60353 0.945694C3.39893 1.56702 2.36037 2.46742 1.57451 3.57175C0.788656 4.67609 0.278287 5.95235 0.0859852 7.29404C-0.106316 8.63574 0.0250263 10.004 0.469055 11.2846C0.913084 12.5652 1.65692 13.7211 2.63851 14.6557C3.6201 15.5904 4.81098 16.2768 6.11179 16.6576C7.4126 17.0384 8.78562 17.1026 10.1163 16.8449C11.447 16.5872 12.6967 16.015 13.7613 15.176L17.4133 18.828C17.6019 19.0102 17.8545 19.111 18.1167 19.1087C18.3789 19.1064 18.6297 19.0012 18.8151 18.8158C19.0005 18.6304 19.1057 18.3796 19.108 18.1174C19.1102 17.8552 19.0094 17.6026 18.8273 17.414L15.1753 13.762C16.1633 12.5086 16.7784 11.0024 16.9504 9.41573C17.1223 7.82905 16.8441 6.22602 16.1475 4.79009C15.4509 3.35417 14.3642 2.14336 13.0116 1.29623C11.659 0.449106 10.0952 -0.000107143 8.49928 1.91687e-08ZM1.99928 8.5C1.99928 6.77609 2.6841 5.12279 3.90308 3.90381C5.12207 2.68482 6.77537 2 8.49928 2C10.2232 2 11.8765 2.68482 13.0955 3.90381C14.3145 5.12279 14.9993 6.77609 14.9993 8.5C14.9993 10.2239 14.3145 11.8772 13.0955 13.0962C11.8765 14.3152 10.2232 15 8.49928 15C6.77537 15 5.12207 14.3152 3.90308 13.0962C2.6841 11.8772 1.99928 10.2239 1.99928 8.5Z" fill="#09244B"></path>
                        </svg>
                    </button>

                    <script>
                    $('.ser_label').click(function() {
                        var dataKey = $(this).attr('data-key');
                        $('.rb_bs_inp').val(dataKey);
                    });
                    document.addEventListener('click', function (ev) {
                        // 검색 버튼 -> 열기
                        var openBtn = ev.target.closest('.rb_btm_search_btn');
                        if (openBtn) {
                            var pn1 = document.querySelector('.rb_btm_search_pn');
                            if (pn1) pn1.classList.add('open');
                            return;
                        }

                        // 뒤로 버튼 -> 닫기
                        var backBtn = ev.target.closest('.rb_bs_back_btn');
                        if (backBtn) {
                            var pn2 = document.querySelector('.rb_btm_search_pn');
                            if (pn2) pn2.classList.remove('open');
                            return;
                        }
                    });
                    </script>

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
        include_once(G5_THEME_PATH.'/rb.theme/rb.config.php'); //테마설정
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

if(defined('_INDEX_') || isset($_GET['gr_id']) && $_GET['gr_id'] || isset($co_id) && $co_id) {
    if ($rb_aos_exists) {
        echo '<script>AOS.init();</script>';
    }
}
?>

<script src="<?php echo G5_JS_URL; ?>/sns.js"></script>
<!-- } 하단 끝 -->

<style>
    @media all and (max-width:1024px) {
        .chat_open_btn {bottom:90px !important;}
    }
</style>


<script>
// 뷰포트 안에 들어온 요소는 AOS 발동 위치와 관계없이 표시
function rb_aos_force_visible() {
    var windowH = window.innerHeight;
    document.querySelectorAll('[data-aos]').forEach(function(el) {
        var rect = el.getBoundingClientRect();
        if (rect.top < windowH && rect.bottom > 0) {
            el.classList.add('aos-animate');
        }
    });
}

document.addEventListener('DOMContentLoaded', rb_aos_force_visible);
window.addEventListener('load', rb_aos_force_visible);

(function() {
    var ticking = false;
    var trailingTimer = null;

    function requestAosVisibleCheck() {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(function() {
                rb_aos_force_visible();
                ticking = false;
            });
        }
        clearTimeout(trailingTimer);
        trailingTimer = setTimeout(rb_aos_force_visible, 120);
    }

    window.addEventListener('scroll', requestAosVisibleCheck, { passive: true });
    window.addEventListener('resize', requestAosVisibleCheck);
})();

function rb_update_mod_label_heights() {
    document.querySelectorAll('.rb-mod-label[data-mod-id]').forEach(function(label) {
        var modId = label.getAttribute('data-mod-id');
        var layoutBox = document.querySelector('.rb_layout_box[data-id="' + modId + '"]');
        if (!layoutBox) return;
        var moduleWrap = layoutBox.querySelector('.rb-module-wrap');
        if (!moduleWrap) return;
        var h = Math.round(moduleWrap.getBoundingClientRect().height);
        var hSpan = label.querySelector('.rb-mod-label-h');
        if (hSpan) hSpan.textContent = h + 'px';
    });
}

window.addEventListener('load', function() {
    setTimeout(rb_update_mod_label_heights, 300);
    var observer = new ResizeObserver(function() {
        rb_update_mod_label_heights();
    });
    document.querySelectorAll('.rb_layout_box[data-id]').forEach(function(box) {
        observer.observe(box);
    });
    setTimeout(function() {
        observer.disconnect();
    }, 7000);
});
</script>

<?php
include_once(G5_THEME_PATH.'/tail.sub.php');
