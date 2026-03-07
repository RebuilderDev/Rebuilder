<?php
if (!defined('_GNUBOARD_')) exit;

// 관리자 화면에서만 동작
if (!defined('G5_IS_ADMIN') || !G5_IS_ADMIN) return;

if (!function_exists('rb_adm_meta_filter')) {
    function rb_adm_meta_filter($buffer) {
        if (stripos($buffer, '</head>') === false) {
            return $buffer;
        }

        $patterns = [
            '/<meta[^>]+(?:id=["\']meta_viewport["\']|name=["\']viewport["\'])[^>]*>\s*/i',
            '/<meta[^>]+name=["\']HandheldFriendly["\'][^>]*>\s*/i',
            '/<meta[^>]+name=["\']format-detection["\'][^>]*>\s*/i',
            '/<meta[^>]+http-equiv=["\']imagetoolbar["\'][^>]*>\s*/i',
            '/<meta[^>]+http-equiv=["\']X-UA-Compatible["\'][^>]*>\s*/i',
        ];

        $buffer = preg_replace($patterns, '', $buffer);

        $inject_list = [
            '<meta name="viewport" id="meta_viewport" content="width=device-width,initial-scale=0.9,minimum-scale=0,maximum-scale=10">',
            '<meta name="HandheldFriendly" content="true">',
            '<meta name="format-detection" content="telephone=no">',
            '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_rb_theme.css">',
            '<script src="'.G5_URL.'/js/rb.common.js"></script>'
        ];

        // 테마 사용중일 때만 테마 자원 추가
        if (defined('G5_THEME_URL') && G5_THEME_URL) {
            $inject_list[] = '<link rel="stylesheet" href="'.G5_THEME_URL.'/rb.fonts/Pretendard/Pretendard.css">';

        }

        $inject = implode(PHP_EOL, $inject_list) . PHP_EOL;

        return preg_replace('/<\/head>/i', $inject.'</head>', $buffer, 1);
    }
}

if (!headers_sent()) {
    ob_start('rb_adm_meta_filter');
}
