<?php
if (!defined('_GNUBOARD_')) exit;

if (!isset($rb_panel_stage)) {
    $rb_panel_stage = 'register';
}

// 필수설정
if ($rb_panel_stage === 'register') {
    return array(
        'id' => 'sample_panel', //패널 고유아이디
        'title' => '샘플패널', //패널 타이틀
        'tooltip' => '샘플패널', //메뉴 롤오버 툴팁
        'order' => 100, //메뉴순서
        'icon' => G5_URL . '/rb/rb.config/image/icon_folder.svg', //메뉴아이콘 위치
        'visible' => false, //사용여부 true, false
    );
}

if ($rb_panel_stage === 'render') { //패널을 구성하세요.
    $panel_title = isset($rb_panel['title']) ? (string)$rb_panel['title'] : '샘플패널';
    $saved_message = isset($_SESSION['rb_panel_sample_message']) ? (string)$_SESSION['rb_panel_sample_message'] : '';
    $saved_enabled = !empty($_SESSION['rb_panel_sample_enabled']) ? '1' : '0';
    $saved_radio = isset($_SESSION['rb_panel_sample_radio']) ? (string)$_SESSION['rb_panel_sample_radio'] : 'option1';
    $saved_select = isset($_SESSION['rb_panel_sample_select']) ? (string)$_SESSION['rb_panel_sample_select'] : 'basic';
    ?>

    <h2 class="font-B">샘플 패널</h2>
    <h6 class="font-R rb_config_sub_txt">등록, 화면 출력, Ajax 저장을 한 파일에서 처리하는 예시입니다.<br>실무에서는 DB 저장 로직으로 변경하세요.</h6>

    <ul class="rb_config_sec">
        <h6 class="font-B">샘플 타이틀</h6>
        <h6 class="font-R rb_config_sub_txt">샘플입니다.<br>세션에 저장되는 간단한 텍스트입니다.</h6>

        <li class="config_wrap">
            <input type="text" id="rb_sample_panel_message" value="<?php echo htmlspecialchars($saved_message, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%; height:42px; padding:0 12px; border:1px solid #ddd; border-radius:6px;">
        </li>
    </ul>

    <ul class="rb_config_sec">
        <h6 class="font-B">샘플 타이틀</h6>
        <h6 class="font-R rb_config_sub_txt">샘플입니다.<br>세션에 저장되는 체크값 입니다.</h6>

        <li class="config_wrap">
            <input type="checkbox" id="rb_sample_panel_enabled" value="1" <?php echo $saved_enabled === '1' ? 'checked' : ''; ?>>
            <label for="rb_sample_panel_enabled" class="font-R">활성화</label>
        </li>
    </ul>

    <ul class="rb_config_sec">
        <h6 class="font-B">샘플 타이틀</h6>
        <h6 class="font-R rb_config_sub_txt">샘플입니다.<br>세션에 저장되는 체크값 입니다.</h6>

        <li class="config_wrap">
            <input type="radio" name="rb_sample_panel_radio" id="rb_sample_panel_radio_1" value="option1" <?php echo $saved_radio === 'option1' ? 'checked' : ''; ?>>
            <label for="rb_sample_panel_radio_1" class="font-R">옵션 1</label>
            <input type="radio" name="rb_sample_panel_radio" id="rb_sample_panel_radio_2" value="option2" <?php echo $saved_radio === 'option2' ? 'checked' : ''; ?>>
            <label for="rb_sample_panel_radio_2" class="font-R">옵션 2</label>
        </li>
    </ul>
    <ul class="rb_config_sec">
        <h6 class="font-B">샘플 타이틀</h6>
        <h6 class="font-R rb_config_sub_txt">샘플입니다.<br>세션에 저장되는 셀렉트 입니다.</h6>

        <li class="config_wrap">
            <select id="rb_sample_panel_select" style="width:100%; height:42px; padding:0 12px; border:1px solid #ddd; border-radius:6px;">
                <option value="basic" <?php echo $saved_select === 'basic' ? 'selected' : ''; ?>>기본</option>
                <option value="premium" <?php echo $saved_select === 'premium' ? 'selected' : ''; ?>>프리미엄</option>
                <option value="custom" <?php echo $saved_select === 'custom' ? 'selected' : ''; ?>>커스텀</option>
            </select>
        </li>
    </ul>
    <ul class="rb_config_sec">
        <button type="button" class="rb_config_reload mt-5 font-B" id="rb_sample_panel_save_btn">저장하기</button>
        <button type="button" class="rb_config_close mt-5 font-B" onclick="toggleSideOptions_close()">취소</button>
        <div class="cb"></div>
    </ul>

    <script>
        (function() { //저장하기 클릭액션
            var $btn = $('#rb_sample_panel_save_btn');
            if (!$btn.length) return;

            $btn.off('click.rbSamplePanel').on('click.rbSamplePanel', function() {
                var message = $('#rb_sample_panel_message').val() || '';
                var enabled = $('#rb_sample_panel_enabled').is(':checked') ? '1' : '0';
                var radioValue = $('input[name="rb_sample_panel_radio"]:checked').val() || 'option1';
                var selectValue = $('#rb_sample_panel_select').val() || 'basic';

                $.ajax({
                    url: '<?php echo G5_URL; ?>/rb/rb.config/ajax.panel.php', //고정
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        //패널 고유아이디 (필수)
                        //고유아이디를 ajax.panel.php 에서 찾아서 ($rb_panel_stage === 'ajax') { 를 실행 합니다.
                        panel_id: 'sample_panel',

                        //사용자데이터
                        sample_message: message,
                        sample_enabled: enabled,
                        sample_radio: radioValue,
                        sample_select: selectValue
                    },
                    success: function(response) {
                        if (response && response.status === 'ok') {
                            alert('<?php echo addslashes($panel_title); ?> 설정이 저장되었습니다.');
                            return;
                        }
                        alert(response && response.message ? response.message : '저장 중 오류가 발생했습니다.');
                    },
                    error: function() {
                        alert('저장 요청에 실패했습니다.');
                    }
                });
            });
        })();
    </script>
    <?php
    return;
}

if ($rb_panel_stage === 'ajax') { //저장 쿼리를 구성하세요.
    $sample_message = isset($_POST['sample_message']) ? trim((string)$_POST['sample_message']) : '';
    $sample_enabled = isset($_POST['sample_enabled']) && (string)$_POST['sample_enabled'] === '1' ? '1' : '0';
    $sample_radio = isset($_POST['sample_radio']) ? trim((string)$_POST['sample_radio']) : 'option1';
    $sample_select = isset($_POST['sample_select']) ? trim((string)$_POST['sample_select']) : 'basic';

    $_SESSION['rb_panel_sample_message'] = $sample_message;
    $_SESSION['rb_panel_sample_enabled'] = $sample_enabled;
    $_SESSION['rb_panel_sample_radio'] = $sample_radio;
    $_SESSION['rb_panel_sample_select'] = $sample_select;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'status' => 'ok',
        'message' => 'sample ajax response',
        'saved' => array(
            'sample_message' => $sample_message,
            'sample_enabled' => $sample_enabled,
            'sample_radio' => $sample_radio,
            'sample_select' => $sample_select,
        ),
    ));
    return;
}
