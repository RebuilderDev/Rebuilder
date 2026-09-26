<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

include_once(__DIR__ . '/layout.cache.php');

if (!function_exists('rb_module_has_connection')) {
    function rb_module_has_connection(array $module)
    {
        // 새 설치 자료에서 연결을 생략한 경우만 처리한다. 기존 테마의 기본 설문 등은 유지한다.
        if (empty($module['md_theme']) || !function_exists('rb_tp_state')) return true;
        $state=rb_tp_state($module['md_theme']);
        if (!isset($state['scope']) || $state['scope']!=='main-design') return true;
        $type=isset($module['md_type'])?$module['md_type']:'';
        if (($type==='item' || $type==='item_tab') && (!defined('G5_USE_SHOP') || !G5_USE_SHOP)) return false;
        static $catalogs=array();
        $kind=($type==='latest' || $type==='tab')?'board':($type==='poll'?'poll':(($type==='item' || $type==='item_tab')?'category':''));
        if($kind!=='' && !isset($catalogs[$kind])) $catalogs[$kind]=rb_tp_catalog($kind);
        if ($type==='latest') return !empty($module['md_bo_table']) && isset($catalogs['board'][$module['md_bo_table']]);
        if ($type==='poll') return !empty($module['md_poll_id']) && isset($catalogs['poll'][$module['md_poll_id']]);
        if ($type==='item') return !empty($module['md_sca']) && isset($catalogs['category'][$module['md_sca']]);
        if ($type==='tab' || $type==='item_tab') {
            $field=$type==='tab'?'md_tab_list':'md_item_tab_list';
            $tabs=isset($module[$field])?json_decode($module[$field],true):null;
            if(!is_array($tabs) || !$tabs) return false;
            foreach($tabs as $tab) {
                if(!is_string($tab) && !is_int($tab)) return false;
                $parts=explode('||',(string)$tab,2);
                if($parts[0]==='' || !isset($catalogs[$kind][$parts[0]])) return false;
            }
            return true;
        }
        return true;
    }
}

if (!function_exists('rb_module_unconnected_html')) {
    function rb_module_unconnected_html(array $module, $is_admin = false)
    {
        // 연결 데이터가 없어도 저장된 제목과 제목 표시 설정은 유지한다.
        ob_start();
        if (isset($module['md_title']) && $module['md_title'] !== '') {
            $font = !empty($module['md_title_font']) ? $module['md_title_font'] : 'font-B';
            $color = !empty($module['md_title_color']) ? $module['md_title_color'] : '#25282b';
            $size = isset($module['md_title_size']) && $module['md_title_size'] !== '' ? $module['md_title_size'] : '20';
            ?>
            <li class="bbs_main_wrap_tit" style="display:<?php echo isset($module['md_title_hide']) && $module['md_title_hide'] == '1' ? 'none' : 'block'; ?>;">
                <div class="bbs_main_wrap_tit_l">
                    <h2 class="<?php echo htmlspecialchars($font, ENT_QUOTES, 'UTF-8'); ?>" style="color:<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>; font-size:<?php echo htmlspecialchars((string)$size, ENT_QUOTES, 'UTF-8'); ?>px;"><?php echo $module['md_title']; ?></h2>
                </div>
                <div class="cb"></div>
            </li>
            <?php
        }
        if ($is_admin) { ?><li class="no_data">모듈 설정에서 연결해 주세요.</li><?php }
        return ob_get_clean();
    }
}

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
