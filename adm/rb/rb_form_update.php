<?php
$sub_menu = '000000';
include_once('./_common.php');

check_demo();
auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

if (isset($_POST['install']) && $_POST['install'] == 1) {

    @mkdir(G5_DATA_PATH."/logos", G5_DIR_PERMISSION);
    @chmod(G5_DATA_PATH."/logos", G5_DIR_PERMISSION);

    $lpimg = isset($_FILES['bu_logo_pc']['tmp_name']) ? $_FILES['bu_logo_pc']['tmp_name'] : null;
    $lpimg_name = isset($_FILES['bu_logo_pc']['name']) ? $_FILES['bu_logo_pc']['name'] : null;

    $lpwimg = isset($_FILES['bu_logo_pc_w']['tmp_name']) ? $_FILES['bu_logo_pc_w']['tmp_name'] : null;
    $lpwimg_name = isset($_FILES['bu_logo_pc_w']['name']) ? $_FILES['bu_logo_pc_w']['name'] : null;

    $lmimg = isset($_FILES['bu_logo_mo']['tmp_name']) ? $_FILES['bu_logo_mo']['tmp_name'] : null;
    $lmimg_name = isset($_FILES['bu_logo_mo']['name']) ? $_FILES['bu_logo_mo']['name'] : null;

    $lmwimg = isset($_FILES['bu_logo_mo_w']['tmp_name']) ? $_FILES['bu_logo_mo_w']['tmp_name'] : null;
    $lmwimg_name = isset($_FILES['bu_logo_mo_w']['name']) ? $_FILES['bu_logo_mo_w']['name'] : null;

    if (isset($bu_logo_pc_del) && $bu_logo_pc_del) @unlink(G5_DATA_PATH."/logos/pc");
    if (isset($bu_logo_pc_w_del) && $bu_logo_pc_w_del) @unlink(G5_DATA_PATH."/logos/pc_w");
    if (isset($bu_logo_mo_del) && $bu_logo_mo_del) @unlink(G5_DATA_PATH."/logos/mo");
    if (isset($bu_logo_mo_w_del) && $bu_logo_mo_w_del) @unlink(G5_DATA_PATH."/logos/mo_w");

    //이미지인지 체크
    if( $lpimg || $lpimg_name){
        if( !preg_match('/\.(gif|jpe?g|bmp|png)$/i', $lpimg_name) ){
            alert("이미지 파일만 업로드 할수 있습니다.");
        }
    }

    //이미지인지 체크
    if( $lpwimg || $lpwimg_name ){
        if( !preg_match('/\.(gif|jpe?g|bmp|png)$/i', $lpwimg_name) ){
            alert("이미지 파일만 업로드 할수 있습니다.");
        }
    }

    //이미지인지 체크
    if( $lmimg || $lmimg_name ){
        if( !preg_match('/\.(gif|jpe?g|bmp|png)$/i', $lmimg_name) ){
            alert("이미지 파일만 업로드 할수 있습니다.");
        }
    }

    //이미지인지 체크
    if( $lmwimg || $lmwimg_name ){
        if( !preg_match('/\.(gif|jpe?g|bmp|png)$/i', $lmwimg_name) ){
            alert("이미지 파일만 업로드 할수 있습니다.");
        }
    }

    //컬럼이 있는지 검사한다.
    $cnt = sql_fetch (" select COUNT(*) as cnt from rb_builder ");

    $spinner_col = sql_fetch(" SHOW COLUMNS FROM rb_builder LIKE 'bu_module_spinner_use' ");
    if (empty($spinner_col['Field'])) {
        sql_query(" ALTER TABLE rb_builder ADD `bu_module_spinner_use` int(4) NOT NULL DEFAULT 0 AFTER `bu_load` ", false);
    }

    $mobile_menu_position_col = sql_fetch(" SHOW COLUMNS FROM rb_builder LIKE 'bu_mobile_menu_position' ");
    $mobile_menu_icon_col = sql_fetch(" SHOW COLUMNS FROM rb_builder LIKE 'bu_mobile_menu_icon' ");
    $mobile_menu_icon_color_col = sql_fetch(" SHOW COLUMNS FROM rb_builder LIKE 'bu_mobile_menu_icon_color_disable' ");
    $mobile_menu_icon_svg_col = sql_fetch(" SHOW COLUMNS FROM rb_builder LIKE 'bu_mobile_menu_icon_svg' ");
    if (empty($mobile_menu_position_col['Field']) || empty($mobile_menu_icon_col['Field']) || empty($mobile_menu_icon_color_col['Field']) || empty($mobile_menu_icon_svg_col['Field'])) {
        alert('모바일 메뉴 설정 DB가 적용되지 않았습니다. 빌더정보의 DB 설치 및 업데이트를 먼저 실행해 주세요.', './rb_form.php#anc_rb5');
    }

    //@닥본사 님 코드적용 (PHP8.4.4 관련 오류)
    $bu_load = isset($_POST['bu_load']) && is_numeric($_POST['bu_load']) ? (int)$_POST['bu_load'] : 0;
    $bu_module_spinner_use = !empty($_POST['bu_module_spinner_use']) ? 1 : 0;
    $bu_systemmsg_use = isset($_POST['bu_systemmsg_use']) && is_numeric($_POST['bu_systemmsg_use']) ? (int)$_POST['bu_systemmsg_use'] : 0;
    $bu_purchase_confirm_use = !empty($_POST['bu_purchase_confirm_use']) ? 1 : 0;
    $bu_mobile_menu_position = isset($_POST['bu_mobile_menu_position']) && $_POST['bu_mobile_menu_position'] === 'right' ? 'right' : 'left';
    $bu_mobile_menu_icon = isset($_POST['bu_mobile_menu_icon']) ? (int) $_POST['bu_mobile_menu_icon'] : 1;
    if ($bu_mobile_menu_icon < 1 || $bu_mobile_menu_icon > 7) {
        $bu_mobile_menu_icon = 1;
    }
    $bu_mobile_menu_icon_color_disable = !empty($_POST['bu_mobile_menu_icon_color_disable']) ? 1 : 0;
    $bu_mobile_menu_icon_svg_raw = isset($_POST['bu_mobile_menu_icon_svg']) ? trim((string) $_POST['bu_mobile_menu_icon_svg']) : '';
    $bu_mobile_menu_icon_svg = rb_sanitize_mobile_menu_svg($bu_mobile_menu_icon_svg_raw);
    if ($bu_mobile_menu_icon_svg_raw !== '' && $bu_mobile_menu_icon_svg === '') {
        alert('사용할 수 없는 SVG 코드입니다. 올바른 SVG 코드를 입력해 주세요.', './rb_form.php#anc_rb5');
    }
    if ($bu_mobile_menu_icon === 7 && $bu_mobile_menu_icon_svg === '') {
        alert('직접 추가 아이콘을 사용하려면 SVG 코드를 입력해 주세요.', './rb_form.php#anc_rb5');
    }
    $bu_mobile_menu_icon_svg_sql = sql_escape_string($bu_mobile_menu_icon_svg);

    $bu_mini_use1 = isset($_POST['bu_mini_use1']) && is_numeric($_POST['bu_mini_use1']) ? (int)$_POST['bu_mini_use1'] : 0;
    $bu_mini_use2 = isset($_POST['bu_mini_use2']) && is_numeric($_POST['bu_mini_use2']) ? (int)$_POST['bu_mini_use2'] : 0;
    $bu_mini_use3 = isset($_POST['bu_mini_use3']) && is_numeric($_POST['bu_mini_use3']) ? (int)$_POST['bu_mini_use3'] : 0;
    $bu_mini_use4 = isset($_POST['bu_mini_use4']) && is_numeric($_POST['bu_mini_use4']) ? (int)$_POST['bu_mini_use4'] : 0;
    $bu_mini_use5 = isset($_POST['bu_mini_use5']) && is_numeric($_POST['bu_mini_use5']) ? (int)$_POST['bu_mini_use5'] : 0;

    if($cnt['cnt'] > 0) {
            $sql = " update rb_builder
                set bu_load = '{$bu_load}',
                    bu_module_spinner_use = '{$bu_module_spinner_use}',
                    bu_1 = '{$_POST['bu_1']}',
                    bu_2 = '{$_POST['bu_2']}',
                    bu_3 = '{$_POST['bu_3']}',
                    bu_4 = '{$_POST['bu_4']}',
                    bu_5 = '{$_POST['bu_5']}',
                    bu_6 = '{$_POST['bu_6']}',
                    bu_7 = '{$_POST['bu_7']}',
                    bu_8 = '{$_POST['bu_8']}',
                    bu_9 = '{$_POST['bu_9']}',
                    bu_10 = '{$_POST['bu_10']}',
                    bu_11 = '{$_POST['bu_11']}',
                    bu_12 = '{$_POST['bu_12']}',
                    bu_13 = '{$_POST['bu_13']}',
                    bu_14 = '{$_POST['bu_14']}',
                    bu_15 = '{$_POST['bu_15']}',
                    bu_16 = '{$_POST['bu_16']}',
                    bu_17 = '{$_POST['bu_17']}',
                    bu_18 = '{$_POST['bu_18']}',
                    bu_19 = '{$_POST['bu_19']}',
                    bu_20 = '{$_POST['bu_20']}',
                    bu_sns1 = '{$_POST['bu_sns1']}',
                    bu_sns2 = '{$_POST['bu_sns2']}',
                    bu_sns3 = '{$_POST['bu_sns3']}',
                    bu_sns4 = '{$_POST['bu_sns4']}',
                    bu_sns5 = '{$_POST['bu_sns5']}',
                    bu_sns6 = '{$_POST['bu_sns6']}',
                    bu_sns7 = '{$_POST['bu_sns7']}',
                    bu_sns8 = '{$_POST['bu_sns8']}',
                    bu_sns9 = '{$_POST['bu_sns9']}',
                    bu_sns10 = '{$_POST['bu_sns10']}',
                    bu_mini_use1 = '{$bu_mini_use1}',
                    bu_mini_use2 = '{$bu_mini_use2}',
                    bu_mini_use3 = '{$bu_mini_use3}',
                    bu_mini_use4 = '{$bu_mini_use4}',
                    bu_mini_use5 = '{$bu_mini_use5}',
                    bu_purchase_confirm_use = '{$bu_purchase_confirm_use}',
                    bu_viewport = '{$_POST['bu_viewport']}',
                    bu_mobile_menu_position = '{$bu_mobile_menu_position}',
                    bu_mobile_menu_icon = '{$bu_mobile_menu_icon}',
                    bu_mobile_menu_icon_color_disable = '{$bu_mobile_menu_icon_color_disable}',
                    bu_mobile_menu_icon_svg = '{$bu_mobile_menu_icon_svg_sql}',
                    bu_systemmsg_use = '{$bu_systemmsg_use}',
                    bu_datetime = '".G5_TIME_YMDHIS."' ";
            sql_query($sql);
    } else {

            $sql = " insert rb_builder
                set bu_load = '{$bu_load}',
                    bu_module_spinner_use = '{$bu_module_spinner_use}',
                    bu_1 = '{$_POST['bu_1']}',
                    bu_2 = '{$_POST['bu_2']}',
                    bu_3 = '{$_POST['bu_3']}',
                    bu_4 = '{$_POST['bu_4']}',
                    bu_5 = '{$_POST['bu_5']}',
                    bu_6 = '{$_POST['bu_6']}',
                    bu_7 = '{$_POST['bu_7']}',
                    bu_8 = '{$_POST['bu_8']}',
                    bu_9 = '{$_POST['bu_9']}',
                    bu_10 = '{$_POST['bu_10']}',
                    bu_11 = '{$_POST['bu_11']}',
                    bu_12 = '{$_POST['bu_12']}',
                    bu_13 = '{$_POST['bu_13']}',
                    bu_14 = '{$_POST['bu_14']}',
                    bu_15 = '{$_POST['bu_15']}',
                    bu_16 = '{$_POST['bu_16']}',
                    bu_17 = '{$_POST['bu_17']}',
                    bu_18 = '{$_POST['bu_18']}',
                    bu_19 = '{$_POST['bu_19']}',
                    bu_20 = '{$_POST['bu_20']}',
                    bu_sns1 = '{$_POST['bu_sns1']}',
                    bu_sns2 = '{$_POST['bu_sns2']}',
                    bu_sns3 = '{$_POST['bu_sns3']}',
                    bu_sns4 = '{$_POST['bu_sns4']}',
                    bu_sns5 = '{$_POST['bu_sns5']}',
                    bu_sns6 = '{$_POST['bu_sns6']}',
                    bu_sns7 = '{$_POST['bu_sns7']}',
                    bu_sns8 = '{$_POST['bu_sns8']}',
                    bu_sns9 = '{$_POST['bu_sns9']}',
                    bu_sns10 = '{$_POST['bu_sns10']}',
                    bu_mini_use1 = '{$bu_mini_use1}',
                    bu_mini_use2 = '{$bu_mini_use2}',
                    bu_mini_use3 = '{$bu_mini_use3}',
                    bu_mini_use4 = '{$bu_mini_use4}',
                    bu_mini_use5 = '{$bu_mini_use5}',
                    bu_purchase_confirm_use = '{$bu_purchase_confirm_use}',
                    bu_viewport = '{$_POST['bu_viewport']}',
                    bu_mobile_menu_position = '{$bu_mobile_menu_position}',
                    bu_mobile_menu_icon = '{$bu_mobile_menu_icon}',
                    bu_mobile_menu_icon_color_disable = '{$bu_mobile_menu_icon_color_disable}',
                    bu_mobile_menu_icon_svg = '{$bu_mobile_menu_icon_svg_sql}',
                    bu_systemmsg_use = '{$bu_systemmsg_use}',
                    bu_datetime = '".G5_TIME_YMDHIS."' ";
            sql_query($sql);
    }

    if ($lpimg_name) rb_upload_files($lpimg, 'pc', G5_DATA_PATH."/logos");
    if ($lpwimg_name) rb_upload_files($lpwimg, 'pc_w', G5_DATA_PATH."/logos");
    if ($lmimg_name) rb_upload_files($lmimg, 'mo', G5_DATA_PATH."/logos");
    if ($lmwimg_name) rb_upload_files($lmwimg, 'mo_w', G5_DATA_PATH."/logos");

    $lpimg_in = G5_DATA_PATH."/logos/pc";
    if (file_exists($lpimg_in)) {
        $sql = " update rb_builder set bu_logo_pc = 'pc' ";
        sql_query($sql);
    }

    $lpwimg_in = G5_DATA_PATH."/logos/pc_w";
    if (file_exists($lpwimg_in)) {
        $sql = " update rb_builder set bu_logo_pc_w = 'pc_w' ";
        sql_query($sql);
    }

    $lmimg_in = G5_DATA_PATH."/logos/mo";
    if (file_exists($lmimg_in)) {
        $sql = " update rb_builder set bu_logo_mo = 'mo' ";
        sql_query($sql);
    }

    $lmwimg_in = G5_DATA_PATH."/logos/mo_w";
    if (file_exists($lmwimg_in)) {
        $sql = " update rb_builder set bu_logo_mo_w = 'mo_w' ";
        sql_query($sql);
    }

} else {
    alert('빌더 2.2.7은 인증 토큰 등록 후 DB 설치 및 업데이트에서 자동 설치됩니다.', './rb_form.php');
}
update_rewrite_rules();
goto_url('./rb_form.php', false);

?>
