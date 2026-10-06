<?php
if (!defined('_GNUBOARD_')) exit;

/** 2.2.7.8: 푸시 클릭은 수신 회원의 정확한 알림 한 건에만 연결합니다. */
function rb_push_click_signature($kind, $id, $mb_id)
{
    global $member;
    if (!in_array($kind, array('notification', 'memo'), true) || $id < 1 || $mb_id === '') return '';
    $secret = defined('G5_MYSQL_PASSWORD') ? (string) G5_MYSQL_PASSWORD : '';
    // 로컬 서버처럼 DB 비밀번호가 비어 있으면 회원의 비밀번호 해시를 사용합니다.
    if ($secret === '') {
        static $passwords = array();
        if (!isset($passwords[$mb_id])) {
            $row = isset($member['mb_id']) && $member['mb_id'] === $mb_id && !empty($member['mb_password'])
                ? $member : get_member($mb_id, 'mb_password');
            $passwords[$mb_id] = isset($row['mb_password']) ? (string) $row['mb_password'] : '';
        }
        $secret = $passwords[$mb_id];
    }
    if ($secret === '') return '';
    return hash_hmac('sha256', $kind.'|'.(int) $id.'|'.$mb_id.'|'.G5_URL, $secret);
}

function rb_push_click_url($kind, $id, $mb_id)
{
    $signature = rb_push_click_signature($kind, (int) $id, (string) $mb_id);
    if ($signature === '') return '';
    return G5_URL.'/rb/rb.lib/push.open.php?kind='.rawurlencode($kind).'&id='.(int) $id.'&token='.$signature;
}

function rb_push_click_target($url)
{
    $url = trim(html_entity_decode((string) $url, ENT_QUOTES, 'UTF-8'));
    if ($url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) return G5_URL;
    if (preg_match('#^https?://#i', $url)) return $url;
    if (strpos($url, '//') === 0 || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) return G5_URL;
    if ($url[0] === '/') {
        $site = parse_url(G5_URL);
        return $site['scheme'].'://'.$site['host'].(isset($site['port']) ? ':'.$site['port'] : '').$url;
    }
    return rtrim(G5_URL, '/').'/'.$url;
}

/** 구버전 빌더에서도 최신 알림 함수에 의존하지 않고 사용할 수 있습니다. */
function rb_push_click_read($kind, $id, $token, $mb_id)
{
    global $g5;
    $signature = rb_push_click_signature($kind, (int) $id, (string) $mb_id);
    if ($signature === '' || !is_string($token) || !hash_equals($signature, $token)) return '';
    $id = (int) $id;
    $recipient = sql_real_escape_string($mb_id);
    $now = sql_real_escape_string(G5_TIME_YMDHIS);
    if ($kind === 'notification') {
        $row = sql_fetch("SELECT noti_link FROM rb_notification WHERE noti_id='{$id}' AND noti_recv_mb_id='{$recipient}' LIMIT 1", false);
        if (!is_array($row) || !array_key_exists('noti_link', $row)) return '';
        sql_query("UPDATE rb_notification SET noti_read_at='{$now}' WHERE noti_id='{$id}' AND noti_recv_mb_id='{$recipient}' AND noti_read_at IS NULL", false);
        return rb_push_click_target($row['noti_link']);
    }
    $row = sql_fetch("SELECT me_id FROM {$g5['memo_table']} WHERE me_id='{$id}' AND me_recv_mb_id='{$recipient}' AND me_type='recv' LIMIT 1", false);
    if (empty($row['me_id'])) return '';
    sql_query("UPDATE {$g5['memo_table']} SET me_read_datetime='{$now}' WHERE (me_id='{$id}' OR me_send_id='{$id}') AND me_recv_mb_id='{$recipient}' AND (me_read_datetime='0000-00-00 00:00:00' OR me_read_datetime IS NULL)", false);
    $count = (int) get_memo_not_read($mb_id);
    sql_query("UPDATE {$g5['member_table']} SET mb_memo_cnt='{$count}'".($count === 0 ? ", mb_memo_call=''" : '')." WHERE mb_id='{$recipient}'", false);
    return G5_URL;
}
