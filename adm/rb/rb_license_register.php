<?php
$sub_menu = '000000';
include_once('./_common.php');
include_once('./rb_license.lib.php');

$is_ajax = isset($_POST['ajax']) && (string) $_POST['ajax'] === '1';

function rb_license_register_response($success, $message, $data = array())
{
    if (!$success) {
        set_session('ss_rb_db_update_result', array(
            'success' => false,
            'message' => (string) $message,
        ));
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => (bool) $success,
        'message' => (string) $message,
        'data' => is_array($data) ? $data : array(),
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') {
    if ($is_ajax) {
        rb_license_register_response(false, '최고관리자만 접근 가능합니다.');
    }
    alert('최고관리자만 접근 가능합니다.');
}
check_demo();

if ($is_ajax) {
    $session_token = get_session('ss_admin_token');
    set_session('ss_admin_token', '');
    $request_token = isset($_POST['token']) ? (string) $_POST['token'] : '';
    if (!$session_token || !$request_token || !hash_equals((string) $session_token, $request_token)) {
        rb_license_register_response(false, '올바른 방법으로 이용해 주십시오.');
    }
} else {
    check_admin_token();
}

$install_token = isset($_POST['install_token']) ? trim((string) $_POST['install_token']) : '';
$result = rb_license_register_token($install_token);
if (empty($result['success'])) {
    if ($is_ajax) {
        rb_license_register_response(false, isset($result['message']) ? $result['message'] : '설치 토큰을 등록하지 못했습니다.');
    }
    alert(isset($result['message']) ? $result['message'] : '설치 토큰을 등록하지 못했습니다.', './rb_form.php');
}

if ($is_ajax) {
    rb_license_register_response(true, '설치 토큰 등록이 완료되었습니다.', isset($result['data']) ? $result['data'] : array());
}

alert("토큰 등록이 완료되었습니다.\n빌더 설치 및 업데이트를 진행합니다.", './rb_db_update.php');
