<?php
include_once '../../../common.php';

if (!$is_admin) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('status' => 'error', 'msg' => '권한이 없습니다.'));
    exit;
}

// 테이블별 필터 컬럼 정의 (추가/삭제 용이하도록 배열로 관리)
$io_tables = array(
    array('table' => 'rb_module',        'col' => 'md_theme',   'pk' => 'md_id'),
    array('table' => 'rb_module_shop',   'col' => 'md_theme',   'pk' => 'md_id'),
    array('table' => 'rb_section',       'col' => 'sec_theme',  'pk' => 'sec_id'),
    array('table' => 'rb_section_shop',  'col' => 'sec_theme',  'pk' => 'sec_id'),
    array('table' => 'rb_theme',         'col' => 'theme_key',  'pk' => ''),
    array('table' => 'rb_theme_carousel','col' => 'cf_theme',   'pk' => 'id'),
    array('table' => 'rb_config',        'col' => 'co_theme',   'pk' => 'co_id'),
);

$input = array();

$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

if (strpos($content_type, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) $input = array();
} else {
    $input = $_POST;
}

$mode      = isset($input['mode'])      ? $input['mode']      : '';
$theme_key = isset($input['theme_key']) ? trim($input['theme_key']) : '';

if ($theme_key === '') {
    echo json_encode(array('status' => 'error', 'msg' => 'theme_key 누락'));
    exit;
}

$theme_key_esc = addslashes($theme_key);

// 내보내기
if ($mode === 'export') {
    $export = array();

    foreach ($io_tables as $t) {
        $tname = $t['table'];
        $tcol  = $t['col'];

        $rows = array();

        if ($tcol === '') {
            $sql = "SELECT * FROM `{$tname}`";
        } else {
            $sql = "SELECT * FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'";
        }

        $res = sql_query($sql);

        if ($res) {
            while ($row = sql_fetch_array($res)) {
                if ($t['pk'] !== '') {
                    unset($row[$t['pk']]);
                }
                $rows[] = $row;
            }
        }

        $export[$tname] = $rows;
    }

    $filename = $theme_key . '_' . date('Ymd_His') . '.json';
    $json_out = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_out));
    echo $json_out;
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 충돌 확인
if ($mode === 'check') {
    $has_data = false;

    foreach ($io_tables as $t) {
        $tname = $t['table'];
        $tcol  = $t['col'];

        if ($tcol === '') {
            $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$tname}`");
        } else {
            $row = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'");
        }

        if ($row && (int)$row['cnt'] > 0) {
            $has_data = true;
            break;
        }
    }

    echo json_encode(array('status' => 'success', 'has_data' => $has_data));
    exit;
}

// 불러오기
if ($mode === 'import') {
    $import_data = isset($input['data']) ? $input['data'] : array();

    if (!is_array($import_data) || empty($import_data)) {
        echo json_encode(array('status' => 'error', 'msg' => '데이터 없음'));
        exit;
    }

    $allowed_tables = array();
    $allowed_pks = array();
    foreach ($io_tables as $t) {
        $allowed_tables[$t['table']] = $t['col'];
        $allowed_pks[$t['table']] = $t['pk'];
    }

    foreach ($import_data as $tname => $rows) {
        if (!isset($allowed_tables[$tname])) continue;
        if (!is_array($rows)) continue;

        $tcol = $allowed_tables[$tname];

        // col 없으면 전체 삭제, 있으면 테마키 조건 삭제
        if ($tcol === '') {
            sql_query("DELETE FROM `{$tname}`");
        } else {
            sql_query("DELETE FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'");
        }

        $pk = isset($allowed_pks[$tname]) ? $allowed_pks[$tname] : '';

        $valid_cols = array();
        $col_res = sql_query("SHOW COLUMNS FROM `{$tname}`");
        while ($col_row = sql_fetch_array($col_res)) {
            $valid_cols[] = $col_row['Field'];
        }

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row)) continue;
            if ($pk !== '') unset($row[$pk]);

            $cols = array();
            $vals = array();

            foreach ($row as $k => $v) {
                if (!in_array($k, $valid_cols)) continue;
                $cols[] = '`' . addslashes($k) . '`';
                $vals[] = "'" . addslashes((string)$v) . "'";
            }

            if (empty($cols)) continue;

            $sql = "INSERT INTO `{$tname}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            sql_query($sql);
        }
    }

    echo json_encode(array('status' => 'success'));
    exit;
}

echo json_encode(array('status' => 'error', 'msg' => '알 수 없는 mode'));
exit;
