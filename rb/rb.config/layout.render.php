<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

include_once(__DIR__ . '/layout.cache.php');

if (!function_exists('rb_capture_layout_render_map')) {
    function rb_capture_layout_render_map(array $layouts, $is_index)
    {
        $layouts = array_values(array_unique(array_filter(array_map('strval', $layouts), 'strlen')));
        if (empty($layouts)) {
            return array();
        }

        $post_backup = $_POST;
        $capture_backup_exists = array_key_exists('rb_layout_capture_only', $GLOBALS);
        $capture_backup = $capture_backup_exists ? $GLOBALS['rb_layout_capture_only'] : null;

        $GLOBALS['rb_layout_capture_only'] = true;
        $_POST = $post_backup;
        $_POST['layouts'] = $layouts;
        $_POST['is_index'] = $is_index ? 'true' : 'false';

        extract($GLOBALS, EXTR_SKIP);
        $capture_script = defined('_SHOP_')
            ? G5_PATH . '/rb/rb.config/ajax.layout_set.shop.php'
            : G5_PATH . '/rb/rb.config/ajax.layout_set.php';

        $result = include $capture_script;

        $_POST = $post_backup;
        if ($capture_backup_exists) {
            $GLOBALS['rb_layout_capture_only'] = $capture_backup;
        } else {
            unset($GLOBALS['rb_layout_capture_only']);
        }

        if (!is_array($result)) {
            return array();
        }

        return $result;
    }
}

if (!function_exists('rb_render_layout_server_html')) {
    function rb_render_layout_server_html($layout_no, $is_index = false)
    {
        static $render_stack = array();

        $layout_no = trim((string) $layout_no);
        if ($layout_no === '') {
            return '';
        }

        $is_shop = defined('_SHOP_');
        $stack_key = ($is_shop ? 'shop:' : 'general:') . $layout_no;
        if (isset($render_stack[$stack_key])) {
            return '';
        }

        $render_stack[$stack_key] = true;

        // 일반 방문자는 생성된 PHP 캐시를 바로 실행합니다. DB 체크와 생성기는
        // 캐시가 없을 때만 동작하며, 관리자 편집 화면은 기존 실시간 렌더를 유지합니다.
        $is_admin = isset($GLOBALS['is_admin']) ? $GLOBALS['is_admin'] : '';
        if (!$is_admin) {
            $rb_core = isset($GLOBALS['rb_core']) && is_array($GLOBALS['rb_core']) ? $GLOBALS['rb_core'] : array();
            $theme_name = isset($rb_core['theme']) ? $rb_core['theme'] : '';
            $layout_key = $is_shop ? 'layout_shop' : 'layout';
            $layout_name = isset($rb_core[$layout_key]) ? $rb_core[$layout_key] : '';
            $cache_hit = false;
            $cached_html = rb_layout_cache_load($layout_no, $is_shop, $theme_name, $layout_name, (bool) $is_index, $cache_hit);
            if ($cache_hit) {
                unset($render_stack[$stack_key]);
                return $cached_html;
            }
        }

        $render_map = rb_capture_layout_render_map(array($layout_no), $is_index);
        $html = isset($render_map[$layout_no]) ? $render_map[$layout_no] : '';
        unset($render_stack[$stack_key]);

        return $html;
    }
}

if (!function_exists('rb_render_server_layout_box')) {
    function rb_render_server_layout_box($layout_no = null, array $attrs = array(), $is_index = null)
    {
        static $layout_seq = 0;

        if ($layout_no === null || $layout_no === '') {
            $layout_seq++;
            $layout_no = (string) $layout_seq;
        } else {
            $layout_no = (string) $layout_no;
        }

        if ($is_index === null) {
            $is_index = defined('_INDEX_');
        }

        $classes = array('flex_box');
        if (!empty($attrs['class'])) {
            $classes[] = trim($attrs['class']);
        }
        $attrs['class'] = trim(implode(' ', array_filter($classes)));
        $attrs['data-layout'] = $layout_no;
        $attrs['data-layout-loaded'] = '1';

        $attr_html = '';
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $attr_html .= ' ' . $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<div' . $attr_html . '>' . rb_render_layout_server_html($layout_no, (bool) $is_index) . '</div>';
    }
}

if (!function_exists('rb_prime_server_layouts')) {
    function rb_prime_server_layouts(array $layouts, $is_index = false)
    {
        $layouts = array_values(array_unique(array_filter(array_map('strval', $layouts), 'strlen')));
        if (empty($layouts)) {
            return;
        }

        $render_map = rb_capture_layout_render_map($layouts, (bool) $is_index);
        if (empty($render_map)) {
            return;
        }

        $json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $render_json = json_encode($render_map, $json_flags);
        if ($render_json === false) {
            return;
        }

        echo '<script>(function(w,d,map){'
            . 'w.rbServerLayoutMap=Object.assign(w.rbServerLayoutMap||{},map||{});'
            . 'if(!w.rbHydrateServerLayouts){'
            . 'w.rbHydrateServerLayouts=function(root){'
            . 'var source=w.rbServerLayoutMap||{};'
            . 'var fill=function(node){'
            . 'if(!node||node.nodeType!==1||!node.matches||!node.matches(".flex_box[data-layout]:not([data-layout-loaded])"))return;'
            . 'if(node.closest(".rb_section_box"))return;'
            . 'var key=node.getAttribute("data-layout")||"";'
            . 'if(!Object.prototype.hasOwnProperty.call(source,key))return;'
            . 'node.setAttribute("data-layout-loaded","1");'
            . 'if(w.jQuery){w.jQuery(node).html(source[key]);}else{node.innerHTML=source[key];}'
            . '};'
            . 'if(root&&root.nodeType===1)fill(root);'
            . 'if(root&&root.querySelectorAll){root.querySelectorAll(".flex_box[data-layout]:not([data-layout-loaded])").forEach(fill);}'
            . '};'
            . 'var observer=new MutationObserver(function(records){records.forEach(function(record){Array.prototype.forEach.call(record.addedNodes,function(node){w.rbHydrateServerLayouts(node);});});});'
            . 'observer.observe(d.documentElement,{childList:true,subtree:true});'
            . 'd.addEventListener("DOMContentLoaded",function(){w.rbHydrateServerLayouts(d);});'
            . '}'
            . 'w.rbHydrateServerLayouts(d);'
            . '})(window,document,' . $render_json . ');</script>' . "\n";
    }
}
