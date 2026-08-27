<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 관리자 회원정보의 알림 설정을 프론트 회원정보와 같은 저장소에 연결합니다.
 */
function rb_notification_admin_member_form_add($mb, $w, $position)
{
    if ($position !== 'table') {
        return;
    }

    $mb_id = isset($mb['mb_id']) ? (string) $mb['mb_id'] : '';
    $preference = function_exists('rb_notification_get_preference')
        ? rb_notification_get_preference($mb_id)
        : array();
    $items = array(
        'push' => array('앱 Push 알림 동의', !empty($preference['notify_push'])),
        'site' => array('사이트 내 알림 동의', !empty($preference['notify_site'])),
        'comment' => array('댓글 알림', !empty($preference['notify_comment'])),
        'reply' => array('답글·대댓글 알림', !empty($preference['notify_reply'])),
        'shop' => array('쇼핑 알림', !empty($preference['notify_shop'])),
        'subscribe' => array('구독 알림', !empty($preference['notify_subscribe'])),
        'other' => array('기타 알림', !empty($preference['notify_other'])),
    );

    $pairs = array(
        array('push', 'site'),
        array('comment', 'reply'),
        array('shop', 'subscribe'),
    );
    foreach ($pairs as $pair) {
        echo '<tr>';
        foreach ($pair as $key) {
            rb_notification_admin_member_radio($key, $items[$key][0], $items[$key][1]);
        }
        echo '</tr>';
    }

    echo '<tr>';
    rb_notification_admin_member_radio('other', $items['other'][0], $items['other'][1], 3);
    echo '</tr>';
}

function rb_notification_admin_member_radio($key, $label, $checked, $colspan = 1)
{
    $name = 'rb_notify_'.$key;
    $yes = $checked ? ' checked="checked"' : '';
    $no = !$checked ? ' checked="checked"' : '';
    $colspan_attr = $colspan > 1 ? ' colspan="'.(int) $colspan.'"' : '';

    echo '<th scope="row">'.htmlspecialchars($label, ENT_QUOTES).'</th>';
    echo '<td'.$colspan_attr.'>';
    if ($key === 'push') {
        echo '<input type="hidden" name="rb_notification_preference_present" value="1">';
    }
    echo '<input type="radio" name="'.$name.'" value="1" id="'.$name.'_yes"'.$yes.'>';
    echo '<label for="'.$name.'_yes">예</label> ';
    echo '<input type="radio" name="'.$name.'" value="0" id="'.$name.'_no"'.$no.'>';
    echo '<label for="'.$name.'_no">아니오</label>';
    echo '</td>';
}

function rb_notification_admin_member_form_update($w, $mb_id)
{
    if (($w !== '' && $w !== 'u')
        || !isset($_POST['rb_notification_preference_present'])
        || (string) $_POST['rb_notification_preference_present'] !== '1'
        || !function_exists('rb_notification_save_preference')) {
        return;
    }

    $enabled = function ($key) {
        return isset($_POST[$key]) && (string) $_POST[$key] === '1';
    };
    $notify_push = $enabled('rb_notify_push');
    $notify_site = $enabled('rb_notify_site');
    $categories = array();
    foreach (array('comment', 'reply', 'shop', 'subscribe', 'other') as $type) {
        $categories['notify_'.$type] = $enabled('rb_notify_'.$type);
    }

    if (!rb_notification_save_preference($mb_id, $notify_push, $notify_site, $categories)) {
        alert('알림 수신 설정을 저장하지 못했습니다. 알림 설정 DB 테이블을 확인해 주세요.');
    }
}

add_event('admin_member_form_add', 'rb_notification_admin_member_form_add', G5_HOOK_DEFAULT_PRIORITY, 3);
add_event('admin_member_form_update', 'rb_notification_admin_member_form_update', G5_HOOK_DEFAULT_PRIORITY, 2);
