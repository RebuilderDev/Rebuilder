<?php
$sub_menu = '000000';
include_once('./_common.php');
include_once('./rb_license.lib.php');

auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') {
    alert('최고관리자만 접근 가능합니다.');
}
check_demo();
check_admin_token();

$install_token = isset($_POST['install_token']) ? trim((string) $_POST['install_token']) : '';
$result = rb_license_register_token($install_token);
if (empty($result['success'])) {
    alert(isset($result['message']) ? $result['message'] : '설치 토큰을 등록하지 못했습니다.', './rb_form.php');
}

alert('설치 토큰이 등록되었습니다. 이제 DB 설치 및 업데이트를 진행해 주세요.', './rb_db_update.php');

