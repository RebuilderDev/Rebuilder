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
    $insert_at = count($admin_menu['menu000']);

    foreach ($admin_menu['menu000'] as $index => $menu_item) {
        if (isset($menu_item[0]) && $menu_item[0] === $notification_menu[0]) {
            return $admin_menu;
        }
        if (isset($menu_item[0]) && $menu_item[0] === '000210') {
            $insert_at = $index + 1;
        }
    }

    array_splice($admin_menu['menu000'], $insert_at, 0, array($notification_menu));
    return $admin_menu;
}
