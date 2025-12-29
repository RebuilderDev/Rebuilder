<?php
$sub_menu = "100290";
require_once './_common.php';

check_demo();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

check_admin_token();

// // 이전 메뉴정보 삭제
$sql = " delete from {$g5['menu_table']} ";
sql_query($sql);

$_POST = array_map_deep('trim', $_POST);

$count = isset($_POST['code']) && is_array($_POST['code']) ? count($_POST['code']) : 0;
$seen = array();

for ($i = 0; $i < $count; $i++) {

    $raw_link = isset($_POST['me_link'][$i]) ? (string)$_POST['me_link'][$i] : '';
    $raw_link = preg_replace('/[ ]{2,}|[\t]/', '', $raw_link);

    if (preg_match('/^javascript/i', preg_replace('/[ ]{1,}|[\t]/', '', $raw_link))) {
        $raw_link = G5_URL;
    }

    $raw_link = clean_xss_tags(clean_xss_attributes($raw_link, 1), 1);
    $raw_link = html_purifier($raw_link);

    $code    = isset($_POST['code'][$i]) ? (string)$_POST['code'][$i] : '';
    $me_name = isset($_POST['me_name'][$i]) ? (string)$_POST['me_name'][$i] : '';

    $code = strip_tags($code);
    $code = preg_replace('/[^0-9a-zA-Z]/', '', $code);

    $me_name = strip_tags($me_name);

    $me_link = (preg_match('/^javascript/i', $raw_link) || preg_match('/script:/i', $raw_link)) ? G5_URL : strip_tags(clean_xss_attributes($raw_link));

    if (!$code || !$me_name || !$me_link) {
        continue;
    }

    // // 2/4/6 자리만 허용(1/2/3차)
    $len = strlen($code);
    if (!($len === 2 || $len === 4 || $len === 6)) {
        continue;
    }

    // // 중복 방지
    if (isset($seen[$code])) {
        continue;
    }
    $seen[$code] = 1;

    // 메뉴 등록(코드를 재생성하지 않고 그대로 저장)
    $sql = " insert into {$g5['menu_table']}
                set me_code         = '" . sql_real_escape_string($code) . "',
                    me_name         = '" . sql_real_escape_string($me_name) . "',
                    me_link         = '" . sql_real_escape_string($me_link) . "',
                    me_target       = '" . sql_real_escape_string(strip_tags(isset($_POST['me_target'][$i]) ? $_POST['me_target'][$i] : 'self')) . "',
                    me_order        = '" . sql_real_escape_string(strip_tags(isset($_POST['me_order'][$i]) ? $_POST['me_order'][$i] : '0')) . "',
                    me_use          = '" . sql_real_escape_string(strip_tags(isset($_POST['me_use'][$i]) ? $_POST['me_use'][$i] : '0')) . "',
                    me_mobile_use   = '" . sql_real_escape_string(strip_tags(isset($_POST['me_mobile_use'][$i]) ? $_POST['me_mobile_use'][$i] : '0')) . "',
                    me_level        = '" . sql_real_escape_string(strip_tags(isset($_POST['me_level'][$i]) ? $_POST['me_level'][$i] : '1')) . "',
                    me_level_opt    = '" . sql_real_escape_string(strip_tags(isset($_POST['me_level_opt'][$i]) ? $_POST['me_level_opt'][$i] : '1')) . "' ";
    sql_query($sql);
}

run_event('admin_menu_list_update');

goto_url('./menu_list.php');
