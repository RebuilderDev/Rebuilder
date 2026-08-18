<?php
$sub_menu = '000220';
include_once('./_common.php');

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if (!function_exists('rb_notification_table_ready') || !rb_notification_table_ready()) {
    alert('알림 기능을 사용하려면 빌더설정 > DB업데이트를 먼저 실행해 주세요.', './rb_form.php');
}
if (!function_exists('rb_notification_database_table_exists')
    || !rb_notification_database_table_exists('rb_notification_dispatch')) {
    alert('알림 기능을 사용하려면 빌더설정 > DB업데이트를 먼저 실행해 주세요.', './rb_form.php');
}

$target_type = isset($_POST['target_type']) ? (string) $_POST['target_type'] : '';
$content = isset($_POST['noti_content']) ? trim((string) $_POST['noti_content']) : '';
$link = isset($_POST['noti_link']) ? trim((string) $_POST['noti_link']) : '';
if (!in_array($target_type, array('member', 'level', 'all'), true)) alert('발송 대상을 확인해 주세요.');
if ($content === '') alert('내용을 입력해 주세요.');
if (strlen($link) > 1000) alert('링크 URL이 너무 깁니다.');
if ($link !== '' && !preg_match('#^(https?://|/)#i', $link)) alert('연결 주소는 http(s) 주소 또는 /로 시작하는 내부 주소만 입력할 수 있습니다.');

$title = trim((string) preg_replace('/\s+/u', ' ', strip_tags($content)));
if ($title === '') $title = '알림';
$title = function_exists('mb_substr') ? mb_substr($title, 0, 255, 'UTF-8') : substr($title, 0, 255);

$recipient_ids = array();
$target_value = '';
if ($target_type === 'member') {
    $raw = isset($_POST['target_members']) ? trim((string) $_POST['target_members']) : '';
    $ids = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $invalid_ids = array();
    foreach (array_unique($ids) as $mb_id) {
        $mb = get_member($mb_id, 'mb_id, mb_leave_date');
        if (!empty($mb['mb_id']) && empty($mb['mb_leave_date'])) $recipient_ids[] = $mb['mb_id'];
        else $invalid_ids[] = $mb_id;
    }
    if ($invalid_ids) alert('존재하지 않거나 탈퇴한 회원 아이디가 있습니다: '.implode(', ', array_slice($invalid_ids, 0, 10)));
    $target_value = implode(',', $recipient_ids);
} elseif ($target_type === 'level') {
    $levels = isset($_POST['target_levels']) && is_array($_POST['target_levels']) ? $_POST['target_levels'] : array();
    $safe_levels = array();
    foreach ($levels as $level) {
        $level = (int) $level;
        if ($level >= 1 && $level <= 10) $safe_levels[$level] = $level;
    }
    if (!$safe_levels) alert('발송할 회원 레벨을 선택해 주세요.');
    $target_value = implode(',', $safe_levels);
    $result = sql_query("SELECT mb_id FROM {$g5['member_table']}
                         WHERE mb_leave_date=''
                           AND mb_level IN (".implode(',', $safe_levels).")", false);
    while ($result && $row=sql_fetch_array($result)) $recipient_ids[] = $row['mb_id'];
} else {
    $target_value = 'all';
    $result = sql_query("SELECT mb_id FROM {$g5['member_table']} WHERE mb_leave_date=''", false);
    while ($result && $row=sql_fetch_array($result)) $recipient_ids[] = $row['mb_id'];
}

$recipient_ids = array_values(array_unique(array_filter($recipient_ids)));
if (!$recipient_ids) alert('발송할 정상 회원이 없습니다.');

@set_time_limit(0);
$batch_key = 'NTF-'.strtoupper(substr(get_random_token_string(40), 0, 32));
$sent_count = 0;
sql_query('START TRANSACTION', false);
foreach ($recipient_ids as $recv_id) {
    $sent = rb_notification_send('notice', $title, $content, $link, $recv_id, $member['mb_id'], array(
        'source_type' => 'admin_notice',
        'batch_key' => $batch_key,
        'push' => false,
    ));
    if ($sent === false) {
        sql_query('ROLLBACK', false);
        alert('공지 저장 중 오류가 발생했습니다. 발송된 알림 없이 취소했습니다.');
    }
    if ($sent) $sent_count++;
}
if (!$sent_count) {
    sql_query('ROLLBACK', false);
    alert('공지를 저장하지 못했습니다.');
}

$now = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');
$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$dispatch_saved = sql_query("INSERT INTO rb_notification_dispatch SET
            dispatch_key='".sql_real_escape_string($batch_key)."',
            dispatch_category='notice',
            dispatch_target_type='".sql_real_escape_string($target_type)."',
            dispatch_target_value='".sql_real_escape_string($target_value)."',
            dispatch_title='".sql_real_escape_string($title)."',
            dispatch_content='".sql_real_escape_string($content)."',
            dispatch_link='".sql_real_escape_string($link)."',
            dispatch_recipient_count='".(int) $sent_count."',
            dispatch_admin_id='".sql_real_escape_string($member['mb_id'])."',
            dispatch_created_at='".sql_real_escape_string($now)."',
            dispatch_ip='".sql_real_escape_string($ip)."'", false);
if (!$dispatch_saved) {
    sql_query('ROLLBACK', false);
    alert('공지 알림 발송내역을 저장하지 못했습니다. 발송된 알림 없이 취소했습니다.');
}
sql_query('COMMIT', false);

alert(number_format($sent_count).'명에게 공지 알림을 발송했습니다.', './notification_form.php');
