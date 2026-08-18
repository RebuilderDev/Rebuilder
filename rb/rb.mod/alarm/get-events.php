<?php
include_once('../_common.php');

$act = isset($_POST['act']) ? $_POST['act'] : '';
$result = array('msg' => 'NOMSG', 'notification_id' => '');
header('Content-Type: application/json; charset=utf-8');

if (empty($member['mb_id'])) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

$notification_ready = function_exists('rb_notification_table_ready') && rb_notification_table_ready();
$result['unread_count'] = $notification_ready ? rb_notification_unread_count($member['mb_id']) : 0;
$result['memo_unread_count'] = (int) get_memo_not_read($member['mb_id']);

if ($act == 'alarm') {
    $mb_id = sql_real_escape_string($member['mb_id']);
    $notification = array();
    if ($notification_ready) {
        $notification = sql_fetch("SELECT * FROM rb_notification
                                    WHERE noti_recv_mb_id='{$mb_id}'
                                      AND noti_read_at IS NULL
                                    ORDER BY noti_id DESC
                                    LIMIT 1", false);
    }
    $memo = sql_fetch("SELECT me_id, me_send_mb_id, me_memo, me_send_datetime
                         FROM {$g5['memo_table']}
                        WHERE me_recv_mb_id='{$mb_id}'
                          AND me_type='recv'
                          AND me_send_mb_id<>'system-msg'
                          AND (me_read_datetime='0000-00-00 00:00:00' OR me_read_datetime IS NULL)
                        ORDER BY me_id DESC
                        LIMIT 1", false);

    $events = array();
    if (!empty($memo['me_id'])) {
        $sender = get_member($memo['me_send_mb_id'], 'mb_nick');
        $sender_nick = isset($sender['mb_nick']) ? trim((string) $sender['mb_nick']) : '';
        $events[] = array(
            'event_type' => 'memo',
            'memo_id' => (int) $memo['me_id'],
            'title' => $sender_nick !== '' ? $sender_nick.'님의 쪽지' : '새 쪽지',
            'content' => isset($memo['me_memo']) ? $memo['me_memo'] : '',
            'created_at' => isset($memo['me_send_datetime']) ? $memo['me_send_datetime'] : '',
        );
    }
    if (!empty($notification['noti_id'])) {
        $categories = function_exists('rb_notification_categories') ? rb_notification_categories() : array();
        $category = isset($notification['noti_category']) ? $notification['noti_category'] : 'other';
        $events[] = array(
            'event_type' => 'notification',
            'notification_id' => (int) $notification['noti_id'],
            'category' => $category,
            'category_label' => isset($categories[$category]) ? $categories[$category] : '기타',
            'title' => isset($notification['noti_title']) ? $notification['noti_title'] : '',
            'content' => isset($notification['noti_content']) ? $notification['noti_content'] : '',
            'created_at' => isset($notification['noti_created_at']) ? $notification['noti_created_at'] : '',
            'url' => isset($notification['noti_link']) ? $notification['noti_link'] : '',
        );
    }

    if ($events) {
        usort($events, function($left, $right) {
            return strcmp((string) $right['created_at'], (string) $left['created_at']);
        });
        $result = array_merge($result, $events[0]);
        $result['events'] = $events;
        $result['msg'] = 'SUCCESS';
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($act == 'read_notification') {
    if (!$notification_ready) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
    rb_notification_member_get($member['mb_id'], $notification_id, true);
    $result['msg'] = 'SUCCESS';
    $result['unread_count'] = rb_notification_unread_count($member['mb_id']);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($act == 'notification_list') {
    if (!$notification_ready) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $category = isset($_POST['category']) ? trim((string) $_POST['category']) : 'all';
    $categories = rb_notification_visible_categories();
    if ($category !== 'all' && !isset($categories[$category])) {
        $category = 'all';
    }
    $result['categories'] = array('all' => '전체') + $categories;
    $result['items'] = rb_notification_member_list($member['mb_id'], $category, 50);
    $result['unread_count'] = rb_notification_unread_count($member['mb_id']);
    $result['msg'] = 'SUCCESS';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($act == 'notification_view' || $act == 'notification_delete' || $act == 'notification_delete_all') {
    if (!$notification_ready) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $action_token = isset($_POST['action_token']) ? (string) $_POST['action_token'] : '';
    if (!rb_notification_action_token_valid($action_token)) {
        $result['msg'] = 'INVALID_TOKEN';
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($act == 'notification_view') {
        $notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
        $result['item'] = rb_notification_member_get($member['mb_id'], $notification_id, true);
        $result['msg'] = empty($result['item']) ? 'NOT_FOUND' : 'SUCCESS';
    } elseif ($act == 'notification_delete') {
        $notification_id = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
        $result['msg'] = rb_notification_member_delete($member['mb_id'], $notification_id) ? 'SUCCESS' : 'FAILED';
    } else {
        $result['msg'] = rb_notification_member_delete_all($member['mb_id']) ? 'SUCCESS' : 'FAILED';
    }
    $result['unread_count'] = rb_notification_unread_count($member['mb_id']);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
