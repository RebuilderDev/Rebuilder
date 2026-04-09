
<!-- 캐러셀 추가/수정 모달 -->
<div id="carousel_modal" class="rb_modal" style="display:none;">
    <div class="rb_modal_overlay"></div>
    <div class="rb_modal_content">
        <div class="rb_modal_header">
            <h3 id="carousel_modal_title">캐러셀 추가</h3>
            <button type="button" class="rb_modal_close"><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                    <g id="close_line" fill='none' fill-rule='evenodd'>
                        <path d='M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z' />
                        <path fill='#09244BFF' d='m12 13.414 5.657 5.657a1 1 0 0 0 1.414-1.414L13.414 12l5.657-5.657a1 1 0 0 0-1.414-1.414L12 10.586 6.343 4.929A1 1 0 0 0 4.93 6.343L10.586 12l-5.657 5.657a1 1 0 1 0 1.414 1.414z' />
                    </g>
                </svg></button>
        </div>

        <div class="rb_modal_body">

            <form id="carousel_form" style="display:contents;">
                <input type="hidden" id="carousel_id" name="carousel_id" value="">
                <input type="hidden" id="carousel_mode" name="mode" value="insert">
                <input type="hidden" id="carousel_type_mode" name="carousel_type_mode" value="">

                <!-- 왼쪽 패널: 미리보기 + 탭 편집 -->
                <div class="cm_left">

                    <div class="cm_preview_box" id="carousel_preview">
                        <div class="preview_inner" id="preview_inner">
                            <div class="preview_text_main" id="preview_main">메인텍스트</div>
                            <div class="preview_text_sub" id="preview_sub">서브텍스트</div>
                            <div class="preview_btn_wrap" id="preview_btn_wrap">
                                <button class="preview_btn" id="preview_btn" type="button">
                                    <span id="preview_btn_text">버튼텍스트</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="cm_preview_actions">
                        <span class="cm_preview_hint">실제 크기보다 2배 작게 표시됩니다</span>
                        <button type="button" class="cm_fullpreview_btn" id="cm_fullpreview_btn">
                            <svg width="11" height="11" viewBox="0 0 11 11" fill="none"><path d="M1 4V1h3M7 1h3v3M10 7v3H7M4 10H1V7" stroke="#aa20ff" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            실제 크기 미리보기
                        </button>
                    </div>

                    <div class="cm_tabs">
                        <button type="button" class="cm_tab on" data-tab="main">메인 텍스트</button>
                        <button type="button" class="cm_tab" data-tab="sub">서브 텍스트</button>
                        <button type="button" class="cm_tab" data-tab="btn">버튼</button>
                    </div>

                    <!-- 메인 텍스트 탭 -->
                    <div class="cm_tab_panel on" id="cm_panel_main">
                        <div>
                            <textarea id="main_text" name="main_text" rows="3" placeholder="메인텍스트를 입력하세요"></textarea>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">정렬</span>
                                <select id="main_align" name="main_align" class="select">
                                    <option value="left">좌측</option>
                                    <option value="center">중앙</option>
                                    <option value="right">우측</option>
                                </select>
                            </div>
                            <div>
                                <span class="cm_field_label">굵기</span>
                                <select id="main_weight" name="main_weight" class="select">
                                    <option value="font-R">Regular</option>
                                    <option value="font-B">Bold</option>
                                    <option value="font-H">Heavy</option>
                                </select>
                            </div>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">컬러</span>
                                <div class="cm_color_row">
                                    <span class="cm_color_swatch" id="main_color_swatch" style="background:#ffffff;"></span>
                                    <input type="text" class="coloris-modal" id="main_color" name="main_color" value="#ffffff">
                                </div>
                            </div>
                            <div>
                                <span class="cm_field_label">크기 <span id="main_size_val" class="font-B">20</span>px</span>
                                <div id="main_size_range" class="rb_range_item"></div>
                                <input type="hidden" id="main_size" name="main_size" value="20">
                            </div>
                        </div>
                    </div>

                    <!-- 서브 텍스트 탭 -->
                    <div class="cm_tab_panel" id="cm_panel_sub">
                        <div>
                            <textarea id="sub_text" name="sub_text" rows="3" placeholder="서브텍스트를 입력하세요"></textarea>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">정렬</span>
                                <select id="sub_align" name="sub_align" class="select">
                                    <option value="left">좌측</option>
                                    <option value="center">중앙</option>
                                    <option value="right">우측</option>
                                </select>
                            </div>
                            <div>
                                <span class="cm_field_label">굵기</span>
                                <select id="sub_weight" name="sub_weight" class="select">
                                    <option value="font-R">Regular</option>
                                    <option value="font-B">Bold</option>
                                    <option value="font-H">Heavy</option>
                                </select>
                            </div>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">컬러</span>
                                <div class="cm_color_row">
                                    <span class="cm_color_swatch" id="sub_color_swatch" style="background:#999999;"></span>
                                    <input type="text" class="coloris-modal" id="sub_color" name="sub_color" value="#999999">
                                </div>
                            </div>
                            <div>
                                <span class="cm_field_label">크기 <span id="sub_size_val" class="font-B">14</span>px</span>
                                <div id="sub_size_range" class="rb_range_item"></div>
                                <input type="hidden" id="sub_size" name="sub_size" value="14">
                            </div>
                        </div>
                        <div>
                            <span class="cm_field_label">상단간격 <span id="sub_margin_val" class="font-B">10</span>px</span>
                            <div id="sub_margin_range" class="rb_range_item"></div>
                            <input type="hidden" id="sub_margin" name="sub_margin" value="10">
                        </div>
                    </div>

                    <!-- 버튼 탭 -->
                    <div class="cm_tab_panel" id="cm_panel_btn">
                        <div>
                            <span class="cm_field_label">텍스트</span>
                            <input type="text" id="btn_text" name="btn_text" maxlength="100" placeholder="버튼 텍스트를 입력하세요">
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">배경 컬러</span>
                                <div class="cm_color_row">
                                    <span class="cm_color_swatch" id="btn_bg_color_swatch" style="background:#ffffff;"></span>
                                    <input type="text" class="coloris-modal" id="btn_bg_color" name="btn_bg_color" value="#ffffff">
                                </div>
                            </div>
                            <div>
                                <span class="cm_field_label">텍스트 컬러</span>
                                <div class="cm_color_row">
                                    <span class="cm_color_swatch" id="btn_text_color_swatch" style="background:#000000;"></span>
                                    <input type="text" class="coloris-modal" id="btn_text_color" name="btn_text_color" value="#000000">
                                </div>
                            </div>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">테두리 컬러</span>
                                <div class="cm_color_row">
                                    <span class="cm_color_swatch" id="btn_border_color_swatch" style="background:#000000;"></span>
                                    <input type="text" class="coloris-modal" id="btn_border_color" name="btn_border_color" value="#000000">
                                </div>
                            </div>
                            <div>
                                <span class="cm_field_label">테두리 두께 <span id="btn_border_val" class="font-B">1</span>px</span>
                                <div id="btn_border_range" class="rb_range_item"></div>
                                <input type="hidden" id="btn_border" name="btn_border" value="1">
                            </div>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">상하여백 <span id="btn_padding_val" class="font-B">10</span>px</span>
                                <div id="btn_padding_range" class="rb_range_item"></div>
                                <input type="hidden" id="btn_padding" name="btn_padding" value="10">
                            </div>
                            <div>
                                <span class="cm_field_label">좌우여백 <span id="btn_padding_lr_val" class="font-B">20</span>px</span>
                                <div id="btn_padding_lr_range" class="rb_range_item"></div>
                                <input type="hidden" id="btn_padding_lr" name="btn_padding_lr" value="20">
                            </div>
                        </div>
                        <div>
                            <span class="cm_field_label">상단간격 <span id="btn_margin_val" class="font-B">0</span>px</span>
                            <div id="btn_margin_range" class="rb_range_item"></div>
                            <input type="hidden" id="btn_margin" name="btn_margin" value="0">
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">크기 <span id="btn_size_val" class="font-B">14</span>px</span>
                                <div id="btn_size_range" class="rb_range_item"></div>
                                <input type="hidden" id="btn_size" name="btn_size" value="14">
                            </div>
                            <div>
                                <span class="cm_field_label">모서리 라운드 <span id="btn_radius_val" class="font-B">4</span>px</span>
                                <div id="btn_radius_range" class="rb_range_item"></div>
                                <input type="hidden" id="btn_radius" name="btn_radius" value="4">
                            </div>
                        </div>
                        <div class="cm_field_row">
                            <div>
                                <span class="cm_field_label">정렬</span>
                                <select id="btn_align" name="btn_align" class="select">
                                    <option value="left">좌측</option>
                                    <option value="center">중앙</option>
                                    <option value="right">우측</option>
                                </select>
                            </div>
                            <div>
                                <span class="cm_field_label">텍스트 굵기</span>
                                <select id="btn_weight" name="btn_weight" class="select">
                                    <option value="font-R">Regular</option>
                                    <option value="font-B">Bold</option>
                                    <option value="font-H">Heavy</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <span class="cm_field_label">링크 URL</span>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="btn_link" name="btn_link" placeholder="https://" style="flex:1;">
                                <div class="cm_new_tab_row">
                                    <input type="checkbox" id="btn_link_blank" name="btn_link_blank" value="1">
                                    <label for="btn_link_blank">새창</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- form_buttons는 form 안에 있어야 submit 작동 -->
                    <div class="form_buttons" style="display:none;">
                        <button type="button" id="carousel_delete_btn" class="btn_delete" style="display:none;">삭제</button>
                        <div class="cm_btn_group">
                            <button type="button" class="btn_cancel">취소</button>
                            <button type="submit" class="btn_submit font-B">저장</button>
                        </div>
                    </div>
                </div>

                <!-- 오른쪽 패널: 이미지 + 기타 설정 -->
                <div class="cm_right">

                    <div class="cm_r_card">
                        <div class="cm_r_card_title">이미지</div>
                        <div class="cm_file_btn">
                            <input type="file" id="carousel_image" name="carousel_image" accept="image/*">
                            <div class="cm_file_icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <rect x="1" y="3" width="14" height="10" rx="2" stroke="#aa20ff" stroke-width="1.3"/>
                                    <circle cx="5.5" cy="6.5" r="1.2" fill="#aa20ff"/>
                                    <path d="M1 11l4-3 3 2.5 2.5-2 4.5 3.5" stroke="#aa20ff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <div class="cm_file_text">이미지 선택</div>
                                <div class="cm_file_sub">JPG · PNG</div>
                            </div>
                        </div>
                        <div id="image_preview" class="image_preview" style="display:none;"></div>
                    </div>

                    <div class="cm_r_card">
                        <div class="cm_toggle_row">
                            <span class="cm_toggle_label">버튼 표시</span>
                            <button type="button" class="cm_toggle" id="btn_use_toggle"></button>
                            <input type="hidden" id="btn_use" name="btn_use" value="0">
                        </div>
                    </div>

                    <div class="cm_r_card">
                        <div class="cm_toggle_row">
                            <span class="cm_toggle_label">서브페이지 공통 배경</span>
                            <button type="button" class="cm_toggle" id="is_sub_toggle"></button>
                            <input type="hidden" id="is_sub" name="is_sub" value="0">
                        </div>
                        <div class="cm_toggle_sub">서브페이지 상단에 이미지만 공통 적용</div>

                    </div>
                    <button type="button" id="is_sub_load_btn" class="cm_load_btn">기존 설정 불러오기</button>

                </div>

            </form>
        </div>

        <!-- 하단 버튼 -->
        <div class="form_buttons" style="display:flex;">
            <button type="button" id="carousel_delete_btn_footer" class="btn_delete" style="display:none;">삭제</button>
            <div class="cm_btn_group" style="margin-left:auto;">
                <button type="button" class="btn_cancel">취소</button>
                <button type="button" class="btn_submit font-B" id="carousel_submit_btn">저장</button>
            </div>
        </div>

        <!-- 실제크기 미리보기 팝업 -->
        <div id="cm_fullpreview_overlay"></div>
        <div id="cm_fullpreview_popup">
            <div id="cm_fullpreview_bg" style="position:absolute;inset:0;background-size:cover;background-position:center;z-index:0;"></div>
            <button type="button" class="fp_close" id="cm_fullpreview_close">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/></svg>
            </button>
            <div class="fp_inner" id="fp_inner">
                <div id="fp_main"></div>
                <div id="fp_sub"></div>
                <div id="fp_btn_wrap"><button type="button" id="fp_btn"><span id="fp_btn_text"></span></button></div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
    Coloris({
        el: '.coloris-modal'
    });
    Coloris.setInstance('.coloris-modal', {
        parent: 'body',
        formatToggle: false,
        format: 'hex',
        margin: 0,
        swatchesOnly: false,
        alpha: true,
        themeMode: 'Light',
        focusInput: true,
        selectInput: true,
        autoClose: true,
        inline: false,
        defaultColor: '#ffffff',
        closeButton: true,
        closeLabel: '닫기',
        swatches: [
            '#AA20FF',
            '#FFC700',
            '#00A3FF',
            '#8ED100',
            '#FF5A5A',
            '#25282B',
            '<?php echo isset($rb_config['co_color']) ? $rb_config['co_color'] : '#25282B'; ?>',
            '#FFFFFF00',
            '#FFFFFF',
        ]
    });
</script>

<script>
    jQuery(document).ready(function($) {

        // 탭 전환
        $("#carousel_modal .cm_tab").on("click", function() {
            var tab = $(this).data("tab");
            $("#carousel_modal .cm_tab").removeClass("on");
            $(this).addClass("on");
            $("#carousel_modal .cm_tab_panel").removeClass("on");
            $("#cm_panel_" + tab).addClass("on");
        });

        // 버튼 표시 토글
        $("#btn_use_toggle").on("click", function() {
            $(this).toggleClass("on");
            var isOn = $(this).hasClass("on");
            $("#btn_use").val(isOn ? "1" : "0");
            updatePreview();
        });

        // 서브페이지 토글
        $("#is_sub_toggle").on("click", function() {
            $(this).toggleClass("on");
            $("#is_sub").val($(this).hasClass("on") ? "1" : "0");
        });

        // 컬러 스와치 동기화
        function syncSwatch(inputId, swatchId) {
            $("#" + inputId).on("change input", function() {
                $("#" + swatchId).css("background", $(this).val());
            });
        }
        syncSwatch("main_color", "main_color_swatch");
        syncSwatch("sub_color", "sub_color_swatch");
        syncSwatch("btn_bg_color", "btn_bg_color_swatch");
        syncSwatch("btn_text_color", "btn_text_color_swatch");
        syncSwatch("btn_border_color", "btn_border_color_swatch");

        // 기존 설정 불러오기
        $("#is_sub_load_btn").on("click", function() {
            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_get.php",
                type: "POST",
                data: {
                    get_first: 1,
                    cf_theme: "<?php echo $config['cf_theme']; ?>",
                    carousel_type_mode: $("#carousel_type_mode").val(),
                    exclude_id: $("#carousel_id").val() || 0
                },
                dataType: "json",
                success: function(res) {
                    if (!res.carousel) {
                        alert("불러올 캐러셀 설정이 없습니다.");
                        return;
                    }
                    var confirmFn = (typeof rb_confirm === "function") ? rb_confirm : function(m) {
                        return Promise.resolve(confirm(m));
                    };
                    confirmFn("기존 캐러셀 설정을 불러옵니다.\n현재 입력 내용이 덮어씌워집니다. 계속하시겠습니까?")
                        .then(function(ok) {
                            if (!ok) return;
                            var currentIdVal = $("#carousel_id").val();
                            var currentMode = $("#carousel_mode").val();
                            var currentTypeMode = $("#carousel_type_mode").val();
                            var currentIsSub = $("#is_sub").val();
                            var carouselData = $.extend({}, res.carousel);
                            carouselData.image_url = '';
                            fillFormWithData(carouselData);
                            $("#carousel_id").val(currentIdVal);
                            $("#carousel_mode").val(currentMode);
                            $("#carousel_type_mode").val(currentTypeMode);
                            $("#is_sub").val(currentIsSub);
                            if (currentIsSub == "1") {
                                $("#is_sub_toggle").addClass("on");
                            } else {
                                $("#is_sub_toggle").removeClass("on");
                            }
                            $("#carousel_image").val("");
                            $("#carousel_preview").css("background-image", "none");
                        });
                },
                error: function() { alert("서버 오류"); }
            });
        });

        // 슬라이더 초기화
        function initSlider(element, hiddenInput, valueDisplay, min, max, step, defaultVal) {
            $(element).slider({
                range: "min",
                min: min,
                max: max,
                value: defaultVal,
                step: step,
                slide: function(e, ui) {
                    $(element + " .ui-slider-handle").html(ui.value);
                    $(hiddenInput).val(ui.value);
                    $(valueDisplay).text(ui.value);
                    updatePreview();
                }
            });
            $(element + " .ui-slider-handle").html(defaultVal);
            $(hiddenInput).val(defaultVal);
            $(valueDisplay).text(defaultVal);
        }

        function setSlider(rangeId, hiddenId, displayId, min, max, step, val) {
            val = parseInt(val) || 0;
            $(rangeId).slider("destroy");
            $(rangeId).slider({
                range: "min",
                min: min,
                max: max,
                value: val,
                step: step,
                slide: function(e, ui) {
                    $(rangeId + " .ui-slider-handle").html(ui.value);
                    $(hiddenId).val(ui.value);
                    $(displayId).text(ui.value);
                    updatePreview();
                }
            });
            $(rangeId + " .ui-slider-handle").html(val);
            $(hiddenId).val(val);
            $(displayId).text(val);
        }

        initSlider("#main_size_range", "#main_size", "#main_size_val", 12, 80, 1, 20);
        initSlider("#sub_size_range", "#sub_size", "#sub_size_val", 12, 50, 1, 14);
        initSlider("#sub_margin_range", "#sub_margin", "#sub_margin_val", 0, 200, 5, 10);
        initSlider("#btn_size_range", "#btn_size", "#btn_size_val", 12, 50, 1, 14);
        initSlider("#btn_radius_range", "#btn_radius", "#btn_radius_val", 0, 100, 2, 4);
        initSlider("#btn_border_range", "#btn_border", "#btn_border_val", 0, 20, 1, 1);
        initSlider("#btn_padding_range", "#btn_padding", "#btn_padding_val", 0, 100, 1, 10);
        initSlider("#btn_padding_lr_range", "#btn_padding_lr", "#btn_padding_lr_val", 0, 100, 1, 20);
        initSlider("#btn_margin_range", "#btn_margin", "#btn_margin_val", 0, 200, 5, 0);

        function convertNewlineToBr(text) {
            return text.replace(/\n/g, '<br>');
        }

        function hexToRgba(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            if (hex.length === 6) hex += 'ff';
            if (hex.length !== 8) return hex;
            var r = parseInt(hex.slice(0,2),16);
            var g = parseInt(hex.slice(2,4),16);
            var b = parseInt(hex.slice(4,6),16);
            var a = (parseInt(hex.slice(6,8),16)/255).toFixed(3);
            return 'rgba('+r+','+g+','+b+','+a+')';
        }

        function updatePreview() {
            var mainText   = $("#main_text").val() || "메인텍스트";
            var mainSize   = $("#main_size").val();
            var mainColor  = hexToRgba($("#main_color").val());
            var mainAlign  = $("#main_align").val();
            var mainWeight = $("#main_weight").val();

            var subText   = $("#sub_text").val() || "서브텍스트";
            var subSize   = $("#sub_size").val();
            var subColor  = hexToRgba($("#sub_color").val());
            var subMargin = $("#sub_margin").val();
            var subAlign  = $("#sub_align").val();
            var subWeight = $("#sub_weight").val();

            var btnText       = $("#btn_text").val() || "버튼텍스트";
            var btnSize       = $("#btn_size").val();
            var btnRadius     = $("#btn_radius").val();
            var btnBorder     = $("#btn_border").val();
            var btnPadding    = $("#btn_padding").val();
            var btnPaddingLr  = $("#btn_padding_lr").val();
            var btnBgColor    = hexToRgba($("#btn_bg_color").val());
            var btnTextColor  = hexToRgba($("#btn_text_color").val());
            var btnBorderColor= hexToRgba($("#btn_border_color").val());
            var btnAlign      = $("#btn_align").val();
            var btnWeight     = $("#btn_weight").val();
            var btnMargin     = $("#btn_margin").val();

            $("#preview_main").html(convertNewlineToBr(mainText)).css({
                "font-size": (mainSize/2)+"px",
                "color": mainColor,
                "text-align": mainAlign
            }).removeClass('font-R font-B font-H').addClass(mainWeight);

            $("#preview_sub").html(convertNewlineToBr(subText)).css({
                "font-size": (subSize/2)+"px",
                "color": subColor,
                "margin-top": (subMargin/2)+"px",
                "text-align": subAlign
            }).removeClass('font-R font-B font-H').addClass(subWeight);

            $("#preview_btn_text").text(btnText);
            $("#preview_btn").css({
                "font-size": (btnSize/2)+"px",
                "border-radius": (btnRadius/2)+"px",
                "border-width": btnBorder+"px",
                "background-color": btnBgColor,
                "border-color": btnBorderColor,
                "color": btnTextColor,
                "padding": (btnPadding/2)+"px "+(btnPaddingLr/2)+"px"
            });
            $("#preview_btn_wrap").css({
                "text-align": btnAlign,
                "margin-top": (btnMargin/2)+"px"
            });
            $("#preview_btn").removeClass("font-R font-B font-H").addClass(btnWeight);

            if ($("#btn_use_toggle").hasClass("on")) {
                $("#preview_btn_wrap").show();
            } else {
                $("#preview_btn_wrap").hide();
            }
        }

        $("#main_text, #sub_text").on("keydown", function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                var ta = $(this);
                var pos = ta[0].selectionStart;
                ta.val(ta.val().substring(0,pos)+"\n"+ta.val().substring(pos));
                ta[0].selectionStart = ta[0].selectionEnd = pos+1;
                updatePreview();
            }
        });

        $("#main_text, #sub_text, #btn_text").on("input", updatePreview);
        $("#main_color, #sub_color, #btn_bg_color, #btn_text_color, #btn_border_color").on("change", updatePreview);
        $("#main_align, #sub_align, #btn_align, #main_weight, #sub_weight, #btn_weight").on("change", updatePreview);

        // 이미지 미리보기
        $("#carousel_image").on("change", function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $("#carousel_preview").css("background-image", "url("+e.target.result+")");
                };
                reader.readAsDataURL(file);
            }
        });

        // 캐러셀 추가 버튼
        $("#theme_ca_add_btn").on("click", function() {
            resetForm();
            $("#carousel_modal_title").text("캐러셀 추가");
            $("#carousel_mode").val("insert");
            $("#carousel_delete_btn_footer").hide();
            $("#carousel_modal").fadeIn(200);
        });

        // 모달 닫기
        $(".rb_modal_close, .btn_cancel").on("click", function() {
            $("#carousel_modal").fadeOut(200);
        });

        $(".rb_modal_overlay").on("click", function() {
            $("#carousel_modal").fadeOut(200);
        });

        // 실제크기 미리보기
        $("#cm_fullpreview_btn").on("click", function() {
            var bgImg = $("#carousel_preview").css("background-image");
            $("#cm_fullpreview_bg").css("background-image", bgImg);

            var mainText   = $("#main_text").val() || "메인텍스트";
            var mainSize   = parseInt($("#main_size").val());
            var mainColor  = hexToRgba($("#main_color").val());
            var mainAlign  = $("#main_align").val();
            var mainWeight = $("#main_weight").val();

            var subText   = $("#sub_text").val() || "서브텍스트";
            var subSize   = parseInt($("#sub_size").val());
            var subColor  = hexToRgba($("#sub_color").val());
            var subMargin = parseInt($("#sub_margin").val());
            var subAlign  = $("#sub_align").val();
            var subWeight = $("#sub_weight").val();

            $("#fp_main").html(convertNewlineToBr(mainText)).css({
                "font-size": mainSize + "px",
                "color": mainColor,
                "text-align": mainAlign,
                "margin": "0"
            }).removeClass("font-R font-B font-H").addClass(mainWeight);

            $("#fp_sub").html(convertNewlineToBr(subText)).css({
                "font-size": subSize + "px",
                "color": subColor,
                "margin-top": subMargin + "px",
                "text-align": subAlign
            }).removeClass("font-R font-B font-H").addClass(subWeight);

            if ($("#btn_use_toggle").hasClass("on")) {
                var btnText        = $("#btn_text").val() || "버튼텍스트";
                var btnSize        = parseInt($("#btn_size").val());
                var btnRadius      = parseInt($("#btn_radius").val());
                var btnBorder      = parseInt($("#btn_border").val());
                var btnPadding     = parseInt($("#btn_padding").val());
                var btnPaddingLr   = parseInt($("#btn_padding_lr").val());
                var btnBgColor     = hexToRgba($("#btn_bg_color").val());
                var btnTextColor   = hexToRgba($("#btn_text_color").val());
                var btnBorderColor = hexToRgba($("#btn_border_color").val());
                var btnAlign       = $("#btn_align").val();
                var btnWeight      = $("#btn_weight").val();
                var btnMargin      = parseInt($("#btn_margin").val());

                $("#fp_btn_text").text(btnText);
                $("#fp_btn").css({
                    "font-size": btnSize + "px",
                    "border-radius": btnRadius + "px",
                    "border-width": btnBorder + "px",
                    "border-style": "solid",
                    "background-color": btnBgColor,
                    "border-color": btnBorderColor,
                    "color": btnTextColor,
                    "padding": btnPadding + "px " + btnPaddingLr + "px",
                    "cursor": "default",
                    "display": "inline-block"
                }).removeClass("font-R font-B font-H").addClass(btnWeight);
                $("#fp_btn_wrap").css({
                    "text-align": btnAlign,
                    "margin-top": btnMargin + "px"
                }).show();
            } else {
                $("#fp_btn_wrap").hide();
            }

            $("#cm_fullpreview_overlay").fadeIn(150);
            $("#cm_fullpreview_popup").fadeIn(150);
        });

        $("#cm_fullpreview_close, #cm_fullpreview_overlay").on("click", function() {
            $("#cm_fullpreview_popup").fadeOut(150);
            $("#cm_fullpreview_overlay").fadeOut(150);
        });

        // 저장 버튼
        $("#carousel_submit_btn").on("click", function() {
            doSubmit();
        });

        // 삭제 버튼 (footer)
        $("#carousel_delete_btn_footer").on("click", function() {
            if (confirm("정말 삭제하시겠습니까?")) {
                $.ajax({
                    url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_update.php",
                    type: "POST",
                    data: {
                        mode: "delete",
                        id: $("#carousel_id").val(),
                        cf_theme: "<?php echo $config['cf_theme']; ?>"
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            alert(response.message, function() {
                                location.reload();
                            });
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        });

        function resetForm() {
            $("#carousel_form")[0].reset();
            $("#carousel_id").val("");
            $("#carousel_type_mode").val("<?php echo (defined('_SHOP_')) ? 'shop' : 'community'; ?>");
            $("#image_preview").hide().html("");
            $("#carousel_preview").css("background-image", "none");

            $("#main_align").val("left");
            $("#sub_align").val("left");
            $("#btn_align").val("left");
            $("#main_weight").val("font-R");
            $("#sub_weight").val("font-R");
            $("#btn_weight").val("font-R");

            $("#main_color").val("#ffffff");
            $("#main_color_swatch").css("background", "#ffffff");
            $("#sub_color").val("#999999");
            $("#sub_color_swatch").css("background", "#999999");
            $("#btn_bg_color").val("#ffffff");
            $("#btn_bg_color_swatch").css("background", "#ffffff");
            $("#btn_text_color").val("#000000");
            $("#btn_text_color_swatch").css("background", "#000000");
            $("#btn_border_color").val("#000000");
            $("#btn_border_color_swatch").css("background", "#000000");

            setSlider("#main_size_range", "#main_size", "#main_size_val", 12, 80, 1, 40);
            setSlider("#sub_size_range", "#sub_size", "#sub_size_val", 12, 50, 1, 26);
            setSlider("#sub_margin_range", "#sub_margin", "#sub_margin_val", 0, 200, 5, 10);
            setSlider("#btn_size_range", "#btn_size", "#btn_size_val", 12, 50, 1, 22);
            setSlider("#btn_radius_range", "#btn_radius", "#btn_radius_val", 0, 100, 2, 4);
            setSlider("#btn_border_range", "#btn_border", "#btn_border_val", 0, 20, 1, 1);
            setSlider("#btn_padding_range", "#btn_padding", "#btn_padding_val", 0, 100, 1, 10);
            setSlider("#btn_padding_lr_range", "#btn_padding_lr", "#btn_padding_lr_val", 0, 100, 1, 20);
            setSlider("#btn_margin_range", "#btn_margin", "#btn_margin_val", 0, 200, 5, 0);

            $("#btn_use_toggle").removeClass("on");
            $("#btn_use").val("0");
            $("#is_sub_toggle").removeClass("on");
            $("#is_sub").val("0");
            $("#btn_link_blank").prop("checked", false);

            updatePreview();
        }

        $(document).on("click", ".rb_swiper_list", function() {
            var carouselId = $(this).data("carousel-id");
            if (carouselId) loadCarouselData(carouselId);
        });

        function loadCarouselData(id) {
            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_get.php",
                type: "POST",
                data: { id: id },
                dataType: "json",
                success: function(data) {
                    if (data.success) {
                        $("#carousel_modal_title").text("캐러셀 수정");
                        $("#carousel_mode").val("update");
                        $("#carousel_delete_btn_footer").show();
                        $("#carousel_modal").fadeIn(200, function() {
                            fillFormWithData(data.carousel);
                        });
                    }
                }
            });
        }

        function fillFormWithData(data) {
            $("#carousel_id").val(data.id);
            $("#main_text").val(data.main_text);
            $("#sub_text").val(data.sub_text);
            $("#btn_text").val(data.btn_text);
            $("#btn_link").val(data.btn_link || "");
            $("#btn_link_blank").prop("checked", data.btn_link_blank == 1);
            $("#main_color").val(data.main_color);
            $("#sub_color").val(data.sub_color);
            $("#btn_bg_color").val(data.btn_bg_color);
            $("#btn_text_color").val(data.btn_text_color || "#ffffff");
            $("#btn_border_color").val(data.btn_border_color || "#000000");
            $("#carousel_type_mode").val(data.carousel_type_mode || "<?php echo (defined('_SHOP_')) ? 'shop' : 'community'; ?>");

            var colorFields = ["#main_color","#sub_color","#btn_bg_color","#btn_text_color","#btn_border_color"];
            colorFields.forEach(function(selector) {
                var el = document.querySelector(selector);
                if (el) el.dispatchEvent(new Event("input", {bubbles:true}));
            });

            $("#main_align").val(data.main_align || "left");
            $("#sub_align").val(data.sub_align || "left");
            $("#btn_align").val(data.btn_align || "left");
            $("#main_weight").val(data.main_weight || "font-R");
            $("#sub_weight").val(data.sub_weight || "font-R");
            $("#btn_weight").val(data.btn_weight || "font-R");

            setSlider("#main_size_range", "#main_size", "#main_size_val", 12, 80, 1, data.main_size || 20);
            setSlider("#sub_size_range", "#sub_size", "#sub_size_val", 12, 50, 1, data.sub_size || 14);
            setSlider("#sub_margin_range", "#sub_margin", "#sub_margin_val", 0, 200, 5, data.sub_margin || 10);
            setSlider("#btn_size_range", "#btn_size", "#btn_size_val", 12, 50, 1, data.btn_size || 14);
            setSlider("#btn_radius_range", "#btn_radius", "#btn_radius_val", 0, 100, 2, data.btn_radius || 4);
            setSlider("#btn_border_range", "#btn_border", "#btn_border_val", 0, 20, 1, data.btn_border || 1);
            setSlider("#btn_padding_range", "#btn_padding", "#btn_padding_val", 0, 100, 1, data.btn_padding || 10);
            setSlider("#btn_padding_lr_range", "#btn_padding_lr", "#btn_padding_lr_val", 0, 100, 1, data.btn_padding_lr || 20);
            setSlider("#btn_margin_range", "#btn_margin", "#btn_margin_val", 0, 200, 5, data.btn_margin || 0);

            var hasBtn = (data.btn_text && data.btn_text.trim() !== '');
            if (hasBtn) {
                $("#btn_use_toggle").addClass("on");
                $("#btn_use").val("1");
            } else {
                $("#btn_use_toggle").removeClass("on");
                $("#btn_use").val("0");
            }

            var isSub = (data.is_sub == 1);
            if (isSub) {
                $("#is_sub_toggle").addClass("on");
                $("#is_sub").val("1");
            } else {
                $("#is_sub_toggle").removeClass("on");
                $("#is_sub").val("0");
            }

            if (data.image_url) {
                $("#carousel_preview").css("background-image", "url("+data.image_url+")");
            }

            updatePreview();
        }

        function doSubmit() {
            if ($("#btn_use_toggle").hasClass("on") && $("#btn_text").val().trim() === '') {
                alert("버튼 텍스트를 입력해주세요.");
                // 버튼 탭으로 이동
                $("#carousel_modal .cm_tab[data-tab='btn']").trigger("click");
                $("#btn_text").focus();
                return;
            }

            var formData = new FormData($("#carousel_form")[0]);
            formData.append("cf_theme", "<?php echo $config['cf_theme']; ?>");
            formData.set("main_text", $("#main_text").val());
            formData.set("sub_text", $("#sub_text").val());
            formData.set("carousel_type_mode", $("#carousel_type_mode").val());
            formData.set("btn_use", $("#btn_use_toggle").hasClass("on") ? "1" : "0");
            formData.set("is_sub", $("#is_sub_toggle").hasClass("on") ? "1" : "0");
            formData.set("btn_margin", $("#btn_margin").val());

            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_update.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert(response.message, function() {
                            location.reload();
                        });
                    } else {
                        alert(response.message);
                    }
                },
                error: function() { alert("오류가 발생했습니다."); }
            });
        }

        function refreshCarouselList() {
            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_get.php",
                type: "POST",
                data: {
                    get_list: 1,
                    cf_theme: "<?php echo $config['cf_theme']; ?>",
                    carousel_type_mode: "<?php echo (defined('_SHOP_')) ? 'shop' : 'community'; ?>"
                },
                dataType: "json",
                success: function(res) {
                    if (!res.success) return;
                    $(".ca_cnt").text(res.count);
                    if (res.count === 0) {
                        $("#carousel_list").hide();
                        $(".no_ca_data").show();
                        return;
                    }
                    $(".no_ca_data").hide();
                    var html = '';
                    $.each(res.list, function(i, item) {
                        html += '<div class="rb_swiper_list" data-carousel-id="'+item.id+'" style="cursor:pointer;">';
                        if (item.image_url) {
                            html += '<img src="'+item.image_url+'" alt="" class="rb_th_sw_card_img">';
                        }
                        html += '<div class="rb_th_sw_card">';
                        html += '<ul class="cut font-R font-14">'+item.main_text+'</ul>';
                        if (item.sub_text) {
                            html += '<ul class="cut font-R font-12 color-999">'+item.sub_text+'</ul>';
                        }
                        html += '</div></div>';
                    });
                    var $swiperEl = $("#carousel_list .rb_swiper");
                    if ($swiperEl.length) {
                        var oldInstance = $swiperEl.data('rb-swiper-instance');
                        if (oldInstance) {
                            oldInstance.destroy(true, true);
                            $swiperEl.removeData('rb-swiper-instance');
                            $swiperEl.removeData('rb-swiper-mode');
                        }
                        $swiperEl.find('.rb-swiper-wrapper').html(html);
                        setupResponsiveSlider($swiperEl);
                    }
                    if ($("#carousel_list").length === 0) {
                        location.reload();
                    }
                }
            });
        }

    });
</script>
