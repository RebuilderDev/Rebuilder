<?php
if (!defined('_GNUBOARD_') || !defined('RB_BUSINESS_CONSOLE')) exit;
$console_config = rb_console_config();
$console_title = isset($rb_console_current['title']) ? $rb_console_current['title'] : $console_config['bc_name'];
$console_active_menu_id = !empty($rb_console_current['menu_parent']) ? $rb_console_current['menu_parent'] : $rb_console_current['id'];
$console_dashboard_item = isset($rb_console_registry['dashboard']) ? $rb_console_registry['dashboard'] : array();
$console_is_dashboard = $rb_console_current['id'] === 'dashboard';
$groups = array();
foreach ($rb_console_registry as $item) {
    if (empty($item['menu'])) continue;
    $group_id = $item['group'];
    if (!isset($groups[$group_id])) {
        $groups[$group_id] = array(
            'title' => $item['group_title'],
            'icon' => $item['icon'],
            'icon_path' => $item['group_icon_path'],
            'items' => array()
        );
    } elseif (empty($groups[$group_id]['icon_path']) && !empty($item['group_icon_path'])) {
        $groups[$group_id]['icon_path'] = $item['group_icon_path'];
    }
    $groups[$group_id]['items'][] = $item;
}
$console_profile_url = G5_BBS_URL.'/member_confirm.php?url='.urlencode(G5_BBS_URL.'/register_form.php');
$console_point_charge_enabled = isset($pnt) && is_array($pnt) && !empty($pnt['pnt_add_use']) && is_file(G5_PATH.'/rb/point.php');
$console_custom_point_enabled = isset($pnt_c) && is_array($pnt_c) && function_exists('insert_point_c') && function_exists('get_point_sum_c') && isset($member['rb_point']);
$console_custom_point_charge_enabled = $console_custom_point_enabled && !empty($pnt_c['pnt_add_use']) && is_file(G5_PATH.'/rb/point_c.php');
$console_custom_point_name = isset($pnt_c_name) && $pnt_c_name !== '' ? $pnt_c_name : '추가 포인트';
$console_custom_point_unit = isset($pnt_c_name_st) && $pnt_c_name_st !== '' ? $pnt_c_name_st : 'P';
$console_store_url = isset($rb_console_registry['partner.items']) && is_file(G5_PATH.'/store/index.php') ? G5_URL.'/store/?p='.rawurlencode($member['mb_id']) : '';
$console_is_dark = isset($_COOKIE['adm_dark']) && trim((string) $_COOKIE['adm_dark']) !== '' && trim((string) $_COOKIE['adm_dark']) !== '0';
$console_menu_collapsed = !empty($_COOKIE['g5_business_console_btn_gnb']);
$console_notification_unread_count = 0;
$console_notification_action_token = '';
$console_notification_category_tabs = array();
if (function_exists('rb_notification_table_ready') && rb_notification_table_ready()) {
    $console_notification_unread_count = rb_notification_unread_count($member['mb_id']);
    $console_notification_action_token = rb_notification_action_token();
    $console_notification_category_tabs = array('all'=>'전체') + rb_notification_visible_categories();
}
?>
<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo rb_console_h($console_title.' | '.$console_config['bc_name']); ?></title>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/css/admin.css?ver=<?php echo G5_CSS_VER; ?>">
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/fonts/Pretendard/Pretendard.css">
<?php foreach ((array) glob(G5_ADMIN_PATH.'/css/admin_extend_*.css') as $css) { ?><link rel="stylesheet" href="<?php echo rb_console_h(str_replace(G5_ADMIN_PATH, G5_ADMIN_URL, $css)); ?>"><?php } ?>
<link rel="stylesheet" href="<?php echo G5_JS_URL; ?>/font-awesome/css/font-awesome.min.css">
<script>var g5_url=<?php echo json_encode(G5_URL); ?>,g5_bbs_url=<?php echo json_encode(G5_BBS_URL); ?>,g5_admin_url=<?php echo json_encode(G5_ADMIN_URL); ?>,g5_is_member="1",g5_is_admin="",g5_is_mobile=<?php echo json_encode((string) G5_IS_MOBILE); ?>;</script>
<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script><script src="<?php echo G5_JS_URL; ?>/jquery-migrate-1.4.1.min.js"></script><script src="<?php echo G5_JS_URL; ?>/common.js?ver=<?php echo G5_JS_VER; ?>"></script><script src="<?php echo G5_JS_URL; ?>/wrest.js?ver=<?php echo G5_JS_VER; ?>"></script>
<?php if ($console_notification_category_tabs) { ?><script src="<?php echo G5_URL; ?>/rb/rb.console/console.notification.js?ver=<?php echo G5_JS_VER; ?>"></script><?php } ?>
<?php if (is_file(G5_THEME_PATH.'/shop/apexcharts/apexcharts.js')) { ?><script src="<?php echo G5_THEME_URL; ?>/shop/apexcharts/apexcharts.js"></script><?php } ?>
<?php if ($console_is_dashboard && is_file(G5_ADMIN_PATH.'/rb/rb.widget/sortable.min.js')) { ?><script src="<?php echo G5_ADMIN_URL; ?>/rb/rb.widget/sortable.min.js"></script><?php } ?>
</head><body class="business-console<?php echo $console_is_dark ? ' adm-dark' : ''; ?>"><div id="to_content"><a href="#container">본문 바로가기</a></div>
<header id="hd">
    <h1><?php echo rb_console_h($console_config['bc_name']); ?></h1>
    <div id="hd_top">
        <button type="button" id="btn_gnb" class="btn_gnb_close<?php echo $console_menu_collapsed ? ' btn_gnb_open' : ''; ?>">메뉴</button>
        <div id="logo" class="<?php echo $console_menu_collapsed ? 'logo_small' : ''; ?>"><a href="<?php echo rb_console_url(); ?>" title="Business Console"><strong>Business Console</strong></a></div>
        <div class="top_gnb_wrap rb-console-top-menu">
            <ul class="top_gnb_ul">
                <?php $console_top_menu_order = 1; foreach ($groups as $group_id => $group) {
                    $group_current = $rb_console_current['group'] === $group_id;
                    $previous_menu_section = null;
                ?>
                <li class="top-gnb-item<?php echo $group_current ? ' on' : ''; ?>">
                    <button type="button" class="top-menu-console-<?php echo $console_top_menu_order; ?> top-gnb-btn" aria-haspopup="true" aria-expanded="false" title="<?php echo rb_console_h($group['title']); ?>"><?php echo rb_console_h($group['title']); ?></button>
                    <div class="top_gnb_oparea_wr" role="menu">
                        <div class="top_gnb_oparea">
                            <h3><?php echo rb_console_h($group['title']); ?></h3>
                            <ul>
                                <?php foreach ($group['items'] as $item) {
                                    $menu_section = isset($item['menu_section']) ? trim((string) $item['menu_section']) : '';
                                    if ($menu_section !== '' && $menu_section !== $previous_menu_section) {
                                ?>
                                <li class="rb-console-top-subtitle"><span class="font-B"><?php echo rb_console_h($menu_section); ?></span></li>
                                <?php
                                    }
                                    $previous_menu_section = $menu_section !== '' ? $menu_section : null;
                                ?>
                                <li><a class="gnb_2da<?php echo $item['id'] === $console_active_menu_id ? ' on' : ''; ?>" href="<?php echo rb_console_url($item['id']); ?>"><?php echo rb_console_h($item['title']); ?></a></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </li>
                <?php $console_top_menu_order++; } ?>
            </ul>
        </div>
        <div id="tnb"><ul>
            <li class="tnb_li adm-dark-toggle" id="admDarkToggle" role="button" aria-label="다크모드 전환" aria-pressed="<?php echo $console_is_dark ? 'true' : 'false'; ?>" tabindex="0"><span class="adm-dark-toggle__knob"></span></li>
            <?php if ($console_store_url) { ?><li class="tnb_li rb-console-store-link"><a href="<?php echo rb_console_h($console_store_url); ?>" target="_blank" rel="noopener noreferrer" title="내 미니샵 바로가기"><img src="<?php echo G5_ADMIN_URL; ?>/img/sh.svg" alt=""></a></li><?php } ?>
            <li class="tnb_li rb-console-home-link"><a href="<?php echo G5_URL; ?>/" target="_blank" rel="noopener noreferrer" title="사이트 메인 바로가기"><img src="<?php echo G5_ADMIN_URL; ?>/img/hm.svg" alt=""></a></li>
            <?php if ($console_notification_category_tabs) include G5_PATH.'/rb/rb.console/console.notification.php'; ?>
            <?php if (!empty($console_config['bc_support_url'])) { ?><li class="tnb_li rb-console-support-link"><a href="<?php echo rb_console_h($console_config['bc_support_url']); ?>" target="_blank" rel="noopener noreferrer" title="고객지원 바로가기"><img src="<?php echo G5_ADMIN_URL; ?>/img/question.svg" alt=""></a></li><?php } ?>
            <li id="tnb_logout"><a href="<?php echo G5_BBS_URL; ?>/logout.php">로그아웃</a></li>
        </ul></div>
    </div>
</header>
<nav id="gnb" class="gnb_large<?php echo $console_menu_collapsed ? ' gnb_small' : ''; ?>"><h2>비즈니스 콘솔 메뉴</h2><ul class="gnb_ul">
<li class="gnb_li rb-console-dashboard-link<?php echo $console_is_dashboard ? ' on' : ''; ?>"><a class="btn_op rb-console-icon-button" href="<?php echo rb_console_url('dashboard'); ?>" title="대시보드"<?php echo $console_is_dashboard ? ' aria-current="page"' : ''; ?>><?php echo rb_console_menu_icon(isset($console_dashboard_item['group_icon_path']) ? $console_dashboard_item['group_icon_path'] : '', isset($console_dashboard_item['icon']) ? $console_dashboard_item['icon'] : 'fa-home'); ?><span class="sound_only">대시보드</span></a></li>
<?php foreach ($groups as $group_id => $group) { $group_current = !$console_is_dashboard && $rb_console_current['group'] === $group_id; ?><li class="gnb_li<?php echo $group_current ? ' on' : ''; ?>" data-group-id="<?php echo rb_console_h($group_id); ?>"><button type="button" class="btn_op rb-console-icon-button rb-console-group-button" data-group-id="<?php echo rb_console_h($group_id); ?>" aria-controls="rb-console-menu-<?php echo rb_console_h($group_id); ?>" aria-expanded="<?php echo $group_current ? 'true' : 'false'; ?>" title="<?php echo rb_console_h($group['title']); ?>"><?php echo rb_console_menu_icon($group['icon_path'], $group['icon']); ?><span class="sound_only"><?php echo rb_console_h($group['title']); ?></span></button></li><?php } ?>
</ul><div class="gnb_li gnb_oparea rb-console-side-panel"><div class="rb-console-side-inner rb-welcome-card">
    <section class="rb-welcome" aria-label="로그인 사용자 정보">
        <div class="rb-welcome-card">
        <div class="rb-welcome-head">
            <div class="rb-avatar"><?php echo get_member_profile_img($member['mb_id']); ?></div>
            <div class="rb-head-text">
                <div class="rb-name"><?php echo rb_console_h($member['mb_nick']); ?> 님</div>
                <button type="button" class="rb-btn" onclick="location.href='<?php echo rb_console_h($console_profile_url); ?>';">정보수정</button>
            </div>
        </div>
        <?php if (!empty($console_config['bc_show_point']) || $console_custom_point_enabled) { ?>
        <div class="rb-info-grid">
        <div class="rb-kv-top">
            <?php if (!empty($console_config['bc_show_point'])) { ?><div class="rb-kv"><span class="k">보유 포인트</span><span class="v rb-console-stat-main"><a class="win_point font-B" href="<?php echo G5_BBS_URL; ?>/point.php" target="_blank"><?php echo number_format((int) $member['mb_point']); ?>P</a><?php if ($console_point_charge_enabled) { ?><a class="rb-console-charge win_point font-R" href="<?php echo G5_URL; ?>/rb/point.php?types=add" target="_blank" rel="noopener noreferrer">충전</a><?php } ?></span></div><?php } ?>
            <?php if ($console_custom_point_enabled) { ?><div class="rb-kv"><span class="k"><?php echo rb_console_h($console_custom_point_name); ?></span><span class="v rb-console-stat-main"><a class="win_point font-B" href="<?php echo G5_URL; ?>/rb/point_c.php" target="_blank"><?php echo number_format((int) $member['rb_point']); ?><?php echo rb_console_h($console_custom_point_unit); ?></a><?php if ($console_custom_point_charge_enabled) { ?><a class="rb-console-charge win_point font-R" href="<?php echo G5_URL; ?>/rb/point_c.php?types=add" target="_blank" rel="noopener noreferrer">충전</a><?php } ?></span></div><?php } ?>
        </div>
        </div>
        <?php } ?>
        </div>
    </section>
    <div class="rb-console-side-menus">
        <section id="rb-console-menu-dashboard" class="rb-console-menu-section rb-console-dashboard-menu<?php echo $console_is_dashboard ? ' is-active' : ''; ?>" data-group-id="dashboard" aria-hidden="<?php echo $console_is_dashboard ? 'false' : 'true'; ?>">
            <h3>비즈니스 콘솔</h3>
            <ul>
                <?php foreach ($groups as $group_id => $group) {
                    $group_items = $group['items'];
                    $group_first_item = reset($group_items);
                    if (!$group_first_item) continue;
                ?>
                <li class="rb-console-dashboard-menu-item"><a class="gnb_2da font-B" href="<?php echo rb_console_url($group_first_item['id']); ?>"><?php echo rb_console_h($group['title']); ?></a></li>
                <?php } ?>
            </ul>
        </section>
        <?php foreach ($groups as $group_id => $group) {
            $group_current = !$console_is_dashboard && $rb_console_current['group'] === $group_id;
            $previous_menu_section = null;
        ?>
        <section id="rb-console-menu-<?php echo rb_console_h($group_id); ?>" class="rb-console-menu-section<?php echo $group_current ? ' is-active' : ''; ?>" data-group-id="<?php echo rb_console_h($group_id); ?>" aria-hidden="<?php echo $group_current ? 'false' : 'true'; ?>">
            <h3><?php echo rb_console_h($group['title']); ?></h3>
            <ul>
                <?php foreach ($group['items'] as $item) {
                    $menu_section = isset($item['menu_section']) ? trim((string) $item['menu_section']) : '';
                    if ($menu_section !== '' && $menu_section !== $previous_menu_section) {
                ?>
                <li class="rb-console-menu-subtitle"><span class="font-B"><?php echo rb_console_h($menu_section); ?></span></li>
                <?php
                    }
                    $previous_menu_section = $menu_section !== '' ? $menu_section : null;
                ?>
                <li class="rb-console-menu-item"><a class="gnb_2da<?php echo $item['id'] === $console_active_menu_id ? ' on' : ''; ?>" href="<?php echo rb_console_url($item['id']); ?>"><?php echo rb_console_h($item['title']); ?></a></li>
                <?php } ?>
            </ul>
        </section>
        <?php } ?>
    </div>
    <section class="rb-memo rb-console-memo rb-console-side-menus" data-endpoint="<?php echo G5_URL; ?>/rb/rb.console/memo.ajax.php" data-token="<?php echo rb_console_h(rb_console_token()); ?>">
        <div class="rb-memo-title"><?php echo rb_console_h($member['mb_nick']); ?>님의 한줄 메모</div>
        <div class="rb-memo-input"><input type="search" class="rb-console-memo-input" placeholder="메모 입력 후 엔터" maxlength="200" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" readonly></div>
        <ul class="rb-memo-list rb-console-memo-list" aria-live="polite"><li class="rb-memo-empty">메모를 불러오는 중입니다.</li></ul>
    </section>
    <?php if ($console_is_dashboard) { ?><section class="rb-console-collapsed-widgets" aria-label="접어둔 대시보드 위젯"><ul id="rb-console-collapsed-widget-list"></ul></section><?php } ?>
</div></div></nav><div id="wrapper"><div id="container" class="<?php echo $console_menu_collapsed ? 'container-small' : ''; ?>"><h1 id="container_title"><?php echo rb_console_h($console_title); ?></h1>
<?php if ($rb_console_current['id'] === 'dashboard' && !empty($console_config['bc_notice'])) { ?><div class="local_desc01 local_desc"><p><?php echo nl2br(rb_console_h($console_config['bc_notice'])); ?></p></div><?php } ?>
