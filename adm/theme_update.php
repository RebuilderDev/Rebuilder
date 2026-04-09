<?php
$sub_menu = "100280";
include_once('./_common.php');

if ($is_admin != 'super')
    die('최고관리자만 접근 가능합니다.');

admin_referer_check();

$theme = isset($_POST['theme']) ? trim($_POST['theme']) : '';
$post_type = isset($_POST['type']) ? clean_xss_tags($_POST['type'], 1, 1) : '';
$post_set_default_skin = isset($_POST['set_default_skin']) ? clean_xss_tags($_POST['set_default_skin'], 1, 1) : '';

$theme_dir = get_theme_dir();

// 백업 함수
function rb_backup_theme_data($theme_key) {
    $backup_dir = G5_DATA_PATH . '/rb.backup';

    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, G5_DIR_PERMISSION, true);
        @chmod($backup_dir, G5_DIR_PERMISSION);
    }

    if (!is_dir($backup_dir)) return false;

    $theme_key_esc = sql_real_escape_string($theme_key);

    $tables = array(
        'rb_module'         => 'md_theme',
        'rb_module_shop'    => 'md_theme',
        'rb_section'        => 'sec_theme',
        'rb_section_shop'   => 'sec_theme',
        'rb_theme'          => 'theme_key',
        'rb_theme_carousel' => 'cf_theme',
        'rb_config'         => 'co_theme',
    );

    $backup_data = array();

    foreach ($tables as $tname => $tcol) {
        $res = sql_query("SELECT * FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'", false);
        if (!$res) continue;

        $backup_data[$tname] = array();
        while ($row = sql_fetch_array($res)) {
            $backup_data[$tname][] = $row;
        }
    }

    if (empty($backup_data)) return false;

    $filename = $backup_dir . '/' . $theme_key . '_' . date('YmdHis') . '.json';
    $result = file_put_contents($filename, json_encode($backup_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return $result !== false;
}

if($post_type == 'reset') {
    $reset_theme = sql_real_escape_string(trim($config['cf_theme']));

    $sql = " update {$g5['config_table']} set cf_theme = '' ";
    sql_query($sql);

    die('');
}

if($post_type == 'reset_data') {
    $reset_theme = sql_real_escape_string(trim($theme));

    if ($reset_theme === '') die('테마 정보가 없습니다.');

    $rb_json_io_tables_reset = array(
        array('table' => 'rb_module',         'col' => 'md_theme',  'pk' => 'md_id'),
        array('table' => 'rb_module_shop',    'col' => 'md_theme',  'pk' => 'md_id'),
        array('table' => 'rb_section',        'col' => 'sec_theme', 'pk' => 'sec_id'),
        array('table' => 'rb_section_shop',   'col' => 'sec_theme', 'pk' => 'sec_id'),
        array('table' => 'rb_theme',          'col' => 'theme_key', 'pk' => ''),
        array('table' => 'rb_theme_carousel', 'col' => 'cf_theme',  'pk' => 'id'),
        array('table' => 'rb_config',         'col' => 'co_theme',  'pk' => 'co_id'),
    );

    // 백업 실행
    rb_backup_theme_data($reset_theme);

    // import_log 삭제
    sql_query("DELETE FROM rb_import_log WHERE theme_key = '{$reset_theme}'");

    // JSON 파일 로드
    $reset_json_theme_path = G5_PATH . '/theme/' . $reset_theme;
    $reset_json_files = glob($reset_json_theme_path . '/' . $reset_theme . '_*.json');

    if (empty($reset_json_files)) die('JSON 파일이 없습니다.');

    usort($reset_json_files, function($a, $b) { return strcmp($b, $a); });
    $reset_json_file = $reset_json_files[0];
    $reset_json_basename = basename($reset_json_file);
    $reset_json_raw = file_get_contents($reset_json_file);
    $reset_json_data = json_decode($reset_json_raw, true);

    if (!is_array($reset_json_data) || empty($reset_json_data)) die('JSON 데이터가 없습니다.');

    $reset_allowed = array();
    $reset_pks = array();
    foreach ($rb_json_io_tables_reset as $t) {
        $reset_allowed[$t['table']] = $t['col'];
        $reset_pks[$t['table']] = $t['pk'];
    }

    $reset_table_cols = array();

    foreach ($reset_json_data as $tname => $rows) {
        if (!isset($reset_allowed[$tname])) continue;
        if (!is_array($rows) || empty($rows)) continue;

        $tcol = (string)$reset_allowed[$tname];
        $pk = isset($reset_pks[$tname]) ? $reset_pks[$tname] : '';

        if (!isset($reset_table_cols[$tname])) {
            $reset_table_cols[$tname] = array();
            $col_res = sql_query("SHOW COLUMNS FROM `{$tname}`");
            while ($col_row = sql_fetch_array($col_res)) {
                $reset_table_cols[$tname][] = $col_row['Field'];
            }
        }
        $valid_cols = $reset_table_cols[$tname];

        if ($tcol !== '') {
            sql_query("DELETE FROM `{$tname}` WHERE `{$tcol}` = '{$reset_theme}'");
        }

        foreach ($rows as $row) {
            if (!is_array($row) || empty($row)) continue;
            if ($pk !== '') unset($row[$pk]);

            $cols = array();
            $vals = array();
            foreach ($row as $k => $v) {
                if (!in_array($k, $valid_cols)) continue;
                $cols[] = '`' . addslashes((string)$k) . '`';
                $vals[] = "'" . addslashes((string)$v) . "'";
            }
            if (empty($cols)) continue;

            sql_query("INSERT INTO `{$tname}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
        }
    }

    sql_query("INSERT INTO rb_import_log (theme_key, import_done) VALUES ('{$reset_theme}', '" . addslashes($reset_json_basename) . "') ON DUPLICATE KEY UPDATE import_done = '" . addslashes($reset_json_basename) . "'");

    die('');
}

if(!in_array($theme, $theme_dir))
    die('선택하신 테마가 설치되어 있지 않습니다.');

// 테마적용
$sql = " update {$g5['config_table']} set cf_theme = '$theme' ";
sql_query($sql);

// 테마 설정 스킨 적용
if($post_set_default_skin == 1) {
    $keys = 'set_default_skin, cf_member_skin, cf_mobile_member_skin, cf_new_skin, cf_mobile_new_skin, cf_search_skin, cf_mobile_search_skin, cf_connect_skin, cf_mobile_connect_skin, cf_faq_skin, cf_mobile_faq_skin, qa_skin, qa_mobile_skin, de_shop_skin, de_shop_mobile_skin';

    $tconfig = get_theme_config_value($theme, $keys);

    if($tconfig['set_default_skin']) {
        $sql_common = array();
        $qa_sql_common = array();
        $de_sql_common = array();

        foreach($tconfig as $key => $val) {
            if(preg_match('#^qa_.+$#', $key)) {
                if($val) {
                    if(!preg_match('#^theme/.+$#', $val))
                        $val = 'theme/'.$val;
                    $qa_sql_common[] = " $key = '$val' ";
                }
                continue;
            }

            if(preg_match('#^de_.+$#', $key)) {
                if(!isset($default[$key]))
                    continue;
                if($val) {
                    if(!preg_match('#^theme/.+$#', $val))
                        $val = 'theme/'.$val;
                    $de_sql_common[] = " $key = '$val' ";
                }
                continue;
            }

            if(!isset($config[$key]))
                continue;

            if($val) {
                if(!preg_match('#^theme/.+$#', $val))
                    $val = 'theme/'.$val;
                $sql_common[] = " $key = '$val' ";
            }
        }

        if(!empty($sql_common)) {
            $sql = " update {$g5['config_table']} set " . implode(', ', $sql_common);
            sql_query($sql);
        }

        if(!empty($qa_sql_common)) {
            $sql = " update {$g5['qa_config_table']} set " . implode(', ', $qa_sql_common);
            sql_query($sql);
        }

        if(!empty($de_sql_common)) {
            $sql = " update {$g5['g5_shop_default_table']} set " . implode(', ', $de_sql_common);
            sql_query($sql);
        }
    }
}

$rb_json_io_tables = array(
    array('table' => 'rb_module',         'col' => 'md_theme',  'pk' => 'md_id'),
    array('table' => 'rb_module_shop',    'col' => 'md_theme',  'pk' => 'md_id'),
    array('table' => 'rb_section',        'col' => 'sec_theme', 'pk' => 'sec_id'),
    array('table' => 'rb_section_shop',   'col' => 'sec_theme', 'pk' => 'sec_id'),
    array('table' => 'rb_theme',          'col' => 'theme_key', 'pk' => ''),
    array('table' => 'rb_theme_carousel', 'col' => 'cf_theme',  'pk' => 'id'),
    array('table' => 'rb_config',         'col' => 'co_theme',  'pk' => 'co_id'),
);

sql_query("CREATE TABLE IF NOT EXISTS `rb_import_log` (`theme_key` varchar(100) NOT NULL, `import_done` varchar(100) NOT NULL DEFAULT '', PRIMARY KEY (`theme_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");

$rb_cf_theme = (string)$theme;
$rb_json_theme_path = G5_PATH . '/theme/' . $rb_cf_theme;
$rb_json_files = glob($rb_json_theme_path . '/' . $rb_cf_theme . '_*.json');

if (!empty($rb_json_files)) {
    usort($rb_json_files, function($a, $b) { return strcmp($b, $a); });
    $rb_json_file = $rb_json_files[0];
    $rb_json_basename = basename($rb_json_file);
    $theme_key_esc = sql_real_escape_string($rb_cf_theme);

    $rb_done_row = sql_fetch("SELECT import_done FROM rb_import_log WHERE theme_key = '{$theme_key_esc}'");
    $rb_import_done = isset($rb_done_row['import_done']) ? (string)$rb_done_row['import_done'] : '';

    // rb_module 또는 rb_module_shop 에 같은 테마의 데이터 있으면 기존사용자로 판단
    if (empty($rb_import_done)) {
        $has_module = sql_fetch("SELECT md_id FROM rb_module WHERE md_theme = '{$theme_key_esc}' LIMIT 1");
        if (empty($has_module)) {
            $has_module = sql_fetch("SELECT md_id FROM rb_module_shop WHERE md_theme = '{$theme_key_esc}' LIMIT 1");
        }
        if (!empty($has_module)) {
            $rb_import_done = 'protected';
        }
    }

    if (empty($rb_import_done)) {
        $rb_json_raw = file_get_contents($rb_json_file);
        $rb_json_data = json_decode($rb_json_raw, true);

        if (is_array($rb_json_data) && !empty($rb_json_data)) {

            // 백업 실행
            rb_backup_theme_data($rb_cf_theme);

            $rb_json_allowed = array();
            $rb_json_pks = array();
            foreach ($rb_json_io_tables as $t) {
                $rb_json_allowed[$t['table']] = $t['col'];
                $rb_json_pks[$t['table']] = $t['pk'];
            }

            $rb_json_table_cols = array();

            foreach ($rb_json_data as $tname => $rows) {
                if (!isset($rb_json_allowed[$tname])) continue;
                if (!is_array($rows) || empty($rows)) continue;

                $tcol = (string)$rb_json_allowed[$tname];
                $pk = isset($rb_json_pks[$tname]) ? $rb_json_pks[$tname] : '';

                if (!isset($rb_json_table_cols[$tname])) {
                    $rb_json_table_cols[$tname] = array();
                    $col_res = sql_query("SHOW COLUMNS FROM `{$tname}`");
                    while ($col_row = sql_fetch_array($col_res)) {
                        $rb_json_table_cols[$tname][] = $col_row['Field'];
                    }
                }
                $valid_cols = $rb_json_table_cols[$tname];

                if ($tname === 'rb_config') {
                    sql_query("DELETE FROM `rb_config` WHERE `co_theme` = '{$theme_key_esc}'");
                    foreach ($rows as $row) {
                        if (!is_array($row) || empty($row)) continue;
                        if ($pk !== '') unset($row[$pk]);

                        $cols = array();
                        $vals = array();
                        foreach ($row as $k => $v) {
                            if (!in_array($k, $valid_cols)) continue;
                            $cols[] = '`' . addslashes((string)$k) . '`';
                            $vals[] = "'" . addslashes((string)$v) . "'";
                        }
                        if (empty($cols)) continue;

                        sql_query("INSERT INTO `{$tname}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
                    }
                    continue;
                }

                if ($tcol !== '') {
                    sql_query("DELETE FROM `{$tname}` WHERE `{$tcol}` = '{$theme_key_esc}'");
                }

                foreach ($rows as $row) {
                    if (!is_array($row) || empty($row)) continue;
                    if ($pk !== '') unset($row[$pk]);

                    $cols = array();
                    $vals = array();
                    foreach ($row as $k => $v) {
                        if (!in_array($k, $valid_cols)) continue;
                        $cols[] = '`' . addslashes((string)$k) . '`';
                        $vals[] = "'" . addslashes((string)$v) . "'";
                    }
                    if (empty($cols)) continue;

                    sql_query("INSERT INTO `{$tname}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
                }
            }

            sql_query("INSERT INTO rb_import_log (theme_key, import_done) VALUES ('{$theme_key_esc}', '" . addslashes($rb_json_basename) . "') ON DUPLICATE KEY UPDATE import_done = '" . addslashes($rb_json_basename) . "'");
        }
    }
}

run_event('adm_theme_update', $theme, $post_set_default_skin);

die('');
