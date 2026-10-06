<?php
include_once('../../common.php');
require_once G5_PATH.'/rb/rb.lib/rb_push_click.lib.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$kind = isset($_GET['kind']) && is_string($_GET['kind']) ? $_GET['kind'] : '';
$id = isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;
$token = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
if (!in_array($kind, array('notification', 'memo'), true) || $id < 1 || !preg_match('/^[a-f0-9]{64}$/', $token)) goto_url(G5_URL);

if (empty($member['mb_id'])) {
    $resume = G5_URL.'/rb/rb.lib/push.open.php?kind='.$kind.'&id='.$id.'&token='.$token;
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode($resume));
}

$target = '';
try {
    $target = rb_push_click_read($kind, $id, $token, (string) $member['mb_id']);
} catch (Exception $e) {
    error_log('[RB PUSH CLICK] READ_FAILED');
} catch (Throwable $e) {
    error_log('[RB PUSH CLICK] READ_FAILED');
}
goto_url($target !== '' ? $target : G5_URL);
