<?php
$sub_menu = '000000';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

alert('빌더 2.2.7부터 기존 라이선스 키 재발급 기능은 사용하지 않습니다.', './rb_form.php');

