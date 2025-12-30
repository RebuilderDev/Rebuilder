<?php
include_once(dirname(__FILE__).'/../../../common.php');
if (!defined('_GNUBOARD_')) exit;

$tbl = RB_TABLE_PREFIX . 'aos';

// // 안전 출력
function rb_aos_js_exit($configs) {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    echo "window.RB_AOS_CONFIGS = " . json_encode($configs, JSON_UNESCAPED_UNICODE) . ";\n";
    exit;
}

// // 2타입 기본(없어도 에러 X)
$configs = array(
    'general' => array('use' => 0),
    'market'  => array('use' => 0)
);

// // 테이블 없으면 그대로 종료
$tbl_esc = sql_real_escape_string($tbl);
$row_tbl = sql_fetch("SHOW TABLES LIKE '{$tbl_esc}'", false);
if (!$row_tbl) rb_aos_js_exit($configs);

// // 로드
$res = sql_query("SELECT * FROM `{$tbl}` WHERE ra_type IN ('general','market')", false);
while ($r = sql_fetch_array($res)) {
    $type = isset($r['ra_type']) ? trim((string)$r['ra_type']) : '';
    if ($type !== 'general' && $type !== 'market') continue;

    $configs[$type] = array(
        'use' => isset($r['ra_use']) ? (int)$r['ra_use'] : 0,
        'aos' => isset($r['ra_aos']) ? (string)$r['ra_aos'] : null,
        'offset' => isset($r['ra_offset']) ? (is_null($r['ra_offset']) ? null : (int)$r['ra_offset']) : null,
        'delay' => isset($r['ra_delay']) ? (is_null($r['ra_delay']) ? null : (int)$r['ra_delay']) : null,
        'duration' => isset($r['ra_duration']) ? (is_null($r['ra_duration']) ? null : (int)$r['ra_duration']) : null,
        'easing' => isset($r['ra_easing']) ? (string)$r['ra_easing'] : null,
        'mirror' => isset($r['ra_mirror']) ? ((int)$r['ra_mirror'] === 1 ? 'true' : 'false') : null,
        'once' => isset($r['ra_once']) ? ((int)$r['ra_once'] === 1 ? 'true' : 'false') : null,
        'anchorPlacement' => isset($r['ra_anchor_placement']) ? (string)$r['ra_anchor_placement'] : null
    );
}

rb_aos_js_exit($configs);
