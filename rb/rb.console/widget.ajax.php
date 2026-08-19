<?php
include_once('../../common.php');
include_once(G5_PATH.'/rb/rb.console/console.lib.php');

header('Content-Type: application/json; charset=utf-8');

function rb_console_widget_response($result, $data = array(), $status = 200)
{
    if (function_exists('http_response_code')) http_response_code((int) $status);
    echo json_encode(array_merge(array('result'=>$result), (array) $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
    rb_console_widget_response('error', array('message'=>'허용되지 않은 요청입니다.'), 405);
}
if (empty($member['mb_id']) || !rb_console_can('console.access')) {
    rb_console_widget_response('error', array('message'=>'비즈니스 콘솔 이용 권한이 없습니다.'), 403);
}
$console_config = rb_console_config();
if (empty($console_config['bc_enabled']) && $is_admin !== 'super') {
    rb_console_widget_response('error', array('message'=>'현재 비즈니스 콘솔을 이용할 수 없습니다.'), 403);
}
if (!rb_console_check_token(false)) {
    rb_console_widget_response('error', array('message'=>'요청이 만료되었습니다. 화면을 새로고침해 주세요.'), 403);
}

$layout_json = isset($_POST['layout']) ? (string) $_POST['layout'] : '';
$layout = json_decode($layout_json, true);
if (!is_array($layout) && $layout_json !== '') {
    // common.php의 G5_ESCAPE_FUNCTION 처리로 JSON 따옴표에 추가된 역슬래시를 복원한다.
    $layout = json_decode(stripslashes($layout_json), true);
}
if (!is_array($layout)) {
    rb_console_widget_response('error', array('message'=>'위젯 설정을 확인할 수 없습니다.'), 422);
}
$layout = rb_console_widget_layout_normalize($layout);
if (!rb_console_widget_layout_save($member['mb_id'], $layout)) {
    rb_console_widget_response('error', array('message'=>'위젯 설정을 저장하지 못했습니다.'), 500);
}

rb_console_widget_response('ok', array('layout'=>$layout));
