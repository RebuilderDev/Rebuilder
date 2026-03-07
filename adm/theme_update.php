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

if($post_type == 'reset') {
    $reset_theme = sql_real_escape_string(trim($config['cf_theme']));

    $sql = " update {$g5['config_table']} set cf_theme = '' ";
    sql_query($sql);

    if($reset_theme !== '') {
        sql_query("UPDATE rb_config SET co_theme = '' WHERE co_theme = '{$reset_theme}'");
        sql_query("DELETE FROM rb_import_log WHERE theme_key = '{$reset_theme}'");
    }

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

    if ($rb_import_done !== $rb_json_basename) {
        $rb_json_raw = file_get_contents($rb_json_file);
        $rb_json_data = json_decode($rb_json_raw, true);

        if (is_array($rb_json_data) && !empty($rb_json_data)) {
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

                // rb_config는 DELETE 없이 없을때만 INSERT
                if ($tname === 'rb_config') {
                    foreach ($rows as $row) {
                        if (!is_array($row) || empty($row)) continue;
                        if ($pk !== '') unset($row[$pk]);

                        $co_theme_val = isset($row['co_theme']) ? sql_real_escape_string((string)$row['co_theme']) : '';
                        $exists = sql_fetch("SELECT co_id FROM rb_config WHERE co_theme = '{$co_theme_val}' LIMIT 1");
                        if ($exists) continue;

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
