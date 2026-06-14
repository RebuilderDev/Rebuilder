<?php
$sub_menu = '000300';
include_once('./_common.php');

check_demo();

$w = isset($w) ? $w : (isset($_POST['w']) ? $_POST['w'] : (isset($_GET['w']) ? $_GET['w'] : ''));

if ($w == 'd') auth_check_menu($auth, $sub_menu, "d");
else auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

function rb_sql_escape($s) {
    if (function_exists('sql_real_escape_string')) return sql_real_escape_string($s);
    return addslashes($s);
}

// // 기존 이미지형 저장 폴더(유지)
$save_dir   = G5_DATA_PATH . "/banners";
// // 혹시 이전에 저장된 rb_display도 남아있을 수 있어 삭제/정리용으로만 유지
$alt_dir    = G5_DATA_PATH . "/rb_display";
// // 디자인형 배경 이미지 저장 폴더
$design_dir = G5_DATA_PATH . "/rb_display_design";

@mkdir($save_dir, G5_DIR_PERMISSION);
@chmod($save_dir, G5_DIR_PERMISSION);

@mkdir($alt_dir, G5_DIR_PERMISSION);
@chmod($alt_dir, G5_DIR_PERMISSION);

$bn_id = isset($bn_id) ? (int)$bn_id : (isset($_POST['bn_id']) ? (int)$_POST['bn_id'] : 0);

$bn_bimg_del = isset($bn_bimg_del) ? $bn_bimg_del : (isset($_POST['bn_bimg_del']) ? $_POST['bn_bimg_del'] : null);

// 기존 디자인형 배너는 새 이미지를 업로드하기 전까지 출력 상태를 유지한다.
$existing_bn_type = 'image';
if ($w === 'u' && $bn_id > 0) {
    $existing_banner = sql_fetch("SELECT bn_type FROM rb_banner WHERE bn_id = '" . (int)$bn_id . "' LIMIT 1");
    if (isset($existing_banner['bn_type']) && $existing_banner['bn_type'] === 'design') {
        $existing_bn_type = 'design';
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

$bn_device     = isset($bn_device) ? $bn_device : (isset($_POST['bn_device']) ? $_POST['bn_device'] : '');
$bn_border     = isset($bn_border) ? $bn_border : (isset($_POST['bn_border']) ? $_POST['bn_border'] : '0');
$bn_radius     = isset($bn_radius) ? $bn_radius : (isset($_POST['bn_radius']) ? $_POST['bn_radius'] : '0');
$bn_ad_ico     = isset($bn_ad_ico) ? $bn_ad_ico : (isset($_POST['bn_ad_ico']) ? $_POST['bn_ad_ico'] : '0');
$bn_new_win    = isset($bn_new_win) ? $bn_new_win : (isset($_POST['bn_new_win']) ? $_POST['bn_new_win'] : '0');
$bn_begin_time = isset($bn_begin_time) ? $bn_begin_time : (isset($_POST['bn_begin_time']) ? $_POST['bn_begin_time'] : '');
$bn_end_time   = isset($bn_end_time) ? $bn_end_time : (isset($_POST['bn_end_time']) ? $_POST['bn_end_time'] : '');
$bn_order      = isset($bn_order) ? $bn_order : (isset($_POST['bn_order']) ? $_POST['bn_order'] : '0');

// // 파일 핸들
$bn_bimg_tmp  = isset($_FILES['bn_bimg']['tmp_name']) ? $_FILES['bn_bimg']['tmp_name'] : null;
$bn_bimg_name = isset($_FILES['bn_bimg']['name']) ? $_FILES['bn_bimg']['name'] : null;

$bn_type = ($existing_bn_type === 'design' && !$bn_bimg_name) ? 'design' : 'image';


// // 이미지형 삭제 체크(기존 저장부 유지 + rb_display에도 남아있을 수 있으니 같이 삭제)
if ($bn_bimg_del && $bn_id > 0) {
    @unlink($save_dir . "/{$bn_id}");
    @unlink($alt_dir . "/{$bn_id}");
}

// // 이미지 파일 검증(업로드된 경우에만)
function rb_is_valid_image_upload($tmp, $name) {
    if (!$tmp || !$name) return true;
    if (!preg_match('/\.(gif|jpe?g|bmp|png)$/i', (string)$name)) return false;
    $timg = @getimagesize($tmp);
    if ($timg === false || !isset($timg[2]) || $timg[2] < 1 || $timg[2] > 16) return false;
    return true;
}

if (!rb_is_valid_image_upload($bn_bimg_tmp, $bn_bimg_name)) alert("이미지 파일만 업로드 할 수 있습니다.");

// 신규 등록은 이미지형만 지원
if ($w == "") {
    if (!$bn_bimg_name) alert('배너 이미지를 업로드 하세요.');

    $now = G5_TIME_YMDHIS;

    sql_query("ALTER TABLE rb_banner AUTO_INCREMENT=1");

    $sql = "INSERT INTO rb_banner
                SET bn_alt        = '" . rb_sql_escape($bn_alt) . "',
                    bn_url        = '" . rb_sql_escape($bn_url) . "',
                    bn_device     = '" . rb_sql_escape($bn_device) . "',
                    bn_position   = '" . rb_sql_escape($bn_position) . "',
                    bn_border     = '" . rb_sql_escape($bn_border) . "',
                    bn_radius     = '" . rb_sql_escape($bn_radius) . "',
                    bn_ad_ico     = '" . rb_sql_escape($bn_ad_ico) . "',
                    bn_new_win    = '" . rb_sql_escape($bn_new_win) . "',
                    bn_begin_time = '" . rb_sql_escape($bn_begin_time) . "',
                    bn_end_time   = '" . rb_sql_escape($bn_end_time) . "',
                    bn_time       = '" . rb_sql_escape($now) . "',
                    bn_hit        = '0',
                    bn_order      = '" . rb_sql_escape($bn_order) . "',
                    bn_type       = '" . rb_sql_escape($bn_type) . "'";
    sql_query($sql);

    $bn_id = sql_insert_id();

} elseif ($w == "u") {

    $sql = "UPDATE rb_banner
                SET bn_alt        = '" . rb_sql_escape($bn_alt) . "',
                    bn_url        = '" . rb_sql_escape($bn_url) . "',
                    bn_device     = '" . rb_sql_escape($bn_device) . "',
                    bn_position   = '" . rb_sql_escape($bn_position) . "',
                    bn_border     = '" . rb_sql_escape($bn_border) . "',
                    bn_radius     = '" . rb_sql_escape($bn_radius) . "',
                    bn_ad_ico     = '" . rb_sql_escape($bn_ad_ico) . "',
                    bn_new_win    = '" . rb_sql_escape($bn_new_win) . "',
                    bn_begin_time = '" . rb_sql_escape($bn_begin_time) . "',
                    bn_end_time   = '" . rb_sql_escape($bn_end_time) . "',
                    bn_order      = '" . rb_sql_escape($bn_order) . "',
                    bn_type       = '" . rb_sql_escape($bn_type) . "'
              WHERE bn_id = '" . (int)$bn_id . "'";
    sql_query($sql);

} elseif ($w == "d") {

    @unlink($save_dir . "/{$bn_id}");
    @unlink($alt_dir . "/{$bn_id}");
    @unlink($design_dir . "/{$bn_id}");

    $sql = "DELETE FROM rb_banner WHERE bn_id = " . (int)$bn_id;
    sql_query($sql);
}

// 이미지 업로드 처리
if ($w == "" || $w == "u") {

    // 이미지형 배너 이미지 저장(기존 방식 유지: data/banners/{id})
    if ($bn_type === 'image' && isset($_FILES['bn_bimg']['name']) && $_FILES['bn_bimg']['name']) {
        rb_upload_files($_FILES['bn_bimg']['tmp_name'], $bn_id, $save_dir);
    }

    goto_url("./banner_form.php?w=u&amp;bn_id={$bn_id}");
} else {
    goto_url("./banner_list.php");
}
?>
