<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_replace('admin_menu', 'add_admin_bbs_menu_memo', 1, 1); // 관리자 메뉴를 추가함

function add_admin_bbs_menu_memo($admin_menu){ // 메뉴추가
    if (!isset($admin_menu['menu000']) || !is_array($admin_menu['menu000'])) {
        return $admin_menu;
    }

    $notification_menu = array(
        '000220', '알림 관리', G5_ADMIN_URL.'/rb/notification_form.php', 'rb_config',
    );
    $memo_menu = array(
        '000630', '쪽지 관리', G5_ADMIN_URL.'/rb/memo_form.php', 'rb_config',
    );
    $menu_items = array();
    foreach ($admin_menu['menu000'] as $menu_item) {
        $menu_code = isset($menu_item[0]) ? (string) $menu_item[0] : '';
        if ($menu_code === $memo_menu[0] || $menu_code === $notification_menu[0]) {
            continue;
        }
        $menu_items[] = $menu_item;
    }

    $insert_at = count($menu_items);
    foreach ($menu_items as $index => $menu_item) {
        if (isset($menu_item[0]) && $menu_item[0] === '000300') {
            $insert_at = $index;
            break;
        }
    }

    array_splice($menu_items, $insert_at, 0, array($memo_menu, $notification_menu));
    $admin_menu['menu000'] = $menu_items;
    return $admin_menu;
}
