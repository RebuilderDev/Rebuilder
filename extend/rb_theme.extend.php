<?php
if (!defined('_GNUBOARD_')) exit;

// 테이블 보장
$theme_config_tables = 'rb_theme';

$sql = "
    CREATE TABLE IF NOT EXISTS `{$theme_config_tables}` (
        `theme_key` varchar(50) NOT NULL,
        `use1_yn` tinyint(1) NOT NULL DEFAULT 1,
        `use2_yn` tinyint(1) NOT NULL DEFAULT 0,
        `use3_yn` tinyint(1) NOT NULL DEFAULT 0,
        `use4_yn` tinyint(1) NOT NULL DEFAULT 0,
        `use5_yn` tinyint(1) NOT NULL DEFAULT 0,

        `carousel_use` tinyint(1) NOT NULL DEFAULT 0,
        `carousel_type` varchar(20) NOT NULL DEFAULT 'fade',
        `carousel_time` int(11) NOT NULL DEFAULT 4000,
        `carousel_speed` int(11) NOT NULL DEFAULT 600,
        `carousel_height_pc` int(11) NOT NULL DEFAULT 600,
        `carousel_height_mo` int(11) NOT NULL DEFAULT 600,

        `reg_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `upd_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (`theme_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
";
sql_query($sql);


$sql = "CREATE TABLE IF NOT EXISTS `rb_theme_carousel` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cf_theme` varchar(50) NOT NULL,
    `main_text` text NOT NULL,
    `main_size` int(11) NOT NULL DEFAULT 40,
    `main_color` varchar(10) NOT NULL DEFAULT '#ffffff',
    `main_align` varchar(10) NOT NULL DEFAULT 'center',
    `main_weight` varchar(10) NOT NULL DEFAULT 'font-B',
    `sub_text` text NOT NULL,
    `sub_size` int(11) NOT NULL DEFAULT 26,
    `sub_color` varchar(10) NOT NULL DEFAULT '#ffffff',
    `sub_margin` int(11) NOT NULL DEFAULT 10,
    `sub_align` varchar(10) NOT NULL DEFAULT 'center',
    `sub_weight` varchar(10) NOT NULL DEFAULT 'font-R',
    `btn_text` varchar(100) NOT NULL DEFAULT '',
    `btn_link` varchar(255) NOT NULL DEFAULT '',
    `btn_size` int(11) NOT NULL DEFAULT 24,
    `btn_radius` int(11) NOT NULL DEFAULT 20,
    `btn_border` int(11) NOT NULL DEFAULT 0,
    `btn_padding` int(11) NOT NULL DEFAULT 10,
    `btn_bg_color` varchar(10) NOT NULL DEFAULT '#ffffff',
    `btn_text_color` varchar(10) NOT NULL DEFAULT '#000000',
    `btn_border_color` varchar(10) NOT NULL DEFAULT '#000000',
    `btn_svg` varchar(100) NOT NULL DEFAULT '',
    `btn_align` varchar(10) NOT NULL DEFAULT 'center',
    `btn_link_blank` tinyint(1) NOT NULL DEFAULT 0,
    `image_path` varchar(255) NOT NULL DEFAULT '',
    `reg_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `cf_theme` (`cf_theme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
sql_query($sql);

$col_check = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'btn_link_blank'");
if (!$col_check) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN btn_link_blank tinyint(1) NOT NULL DEFAULT 0 AFTER btn_align");
}

$col_check2 = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'carousel_type_mode'");
if (!$col_check2) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN carousel_type_mode varchar(20) NOT NULL DEFAULT 'community' AFTER cf_theme");
}

$col_check3 = sql_fetch("SHOW COLUMNS FROM rb_theme LIKE 'carousel_use_shop'");
if (!$col_check3) {
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_use_shop tinyint(1) NOT NULL DEFAULT 0 AFTER carousel_use");
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_type_shop varchar(20) NOT NULL DEFAULT 'fade' AFTER carousel_use_shop");
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_time_shop int(11) NOT NULL DEFAULT 4000 AFTER carousel_type_shop");
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_speed_shop int(11) NOT NULL DEFAULT 600 AFTER carousel_time_shop");
}

$col_check_height_pc = sql_fetch("SHOW COLUMNS FROM rb_theme LIKE 'carousel_height_pc'");
if (!$col_check_height_pc) {
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_height_pc int(11) NOT NULL DEFAULT 600 AFTER carousel_speed");
}

$col_check_height_mo = sql_fetch("SHOW COLUMNS FROM rb_theme LIKE 'carousel_height_mo'");
if (!$col_check_height_mo) {
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_height_mo int(11) NOT NULL DEFAULT 600 AFTER carousel_height_pc");
}

$col_check_height_pc_shop = sql_fetch("SHOW COLUMNS FROM rb_theme LIKE 'carousel_height_pc_shop'");
if (!$col_check_height_pc_shop) {
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_height_pc_shop int(11) NOT NULL DEFAULT 600 AFTER carousel_speed_shop");
}

$col_check_height_mo_shop = sql_fetch("SHOW COLUMNS FROM rb_theme LIKE 'carousel_height_mo_shop'");
if (!$col_check_height_mo_shop) {
    sql_query("ALTER TABLE rb_theme ADD COLUMN carousel_height_mo_shop int(11) NOT NULL DEFAULT 600 AFTER carousel_height_pc_shop");
}

$col_check4 = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'is_sub'");
if (!$col_check4) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN is_sub tinyint(1) NOT NULL DEFAULT 0 AFTER carousel_type_mode");
}

$col_check5 = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'btn_weight'");
if (!$col_check5) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN btn_weight varchar(10) NOT NULL DEFAULT 'font-R' AFTER btn_text");
}

$col_check6 = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'btn_padding_lr'");
if (!$col_check6) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN btn_padding_lr int(11) NOT NULL DEFAULT 20 AFTER btn_padding");
}

$col_check7 = sql_fetch("SHOW COLUMNS FROM rb_theme_carousel LIKE 'btn_margin'");
if (!$col_check7) {
    sql_query("ALTER TABLE rb_theme_carousel ADD COLUMN btn_margin int(11) NOT NULL DEFAULT 0 AFTER btn_padding_lr");
}

// 현재 테마 키
$rb_theme_key = isset($config['cf_theme']) ? trim($config['cf_theme']) : '';

// 기본값
$rb_theme_row = array(
    'use1_yn' => 1,
    'use2_yn' => 0,
    'use3_yn' => 0,
    'use4_yn' => 0,
    'use5_yn' => 0,

    'carousel_use'   => 0,
    'carousel_type'  => 'fade',
    'carousel_time'  => 4000,
    'carousel_speed' => 600,
    'carousel_height_pc' => 600,
    'carousel_height_mo' => 600,

    'carousel_use_shop'   => 0,
    'carousel_type_shop'  => 'fade',
    'carousel_time_shop'  => 4000,
    'carousel_speed_shop' => 600,
    'carousel_height_pc_shop' => 600,
    'carousel_height_mo_shop' => 600,
);

// 테마별 설정 로드
if ($rb_theme_key !== '') {

    sql_query("
        INSERT IGNORE INTO rb_theme (theme_key)
        VALUES ('{$rb_theme_key}')
    ");

    $rb_theme_key = sql_real_escape_string($rb_theme_key);
    $row = sql_fetch("SELECT * FROM {$theme_config_tables} WHERE theme_key = '{$rb_theme_key}'");

    if ($row) {
        foreach ($rb_theme_row as $k => $v) {
            if (isset($row[$k])) {
                $rb_theme_row[$k] = $row[$k];
            }
        }
    }
}


$carousel_list = sql_query("SELECT * FROM rb_theme_carousel WHERE cf_theme = '$rb_theme_key' ORDER BY sort_order ASC, id ASC");
$rb_carousel_data = array();

while ($rb_carousel_f_row = sql_fetch_array($carousel_list)) {
    $rb_carousel_data[] = array(
        'id' => $rb_carousel_f_row['id'],
        'main_text' => $rb_carousel_f_row['main_text'],
        'main_size' => $rb_carousel_f_row['main_size'],
        'main_color' => $rb_carousel_f_row['main_color'],
        'main_align' => isset($rb_carousel_f_row['main_align']) ? $rb_carousel_f_row['main_align'] : 'center',
        'main_weight' => isset($rb_carousel_f_row['main_weight']) ? $rb_carousel_f_row['main_weight'] : 'font-B',
        'sub_text' => $rb_carousel_f_row['sub_text'],
        'sub_size' => $rb_carousel_f_row['sub_size'],
        'sub_color' => $rb_carousel_f_row['sub_color'],
        'sub_margin' => $rb_carousel_f_row['sub_margin'],
        'sub_align' => isset($rb_carousel_f_row['sub_align']) ? $rb_carousel_f_row['sub_align'] : 'center',
        'sub_weight' => isset($rb_carousel_f_row['sub_weight']) ? $rb_carousel_f_row['sub_weight'] : 'font-R',
        'btn_text' => $rb_carousel_f_row['btn_text'],
        'btn_weight' => isset($rb_carousel_f_row['btn_weight']) ? $rb_carousel_f_row['btn_weight'] : 'font-R',
        'btn_link' => isset($rb_carousel_f_row['btn_link']) ? $rb_carousel_f_row['btn_link'] : '',
        'btn_size' => $rb_carousel_f_row['btn_size'],
        'btn_radius' => $rb_carousel_f_row['btn_radius'],
        'btn_border' => $rb_carousel_f_row['btn_border'],
        'btn_padding'    => isset($rb_carousel_f_row['btn_padding'])    ? (int)$rb_carousel_f_row['btn_padding']    : 10,
        'btn_padding_lr' => isset($rb_carousel_f_row['btn_padding_lr']) ? (int)$rb_carousel_f_row['btn_padding_lr'] : 20,
        'btn_margin'     => isset($rb_carousel_f_row['btn_margin'])     ? (int)$rb_carousel_f_row['btn_margin']     : 0,
        'btn_bg_color' => isset($rb_carousel_f_row['btn_bg_color']) ? $rb_carousel_f_row['btn_bg_color'] : '#ffffff',
        'btn_text_color' => isset($rb_carousel_f_row['btn_text_color']) ? $rb_carousel_f_row['btn_text_color'] : '#000000',
        'btn_border_color' => isset($rb_carousel_f_row['btn_border_color']) ? $rb_carousel_f_row['btn_border_color'] : '#000000',
        'btn_svg' => $rb_carousel_f_row['btn_svg'],
        'btn_align' => isset($rb_carousel_f_row['btn_align']) ? $rb_carousel_f_row['btn_align'] : 'center',
        'btn_link_blank' => isset($rb_carousel_f_row['btn_link_blank']) ? (int)$rb_carousel_f_row['btn_link_blank'] : 0,
        'carousel_type_mode' => isset($rb_carousel_f_row['carousel_type_mode']) ? $rb_carousel_f_row['carousel_type_mode'] : 'community',
        'is_sub' => isset($rb_carousel_f_row['is_sub']) ? (int)$rb_carousel_f_row['is_sub'] : 0,
        'image_path' => $rb_carousel_f_row['image_path'] ? G5_DATA_URL . '/'.$rb_theme_key.'/carousel_img/' . $rb_carousel_f_row['image_path'] : ''
    );
}
