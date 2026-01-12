<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

global $row_mod, $rb_module_table, $is_admin;
$rb_skin = sql_fetch(" select * from {$rb_module_table} where md_id = '{$row_mod['md_id']}' "); //최신글 환경설정 테이블 조회 (삭제금지)

$i = 0;
$opened = false;

while ($row = sql_fetch_array($result)) {

    $bn_id = isset($row['bn_id']) ? (int)$row['bn_id'] : 0;
    if ($bn_id <= 0) continue;

    $bn_type = (isset($row['bn_type']) && $row['bn_type'] === 'design') ? 'design' : 'image';

    $display_border  = isset($row['bn_border']) && $row['bn_border'] ? ' display_border' : '';
    $display_radius  = isset($row['bn_radius']) && $row['bn_radius'] ? ' display_radius' : '';
    $display_new_win = isset($row['bn_new_win']) && $row['bn_new_win'] ? ' target="_blank" rel="noopener"' : '';

    $can_render = false;
    $dimg = '';

    if ($bn_type === 'image') {
        $dimg = rb_display_file_path($bn_id);
        if ($dimg) $can_render = true;
    } else {
        $can_render = true; // // 디자인형은 이미지 없어도 렌더
    }

    if (!$opened && $can_render) {
        $opened = true;
        echo '<div class="mod_display_wrap">' . PHP_EOL;
        echo '  <div class="swiper-container swiper-container-slide_display swiper-container-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
        echo '    <ul class="swiper-wrapper swiper-wrapper-slide_display swiper-wrapper-slide_display_'.$row_mod['md_id'].'">' . PHP_EOL;
    }

    if (!$can_render) continue;

    echo '      <div class="swiper-slide swiper-slide-slide_display swiper-slide-slide_display_'.$row_mod['md_id'].' slide_item'.$display_border.$display_radius.'">' . PHP_EOL;

    if ($bn_type === 'image') {

        $a_open = '';
        $a_close = '';

        if (isset($row['bn_url'][0]) && $row['bn_url'][0] == '#') {
            $a_open = '<a href="'.$row['bn_url'].'">';
            $a_close = '</a>';
        } else if (!empty($row['bn_url']) && $row['bn_url'] != 'http://') {
            $a_open = '<a href="'.G5_URL.'/rb/rb.mod/display/displayhit.php?did='.$bn_id.'"'.$display_new_win.'>';
            $a_close = '</a>';
        }

        echo '        '.$a_open
            .'<img src="'.G5_URL.'/rb/rb.mod/display/displayimg.php?did='.$bn_id.'&v='.G5_SERVER_TIME.'"'
            .' title="'.get_text($row['bn_alt']).'" width="100%">'.$a_close . PHP_EOL;

        if (isset($row['bn_ad_ico']) && $row['bn_ad_ico']) {
            echo '        <span class="ico_tag">PR</span>' . PHP_EOL;
        }

    } else {

        $design_json = isset($row['bn_design_json']) ? (string)$row['bn_design_json'] : '';
        $stage_json  = isset($row['bn_stage_json']) ? (string)$row['bn_stage_json'] : '';

        $design_json = stripslashes($design_json);
        $stage_json  = stripslashes($stage_json);

        $st = json_decode($design_json, true);
        if (!is_array($st)) $st = array();

        $stage = json_decode($stage_json, true);
        if (!is_array($stage)) $stage = array();

        $ratio = rb_ratio_sanitize(isset($stage['ratio']) ? $stage['ratio'] : '16 / 9');
        $pt = rb_int_range(isset($stage['pad_t']) ? $stage['pad_t'] : 0, 0, 300, 0);
        $pr = rb_int_range(isset($stage['pad_r']) ? $stage['pad_r'] : 0, 0, 300, 0);
        $pb = rb_int_range(isset($stage['pad_b']) ? $stage['pad_b'] : 0, 0, 300, 0);
        $pl = rb_int_range(isset($stage['pad_l']) ? $stage['pad_l'] : 0, 0, 300, 0);

        $valign = isset($stage['valign']) ? (string)$stage['valign'] : 'center';
        if (!in_array($valign, array('top','center','bottom'), true)) $valign = 'center';

        $jc = 'center';
        if ($valign === 'top') $jc = 'flex-start';
        else if ($valign === 'bottom') $jc = 'flex-end';

        $scale_use = isset($stage['scale']) ? (int)$stage['scale'] : 0;
        $base_w = isset($stage['base_w']) ? (int)$stage['base_w'] : 0;
        if ($base_w <= 0) $base_w = isset($stage['width']) ? (int)$stage['width'] : 750;
        if ($base_w <= 0) $base_w = 750;

        $pad_css = ($scale_use === 1)
            ? 'padding:calc('.$pt.'px * var(--rb-scale,1)) calc('.$pr.'px * var(--rb-scale,1)) calc('.$pb.'px * var(--rb-scale,1)) calc('.$pl.'px * var(--rb-scale,1));'
            : 'padding:'.$pt.'px '.$pr.'px '.$pb.'px '.$pl.'px;';

        $bg = isset($st['bg']) && is_array($st['bg']) ? $st['bg'] : array();
        $bg_mode = isset($bg['mode']) ? (string)$bg['mode'] : 'solid';
        $brightness = rb_int_range(isset($bg['brightness']) ? $bg['brightness'] : 100, 30, 200, 100);

        $bg_style = 'background:#f0f5f9;';
        if ($bg_mode === 'solid') {
            $c = isset($bg['solid']) ? (string)$bg['solid'] : '#f0f5f9';
            $bg_style = 'background:'.$c.';';
        } else if ($bg_mode === 'gradient') {
            $g1 = isset($bg['g1']) ? (string)$bg['g1'] : '#f0f5f9';
            $g2 = isset($bg['g2']) ? (string)$bg['g2'] : '#ffffff';
            $ang = rb_int_range(isset($bg['angle']) ? $bg['angle'] : 45, 0, 360, 45);
            $bg_style = 'background:linear-gradient('.$ang.'deg, '.$g1.', '.$g2.');';
        }

        $has_bg_file = rb_design_bg_exists($bn_id);
        $want_has = isset($bg['hasImage']) ? (int)$bg['hasImage'] : 0;

        $full_link = '';
        if (isset($row['bn_url'][0]) && $row['bn_url'][0] == '#') {
            $full_link = $row['bn_url'];
        } else if (!empty($row['bn_url']) && $row['bn_url'] != 'http://') {
            $full_link = G5_URL.'/rb/rb.mod/display/displayhit.php?did='.$bn_id;
        }
        $full_newwin = (isset($row['bn_new_win']) && $row['bn_new_win']) ? 1 : 0;

        echo '        <div class="rb_bd_out" data-href="'.htmlspecialchars($full_link, ENT_QUOTES, 'UTF-8').'" data-newwin="'.$full_newwin.'" data-scale="'.($scale_use===1?'1':'0').'" data-basew="'.$base_w.'" style="--rb-scale:1;aspect-ratio:'.$ratio.';">' . PHP_EOL;

        echo '          <div class="rb_bd_out_bg" style="'.$bg_style.'filter:brightness('.($brightness/100).');">' . PHP_EOL;

        if ($bg_mode === 'image' && $want_has === 1 && $has_bg_file) {
            echo '            <img class="rb_bd_out_bgimg" src="'.G5_URL.'/rb/rb.mod/display/displaydesignimg.php?did='.$bn_id.'&v='.G5_SERVER_TIME.'" alt="">' . PHP_EOL;
        }

        echo '          </div>' . PHP_EOL;

        echo '          <div class="rb_bd_out_stage" style="'.$pad_css.'justify-content:'.$jc.';">' . PHP_EOL;

        $texts = isset($st['texts']) && is_array($st['texts']) ? $st['texts'] : array();
        for ($ti=0; $ti<count($texts); $ti++) {
            $t = $texts[$ti];
            if (!is_array($t)) continue;

            $mt = rb_int_range(isset($t['mt']) ? $t['mt'] : 0, 0, 999, 0);
            $fs = rb_int_range(isset($t['fontSize']) ? $t['fontSize'] : 32, 8, 140, 32);
            $al = isset($t['align']) ? (string)$t['align'] : 'left';
            if (!in_array($al, array('left','center','right'), true)) $al = 'left';
            $co = isset($t['color']) ? (string)$t['color'] : '#000000';
            $fw = isset($t['fontw']) ? (string)$t['fontw'] : 'font-R';
            if (!in_array($fw, array('font-R','font-B','font-H'), true)) $fw = 'font-R';

            $html = isset($t['html']) ? rb_safe_admin_html($t['html']) : '텍스트';

            $mt_css = ($scale_use === 1) ? 'calc('.$mt.'px * var(--rb-scale,1))' : ($mt.'px');
            $fs_css = ($scale_use === 1) ? 'calc('.$fs.'px * var(--rb-scale,1))' : ($fs.'px');

            echo '            <div class="rb_bd_out_txt '.$fw.'" style="margin-top:'.$mt_css.';font-size:'.$fs_css.';text-align:'.$al.';color:'.$co.';font-family:\''.$fw.'\';">'.$html.'</div>' . PHP_EOL;

        }

        $btn = isset($st['btn']) && is_array($st['btn']) ? $st['btn'] : array();
        $btn_use = isset($btn['use']) ? (int)$btn['use'] : 0;

        if ($btn_use === 1) {
            $btn_align = isset($btn['align']) ? (string)$btn['align'] : 'left';
            if (!in_array($btn_align, array('left','center','right'), true)) $btn_align = 'left';

            $btn_text = isset($btn['text']) ? (string)$btn['text'] : '자세히 보기';
            $btn_link = isset($btn['link']) ? trim((string)$btn['link']) : '';

            $px = rb_int_range(isset($btn['px']) ? $btn['px'] : 10, 0, 80, 10);
            $py = rb_int_range(isset($btn['py']) ? $btn['py'] : 16, 0, 80, 16);
            $radius = rb_int_range(isset($btn['radius']) ? $btn['radius'] : 12, 0, 80, 12);
            $font = rb_int_range(isset($btn['font']) ? $btn['font'] : 14, 8, 80, 14);
            $bgc = isset($btn['bg']) ? (string)$btn['bg'] : '#ffffff';
            $tc  = isset($btn['color']) ? (string)$btn['color'] : '#000000';
            $mt  = rb_int_range(isset($btn['mt']) ? $btn['mt'] : 0, 0, 999, 0);

            $border = isset($btn['border']) ? (int)$btn['border'] : 0;
            $border_style = isset($btn['border_style']) ? (string)$btn['border_style'] : 'solid';
            if (!in_array($border_style, array('solid','dashed','dotted','double'), true)) $border_style = 'solid';
            $border_w = rb_int_range(isset($btn['border_w']) ? $btn['border_w'] : 1, 0, 20, 1);
            $border_c = isset($btn['border_color']) ? (string)$btn['border_color'] : '#cccccc';

            $fw = isset($btn['fontw']) ? (string)$btn['fontw'] : 'font-R';
            if (!in_array($fw, array('font-R','font-B','font-H'), true)) $fw = 'font-R';

            // 버튼 링크의 새창 설정 (btn['new_win'] 사용)
            $btn_new_win = isset($btn['new_win']) && $btn['new_win'] ? ' target="_blank" rel="noopener"' : '';

            $pad_btn = ($scale_use === 1)
                ? 'calc('.$py.'px * var(--rb-scale,1)) calc('.$px.'px * var(--rb-scale,1))'
                : ($py.'px '.$px.'px');

            $radius_css = ($scale_use === 1) ? 'calc('.$radius.'px * var(--rb-scale,1))' : ($radius.'px');
            $font_css   = ($scale_use === 1) ? 'calc('.$font.'px * var(--rb-scale,1))' : ($font.'px');
            $mt_btn_css = ($scale_use === 1) ? 'calc('.$mt.'px * var(--rb-scale,1))' : ($mt.'px');
            $bw_css     = ($scale_use === 1) ? 'calc('.$border_w.'px * var(--rb-scale,1))' : ($border_w.'px');

            $btn_style = 'display:inline-flex;align-items:center;justify-content:center;gap:8px;';
            $btn_style .= 'padding:'.$pad_btn.';';
            $btn_style .= 'border-radius:'.$radius_css.';';
            $btn_style .= 'font-size:'.$font_css.';';
            $btn_style .= 'background:'.$bgc.';';
            $btn_style .= 'color:'.$tc.';';
            $btn_style .= 'margin-top:'.$mt_btn_css.';';
            $btn_style .= 'text-decoration:none;';
            $btn_style .= 'font-family:\''.$fw.'\';';

            if ($border === 1) {
                $btn_style .= 'border-style:'.$border_style.';';
                $btn_style .= 'border-color:'.$border_c.';';
                $btn_style .= 'border-width:'.$bw_css.';';
            } else {
                $btn_style .= 'border:none;';
            }

            echo '            <div class="rb_bd_out_btn_wrap" style="text-align:'.$btn_align.';">' . PHP_EOL;

            if ($btn_link !== '') {
                echo '              <a class="rb_bd_out_btn '.$fw.'" href="'.$btn_link.'"'.$btn_new_win.' style="'.$btn_style.'">'.get_text($btn_text).'</a>' . PHP_EOL;
            } else {
                echo '              <span class="rb_bd_out_btn '.$fw.'" style="'.$btn_style.'">'.get_text($btn_text).'</span>' . PHP_EOL;
            }

            echo '            </div>' . PHP_EOL;
        }

        echo '          </div>' . PHP_EOL;
        echo '        </div>' . PHP_EOL;

        if (isset($row['bn_ad_ico']) && $row['bn_ad_ico']) {
            echo '        <span class="ico_tag">PR</span>' . PHP_EOL;
        }
    }

    echo '      </div>' . PHP_EOL;

    $i++;
}

if ($opened && $i > 0) {

    echo '    </ul>' . PHP_EOL;

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

    echo '</div>' . PHP_EOL;
}

if ($i == 0 && $is_admin) {
?>
<div class="mod_display_wrap">
    <div style="padding:20px; background-color:#f9f9f9; text-align:center; color:#999;">
        출력할 배너가 없습니다.
    </div>
</div>
<?php } ?>

<style>
.rb_bd_out{position:relative;width:100%;overflow:hidden;--rb-scale:1}
.rb_bd_out_bg{position:absolute;inset:0;overflow:hidden}
.rb_bd_out_bgimg{width:100%;height:100%;object-fit:cover;display:block}
.rb_bd_out_stage{position:relative;z-index:2;min-height:100%;display:flex;flex-direction:column;justify-content:center}
.rb_bd_out_txt{word-break:break-word;line-height:1.25}
.rb_bd_out_btn_wrap{margin-top:0}
.rb_bd_out_btn:hover {opacity: 0.7;}
.rb_bd_out[data-href]:not([data-href=""]){cursor:pointer}
</style>

<script>
(function(){
    function initClickHandlers() {
        // 버튼 링크들의 이벤트 전파 차단
        var btns = document.querySelectorAll('.rb_bd_out a:not([data-click-bound]), .rb_bd_out_btn:not([data-click-bound])');
        for (var j = 0; j < btns.length; j++) {
            btns[j].setAttribute('data-click-bound', 'true');
            btns[j].addEventListener('click', function(ev) {
                ev.stopPropagation();
            }, false);
        }

        // 전체 영역 클릭 처리
        var wraps = document.querySelectorAll('.rb_bd_out[data-href]:not([data-click-bound])');

        for (var i = 0; i < wraps.length; i++) {
            wraps[i].setAttribute('data-click-bound', 'true');

            (function(wrap) {
                wrap.addEventListener('click', function(ev) {
                    // a 태그나 버튼을 클릭한 경우 전체링크 동작 안함
                    var target = ev.target;
                    if (target.tagName === 'A' || target.closest('a') || target.classList.contains('rb_bd_out_btn')) {
                        return;
                    }

                    var href = wrap.getAttribute('data-href');
                    if (!href) return;

                    // 이벤트 전파 중지
                    ev.stopPropagation();

                    var nw = wrap.getAttribute('data-newwin') === '1';

                    if (nw) window.open(href, '_blank', 'noopener');
                    else window.location.href = href;
                }, false);
            })(wraps[i]);
        }
    }

    // 초기 실행
    initClickHandlers();

    // Swiper 초기화 후 재실행
    setTimeout(initClickHandlers, 100);
    setTimeout(initClickHandlers, 500);
})();
</script>

<script>
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
        speed: <?php echo (isset($rb_skin['md_speed'])) ? (int)$rb_skin['md_speed'] : 400; ?>,
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
}
</script>

<script>
(function(){
    // swiper 스크립트에서 만든 _wrap, swiper를 그대로 사용
    if (typeof _wrap === 'undefined' || !_wrap) return;

    function clampScale(s){
        if (s < 0.2) return 0.2;
        if (s > 3) return 3;
        return s;
    }

    function updateDesignScale(){
        // // 호출 타이밍에 _wrap이 null일 수 있어서 매번 보정
        var wrap = _wrap;

        // // _wrap이 없으면 현재 md_id 셀렉터로 다시 찾기(안 찾히면 그냥 종료)
        if (!wrap) {
            wrap = document.querySelector('.swiper-container-slide_display_<?php echo $row_mod['md_id'] ?>');
            if (!wrap) return;
            _wrap = wrap; // // 다음 호출을 위해 갱신
        }

        var outs = wrap.querySelectorAll('.rb_bd_out[data-scale="1"]');
        if (!outs || outs.length === 0) return;

        for (var i=0;i<outs.length;i++){
            var el = outs[i];
            var basew = parseFloat(el.getAttribute('data-basew')) || 750;

            var w = el.getBoundingClientRect().width;
            if (!w || w <= 0) continue;

            var s = clampScale(w / basew);
            el.style.setProperty('--rb-scale', s.toFixed(4));
        }
    }

    // swiper가 렌더링/사이즈 바뀌는 타이밍에 맞춰 갱신
    if (typeof ResizeObserver !== 'undefined') {
        var ro = new ResizeObserver(function(){ updateDesignScale(); });
        ro.observe(_wrap);
    }
    window.addEventListener('resize', updateDesignScale);

    if (typeof swiper !== 'undefined' && swiper && typeof swiper.on === 'function') {
        swiper.on('resize', updateDesignScale);
        swiper.on('observerUpdate', updateDesignScale);
        swiper.on('slideChangeTransitionEnd', updateDesignScale);
    }

    updateDesignScale();
    setTimeout(updateDesignScale, 60);
})();
</script>
