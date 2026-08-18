<?php
$sub_menu = '000220';
include_once('./_common.php');

check_demo();
auth_check_menu($auth, $sub_menu, 'd');
check_admin_token();

if (!function_exists('rb_notification_table_ready') || !rb_notification_table_ready()) {
    alert('알림 기능을 사용하려면 빌더설정 > DB업데이트를 먼저 실행해 주세요.', './rb_form.php');
}

$ids = isset($_POST['noti_id']) && is_array($_POST['noti_id']) ? $_POST['noti_id'] : array();
$safe_ids = array();
foreach ($ids as $id) {
    $id = (int) $id;
    if ($id > 0) $safe_ids[$id] = $id;
}
if (!$safe_ids) alert('삭제할 알림을 선택해 주세요.');

sql_query("DELETE FROM rb_notification WHERE noti_id IN (".implode(',', $safe_ids).")", false);
$qstr = isset($_POST['qstr']) ? str_replace('&amp;', '&', (string) $_POST['qstr']) : '';
$page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
goto_url('./notification_form.php?'.$qstr.'&page='.$page);
