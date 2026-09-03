<?php
include_once('../../common.php');
header("Content-Type: text/css; charset=utf-8");

$rb_header_set_raw = isset($_GET['rb_header_set']) ? (string) $_GET['rb_header_set'] : (string) $rb_core['header'];
$rb_header_set = preg_replace('/[^a-zA-Z0-9_-]/', '', $rb_header_set_raw);
if ($rb_header_set === '') {
    $rb_header_set = 'co_header_ffffff';
}

$rb_header_code_raw = isset($_GET['rb_header_code']) ? (string) $_GET['rb_header_code'] : (string) $rb_config['co_header'];
$rb_header_info = function_exists('rb_header_color_info')
    ? rb_header_color_info($rb_header_code_raw)
    : array('color' => '#ffffff', 'text' => 'black');
$rb_header_code = $rb_header_info['color'];

$rb_header_mode = isset($_GET['rb_header_txt']) ? strtolower((string) $_GET['rb_header_txt']) : '';
if ($rb_header_mode !== 'black' && $rb_header_mode !== 'white') {
    $rb_header_mode = $rb_header_info['text'];
}

$rb_mobile_menu_icon_color_disable = isset($_GET['rb_mobile_menu_icon_color_disable'])
    ? (int) $_GET['rb_mobile_menu_icon_color_disable']
    : (!empty($rb_builder['bu_mobile_menu_icon_color_disable']) ? 1 : 0);

if ($rb_header_mode === 'black') {
    $rb_rgba_border = "border-color:rgba(0,0,0,0.1);";
    $rb_rgba_bg = "background-color:rgba(0,0,0,0.05);";
    $rb_header_txt = $rb_config['co_color'];
    $rb_header_search_h = "color:rgba(0,0,0,0.6);";
    $rb_header_a = "";
    $arr_w = "";
} else {
    $rb_rgba_border = "border-color:rgba(255,255,255,0.1);";
    $rb_rgba_bg = "background-color:rgba(255,255,255,1);";
    $rb_header_txt = "#fff";
    $rb_header_search_h = "";
    $rb_header_a = "#fff";
    $arr_w = "background-image: url(../rb.config/image/arr_down_w.svg)";
}
$rb_mobile_menu_icon_color = $rb_mobile_menu_icon_color_disable ? '#000' : $rb_header_txt;
?>


.<?php echo $rb_header_set ?> #header {background-color: <?php echo $rb_header_code ?>; border-bottom: 1px solid <?php echo $rb_header_code ?>;}
.<?php echo $rb_header_set ?> #header .rows_gnb_wrap {<?php echo $rb_rgba_border ?>}
.<?php echo $rb_header_set ?> #header .tog_wrap button svg path:not([fill="none"]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg circle:not([fill="none"]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg ellipse:not([fill="none"]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg rect:not([fill="none"]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg polygon:not([fill="none"]) {fill:<?php echo $rb_mobile_menu_icon_color ?>;}
.<?php echo $rb_header_set ?> #header .tog_wrap button svg [fill="none"] path:not([fill]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg [fill="none"] circle:not([fill]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg [fill="none"] ellipse:not([fill]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg [fill="none"] rect:not([fill]),
.<?php echo $rb_header_set ?> #header .tog_wrap button svg [fill="none"] polygon:not([fill]) {fill:none;}
.<?php echo $rb_header_set ?> #header .gnb_wrap nav a {color:<?php echo $rb_header_a ?>;}
.<?php echo $rb_header_set ?> #header .gnb_wrap nav a:hover{color:<?php echo $rb_header_txt ?>;}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_wrap .member_info_wrap span {color:<?php echo $rb_header_txt ?>;}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_wrap .member_info_wrap {color:<?php echo $rb_header_txt ?>;}
.<?php echo $rb_header_set ?> #header .my_btn_wrap .btn_round {<?php echo $rb_rgba_border ?>;}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_wrap .member_info_wrap a {color:<?php echo $rb_header_a ?>}
.<?php echo $rb_header_set ?> #header .logo_wrap span {color:<?php echo $rb_header_txt ?>;}
.<?php echo $rb_header_set ?> #header .search_top_wrap_inner button svg path {}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_wrap .qm_wrap a svg path {fill:<?php echo $rb_header_a ?>}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_wrap .qm_wrap button svg path {fill:<?php echo $rb_header_a ?>}
.<?php echo $rb_header_set ?> #header #rb_memo_top_btn svg g[fill="none"] > path:first-child:not(:last-child),
.<?php echo $rb_header_set ?> #header #notification_top_btn svg g[fill="none"] > path:first-child:not(:last-child) {fill:none !important;}
.<?php echo $rb_header_set ?> #header .gnb_all_menu {<?php echo $arr_w ?>}
.<?php echo $rb_header_set ?> #header .gnb_wrap .snb_q_wrap a {color:<?php echo $rb_header_a ?>}

.<?php echo $rb_header_set ?> .co_header_ex_dd {background-color:<?php echo $rb_header_code ?>; color:<?php echo $rb_header_txt ?>; border:1px solid rgba(0,0,0,0.1);}
.<?php echo $rb_header_set ?> .co_header_ex_dd svg path {fill:<?php echo $rb_header_a ?>;}
.<?php echo $rb_header_set ?> .search_top_wrap input {<?php echo $rb_rgba_bg ?>}
.<?php echo $rb_header_set ?> .search_top_wrap input::placeholder {<?php echo $rb_header_search_h ?>}
