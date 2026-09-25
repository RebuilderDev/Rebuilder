<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$css_dir = G5_DATA_PATH . '/rb_custom_css';
$css_url = G5_DATA_URL  . '/rb_custom_css';

if (is_dir($css_dir)) {
    // 모든 css 수집 + 정렬
    $files = glob($css_dir.'/*.css') ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    // 다른 테마의 개별 CSS가 현재 화면까지 바꾸지 않도록 현재 배치만 로드한다.
    $rb_css_allowed = array();
    $rb_css_theme = sql_real_escape_string(isset($config['cf_theme']) ? $config['cf_theme'] : '');
    foreach (array('rb_module'=>'md', 'rb_module_shop'=>'md', 'rb_section'=>'sec', 'rb_section_shop'=>'sec') as $rb_css_table=>$rb_css_prefix) {
        $rb_css_result = sql_query("SELECT {$rb_css_prefix}_id AS id, {$rb_css_prefix}_layout AS layout FROM {$rb_css_table} WHERE {$rb_css_prefix}_theme='{$rb_css_theme}'", false);
        if (!$rb_css_result) continue;
        while ($rb_css_row = sql_fetch_array($rb_css_result)) {
            $rb_css_name = ($rb_css_prefix === 'md' ? 'mod' : 'sec') . (substr($rb_css_table, -5) === '_shop' ? '_shop' : '')
                . '_' . preg_replace('~[^A-Za-z0-9_\-]~', '-', $rb_css_row['layout']) . '_' . $rb_css_row['id'] . '.css';
            $rb_css_allowed[$rb_css_name] = true;
        }
    }
    $files = array_values(array_filter($files, function ($f) use ($rb_css_allowed) { return isset($rb_css_allowed[basename($f)]); }));

    // 캐시 무력화용 버전 문자열
    if (!function_exists('rb_css_ver')) {
        function rb_css_ver($path){
            $mt  = @filemtime($path) ?: time();
            $sz  = @filesize($path) ?: 0;
            $md5 = @md5_file($path);
            if (!$md5) $md5 = md5($mt.$sz);
            return $mt.'-'.$sz.'-'.substr($md5,0,8);
        }
    }

    foreach ($files as $f) {
        $id   = 'rb-css-'.pathinfo($f, PATHINFO_FILENAME);
        $ver  = rb_css_ver($f);
        $href = $css_url . '/' . basename($f) . '?v=' . $ver;

        // 테마/플러그인보다 뒤에서 로드되도록 우선순위 넉넉히
        add_stylesheet(
            '<link id="'.htmlspecialchars($id, ENT_QUOTES).'" rel="stylesheet" href="'.
            htmlspecialchars($href, ENT_QUOTES).'" media="all">',
            99
        );
    }
}
