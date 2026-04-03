<?php
// ====== 안전부팅 & 에러를 JSON으로 강제 변환 ======
// @ini_set('display_errors', '0');
// @ini_set('html_errors', '0');
// error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function($e){
    http_response_code(500);
    echo json_encode(array(
        'ok'   => false,
        'type' => 'exception',
        'msg'  => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ));
    exit;
});

set_error_handler(function($no,$str,$file,$line){
    http_response_code(500);
    echo json_encode(array(
        'ok'   => false,
        'type' => 'error',
        'no'   => $no,
        'msg'  => $str,
        'file' => $file,
        'line' => $line
    ));
    exit;
});

register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR))) {
        http_response_code(500);
        echo json_encode(array(
            'ok'   => false,
            'type' => 'fatal',
            'msg'  => $e['message'],
            'file' => $e['file'],
            'line' => $e['line']
        ));
    }
});

// ====== 관리자 컨텍스트 로드 (전용 _common) ======
$local_common = __DIR__ . '/_common.php';
if (!is_file($local_common)) {
    throw new Exception("_common.php not found: {$local_common}");
}
require_once $local_common;

// ====== 권한 체크 ======
if (empty($is_admin)) {
    echo json_encode(array('ok'=>false,'msg'=>'편집 권한이 없습니다.'));
    exit;
}

// ====== 유틸 ======
function rbw_sql_esc($s){
    return function_exists('sql_escape_string') ? sql_escape_string($s) : addslashes($s);
}

function rbw_ensure_widget_schema() {
    static $done = false;
    if ($done) return;
    $done = true;

    $col = sql_fetch("SHOW COLUMNS FROM rb_admin_widget LIKE 'aw_span'");
    if (!(isset($col['Field']) && $col['Field'] === 'aw_span')) {
        sql_query("ALTER TABLE rb_admin_widget ADD aw_span TINYINT(1) NOT NULL DEFAULT 1 AFTER aw_area", false);
    }
}

// FormData 파서 (JSON/문자열/배열 다 처리)
function rbw_parse_ids($val){
    if (is_array($val)) {
        $arr = $val;
    } else {
        $s = trim((string)$val);
        if ($s === '') return array();
        $j = json_decode($s, true);
        if (is_array($j)) {
            $arr = $j;
        } else {
            if (preg_match_all('/\d+/', $s, $m)) $arr = $m[0];
            else $arr = array();
        }
    }
    $arr = array_map('intval', $arr);
    // PHP5 호환: 화살표함수 대신 익명함수
    return array_values(array_filter($arr, function($v){ return $v > 0; }));
}

// ====== 액션 스위치 ======
$action = isset($_POST['action']) ? $_POST['action'] : '';
rbw_ensure_widget_schema();

// --- ping (연결 확인용) ---
if ($action === 'ping') {
    echo json_encode(array('ok'=>true,'pong'=>time(),'whoami'=>__FILE__));
    exit;
}

// --- add ---
if ($action === 'add') {
    $key  = preg_replace('~[^a-z0-9_/-]~i','', (string)(isset($_POST['key']) ? $_POST['key'] : ''));
    $area = 'main';
    $span = (int)(isset($_POST['span']) ? $_POST['span'] : 1);
    if ($span < 1) $span = 1;
    if ($span > 4) $span = 4;

    if ($key === '') { echo json_encode(array('ok'=>false,'msg'=>'키(ID)가 누락되었습니다.')); exit; }

    $metaFile   = __DIR__.'/'.$key.'/meta.php';
    $widgetFile = __DIR__.'/'.$key.'/widget.php';
    if (!is_file($metaFile) || !is_file($widgetFile)) {
        echo json_encode(array('ok'=>false,'msg'=>'위젯 파일이 없습니다.','meta'=>$metaFile,'widget'=>$widgetFile)); exit;
    }
    $meta = @include $metaFile;
    if (!is_array($meta) || empty($meta['key'])) { echo json_encode(array('ok'=>false,'msg'=>'메타 정보가 없습니다.')); exit; }
    $row = sql_fetch("SELECT IFNULL(MAX(aw_sort),0)+1 AS ns FROM rb_admin_widget WHERE aw_area='".rbw_sql_esc($area)."' ");
    $ns  = (int)(isset($row['ns']) ? $row['ns'] : 1);

    $q = "
      INSERT INTO rb_admin_widget
      SET aw_key='".rbw_sql_esc($key)."',
          aw_area='".rbw_sql_esc($area)."',
          aw_span='{$span}',
          aw_sort='{$ns}',
          aw_enabled=1,
          aw_conf='',
          aw_created=NOW(),
          aw_updated=NOW()
    ";
    $ok = sql_query($q);
    if (!$ok && function_exists('sql_error')) {
        echo json_encode(array('ok'=>false,'msg'=>'INSERT 실패','sql'=>trim($q),'err'=>sql_error()));
        exit;
    }
    echo json_encode(array('ok'=>true)); exit;
}

// --- sort ---
if ($action === 'sort') {
    $main_ids = rbw_parse_ids(isset($_POST['main']) ? $_POST['main'] : array());
    $all_ids  = array_values(array_unique($main_ids));
    if (!$all_ids) { echo json_encode(array('ok'=>false,'msg'=>'갱신할 항목이 없습니다.')); exit; }

    // before snapshot
    $in_sql = implode(',', $all_ids);
    $before = array();
    $rs = sql_query("SELECT aw_id, aw_area, aw_sort FROM rb_admin_widget WHERE aw_id IN ($in_sql)");
    while($r = sql_fetch_array($rs)){
        $id = (int)$r['aw_id'];
        $before[$id] = array('area'=>$r['aw_area'],'sort'=>(int)$r['aw_sort']);
    }

    // bulk updater
    $bulk = function($ids, $area){
        if (!$ids) return array('sql'=>null,'error'=>null);
        $cases = array(); $in = array(); $i = 1;
        foreach($ids as $id){
            $id = (int)$id;
            $cases[] = "WHEN {$id} THEN {$i}";
            $in[] = $id;
            $i++;
        }
        $case_sql = implode(' ', $cases);
        $in_sql   = implode(',', $in);
        $sql = "
          UPDATE rb_admin_widget
          SET aw_area='".rbw_sql_esc($area)."',
              aw_sort = CASE aw_id {$case_sql} ELSE aw_sort END,
              aw_updated = NOW()
          WHERE aw_id IN ({$in_sql})
        ";
        $ok = sql_query($sql);
        return array('sql'=>$sql,'error'=>($ok?null:(function_exists('sql_error')?sql_error(): 'update failed')));
    };

    $r1 = $bulk($main_ids,'main');
    if ($r1['error']) {
        echo json_encode(array(
            'ok'=>false,
            'msg'=>'UPDATE 오류',
            'main_err'=>$r1['error'],
            'main_sql'=>$r1['sql']
        ));
        exit;
    }

    // after snapshot
    $after = array();
    $rs2 = sql_query("SELECT aw_id, aw_area, aw_sort FROM rb_admin_widget WHERE aw_id IN ($in_sql)");
    while($r = sql_fetch_array($rs2)){
        $id = (int)$r['aw_id'];
        $after[$id] = array('area'=>$r['aw_area'],'sort'=>(int)$r['aw_sort']);
    }

    // diff count
    $changed = 0; $diff = array();
    foreach($all_ids as $id){
        $b = isset($before[$id]) ? $before[$id] : null;
        $a = isset($after[$id]) ? $after[$id] : null;
        if (!$b || !$a) continue;
        if ($b['area'] !== $a['area'] || $b['sort'] !== $a['sort']) {
            $changed++;
            $diff[] = array('aw_id'=>$id,'from'=>$b,'to'=>$a);
        }
    }

    echo json_encode(array('ok'=>true,'changed'=>$changed));
    exit;
}

// --- update_span ---
if ($action === 'update_span') {
    $aw_id = (int)(isset($_POST['aw_id']) ? $_POST['aw_id'] : 0);
    $span = (int)(isset($_POST['span']) ? $_POST['span'] : 1);
    if ($aw_id <= 0) { echo json_encode(array('ok'=>false,'msg'=>'잘못된 ID')); exit; }
    if ($span < 1) $span = 1;
    if ($span > 4) $span = 4;
    $ok = sql_query("UPDATE rb_admin_widget SET aw_span='{$span}', aw_updated=NOW() WHERE aw_id='{$aw_id}'");
    if (!$ok && function_exists('sql_error')) {
        echo json_encode(array('ok'=>false,'msg'=>'UPDATE 실패','err'=>sql_error()));
        exit;
    }
    echo json_encode(array('ok'=>true,'span'=>$span)); exit;
}

// --- remove ---
if ($action === 'remove') {
    $aw_id = (int)(isset($_POST['aw_id']) ? $_POST['aw_id'] : 0);
    if ($aw_id<=0) { echo json_encode(array('ok'=>false,'msg'=>'잘못된 ID')); exit; }
    $ok = sql_query("DELETE FROM rb_admin_widget WHERE aw_id='{$aw_id}'");
    if (!$ok && function_exists('sql_error')) {
        echo json_encode(array('ok'=>false,'msg'=>'DELETE 실패','err'=>sql_error()));
        exit;
    }
    echo json_encode(array('ok'=>true)); exit;
}

// 기본: 핑
echo json_encode(array('ok'=>true,'hello'=>'rb_widget_api','hint'=>'POST action=ping 로 점검 가능'));
