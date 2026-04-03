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
            <form id="carousel_form">
                <input type="hidden" id="carousel_id" name="carousel_id" value="">
                <input type="hidden" id="carousel_mode" name="mode" value="insert">
                <input type="hidden" id="carousel_type_mode" name="carousel_type_mode" value="">

                <!-- 미리보기 영역 -->
                <div class="carousel_preview_wrap">
                    <h4>미리보기는 실제 보여지는 크기보다 2배 작게 보여요.</h4>
                    <div class="carousel_preview" id="carousel_preview">
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
                </div>

                <div class="form_row">
                    <div>
                        <input type="checkbox" id="is_sub" name="is_sub" value="1">
                        <label for="is_sub" class="label_chk">서브페이지 상단 공통 배경으로 사용 (이미지만 사용)</label>
                    </div>
                    <button type="button" id="is_sub_load_btn" class="btn_sub_load">기존 설정 적용하기</button>
                </div>

                <!-- 이미지 등록 -->
                <div class="form_section">
                    <h5 class="section_title">이미지</h5>
                    <div class="form_group">
                        <input type="file" id="carousel_image" name="carousel_image" accept="image/*">
                        <!--
                        <div id="image_preview" class="image_preview"></div>
                        -->
                    </div>
                </div>

                <!-- 메인텍스트 -->

                <div class="form_row">

                    <div class="form_group half">
                        <div class="form_section">
                            <h5 class="section_title">메인 텍스트</h5>

                            <div class="form_group">
                                <textarea id="main_text" name="main_text" rows="3" placeholder="메인텍스트를 입력하세요"></textarea>
                            </div>

                            <div class="form_row">
                                <div class="form_group half">
                                    <label>정렬</label>
                                    <select id="main_align" name="main_align" class="select_tiny">
                                        <option value="left">좌측</option>
                                        <option value="center">중앙</option>
                                        <option value="right">우측</option>
                                    </select>
                                </div>
                                <div class="form_group half">
                                    <label>굵기</label>
                                    <select id="main_weight" name="main_weight" class="select_tiny">
                                        <option value="font-R">Regular</option>
                                        <option value="font-B">Bold</option>
                                        <option value="font-H">Heavy</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form_row">
                                <div class="form_group half">
                                    <label>컬러</label>
                                    <div class="color_set_wrap square">
                                        <input type="text" class="coloris-modal" id="main_color" name="main_color" value="#000000">
                                    </div>
                                </div>
                                <div class="form_group half">
                                    <label>텍스트 크기 <span id="main_size_val" class="font-B">20</span>px</label>
                                    <div id="main_size_range" class="rb_range_item"></div>
                                    <input type="hidden" id="main_size" name="main_size" value="20">
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="form_group half">
                        <div class="form_section">
                            <h5 class="section_title">서브 텍스트</h5>

                            <div class="form_group">
                                <textarea id="sub_text" name="sub_text" rows="3" placeholder="서브텍스트를 입력하세요"></textarea>
                            </div>

                            <div class="form_row">
                                <div class="form_group half">
                                    <label>정렬</label>
                                    <select id="sub_align" name="sub_align" class="select_tiny">
                                        <option value="left">좌측</option>
                                        <option value="center">중앙</option>
                                        <option value="right">우측</option>
                                    </select>
                                </div>
                                <div class="form_group half">
                                    <label>굵기</label>
                                    <select id="sub_weight" name="sub_weight" class="select_tiny">
                                        <option value="font-R">Regular</option>
                                        <option value="font-B">Bold</option>
                                        <option value="font-H">Heavy</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form_row">
                                <div class="form_group half">
                                    <label>컬러</label>
                                    <div class="color_set_wrap square">
                                        <input type="text" class="coloris-modal" id="sub_color" name="sub_color" value="#999999">
                                    </div>
                                </div>
                                <div class="form_group half">
                                    <label>텍스트 크기 <span id="sub_size_val" class="font-B">14</span>px</label>
                                    <div id="sub_size_range" class="rb_range_item"></div>
                                    <input type="hidden" id="sub_size" name="sub_size" value="14">
                                </div>
                            </div>

                            <div class="form_group">
                                <label>상단간격 <span id="sub_margin_val">10</span>px</label>
                                <div id="sub_margin_range" class="rb_range_item"></div>
                                <input type="hidden" id="sub_margin" name="sub_margin" value="10">
                            </div>


                        </div>
                    </div>

                </div>


                <!-- 버튼 -->
                <div class="form_section">
                    <h5 class="section_title">버튼　<div class="right_wrap"><input type="checkbox" id="btn_use" name="btn_use" value="1"> <label for="btn_use">보이기</label></div>
                    </h5>

                    <div id="btn_fields" style="display:none;">

                        <div class="form_row">
                            <div class="form_group half">

                                <div class="form_group">
                                    <label>텍스트</label>
                                    <input type="text" id="btn_text" name="btn_text" maxlength="100" placeholder="버튼 텍스트를 입력하세요">
                                </div>
                                <div class="form_row">
                                    <div class="form_group half">
                                        <label>배경컬러</label>
                                        <div class="color_set_wrap square">
                                            <input type="text" class="coloris-modal" id="btn_bg_color" name="btn_bg_color" value="#000000">
                                        </div>
                                    </div>
                                    <div class="form_group half">
                                        <label>텍스트 컬러</label>
                                        <div class="color_set_wrap square">
                                            <input type="text" class="coloris-modal" id="btn_text_color" name="btn_text_color" value="#ffffff">
                                        </div>
                                    </div>
                                </div>

                                <div class="form_row">
                                    <div class="form_group half">
                                        <label>상하여백 <span id="btn_padding_val" class="font-B">10</span>px</label>
                                        <div id="btn_padding_range" class="rb_range_item"></div>
                                        <input type="hidden" id="btn_padding" name="btn_padding" value="10">
                                    </div>
                                    <div class="form_group half">
                                        <label>좌우여백 <span id="btn_padding_lr_val" class="font-B">20</span>px</label>
                                        <div id="btn_padding_lr_range" class="rb_range_item"></div>
                                        <input type="hidden" id="btn_padding_lr" name="btn_padding_lr" value="20">
                                    </div>

                                </div>


                                <div class="form_row">
                                    <div class="form_group half">
                                        <label>텍스트 크기 <span id="btn_size_val" class="font-B">14</span>px</label>
                                        <div id="btn_size_range" class="rb_range_item"></div>
                                        <input type="hidden" id="btn_size" name="btn_size" value="14">
                                    </div>

                                    <div class="form_group half">
                                        <label>모서리 라운드 <span id="btn_radius_val" class="font-B">4</span>px</label>
                                        <div id="btn_radius_range" class="rb_range_item"></div>
                                        <input type="hidden" id="btn_radius" name="btn_radius" value="4">
                                    </div>
                                </div>


                            </div>

                            <div class="form_group half">

                                <div class="form_group">
                                    <label>링크 URL</label>
                                    <div class="form_row">
                                        <div class="form_group third">
                                            <input type="text" id="btn_link" name="btn_link" placeholder="링크 URL">
                                        </div>
                                        <div class="form_group third">
                                            <input type="checkbox" id="btn_link_blank" name="btn_link_blank" value="1"> <label for="btn_link_blank" class="label_chk">새창</label>
                                        </div>
                                    </div>

                                </div>

                                <div class="form_row">
                                    <div class="form_group half">
                                        <label>테두리 컬러</label>
                                        <div class="color_set_wrap square">
                                            <input type="text" class="coloris-modal" id="btn_border_color" name="btn_border_color" value="#000000">
                                        </div>
                                    </div>

                                    <div class="form_group half">
                                        <label>테두리 두께 <span id="btn_border_val" class="font-B">1</span>px</label>
                                        <div id="btn_border_range" class="rb_range_item"></div>
                                        <input type="hidden" id="btn_border" name="btn_border" value="1">
                                    </div>
                                </div>

                                <div class="form_row">
                                    <div class="form_group half">
                                        <label>정렬</label>
                                        <select id="btn_align" name="btn_align" class="select_tiny">
                                            <option value="left">좌측</option>
                                            <option value="center">중앙</option>
                                            <option value="right">우측</option>
                                        </select>
                                    </div>

                                    <div class="form_group half">
                                        <label>텍스트 굵기</label>
                                        <select id="btn_weight" name="btn_weight" class="select_tiny">
                                            <option value="font-R">Regular</option>
                                            <option value="font-B">Bold</option>
                                            <option value="font-H">Heavy</option>
                                        </select>
                                    </div>


                                </div>







                            </div>
                        </div>

                    </div>
                </div>


                <!-- 버튼 영역 -->
                <div class="form_buttons">
                    <button type="button" id="carousel_delete_btn" class="btn_delete" style="display:none;">삭제</button>
                    <button type="submit" class="btn_submit">저장</button>
                    <button type="button" class="btn_cancel">취소</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    Coloris({
        el: '.coloris-modal'
    });
    Coloris.setInstance('.coloris-modal', {
        parent: '#carousel_modal', // 상위 container
        formatToggle: false, // Hex, RGB, HSL 토글버튼 활성
        format: 'hex', // 색상 포맷지정
        margin: 0, // margin
        swatchesOnly: false, // 색상 견본만 표시여부
        alpha: true, // 알파(투명) 활성여부
        //theme: 'polaroid', // default, large, polaroid, pill
        themeMode: 'Light', // dark, Light
        focusInput: true, // 색상코드 Input에 포커스 여부
        selectInput: true, // 선택기가 열릴때 색상값을 select 여부
        autoClose: true, // 자동닫기 - 확인 안됨
        inline: false, // color picker를 인라인 위젯으로 사용시 true
        defaultColor: '#ffffff', // 기본 색상인 인라인 mode
        //clearButton: true,
        //clearLabel: '초기화',
        closeButton: true, // true, false
        closeLabel: '닫기', // 닫기버튼 텍스트
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
                            var currentIsSub = $("#is_sub").is(":checked");

                            var carouselData = $.extend({}, res.carousel);
                            carouselData.image_url = '';
                            fillFormWithData(carouselData);

                            $("#carousel_id").val(currentIdVal);
                            $("#carousel_mode").val(currentMode);
                            $("#carousel_type_mode").val(currentTypeMode);
                            $("#is_sub").prop("checked", currentIsSub);
                            $("#carousel_image").val("");
                            $("#carousel_preview").css("background-image", "none");
                        });
                },
                error: function() {
                    alert("서버 오류");
                }
            });
        });

        $("#btn_use").on("change", function() {
            if ($(this).is(":checked")) {
                $("#btn_fields").show();
            } else {
                $("#btn_fields").hide();
            }
            updatePreview();
        });

        // 슬라이더 초기화 함수
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

        // 슬라이더 값 변경 함수 (destroy 후 재초기화)
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

        // 슬라이더들 초기화
        initSlider("#main_size_range", "#main_size", "#main_size_val", 12, 80, 1, 20);
        initSlider("#sub_size_range", "#sub_size", "#sub_size_val", 12, 50, 1, 14);
        initSlider("#sub_margin_range", "#sub_margin", "#sub_margin_val", 0, 200, 5, 10);
        initSlider("#btn_size_range", "#btn_size", "#btn_size_val", 12, 50, 1, 14);
        initSlider("#btn_radius_range", "#btn_radius", "#btn_radius_val", 0, 100, 2, 4);
        initSlider("#btn_border_range", "#btn_border", "#btn_border_val", 0, 20, 1, 1);
        initSlider("#btn_padding_range", "#btn_padding", "#btn_padding_val", 0, 100, 1, 10);
        initSlider("#btn_padding_lr_range", "#btn_padding_lr", "#btn_padding_lr_val", 0, 100, 1, 20);

        // 엔터키를 <br>로 변환하는 함수
        function convertNewlineToBr(text) {
            return text.replace(/\n/g, '<br>');
        }

        function hexToRgba(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            if (hex.length === 6) hex += 'ff';
            if (hex.length !== 8) return hex;
            var r = parseInt(hex.slice(0, 2), 16);
            var g = parseInt(hex.slice(2, 4), 16);
            var b = parseInt(hex.slice(4, 6), 16);
            var a = (parseInt(hex.slice(6, 8), 16) / 255).toFixed(3);
            return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
        }

        // 미리보기 업데이트 함수
        function updatePreview() {
            var mainText = $("#main_text").val() || "메인텍스트";
            var mainSize = $("#main_size").val();
            var mainColor = hexToRgba($("#main_color").val());
            var mainAlign = $("#main_align").val();
            var mainWeight = $("#main_weight").val();

            var subText = $("#sub_text").val() || "서브텍스트";
            var subSize = $("#sub_size").val();
            var subColor = hexToRgba($("#sub_color").val());
            var subMargin = $("#sub_margin").val();
            var subAlign = $("#sub_align").val();
            var subWeight = $("#sub_weight").val();

            var btnText = $("#btn_text").val() || "버튼텍스트";
            var btnSize = $("#btn_size").val();
            var btnRadius = $("#btn_radius").val();
            var btnBorder = $("#btn_border").val();
            var btnPadding = $("#btn_padding").val();
            var btnPaddingLr = $("#btn_padding_lr").val();
            var btnBgColor = hexToRgba($("#btn_bg_color").val());
            var btnTextColor = hexToRgba($("#btn_text_color").val());
            var btnBorderColor = hexToRgba($("#btn_border_color").val());
            var btnAlign = $("#btn_align").val();

            $("#preview_main").html(convertNewlineToBr(mainText)).css({
                "font-size": (mainSize / 2) + "px",
                "color": mainColor,
                "text-align": mainAlign
            }).removeClass('font-R font-B font-H').addClass(mainWeight);

            $("#preview_sub").html(convertNewlineToBr(subText)).css({
                "font-size": (subSize / 2) + "px",
                "color": subColor,
                "margin-top": (subMargin / 2) + "px",
                "text-align": subAlign
            }).removeClass('font-R font-B font-H').addClass(subWeight);

            var btnWeight = $("#btn_weight").val();
            $("#preview_btn_text").text(btnText);
            $("#preview_btn").css({
                "font-size": (btnSize / 2) + "px",
                "border-radius": (btnRadius / 2) + "px",
                "border-width": btnBorder + "px",
                "background-color": btnBgColor,
                "border-color": btnBorderColor,
                "color": btnTextColor,
                "padding": (btnPadding / 2) + "px " + (btnPaddingLr / 2) + "px"
            });

            $("#preview_btn_wrap").css("text-align", btnAlign);
            $("#preview_btn").removeClass("font-R font-B font-H").addClass(btnWeight);

            if ($("#btn_use").is(":checked")) {
                $("#preview_btn_wrap").show();
            } else {
                $("#preview_btn_wrap").hide();
            }
        }

        // textarea 엔터키 처리
        $("#main_text, #sub_text").on("keydown", function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                var textarea = $(this);
                var cursorPos = textarea[0].selectionStart;
                var textBefore = textarea.val().substring(0, cursorPos);
                var textAfter = textarea.val().substring(cursorPos);
                textarea.val(textBefore + "\n" + textAfter);
                textarea[0].selectionStart = textarea[0].selectionEnd = cursorPos + 1;
                updatePreview();
            }
        });

        // 입력 변경시 미리보기 업데이트
        $("#main_text, #sub_text, #btn_text").on("input", updatePreview);
        $("#main_color, #sub_color, #btn_bg_color, #btn_text_color, #btn_border_color").on("change", updatePreview);
        $("#main_align, #sub_align, #btn_align, #main_weight, #sub_weight, #btn_weight").on("change", updatePreview);

        // 이미지 미리보기
        $("#carousel_image").on("change", function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $("#carousel_preview").css("background-image", "url(" + e.target.result + ")");
                    //$("#image_preview").html('<img src="' + e.target.result + '">').show();
                };
                reader.readAsDataURL(file);
            }
        });

        // 캐러셀 추가 버튼
        $("#theme_ca_add_btn").on("click", function() {
            resetForm();
            $("#carousel_modal_title").text("캐러셀 추가");
            $("#carousel_mode").val("insert");
            $("#carousel_delete_btn").hide();
            $("#carousel_modal").fadeIn(200);
        });

        // 모달 닫기
        $(".rb_modal_close, .btn_cancel").on("click", function() {
            $("#carousel_modal").fadeOut(200);
        });

        // 오버레이 클릭시 닫기
        $(".rb_modal_overlay").on("click", function() {
            $("#carousel_modal").fadeOut(200);
        });

        // 폼 초기화
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

            setSlider("#main_size_range", "#main_size", "#main_size_val", 12, 80, 1, 40);
            setSlider("#sub_size_range", "#sub_size", "#sub_size_val", 12, 50, 1, 26);
            setSlider("#sub_margin_range", "#sub_margin", "#sub_margin_val", 0, 200, 5, 10);
            setSlider("#btn_size_range", "#btn_size", "#btn_size_val", 12, 50, 1, 22);
            setSlider("#btn_radius_range", "#btn_radius", "#btn_radius_val", 0, 100, 2, 4);
            setSlider("#btn_border_range", "#btn_border", "#btn_border_val", 0, 20, 1, 1);
            setSlider("#btn_padding_range", "#btn_padding", "#btn_padding_val", 0, 100, 1, 10);
            setSlider("#btn_padding_lr_range", "#btn_padding_lr", "#btn_padding_lr_val", 0, 100, 1, 20);

            $("#btn_use").prop("checked", false);
            $("#btn_link_blank").prop("checked", false);
            $("#btn_fields").hide();
            $("#is_sub").prop("checked", false);

            updatePreview();
        }

        // 캐러셀 아이템 클릭 (수정)
        $(document).on("click", ".rb_swiper_list", function() {
            var carouselId = $(this).data("carousel-id");
            if (carouselId) {
                loadCarouselData(carouselId);
            }
        });

        // 캐러셀 데이터 불러오기
        function loadCarouselData(id) {
            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_get.php",
                type: "POST",
                data: {
                    id: id
                },
                dataType: "json",
                success: function(data) {
                    if (data.success) {
                        $("#carousel_modal_title").text("캐러셀 수정");
                        $("#carousel_mode").val("update");
                        $("#carousel_delete_btn").show();
                        $("#carousel_modal").fadeIn(200, function() {
                            fillFormWithData(data.carousel);
                        });
                    }
                }
            });
        }

        // 폼에 데이터 채우기
        function fillFormWithData(data) {
            $("#carousel_id").val(data.id);
            $("#main_text").val(data.main_text);
            $("#sub_text").val(data.sub_text);
            $("#btn_text").val(data.btn_text);
            $("#btn_link").val(data.btn_link || "");
            $("#btn_link_blank").prop("checked", data.btn_link_blank == 1);
            $("#btn_svg").val(data.btn_svg || "");
            $("#main_color").val(data.main_color);
            $("#sub_color").val(data.sub_color);
            $("#btn_bg_color").val(data.btn_bg_color);
            $("#btn_text_color").val(data.btn_text_color || "#ffffff");
            $("#btn_border_color").val(data.btn_border_color || "#000000");
            $("#carousel_type_mode").val(data.carousel_type_mode || "<?php echo (defined('_SHOP_')) ? 'shop' : 'community'; ?>");

            // 아래 추가
            var colorFields = ["#main_color", "#sub_color", "#btn_bg_color", "#btn_text_color", "#btn_border_color"];
            colorFields.forEach(function(selector) {
                var el = document.querySelector(selector);
                if (el) {
                    el.dispatchEvent(new Event("input", {
                        bubbles: true
                    }));
                }
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

            var hasBtn = (data.btn_text && data.btn_text.trim() !== '');
            $("#btn_use").prop("checked", hasBtn);
            if (hasBtn) {
                $("#btn_fields").show();
            } else {
                $("#btn_fields").hide();
            }

            if (data.image_url) {
                $("#carousel_preview").css("background-image", "url(" + data.image_url + ")");
                $("#image_preview").html('<img src="' + data.image_url + '">').show();
            }

            $("#is_sub").prop("checked", data.is_sub == 1);
            updatePreview();
        }

        // 폼 제출
        $("#carousel_form").on("submit", function(e) {
            e.preventDefault();
            doSubmit(this);
        });

        function doSubmit(form) {

            // 추가
            if ($("#btn_use").is(":checked") && $("#btn_text").val().trim() === '') {
                alert("버튼 텍스트를 입력해주세요.");
                $("#btn_text").focus();
                return;
            }

            var formData = new FormData(form);
            formData.append("cf_theme", "<?php echo $config['cf_theme']; ?>");
            formData.set("main_text", $("#main_text").val());
            formData.set("sub_text", $("#sub_text").val());
            formData.set("carousel_type_mode", $("#carousel_type_mode").val());

            $.ajax({
                url: "<?php echo G5_THEME_URL ?>/rb.theme/rb.carousel_update.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $("#carousel_modal").fadeOut(200);
                        refreshCarouselList();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("오류가 발생했습니다.");
                }
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

                    // 카운트 업데이트
                    $(".ca_cnt").text(res.count);

                    if (res.count === 0) {
                        $("#carousel_list").hide();
                        $(".no_ca_data").show();
                        return;
                    }

                    $(".no_ca_data").hide();

                    // 목록 HTML 생성
                    var html = '';
                    $.each(res.list, function(i, item) {
                        html += '<div class="rb_swiper_list" data-carousel-id="' + item.id + '" style="cursor: pointer;">';
                        if (item.image_url) {
                            html += '<img src="' + item.image_url + '" alt="" class="rb_th_sw_card_img">';
                        }
                        html += '<div class="rb_th_sw_card">';
                        html += '<ul class="cut font-R font-14">' + item.main_text + '</ul>';
                        if (item.sub_text) {
                            html += '<ul class="cut font-R font-12 color-999">' + item.sub_text + '</ul>';
                        }
                        html += '</div></div>';
                    });

                    var $swiperEl = $("#carousel_list .rb_swiper");
                    if ($swiperEl.length) {
                        // 기존 인스턴스 destroy
                        var oldInstance = $swiperEl.data('rb-swiper-instance');
                        if (oldInstance) {
                            oldInstance.destroy(true, true);
                            $swiperEl.removeData('rb-swiper-instance');
                            $swiperEl.removeData('rb-swiper-mode'); // mode 초기화해야 재초기화됨
                        }

                        // wrapper를 rb_swiper_list만 있는 상태로 교체
                        $swiperEl.find('.rb-swiper-wrapper').html(html);

                        setupResponsiveSlider($swiperEl);
                    }


                    // carousel_list 없으면 새로 생성
                    if ($("#carousel_list").length === 0) {
                        location.reload();
                    }
                }
            });
        }

        // 삭제 버튼
        $("#carousel_delete_btn").on("click", function() {
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
                            alert(response.message);
                            $("#carousel_modal").fadeOut(200);
                            refreshCarouselList();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        });

    });
</script>
