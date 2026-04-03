<?php
if (!defined('_GNUBOARD_')) exit;

// 관리자 화면에서만 동작
if (!defined('G5_IS_ADMIN') || !G5_IS_ADMIN) return;

function rb_create_table_admin_widget()
{
    global $g5;

    $table = isset($g5['rb_admin_widget_table']) && $g5['rb_admin_widget_table']
        ? $g5['rb_admin_widget_table']
        : 'rb_admin_widget';

    // 테이블 존재 확인
    $chk = sql_fetch(" SHOW TABLES LIKE '".sql_escape_string($table)."' ");
    if (isset($chk[0]) && $chk[0]) {
        $col_span = sql_fetch(" SHOW COLUMNS FROM `{$table}` LIKE 'aw_span' ");
        if (!(isset($col_span['Field']) && $col_span['Field'] === 'aw_span')) {
            sql_query(" ALTER TABLE `{$table}` ADD `aw_span` tinyint(1) NOT NULL DEFAULT 1 AFTER `aw_area` ", false);
        }
        return true; // 이미 있음
    }

    // 생성
    $sql = "
    CREATE TABLE `{$table}` (
      `aw_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `aw_user` varchar(50) DEFAULT NULL,
      `aw_key` varchar(64) NOT NULL,
      `aw_area` enum('main','side') NOT NULL,
      `aw_span` tinyint(1) NOT NULL DEFAULT 1,
      `aw_sort` int(11) NOT NULL DEFAULT 0,
      `aw_enabled` tinyint(1) NOT NULL DEFAULT 1,
      `aw_conf` text DEFAULT NULL,
      `aw_created` datetime NOT NULL DEFAULT current_timestamp(),
      `aw_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`aw_id`),
      KEY `aw_area` (`aw_area`,`aw_sort`),
      KEY `aw_user` (`aw_user`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=25
    ";
    sql_query($sql, false);

    // 최종 확인
    $chk2 = sql_fetch(" SHOW TABLES LIKE '".sql_escape_string($table)."' ");
    return (isset($chk2[0]) && $chk2[0]) ? true : false;
}

function rb_seed_table_admin_widget()
{
    global $g5;

    $table = isset($g5['rb_admin_widget_table']) && $g5['rb_admin_widget_table']
        ? $g5['rb_admin_widget_table']
        : 'rb_admin_widget';

    $cnt = sql_fetch(" SELECT COUNT(*) AS cnt FROM `{$table}` ");
    $count = isset($cnt['cnt']) ? (int)$cnt['cnt'] : 0;
    if ($count > 0) {
        return true;
    }

    $seed_rows = array(
        array('aw_key' => 'posts',    'aw_area' => 'main', 'aw_span' => 2, 'aw_sort' => 1),
        array('aw_key' => 'comments', 'aw_area' => 'main', 'aw_span' => 2, 'aw_sort' => 2),
        array('aw_key' => 'members',  'aw_area' => 'main', 'aw_span' => 1, 'aw_sort' => 3),
        array('aw_key' => 'visitors', 'aw_area' => 'main', 'aw_span' => 1, 'aw_sort' => 4),
        array('aw_key' => 'qa',       'aw_area' => 'main', 'aw_span' => 1, 'aw_sort' => 5),
        array('aw_key' => 'points',   'aw_area' => 'main', 'aw_span' => 1, 'aw_sort' => 6),
    );

    foreach ($seed_rows as $row) {
        $sql = "
            INSERT INTO `{$table}`
            SET aw_user = NULL,
                aw_key = '".sql_escape_string($row['aw_key'])."',
                aw_area = '".sql_escape_string($row['aw_area'])."',
                aw_span = '".(int)$row['aw_span']."',
                aw_sort = '".(int)$row['aw_sort']."',
                aw_enabled = '1',
                aw_conf = NULL,
                aw_created = NOW(),
                aw_updated = NOW()
        ";
        sql_query($sql, false);
    }

    return true;
}

if (rb_create_table_admin_widget()) {
    rb_seed_table_admin_widget();
}

if (!function_exists('rb_adm_meta_filter')) {
    function rb_adm_meta_filter($buffer) {
        if (stripos($buffer, '</head>') === false) return $buffer;

        // 1) 기존 관련 메타 전부 제거
        $patterns = [
            '/<meta[^>]+(?:id=["\']meta_viewport["\']|name=["\']viewport["\'])[^>]*>\s*/i',
            '/<meta[^>]+name=["\']HandheldFriendly["\'][^>]*>\s*/i',
            '/<meta[^>]+name=["\']format-detection["\'][^>]*>\s*/i',
            '/<meta[^>]+http-equiv=["\']imagetoolbar["\'][^>]*>\s*/i',
            '/<meta[^>]+http-equiv=["\']X-UA-Compatible["\'][^>]*>\s*/i',
        ];
        $buffer = preg_replace($patterns, '', $buffer);

        // 2) 원하는 3개 메타만 주입 (PC/모바일 공통)
        $inject = implode(PHP_EOL, [
            '<meta name="viewport" id="meta_viewport" content="width=device-width,initial-scale=0.9,minimum-scale=0,maximum-scale=10">',
            '<meta name="HandheldFriendly" content="true">',
            '<meta name="format-detection" content="telephone=no">',
        ]) . PHP_EOL;

        // 3) </head> 바로 앞에 1회 삽입
        $buffer = preg_replace('/<\/head>/i', $inject.'</head>', $buffer, 1);

        // 4) 쿠키 adm_dark 있을 때만 <body>에 adm-dark class 주입
        $is_dark = false;
        if (isset($_COOKIE['adm_dark'])) {
            $v = trim((string)$_COOKIE['adm_dark']);
            if ($v !== '' && $v !== '0') {
                $is_dark = true;
            }
        }

        if ($is_dark) {
            $buffer = preg_replace_callback('/<body\b([^>]*)>/i', function($m) {
                $attrs = $m[1];

                // class="..." 또는 class='...'가 있는 경우
                if (preg_match('/\bclass\s*=\s*([\'"])(.*?)\1/i', $attrs, $cm)) {
                    $quote   = $cm[1];
                    $classes = $cm[2];

                    // 이미 adm-dark가 있으면 그대로
                    if (preg_match('/(^|\s)adm-dark(\s|$)/i', $classes)) {
                        return $m[0];
                    }

                    $new_classes = trim($classes . ' adm-dark');
                    $new_attrs = preg_replace(
                        '/\bclass\s*=\s*([\'"])(.*?)\1/i',
                        'class=' . $quote . $new_classes . $quote,
                        $attrs,
                        1
                    );

                    return '<body' . $new_attrs . '>';
                }

                // class 속성이 없는 경우
                return '<body' . $attrs . ' class="adm-dark">';
            }, $buffer, 1);
        }

        return $buffer;
    }
}

// 가장 먼저 잡도록 버퍼 시작
if (!headers_sent()) {
    ob_start('rb_adm_meta_filter');
}
