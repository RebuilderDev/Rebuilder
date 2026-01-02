<?php
$sub_menu = '000300';
include_once('./_common.php');

check_demo();

$w = isset($w) ? $w : (isset($_POST['w']) ? $_POST['w'] : (isset($_GET['w']) ? $_GET['w'] : ''));

if ($w == 'd')
    auth_check_menu($auth, $sub_menu, "d");
else
    auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

// // 신규 저장 폴더(권장)
$save_dir = G5_DATA_PATH . "/rb_display";

// // 기존 호환 폴더(예전 데이터 삭제/정리용)
$legacy_dir = G5_DATA_PATH . "/banners";

// // 폴더 생성
@mkdir($save_dir, G5_DIR_PERMISSION);
@chmod($save_dir, G5_DIR_PERMISSION);

// // 기존 폴더도 없을 수 있으니 생성은 유지(삭제 시 에러 방지)
@mkdir($legacy_dir, G5_DIR_PERMISSION);
@chmod($legacy_dir, G5_DIR_PERMISSION);

$bn_bimg      = isset($_FILES['bn_bimg']['tmp_name']) ? $_FILES['bn_bimg']['tmp_name'] : null;
$bn_bimg_name = isset($_FILES['bn_bimg']['name']) ? $_FILES['bn_bimg']['name'] : null;

$bn_id = isset($bn_id) ? (int)$bn_id : (isset($_POST['bn_id']) ? (int)$_POST['bn_id'] : 0);

$bn_bimg_del = isset($bn_bimg_del) ? $bn_bimg_del : (isset($_POST['bn_bimg_del']) ? $_POST['bn_bimg_del'] : null);

if ($bn_bimg_del) {
    // // 신규 폴더 우선 삭제
    @unlink($save_dir . "/{$bn_id}");
    // // 기존 폴더에도 남아있을 수 있으니 같이 삭제(호환)
    @unlink($legacy_dir . "/{$bn_id}");
}

// 파일이 이미지인지 체크합니다.
if ($bn_bimg || $bn_bimg_name) {
    if (!preg_match('/\.(gif|jpe?g|bmp|png)$/i', (string)$bn_bimg_name)) {
        alert("이미지 파일만 업로드 할 수 있습니다.");
    }

    $timg = @getimagesize($bn_bimg);
    if ($timg === false || !isset($timg[2]) || $timg[2] < 1 || $timg[2] > 16) {
        alert("이미지 파일만 업로드 할 수 있습니다.");
    }
}

$bn_url = isset($bn_url) ? clean_xss_tags($bn_url) : (isset($_POST['bn_url']) ? clean_xss_tags($_POST['bn_url']) : '');
$bn_alt_raw = isset($bn_alt) ? $bn_alt : (isset($_POST['bn_alt']) ? $_POST['bn_alt'] : '');
$bn_alt = ($bn_alt_raw !== '') ? (function_exists('clean_xss_attributes') ? clean_xss_attributes(strip_tags($bn_alt_raw)) : strip_tags($bn_alt_raw)) : '';

if (isset($_POST['bn_position_use']) && $_POST['bn_position_use']) {
    $bn_position = $_POST['bn_position_use'];
} else {
    $bn_position = isset($_POST['bn_position']) ? $_POST['bn_position'] : '';
}

// // 나머지 값들(기존 변수 사용 유지)
$bn_device     = isset($bn_device) ? $bn_device : (isset($_POST['bn_device']) ? $_POST['bn_device'] : '');
$bn_border     = isset($bn_border) ? $bn_border : (isset($_POST['bn_border']) ? $_POST['bn_border'] : '0');
$bn_radius     = isset($bn_radius) ? $bn_radius : (isset($_POST['bn_radius']) ? $_POST['bn_radius'] : '0');
$bn_ad_ico     = isset($bn_ad_ico) ? $bn_ad_ico : (isset($_POST['bn_ad_ico']) ? $_POST['bn_ad_ico'] : '0');
$bn_new_win    = isset($bn_new_win) ? $bn_new_win : (isset($_POST['bn_new_win']) ? $_POST['bn_new_win'] : '0');
$bn_begin_time = isset($bn_begin_time) ? $bn_begin_time : (isset($_POST['bn_begin_time']) ? $_POST['bn_begin_time'] : '');
$bn_end_time   = isset($bn_end_time) ? $bn_end_time : (isset($_POST['bn_end_time']) ? $_POST['bn_end_time'] : '');
$bn_order      = isset($bn_order) ? $bn_order : (isset($_POST['bn_order']) ? $_POST['bn_order'] : '0');

if ($w == "") {
    if (!$bn_bimg_name) alert('배너 이미지를 업로드 하세요.');

    sql_query("ALTER TABLE rb_banner AUTO_INCREMENT=1");

    $sql = "INSERT INTO rb_banner
                SET bn_alt        = '{$bn_alt}',
                    bn_url        = '{$bn_url}',
                    bn_device     = '{$bn_device}',
                    bn_position   = '{$bn_position}',
                    bn_border     = '{$bn_border}',
                    bn_radius     = '{$bn_radius}',
                    bn_ad_ico     = '{$bn_ad_ico}',
                    bn_new_win    = '{$bn_new_win}',
                    bn_begin_time = '{$bn_begin_time}',
                    bn_end_time   = '{$bn_end_time}',
                    bn_time       = '{$now}',
                    bn_hit        = '0',
                    bn_order      = '{$bn_order}'";
    sql_query($sql);

    $bn_id = sql_insert_id();
} elseif ($w == "u") {
    $sql = "UPDATE rb_banner
                SET bn_alt        = '{$bn_alt}',
                    bn_url        = '{$bn_url}',
                    bn_device     = '{$bn_device}',
                    bn_position   = '{$bn_position}',
                    bn_border     = '{$bn_border}',
                    bn_radius     = '{$bn_radius}',
                    bn_ad_ico     = '{$bn_ad_ico}',
                    bn_new_win    = '{$bn_new_win}',
                    bn_begin_time = '{$bn_begin_time}',
                    bn_end_time   = '{$bn_end_time}',
                    bn_order      = '{$bn_order}'
              WHERE bn_id = '{$bn_id}'";
    sql_query($sql);
} elseif ($w == "d") {
    // // 삭제: 신규/기존 둘 다 제거
    @unlink($save_dir . "/{$bn_id}");
    @unlink($legacy_dir . "/{$bn_id}");

    $sql = "DELETE FROM rb_banner WHERE bn_id = {$bn_id}";
    sql_query($sql);
}

if ($w == "" || $w == "u") {
    if (isset($_FILES['bn_bimg']['name']) && $_FILES['bn_bimg']['name']) {
        // // 업로드 저장 폴더를 rb_display로
        rb_upload_files($_FILES['bn_bimg']['tmp_name'], $bn_id, $save_dir);

        // // 혹시 기존 폴더에 남아있던 파일이 있으면 정리(선택)
        // @unlink($legacy_dir . "/{$bn_id}");
    }

    goto_url("./banner_form.php?w=u&amp;bn_id={$bn_id}");
} else {
    goto_url("./banner_list.php");
}
?>
