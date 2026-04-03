<?php
include_once '../../../common.php';

// 관리자 권한 체크
if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

if (isset($_POST['get_sub']) && (int)$_POST['get_sub'] === 1) {
    $cf_theme  = isset($_POST['cf_theme']) ? sql_real_escape_string($_POST['cf_theme']) : '';
    $type_mode = isset($_POST['carousel_type_mode']) ? sql_real_escape_string($_POST['carousel_type_mode']) : 'community';
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

    $where = "cf_theme = '$cf_theme' AND carousel_type_mode = '$type_mode' AND is_sub = 1";
    if ($exclude_id > 0) {
        $where .= " AND id != $exclude_id";
    }
    $sub_row = sql_fetch("SELECT * FROM rb_theme_carousel WHERE $where LIMIT 1");

    if ($sub_row) {
        $image_url = $sub_row['image_path'] ? G5_DATA_URL . '/' . $cf_theme . '/carousel_img/' . $sub_row['image_path'] : '';
        $sub_data = array(
            'id'             => (int)$sub_row['id'],
            'main_text'      => str_replace('<br>', "\n", html_entity_decode(stripslashes($sub_row['main_text']), ENT_QUOTES, 'UTF-8')),
            'main_size'      => $sub_row['main_size'],
            'main_color'     => $sub_row['main_color'],
            'main_align'     => $sub_row['main_align'],
            'main_weight'    => isset($sub_row['main_weight']) ? $sub_row['main_weight'] : 'font-R',
            'sub_text'       => str_replace('<br>', "\n", html_entity_decode(stripslashes($sub_row['sub_text']), ENT_QUOTES, 'UTF-8')),
            'sub_size'       => $sub_row['sub_size'],
            'sub_color'      => $sub_row['sub_color'],
            'sub_margin'     => $sub_row['sub_margin'],
            'sub_align'      => $sub_row['sub_align'],
            'sub_weight'     => isset($sub_row['sub_weight']) ? $sub_row['sub_weight'] : 'font-R',
            'btn_text'       => html_entity_decode(stripslashes($sub_row['btn_text']), ENT_QUOTES, 'UTF-8'),
            'btn_weight'     => isset($sub_row['btn_weight']) ? $sub_row['btn_weight'] : 'font-R',
            'btn_link'       => isset($sub_row['btn_link']) ? $sub_row['btn_link'] : '',
            'btn_size'       => $sub_row['btn_size'],
            'btn_radius'     => $sub_row['btn_radius'],
            'btn_border'     => $sub_row['btn_border'],
            'btn_padding'    => isset($sub_row['btn_padding']) ? $sub_row['btn_padding'] : 10,
            'btn_padding_lr' => isset($sub_row['btn_padding_lr']) ? (int)$sub_row['btn_padding_lr'] : 20,
            'btn_bg_color'   => $sub_row['btn_bg_color'],
            'btn_text_color' => isset($sub_row['btn_text_color']) ? $sub_row['btn_text_color'] : '#ffffff',
            'btn_border_color' => isset($sub_row['btn_border_color']) ? $sub_row['btn_border_color'] : '#000000',
            'btn_svg'        => $sub_row['btn_svg'],
            'btn_align'      => $sub_row['btn_align'],
            'btn_link_blank' => isset($sub_row['btn_link_blank']) ? (int)$sub_row['btn_link_blank'] : 0,
            'is_sub'         => 1,
            'image_url'      => $image_url,
        );
    } else {
        $sub_data = null;
    }

    echo json_encode(array('success' => true, 'sub_carousel' => $sub_data));
    exit;
}

if (isset($_POST['get_first']) && (int)$_POST['get_first'] === 1) {
    $cf_theme   = isset($_POST['cf_theme']) ? sql_real_escape_string($_POST['cf_theme']) : '';
    $type_mode  = isset($_POST['carousel_type_mode']) ? sql_real_escape_string($_POST['carousel_type_mode']) : 'community';
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;

    $where = "cf_theme = '$cf_theme' AND carousel_type_mode = '$type_mode'";
    if ($exclude_id > 0) {
        $where .= " AND id != $exclude_id";
    }
    $row = sql_fetch("SELECT * FROM rb_theme_carousel WHERE $where ORDER BY sort_order ASC, id ASC LIMIT 1");

    if ($row) {
        $image_url = $row['image_path'] ? G5_DATA_URL . '/' . $cf_theme . '/carousel_img/' . $row['image_path'] : '';
        $data = array(
            'id'               => (int)$row['id'],
            'main_text'        => str_replace('<br>', "\n", html_entity_decode(stripslashes($row['main_text']), ENT_QUOTES, 'UTF-8')),
            'main_size'        => $row['main_size'],
            'main_color'       => $row['main_color'],
            'main_align'       => $row['main_align'],
            'main_weight'      => isset($row['main_weight']) ? $row['main_weight'] : 'font-R',
            'sub_text'         => str_replace('<br>', "\n", html_entity_decode(stripslashes($row['sub_text']), ENT_QUOTES, 'UTF-8')),
            'sub_size'         => $row['sub_size'],
            'sub_color'        => $row['sub_color'],
            'sub_margin'       => $row['sub_margin'],
            'sub_align'        => $row['sub_align'],
            'sub_weight'       => isset($row['sub_weight']) ? $row['sub_weight'] : 'font-R',
            'btn_text'         => html_entity_decode(stripslashes($row['btn_text']), ENT_QUOTES, 'UTF-8'),
            'btn_weight'       => isset($row['btn_weight']) ? $row['btn_weight'] : 'font-R',
            'btn_link'         => isset($row['btn_link']) ? $row['btn_link'] : '',
            'btn_size'         => $row['btn_size'],
            'btn_radius'       => $row['btn_radius'],
            'btn_border'       => $row['btn_border'],
            'btn_padding'      => isset($row['btn_padding']) ? $row['btn_padding'] : 10,
            'btn_padding_lr'   => isset($row['btn_padding_lr']) ? (int)$row['btn_padding_lr'] : 20,
            'btn_bg_color'     => $row['btn_bg_color'],
            'btn_text_color'   => isset($row['btn_text_color']) ? $row['btn_text_color'] : '#ffffff',
            'btn_border_color' => isset($row['btn_border_color']) ? $row['btn_border_color'] : '#000000',
            'btn_svg'          => $row['btn_svg'],
            'btn_align'        => $row['btn_align'],
            'btn_link_blank'   => isset($row['btn_link_blank']) ? (int)$row['btn_link_blank'] : 0,
            'is_sub'           => (int)$row['is_sub'],
            'image_url'        => $image_url,
        );
    } else {
        $data = null;
    }

    echo json_encode(array('success' => true, 'carousel' => $data));
    exit;
}

if (isset($_POST['get_list']) && (int)$_POST['get_list'] === 1) {
    $cf_theme = isset($_POST['cf_theme']) ? sql_real_escape_string($_POST['cf_theme']) : '';
    $type_mode = isset($_POST['carousel_type_mode']) ? sql_real_escape_string($_POST['carousel_type_mode']) : 'community';
    $list = sql_query("SELECT * FROM rb_theme_carousel WHERE cf_theme = '$cf_theme' AND carousel_type_mode = '$type_mode' ORDER BY sort_order ASC, id ASC");
    $rows = array();
    while ($row = sql_fetch_array($list)) {
        $img_url = $row['image_path'] ? G5_DATA_URL . '/' . $cf_theme . '/carousel_img/' . $row['image_path'] : '';
        $rows[] = array(
            'id' => (int)$row['id'],
            'main_text' => strip_tags($row['main_text']),
            'sub_text' => strip_tags($row['sub_text']),
            'image_url' => $img_url,
            'carousel_type_mode' => $row['carousel_type_mode']
        );
    }
    echo json_encode(['success' => true, 'list' => $rows, 'count' => count($rows)]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID가 유효하지 않습니다.']);
    exit;
}

$row = sql_fetch("SELECT * FROM rb_theme_carousel WHERE id = '$id'");

if (!$row) {
    echo json_encode(['success' => false, 'message' => '데이터를 찾을 수 없습니다.']);
    exit;
}

$cf_theme = $row['cf_theme'];

// 이미지 URL 생성
$image_url = '';
if ($row['image_path']) {
    $image_url = G5_DATA_URL . '/'.$cf_theme.'/carousel_img/' . $row['image_path'];
}

$carousel = array(
    'id' => $row['id'],
    'main_text' => str_replace('<br>', "\n", html_entity_decode(stripslashes($row['main_text']), ENT_QUOTES, 'UTF-8')),
    'main_size' => $row['main_size'],
    'main_color' => $row['main_color'],
    'main_align' => $row['main_align'],
    'main_weight' => isset($row['main_weight']) ? $row['main_weight'] : 'font-R',
    'sub_text'  => str_replace('<br>', "\n", html_entity_decode(stripslashes($row['sub_text']),  ENT_QUOTES, 'UTF-8')),
    'sub_size' => $row['sub_size'],
    'sub_color' => $row['sub_color'],
    'sub_margin' => $row['sub_margin'],
    'sub_align' => $row['sub_align'],
    'sub_weight' => isset($row['sub_weight']) ? $row['sub_weight'] : 'font-R',
    'btn_text'  => html_entity_decode(stripslashes($row['btn_text']), ENT_QUOTES, 'UTF-8'),
    'btn_weight' => isset($row['btn_weight']) ? $row['btn_weight'] : 'font-R',
    'btn_link' => isset($row['btn_link']) ? $row['btn_link'] : '',
    'btn_size' => $row['btn_size'],
    'btn_radius' => $row['btn_radius'],
    'btn_border' => $row['btn_border'],
    'btn_padding' => isset($row['btn_padding']) ? $row['btn_padding'] : 10,
    'btn_padding_lr' => isset($row['btn_padding_lr']) ? (int)$row['btn_padding_lr'] : 20,
    'btn_bg_color' => $row['btn_bg_color'],
    'btn_text_color' => isset($row['btn_text_color']) ? $row['btn_text_color'] : '#ffffff',
    'btn_border_color' => isset($row['btn_border_color']) ? $row['btn_border_color'] : '#000000',
    'btn_svg' => $row['btn_svg'],
    'btn_align' => $row['btn_align'],
    'btn_link_blank' => isset($row['btn_link_blank']) ? (int)$row['btn_link_blank'] : 0,
    'carousel_type_mode' => isset($row['carousel_type_mode']) ? $row['carousel_type_mode'] : 'community',
    'is_sub' => isset($row['is_sub']) ? (int)$row['is_sub'] : 0,
    'image_url' => $image_url
);
echo json_encode(['success' => true, 'carousel' => $carousel]);
?>
