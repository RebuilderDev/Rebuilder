<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!function_exists('rb_has_css_class')) {
    function rb_has_css_class($class_attr, $class_name)
    {
        $classes = preg_split('/\s+/', trim((string) $class_attr));
        if (!$classes) {
            return false;
        }

        return in_array($class_name, $classes, true);
    }
}

if (!function_exists('rb_is_descendant_of_class')) {
    function rb_is_descendant_of_class(DOMNode $node, $class_name)
    {
        $parent = $node->parentNode;
        while ($parent instanceof DOMElement) {
            if (rb_has_css_class($parent->getAttribute('class'), $class_name)) {
                return true;
            }
            $parent = $parent->parentNode;
        }

        return false;
    }
}

if (!function_exists('rb_find_first_child_by_class')) {
    function rb_find_first_child_by_class(DOMNode $node, $class_name)
    {
        foreach ($node->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }
            if (rb_has_css_class($child->getAttribute('class'), $class_name)) {
                return $child;
            }
        }

        return null;
    }
}

if (!function_exists('rb_replace_element_inner_html')) {
    function rb_replace_element_inner_html(DOMDocument $dom, DOMElement $element, $html)
    {
        while ($element->firstChild) {
            $element->removeChild($element->firstChild);
        }

        if ($html === '') {
            return;
        }

        $tmp = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $tmp->loadHTML(
            '<?xml encoding="utf-8" ?><div id="rb-fragment-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = null;
        foreach ($tmp->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'rb-fragment-root') {
                $root = $div;
                break;
            }
        }

        if (!$root) {
            return;
        }

        $children = array();
        foreach ($root->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            $element->appendChild($dom->importNode($child, true));
        }
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

        return is_array($result) ? $result : array();
    }
}

if (!function_exists('rb_pack_modules_into_sections_html')) {
    function rb_pack_modules_into_sections_html(DOMXPath $xpath)
    {
        $sections = array();
        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " rb_section_box ")]') as $section) {
            $sections[] = $section;
        }

        $modules = array();
        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " rb_layout_box ")]') as $module) {
            $modules[] = $module;
        }

        foreach ($sections as $section) {
            if (!($section instanceof DOMElement)) {
                continue;
            }

            $sec_uid = trim($section->getAttribute('data-sec-uid'));
            if ($sec_uid === '') {
                continue;
            }

            $inner_box = rb_find_first_child_by_class($section, 'flex_box');
            if (!$inner_box instanceof DOMElement) {
                continue;
            }

            $section_layout = trim($section->getAttribute('data-layout'));

            foreach ($modules as $module) {
                if (!($module instanceof DOMElement) || !$module->parentNode) {
                    continue;
                }
                if (rb_is_descendant_of_class($module, 'rb_section_box')) {
                    continue;
                }
                if (trim($module->getAttribute('data-sec-uid')) !== $sec_uid) {
                    continue;
                }

                if ($section_layout !== '') {
                    $module->setAttribute('data-layout', $section_layout);
                }

                $inner_box->appendChild($module);
            }
        }
    }
}

if (!function_exists('rb_render_nested_layout_placeholders')) {
    function rb_render_nested_layout_placeholders(DOMDocument $dom, DOMXPath $xpath, $is_index)
    {
        $placeholders = array();
        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " flex_box_inner ") and contains(concat(" ", normalize-space(@class), " "), " flex_box ") and @data-layout]') as $node) {
            $placeholders[] = $node;
        }

        foreach ($placeholders as $placeholder) {
            if (!($placeholder instanceof DOMElement)) {
                continue;
            }

            $layout_no = trim($placeholder->getAttribute('data-layout'));
            if ($layout_no === '') {
                continue;
            }

            $rendered = rb_render_layout_server_html($layout_no, $is_index);
            rb_replace_element_inner_html($dom, $placeholder, $rendered);
            $placeholder->setAttribute('data-layout-loaded', '1');
        }
    }
}

if (!function_exists('rb_prepare_layout_server_html')) {
    function rb_prepare_layout_server_html($html, $is_index)
    {
        if ($html === '' || !class_exists('DOMDocument')) {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="rb-server-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new DOMXPath($dom);
        rb_pack_modules_into_sections_html($xpath);
        rb_render_nested_layout_placeholders($dom, $xpath, $is_index);

        $root = null;
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'rb-server-root') {
                $root = $div;
                break;
            }
        }

        if (!$root) {
            return $html;
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
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
        if (isset($render_stack[$layout_no])) {
            return '';
        }

        $render_stack[$layout_no] = true;
        $render_map = rb_capture_layout_render_map(array($layout_no), $is_index);
        $html = isset($render_map[$layout_no]) ? $render_map[$layout_no] : '';
        unset($render_stack[$layout_no]);

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
