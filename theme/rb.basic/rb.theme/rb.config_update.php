<?php
include_once '../../../common.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($is_admin) || !$is_admin) {
    echo json_encode(array(
        'status' => 'error',
        'msg' => '권한이 없습니다.'
    ));
    exit;
}

$theme_config_tables = 'rb_theme';

// 값 수집 & 정리
$theme_key = sql_real_escape_string((string)$config['cf_theme']);

$use1_yn = isset($_POST['use1_yn']) ? (int)$_POST['use1_yn'] : 0;
$use2_yn = isset($_POST['use2_yn']) ? (int)$_POST['use2_yn'] : 0;
$use3_yn = isset($_POST['use3_yn']) ? (int)$_POST['use3_yn'] : 0;
$use4_yn = isset($_POST['use4_yn']) ? (int)$_POST['use4_yn'] : 0;
$use5_yn = isset($_POST['use5_yn']) ? (int)$_POST['use5_yn'] : 0;

$is_shop = isset($_POST['is_shop']) ? (int)$_POST['is_shop'] : 0;
$carousel_use   = isset($_POST['carousel_use']) ? (int)$_POST['carousel_use'] : 0;
$carousel_type  = isset($_POST['carousel_type']) ? sql_real_escape_string((string)$_POST['carousel_type']) : 'fade';
$carousel_time  = isset($_POST['carousel_time']) ? (int)$_POST['carousel_time'] : 4000;
$carousel_speed = isset($_POST['carousel_speed']) ? (int)$_POST['carousel_speed'] : 600;


// 기존 데이터 존재 확인
$row = sql_fetch("SELECT theme_key FROM {$theme_config_tables} WHERE theme_key = '{$theme_key}'");

// INSERT or UPDATE
if ($row) {

    $sql = "
        UPDATE {$theme_config_tables} SET
            use1_yn = '{$use1_yn}',
            use2_yn = '{$use2_yn}',
            use3_yn = '{$use3_yn}',
            use4_yn = '{$use4_yn}',
            use5_yn = '{$use5_yn}',

            carousel_use   = IF('{$is_shop}'=1, carousel_use,   '{$carousel_use}'),
            carousel_type  = IF('{$is_shop}'=1, carousel_type,  '{$carousel_type}'),
            carousel_time  = IF('{$is_shop}'=1, carousel_time,  '{$carousel_time}'),
            carousel_speed = IF('{$is_shop}'=1, carousel_speed, '{$carousel_speed}'),
            carousel_use_shop   = IF('{$is_shop}'=1, '{$carousel_use}',   carousel_use_shop),
            carousel_type_shop  = IF('{$is_shop}'=1, '{$carousel_type}',  carousel_type_shop),
            carousel_time_shop  = IF('{$is_shop}'=1, '{$carousel_time}',  carousel_time_shop),
            carousel_speed_shop = IF('{$is_shop}'=1, '{$carousel_speed}', carousel_speed_shop)

        WHERE theme_key = '{$theme_key}'
    ";
    sql_query($sql);

} else {

    $sql = "
        INSERT INTO {$table} SET
            theme_key = '{$theme_key}',

            use1_yn = '{$use1_yn}',
            use2_yn = '{$use2_yn}',
            use3_yn = '{$use3_yn}',
            use4_yn = '{$use4_yn}',
            use5_yn = '{$use5_yn}',

            carousel_use   = IF('{$is_shop}'=1, carousel_use,   '{$carousel_use}'),
            carousel_type  = IF('{$is_shop}'=1, carousel_type,  '{$carousel_type}'),
            carousel_time  = IF('{$is_shop}'=1, carousel_time,  '{$carousel_time}'),
            carousel_speed = IF('{$is_shop}'=1, carousel_speed, '{$carousel_speed}'),
            carousel_use_shop   = IF('{$is_shop}'=1, '{$carousel_use}',   carousel_use_shop),
            carousel_type_shop  = IF('{$is_shop}'=1, '{$carousel_type}',  carousel_type_shop),
            carousel_time_shop  = IF('{$is_shop}'=1, '{$carousel_time}',  carousel_time_shop),
            carousel_speed_shop = IF('{$is_shop}'=1, '{$carousel_speed}', carousel_speed_shop)
    ";
    sql_query($sql);
}


// 결과
echo json_encode(array(
    'status' => 'success'
));
exit;
