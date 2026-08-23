<?php
include_once '../../../common.php';
include_once G5_PATH . '/rb/rb.config/layout.cache.php';

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

$theme_key_esc = sql_real_escape_string($theme_key);

// 내보내기
if ($mode === 'export') {
    $export = array();

    foreach ($io_tables as $t) {
        $tname = $t['table'];
        $tcol  = $t['col'];
        $rows  = array();

        if ($tcol === '') {
            $sql = "SELECT * FROM `{$tname}`";
        } else {
            $sql = "SELECT * FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'";
        }

        $res = sql_query($sql);
        if ($res) {
            while ($row = sql_fetch_array($res)) {
                if ($t['pk'] !== '') unset($row[$t['pk']]);
                $rows[] = $row;
            }
        }

        $export[$tname] = $rows;
    }

    $json_filename = $theme_key . '_' . date('Ymd_His') . '.json';
    $json_out = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // 이미지가 없으면 JSON 단독 다운로드
    $has_images = false;
    $carousel_img_dir = G5_DATA_PATH . '/' . $theme_key . '/carousel_img/';
    if (isset($export['rb_theme_carousel']) && is_array($export['rb_theme_carousel'])) {
        foreach ($export['rb_theme_carousel'] as $crow) {
            if (!empty($crow['image_path'])) {
                $img_file = $carousel_img_dir . basename($crow['image_path']);
                if (file_exists($img_file)) {
                    $has_images = true;
                    break;
                }
            }
        }
    }

    if (!$has_images || !class_exists('ZipArchive')) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $json_filename . '"');
        header('Content-Length: ' . strlen($json_out));
        echo $json_out;
        exit;
    }

    // ZIP 생성
    $zip_filename = $theme_key . '_' . date('Ymd_His') . '.zip';
    $tmp_zip = sys_get_temp_dir() . '/' . $zip_filename;

    $zip = new ZipArchive();
    if ($zip->open($tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        // ZIP 생성 실패시 JSON 단독 다운로드
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $json_filename . '"');
        header('Content-Length: ' . strlen($json_out));
        echo $json_out;
        exit;
    }

    // JSON 파일 추가
    $zip->addFromString($json_filename, $json_out);

    // 이미지 파일 추가
    foreach ($export['rb_theme_carousel'] as $crow) {
        if (empty($crow['image_path'])) continue;
        $basename = basename($crow['image_path']);
        $img_file = $carousel_img_dir . $basename;
        if (file_exists($img_file)) {
            $zip->addFile($img_file, 'data/' . $theme_key . '/carousel_img/' . $basename);
        }
    }

    $zip->close();

    if (!file_exists($tmp_zip)) {
        echo json_encode(array('status' => 'error', 'msg' => 'ZIP 생성 실패'));
        exit;
    }

    $zip_size = filesize($tmp_zip);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . $zip_size);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    readfile($tmp_zip);
    @unlink($tmp_zip);
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

    // 백업
    $backup_dir = G5_DATA_PATH . '/rb.backup';
    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, G5_DIR_PERMISSION, true);
    }

    $backup_data = array();
    foreach ($io_tables as $bt) {
        $btname = $bt['table'];
        $btcol  = $bt['col'];
        $bres = ($btcol === '')
            ? sql_query("SELECT * FROM `{$btname}`", false)
            : sql_query("SELECT * FROM `{$btname}` WHERE `{$btcol}` = '{$theme_key_esc}'", false);
        if (!$bres) continue;
        $backup_data[$btname] = array();
        while ($br = sql_fetch_array($bres)) {
            $backup_data[$btname][] = $br;
        }
    }

    if (!empty($backup_data)) {
        $backup_file = $backup_dir . '/' . $theme_key . '_' . date('YmdHis') . '.json';
        file_put_contents($backup_file, json_encode($backup_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
                $vals[] = "'" . sql_real_escape_string((string)$v) . "'";
            }

            if (empty($cols)) continue;

            $sql = "INSERT INTO `{$tname}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            sql_query($sql);
        }
    }

    rb_layout_cache_delete_all();
    echo json_encode(array('status' => 'success'));
    exit;
}

echo json_encode(array('status' => 'error', 'msg' => '알 수 없는 mode'));
exit;

