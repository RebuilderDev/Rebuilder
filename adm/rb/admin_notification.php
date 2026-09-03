<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (
    empty($member['mb_id'])
    || !function_exists('rb_notification_table_ready')
    || !rb_notification_table_ready()
) {
    return;
}

$admin_notification_categories = function_exists('rb_notification_visible_categories')
    ? (array) rb_notification_visible_categories()
    : array();

if (!$admin_notification_categories) {
    return;
}

$admin_notification_tabs = array('all' => '전체') + $admin_notification_categories;
$admin_notification_unread_count = function_exists('rb_notification_unread_count')
    ? (int) rb_notification_unread_count($member['mb_id'])
    : 0;
$admin_notification_action_token = function_exists('rb_notification_action_token')
    ? rb_notification_action_token()
    : '';
$admin_notification_h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

add_javascript('<script src="'.G5_URL.'/rb/rb.console/console.notification.js"></script>', 20);
?>
<li class="tnb_li rb-admin-notification">
    <a href="#" id="notification_top_btn" title="알림" aria-label="알림" aria-haspopup="true" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><g fill="none"><path d="M24 0v24H0V0zM12.594 23.258l-.012.002-.071.035-.02.004-.014-.004-.071-.036c-.01-.003-.019 0-.024.006l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.016-.018m.264-.113-.014.002-.184.093-.01.01-.003.011.018.43.005.012.008.008.201.092c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.003-.011.018-.43-.003-.012-.01-.01z"/><path d="M12 2a7 7 0 0 0-7 7v3.528a1 1 0 0 1-.105.447l-1.717 3.433A1.1 1.1 0 0 0 4.162 18h15.676a1.1 1.1 0 0 0 .984-1.592l-1.716-3.433a1 1 0 0 1-.106-.447V9a7 7 0 0 0-7-7m0 19a3.001 3.001 0 0 1-2.83-2h5.66A3.001 3.001 0 0 1 12 21"/></g></svg>
        <span class="font-H" id="notification_unread_badge"<?php echo $admin_notification_unread_count > 0 ? '' : ' style="display:none"'; ?>><?php echo $admin_notification_unread_count; ?></span>
    </a>
    <div id="notification_box_wrap" data-endpoint="<?php echo G5_URL; ?>/rb/rb.mod/alarm/get-events.php" data-action-token="<?php echo $admin_notification_h($admin_notification_action_token); ?>">
        <div class="rb_notification_tabs" role="tablist" aria-label="알림 구분">
            <?php foreach ($admin_notification_tabs as $category => $label) { ?>
            <button type="button" class="<?php echo $category === 'all' ? 'active' : ''; ?>" data-category="<?php echo $admin_notification_h($category); ?>" role="tab" aria-selected="<?php echo $category === 'all' ? 'true' : 'false'; ?>"><?php echo $admin_notification_h($label); ?></button>
            <?php } ?>
        </div>
        <div class="rb_notification_body">
            <div class="rb_notification_list"><div class="rb_notification_loading" role="status" aria-label="알림을 불러오는 중"><span aria-hidden="true"></span></div></div>
            <div class="rb_notification_view" style="display:none">
                <div class="rb_notification_view_header">
                    <button type="button" class="rb_notification_back">← 목록</button>
                    <span class="rb_notification_view_date"></span>
                </div>
                <div class="rb_notification_view_inner">
                    <div class="rb_notification_view_content"></div>
                    <div class="rb_notification_view_links"></div>
                </div>
            </div>
        </div>
        <div class="rb_notification_footer">
            <span>알림 보관일수는 최장 180일입니다.</span>
            <button type="button" class="rb_notification_delete_all">전체삭제</button>
        </div>
    </div>
</li>
