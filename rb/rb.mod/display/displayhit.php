<?php
include_once("../_common.php");

// // 파라미터 호환: did 우선, 없으면 bn_id
$did = isset($_GET['did']) ? (int)$_GET['did'] : 0;
if ($did <= 0) {
    $did = isset($_GET['bn_id']) ? (int)$_GET['bn_id'] : 0;
}

$bn_id = $did;

$sql = "SELECT bn_id, bn_url FROM rb_banner WHERE bn_id = '{$bn_id}'";
$row = sql_fetch($sql);

if (!isset($row['bn_id']) || !(int)$row['bn_id']) {
    alert('등록된 배너가 없습니다.', G5_URL);
}

// // 쿠키 호환: 기존 ck_bn_id / 신규 ck_disp_id 둘 다 체크
$ck_old = isset($_COOKIE['ck_bn_id']) ? (int)$_COOKIE['ck_bn_id'] : 0;
$ck_new = isset($_COOKIE['ck_disp_id']) ? (int)$_COOKIE['ck_disp_id'] : 0;

if ($ck_old !== $bn_id && $ck_new !== $bn_id) {
    $sql = "UPDATE rb_banner SET bn_hit = bn_hit + 1 WHERE bn_id = '{$bn_id}'";
    sql_query($sql);

    // // 하루 동안(기존/신규 쿠키 둘 다 심어 호환 유지)
    set_cookie("ck_bn_id", $bn_id, 60 * 60 * 24);
    set_cookie("ck_disp_id", $bn_id, 60 * 60 * 24);
}

$url = isset($row['bn_url']) ? clean_xss_tags($row['bn_url']) : G5_URL;

goto_url($url);
