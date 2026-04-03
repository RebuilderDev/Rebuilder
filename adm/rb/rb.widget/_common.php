<?php
// 관리자 컨텍스트 세팅 (위젯 API 전용)
define('G5_IS_ADMIN', true);

// /public_html/common.php 로드
$root_common = dirname(__DIR__, 3) . '/common.php';
if (!is_file($root_common)) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode([
        'ok' => false,
        'where' => __FILE__,
        'msg' => 'root common.php not found',
        'expected' => $root_common
    ]);
    exit;
}
require_once $root_common;

// 관리자 라이브러리
require_once G5_ADMIN_PATH . '/admin.lib.php';

// 이벤트
if (function_exists('run_event')) run_event('admin_common');
