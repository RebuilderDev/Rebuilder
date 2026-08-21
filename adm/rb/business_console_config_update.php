<?php
$sub_menu = '000999';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') alert('최고관리자만 접근할 수 있습니다.');
check_demo();
check_admin_token();
include_once(G5_PATH.'/rb/rb.console/console.lib.php');
if (!rb_console_table_exists('rb_business_console_config')) alert('빌더설정 > DB업데이트를 먼저 실행해 주세요.', './business_console_config.php');
$name = trim(isset($_POST['bc_name']) ? $_POST['bc_name'] : '');
if ($name === '') alert('콘솔 이름을 입력해 주세요.');
$route = preg_replace('/[^a-z0-9._-]/i', '', isset($_POST['bc_default_route']) ? $_POST['bc_default_route'] : 'dashboard');
$policy = isset($_POST['bc_partner_policy']) && in_array($_POST['bc_partner_policy'], array('approved','all'), true) ? $_POST['bc_partner_policy'] : 'all';
$support_url = trim(isset($_POST['bc_support_url']) ? $_POST['bc_support_url'] : '');
if ($support_url !== '' && (!filter_var($support_url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($support_url, PHP_URL_SCHEME)), array('http','https'), true))) alert('고객지원 URL은 http 또는 https 주소로 입력해 주세요.');
$sql = "INSERT INTO rb_business_console_config SET bc_id=1, bc_enabled='".(empty($_POST['bc_enabled']) ? 0 : 1)."', bc_name='".sql_real_escape_string($name)."', bc_default_route='".sql_real_escape_string($route)."', bc_min_level='".max(1, min(10, (int) $_POST['bc_min_level']))."', bc_show_point='".(empty($_POST['bc_show_point']) ? 0 : 1)."', bc_partner_policy='".sql_real_escape_string($policy)."', bc_support_url='".sql_real_escape_string($support_url)."', bc_notice='".sql_real_escape_string(trim(isset($_POST['bc_notice']) ? $_POST['bc_notice'] : ''))."', bc_updated_at='".G5_TIME_YMDHIS."' ON DUPLICATE KEY UPDATE bc_enabled=VALUES(bc_enabled), bc_name=VALUES(bc_name), bc_default_route=VALUES(bc_default_route), bc_min_level=VALUES(bc_min_level), bc_show_point=VALUES(bc_show_point), bc_partner_policy=VALUES(bc_partner_policy), bc_support_url=VALUES(bc_support_url), bc_notice=VALUES(bc_notice), bc_updated_at=VALUES(bc_updated_at)";
sql_query($sql);
goto_url('./business_console_config.php');
