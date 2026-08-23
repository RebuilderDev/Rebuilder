<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('rb_layout_cache_paths')) {
    function rb_layout_cache_paths($layout_no, $is_shop, $theme_name, $layout_name, $is_index = false)
    {
        $safe_layout = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) $layout_no);
        if ($safe_layout === '') {
            $safe_layout = '0';
        }

        // 테마와 레이아웃 구성이 다른 캐시가 같은 파일을 공유하지 않도록 분리합니다.
        $context = substr(sha1((string) $theme_name . '|' . (string) $layout_name . '|' . ($is_index ? 'index' : 'sub')), 0, 12);
        $prefix = $is_shop ? 'rb_layout_shop_' : 'rb_layout_';
        $base = G5_DATA_PATH . '/cache/' . $prefix . $context . '_' . $safe_layout;

        return array(
            'cache' => $base . '.php',
            'hash' => $base . '.hash',
        );
    }
}

if (!function_exists('rb_layout_cache_load')) {
    function rb_layout_cache_load($layout_no, $is_shop, $theme_name, $layout_name, $is_index = false, &$cache_hit = null)
    {
        $cache_hit = false;
        $paths = rb_layout_cache_paths($layout_no, $is_shop, $theme_name, $layout_name, $is_index);
        if (!is_file($paths['cache'])) {
            return '';
        }

        // 캐시 안의 동적 위젯 코드가 기존 페이지 전역값을 그대로 사용할 수 있게 합니다.
        extract($GLOBALS, EXTR_SKIP);
        $html = include $paths['cache'];
        $cache_hit = true;

        return is_string($html) ? $html : '';
    }
}

if (!function_exists('rb_layout_cache_write')) {
    function rb_layout_cache_write($cache_file, $hash_file, $output, $checksum)
    {
        $cache_dir = dirname($cache_file);
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, G5_DIR_PERMISSION, true);
        }
        if (!is_dir($cache_dir)) {
            return false;
        }

        $tmp_file = tempnam($cache_dir, 'rb_layout_');
        if ($tmp_file === false) {
            return false;
        }

        $written = file_put_contents($tmp_file, $output, LOCK_EX);
        if ($written === false) {
            @unlink($tmp_file);
            return false;
        }

        // 완성된 파일만 노출해 동시 첫 요청에서 불완전한 PHP 캐시를 읽지 않게 합니다.
        if (!@rename($tmp_file, $cache_file)) {
            @unlink($cache_file);
            if (!@rename($tmp_file, $cache_file)) {
                @unlink($tmp_file);
                return false;
            }
        }

        file_put_contents($hash_file, $checksum, LOCK_EX);
        return true;
    }
}

if (!function_exists('rb_layout_cache_delete_all')) {
    function rb_layout_cache_delete_all()
    {
        $cache_dir = G5_DATA_PATH . '/cache';
        if (!is_dir($cache_dir)) {
            return;
        }

        foreach (array('rb_layout_*.php', 'rb_layout_*.hash', 'rb_layout_shop_*.php', 'rb_layout_shop_*.hash') as $pattern) {
            $files = glob($cache_dir . '/' . $pattern);
            if (!$files) {
                continue;
            }

            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
