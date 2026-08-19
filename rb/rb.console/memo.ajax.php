<?php
include_once('../../common.php');
include_once(G5_PATH.'/rb/rb.console/console.lib.php');

header('Content-Type: application/json; charset=utf-8');

function rb_console_memo_response($result, $data = array(), $status = 200)
{
    if (function_exists('http_response_code')) http_response_code((int) $status);
    echo json_encode(array_merge(array('result' => $result), (array) $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function rb_console_memo_file($mb_id)
{
    $dir = G5_DATA_PATH.'/rb_console_memo';
    if (!is_dir($dir) && !@mkdir($dir, G5_DIR_PERMISSION, true)) return '';
    if (!is_dir($dir) || !is_writable($dir)) return '';
    return $dir.'/'.hash('sha256', (string) $mb_id).'.json';
}

function rb_console_memo_load($file)
{
    if ($file === '' || !is_file($file)) return array();
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return array();
    $list = json_decode($raw, true);
    if (!is_array($list)) return array();
    $safe = array();
    foreach ($list as $item) {
        if (!is_array($item) || empty($item['id']) || !isset($item['content'])) continue;
        $safe[] = array(
            'id' => preg_replace('/[^a-f0-9]/i', '', (string) $item['id']),
            'created_at' => isset($item['created_at']) ? (string) $item['created_at'] : '',
            'content' => (string) $item['content']
        );
    }
    return array_slice($safe, 0, 20);
}

function rb_console_memo_save($file, $list)
{
    $json = json_encode(array_values((array) $list), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return $json !== false && @file_put_contents($file, $json, LOCK_EX) !== false;
}

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
    rb_console_memo_response('error', array('message' => '허용되지 않은 요청입니다.'), 405);
}
if (empty($member['mb_id']) || !rb_console_can('console.access')) {
    rb_console_memo_response('error', array('message' => '비즈니스 콘솔 이용 권한이 없습니다.'), 403);
}
$console_config = rb_console_config();
if (empty($console_config['bc_enabled']) && $is_admin !== 'super') {
    rb_console_memo_response('error', array('message' => '현재 비즈니스 콘솔을 이용할 수 없습니다.'), 403);
}
if (!rb_console_check_token(false)) {
    rb_console_memo_response('error', array('message' => '요청이 만료되었습니다. 화면을 새로고침해 주세요.'), 403);
}

$file = rb_console_memo_file($member['mb_id']);
if ($file === '') rb_console_memo_response('error', array('message' => '메모 저장공간을 준비할 수 없습니다.'), 500);

$mode = isset($_POST['mode']) ? trim((string) $_POST['mode']) : 'list';
$list = rb_console_memo_load($file);

if ($mode === 'list') rb_console_memo_response('ok', array('list' => $list));

if ($mode === 'add') {
    $content = isset($_POST['content']) ? trim(strip_tags((string) $_POST['content'])) : '';
    $content = preg_replace('/\s+/u', ' ', $content);
    if ($content === '') rb_console_memo_response('error', array('message' => '메모 내용을 입력해 주세요.'), 422);
    if (function_exists('mb_substr')) $content = mb_substr($content, 0, 200, 'UTF-8');
    else $content = substr($content, 0, 600);
    $id = '';
    if (function_exists('random_bytes')) {
        try {
            $id = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            $id = '';
        }
    }
    if ($id === '') $id = substr(md5(uniqid(mt_rand(), true)), 0, 16);
    array_unshift($list, array('id' => $id, 'created_at' => G5_TIME_YMDHIS, 'content' => $content));
    $list = array_slice($list, 0, 20);
    if (!rb_console_memo_save($file, $list)) rb_console_memo_response('error', array('message' => '메모를 저장하지 못했습니다.'), 500);
    rb_console_memo_response('ok', array('list' => $list));
}

if ($mode === 'delete') {
    $id = isset($_POST['id']) ? preg_replace('/[^a-f0-9]/i', '', (string) $_POST['id']) : '';
    if ($id === '') rb_console_memo_response('error', array('message' => '삭제할 메모를 찾을 수 없습니다.'), 422);
    $next = array();
    foreach ($list as $item) {
        $matches = function_exists('hash_equals') ? hash_equals((string) $item['id'], $id) : ((string) $item['id'] === $id);
        if (!$matches) $next[] = $item;
    }
    if (!rb_console_memo_save($file, $next)) rb_console_memo_response('error', array('message' => '메모를 삭제하지 못했습니다.'), 500);
    rb_console_memo_response('ok', array('list' => $next));
}

rb_console_memo_response('error', array('message' => '지원하지 않는 요청입니다.'), 400);
