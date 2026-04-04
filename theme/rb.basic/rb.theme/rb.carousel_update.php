<?php
include_once '../../../common.php';

// btn_margin 컬럼 없으면 추가
$chk = sql_query("SHOW COLUMNS FROM `rb_theme_carousel` LIKE 'btn_margin'");
if (!sql_fetch_array($chk)) {
    sql_query("ALTER TABLE `rb_theme_carousel` ADD COLUMN `btn_margin` INT NOT NULL DEFAULT 0 AFTER `btn_padding_lr`");
}

// 관리자 권한 체크
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$mode = isset($_POST['mode']) ? $_POST['mode'] : 'insert';
$cf_theme = isset($_POST['cf_theme']) ? trim($_POST['cf_theme']) : '';

// 테마 값 검증
if (empty($cf_theme)) {
    echo json_encode(['success' => false, 'message' => '테마 정보가 없습니다.']);
    exit;
}

// 삭제
if ($mode == 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID가 유효하지 않습니다.']);
        exit;
    }

    // 기존 이미지 삭제
    $row = sql_fetch("SELECT image_path FROM rb_theme_carousel WHERE id = '$id' AND cf_theme = '$cf_theme'");
    if ($row && $row['image_path']) {
        $img_file = G5_DATA_PATH . '/'.$cf_theme.'/carousel_img/' . basename($row['image_path']);
        if (file_exists($img_file)) {
            @unlink($img_file);
        }
    }

    sql_query("DELETE FROM rb_theme_carousel WHERE id = '$id' AND cf_theme = '$cf_theme'");

    echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
    exit;
}

// 데이터 받기
$main_text = isset($_POST['main_text']) ? stripslashes($_POST['main_text']) : '';
$main_size = isset($_POST['main_size']) ? (int)$_POST['main_size'] : 20;
$main_color  = isset($_POST['main_color'])  ? $_POST['main_color']  : '#000000';
$main_align  = isset($_POST['main_align'])  ? $_POST['main_align']  : 'left';
$main_weight = isset($_POST['main_weight']) ? $_POST['main_weight'] : 'font-R';

$sub_text  = isset($_POST['sub_text'])  ? stripslashes($_POST['sub_text'])  : '';
$sub_size = isset($_POST['sub_size']) ? (int)$_POST['sub_size'] : 14;
$sub_color  = isset($_POST['sub_color'])  ? $_POST['sub_color']  : '#999999';
$sub_align  = isset($_POST['sub_align'])  ? $_POST['sub_align']  : 'left';
$sub_weight = isset($_POST['sub_weight']) ? $_POST['sub_weight'] : 'font-R';
$sub_margin = isset($_POST['sub_margin']) ? (int)$_POST['sub_margin'] : 10;

$btn_use  = isset($_POST['btn_use']) ? (int)$_POST['btn_use'] : 0;
$btn_text = ($btn_use === 1 && isset($_POST['btn_text'])) ? sql_real_escape_string(stripslashes(trim($_POST['btn_text']))) : '';

$btn_link = isset($_POST['btn_link']) ? trim($_POST['btn_link']) : '';
$btn_link_blank = isset($_POST['btn_link_blank']) ? (int)$_POST['btn_link_blank'] : 0;
$btn_size = isset($_POST['btn_size']) ? (int)$_POST['btn_size'] : 14;
$btn_radius = isset($_POST['btn_radius']) ? (int)$_POST['btn_radius'] : 4;
$btn_border = isset($_POST['btn_border']) ? (int)$_POST['btn_border'] : 1;
$btn_padding = isset($_POST['btn_padding']) ? (int)$_POST['btn_padding'] : 10;
$btn_padding_lr = isset($_POST['btn_padding_lr']) ? (int)$_POST['btn_padding_lr'] : 20;
$btn_bg_color = isset($_POST['btn_bg_color']) ? $_POST['btn_bg_color'] : '#000000';
$btn_text_color = isset($_POST['btn_text_color']) ? $_POST['btn_text_color'] : '#ffffff';
$btn_border_color = isset($_POST['btn_border_color']) ? $_POST['btn_border_color'] : '#000000';
$btn_svg = isset($_POST['btn_svg']) ? trim($_POST['btn_svg']) : '';
$btn_align = isset($_POST['btn_align']) ? $_POST['btn_align'] : 'left';
$btn_weight = isset($_POST['btn_weight']) ? $_POST['btn_weight'] : 'font-R';
$btn_margin = isset($_POST['btn_margin']) ? (int)$_POST['btn_margin'] : 0;

$carousel_type_mode = isset($_POST['carousel_type_mode']) ? $_POST['carousel_type_mode'] : 'community';
if (!in_array($carousel_type_mode, array('shop', 'community'))) {
    $carousel_type_mode = 'community';
}
$is_sub = (isset($_POST['is_sub']) && (int)$_POST['is_sub'] === 1) ? 1 : 0;


$image_path = '';

// 이미지 업로드 처리
if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['error'] == 0) {
    $upload_dir = G5_DATA_PATH . '/'.$cf_theme.'/carousel_img/';

    // 디렉토리 생성
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['carousel_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // 이미지 파일만 허용
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => '이미지 파일만 업로드 가능합니다.']);
        exit;
    }

    // 파일명 생성
    $filename = 'carousel_' . $cf_theme . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $image_path = $filename;
    }
}

// 수정
if ($mode == 'update') {
    $id = isset($_POST['carousel_id']) ? (int)$_POST['carousel_id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID가 유효하지 않습니다.']);
        exit;
    }

    // 이미지가 새로 업로드된 경우 기존 이미지 삭제
    if ($image_path) {
        $row = sql_fetch("SELECT image_path FROM rb_theme_carousel WHERE id = '$id' AND cf_theme = '$cf_theme'");
        if ($row && $row['image_path']) {
            $old_img = G5_DATA_PATH . '/'.$cf_theme.'/carousel_img/' . basename($row['image_path']);
            if (file_exists($old_img)) {
                @unlink($old_img);
            }
        }
    }
    if ($is_sub === 1) {
        sql_query("UPDATE rb_theme_carousel SET is_sub = 0 WHERE cf_theme = '" . sql_real_escape_string($cf_theme) . "' AND carousel_type_mode = '" . $carousel_type_mode . "' AND id != '$id'");
    }
    $sql = "UPDATE rb_theme_carousel SET
            main_text = '" . sql_real_escape_string($main_text) . "',
            main_size = '$main_size',
            main_color = '" . $main_color . "',
            main_align = '" . $main_align . "',
            main_weight = '" . $main_weight . "',
            sub_text  = '" . sql_real_escape_string($sub_text)  . "',
            sub_size = '$sub_size',
            sub_color = '" . $sub_color . "',
            sub_margin = '$sub_margin',
            sub_align = '" . $sub_align . "',
            sub_weight = '" . $sub_weight . "',
            btn_text = '" . sql_real_escape_string($btn_text) . "',
            btn_weight = '" . $btn_weight . "',
            btn_link = '" . sql_real_escape_string($btn_link) . "',
            btn_link_blank = '$btn_link_blank',
            btn_size = '$btn_size',
            btn_radius = '$btn_radius',
            btn_border = '$btn_border',
            btn_padding = '$btn_padding',
            btn_padding_lr = '$btn_padding_lr',
            btn_margin = '$btn_margin',
            btn_bg_color = '" . $btn_bg_color . "',
            btn_text_color = '" . $btn_text_color . "',
            btn_border_color = '" . $btn_border_color . "',
            btn_svg = '" . sql_real_escape_string($btn_svg) . "',
            btn_align = '" . $btn_align . "',
            is_sub = '$is_sub',
            carousel_type_mode = '" . $carousel_type_mode . "'";

    if ($image_path) {
        $sql .= ", image_path = '" . sql_real_escape_string($image_path) . "'";
    }

    $sql .= " WHERE id = '$id' AND cf_theme = '$cf_theme'";

    sql_query($sql);

    echo json_encode(['success' => true, 'message' => '수정되었습니다.']);
    exit;
}

// 추가
if ($mode == 'insert') {
    // 정렬 순서 계산
    $sort_row = sql_fetch("SELECT MAX(sort_order) as max_sort FROM rb_theme_carousel WHERE cf_theme = '$cf_theme'");
    $sort_order = $sort_row ? (int)$sort_row['max_sort'] + 1 : 1;

    if ($is_sub === 1) {
        sql_query("UPDATE rb_theme_carousel SET is_sub = 0 WHERE cf_theme = '" . sql_real_escape_string($cf_theme) . "' AND carousel_type_mode = '" . $carousel_type_mode . "'");
    }

    $sql = "INSERT INTO rb_theme_carousel SET
            cf_theme = '" . sql_real_escape_string($cf_theme) . "',
            main_text = '" . $main_text . "',
            main_size = '$main_size',
            main_color = '" . $main_color . "',
            main_align = '" . $main_align . "',
            main_weight = '" . $main_weight . "',
            sub_text = '" . $sub_text . "',
            sub_size = '$sub_size',
            sub_color = '" . $sub_color . "',
            sub_margin = '$sub_margin',
            sub_align = '" . $sub_align . "',
            sub_weight = '" . $sub_weight . "',
            btn_text = '" . sql_real_escape_string($btn_text) . "',
            btn_weight = '" . $btn_weight . "',
            btn_link = '" . sql_real_escape_string($btn_link) . "',
            btn_size = '$btn_size',
            btn_radius = '$btn_radius',
            btn_border = '$btn_border',
            btn_padding = '$btn_padding',
            btn_padding_lr = '$btn_padding_lr',
            btn_margin = '$btn_margin',
            btn_bg_color = '" . $btn_bg_color . "',
            btn_text_color = '" . $btn_text_color . "',
            btn_border_color = '" . $btn_border_color . "',
            btn_svg = '" . sql_real_escape_string($btn_svg) . "',
            btn_align = '" . $btn_align . "',
            carousel_type_mode = '" . $carousel_type_mode . "',
            is_sub = '$is_sub',
            image_path = '" . sql_real_escape_string($image_path) . "',
            sort_order = '$sort_order',
            reg_date = '".G5_TIME_YMDHIS."'";

    sql_query($sql);

    echo json_encode(['success' => true, 'message' => '등록 되었습니다.']);
    exit;
}

echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
?>
