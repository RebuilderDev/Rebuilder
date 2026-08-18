<?php
$sub_menu = '000630';
include_once('./_common.php');

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$sql_common = " FROM {$g5['member_table']}";
$sql_where = " WHERE (1) AND mb_id NOT IN ('{$config['cf_admin']}') ";
$levels = isset($_POST['mb_level']) && is_array($_POST['mb_level']) ? $_POST['mb_level'] : array();
$levels = array_values(array_unique(array_filter(array_map('intval', $levels), function($level) {
    return $level >= 2 && $level <= 10;
})));
$memo_content = isset($_POST['me_memo']) ? trim((string) $_POST['me_memo']) : '';

if (!$levels) {
    alert('수신그룹을 선택해 주세요.');
}
if ($memo_content === '') {
    alert('쪽지 내용을 입력해 주세요.');
}

$send_id = sql_escape_string($member['mb_id']);
$send_ip = sql_escape_string(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
$memo_content = sql_escape_string($memo_content);
$sent_count = 0;

foreach ($levels as $level) {

    $sql = "SELECT mb_id, mb_leave_date {$sql_common} {$sql_where} AND mb_level = '{$level}'";
    $result = sql_query($sql);

    while ($row = sql_fetch_array($result)) {
        if (!$row['mb_leave_date']) {

            $rows_me = sql_fetch("SELECT MAX(me_id) AS new_me_id FROM {$g5['memo_table']}");
            $me_id = $rows_me['new_me_id'] + 1;

            $recv_id = sql_escape_string($row['mb_id']);

            $sql_m1 = "INSERT INTO {$g5['memo_table']} SET me_id='{$me_id}', me_recv_mb_id='{$recv_id}', me_send_mb_id='{$send_id}', me_type='recv', me_send_datetime='".G5_TIME_YMDHIS."', me_memo='{$memo_content}', me_send_ip='{$send_ip}'";
            sql_query($sql_m1);

            $sql_m2 = "UPDATE {$g5['member_table']} SET mb_memo_call='{$send_id}', mb_memo_cnt='".get_memo_not_read($row['mb_id'])."' WHERE mb_id='{$recv_id}'";
            sql_query($sql_m2);

            $sent_count++;
        }
    }
}

if ($sent_count > 0) {
    alert(number_format($sent_count).'명에게 관리자 쪽지를 발송했습니다.', './memo_form.php');
}
alert('관리자 쪽지를 발송할 회원이 없습니다.', './memo_form.php');


?>
