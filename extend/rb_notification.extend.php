<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 자동 시스템 알림은 그누보드 쪽지와 분리합니다.
 * rb_notification 테이블 구조는 빌더에 포함하지 않고 공식 홈페이지 API에서만 제공합니다.
 */
function rb_notification_database_table_exists($table)
{
    if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $table)) {
        return false;
    }
    $row = sql_fetch("SELECT COUNT(*) AS cnt
                        FROM information_schema.TABLES
                       WHERE TABLE_SCHEMA='".sql_real_escape_string(G5_MYSQL_DB)."'
                         AND TABLE_NAME='".sql_real_escape_string($table)."'", false);
    return !empty($row['cnt']);
}

function rb_notification_table_ready()
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = rb_notification_database_table_exists('rb_notification');
    return $ready;
}

function rb_notification_preference_table_ready()
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = rb_notification_database_table_exists('rb_notification_preference');
    return $ready;
}

/**
 * 설정 행이 아직 없는 회원과 신규 DB는 두 채널 모두 동의 상태로 처리합니다.
 * 기존 mb_sms 값은 알림 수신설정에 사용하지 않습니다.
 */
function rb_notification_get_preference($mb_id)
{
    $mb_id = trim((string) $mb_id);
    $defaults = array(
        'notify_push' => 1,
        'notify_site' => 1,
        'notify_updated_at' => '',
        'is_saved' => 0,
    );
    if ($mb_id === '' || !rb_notification_preference_table_ready()) {
        return $defaults;
    }

    if (!isset($GLOBALS['rb_notification_preference_cache']) || !is_array($GLOBALS['rb_notification_preference_cache'])) {
        $GLOBALS['rb_notification_preference_cache'] = array();
    }
    if (isset($GLOBALS['rb_notification_preference_cache'][$mb_id])) {
        return $GLOBALS['rb_notification_preference_cache'][$mb_id];
    }

    $row = sql_fetch("SELECT notify_push, notify_site, notify_updated_at
                        FROM rb_notification_preference
                       WHERE mb_id='".sql_real_escape_string($mb_id)."'
                       LIMIT 1", false);
    if (!isset($row['notify_push'])) {
        $GLOBALS['rb_notification_preference_cache'][$mb_id] = $defaults;
        return $defaults;
    }

    $preference = array(
        'notify_push' => !empty($row['notify_push']) ? 1 : 0,
        'notify_site' => !empty($row['notify_site']) ? 1 : 0,
        'notify_updated_at' => isset($row['notify_updated_at']) ? (string) $row['notify_updated_at'] : '',
        'is_saved' => 1,
    );
    $GLOBALS['rb_notification_preference_cache'][$mb_id] = $preference;
    return $preference;
}

function rb_notification_save_preference($mb_id, $notify_push, $notify_site)
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '' || !rb_notification_preference_table_ready()) {
        return false;
    }

    $notify_push = $notify_push ? 1 : 0;
    $notify_site = $notify_site ? 1 : 0;
    $now = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $saved = sql_query("INSERT INTO rb_notification_preference SET
                mb_id='".sql_real_escape_string($mb_id)."',
                notify_push='{$notify_push}',
                notify_site='{$notify_site}',
                notify_updated_at='".sql_real_escape_string($now)."',
                notify_ip='".sql_real_escape_string($ip)."'
            ON DUPLICATE KEY UPDATE
                notify_push=VALUES(notify_push),
                notify_site=VALUES(notify_site),
                notify_updated_at=VALUES(notify_updated_at),
                notify_ip=VALUES(notify_ip)", false);
    if (!$saved) {
        return false;
    }

    if (!isset($GLOBALS['rb_notification_preference_cache']) || !is_array($GLOBALS['rb_notification_preference_cache'])) {
        $GLOBALS['rb_notification_preference_cache'] = array();
    }
    $GLOBALS['rb_notification_preference_cache'][$mb_id] = array(
        'notify_push' => $notify_push,
        'notify_site' => $notify_site,
        'notify_updated_at' => $now,
        'is_saved' => 1,
    );
    return true;
}

function rb_notification_push_allowed($mb_id)
{
    $preference = rb_notification_get_preference($mb_id);
    return !empty($preference['notify_push']);
}

function rb_notification_site_allowed($mb_id)
{
    $preference = rb_notification_get_preference($mb_id);
    return !empty($preference['notify_site']);
}

function rb_notification_categories()
{
    return array(
        'board' => '게시물',
        'shop' => '쇼핑',
        'subscribe' => '구독',
        'notice' => '공지',
        'other' => '기타',
    );
}

function rb_notification_detect_category_from_link($link_url)
{
    $link = strtolower(str_replace('\\', '/', (string) $link_url));
    if (strpos($link, 'subscribe') !== false
        || preg_match('/[?&]ca=fn(?:&|$)/', $link)) {
        return 'subscribe';
    }
    if (strpos($link, '/shop/') !== false
        || strpos($link, '/shop_admin/') !== false
        || strpos($link, '/adm/shop_admin/') !== false) {
        return 'shop';
    }
    if (strpos($link, '/bbs/qawrite') !== false
        || strpos($link, '/bbs/qalist') !== false
        || strpos($link, '/bbs/board.php') !== false
        || strpos($link, 'bo_table=') !== false) {
        return 'board';
    }
    return '';
}

function rb_notification_detect_category_from_path($source_path)
{
    $file = strtolower(str_replace('\\', '/', (string) $source_path));
    if (strpos($file, 'rb_subscribe') !== false
        || strpos($file, '/subscribe/') !== false
        || strpos($file, 'ajax.subscribe') !== false) {
        return 'subscribe';
    }
    if (strpos($file, '/shop/') !== false
        || strpos($file, '/shop_admin/') !== false
        || strpos($file, '/adm/shop_admin/') !== false
        || strpos($file, '/rb.mod/partner/') !== false
        || strpos($file, '/rb.lib/ajax.partner_') !== false
        || strpos($file, '/adm/rb/partner_') !== false
        || (strpos($file, '/skin/member/') !== false
            && strpos($file, 'register_form_update.tail.skin.php') !== false)
        || strpos($file, '/@시스템/store_') !== false
        || strpos($file, '/@시스템/입점_') !== false) {
        return 'shop';
    }
    if (strpos($file, '/skin/board/') !== false
        || strpos($file, '/@스킨/') !== false
        || strpos($file, 'rb_mention') !== false
        || strpos($file, '댓글첨부') !== false) {
        return 'board';
    }
    return '';
}

function rb_notification_detect_category($title, $link_url)
{
    // 이전 호출부와 커스텀 스킨은 유형값이 없어도 링크를 우선하여 분류합니다.
    // 제목은 판별에 사용하지 않으며, 링크가 없거나 불명확할 때만 호출 경로를 보조로 확인합니다.
    $category = rb_notification_detect_category_from_link($link_url);
    if ($category !== '') return $category;

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
    foreach ($trace as $call) {
        if (empty($call['file'])) continue;
        $category = rb_notification_detect_category_from_path($call['file']);
        if ($category !== '') return $category;
    }
    return 'other';
}

function rb_notification_normalize_category($category, $title = '', $link_url = '')
{
    $category = strtolower(trim((string) $category));
    $categories = rb_notification_categories();
    return isset($categories[$category]) ? $category : rb_notification_detect_category($title, $link_url);
}

function rb_notification_send($category, $title, $content, $link_url, $recv_id, $send_id = 'system-msg', $options = array())
{
    global $app, $config, $rb_builder;

    $recv_id = trim((string) $recv_id);
    $send_id = trim((string) $send_id);
    $title = trim((string) $title);
    $content = trim((string) $content);
    $link_url = trim((string) $link_url);
    if ($recv_id === '' || $title === '' || !rb_notification_table_ready()) {
        return false;
    }

    if ($recv_id === (string) $config['cf_admin']
        && $send_id === 'system-msg'
        && isset($rb_builder['bu_systemmsg_use'])
        && (int) $rb_builder['bu_systemmsg_use'] !== 1) {
        return false;
    }

    $category = rb_notification_normalize_category($category, $title, $link_url);
    $source_type = isset($options['source_type']) ? trim((string) $options['source_type']) : '';
    $source_id = isset($options['source_id']) ? trim((string) $options['source_id']) : '';
    $batch_key = isset($options['batch_key']) ? trim((string) $options['batch_key']) : '';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $now = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');

    $noti_id = 0;
    if (rb_notification_site_allowed($recv_id)) {
        $sql = "INSERT INTO rb_notification SET
                    noti_category='".sql_real_escape_string($category)."',
                    noti_title='".sql_real_escape_string($title)."',
                    noti_content='".sql_real_escape_string($content !== '' ? $content : $title)."',
                    noti_link='".sql_real_escape_string($link_url)."',
                    noti_recv_mb_id='".sql_real_escape_string($recv_id)."',
                    noti_send_mb_id='".sql_real_escape_string($send_id !== '' ? $send_id : 'system-msg')."',
                    noti_source_type='".sql_real_escape_string($source_type)."',
                    noti_source_id='".sql_real_escape_string($source_id)."',
                    noti_batch_key='".sql_real_escape_string($batch_key)."',
                    noti_created_at='".sql_real_escape_string($now)."',
                    noti_ip='".sql_real_escape_string($ip)."'";
        if (!sql_query($sql, false)) {
            return false;
        }
        $noti_id = function_exists('sql_insert_id') ? sql_insert_id() : mysqli_insert_id($GLOBALS['g5']['connect_db']);
    }

    // PWA는 자체 구독·수신동의 절차를 사용하며, 회원의 앱 Push 설정과 분리합니다.
    $pwa_push = (!isset($options['pwa_push']) || (bool) $options['pwa_push']);
    $app_push = rb_notification_push_allowed($recv_id)
        && (!isset($options['push']) || (bool) $options['push'])
        && (!isset($options['app_push']) || (bool) $options['app_push']);
    $labels = rb_notification_categories();
    $push_title = isset($labels[$category]) ? $labels[$category].' 알림' : '새 알림';

    if ($pwa_push && function_exists('send_pwa_if_needed')) {
        send_pwa_if_needed($recv_id, $send_id, $push_title, $link_url, $title);
    }
    if ($app_push
        && isset($app['ap_title'], $app['ap_key'], $app['ap_pid'])
        && $app['ap_title'] && $app['ap_key'] && $app['ap_pid']) {
        rb_notification_send_app_push($recv_id, $push_title, $title, $app['ap_key']);
    }
    return $noti_id;
}

/** 기존 호출부와 설치된 부가기능의 호환성을 유지하는 이름입니다. 실제로는 쪽지를 만들지 않습니다. */
function memo_auto_send($title, $link_url, $recv_id, $send_id, $category = '', $options = array())
{
    $category = rb_notification_normalize_category($category, $title, $link_url);
    return rb_notification_send($category, $title, $title, $link_url, $recv_id, $send_id, $options);
}

/* 기존 앱 및 부가기능이 사용하는 푸시 공통함수 이름을 계속 지원합니다. */
if (!function_exists('get_user_tokens')) {
    function get_user_tokens($recv_id)
    {
        $tokens = array();
        if (!rb_notification_database_table_exists('rb_app_token')) return $tokens;
        $result = sql_query("SELECT tk_token FROM rb_app_token
                             WHERE tk_token != ''
                               AND mb_id='".sql_real_escape_string($recv_id)."'", false);
        while ($result && $row=sql_fetch_array($result)) $tokens[] = $row['tk_token'];
        return $tokens;
    }
}

if (!function_exists('sendPushNotificationAsync')) {
    function sendPushNotificationAsync($tokens, $title, $body, $json_key_file_path)
    {
        $tokens = rb_notification_filter_push_tokens($tokens);
        if (!$tokens || !function_exists('curl_init')) return;
        $post_data = json_encode(array(
            'tokens' => array_values($tokens),
            'title' => (string) $title,
            'body' => (string) $body,
            'jsonKeyFilePath' => (string) $json_key_file_path,
        ));
        $ch = curl_init(G5_URL.'/rb/rb.lib/curl.send_push.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
        curl_exec($ch);
        curl_close($ch);
    }
}

function rb_notification_filter_push_tokens($tokens)
{
    $tokens = array_values(array_unique(array_filter(array_map('trim', (array) $tokens))));
    if (!$tokens || !rb_notification_database_table_exists('rb_app_token')) {
        return $tokens;
    }

    $token_members = array();
    foreach (array_chunk($tokens, 200) as $chunk) {
        $escaped = array();
        foreach ($chunk as $token) {
            $escaped[] = "'".sql_real_escape_string($token)."'";
        }
        $result = sql_query("SELECT tk_token, mb_id FROM rb_app_token
                              WHERE tk_token IN (".implode(',', $escaped).")", false);
        while ($result && $row=sql_fetch_array($result)) {
            $token_members[(string) $row['tk_token']] = isset($row['mb_id']) ? (string) $row['mb_id'] : '';
        }
    }

    $allowed = array();
    foreach ($tokens as $token) {
        if (!isset($token_members[$token])
            || $token_members[$token] === ''
            || rb_notification_push_allowed($token_members[$token])) {
            $allowed[] = $token;
        }
    }
    return $allowed;
}

if (!function_exists('send_push_if_needed')) {
    function send_push_if_needed($recv_id, $body, $api_key)
    {
        global $app, $config;
        if (!rb_notification_push_allowed($recv_id)) return;
        if ($recv_id === (string) $config['cf_admin'] && empty($app['ap_systems_msg'])) return;
        $tokens = get_user_tokens($recv_id);
        if ($tokens) sendPushNotificationAsync($tokens, '시스템 알림', $body, G5_DATA_PATH.'/push/'.basename((string) $api_key));
    }
}

function rb_notification_send_app_push($recv_id, $push_title, $body, $api_key)
{
    global $app, $config;
    if ($recv_id === '') {
        return;
    }
    if (!rb_notification_push_allowed($recv_id)) return;
    if ($recv_id === (string) $config['cf_admin'] && empty($app['ap_systems_msg'])) return;
    $tokens = get_user_tokens($recv_id);
    if (!$tokens) {
        return;
    }
    sendPushNotificationAsync($tokens, $push_title, $body, G5_DATA_PATH.'/push/'.basename((string) $api_key));
}

/*
 * 업데이트 전 subscribe 1.1.5는 게시글 구독 알림을 쪽지 테이블에 직접 기록합니다.
 * 이전 부가기능을 그대로 사용하는 설치본도 이벤트 완료 후 새 알림으로 안전하게 옮깁니다.
 */
add_event('write_update_after', 'rb_notification_subscribe_compatibility', G5_HOOK_DEFAULT_PRIORITY + 20, 5);

function rb_notification_subscribe_compatibility($board, $wr_id, $w, $qstr, $redirect_url)
{
    global $g5;
    if ($w !== '' || empty($board['bo_table']) || !$wr_id
        || !rb_notification_table_ready()
        || !rb_notification_database_table_exists('rb_subscribe')) {
        return;
    }

    $write_table = $g5['write_prefix'].$board['bo_table'];
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $write_table)) {
        return;
    }
    $write = sql_fetch("SELECT mb_id FROM `{$write_table}`
                         WHERE wr_id='".(int) $wr_id."'
                           AND wr_is_comment=0", false);
    if (empty($write['mb_id'])) {
        return;
    }
    $writer = get_member($write['mb_id'], 'mb_nick');
    $title = (isset($writer['mb_nick']) ? $writer['mb_nick'] : $write['mb_id'])
        .'님 께서 '.$board['bo_subject'].'에 새글을 등록했습니다.';
    $link_url = G5_BBS_URL.'/board.php?bo_table='.urlencode($board['bo_table']).'&wr_id='.(int) $wr_id;
    $source_id = $board['bo_table'].':'.(int) $wr_id;
    $memo_content = $title."\n".$link_url;

    $subscribers = sql_query("SELECT sb_mb_id FROM rb_subscribe
                               WHERE sb_fw_id='".sql_real_escape_string($write['mb_id'])."'
                                 AND sb_push=1", false);
    while ($subscribers && $subscriber=sql_fetch_array($subscribers)) {
        $recv_id = isset($subscriber['sb_mb_id']) ? trim((string) $subscriber['sb_mb_id']) : '';
        if ($recv_id === '') continue;

        $duplicate = sql_fetch("SELECT noti_id FROM rb_notification
                                 WHERE noti_recv_mb_id='".sql_real_escape_string($recv_id)."'
                                   AND noti_source_type='subscribe_post'
                                   AND noti_source_id='".sql_real_escape_string($source_id)."'
                                 LIMIT 1", false);
        // 업데이트된 구독 부가기능이 이미 저장했다면 호환 처리를 반복하지 않습니다.
        if (!empty($duplicate['noti_id'])) continue;

        $noti_id = rb_notification_send(
            'subscribe', $title, $title, $link_url, $recv_id, 'system-msg', array(
                'source_type' => 'subscribe_post',
                'source_id' => $source_id,
                // 기존 부가기능의 앱 푸시는 유지하고 PWA 푸시만 새 알림에서 처리합니다.
                'app_push' => false,
            )
        );
        if (!$noti_id) continue;

        sql_query("DELETE FROM {$g5['memo_table']}
                    WHERE me_recv_mb_id='".sql_real_escape_string($recv_id)."'
                      AND me_send_mb_id='system-msg'
                      AND me_memo='".sql_real_escape_string($memo_content)."'", false);
        $memo_count = (int) get_memo_not_read($recv_id);
        sql_query("UPDATE {$g5['member_table']}
                      SET mb_memo_cnt='{$memo_count}',
                          mb_memo_call=IF('{$memo_count}'=0, '', mb_memo_call)
                    WHERE mb_id='".sql_real_escape_string($recv_id)."'", false);
    }
}
