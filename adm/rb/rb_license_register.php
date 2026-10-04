<?php
$sub_menu = '000000';
include_once('./_common.php');
include_once('./rb_license.lib.php');

$is_ajax = isset($_POST['ajax']) && (string) $_POST['ajax'] === '1';
$rb_license_ajax_ob_level = ob_get_level();
if ($is_ajax) {
    ob_start();
    rb_license_ajax_guard('인증 토큰 등록', $rb_license_ajax_ob_level);
}

function rb_license_register_response($success, $message, $data = array())
{
    global $rb_license_ajax_ob_level;

    if (!$success) {
        set_session('ss_rb_db_update_result', array(
            'success' => false,
            'message' => (string) $message,
        ));
    }
    while (ob_get_level() > $rb_license_ajax_ob_level) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => (bool) $success,
        'message' => (string) $message,
        'data' => is_array($data) ? $data : array(),
    ), JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
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
        rb_license_register_response(false, '관리자 요청 인증이 만료되었거나 일치하지 않아 토큰 등록이 거절되었습니다. [admin_token_mismatch]');
    }
} else {
    check_admin_token();
}

$install_token = isset($_POST['install_token']) ? trim((string) $_POST['install_token']) : '';
$registration_mode = isset($_POST['registration_mode']) && $_POST['registration_mode'] === 'move' ? 'move' : 'clone';
try {
    $result = rb_license_register_token($install_token, $registration_mode);
} catch (Throwable $error) {
    $result = rb_license_exception_result($error, '인증 토큰 등록');
}
if (empty($result['success'])) {
    if ($is_ajax) {
        rb_license_register_response(false, isset($result['message']) ? $result['message'] : '인증 토큰을 등록하지 못했습니다.');
    }
    alert(isset($result['message']) ? $result['message'] : '인증 토큰을 등록하지 못했습니다.', './rb_form.php');
}

if ($is_ajax) {
    rb_license_register_response(true, '인증 토큰 등록이 완료되었습니다.', isset($result['data']) ? $result['data'] : array());
}

alert("토큰 등록이 완료되었습니다.\n빌더 설치 및 업데이트를 진행합니다.", './rb_db_update.php');
