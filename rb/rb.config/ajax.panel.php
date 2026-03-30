<?php
include_once('../../common.php');

if (!defined('_GNUBOARD_')) exit;

include_once(G5_PATH . '/rb/rb.lib/lib.panel.php');

$panel_id = isset($_REQUEST['panel_id']) ? trim((string)$_REQUEST['panel_id']) : '';

if (empty($is_admin)) {
    http_response_code(403);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'admin only',
    ));
    exit;
}

if ($panel_id === '') {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'panel_id is required',
    ));
    exit;
}

$rb_panel_context = rb_config_panel_context(array(
    'is_admin' => isset($is_admin) ? $is_admin : false,
    'rb_core' => isset($rb_core) ? $rb_core : array(),
    'rb_config' => isset($rb_config) ? $rb_config : array(),
));

$rb_side_panels = rb_config_get_all_panels($rb_panel_context);
$target_panel = null;

foreach ($rb_side_panels as $panel) {
    if (!empty($panel['id']) && $panel['id'] === $panel_id) {
        $target_panel = $panel;
        break;
    }
}

if (!$target_panel || empty($target_panel['file']) || !is_file($target_panel['file'])) {
    http_response_code(404);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'panel not found',
    ));
    exit;
}

rb_config_include_panel_file($target_panel['file'], 'ajax', $rb_panel_context, $target_panel);
exit;
