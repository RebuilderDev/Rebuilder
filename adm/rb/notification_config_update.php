<?php
$sub_menu = '000220';
include_once('./_common.php');

check_demo();
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$retention_column = sql_fetch("SHOW COLUMNS FROM rb_builder LIKE 'bu_notification_retention_days'", false);
$polling_column = sql_fetch("SHOW COLUMNS FROM rb_builder LIKE 'bu_notification_polling_seconds'", false);
if (empty($retention_column['Field']) || empty($polling_column['Field'])) {
    alert('빌더설정 > DB업데이트를 먼저 실행해 주세요.', './notification_form.php');
}

$days = isset($_POST['notification_retention_days'])
    ? (int) $_POST['notification_retention_days']
    : 180;
if ($days < 1 || $days > 180) {
    alert('알림 보관일수는 1일부터 180일까지 설정할 수 있습니다.');
}

$polling_seconds = isset($_POST['notification_polling_seconds'])
    ? (int) $_POST['notification_polling_seconds']
    : 60;
if ($polling_seconds < 10 || $polling_seconds > 3600) {
    alert('알림 폴링주기는 10초부터 3600초까지 설정할 수 있습니다.');
}

$row_count = sql_fetch("SELECT COUNT(*) AS cnt FROM rb_builder", false);
if (!empty($row_count['cnt'])) {
    $saved = sql_query("UPDATE rb_builder
                           SET bu_notification_retention_days='{$days}',
                               bu_notification_polling_seconds='{$polling_seconds}'", false);
} else {
    $saved = sql_query("INSERT INTO rb_builder SET
                           bu_viewport='0.9',
                           bu_notification_retention_days='{$days}',
                           bu_notification_polling_seconds='{$polling_seconds}',
                           bu_datetime='".sql_real_escape_string(G5_TIME_YMDHIS)."'", false);
}
if (!$saved) {
    alert('알림 설정을 저장하지 못했습니다. 빌더설정 > DB업데이트를 확인해 주세요.');
}

$stored = sql_fetch("SELECT bu_notification_retention_days, bu_notification_polling_seconds
                       FROM rb_builder
                      LIMIT 1", false);
if (!isset($stored['bu_notification_retention_days'], $stored['bu_notification_polling_seconds'])
    || (int) $stored['bu_notification_retention_days'] !== $days
    || (int) $stored['bu_notification_polling_seconds'] !== $polling_seconds) {
    alert('알림 설정이 정상적으로 저장되지 않았습니다. 빌더설정 > DB업데이트를 확인해 주세요.');
}
if (function_exists('rb_notification_cleanup_expired')) {
    $rb_builder['bu_notification_retention_days'] = $days;
    $rb_builder['bu_notification_polling_seconds'] = $polling_seconds;
    rb_notification_cleanup_expired();
}

alert('알림 설정을 저장했습니다.', './notification_form.php');
