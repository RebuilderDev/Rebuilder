<?php
include_once('./_common.php');
//include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

if ($is_member) { alert("이미 로그인중입니다."); goto_url(G5_URL); }

if (function_exists('g5_is_cert_find_excluded') && isset($_POST['mb_id']) && is_string($_POST['mb_id'])
    && g5_is_cert_find_excluded($_POST['mb_id'])) {
    g5_clear_cert_find();
    alert('최고관리자 계정은 본인인증으로 비밀번호를 재설정할 수 없습니다.', G5_URL);
}

$ss_cert_mb_id = function_exists('g5_cert_find_member_id') ? g5_cert_find_member_id() : get_session('ss_cert_mb_id');
$ss_cert_mb_id = is_string($ss_cert_mb_id) ? trim($ss_cert_mb_id) : '';
if ($ss_cert_mb_id === '' || !isset($_POST['mb_id']) || $_POST['mb_id'] !== $ss_cert_mb_id) {
    alert('잘못된 접근입니다.', G5_URL);
}

if($config['cf_cert_find'] != 1) alert("본인인증을 이용하여 아이디/비밀번호 찾기를 할 수 없습니다. 관리자에게 문의 하십시오.");

$g5['title'] = '패스워드 변경';
include_once('./_head.sub.php');

$action_url = G5_HTTPS_BBS_URL."/password_reset_update.php";
if (function_exists('g5_get_password_reset_token')) {
    $password_reset_token = g5_get_password_reset_token($ss_cert_mb_id);
}
include_once($member_skin_path.'/password_reset.skin.php');

include_once('./_tail.sub.php');
