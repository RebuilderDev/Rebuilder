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

function rb_notification_retention_days()
{
    global $rb_builder;

    $days = isset($rb_builder['bu_notification_retention_days'])
        ? (int) $rb_builder['bu_notification_retention_days']
        : 180;
    if ($days < 1) {
        $days = 180;
    }
    return min(180, $days);
}

function rb_notification_polling_seconds()
{
    global $rb_builder;

    $seconds = isset($rb_builder['bu_notification_polling_seconds'])
        ? (int) $rb_builder['bu_notification_polling_seconds']
        : 60;
    if ($seconds < 10 || $seconds > 3600) {
        $seconds = 60;
    }
    return $seconds;
}

/**
 * 플로팅 알림 설정입니다.
 *
 * 설정 컬럼이 아직 없는 기존 설치본은 현재 기본 위치(좌 50px, 하단 40px)를
 * 그대로 사용합니다. 관리자가 설정을 저장한 뒤에는 선택한 위치와 간격을 적용합니다.
 */
function rb_notification_floating_settings()
{
    global $rb_builder;

    $has_saved_settings = is_array($rb_builder)
        && array_key_exists('bu_notification_floating_use', $rb_builder)
        && array_key_exists('bu_notification_floating_position', $rb_builder)
        && array_key_exists('bu_notification_floating_offset', $rb_builder);
    $allowed_positions = array('left_top', 'left_bottom', 'right_top', 'right_bottom', 'center');
    $position = $has_saved_settings
        ? trim((string) $rb_builder['bu_notification_floating_position'])
        : 'left_bottom';
    if (!in_array($position, $allowed_positions, true)) {
        $position = 'left_bottom';
    }

    $offset = $has_saved_settings ? (int) $rb_builder['bu_notification_floating_offset'] : 50;
    if ($offset < 0 || $offset > 1000) {
        $offset = 50;
    }

    return array(
        'use' => !$has_saved_settings || !empty($rb_builder['bu_notification_floating_use']) ? 1 : 0,
        'position' => $position,
        'offset' => $offset,
        'is_saved' => $has_saved_settings ? 1 : 0,
    );
}

function rb_notification_floating_style($settings = array())
{
    if (!$settings) {
        $settings = rb_notification_floating_settings();
    }

    // 설정이 없는 기존 사이트는 지금까지 사용하던 기본 CSS 좌표를 유지합니다.
    if (empty($settings['is_saved'])) {
        return 'left:50px;right:auto;top:auto;bottom:40px;transform:none;';
    }

    $offset = isset($settings['offset']) ? max(0, min(1000, (int) $settings['offset'])) : 50;
    $position = isset($settings['position']) ? (string) $settings['position'] : 'left_bottom';
    $styles = array(
        'left_top' => "left:{$offset}px;right:auto;top:{$offset}px;bottom:auto;transform:none;",
        'left_bottom' => "left:{$offset}px;right:auto;top:auto;bottom:{$offset}px;transform:none;",
        'right_top' => "left:auto;right:{$offset}px;top:{$offset}px;bottom:auto;transform:none;",
        'right_bottom' => "left:auto;right:{$offset}px;top:auto;bottom:{$offset}px;transform:none;",
        'center' => 'left:50%;right:auto;top:50%;bottom:auto;transform:translate(-50%, -50%);',
    );

    return isset($styles[$position]) ? $styles[$position] : $styles['left_bottom'];
}

function rb_notification_cleanup_expired()
{
    if (!rb_notification_table_ready()) {
        return false;
    }

    $days = rb_notification_retention_days();
    $cutoff = date('Y-m-d H:i:s', G5_SERVER_TIME - ($days * 86400));
    return (bool) sql_query("DELETE FROM rb_notification
                              WHERE noti_created_at < '".sql_real_escape_string($cutoff)."'", false);
}

/** 그누보드의 쪽지 자동삭제와 같은 최고관리자 일일 DB 정리 주기에 실행합니다. */
function rb_notification_run_daily_cleanup()
{
    global $config, $member, $is_admin;

    if ($is_admin !== 'super'
        || empty($member['mb_id'])
        || (string) $member['mb_id'] !== (string) $config['cf_admin']
        || !isset($config['cf_optimize_date'])
        || (string) $config['cf_optimize_date'] >= G5_TIME_YMD) {
        return;
    }
    rb_notification_cleanup_expired();
}

rb_notification_run_daily_cleanup();

function rb_notification_preference_table_ready()
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = rb_notification_database_table_exists('rb_notification_preference');
    return $ready;
}

function rb_notification_preference_columns()
{
    static $columns = null;
    if ($columns !== null) {
        return $columns;
    }

    $columns = array();
    if (!rb_notification_preference_table_ready()) {
        return $columns;
    }
    $result = sql_query('SHOW COLUMNS FROM rb_notification_preference', false);
    while ($result && $row = sql_fetch_array($result)) {
        if (!empty($row['Field'])) {
            $columns[(string) $row['Field']] = true;
        }
    }
    return $columns;
}

/**
 * 명시적으로 저장된 동의가 없는 회원은 모든 선택 알림을 수신하지 않습니다.
 * 기존 mb_sms 값은 알림 수신설정에 사용하지 않습니다.
 */
function rb_notification_get_preference($mb_id)
{
    $mb_id = trim((string) $mb_id);
    $defaults = array(
        'notify_push' => 0,
        'notify_site' => 0,
        'notify_comment' => 0,
        'notify_reply' => 0,
        'notify_comment_reply' => 0,
        'notify_shop' => 0,
        'notify_subscribe' => 0,
        'notify_other' => 0,
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

    $columns = rb_notification_preference_columns();
    $select = array('notify_push', 'notify_site', 'notify_updated_at');
    foreach (array('notify_comment', 'notify_reply', 'notify_comment_reply', 'notify_shop', 'notify_subscribe', 'notify_other') as $column) {
        if (isset($columns[$column])) {
            $select[] = $column;
        }
    }
    $row = sql_fetch("SELECT ".implode(', ', $select)."
                        FROM rb_notification_preference
                       WHERE mb_id='".sql_real_escape_string($mb_id)."'
                       LIMIT 1", false);
    if (!isset($row['notify_push'])) {
        $GLOBALS['rb_notification_preference_cache'][$mb_id] = $defaults;
        return $defaults;
    }

    $preference = $defaults;
    foreach (array('notify_push', 'notify_site', 'notify_comment', 'notify_reply', 'notify_comment_reply', 'notify_shop', 'notify_subscribe', 'notify_other') as $column) {
        if (array_key_exists($column, $row)) {
            $preference[$column] = !empty($row[$column]) ? 1 : 0;
        }
    }
    // DB 업데이트 전에는 기존 통합 답글 설정을 대댓글 설정의 호환값으로 사용합니다.
    if (!isset($columns['notify_comment_reply'])) {
        $preference['notify_comment_reply'] = $preference['notify_reply'];
    }
    $preference['notify_updated_at'] = isset($row['notify_updated_at']) ? (string) $row['notify_updated_at'] : '';
    $preference['is_saved'] = 1;
    $GLOBALS['rb_notification_preference_cache'][$mb_id] = $preference;
    return $preference;
}

function rb_notification_save_preference($mb_id, $notify_push, $notify_site, $category_preferences = array())
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '' || !rb_notification_preference_table_ready()) {
        return false;
    }

    $current = rb_notification_get_preference($mb_id);
    $values = array(
        'notify_push' => $notify_push ? 1 : 0,
        'notify_site' => $notify_site ? 1 : 0,
    );
    foreach (array('notify_comment', 'notify_reply', 'notify_comment_reply', 'notify_shop', 'notify_subscribe', 'notify_other') as $column) {
        if (!$values['notify_site']) {
            $values[$column] = 0;
        } elseif (array_key_exists($column, $category_preferences)) {
            $values[$column] = $category_preferences[$column] ? 1 : 0;
        } else {
            $values[$column] = !empty($current[$column]) ? 1 : 0;
        }
    }
    $now = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $columns = rb_notification_preference_columns();
    $insert = array("mb_id='".sql_real_escape_string($mb_id)."'");
    $update = array();
    foreach ($values as $column => $value) {
        if (!isset($columns[$column])) continue;
        $insert[] = "{$column}='".(int) $value."'";
        $update[] = "{$column}=VALUES({$column})";
    }
    $insert[] = "notify_updated_at='".sql_real_escape_string($now)."'";
    $insert[] = "notify_ip='".sql_real_escape_string($ip)."'";
    $update[] = 'notify_updated_at=VALUES(notify_updated_at)';
    $update[] = 'notify_ip=VALUES(notify_ip)';
    $saved = sql_query("INSERT INTO rb_notification_preference SET ".implode(', ', $insert)
        ." ON DUPLICATE KEY UPDATE ".implode(', ', $update), false);
    if (!$saved) {
        return false;
    }

    if (!isset($GLOBALS['rb_notification_preference_cache']) || !is_array($GLOBALS['rb_notification_preference_cache'])) {
        $GLOBALS['rb_notification_preference_cache'] = array();
    }
    $GLOBALS['rb_notification_preference_cache'][$mb_id] = array_merge($values, array(
        'notify_updated_at' => $now,
        'is_saved' => 1,
    ));
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

function rb_notification_preference_key($category, $title = '', $source_type = '')
{
    $category = strtolower(trim((string) $category));
    $source_type = strtolower(trim((string) $source_type));
    if ($category === 'shop') return 'notify_shop';
    if ($category === 'subscribe') return 'notify_subscribe';
    if ($category === 'other') return 'notify_other';
    if ($category !== 'board') return '';

    if ($source_type === 'comment_reply'
        || strpos((string) $title, '댓글에 댓글') !== false) {
        return 'notify_comment_reply';
    }
    if (in_array($source_type, array('reply', 'board_reply'), true)) {
        return 'notify_reply';
    }
    if (in_array($source_type, array('comment', 'board_comment'), true)
        || strpos((string) $title, '댓글') !== false) {
        return 'notify_comment';
    }
    return 'notify_other';
}

function rb_notification_site_type_allowed($mb_id, $category, $title = '', $source_type = '')
{
    if ($category === 'notice') {
        return true;
    }
    $preference = rb_notification_get_preference($mb_id);
    if (empty($preference['notify_site'])) {
        return false;
    }
    $key = rb_notification_preference_key($category, $title, $source_type);
    return $key === '' || !empty($preference[$key]);
}

function rb_notification_board_config_table_ready()
{
    return rb_notification_database_table_exists('rb_notification_board_config');
}

function rb_notification_board_push_site_enabled($bo_table)
{
    $bo_table = trim((string) $bo_table);
    if ($bo_table === '' || !rb_notification_board_config_table_ready()) {
        return true;
    }
    $row = sql_fetch("SELECT notify_push_site FROM rb_notification_board_config
                       WHERE bo_table='".sql_real_escape_string($bo_table)."' LIMIT 1", false);
    return !isset($row['notify_push_site']) || !empty($row['notify_push_site']);
}

function rb_notification_board_table_from_link($link_url)
{
    $query = parse_url(html_entity_decode((string) $link_url, ENT_QUOTES, 'UTF-8'), PHP_URL_QUERY);
    if (!$query) return '';
    parse_str($query, $params);
    $bo_table = isset($params['bo_table']) ? trim((string) $params['bo_table']) : '';
    return preg_match('/^[A-Za-z0-9_]{1,20}$/', $bo_table) ? $bo_table : '';
}

function rb_notification_admin_board_form_update($bo_table, $w)
{
    global $g5;
    if (!rb_notification_board_config_table_ready()) return;

    $enabled = isset($_POST['rb_notify_push_site']) && (string) $_POST['rb_notify_push_site'] === '1' ? 1 : 0;
    $targets = array($bo_table);
    if (!empty($_POST['chk_all_rb_notify_push_site'])) {
        $targets = array();
        $result = sql_query("SELECT bo_table FROM {$g5['board_table']}", false);
        while ($result && $row = sql_fetch_array($result)) $targets[] = $row['bo_table'];
    } elseif (!empty($_POST['chk_grp_rb_notify_push_site'])) {
        $board = sql_fetch("SELECT gr_id FROM {$g5['board_table']} WHERE bo_table='".sql_real_escape_string($bo_table)."'", false);
        $targets = array();
        $result = sql_query("SELECT bo_table FROM {$g5['board_table']} WHERE gr_id='".sql_real_escape_string(isset($board['gr_id']) ? $board['gr_id'] : '')."'", false);
        while ($result && $row = sql_fetch_array($result)) $targets[] = $row['bo_table'];
    }

    foreach (array_unique(array_filter($targets)) as $target) {
        sql_query("INSERT INTO rb_notification_board_config SET
                    bo_table='".sql_real_escape_string($target)."',
                    notify_push_site='{$enabled}',
                    notify_updated_at='".sql_real_escape_string(G5_TIME_YMDHIS)."'
                   ON DUPLICATE KEY UPDATE
                    notify_push_site=VALUES(notify_push_site),
                    notify_updated_at=VALUES(notify_updated_at)", false);
    }
}
add_event('admin_board_form_update', 'rb_notification_admin_board_form_update', G5_HOOK_DEFAULT_PRIORITY, 2);

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

function rb_notification_enabled_categories()
{
    global $rb_builder;

    $categories = rb_notification_categories();
    if (!is_array($rb_builder) || !array_key_exists('bu_notification_visible_categories', $rb_builder)) {
        return $categories;
    }

    $saved = trim((string) $rb_builder['bu_notification_visible_categories']);
    if ($saved === '') {
        return array();
    }

    $saved_keys = preg_split('/[\s,]+/', $saved, -1, PREG_SPLIT_NO_EMPTY);
    $enabled = array();
    foreach ($categories as $key => $label) {
        if (in_array($key, $saved_keys, true)) {
            $enabled[$key] = $label;
        }
    }
    return $enabled;
}

function rb_notification_available_categories()
{
    $categories = rb_notification_categories();

    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) {
        unset($categories['shop']);
    }
    if (empty($GLOBALS['sb']['sb_use']) || (int) $GLOBALS['sb']['sb_use'] !== 1) {
        unset($categories['subscribe']);
    }

    return $categories;
}

function rb_notification_visible_categories()
{
    return array_intersect_key(
        rb_notification_available_categories(),
        rb_notification_enabled_categories()
    );
}

function rb_notification_visible_category_sql($column = 'noti_category')
{
    $column = (string) $column;
    if (!preg_match('/^[A-Za-z0-9_.]+$/', $column)) {
        $column = 'noti_category';
    }

    $keys = array_keys(rb_notification_visible_categories());
    if (!$keys) {
        return '1=0';
    }
    $quoted = array();
    foreach ($keys as $key) {
        $quoted[] = "'".sql_real_escape_string($key)."'";
    }
    return $column.' IN ('.implode(', ', $quoted).')';
}

function rb_notification_unread_count($mb_id)
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '' || !rb_notification_table_ready()) {
        return 0;
    }
    $category_sql = rb_notification_visible_category_sql();

    $row = sql_fetch("SELECT COUNT(*) AS cnt
                        FROM rb_notification
                       WHERE noti_recv_mb_id='".sql_real_escape_string($mb_id)."'
                         AND noti_read_at IS NULL
                         AND {$category_sql}", false);
    return isset($row['cnt']) ? (int) $row['cnt'] : 0;
}

function rb_notification_member_item($row)
{
    if (empty($row['noti_id'])) {
        return array();
    }

    $categories = rb_notification_categories();
    $category = isset($row['noti_category']) ? (string) $row['noti_category'] : 'other';
    if (!isset($categories[$category])) {
        $category = 'other';
    }

    return array(
        'id' => (int) $row['noti_id'],
        'category' => $category,
        'category_label' => $categories[$category],
        'title' => isset($row['noti_title']) ? (string) $row['noti_title'] : '',
        'content' => isset($row['noti_content']) ? (string) $row['noti_content'] : '',
        'url' => isset($row['noti_link']) ? (string) $row['noti_link'] : '',
        'read_at' => isset($row['noti_read_at']) ? (string) $row['noti_read_at'] : '',
        'created_at' => isset($row['noti_created_at']) ? (string) $row['noti_created_at'] : '',
        'is_read' => !empty($row['noti_read_at']) ? 1 : 0,
    );
}

function rb_notification_member_list($mb_id, $category = 'all', $limit = 50)
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '' || !rb_notification_table_ready()) {
        return array();
    }

    $categories = rb_notification_visible_categories();
    if (!$categories) {
        return array();
    }
    $category = strtolower(trim((string) $category));
    if ($category !== 'all' && !isset($categories[$category])) {
        return array();
    }
    $category_sql = $category === 'all'
        ? ' AND '.rb_notification_visible_category_sql()
        : " AND noti_category='".sql_real_escape_string($category)."'";
    $limit = max(1, min(100, (int) $limit));

    $items = array();
    $result = sql_query("SELECT noti_id, noti_category, noti_title, noti_content, noti_link,
                                noti_read_at, noti_created_at
                           FROM rb_notification
                          WHERE noti_recv_mb_id='".sql_real_escape_string($mb_id)."'"
                          .$category_sql."
                          ORDER BY noti_id DESC
                          LIMIT {$limit}", false);
    while ($result && $row = sql_fetch_array($result)) {
        $items[] = rb_notification_member_item($row);
    }
    return $items;
}

function rb_notification_member_get($mb_id, $notification_id, $mark_read = false)
{
    $mb_id = trim((string) $mb_id);
    $notification_id = (int) $notification_id;
    if ($mb_id === '' || $notification_id < 1 || !rb_notification_table_ready()) {
        return array();
    }

    $escaped_mb_id = sql_real_escape_string($mb_id);
    $category_sql = rb_notification_visible_category_sql();
    if ($category_sql === '1=0') {
        return array();
    }
    if ($mark_read) {
        sql_query("UPDATE rb_notification
                      SET noti_read_at='".sql_real_escape_string(G5_TIME_YMDHIS)."'
                    WHERE noti_id='{$notification_id}'
                      AND noti_recv_mb_id='{$escaped_mb_id}'
                      AND {$category_sql}
                      AND noti_read_at IS NULL", false);
    }

    $row = sql_fetch("SELECT noti_id, noti_category, noti_title, noti_content, noti_link,
                             noti_read_at, noti_created_at
                        FROM rb_notification
                       WHERE noti_id='{$notification_id}'
                         AND noti_recv_mb_id='{$escaped_mb_id}'
                         AND {$category_sql}
                       LIMIT 1", false);
    return rb_notification_member_item($row);
}

function rb_notification_member_delete($mb_id, $notification_id)
{
    $mb_id = trim((string) $mb_id);
    $notification_id = (int) $notification_id;
    if ($mb_id === '' || $notification_id < 1 || !rb_notification_table_ready()) {
        return false;
    }

    return (bool) sql_query("DELETE FROM rb_notification
                              WHERE noti_id='{$notification_id}'
                                AND noti_recv_mb_id='".sql_real_escape_string($mb_id)."'", false);
}

function rb_notification_member_delete_all($mb_id)
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '' || !rb_notification_table_ready()) {
        return false;
    }

    return (bool) sql_query("DELETE FROM rb_notification
                              WHERE noti_recv_mb_id='".sql_real_escape_string($mb_id)."'", false);
}

function rb_notification_action_token()
{
    $token = trim((string) get_session('ss_rb_notification_action_token'));
    if ($token === '') {
        $token = get_random_token_string(32);
        set_session('ss_rb_notification_action_token', $token);
    }
    return $token;
}

function rb_notification_action_token_valid($token)
{
    $saved = trim((string) get_session('ss_rb_notification_action_token'));
    $token = trim((string) $token);
    if ($saved === '' || $token === '') {
        return false;
    }
    return function_exists('hash_equals') ? hash_equals($saved, $token) : $saved === $token;
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
    $admin_system_force = ($recv_id === (string) $config['cf_admin'] && $send_id === 'system-msg');

    $board_table = isset($options['board_table']) ? trim((string) $options['board_table']) : '';
    if ($board_table === '') {
        $board_table = rb_notification_board_table_from_link($link_url);
    }
    if ($category !== 'notice' && $board_table !== '' && !rb_notification_board_push_site_enabled($board_table)) {
        return false;
    }

    $noti_id = 0;
    if ($admin_system_force || rb_notification_site_type_allowed($recv_id, $category, $title, $source_type)) {
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
    $preference_force = ($category === 'notice' || $admin_system_force);
    $app_push = ($preference_force || rb_notification_push_allowed($recv_id))
        && (!isset($options['push']) || (bool) $options['push'])
        && (!isset($options['app_push']) || (bool) $options['app_push']);
    $labels = rb_notification_categories();
    $push_title = isset($labels[$category]) ? $labels[$category] : '기타';

    if ($pwa_push && function_exists('send_pwa_if_needed')) {
        send_pwa_if_needed($recv_id, $send_id, $push_title, $link_url, $title);
    }
    if ($app_push
        && isset($app['ap_title'], $app['ap_key'], $app['ap_pid'])
        && $app['ap_title'] && $app['ap_key'] && $app['ap_pid']) {
        rb_notification_send_app_push($recv_id, $push_title, $title, $app['ap_key'], $preference_force);
    }
    return $noti_id;
}

/** 기존 호출부와 설치된 부가기능의 호환성을 유지하는 이름입니다. 실제로는 쪽지를 만들지 않습니다. */
function memo_auto_send($title, $link_url, $recv_id, $send_id, $category = '', $options = array())
{
    $category = rb_notification_normalize_category($category, $title, $link_url);
    return rb_notification_send($category, $title, $title, $link_url, $recv_id, $send_id, $options);
}

/** 게시판 답글 등록 시 답글 대상 회원에게 알림을 보냅니다. */
function rb_notification_board_reply_after($board, $wr_id, $w, $qstr, $redirect_url)
{
    global $config, $member, $wr;

    if ($w !== 'r' || empty($board['bo_table']) || !$wr_id || empty($wr['mb_id'])) {
        return;
    }

    $recv_id = trim((string) $wr['mb_id']);
    $send_id = !empty($member['mb_id']) ? trim((string) $member['mb_id']) : 'system-msg';
    if ($recv_id === '' || ($send_id !== 'system-msg' && $recv_id === $send_id)) {
        return;
    }

    $bo_table = trim((string) $board['bo_table']);
    if (!preg_match('/^[A-Za-z0-9_]{1,20}$/', $bo_table)) {
        return;
    }

    $source_id = $bo_table.':'.(int) $wr_id;
    if (rb_notification_table_ready()) {
        $duplicate = sql_fetch("SELECT noti_id FROM rb_notification
                                 WHERE noti_recv_mb_id='".sql_real_escape_string($recv_id)."'
                                   AND noti_source_type='board_reply'
                                   AND noti_source_id='".sql_real_escape_string($source_id)."'
                                 LIMIT 1", false);
        if (!empty($duplicate['noti_id'])) {
            return;
        }
    }

    $title = (isset($board['bo_subject']) ? (string) $board['bo_subject'] : '게시판')
        .'의 게시물에 답글이 등록되었습니다.';
    $link_url = G5_BBS_URL.'/board.php?bo_table='.urlencode($bo_table).'&wr_id='.(int) $wr_id;

    rb_notification_send('board', $title, $title, $link_url, $recv_id, $send_id, array(
        'source_type' => 'board_reply',
        'source_id' => $source_id,
        'board_table' => $bo_table,
    ));
}
add_event('write_update_after', 'rb_notification_board_reply_after', G5_HOOK_DEFAULT_PRIORITY + 10, 5);

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
    function sendPushNotificationAsync($tokens, $title, $body, $json_key_file_path, $force = false)
    {
        $tokens = rb_notification_filter_push_tokens($tokens, $force);
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

function rb_notification_filter_push_tokens($tokens, $force = false)
{
    $tokens = array_values(array_unique(array_filter(array_map('trim', (array) $tokens))));
    if ($force || !$tokens || !rb_notification_database_table_exists('rb_app_token')) {
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
        if (isset($token_members[$token])
            && $token_members[$token] !== ''
            && rb_notification_push_allowed($token_members[$token])) {
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

function rb_notification_send_app_push($recv_id, $push_title, $body, $api_key, $force = false)
{
    global $app, $config;
    if ($recv_id === '') {
        return;
    }
    if (!$force && !rb_notification_push_allowed($recv_id)) return;
    if ($recv_id === (string) $config['cf_admin'] && empty($app['ap_systems_msg'])) return;
    $tokens = get_user_tokens($recv_id);
    if (!$tokens) {
        return;
    }
    sendPushNotificationAsync($tokens, $push_title, $body, G5_DATA_PATH.'/push/'.basename((string) $api_key), $force);
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
        || !rb_notification_board_push_site_enabled(isset($board['bo_table']) ? $board['bo_table'] : '')
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
                'board_table' => $board['bo_table'],
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
