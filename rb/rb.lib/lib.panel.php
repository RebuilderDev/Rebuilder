<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('rb_config_panel_context')) {
    function rb_config_panel_context($vars = array()) {
        $context = array(
            'is_admin' => !empty($vars['is_admin']),
            'is_index' => defined('_INDEX_'),
            'is_shop' => defined('_SHOP_'),
            'has_section_panel' => (defined('_INDEX_') || !empty($_GET['gr_id']) || !empty($_GET['co_id'])),
            'g5_url' => defined('G5_URL') ? G5_URL : '',
            'g5_admin_url' => defined('G5_ADMIN_URL') ? G5_ADMIN_URL : '',
            'panel_dir' => defined('G5_PATH') ? (G5_PATH . '/rb/rb.config/panel') : dirname(__DIR__) . '/rb.config/panel',
            'dynamic_default_icon' => (defined('G5_URL') ? G5_URL : '') . '/rb/rb.config/image/icon_folder.svg',
            'rb_core' => isset($vars['rb_core']) ? $vars['rb_core'] : array(),
            'rb_config' => isset($vars['rb_config']) ? $vars['rb_config'] : array(),
        );

        return $context;
    }
}

if (!function_exists('rb_config_builtin_panels')) {
    function rb_config_builtin_panels($context) {
        return array(
            array(
                'id' => 'module',
                'title' => '모듈설정',
                'tooltip' => '모듈설정',
                'icon' => $context['g5_url'] . '/rb/rb.config/image/icon_mod.svg',
                'button_class' => 'mobule_set_btn',
                'panel_kind' => 'builtin',
                'builtin_target' => 'module',
                'order' => 10,
                'visible' => true,
            ),
            array(
                'id' => 'section',
                'title' => '섹션설정',
                'tooltip' => '섹션설정',
                'icon' => $context['g5_url'] . '/rb/rb.config/image/icon_sec.svg',
                'button_class' => 'section_set_btn',
                'panel_kind' => 'builtin',
                'builtin_target' => 'section',
                'order' => 20,
                'visible' => !empty($context['has_section_panel']),
            ),
            array(
                'id' => 'setting',
                'title' => '환경설정',
                'tooltip' => '환경설정',
                'icon' => $context['g5_url'] . '/rb/rb.config/image/icon_set.svg',
                'button_class' => 'setting_set_btn',
                'panel_kind' => 'builtin',
                'builtin_target' => 'setting',
                'order' => 30,
                'visible' => true,
            ),
        );
    }
}

if (!function_exists('rb_config_include_panel_file')) {
    function rb_config_include_panel_file($file, $stage, $context, $panel = array()) {
        $rb_panel_stage = $stage;
        $rb_panel_context = $context;
        $rb_panel = $panel;

        return include $file;
    }
}

if (!function_exists('rb_config_normalize_panel')) {
    function rb_config_normalize_panel($panel, $file, $context = array()) {
        if (!is_array($panel) || empty($panel['id'])) {
            return null;
        }

        if (!isset($panel['order'])) {
            $panel['order'] = 100;
        }

        if (!isset($panel['tooltip'])) {
            $panel['tooltip'] = isset($panel['title']) ? $panel['title'] : $panel['id'];
        }

        if (!isset($panel['button_class'])) {
            $panel['button_class'] = 'rb-dynamic-panel-btn';
        }

        if (!isset($panel['panel_kind'])) {
            $panel['panel_kind'] = 'dynamic';
        }

        if (!isset($panel['icon']) || !$panel['icon']) {
            $panel['icon'] = isset($panel['panel_kind']) && $panel['panel_kind'] === 'dynamic'
                ? (isset($context['dynamic_default_icon']) ? $context['dynamic_default_icon'] : '')
                : '';
        }

        if (!isset($panel['file'])) {
            $panel['file'] = $file;
        }

        return $panel;
    }
}

if (!function_exists('rb_config_load_dynamic_panels')) {
    function rb_config_load_dynamic_panels($context) {
        $panels = array();
        $panel_dir = !empty($context['panel_dir']) ? $context['panel_dir'] : dirname(__DIR__) . '/rb.config/panel';

        if (!is_dir($panel_dir)) {
            return $panels;
        }

        $files = glob($panel_dir . '/*.panel.php');
        if (!$files) {
            return $panels;
        }

        foreach ($files as $file) {
            $panel = rb_config_include_panel_file($file, 'register', $context);
            $panel = rb_config_normalize_panel($panel, $file, $context);
            if (!$panel) {
                continue;
            }

            $visible = true;
            if (isset($panel['visible'])) {
                $visible = is_callable($panel['visible']) ? call_user_func($panel['visible'], $context) : (bool)$panel['visible'];
            }

            if (!$visible) {
                continue;
            }

            $panels[] = $panel;
        }

        return $panels;
    }
}

if (!function_exists('rb_config_get_all_panels')) {
    function rb_config_get_all_panels($context) {
        $panels = array_merge(
            rb_config_builtin_panels($context),
            rb_config_load_dynamic_panels($context)
        );

        usort($panels, function($a, $b) {
            $a_order = isset($a['order']) ? (int)$a['order'] : 100;
            $b_order = isset($b['order']) ? (int)$b['order'] : 100;

            if ($a_order === $b_order) {
                return strcmp($a['id'], $b['id']);
            }

            return ($a_order < $b_order) ? -1 : 1;
        });

        return $panels;
    }
}

if (!function_exists('rb_config_render_dynamic_panels')) {
    function rb_config_render_dynamic_panels($panels, $context) {
        foreach ($panels as $panel) {
            if (!isset($panel['panel_kind']) || $panel['panel_kind'] !== 'dynamic') {
                continue;
            }
            ?>
            <div
                class="rb_config rb_config_panel_ext<?php echo !empty($panel['panel_class']) ? ' ' . $panel['panel_class'] : ''; ?>"
                id="rb_panel_<?php echo preg_replace('/[^a-zA-Z0-9_\-]/', '_', $panel['id']); ?>"
                data-panel-id="<?php echo $panel['id']; ?>"
                style="display:none;"
            >
                <?php rb_config_include_panel_file($panel['file'], 'render', $context, $panel); ?>
            </div>
            <?php
        }
    }
}
