<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH.'/head.php');
    return;
}

if(G5_COMMUNITY_USE === false) {
    define('G5_IS_COMMUNITY_PAGE', true);
    include_once(G5_THEME_SHOP_PATH.'/shop.head.php');
    return;
}
include_once(G5_THEME_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/poll.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');

if(defined('_INDEX_')) { // index에서만 실행
    include G5_BBS_PATH.'/newwin.inc.php'; // 팝업레이어
}

include_once(G5_PATH.'/rb/rb.mod/alarm/alarm.php'); // 실시간 알림

if(defined('_INDEX_') || isset($_GET['gr_id']) && $_GET['gr_id'] || isset($co_id) && $co_id) {
    if ($rb_aos_exists) {
        echo '<script src="'.G5_URL.'/rb/rb.mod/aos/aos.set.php"></script>'."\n";
        echo '<link rel="stylesheet" href="'.G5_URL.'/rb/rb.mod/aos/aos.css">'."\n";
        echo '<script src="'.G5_URL.'/rb/rb.mod/aos/aos.js"></script>'."\n";
    }
}

?>



    <?php

    if (isset($rb_core['layout_hd']) && $rb_core['layout_hd'] == "") {
        echo "<div class='no_data' style='padding:30px 0 !important; margin-top:0px; border:0px !important; background-color:#f9f9f9;'><span class='no_data_section_ul1 font-B color-000'>선택된 헤더 레이아웃이 없습니다.</span><br>환경설정 패널에서 먼저 헤더 레이아웃을 설정해주세요.</div>";
    } else if (isset($rb_core['layout_hd'])) {
        // 레이아웃 인클루드
        include_once(G5_THEME_PATH . '/rb.layout_hd/' . $rb_core['layout_hd'] . '/header.php');
    } else {
        echo "<div class='no_data' style='padding:30px 0 !important; margin-top:0px; border:0px !important; background-color:#f9f9f9;'>헤더 레이아웃 설정이 올바르지 않습니다.</span><br>환경설정 패널에서 먼저 헤더 레이아웃을 설정해주세요.</div>";
    }

    ?>


    <script>
        function adjustContentPadding() {
            // header의 높이 구하기
            var height_header = $('#header').outerHeight();
            var sticky_header = $('#header').outerHeight() + 30;
            // contents_wrap 에 구해진 높이값 적용
            $('#contents_wrap').css('padding-top', height_header + 'px');
            $('#rb_sidemenu').css('top', sticky_header + 'px');
        }

        $(document).ready(function() {
            // 처음 페이지 로드 시 호출
            adjustContentPadding();

            // 브라우저 리사이즈 시 호출
            $(window).resize(function() {
                adjustContentPadding();
            });
        });
    </script>

    <div class="contents_wrap" id="contents_wrap">

        <?php if (!defined("_INDEX_")) { ?>
            <?php include_once(G5_PATH.'/rb/rb.config/topvisual.php'); ?>
        <?php } ?>

        <?php
        add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/rb.theme/css/style.css?ver='.G5_SERVER_TIME.'">', 0);
        add_javascript('<script src="'.G5_THEME_URL.'/rb.theme/js/rb.carousel.js?ver=3"></script>', 0);
        ?>

        <?php
        $current_mode = defined('_SHOP_') ? 'shop' : 'community';
        $filtered_carousel = array_filter($rb_carousel_data, function($item) use ($current_mode) {
            return $item['carousel_type_mode'] === $current_mode;
        });
        $filtered_carousel = array_values($filtered_carousel);

        $carousel_use   = defined('_SHOP_') ? (int)$rb_theme_row['carousel_use_shop']   : (int)$rb_theme_row['carousel_use'];
        $carousel_type  = defined('_SHOP_') ? $rb_theme_row['carousel_type_shop']        : $rb_theme_row['carousel_type'];
        $carousel_speed = defined('_SHOP_') ? (int)$rb_theme_row['carousel_speed_shop']  : (int)$rb_theme_row['carousel_speed'];
        $carousel_time  = defined('_SHOP_') ? (int)$rb_theme_row['carousel_time_shop']   : (int)$rb_theme_row['carousel_time'];
        $carousel_height_pc = max(1, (int)(defined('_SHOP_') ? $rb_theme_row['carousel_height_pc_shop'] : $rb_theme_row['carousel_height_pc']));
        $carousel_height_mo = max(1, (int)(defined('_SHOP_') ? $rb_theme_row['carousel_height_mo_shop'] : $rb_theme_row['carousel_height_mo']));
        ?>
    <?php if ($carousel_use === 1) { ?>
    <?php if(defined('_INDEX_')) { ?>

    <?php if (empty($filtered_carousel)) { ?>
    <!-- 캐러셀 데이터가 없을 때 -->
    <div class="rb_carousel rb_carousel_main" style="--rb-carousel-height-pc: <?php echo $carousel_height_pc; ?>px; --rb-carousel-height-mo: <?php echo $carousel_height_mo; ?>px; background-color: #25282B;">
        <ul class="rb_carousel_img">
            <li>
                <div class="bg">
                    <div class="bg_bl"></div>
                </div>
                <div class="slogan">
                    <div class="inner">
                        <span class="text1 font-B" style="font-size: 32px; color: #ffffff; text-align: center;">
                            하단의 테마설정 패널 에서 캐러셀을 설정해주세요.
                        </span>
                        <span class="text2 font-R">
                            <p class="text2_sub" style="font-size: 16px; color: #cccccc; margin-top: 20px; text-align: center;">
                                캐러셀을 추가하시면 본 영역에 출력 됩니다.<br>
                                서브페이지 캐러셀은 캐러셀 설정에서 지정하실 수 있습니다.
                            </p>
                        </span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <script>
        $('.rb_carousel').rb_carousel({
            type: '<?php echo $carousel_type; ?>',
            speed: <?php echo $carousel_speed; ?>,
            autoRollingTime: <?php echo $carousel_time; ?>
        });
    </script>
    <?php } else { ?>
    <!-- 캐러셀 데이터가 있을 때 -->
    <div class="rb_carousel rb_carousel_main" style="--rb-carousel-height-pc: <?php echo $carousel_height_pc; ?>px; --rb-carousel-height-mo: <?php echo $carousel_height_mo; ?>px;">
        <ul class="rb_carousel_img">
            <?php foreach ($filtered_carousel as $item) { ?>
            <li>
                <?php if ($item['image_path']) { ?>
                <div class="bg" style="background:url('<?php echo $item['image_path']; ?>') no-repeat center /cover">
                    <div class="bg_bl"></div>
                </div>
                <?php } ?>
                <div class="slogan">
                    <div class="inner">
                        <span class="text1 <?php echo $item['main_weight']; ?>" style="font-size: <?php echo $item['main_size']; ?>px; color: <?php echo $item['main_color']; ?>; text-align: <?php echo $item['main_align']; ?>;">
                            <?php echo nl2br(stripslashes($item['main_text'])); ?>
                        </span>
                        <span class="text2 <?php echo $item['sub_weight']; ?>">
                            <p class="text2_sub" style="font-size: <?php echo $item['sub_size']; ?>px; color: <?php echo $item['sub_color']; ?>; margin-top: <?php echo $item['sub_margin']; ?>px; text-align: <?php echo $item['sub_align']; ?>;">
                                <?php echo nl2br(stripslashes($item['sub_text'])); ?>
                            </p>

                            <?php if ($item['btn_text']) { ?>
                            <div class="rb_carousel_btn_more" style="text-align: <?php echo $item['btn_align']; ?>; margin-top: <?php echo isset($item['btn_margin']) ? (int)$item['btn_margin'] : 0; ?>px;">
                                <button class="rb_carousel_link <?php echo isset($item['btn_weight']) ? $item['btn_weight'] : 'font-R'; ?>" onclick="<?php echo $item['btn_link_blank'] ? "window.open('" . $item['btn_link'] . "','_blank');" : "location.href='" . $item['btn_link'] . "';"; ?>" style="
                                                font-size: <?php echo $item['btn_size']; ?>px;
                                                border-radius: <?php echo $item['btn_radius']; ?>px;
                                                border: <?php echo $item['btn_border']; ?>px solid <?php echo $item['btn_border_color']; ?>;
                                                background-color: <?php echo $item['btn_bg_color']; ?>;
                                                color: <?php echo $item['btn_text_color']; ?>;
                                                padding: <?php echo $item['btn_padding']; ?>px <?php echo isset($item['btn_padding_lr']) ? $item['btn_padding_lr'] : 20; ?>px;
                                            ">
                                    <?php echo strip_tags(stripslashes($item['btn_text'])); ?>
                                </button>
                            </div>
                            <?php } ?>

                        </span>
                    </div>
                </div>
            </li>
            <?php } ?>
        </ul>
        <span class="rb_carousel_btn_prev">prev</span>
        <span class="rb_carousel_btn_next">next</span>
        <ul class="rb_carousel_btn"></ul>
    </div>
    <script>
        $('.rb_carousel').rb_carousel({
            type: '<?php echo $rb_theme_row['carousel_type']; ?>',
            speed: <?php echo (int)$rb_theme_row['carousel_speed']; ?>,
            autoRollingTime: <?php echo (int)$rb_theme_row['carousel_time']; ?>
        });
    </script>
    <?php } ?>
    <?php } else { ?>
    <?php
                $sub_item = null;
                foreach ($filtered_carousel as $fitem) {
                    if (!empty($fitem['is_sub'])) {
                        $sub_item = $fitem;
                        break;
                    }
                }
                ?>
    <?php if ($sub_item) { ?>
    <div class="rb_carousel sub_rb_carousel_b">
        <ul class="rb_carousel_img">
            <li>
                <?php if ($sub_item['image_path']) { ?>
                <div class="bg" style="background:url('<?php echo $sub_item['image_path']; ?>') no-repeat center /cover">
                    <div class="bg_bl"></div>
                </div>
                <?php } else { ?>
                <div class="bg" style="background-color:#000;">
                    <div class="bg_bl"></div>
                </div>
                <?php } ?>
                <div class="slogan">
                    <div class="inner">
                        <span class="text1 font-B text-center"><?php echo get_head_title($g5['title']); ?></span>
                    </div>
                </div>
            </li>
        </ul>
        <span class="rb_carousel_btn_prev">prev</span>
        <span class="rb_carousel_btn_next">next</span>
        <ul class="rb_carousel_btn"></ul>
    </div>
    <script>
        $('.rb_carousel').rb_carousel({
            type: '<?php echo $rb_theme_row['carousel_type']; ?>',
            speed: <?php echo (int)$rb_theme_row['carousel_speed']; ?>,
            autoRollingTime: <?php echo (int)$rb_theme_row['carousel_time']; ?>
        });
    </script>
    <?php } ?>
    <?php } ?>
    <?php } else { ?>

    <?php } ?>

        <!--
        $rb_core['sub_width'] 는 반드시 포함해주세요 (환경설정 > 서브가로폭)
        모듈박스 스타일 설정
        md_border_ : (solid, dashed)
        md_radius_ : (0~30)
        co_inner_padding_ : (0~30)
        co_gap_ : (0~30)
        -->

        <section class="<?php if (defined("_INDEX_")) { ?>index co_gap_pc_<?php echo $rb_core['gap_pc'] ?><?php } else { ?>sub co_gap_pc_<?php echo $rb_core['gap_pc'] ?><?php } ?>">

        <?php
            $safe = sql_escape_string($rb_page_urls);
            $row = sql_fetch("SELECT 1 AS ok FROM rb_sidebar_hide WHERE s_code='{$safe}' LIMIT 1");
            $sidebar_hidden = (bool)$row;
        ?>

        <?php if (!defined('_INDEX_') && !$sidebar_hidden) { ?>

            <?php
                $side_float = "";
                if (isset($rb_core['sidemenu']) && $rb_core['sidemenu'] == "left" && !$sidebar_hidden) {
                    $side_float = "float:right; width: calc(100% - ".$rb_core['sidemenu_width']."px);";
                } else if (isset($rb_core['sidemenu']) && $rb_core['sidemenu'] == "right" && !$sidebar_hidden) {
                    $side_float = "float:left; width: calc(100% - ".$rb_core['sidemenu_width']."px);";
                }
            ?>
            <?php if (!empty($side_float)) { ?>
            <div id="rb_sidemenu_float" style="<?php echo $side_float ?>">
            <?php } ?>

        <?php } ?>


        <?php if (!defined("_INDEX_")) { ?>
            <?php if(isset($bo_table) && $bo_table) { ?>
                <div class="rb_bo_top flex_box rb_sub_module" data-layout="rb_bo_top_<?php echo $bo_table ?>"></div>
            <?php } ?>
            <?php if(isset($co_id) && $co_id) { ?>
                <div class="rb_co_top flex_box rb_sub_module" data-layout="rb_co_top_<?php echo $co_id ?>"></div>
            <?php } ?>
            <?php if(isset($fr_id) && $fr_id) { ?>
                <div class="rb_fr_top flex_box rb_sub_module" data-layout="rb_fr_top_<?php echo $fr_id ?>"></div>
            <?php } ?>
        <?php } ?>

        <?php if (!defined("_INDEX_")) { ?>
        <h2 id="container_title"><?php echo get_head_title($g5['title']); ?></h2>
        <?php } ?>

