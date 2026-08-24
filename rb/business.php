<?php
include_once('../common.php');
include_once(G5_PATH.'/rb/rb.console/console.lib.php');

if (!$member['mb_id']) alert('회원 로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/rb/business.php'));
$console_config = rb_console_config();
if (empty($console_config['bc_enabled']) && $is_admin !== 'super') alert('현재 비즈니스 콘솔을 이용할 수 없습니다.', G5_URL);

define('RB_BUSINESS_CONSOLE', true);
$rb_console_context = array('member' => $member, 'config' => $console_config);
$rb_console_registry = rb_console_visible_registry(rb_console_registry($rb_console_context), $rb_console_context);
$route = isset($_GET['route']) ? preg_replace('/[^a-z0-9._-]/i', '', $_GET['route']) : '';
if ($route === '') $route = preg_replace('/[^a-z0-9._-]/i', '', $console_config['bc_default_route']);
if (!isset($rb_console_registry[$route])) $route = isset($rb_console_registry['dashboard']) ? 'dashboard' : key($rb_console_registry);
if (!$route || !isset($rb_console_registry[$route])) alert('이용 가능한 비즈니스 메뉴가 없습니다.', G5_URL);
rb_console_alert_output_start();
$rb_console_current = $rb_console_registry[$route];
$rb_console_context['route'] = $route;
$rb_console_context['current'] = $rb_console_current;
$rb_console_context['registry'] = $rb_console_registry;
$g5['title'] = $rb_console_current['title'];
include_once(G5_PATH.'/rb/rb.console/console.head.php');
if ($route !== 'dashboard') echo '<div class="container_wr">';
call_user_func($rb_console_current['callback'], $rb_console_context);
if ($route !== 'dashboard') echo '</div>';
include_once(G5_PATH.'/rb/rb.console/console.tail.php');
rb_console_alert_output_finish();
