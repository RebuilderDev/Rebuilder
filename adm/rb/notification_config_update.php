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

$config_columns = array(
    'bu_notification_floating_use' => "tinyint(1) NOT NULL DEFAULT '1'",
    'bu_notification_floating_position' => "varchar(20) NOT NULL DEFAULT 'left_bottom'",
    'bu_notification_floating_offset' => "int(11) NOT NULL DEFAULT '50'",
    'bu_notification_visible_categories' => "varchar(100) NOT NULL DEFAULT 'board,shop,subscribe,notice,other'",
);
foreach ($config_columns as $column_name => $column_definition) {
    $column = sql_fetch("SHOW COLUMNS FROM rb_builder LIKE '{$column_name}'", false);
    if (empty($column['Field'])) {
        sql_query("ALTER TABLE rb_builder ADD `{$column_name}` {$column_definition}", false);
        $column = sql_fetch("SHOW COLUMNS FROM rb_builder LIKE '{$column_name}'", false);
        if (empty($column['Field'])) {
            alert('알림 설정 항목을 추가하지 못했습니다. DB 권한을 확인해 주세요.');
        }
    }
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

$floating_use = isset($_POST['notification_floating_use']) && (string) $_POST['notification_floating_use'] === '0' ? 0 : 1;
$floating_position = isset($_POST['notification_floating_position'])
    ? trim((string) $_POST['notification_floating_position'])
    : 'left_bottom';
$allowed_floating_positions = array('left_top', 'left_bottom', 'right_top', 'right_bottom', 'center');
if (!in_array($floating_position, $allowed_floating_positions, true)) {
    alert('플로팅 알림 위치가 올바르지 않습니다.');
}
$floating_offset = isset($_POST['notification_floating_offset'])
    ? (int) $_POST['notification_floating_offset']
    : 50;
if ($floating_position !== 'center' && ($floating_offset < 0 || $floating_offset > 1000)) {
    alert('플로팅 알림 간격은 0px부터 1000px까지 설정할 수 있습니다.');
}
$floating_offset = max(0, min(1000, $floating_offset));

$all_categories = function_exists('rb_notification_categories')
    ? rb_notification_categories()
    : array('board' => '게시물', 'shop' => '쇼핑', 'subscribe' => '구독', 'notice' => '공지', 'other' => '기타');
$available_categories = function_exists('rb_notification_available_categories')
    ? rb_notification_available_categories()
    : $all_categories;
$current_enabled_categories = function_exists('rb_notification_enabled_categories')
    ? rb_notification_enabled_categories()
    : $all_categories;
$posted_category_keys = array();
foreach ((array) ($_POST['notification_categories'] ?? array()) as $category_key) {
    if (is_string($category_key) || is_int($category_key)) {
        $posted_category_keys[(string) $category_key] = true;
    }
}
$enabled_category_keys = array();
foreach ($all_categories as $category_key => $category_label) {
    if (isset($available_categories[$category_key])) {
        if (isset($posted_category_keys[$category_key])) {
            $enabled_category_keys[] = $category_key;
        }
    } elseif (isset($current_enabled_categories[$category_key])) {
        // 현재 사용할 수 없는 기능의 기존 선택값은 임의로 지우지 않습니다.
        $enabled_category_keys[] = $category_key;
    }
}
$visible_categories = implode(',', $enabled_category_keys);
$visible_categories_sql = sql_real_escape_string($visible_categories);

$row_count = sql_fetch("SELECT COUNT(*) AS cnt FROM rb_builder", false);
if (!empty($row_count['cnt'])) {
    $saved = sql_query("UPDATE rb_builder
                           SET bu_notification_retention_days='{$days}',
                               bu_notification_polling_seconds='{$polling_seconds}',
                               bu_notification_floating_use='{$floating_use}',
                               bu_notification_floating_position='{$floating_position}',
                               bu_notification_floating_offset='{$floating_offset}',
                               bu_notification_visible_categories='{$visible_categories_sql}'", false);
} else {
    $saved = sql_query("INSERT INTO rb_builder SET
                           bu_viewport='0.9',
                           bu_notification_retention_days='{$days}',
                           bu_notification_polling_seconds='{$polling_seconds}',
                           bu_notification_floating_use='{$floating_use}',
                           bu_notification_floating_position='{$floating_position}',
                           bu_notification_floating_offset='{$floating_offset}',
                           bu_notification_visible_categories='{$visible_categories_sql}',
                           bu_datetime='".sql_real_escape_string(G5_TIME_YMDHIS)."'", false);
}
if (!$saved) {
    alert('알림 설정을 저장하지 못했습니다. 빌더설정 > DB업데이트를 확인해 주세요.');
}

$stored = sql_fetch("SELECT bu_notification_retention_days, bu_notification_polling_seconds,
                            bu_notification_floating_use, bu_notification_floating_position,
                            bu_notification_floating_offset, bu_notification_visible_categories
                       FROM rb_builder
                      LIMIT 1", false);
if (!isset($stored['bu_notification_retention_days'], $stored['bu_notification_polling_seconds'],
           $stored['bu_notification_floating_use'], $stored['bu_notification_floating_position'],
           $stored['bu_notification_floating_offset'], $stored['bu_notification_visible_categories'])
    || (int) $stored['bu_notification_retention_days'] !== $days
    || (int) $stored['bu_notification_polling_seconds'] !== $polling_seconds
    || (int) $stored['bu_notification_floating_use'] !== $floating_use
    || (string) $stored['bu_notification_floating_position'] !== $floating_position
    || (int) $stored['bu_notification_floating_offset'] !== $floating_offset
    || (string) $stored['bu_notification_visible_categories'] !== $visible_categories) {
    alert('알림 설정이 정상적으로 저장되지 않았습니다. 빌더설정 > DB업데이트를 확인해 주세요.');
}
if (function_exists('rb_notification_cleanup_expired')) {
    $rb_builder['bu_notification_retention_days'] = $days;
    $rb_builder['bu_notification_polling_seconds'] = $polling_seconds;
    $rb_builder['bu_notification_floating_use'] = $floating_use;
    $rb_builder['bu_notification_floating_position'] = $floating_position;
    $rb_builder['bu_notification_floating_offset'] = $floating_offset;
    $rb_builder['bu_notification_visible_categories'] = $visible_categories;
    rb_notification_cleanup_expired();
}

alert('알림 설정을 저장했습니다.', './notification_form.php');
