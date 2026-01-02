<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

global $row_mod, $rb_module_table, $is_admin;
$rb_skin = sql_fetch(" select * from {$rb_module_table} where md_id = '{$row_mod['md_id']}' "); //최신글 환경설정 테이블 조회 (삭제금지)

// // rb_display 우선, 없으면 기존 banners 폴더 fallback
if (!function_exists('rb_display_file_path')) {
    function rb_display_file_path($id) {
        $id = preg_replace('/[^0-9]/', '', (string)$id);
        if ($id === '') return '';

        $cands = array(
            G5_DATA_PATH . '/rb_display/' . $id, // // 신규 폴더(권장)
            G5_DATA_PATH . '/banners/' . $id     // // 기존 폴더(호환)
        );

        for ($i = 0; $i < count($cands); $i++) {
            if (is_file($cands[$i])) return $cands[$i];
        }
        return '';
    }
}
?>

<?php
$i = 0; // $i 변수를 초기화

while ($row = sql_fetch_array($result)) {

    // // 스타일 옵션(기존 bn_* 값은 유지)
    $display_border  = isset($row['bn_border']) && $row['bn_border'] ? ' display_border' : '';
    $display_radius  = isset($row['bn_radius']) && $row['bn_radius'] ? ' display_radius' : '';

    // // 새창 옵션
    $display_new_win = isset($row['bn_new_win']) && $row['bn_new_win'] ? ' target="_blank"' : '';

    // // 실제 파일 경로(폴더명 변경/이관 대비)
    $dimg = rb_display_file_path($row['bn_id']);

    // // 첫 래퍼 오픈은 "실제 이미지가 있을 때만"
    if ($i == 0 && $dimg) {
        echo '<div class="mod_display_wrap">' . PHP_EOL;
        echo '  <div class="swiper-container swiper-container-slide_display swiper-container-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
        echo '    <ul class="swiper-wrapper swiper-wrapper-slide_display swiper-wrapper-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
    }

    if ($dimg) {

        // // getimagesize 워닝 방지(정보가 필요 없다면 그냥 호출만 해도 됨)
        $size = @getimagesize($dimg);

        echo '      <div class="swiper-slide swiper-slide-slide_display swiper-slide-slide_display_'.$row_mod['md_id'].' slide_item'.$display_border.$display_radius.'">' . PHP_EOL;

        $a_open = '';
        $a_close = '';

        if (isset($row['bn_url'][0]) && $row['bn_url'][0] == '#') {
            $a_open = '<a href="'.$row['bn_url'].'">';
            $a_close = '</a>';
        } else if (!empty($row['bn_url']) && $row['bn_url'] != 'http://') {
            // // 클릭 집계(새 경로)
            $a_open = '<a href="'.G5_URL.'/rb/rb.mod/display/displayhit.php?did='.$row['bn_id'].'"'.$display_new_win.'>';
            $a_close = '</a>';
        }

        // // 이미지 URL은 폴더 직접 노출 대신 displayimg.php로 통일(폴더명 변경 대응)
        echo '        '.$a_open
            .'<img src="'.G5_URL.'/rb/rb.mod/display/displayimg.php?did='.$row['bn_id'].'&v='.G5_SERVER_TIME.'"'
            .' title="'.get_text($row['bn_alt']).'" width="100%">'.$a_close . PHP_EOL;

        // // AD 아이콘 클래스에서 ad 문자열 제거(표시는 그대로 AD 가능)
        if (isset($row['bn_ad_ico']) && $row['bn_ad_ico']) {
            echo '        <span class="ico_tag">PR</span>' . PHP_EOL;
        }

        echo '      </div>' . PHP_EOL;

        $i++; // // 실제 출력된 슬라이드가 있을 때만 카운트
    }
}

if ($i > 0) {
    echo '    </ul>' . PHP_EOL;

    // // 스와이퍼 버튼(사용 설정 + 슬라이드 있을 때만)
    if (isset($rb_skin['md_swiper_is']) && (int)$rb_skin['md_swiper_is'] == 1) {
        echo '  </div>' . PHP_EOL;
        echo '  <div class="rb_swiper_paging_btn">' . PHP_EOL;

        echo '    <div class="swiper-button-next swiper-button-next-slide_display swiper-button-next-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
        echo '      <svg width="24" height="46" viewBox="0 0 24 46" fill="none" xmlns="http://www.w3.org/2000/svg">' . PHP_EOL;
        echo '        <path d="M1 45L22.3333 23L1 1" stroke="#09244B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' . PHP_EOL;
        echo '      </svg>' . PHP_EOL;
        echo '    </div>' . PHP_EOL;

        echo '    <div class="swiper-button-prev swiper-button-prev-slide_display swiper-button-prev-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
        echo '      <svg width="24" height="46" viewBox="0 0 24 46" fill="none" xmlns="http://www.w3.org/2000/svg">' . PHP_EOL;
        echo '        <path d="M23 0.999999L1.66667 23L23 45" stroke="#09244B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' . PHP_EOL;
        echo '      </svg>' . PHP_EOL;
        echo '    </div>' . PHP_EOL;

        echo '  </div>' . PHP_EOL;
    }

    // // 래퍼 닫기(컨테이너/버튼 여부와 무관하게 마지막에 닫아줌)
    echo '</div>' . PHP_EOL;
}
?>

<?php if ($i == 0 && $is_admin) { ?>
<div class="mod_display_wrap">
    <div style="padding:20px; background-color:#f9f9f9; text-align:center; color:#999;">
        출력할 항목이 없습니다. 관리를 확인해주세요
    </div>
</div>
<?php } ?>

<script>
    // // 슬라이드/컨테이너 없으면 Swiper 실행 안함
    var _wrap = document.querySelector('.swiper-container-slide_display_<?php echo $row_mod['md_id'] ?>');
    if (!_wrap) { /* noop */ }
    else if (!_wrap.querySelector('.swiper-slide')) { /* noop */ }
    else {
        var swiper = new Swiper('.swiper-container-slide_display_<?php echo $row_mod['md_id'] ?>', {
            slidesPerView: <?php echo (!empty($rb_skin['md_col'])) ? (int)$rb_skin['md_col'] : 1; ?>,
            spaceBetween: <?php echo (!empty($rb_skin['md_gap'])) ? (int)$rb_skin['md_gap'] : 0; ?>,
            slidesPerColumnFill: 'row',
            slidesPerColumn: <?php echo (!empty($rb_skin['md_row'])) ? (int)$rb_skin['md_row'] : 1; ?>,
            touchRatio: <?php echo (!empty($rb_skin['md_swiper_is'])) ? (int)$rb_skin['md_swiper_is'] : 0; ?>,
            observer: true,
            observeParents: true,
            navigation: {
                nextEl: '.swiper-button-next-slide_display_<?php echo $row_mod['md_id'] ?>',
                prevEl: '.swiper-button-prev-slide_display_<?php echo $row_mod['md_id'] ?>'
            },
            <?php if (isset($rb_skin['md_auto_is']) && (int)$rb_skin['md_auto_is'] == 1) { ?>
            autoplay: {
                delay: <?php echo (!empty($rb_skin['md_auto_time'])) ? (int)$rb_skin['md_auto_time'] : 3000; ?>,
                disableOnInteraction: false
            },
            <?php } ?>
            breakpoints: {
                1024: {
                    slidesPerView: <?php echo (!empty($rb_skin['md_col'])) ? (int)$rb_skin['md_col'] : 1; ?>,
                    spaceBetween: <?php echo (!empty($rb_skin['md_gap'])) ? (int)$rb_skin['md_gap'] : 0; ?>,
                    slidesPerColumn: <?php echo (!empty($rb_skin['md_row'])) ? (int)$rb_skin['md_row'] : 1; ?>,
                    slidesPerColumnFill: 'row'
                },
                10: {
                    slidesPerView: <?php echo (!empty($rb_skin['md_col_mo'])) ? (int)$rb_skin['md_col_mo'] : 1; ?>,
                    spaceBetween: <?php echo (!empty($rb_skin['md_gap_mo'])) ? (int)$rb_skin['md_gap_mo'] : 0; ?>,
                    slidesPerColumn: <?php echo (!empty($rb_skin['md_row_mo'])) ? (int)$rb_skin['md_row_mo'] : 1; ?>,
                    slidesPerColumnFill: 'row'
                }
            }
        });

        // 마진 초기화
        (function(sw) {
            if (!sw) return;

            function resetSlideMargins(s) {
                var slides = s.slides || (s.$wrapperEl ? s.$wrapperEl[0].querySelectorAll('.swiper-slide') : []);
                var len = slides.length || 0;
                for (var i = 0; i < len; i++) {
                    slides[i].style.marginTop = '';
                }
                s.updateSize();
                s.updateSlides();
                s.updateSlidesClasses();
            }

            sw.on('breakpoint', function() {
                resetSlideMargins(sw);
            });
            sw.on('resize', function() {
                resetSlideMargins(sw);
            });
            sw.on('imagesReady', function() {
                resetSlideMargins(sw);
            });
        })(swiper);
    }
</script>
