<?php
include_once('../_common.php');

$act = isset($_POST['act']) ? $_POST['act'] : '';
$result = array('msg' => 'NOMSG', 'notification_id' => '');

if (empty($member['mb_id']) || !function_exists('rb_notification_table_ready') || !rb_notification_table_ready()) {
    echo json_encode($result);
    exit;
}

if ($act == 'alarm') {
    $mb_id = sql_real_escape_string($member['mb_id']);
    $row = sql_fetch("SELECT * FROM rb_notification
                       WHERE noti_recv_mb_id='{$mb_id}'
                         AND noti_read_at IS NULL
                       ORDER BY noti_id ASC
                       LIMIT 1", false);

    if (!empty($row['noti_id'])) {
        $categories = function_exists('rb_notification_categories') ? rb_notification_categories() : array();
        $result['notification_id'] = (int) $row['noti_id'];
        $result['category'] = isset($row['noti_category']) ? $row['noti_category'] : 'other';
        $result['category_label'] = isset($categories[$result['category']]) ? $categories[$result['category']] : '기타';
        $result['title'] = isset($row['noti_title']) ? $row['noti_title'] : '';
        $result['content'] = isset($row['noti_content']) ? $row['noti_content'] : '';
        $result['created_at'] = isset($row['noti_created_at']) ? $row['noti_created_at'] : '';
        $result['url'] = isset($row['noti_link']) ? $row['noti_link'] : '';
        $result['msg'] = 'SUCCESS';
    }
    echo json_encode($result);
    exit;
}

if ($act == 'read_notification') {
    $notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
    if ($notification_id > 0) {
        sql_query("UPDATE rb_notification
                      SET noti_read_at='".G5_TIME_YMDHIS."'
                    WHERE noti_id='{$notification_id}'
                      AND noti_recv_mb_id='".sql_real_escape_string($member['mb_id'])."'
                      AND noti_read_at IS NULL", false);
    }
    $result['msg'] = 'SUCCESS';
    echo json_encode($result);
    exit;
}

echo json_encode($result);
