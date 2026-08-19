<?php
if (!defined('_GNUBOARD_')) exit;

function rb_business_console_admin_menu($admin_menu)
{
    if (!isset($admin_menu['menu000']) || !is_array($admin_menu['menu000'])) return $admin_menu;
    $items = array();
    foreach ($admin_menu['menu000'] as $item) {
        $code = isset($item[0]) ? (string) $item[0] : '';
        $url = isset($item[2]) ? (string) $item[2] : '';
        if ($code === '000860' || $code === '000999' || strpos($url, '/rb/business_console_config.php') !== false) continue;
        $items[] = $item;
    }
    while ($items && isset($items[count($items)-1][0]) && (string) $items[count($items)-1][0] === '000000') array_pop($items);
    $items[] = array('000000', '　', G5_ADMIN_URL, 'rb_config');
    $items[] = array('000999', '비즈니스 콘솔 설정', G5_ADMIN_URL.'/rb/business_console_config.php', 'rb_business_console_config');
    $admin_menu['menu000'] = $items;
    return $admin_menu;
}
add_replace('admin_menu', 'rb_business_console_admin_menu', 999, 1);

function rb_business_console_sideview($items, $data = array())
{
    global $member, $is_admin;
    if (empty($member['mb_id'])) return $items;
    include_once(G5_PATH.'/rb/rb.console/console.lib.php');
    $config = rb_console_config();
    if (($is_admin || (!empty($config['bc_enabled']) && (int)$member['mb_level'] >= (int)$config['bc_min_level']))) $items['business_console'] = '<a href="'.G5_URL.'/rb/business.php">비즈니스 콘솔</a>';
    return $items;
}
add_replace('member_sideview_items', 'rb_business_console_sideview', 20, 2);
