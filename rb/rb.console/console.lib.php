<?php
if (!defined('_GNUBOARD_')) exit;

if (!defined('RB_BUSINESS_CONSOLE_VERSION')) define('RB_BUSINESS_CONSOLE_VERSION', '1.0.0');

function rb_console_alert_output_clear()
{
    global $rb_console_alert_buffer_level;

    if (!isset($rb_console_alert_buffer_level)) return;
    while (ob_get_level() > (int) $rb_console_alert_buffer_level) {
        ob_end_clean();
    }
}

function rb_console_alert_output_start()
{
    global $rb_console_alert_buffer_level;

    if (isset($rb_console_alert_buffer_level)) return;
    $rb_console_alert_buffer_level = ob_get_level();
    ob_start();
    add_event('alert', 'rb_console_alert_output_clear', 999, 0);
}

function rb_console_alert_output_finish()
{
    global $rb_console_alert_buffer_level;

    if (!isset($rb_console_alert_buffer_level)) return;
    while (ob_get_level() > (int) $rb_console_alert_buffer_level) {
        ob_end_flush();
    }
    unset($rb_console_alert_buffer_level);
}

function rb_console_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rb_console_menu_icon($path, $fallback = 'fa-circle-o')
{
    $path = trim((string) $path);
    if ($path !== '') {
        return '<span class="rb-console-menu-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="'.rb_console_h($path).'"></path></svg></span>';
    }
    $fallback = preg_replace('/[^a-z0-9_-]/i', '', (string) $fallback);
    if ($fallback === '') $fallback = 'fa-circle-o';
    return '<i class="fa '.rb_console_h($fallback).' rb-console-menu-icon-fallback" aria-hidden="true"></i>';
}

function rb_console_table_exists($table)
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);
    if ($table === '') return false;
    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA='".sql_real_escape_string(G5_MYSQL_DB)."' AND TABLE_NAME='".sql_real_escape_string($table)."'", false);
    return !empty($row['cnt']);
}

function rb_console_config($refresh = false)
{
    static $cached = null;
    if ($cached !== null && !$refresh) return $cached;

    $cached = array(
        'bc_enabled' => 1,
        'bc_name' => '비즈니스 콘솔',
        'bc_default_route' => 'dashboard',
        'bc_min_level' => 2,
        'bc_show_point' => 1,
        'bc_footer_link' => 1,
        'bc_partner_policy' => 'all',
        'bc_sidebar_open' => 1,
        'bc_support_url' => '',
        'bc_notice' => '',
        'bc_updated_at' => ''
    );
    if (rb_console_table_exists('rb_business_console_config')) {
        $row = sql_fetch("SELECT * FROM rb_business_console_config WHERE bc_id=1", false);
        if (is_array($row) && !empty($row)) $cached = array_merge($cached, $row);
    }
    return $cached;
}

function rb_console_footer_link_enabled($config = array())
{
    if (!$config) $config = rb_console_config();
    if (empty($config['bc_enabled']) || empty($config['bc_footer_link'])) return false;

    $partner_enabled = isset($GLOBALS['pa']) && is_array($GLOBALS['pa']) && !empty($GLOBALS['pa']['pa_is']);
    if (function_exists('rb_ad_load_library')) rb_ad_load_library();
    $advertising_enabled = function_exists('rb_ad_enabled') && rb_ad_enabled();

    return $partner_enabled || $advertising_enabled;
}

function rb_console_business_features($config = array())
{
    if (!$config) $config = rb_console_config();
    $features = array(
        array('name'=>'입점', 'description'=>'상품·주문·문의·후기·정산을 입점사가 직접 관리합니다.', 'installed'=>is_file(G5_PATH.'/rb/rb.mod/partner/partner.lib.php'), 'condition'=>'입점 기능 설치 및 이용 권한 부여', 'manage_url'=>'./partner_form.php', 'manage_label'=>'입점 설정'),
        array('name'=>'광고 관리', 'description'=>'광고 신청·결제·심사·캘린더·유입 분석을 연동합니다.', 'installed'=>is_file(G5_PATH.'/rb/rb.mod/advertising/advertising.lib.php'), 'condition'=>'광고 관리 설치 및 이용 권한 부여', 'manage_url'=>'./ad_config.php', 'manage_label'=>'광고 설정'),
        array('name'=>'예약상품 관리', 'description'=>'입점사의 예약상품 등록과 예약 현황·시즌 관리를 연동합니다.', 'installed'=>is_file(G5_EXTEND_PATH.'/rb_reservation.extend.php'), 'condition'=>'입점·예약상품 관리 설치 및 입점사 권한 부여', 'manage_url'=>'./reservation_set.php', 'manage_label'=>'예약 설정'),
        array('name'=>'콘텐츠상품 관리', 'description'=>'파일상품 등록과 다운로드 권한·이력 관리를 연동합니다.', 'installed'=>is_file(G5_EXTEND_PATH.'/rb_file.extend.php'), 'condition'=>'입점·콘텐츠상품 관리 설치 및 입점사 권한 부여', 'manage_url'=>'./file_set.php', 'manage_label'=>'콘텐츠 설정'),
        array('name'=>'미디어상품 관리', 'description'=>'미디어상품 등록과 시청·다운로드·진도 관리를 연동합니다.', 'installed'=>is_file(G5_EXTEND_PATH.'/rb_media.extend.php'), 'condition'=>'입점·미디어상품 관리 설치 및 입점사 권한 부여', 'manage_url'=>'./media_set.php', 'manage_label'=>'미디어 설정'),
        array('name'=>'예치금', 'description'=>'콘솔 잔액·충전과 광고비 결제 및 입점 정산에 예치금을 연동합니다.', 'installed'=>is_file(G5_EXTEND_PATH.'/rb_point_c_ac.extend.php'), 'condition'=>'예치금 설치 및 사용 설정', 'manage_url'=>'./point_c_set.php', 'manage_label'=>'예치금 설정'),
        array('name'=>'포인트 충전', 'description'=>'콘솔의 포인트 충전과 광고비 포인트 결제를 연동합니다.', 'installed'=>is_file(G5_EXTEND_PATH.'/rb_point_ac.extend.php'), 'condition'=>'포인트 충전 설치 및 사용 설정', 'manage_url'=>'./point_set.php', 'manage_label'=>'포인트 설정'),
    );
    return function_exists('run_replace') ? run_replace('rb_business_console_features', $features, $config) : $features;
}

function rb_console_url($route = 'dashboard', $params = array())
{
    $params = array_merge(array('route' => $route), (array) $params);
    return G5_URL.'/rb/business.php?'.http_build_query($params, '', '&');
}

function rb_console_token()
{
    $token = get_session('ss_rb_business_console_token');
    if (!$token) {
        if (function_exists('get_random_token_string')) $token = get_random_token_string(32);
        else $token = md5(uniqid(mt_rand(), true));
        set_session('ss_rb_business_console_token', $token);
    }
    return $token;
}

function rb_console_token_field()
{
    return '<input type="hidden" name="rb_console_token" value="'.rb_console_h(rb_console_token()).'">';
}

function rb_console_check_token($terminate = true)
{
    $saved = (string) get_session('ss_rb_business_console_token');
    $posted = isset($_POST['rb_console_token']) ? (string) $_POST['rb_console_token'] : '';
    $valid = false;
    if ($saved !== '' && $posted !== '') $valid = function_exists('hash_equals') ? hash_equals($saved, $posted) : ($saved === $posted);
    if (!$valid && $terminate) alert('요청이 만료되었습니다. 화면을 새로고침한 뒤 다시 시도해 주세요.', rb_console_url());
    return $valid;
}

function rb_console_widget_layout_file($mb_id, $create_dir = false)
{
    $mb_id = trim((string) $mb_id);
    if ($mb_id === '') return '';
    $dir = G5_DATA_PATH.'/rb_console_widget_layout';
    if ($create_dir && !is_dir($dir)) @mkdir($dir, G5_DIR_PERMISSION, true);
    if (!is_dir($dir)) return '';
    return $dir.'/'.hash('sha256', $mb_id).'.json';
}

function rb_console_widget_layout_normalize($layout)
{
    $safe = array('order'=>array(), 'hidden'=>array(), 'widths'=>array());
    if (!is_array($layout)) return $safe;

    foreach ((array) (isset($layout['order']) ? $layout['order'] : array()) as $widget_id) {
        $widget_id = preg_replace('/[^a-z0-9._-]/i', '', (string) $widget_id);
        if ($widget_id !== '' && !in_array($widget_id, $safe['order'], true)) $safe['order'][] = $widget_id;
        if (count($safe['order']) >= 100) break;
    }
    foreach ((array) (isset($layout['hidden']) ? $layout['hidden'] : array()) as $widget_id) {
        $widget_id = preg_replace('/[^a-z0-9._-]/i', '', (string) $widget_id);
        if ($widget_id !== '' && !in_array($widget_id, $safe['hidden'], true)) $safe['hidden'][] = $widget_id;
        if (count($safe['hidden']) >= 100) break;
    }
    foreach ((array) (isset($layout['widths']) ? $layout['widths'] : array()) as $widget_id => $width) {
        $widget_id = preg_replace('/[^a-z0-9._-]/i', '', (string) $widget_id);
        if ($widget_id === '') continue;
        $safe['widths'][$widget_id] = (int) $width === 2 ? 2 : 1;
        if (count($safe['widths']) >= 100) break;
    }
    return $safe;
}

function rb_console_widget_layout_load($mb_id)
{
    $file = rb_console_widget_layout_file($mb_id, false);
    if ($file === '' || !is_file($file)) return rb_console_widget_layout_normalize(array());
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return rb_console_widget_layout_normalize(array());
    return rb_console_widget_layout_normalize(json_decode($raw, true));
}

function rb_console_widget_layout_save($mb_id, $layout)
{
    $file = rb_console_widget_layout_file($mb_id, true);
    if ($file === '') return false;
    $json = json_encode(rb_console_widget_layout_normalize($layout), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return $json !== false && @file_put_contents($file, $json, LOCK_EX) !== false;
}

function rb_console_default_registry($registry, $context = array())
{
    $registry['dashboard'] = array(
        'id' => 'dashboard', 'group' => 'dashboard', 'group_title' => '대시보드',
        'title' => '대시보드', 'icon' => 'fa-home',
        'group_icon_path' => 'M10.772 2.688a2 2 0 0 1 2.456 0l8.384 6.52c.753.587.337 1.792-.615 1.792H20v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8h-.997c-.953 0-1.367-1.206-.615-1.791z',
        'order' => 10, 'group_order' => 0, 'menu' => false,
        'capability' => 'console.access', 'callback' => 'rb_console_render_dashboard'
    );
    return $registry;
}

function rb_console_registry($context = array())
{
    $registry = rb_console_default_registry(array(), $context);
    $registry = run_replace('rb_business_console_registry', $registry, $context);
    if (!is_array($registry)) $registry = array();
    foreach ($registry as $id => $item) {
        if (!is_array($item)) { unset($registry[$id]); continue; }
        $item['id'] = isset($item['id']) ? preg_replace('/[^a-z0-9._-]/i', '', $item['id']) : preg_replace('/[^a-z0-9._-]/i', '', $id);
        if ($item['id'] === '' || empty($item['title']) || empty($item['callback']) || !is_callable($item['callback'])) { unset($registry[$id]); continue; }
        $item['group'] = isset($item['group']) ? preg_replace('/[^a-z0-9._-]/i', '', $item['group']) : 'etc';
        $item['group_title'] = isset($item['group_title']) ? $item['group_title'] : '기타';
        $item['icon'] = isset($item['icon']) ? preg_replace('/[^a-z0-9_-]/i', '', $item['icon']) : 'fa-circle-o';
        $item['group_icon_path'] = isset($item['group_icon_path']) ? trim((string) $item['group_icon_path']) : '';
        $item['order'] = isset($item['order']) ? (int) $item['order'] : 100;
        $item['group_order'] = isset($item['group_order']) ? (int) $item['group_order'] : 100;
        $item['menu'] = !isset($item['menu']) || (bool) $item['menu'];
        $item['menu_parent'] = isset($item['menu_parent']) ? preg_replace('/[^a-z0-9._-]/i', '', $item['menu_parent']) : '';
        $item['menu_section'] = isset($item['menu_section']) ? trim(strip_tags((string) $item['menu_section'])) : '';
        $item['capability'] = isset($item['capability']) ? $item['capability'] : 'console.access';
        $registry[$id] = $item;
    }
    uasort($registry, function ($a, $b) {
        if ($a['group'] === $b['group']) return $a['order'] - $b['order'];
        if ($a['group_order'] !== $b['group_order']) return $a['group_order'] - $b['group_order'];
        return strcmp($a['group'], $b['group']);
    });
    return $registry;
}

function rb_console_can($capability, $context = array())
{
    global $member, $is_admin;
    $config = rb_console_config();
    $allowed = !empty($member['mb_id']) && ($is_admin || (int) $member['mb_level'] >= (int) $config['bc_min_level']);
    if ($capability === 'console.admin') $allowed = ($is_admin === 'super');
    return (bool) run_replace('rb_business_console_capability', $allowed, $capability, $context);
}

function rb_console_visible_registry($registry, $context = array())
{
    foreach ($registry as $id => $item) {
        if (!rb_console_can($item['capability'], array_merge($context, array('route' => $id, 'item' => $item)))) unset($registry[$id]);
    }
    return $registry;
}

function rb_console_render_dashboard($context = array())
{
    global $member;
    $registry = isset($context['registry']) ? $context['registry'] : array();
    $groups = array();
    foreach ($registry as $item) {
        if ($item['id'] === 'dashboard' || empty($item['menu'])) continue;
        $groups[$item['group']] = isset($groups[$item['group']]) ? $groups[$item['group']] + 1 : 1;
    }
    $widgets = run_replace('rb_business_console_dashboard_widgets', array(), $context);
    if (!is_array($widgets)) $widgets = array();
    foreach ($widgets as $widget_key => $widget_value) {
        if (!is_array($widget_value)) { unset($widgets[$widget_key]); continue; }
        $widgets[$widget_key]['_widget_id'] = $widget_key;
    }
    $widget_layout = rb_console_widget_layout_load(isset($member['mb_id']) ? $member['mb_id'] : '');
    $widget_layout_order = array_flip($widget_layout['order']);
    uasort($widgets, function ($a, $b) use ($widget_layout_order) {
        $a_id = isset($a['_widget_id']) ? $a['_widget_id'] : '';
        $b_id = isset($b['_widget_id']) ? $b['_widget_id'] : '';
        $a_rank = isset($widget_layout_order[$a_id]) ? (int) $widget_layout_order[$a_id] : 10000 + (isset($a['order']) ? (int) $a['order'] : 100);
        $b_rank = isset($widget_layout_order[$b_id]) ? (int) $widget_layout_order[$b_id] : 10000 + (isset($b['order']) ? (int) $b['order'] : 100);
        return $a_rank - $b_rank;
    });
    ?><div class="rb-console-dashboard-grid" data-layout-endpoint="<?php echo G5_URL; ?>/rb/rb.console/widget.ajax.php" data-layout-token="<?php echo rb_console_h(rb_console_token()); ?>"><?php
    foreach ($widgets as $widget_id => $widget) {
        $widget_id = preg_replace('/[^a-z0-9._-]/i', '', (string) $widget_id);
        if ($widget_id === '') continue;
        $chart = isset($widget['chart']) && is_array($widget['chart']) ? $widget['chart'] : array();
        $latest = isset($widget['latest']) && is_array($widget['latest']) ? $widget['latest'] : array();
        $chart_id = 'rb-console-chart-'.preg_replace('/[^a-z0-9_-]/i', '-', $widget_id);
        $widget_width = isset($widget['span']) && (int) $widget['span'] === 1 ? 2 : 1;
        if (isset($widget_layout['widths'][$widget_id])) $widget_width = (int) $widget_layout['widths'][$widget_id] === 2 ? 2 : 1;
        $widget_span = $widget_width === 1 ? 2 : 1;
        $widget_hidden = in_array($widget_id, $widget_layout['hidden'], true);
        $widget_title = isset($widget['title']) ? $widget['title'] : '현황';
    ?>
    <section class="cbox rb-card rb-console-dashboard-widget<?php echo $widget_hidden ? ' is-collapsed' : ''; ?>" data-widget-id="<?php echo rb_console_h($widget_id); ?>" data-widget-title="<?php echo rb_console_h($widget_title); ?>" data-console-width="<?php echo $widget_width; ?>" data-console-span="<?php echo $widget_span; ?>">
        <div class="rb-console-widget-head">
            <h2><?php echo rb_console_h($widget_title); ?></h2>
            <div class="rb-console-widget-tools">
                <select class="rb-console-widget-width" aria-label="<?php echo rb_console_h($widget_title); ?> 위젯 폭">
                    <option value="1"<?php echo $widget_width === 1 ? ' selected' : ''; ?>>X1</option>
                    <option value="2"<?php echo $widget_width === 2 ? ' selected' : ''; ?>>X2</option>
                </select>
                <button type="button" class="rb-console-widget-close" aria-label="<?php echo rb_console_h($widget_title); ?> 위젯 접기">×</button>
            </div>
        </div>
        <?php if (!empty($widget['description'])) { ?><p><?php echo rb_console_h($widget['description']); ?></p><?php } ?>
        <?php if (!empty($widget['kpis'])) { ?><div class="local_ov01 local_ov"><?php foreach ($widget['kpis'] as $kpi) { ?><span class="btn_ov01"><span class="ov_txt"><?php echo rb_console_h($kpi['label']); ?></span><span class="ov_num"><?php echo rb_console_h($kpi['value']); ?></span></span><?php } ?></div><?php } ?>
        <?php if ($chart) { ?>
        <div id="<?php echo $chart_id; ?>"></div>
        <script>
        (function(){
            if (typeof ApexCharts === 'undefined') return;
            var el = document.querySelector(<?php echo json_encode('#'.$chart_id); ?>);
            if (!el) return;
            var widgetId = <?php echo json_encode($widget_id); ?>;
            var isDark = document.body.classList.contains('adm-dark');
            var textColor = isDark ? '#aab4bf' : '#6b7684';
            var gridColor = isDark ? 'rgba(255,255,255,0.10)' : '#e5e5ef';
            window.rbConsoleCharts = window.rbConsoleCharts || [];
            window.rbConsoleChartEntries = window.rbConsoleChartEntries || {};
            if (typeof window.rbConsoleRenderWidgetChart !== 'function') {
                window.rbConsoleRenderWidgetChart = function(id) {
                    var entry = window.rbConsoleChartEntries && window.rbConsoleChartEntries[id];
                    if (!entry || entry.rendered) return;
                    entry.rendered = true;
                    window.rbConsoleCharts.push(entry.chart);
                    entry.chart.render();
                };
            }
            if (typeof window.rbConsoleApplyChartTheme !== 'function') {
                window.rbConsoleApplyChartTheme = function(enabled) {
                    var nextTextColor = enabled ? '#aab4bf' : '#6b7684';
                    var nextGridColor = enabled ? 'rgba(255,255,255,0.10)' : '#e5e5ef';
                    window.rbConsoleCharts.forEach(function(chart) {
                        if (!chart || typeof chart.updateOptions !== 'function') return;
                        chart.updateOptions({
                            chart: {foreColor: nextTextColor, background: 'transparent'},
                            theme: {mode: enabled ? 'dark' : 'light'},
                            grid: {borderColor: nextGridColor},
                            tooltip: {theme: enabled ? 'dark' : 'light'}
                        }, false, false);
                    });
                };
            }
            var consoleChart = new ApexCharts(el, {
                chart: {type:<?php echo json_encode(isset($chart['type']) ? $chart['type'] : 'line'); ?>,height:<?php echo isset($chart['height']) ? (int) $chart['height'] : 280; ?>,toolbar:{show:false},zoom:{enabled:false},foreColor:textColor,background:'transparent'},
                theme: {mode:isDark ? 'dark' : 'light'},
                series: <?php echo json_encode(isset($chart['series']) ? $chart['series'] : array(), JSON_UNESCAPED_UNICODE); ?>,
                xaxis: {categories:<?php echo json_encode(isset($chart['categories']) ? $chart['categories'] : array(), JSON_UNESCAPED_UNICODE); ?>,axisBorder:{show:false},axisTicks:{show:false},crosshairs:{show:false}},
                stroke: {curve:'smooth',width:2},
                markers: {size:0},
                dataLabels: {enabled:false},
                colors: <?php echo json_encode(isset($chart['colors']) ? $chart['colors'] : array('#6c5ce7','#00b894','#0984e3')); ?>,
                grid: {borderColor:gridColor,strokeDashArray:3},
                legend: {show:true},
                yaxis: {decimalsInFloat:0,labels:{formatter:function(value){return Math.round(value).toLocaleString();}}},
                tooltip: {shared:true,intersect:false,theme:isDark ? 'dark' : 'light',y:{formatter:function(value){return Math.round(value).toLocaleString();}}}
            });
            window.rbConsoleChartEntries[widgetId] = {chart:consoleChart, rendered:false};
            var widgetSection = el.closest ? el.closest('.rb-console-dashboard-widget') : null;
            if (!widgetSection || !widgetSection.classList.contains('is-collapsed')) window.rbConsoleRenderWidgetChart(widgetId);
        })();
        </script>
        <?php } ?>
        <?php if (!empty($latest['columns'])) {
            $latest_columns = (array) $latest['columns'];
            $latest_rows = isset($latest['rows']) && is_array($latest['rows']) ? $latest['rows'] : array();
        ?>
        <div class="rb-mini"><div class="rb-table-wrap"><table class="rb-table">
            <caption><?php echo rb_console_h(isset($latest['caption']) ? $latest['caption'] : (isset($widget['title']) ? $widget['title'] : '최근 목록')); ?></caption>
            <thead><tr><?php foreach ($latest_columns as $column) { $column_class = isset($column['class']) ? preg_replace('/[^a-z0-9 _-]/i', '', $column['class']) : ''; ?><th<?php echo $column_class !== '' ? ' class="'.rb_console_h($column_class).'"' : ''; ?>><?php echo rb_console_h(isset($column['label']) ? $column['label'] : ''); ?></th><?php } ?></tr></thead>
            <tbody>
            <?php if (!$latest_rows) { ?><tr><td colspan="<?php echo count($latest_columns); ?>"><?php echo rb_console_h(isset($latest['empty']) ? $latest['empty'] : '등록된 내용이 없습니다.'); ?></td></tr><?php } ?>
            <?php foreach ($latest_rows as $row) { ?><tr><?php foreach ($latest_columns as $column) {
                $column_key = isset($column['key']) ? preg_replace('/[^a-z0-9_-]/i', '', $column['key']) : '';
                $column_class = isset($column['class']) ? preg_replace('/[^a-z0-9 _-]/i', '', $column['class']) : '';
                $cell_value = isset($row['cells']) && is_array($row['cells']) && array_key_exists($column_key, $row['cells']) ? $row['cells'][$column_key] : '';
                $cell_badge = isset($row['badges'][$column_key]) && is_array($row['badges'][$column_key]) ? $row['badges'][$column_key] : array();
                $cell_image = isset($row['images'][$column_key]) && is_array($row['images'][$column_key]) ? $row['images'][$column_key] : array();
                $cell_link = isset($row['links'][$column_key]) ? $row['links'][$column_key] : '';
            ?><td<?php echo $column_class !== '' ? ' class="'.rb_console_h($column_class).'"' : ''; ?>><?php if ($cell_image && !empty($cell_image['src'])) {
                $image_width = isset($cell_image['width']) ? max(20, min(120, (int) $cell_image['width'])) : 50;
                $image_height = isset($cell_image['height']) ? max(20, min(120, (int) $cell_image['height'])) : $image_width;
                if ($cell_link !== '') { ?><a href="<?php echo rb_console_h($cell_link); ?>"><?php }
                ?><img class="rb-console-list-image" src="<?php echo rb_console_h($cell_image['src']); ?>" alt="<?php echo rb_console_h(isset($cell_image['alt']) ? $cell_image['alt'] : $cell_value); ?>" width="<?php echo $image_width; ?>" height="<?php echo $image_height; ?>" loading="lazy"><?php
                if ($cell_link !== '') { ?></a><?php }
            } elseif ($cell_badge) { $badge_type = isset($cell_badge['type']) && $cell_badge['type'] === 'ok' ? 'ok' : 'wait'; ?><span class="rb-badge-<?php echo $badge_type; ?>"><?php echo rb_console_h(isset($cell_badge['label']) ? $cell_badge['label'] : $cell_value); ?></span><?php } elseif ($cell_link !== '') { ?><a href="<?php echo rb_console_h($cell_link); ?>"><?php echo rb_console_h($cell_value); ?></a><?php } else { echo rb_console_h($cell_value); } ?></td><?php } ?></tr><?php } ?>
            </tbody>
        </table></div></div>
        <?php } ?>
        <?php if (!empty($widget['links'])) { ?><div class="btn_confirm01 btn_confirm"><?php foreach ($widget['links'] as $link) { ?><a class="btn btn_03" href="<?php echo rb_console_h($link['url']); ?>"><?php echo rb_console_h($link['label']); ?></a> <?php } ?></div><?php } ?>
    </section>
    <?php } ?>
    </div>
    <script>
    (function(){
        var grid = document.querySelector('.rb-console-dashboard-grid');
        var miniList = document.getElementById('rb-console-collapsed-widget-list');
        if (!grid || !miniList) return;
        var endpoint = grid.getAttribute('data-layout-endpoint') || '';
        var token = grid.getAttribute('data-layout-token') || '';
        var saveTimer = null;

        function widgets() {
            return Array.prototype.slice.call(grid.querySelectorAll('.rb-console-dashboard-widget'));
        }

        function rebuildMiniList() {
            miniList.innerHTML = '';
            widgets().forEach(function(widget){
                if (!widget.classList.contains('is-collapsed')) return;
                var item = document.createElement('li');
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'rb-console-widget-restore';
                button.setAttribute('data-widget-id', widget.getAttribute('data-widget-id') || '');
                button.innerHTML = '<span></span><span class="rb-console-widget-restore-arrow" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg></span>';
                button.querySelector('span').textContent = (widget.getAttribute('data-widget-title') || '현황') + ' 위젯';
                item.appendChild(button);
                miniList.appendChild(item);
            });
            miniList.parentNode.classList.toggle('has-widgets', miniList.children.length > 0);
        }

        function layoutData() {
            var layout = {order:[], hidden:[], widths:{}};
            widgets().forEach(function(widget){
                var id = widget.getAttribute('data-widget-id') || '';
                if (!id) return;
                layout.order.push(id);
                if (widget.classList.contains('is-collapsed')) layout.hidden.push(id);
                layout.widths[id] = parseInt(widget.getAttribute('data-console-width'), 10) === 2 ? 2 : 1;
            });
            return layout;
        }

        function saveLayout() {
            if (!endpoint || !token) return;
            var data = new FormData();
            data.append('rb_console_token', token);
            data.append('layout', JSON.stringify(layoutData()));
            fetch(endpoint, {method:'POST', body:data, credentials:'same-origin'})
                .then(function(response){ return response.json(); })
                .then(function(response){
                    if (!response || response.result !== 'ok') throw new Error(response && response.message ? response.message : '위젯 설정을 저장하지 못했습니다.');
                })
                .catch(function(error){
                    window.alert(error && error.message ? error.message : '위젯 설정을 저장하지 못했습니다.');
                });
        }

        function queueSave() {
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(saveLayout, 180);
        }

        function resizeCharts() {
            window.setTimeout(function(){
                try { window.dispatchEvent(new Event('resize')); }
                catch (e) {
                    var resizeEvent = document.createEvent('Event');
                    resizeEvent.initEvent('resize', true, true);
                    window.dispatchEvent(resizeEvent);
                }
            }, 40);
        }

        grid.addEventListener('change', function(event){
            var select = event.target.closest ? event.target.closest('.rb-console-widget-width') : null;
            if (!select) return;
            var widget = select.closest('.rb-console-dashboard-widget');
            if (!widget) return;
            var width = parseInt(select.value, 10) === 2 ? 2 : 1;
            widget.setAttribute('data-console-width', width);
            widget.setAttribute('data-console-span', width === 1 ? 2 : 1);
            queueSave();
            resizeCharts();
        });

        grid.addEventListener('click', function(event){
            var close = event.target.closest ? event.target.closest('.rb-console-widget-close') : null;
            if (!close) return;
            var widget = close.closest('.rb-console-dashboard-widget');
            if (!widget) return;
            widget.classList.add('is-collapsed');
            rebuildMiniList();
            queueSave();
        });

        miniList.addEventListener('click', function(event){
            var button = event.target.closest ? event.target.closest('.rb-console-widget-restore') : null;
            if (!button) return;
            var id = button.getAttribute('data-widget-id') || '';
            var widget = widgets().filter(function(item){ return item.getAttribute('data-widget-id') === id; })[0];
            if (!widget) return;
            var firstWidget = grid.querySelector('.rb-console-dashboard-widget');
            if (firstWidget && firstWidget !== widget) grid.insertBefore(widget, firstWidget);
            widget.classList.remove('is-collapsed');
            rebuildMiniList();
            if (typeof window.rbConsoleRenderWidgetChart === 'function') window.rbConsoleRenderWidgetChart(id);
            queueSave();
            resizeCharts();
            window.requestAnimationFrame(function(){
                var top = widget.getBoundingClientRect().top + window.pageYOffset - 85;
                window.scrollTo({top: Math.max(0, top), behavior: 'smooth'});
            });
        });

        if (typeof Sortable !== 'undefined') {
            new Sortable(grid, {
                animation: 150,
                draggable: '.rb-console-dashboard-widget:not(.is-collapsed)',
                handle: '.rb-console-widget-head',
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 3,
                ghostClass: 'rb-console-widget-ghost',
                onEnd: function(){ rebuildMiniList(); queueSave(); }
            });
        }
        rebuildMiniList();
    })();
    </script>
    <?php if (!$groups) { ?>
    <div class="local_desc01 local_desc">
        <p><strong>현재 사용할 수 있는 비즈니스 메뉴가 없습니다.</strong><br>기능이 설치되고 이용 권한이 부여되면 별도 설정 없이 왼쪽 메뉴와 대시보드에 자동으로 표시됩니다.</p>
    </div>
    <?php $business_features = rb_console_business_features(); ?>
    <h2 style="margin-top:20px">확장 가능한 비즈니스 기능</h2>
    <div class="tbl_head01 tbl_wrap rb-console-ready-table">
        <table><caption>확장 가능한 비즈니스 기능 안내</caption><thead><tr><th>기능</th><th>콘솔 연동 내용</th><th>표시 조건</th></tr></thead><tbody>
        <?php foreach ($business_features as $feature) { ?>
        <tr><td><strong><?php echo rb_console_h(isset($feature['name']) ? $feature['name'] : ''); ?></strong></td><td><?php echo rb_console_h(isset($feature['description']) ? $feature['description'] : ''); ?></td><td><?php echo rb_console_h(isset($feature['condition']) ? $feature['condition'] : '기능 설치 및 이용 권한 부여'); ?></td></tr>
        <?php } ?>
        </tbody></table>
    </div>
    <?php return; } ?>
<?php }
