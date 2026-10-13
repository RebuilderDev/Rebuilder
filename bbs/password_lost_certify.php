<?php
include_once('./_common.php');
if (is_file(G5_LIB_PATH.'/abuse_rate.lib.php')) include_once(G5_LIB_PATH.'/abuse_rate.lib.php');
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

// 읽어 둔 인증값과 일치할 때만 비밀번호 변경과 링크 소모를 한 번에 처리한다.
if (function_exists('g5_password_lost_compare_update')) {
    $password_changed = g5_password_lost_compare_update($mb_no, $mb['mb_lost_certify'], '', $new_password_hash);
} else {
    // 구 코어에서도 동시 요청이 새 인증값을 덮어쓰지 않도록 같은 조건을 사용한다.
    $expected_certify = sql_real_escape_string($mb['mb_lost_certify']);
    $password_sql = sql_real_escape_string($new_password_hash);
    $password_changed = sql_query(" update {$g5['member_table']} set mb_lost_certify = '', mb_password = '$password_sql' where mb_no = '$mb_no' and BINARY mb_lost_certify = '$expected_certify' ", false);
    if ($password_changed) {
        if (function_exists('get_sql_affected_rows')) {
            $password_affected_rows = get_sql_affected_rows();
        } else {
            // 해당 함수가 없는 구 코어에서도 기존 DB 연결로 갱신 결과를 확인한다.
            $password_result = sql_fetch('SELECT ROW_COUNT() AS affected', false);
            $password_affected_rows = isset($password_result['affected']) ? $password_result['affected'] : 0;
        }
        $password_changed = (int)$password_affected_rows === 1;
    }
}
if (!$password_changed) die('Error');

run_event('password_lost_certify_after', $mb, $mb_nonce);
setcookie('pwd_reset_real_user', '', time() - 3600, '/');
alert('비밀번호가 변경 되었습니다.\\n변경된 비밀번호로 로그인 하시기 바랍니다.', G5_BBS_URL.'/login.php', false);
