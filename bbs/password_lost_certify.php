<?php
include_once('./_common.php');
if(function_exists('check_mail_bot')){ check_mail_bot($_SERVER['REMOTE_ADDR']); }

// 쿠키로 실제 사용자 구분
if (!isset($_COOKIE['pwd_reset_real_user'])) {
    setcookie('pwd_reset_real_user', '1', time() + 600, '/');
    ?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>비밀번호 재설정</title></head>
<body>
<script>
// 자동으로 페이지 새로고침 (실제 사용자용)
setTimeout(function(){ location.reload(); }, 100);
</script>
<h1>비밀번호 재설정 중..</h1>
</body>
</html>
<?php
    exit;
}

run_event('password_lost_certify_before');

$mb_no = isset($_GET['mb_no']) ? preg_replace('#[^0-9]#', '', trim($_GET['mb_no'])) : 0;
$mb_nonce = isset($_GET['mb_nonce']) ? trim($_GET['mb_nonce']) : '';

$sql = " select mb_id, mb_password, mb_lost_certify from {$g5['member_table']} where mb_no = '$mb_no' ";
$mb  = sql_fetch($sql);

if (strlen($mb['mb_lost_certify']) < 33)
    die("Error");

$stored_nonce = substr($mb['mb_lost_certify'], 0, 32);
$new_password_hash = substr($mb['mb_lost_certify'], 33);

if ($mb_nonce !== $stored_nonce) {
    die("Error");
}

if ($mb['mb_password'] === $new_password_hash) {
    sql_query(" update {$g5['member_table']} set mb_lost_certify = '' where mb_no = '$mb_no' ");
    setcookie('pwd_reset_real_user', '', time() - 3600, '/');
    alert('비밀번호가 변경 되었습니다.\\n변경된 비밀번호로 로그인 하시기 바랍니다.', G5_BBS_URL.'/login.php');
}

sql_query(" update {$g5['member_table']} set mb_lost_certify = '', mb_password = '$new_password_hash' where mb_no = '$mb_no' ");
run_event('password_lost_certify_after', $mb, $mb_nonce);
setcookie('pwd_reset_real_user', '', time() - 3600, '/');
alert('비밀번호가 변경 되었습니다.\\n변경된 비밀번호로 로그인 하시기 바랍니다.', G5_BBS_URL.'/login.php');
