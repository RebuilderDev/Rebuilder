<?php if (defined('RB_THEME_CONFIG') && RB_THEME_CONFIG && isset($is_admin) && $is_admin) { ?>
<link rel="stylesheet" href="<?php echo G5_THEME_URL ?>/rb.theme/css/config.css?ver=<?php echo G5_SERVER_TIME ?>" />

<div class="sh-side-options2">
    <div class="sh-side-demos-container-theme">
        <div class="rb_config">
            <h2 class="font-B">테마설정</h2>
            <h6 class="font-R rb_config_sub_txt">현재 적용중인 테마의 전용 설정을 변경합니다.<br>테마별로 고유한 설정이 있을 수 있습니다.<br>테마의 변경은 관리자모드 > 환경설정 > 테마설정 을 이용해주세요.</h6>

            <?php
                // // 1) readme.txt 파싱
                $readme_file = G5_THEME_PATH . '/readme.txt';

                $info = array(
                    'Theme Name' => '',
                    'Version' => '',
                    'Maker' => '',
                    'Maker URI' => ''
                );

                if (is_file($readme_file) && is_readable($readme_file)) {
                    $lines = file($readme_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (is_array($lines)) {
                        foreach ($lines as $line) {
                            $line = trim((string)$line);
                            if ($line === '' || strpos($line, ':') === false) continue;

                            list($k, $v) = explode(':', $line, 2);
                            $k = trim((string)$k);
                            $v = trim((string)$v);

                            if (array_key_exists($k, $info)) {
                                $info[$k] = $v;
                            }
                        }
                    }
                }

                $theme_name = $info['Theme Name'];
                $version    = $info['Version'];
                $maker      = $info['Maker'];
                $maker_uri  = $info['Maker URI'];

                // // 2) screenshot.* 찾기 (테마 폴더 내)
                $screenshot_url = '';
                $exts = array('png','jpg','jpeg','webp','gif');

                foreach ($exts as $ext) {
                    $p = G5_THEME_PATH . '/screenshot.' . $ext;
                    if (is_file($p)) {
                        $screenshot_url = G5_THEME_URL . '/screenshot.' . $ext;
                        break;
                    }
                }
                ?>
            <ul class="rb_config_sec">
                <div class="rb_theme_box">
                    <?php if ($screenshot_url !== '') { ?>
                    <div class="rb_theme_img">
                        <img src="<?php echo htmlspecialchars($screenshot_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Theme Screenshot">
                    </div>
                    <?php } ?>

                    <div class="rb_theme_meta">
                        <?php if ($theme_name !== '') { ?>
                        <div class="rb_theme_title font-B mt-3"><?php echo htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php } ?>

                        <?php $theme_path = parse_url(G5_THEME_URL, PHP_URL_PATH); ?>
                        <div class="rb_theme_row color-999">경로 : <?php echo htmlspecialchars($theme_path, ENT_QUOTES, 'UTF-8'); ?>/</div>

                        <?php if ($version !== '') { ?>
                        <div class="rb_theme_row color-999">버전 : <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php } ?>

                        <?php if ($maker !== '') { ?>
                        <div class="rb_theme_row color-999">
                            제작자 :
                            <?php if ($maker_uri !== '' && preg_match('/^https?:\/\//i', $maker_uri)) { ?>
                            <a href="<?php echo htmlspecialchars($maker_uri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($maker, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <?php } else { ?>
                            <?php echo htmlspecialchars($maker, ENT_QUOTES, 'UTF-8'); ?>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </ul>

            <?php
                $use1_yn = (int)$rb_theme_row['use1_yn'];
                $use2_yn = (int)$rb_theme_row['use2_yn'];
                $use3_yn = (int)$rb_theme_row['use3_yn'];
                $use4_yn = (int)$rb_theme_row['use4_yn'];
                $use5_yn = (int)$rb_theme_row['use5_yn'];

                // 변경
                if (defined('_SHOP_')) {
                    $carousel_use   = (int)$rb_theme_row['carousel_use_shop'];
                    $carousel_type  = $rb_theme_row['carousel_type_shop'];
                    $carousel_time  = (int)$rb_theme_row['carousel_time_shop'];
                    $carousel_speed = (int)$rb_theme_row['carousel_speed_shop'];
                } else {
                    $carousel_use   = (int)$rb_theme_row['carousel_use'];
                    $carousel_type  = $rb_theme_row['carousel_type'];
                    $carousel_time  = (int)$rb_theme_row['carousel_time'];
                    $carousel_speed = (int)$rb_theme_row['carousel_speed'];
                }
            ?>
            <?php if (defined('G5_USE_SHOP') && G5_USE_SHOP) { ?>
            <ul class="rb_config_sec">
                <h6 class="font-B">구분사용 설정</h6>
                <h6 class="font-R rb_config_sub_txt">
                    해제 하시는 경우 마켓 레이아웃만 사용 합니다.<br>
                    해제 하더라도 커뮤니티의 기능은 모두 사용할 수 있습니다.<br>
                    모두 사용시 경우 커뮤니티 및 마켓의 레이아웃을 각각 사용 합니다.
                </h6>

                <div class="config_wrap">
                    <ul class="config_flex_wrap w100">
                        <label class="label_l">커뮤니티/마켓 모두사용</label>

                        <label class="rb_tg label_r" data-name="use1_yn">
                            <input type="checkbox" <?php echo ($use1_yn === 1) ? ' checked' : ''; ?>>
                            <span class="rb_tg_ui"></span>
                        </label>
                    </ul>
                </div>
            </ul>
            <?php } else { ?>
            <input type="hidden" id="use1_yn_default" value="1">
            <?php } ?>


            <ul class="rb_config_sec po_rel rb_theme_config_si">
                <h6 class="font-B"><?php echo (defined('_SHOP_')) ? '마켓' : ''; ?> 캐러셀 설정</h6>
                <h6 class="font-R rb_config_sub_txt">
                    메인 상단의 슬라이더를 설정할 수 있습니다.
                </h6>


                <div class="config_wrap no_flex">

                    <ul class="rows_inp_lr ">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">사용여부</span><br>
                            carousel
                        </li>
                        <li class="rows_inp_r mt-5">
                            <input type="radio" name="rb_carousel_use" id="rb_carousel_use_0" value="0" <?php echo ($carousel_use === 0) ? ' checked' : ''; ?>><label for="rb_carousel_use_0">사용안함</label>
                            <input type="radio" name="rb_carousel_use" id="rb_carousel_use_1" value="1" <?php echo ($carousel_use === 1) ? ' checked' : ''; ?>><label for="rb_carousel_use_1">사용함</label>
                        </li>

                        <div class="cb"></div>
                    </ul>

                    <ul class="rows_inp_lr mt-5">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">모션타입</span><br>
                            carousel-type
                        </li>
                        <li class="rows_inp_r mt-5">
                            <select id="rb_carousel_type" name="rb_carousel_type" class="select select_tiny w50">
                                <option value="fade" <?php echo ($carousel_type === 'fade') ? ' selected' : ''; ?>>Fade</option>
                                <option value="slide" <?php echo ($carousel_type === 'slide') ? ' selected' : ''; ?>>Slide</option>
                            </select>
                        </li>

                        <div class="cb"></div>
                    </ul>

                    <ul class="rows_inp_lr mt-10">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">자동롤링 시간</span><br>
                            carousel-time
                        </li>
                        <li class="rows_inp_r mt-15">
                            <div id="rb_carousel_time_range" class="rb_range_item"></div>
                            <input type="hidden" id="rb_carousel_time" class="co_range_send" name="rb_carousel_time" value="<?php echo $carousel_time; ?>">
                        </li>


                        <script type="text/javascript">
                            $("#rb_carousel_time_range").slider({
                                range: "min",
                                min: 0,
                                max: 9000,
                                value: <?php echo $carousel_time; ?>,
                                step: 1000,
                                slide: function(e, ui) {
                                    $("#rb_carousel_time_range .ui-slider-handle").html(ui.value);
                                    $("#rb_carousel_time").val(ui.value); // hidden input에 값 업데이트
                                }
                            });

                            $("#rb_carousel_time_range .ui-slider-handle").html("<?php echo $carousel_time; ?>");
                            $("#rb_carousel_time").val("<?php echo $carousel_time; ?>"); // 초기값 설정
                        </script>
                        <div class="cb"></div>
                    </ul>

                    <ul class="rows_inp_lr mt-10">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">모션속도</span><br>
                            carousel-speed
                        </li>
                        <li class="rows_inp_r mt-15">
                            <div id="rb_carousel_speed_range" class="rb_range_item"></div>
                            <input type="hidden" id="rb_carousel_speed" class="co_range_send" name="rb_carousel_speed" value="<?php echo $carousel_speed; ?>">
                        </li>

                        <script type="text/javascript">
                            $("#rb_carousel_speed_range").slider({
                                range: "min",
                                min: 0,
                                max: 1000,
                                value: <?php echo $carousel_speed; ?>,
                                step: 100,
                                slide: function(e, ui) {
                                    $("#rb_carousel_speed_range .ui-slider-handle").html(ui.value);
                                    $("#rb_carousel_speed").val(ui.value); // hidden input에 값 업데이트
                                }
                            });

                            $("#rb_carousel_speed_range .ui-slider-handle").html("<?php echo $carousel_speed; ?>");
                            $("#rb_carousel_speed").val("<?php echo $carousel_speed; ?>"); // 초기값 설정
                        </script>
                        <div class="cb"></div>
                    </ul>


                </div>

            </ul>



            <?php
            // 캐러셀 목록 출력
            $cf_theme = $config['cf_theme'];
            $current_mode = (defined('_SHOP_')) ? 'shop' : 'community';
            $carousel_list = sql_query("SELECT * FROM rb_theme_carousel WHERE cf_theme = '$cf_theme' AND carousel_type_mode = '$current_mode' ORDER BY sort_order ASC, id ASC");
            $carousel_count = sql_num_rows($carousel_list);

            ?>

            <ul class="rb_config_sec po_rel rb_theme_config_si">
                <h6 class="font-B">
                    <span><?php echo (defined('_SHOP_')) ? '마켓' : ''; ?> 캐러셀 관리</span>
                    <span class="ca_cnt"><?php echo $carousel_count; ?></span>
                </h6>
                <h6 class="font-R rb_config_sub_txt">
                    캐러셀 추가하기 버튼으로 추가할 수 있으며, 생성된 캐러셀을<br>
                    클릭하는 경우 수정/삭제 가 가능합니다.
                </h6>

                <?php if ($carousel_count == 0) { ?>
                <div class="no_ca_data">
                    설정된 캐러셀이 없습니다.
                </div>
                <?php } else { ?>
                <div class="config_wrap no_flex" id="carousel_list">
                    <div class="rb_swiper" id="rb_carousel_1" data-pc-w="3" data-pc-h="1" data-mo-w="3" data-mo-h="1" data-pc-gap="10" data-mo-gap="10" data-autoplay="0" data-autoplay-time="0" data-pc-swap="1" data-mo-swap="1" data-pc-speed="400">

                        <div class="rb_swiper_inner">
                            <div class="rb-swiper-wrapper swiper-wrapper">

                                <?php while ($row = sql_fetch_array($carousel_list)) {
                                    // 이미지 경로
                                    $img_src = '';
                                    if ($row['image_path']) {
                                        $img_src = G5_DATA_URL . '/'.$cf_theme.'/carousel_img/' . $row['image_path'];
                                    }

                                    // 텍스트 처리 (HTML 태그는 허용하되, 출력시에는 제거)
                                    $main_text = strip_tags($row['main_text']);
                                    $sub_text = strip_tags($row['sub_text']);
                                    $btn_text = strip_tags($row['btn_text']);

                                    // 정렬 값
                                    $main_align = isset($row['main_align']) ? $row['main_align'] : 'left';
                                    $sub_align = isset($row['sub_align']) ? $row['sub_align'] : 'left';
                                    $btn_align = isset($row['btn_align']) ? $row['btn_align'] : 'left';

                                    // 굵기 값
                                    $main_weight = isset($row['main_weight']) ? $row['main_weight'] : 'font-R';
                                    $sub_weight = isset($row['sub_weight']) ? $row['sub_weight'] : 'font-R';
                                ?>


                                <div class="rb_swiper_list" data-carousel-id="<?php echo $row['id']; ?>" style="cursor: pointer;">
                                    <?php if ($img_src) { ?>
                                    <img src="<?php echo $img_src; ?>" alt="" class="rb_th_sw_card_img">
                                    <?php } ?>
                                    <div class="rb_th_sw_card">
                                        <ul class="cut font-R font-14"><?php echo htmlspecialchars($main_text, ENT_QUOTES, 'UTF-8'); ?></ul>
                                        <?php if ($sub_text) { ?>
                                        <ul class="cut font-R font-12 color-999"><?php echo htmlspecialchars($sub_text, ENT_QUOTES, 'UTF-8'); ?></ul>
                                        <?php } ?>
                                    </div>
                                </div>

                                <?php } ?>

                            </div>
                        </div>

                        <div class="rb_swiper_paging_btn">
                            <button type="button" class="swiper-button-prev rb-swiper-prev">
                                <img src="<?php echo G5_THEME_URL ?>/rb.img/icon/arr_prev.svg">
                            </button>
                            <button type="button" class="swiper-button-next rb-swiper-next">
                                <img src="<?php echo G5_THEME_URL ?>/rb.img/icon/arr_next.svg">
                            </button>
                        </div>

                    </div>
                </div>
                <?php } ?>

                <div class="rb_config_sec no_border">
                    <button type="button" class="main_rb_bg" id="theme_ca_add_btn">캐러셀 추가하기</button>
                </div>
            </ul>

            <?php
            // 데이터 내보내기/불러오기 테이블 목록
            $io_tables = array(
                array('table' => 'rb_module',       'col' => 'md_theme'),
                array('table' => 'rb_module_shop',  'col' => 'md_theme'),
                array('table' => 'rb_section',      'col' => 'sec_theme'),
                array('table' => 'rb_section_shop', 'col' => 'sec_theme'),
                array('table' => 'rb_theme',        'col' => 'theme_key'),
                array('table' => 'rb_theme_carousel','col' => 'cf_theme'),
                array('table' => 'rb_config',        'col' => 'co_theme'),
            );
            $io_tables_json = json_encode($io_tables);
            ?>
            <ul class="rb_config_sec">
                <h6 class="font-B">데이터 내보내기 / 불러오기</h6>
                <h6 class="font-R rb_config_sub_txt">
                    현재 테마의 환경설정, 모듈, 섹션, 캐러셀,<br>
                    테마설정 데이터를 JSON 파일로 저장하거나, JSON 파일을<br>
                    업로드하여 데이터를 복원할 수 있습니다.<br><br>
                    캐러셀에 등록한 이미지가 있는 경우 ZIP 파일로<br>
                    캐러셀 이미지 파일을 포함 합니다.<br><br>
                    <strong>불러오기 시 동일테마의 기존 데이터가 제거 됩니다.<br>내보내기를 통해 백업을 권장합니다.</strong>
                </h6>

                <div class="config_wrap no_flex">

                    <ul class="rows_inp_lr mt-5">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">내보내기</span><br>
                            export
                        </li>
                        <li class="rows_inp_r mt-5">
                            <button type="button" id="rb_data_export_btn">JSON 내보내기</button>
                        </li>
                        <div class="cb"></div>
                    </ul>

                    <ul class="rows_inp_lr mt-10">
                        <li class="rows_inp_l rows_inp_l_span">
                            <span class="font-B">불러오기</span><br>
                            import
                        </li>
                        <li class="rows_inp_r mt-5">
                            <input type="file" id="rb_data_import_file" accept=".json" style="display:none;">
                            <button type="button" id="rb_data_import_select_btn">파일 선택</button>
                        </li>
                        <div class="cb"></div>
                    </ul>

                    <ul>
                        <span id="rb_data_import_filename">파일을 등록해주세요.</span>
                        <button type="button" class="font-B" id="rb_data_import_btn" style="display:none;">불러오기</button>
                    </ul>

                </div>
            </ul>


            <ul class="rb_config_sec">
                <button type="button" class="rb_config_reload mt-5 font-B" id="rb_theme_save">적용하기</button>
                <button type="button" class="rb_config_close mt-5 font-B" onclick="rb_theme_set_close()">취소</button>
                <div class="cb"></div>
            </ul>


        </div>



        <div class="sh-side-demos-container-close" onclick="rb_theme_set_close();">
            <img src="<?php echo G5_THEME_URL ?>/rb.theme/img/icon_close.svg">
        </div>
    </div>
</div>

<?php include_once(G5_THEME_PATH.'/rb.theme/rb.carousel_modal.php'); ?>


<script>
    $('#rb_theme_save').on('click', function() {

        let data = {
            mode: 'theme_config'
        };

        // 토글
        $('.rb_tg').each(function() {
            let name = $(this).data('name');
            if (!name) return;

            let val = $(this).find('input').is(':checked') ? 1 : 0;
            data[name] = val;
        });

        // 쇼핑몰 미사용시 기본값
        if (!(<?php echo (defined('G5_USE_SHOP') && G5_USE_SHOP) ? 'true' : 'false'; ?>)) {
            data.use1_yn = 1;
        }

        // 캐러셀
        var cu = $('input[name="rb_carousel_use"]:checked').val();
        data.is_shop = <?php echo (defined('_SHOP_')) ? 1 : 0; ?>;
        data.carousel_use = (cu !== undefined) ? parseInt(cu, 10) : 0;
        data.carousel_type = $('#rb_carousel_type').val() || 'fade';
        data.carousel_time = parseInt($('#rb_carousel_time').val(), 10) || 4000;
        data.carousel_speed = parseInt($('#rb_carousel_speed').val(), 10) || 600;

        $.ajax({
            url: '<?php echo G5_THEME_URL ?>/rb.theme/rb.config_update.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(res) {
                if (res.status === 'success') {
                    alert('설정이 저장되었습니다.');
                } else {
                    alert(res.msg || '저장 실패');
                }
            },
            error: function() {
                alert('서버 오류');
            }
        });
    });
</script>


<script>
    (function() {
        function injectBtn() {
            var container = document.querySelector('.sh-side-options-container');
            if (!container) return false;

            var existing = container.querySelector('a.setting_set_btn2');
            if (existing) {
                container.appendChild(existing); // 이미 있으면 마지막으로 이동
                return true;
            }

            var a = document.createElement('a');
            a.className = 'sh-side-options-item sh-accent-color setting_set_btn2';
            a.setAttribute('data-tooltip', '테마설정');
            a.setAttribute('data-tooltip-pos', 'top');
            a.href = 'javascript:void(0)';

            a.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof window.toggleSideOptions_open_set === 'function') {
                    window.rb_theme_set_toggle();
                }
            });

            var inner = document.createElement('div');
            inner.className = 'sh-side-options-item-container';

            var img = document.createElement('img');
            img.src = '<?php echo G5_THEME_URL ?>/rb.theme/img/icon_theme.svg';
            img.alt = '환경설정';

            inner.appendChild(img);
            a.appendChild(inner);

            container.appendChild(a);
            return true;
        }

        // // 즉시 1회 시도
        if (injectBtn()) return;

        // // AJAX/동적 로드 감지
        var obs = new MutationObserver(function() {
            if (injectBtn()) obs.disconnect();
        });
        obs.observe(document.documentElement, {
            childList: true,
            subtree: true
        });

        // // jQuery AJAX를 쓰는 경우(있으면) 완료 시에도 시도
        if (window.jQuery) {
            jQuery(document).ajaxComplete(function() {
                injectBtn();
            });
        }
    })();
</script>

<script>
    function rb_theme_set_toggle() {
        // // 테마 설정 패널
        var themePanel = document.querySelector('.sh-side-options2');
        themePanel.classList.toggle('open');

        if (!themePanel) return;

        // // 기존 사이드 옵션 닫기
        var sideOptions = document.querySelector('.sh-side-options');
        if (sideOptions && sideOptions.classList.contains('open')) {
            sideOptions.classList.remove('open');
        }

        // // 설정 버튼 상태 초기화
        var settingBtn = document.querySelector('.setting_set_btn');
        if (settingBtn && settingBtn.classList.contains('open')) {
            settingBtn.classList.remove('open');
        }

        var settingBtn2 = document.querySelector('.setting_set_btn2');
        if (settingBtn2 && settingBtn2.classList.contains('open')) {
            settingBtn2.classList.remove('open');
        }
    }
</script>


<script>
    function rb_theme_set_close() {
        var el = document.querySelector('.sh-side-options2');
        if (!el) return;
        el.classList.remove('open');
    }
</script>

<script>
    (function() {
        function enforceRule() {
            var side = document.querySelector('.sh-side-options');
            var theme = document.querySelector('.sh-side-options2');
            if (!side || !theme) return;

            // // side가 open이면 theme의 open 제거
            if (side.classList.contains('open') && theme.classList.contains('open')) {
                theme.classList.remove('open');
            }
        }

        // // 최초 1회
        enforceRule();

        // // class 변화/DOM 교체까지 전부 감지
        var obs = new MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];

                // // 1) class 속성 변경 감지
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    var el = m.target;
                    // // side 자신이거나, side 내부에서 class 토글되는 경우도 고려
                    if (el && el.classList && (el.classList.contains('sh-side-options') || el.closest('.sh-side-options'))) {
                        enforceRule();
                        return;
                    }
                }

                // // 2) side/theme DOM이 새로 추가/교체되는 경우 감지
                if (m.type === 'childList' && (m.addedNodes && m.addedNodes.length)) {
                    enforceRule();
                    return;
                }
            }
        });

        obs.observe(document.documentElement, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['class']
        });

        // // 혹시 외부 스크립트가 매우 빠르게 토글하는 경우 대비(짧은 폴백)
        setInterval(enforceRule, 300);
    })();
</script>

<script>
    (function() {
        function ensureHidden(tg) {
            var name = (tg.getAttribute('data-name') || '').trim();
            if (!name) return null;

            var hidden = tg.querySelector('input[type="hidden"][data-rb-tg="1"]');
            if (hidden) return hidden;

            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.setAttribute('data-rb-tg', '1');
            tg.appendChild(hidden);
            return hidden;
        }

        function sync(tg) {
            var cb = tg.querySelector('input[type="checkbox"]');
            if (!cb) return;

            // // 초기 강제값 옵션(data-checked="1|0")
            var init = tg.getAttribute('data-checked');
            if (init === '1') cb.checked = true;
            if (init === '0') cb.checked = false;

            var hidden = ensureHidden(tg);
            if (!hidden) return;

            hidden.value = cb.checked ? '1' : '0'; // // 1=on, 0=off
        }

        // // 페이지 내 토글 초기 동기화
        function initAll() {
            var list = document.querySelectorAll('.rb_tg');
            for (var i = 0; i < list.length; i++) {
                sync(list[i]);
            }
        }

        // // change 이벤트 위임(토글 여러개 대응)
        document.addEventListener('change', function(e) {
            var cb = e.target;
            if (!cb || cb.type !== 'checkbox') return;

            var tg = cb.closest('.rb_tg');
            if (!tg) return;

            // // data-checked가 있으면 초기 강제는 1회용으로 쓰는 게 자연스러움
            if (tg.hasAttribute('data-checked')) tg.removeAttribute('data-checked');

            var hidden = ensureHidden(tg);
            if (!hidden) return;

            hidden.value = cb.checked ? '1' : '0';
        });

        document.addEventListener('DOMContentLoaded', initAll);
    })();
</script>

<script>
(function() {
    var IO_THEME_KEY = '<?php echo addslashes($config['cf_theme']); ?>';
    var IO_URL_BASE  = '<?php echo G5_THEME_URL; ?>/rb.theme/rb.data_io.php';

    function rb_confirm_wrap(msg) {
        if (typeof rb_confirm === 'function') {
            return rb_confirm(msg);
        }
        return Promise.resolve(confirm(msg));
    }

    // 내보내기
    document.getElementById('rb_data_export_btn').addEventListener('click', function() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = IO_URL_BASE;
        form.style.display = 'none';

        var f1 = document.createElement('input');
        f1.name = 'mode'; f1.value = 'export';
        form.appendChild(f1);

        var f2 = document.createElement('input');
        f2.name = 'theme_key'; f2.value = IO_THEME_KEY;
        form.appendChild(f2);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

    // 파일 선택
    document.getElementById('rb_data_import_select_btn').addEventListener('click', function() {
        document.getElementById('rb_data_import_file').click();
    });

    document.getElementById('rb_data_import_file').addEventListener('change', function() {
        var file = this.files[0];
        if (!file) {
            document.getElementById('rb_data_import_filename').textContent = '파일을 등록해주세요.';
            document.getElementById('rb_data_import_btn').style.display = 'none';
            return;
        }
        document.getElementById('rb_data_import_filename').textContent = file.name;
        document.getElementById('rb_data_import_btn').style.display = '';
    });

    // 불러오기
    document.getElementById('rb_data_import_btn').addEventListener('click', function() {
        var file = document.getElementById('rb_data_import_file').files[0];
        if (!file) { alert('파일을 선택해주세요.'); return; }

        var reader = new FileReader();
        reader.onload = function(e) {
            var jsonData;
            try {
                jsonData = JSON.parse(e.target.result);
            } catch(err) {
                alert('유효하지 않은 JSON 파일입니다.');
                return;
            }

            // 구조 검증 - io_tables 키 중 하나 이상 존재해야 함
            var validKeys = ['rb_module','rb_module_shop','rb_section','rb_section_shop','rb_theme','rb_theme_carousel','rb_config'];
            var isValid = false;
            for (var i = 0; i < validKeys.length; i++) {
                if (jsonData.hasOwnProperty(validKeys[i]) && Array.isArray(jsonData[validKeys[i]])) {
                    isValid = true;
                    break;
                }
            }
            if (!isValid) {
                alert('올바르지 않은 파일입니다.\n테마 데이터 JSON 파일만 업로드 가능합니다.');
                return;
            }

            // 충돌 여부 확인 요청
            $.ajax({
                url: IO_URL_BASE,
                type: 'POST',
                dataType: 'json',
                data: {
                    mode: 'check',
                    theme_key: IO_THEME_KEY
                },
                success: function(res) {
                    if (res.has_data) {
                        rb_confirm_wrap('이미 설정이 있습니다.\n파일이 업로드 되는 경우 동일테마의 기존 데이터는 제거 됩니다.\n기존 데이터의 백업이 권장됩니다.\n계속 하시겠습니까?')
                        .then(function(ok1) {
                            if (!ok1) return;
                            rb_confirm_wrap('기존 데이터가 유실 되며 되돌릴 수 없습니다.\n정말 데이터를 업로드 하시겠습니까?')
                            .then(function(ok2) {
                                if (!ok2) return;
                                doImport(jsonData);
                            });
                        });
                    } else {
                        doImport(jsonData);
                    }
                },
                error: function() { alert('서버 오류'); }
            });
        };
        reader.readAsText(file);
    });

    function doImport(jsonData) {
        $.ajax({
            url: IO_URL_BASE,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                mode: 'import',
                theme_key: IO_THEME_KEY,
                data: jsonData
            }),
            success: function(res) {
                if (res.status === 'success') {
                    alert('데이터 적용이 완료 되었습니다.\n새로고침 후 확인해주세요.');
                    //location.reload();
                } else {
                    alert(res.msg || '불러오기 실패');
                }
            },
            error: function() { alert('서버 오류'); }
        });
    }
})();
</script>
<?php } ?>
