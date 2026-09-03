<?php
$sub_menu = '000220';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');
add_stylesheet('<link rel="stylesheet" href="./css/style.css">', 0);

$table_ready = function_exists('rb_notification_table_ready') && rb_notification_table_ready();
$dispatch_ready = false;
if ($table_ready) {
    $dispatch_ready = function_exists('rb_notification_database_table_exists')
        && rb_notification_database_table_exists('rb_notification_dispatch');
}
if (!$table_ready || !$dispatch_ready) {
    alert('알림 기능을 사용하려면 빌더설정 > DB업데이트를 먼저 실행해 주세요.', './rb_form.php');
}

$g5['title'] = '알림 관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$categories = function_exists('rb_notification_categories') ? rb_notification_categories() : array(
    'board' => '게시물', 'shop' => '쇼핑', 'subscribe' => '구독', 'notice' => '공지', 'other' => '기타'
);
$filter_categories = function_exists('rb_notification_visible_categories')
    ? rb_notification_visible_categories()
    : $categories;
$sfl = isset($_GET['sfl']) ? (string) $_GET['sfl'] : 'noti_recv_mb_id';
$stx = isset($_GET['stx']) ? trim((string) $_GET['stx']) : '';
$sca = isset($_GET['sca']) ? (string) $_GET['sca'] : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$allowed_fields = array('noti_recv_mb_id', 'noti_send_mb_id', 'noti_content');
if (!in_array($sfl, $allowed_fields, true)) $sfl = 'noti_recv_mb_id';
if (!isset($filter_categories[$sca])) $sca = '';

$where = " WHERE 1=1 ";
if ($stx !== '') $where .= " AND `{$sfl}` LIKE '%".sql_real_escape_string($stx)."%' ";
if ($sca !== '') $where .= " AND noti_category='".sql_real_escape_string($sca)."' ";

$total_count = 0;
$unread_count = 0;
$result = false;
$rows = 20;
$total_page = 1;
if ($table_ready) {
    $count = sql_fetch("SELECT COUNT(*) AS cnt FROM rb_notification {$where}", false);
    $total_count = isset($count['cnt']) ? (int) $count['cnt'] : 0;
    $unread = sql_fetch("SELECT COUNT(*) AS cnt FROM rb_notification WHERE noti_read_at IS NULL", false);
    $unread_count = isset($unread['cnt']) ? (int) $unread['cnt'] : 0;
    $total_page = max(1, (int) ceil($total_count / $rows));
    if ($page > $total_page) $page = $total_page;
    $from_record = ($page - 1) * $rows;
    $result = sql_query("SELECT * FROM rb_notification {$where} ORDER BY noti_id DESC LIMIT {$from_record}, {$rows}", false);
}
$admin_token = get_admin_token();
$retention_days = function_exists('rb_notification_retention_days')
    ? rb_notification_retention_days()
    : 180;
$polling_seconds = function_exists('rb_notification_polling_seconds')
    ? rb_notification_polling_seconds()
    : 60;
$floating_settings = function_exists('rb_notification_floating_settings')
    ? rb_notification_floating_settings()
    : array('use' => 1, 'position' => 'left_bottom', 'offset' => 50, 'is_saved' => 0);
$floating_use = !empty($floating_settings['use']) ? 1 : 0;
$floating_position = isset($floating_settings['position']) ? (string) $floating_settings['position'] : 'left_bottom';
$floating_offset = isset($floating_settings['offset']) ? (int) $floating_settings['offset'] : 50;
$notification_available_categories = function_exists('rb_notification_available_categories')
    ? rb_notification_available_categories()
    : $categories;
$notification_enabled_categories = function_exists('rb_notification_enabled_categories')
    ? rb_notification_enabled_categories()
    : $categories;
$qstr_noti = 'sfl='.urlencode($sfl).'&amp;stx='.urlencode($stx).'&amp;sca='.urlencode($sca);
?>

<section id="rb_notification_config">
    <h2 class="h2_frm">알림 설정</h2>
    <form action="./notification_config_update.php" method="post">
        <input type="hidden" name="token" value="<?php echo $admin_token; ?>">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>알림 설정</caption>
                <colgroup><col class="grid_4"><col></colgroup>
                <tbody>
                <tr>
                    <th scope="row"><label for="notification_retention_days">알림 보관일수</label></th>
                    <td>
                        <?php echo help('설정한 보관기간이 지난 알림은 하루 한 번 자동으로 삭제됩니다. 최대 180일까지 설정할 수 있습니다.'); ?>
                        <input type="number" name="notification_retention_days" value="<?php echo $retention_days; ?>" id="notification_retention_days" required min="1" max="180" class="frm_input required" size="5"> 일
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="notification_polling_seconds">알림 폴링주기</label></th>
                    <td>
                        <?php echo help('새 알림을 확인하는 주기입니다. 10초부터 3600초까지 설정할 수 있습니다.'); ?>
                        <input type="number" name="notification_polling_seconds" value="<?php echo $polling_seconds; ?>" id="notification_polling_seconds" required min="10" max="3600" class="frm_input required" size="5"> 초
                    </td>
                </tr>
                <tr>
                    <th scope="row">알림 항목</th>
                    <td>
                        <?php echo help('체크한 항목만 회원 알림 탭·전체 목록·알림 개수·플로팅 알림에 표시됩니다. 데이터 저장과 알림 발송 기능에는 영향을 주지 않습니다.'); ?>
                        <?php foreach ($notification_available_categories as $category_key => $category_label) { ?>
                        <label style="margin-right:15px"><input type="checkbox" name="notification_categories[]" value="<?php echo get_text($category_key); ?>"<?php echo isset($notification_enabled_categories[$category_key]) ? ' checked' : ''; ?>> <?php echo get_text($category_label); ?></label>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">플로팅 알림</th>
                    <td>
                        <?php echo help('새 쪽지와 새 알림을 화면에 플로팅으로 표시할지 설정합니다. 사용하지 않아도 알림 개수는 정상적으로 갱신됩니다.'); ?>
                        <label><input type="radio" name="notification_floating_use" value="1"<?php echo get_checked($floating_use, 1); ?>> 사용함</label>
                        <label style="margin-left:15px"><input type="radio" name="notification_floating_use" value="0"<?php echo get_checked($floating_use, 0); ?>> 사용안함</label>
                    </td>
                </tr>
                <tr class="notification_floating_detail">
                    <th scope="row">플로팅 알림 위치</th>
                    <td>
                        <label><input type="radio" name="notification_floating_position" value="left_top"<?php echo get_checked($floating_position, 'left_top'); ?>> 좌상단</label>
                        <label style="margin-left:15px"><input type="radio" name="notification_floating_position" value="left_bottom"<?php echo get_checked($floating_position, 'left_bottom'); ?>> 좌하단</label>
                        <label style="margin-left:15px"><input type="radio" name="notification_floating_position" value="right_top"<?php echo get_checked($floating_position, 'right_top'); ?>> 우상단</label>
                        <label style="margin-left:15px"><input type="radio" name="notification_floating_position" value="right_bottom"<?php echo get_checked($floating_position, 'right_bottom'); ?>> 우하단</label>
                        <label style="margin-left:15px"><input type="radio" name="notification_floating_position" value="center"<?php echo get_checked($floating_position, 'center'); ?>> 중앙</label>
                    </td>
                </tr>
                <tr class="notification_floating_detail" id="notification_floating_offset_row">
                    <th scope="row"><label for="notification_floating_offset">화면 가장자리 간격</label></th>
                    <td>
                        <?php echo help('선택한 모서리의 가로·세로 가장자리에서 동일하게 띄울 간격입니다.'); ?>
                        <input type="number" name="notification_floating_offset" value="<?php echo $floating_offset; ?>" id="notification_floating_offset" required min="0" max="1000" class="frm_input required" size="5"> px
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit">설정 저장</button></div>
    </form>
</section>

<section id="rb_notification_send">
    <h2 class="h2_frm">공지 알림 발송</h2>
    <div class="local_desc01 local_desc">
        <p>관리자가 보내는 알림은 공지로 저장됩니다. 여러 아이디는 쉼표 또는 공백으로 구분해 입력할 수 있습니다.</p>
    </div>
    <form action="./notification_update.php" method="post" onsubmit="return rb_notification_send_check(this);">
        <input type="hidden" name="token" value="<?php echo $admin_token; ?>">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>공지 알림 발송</caption>
                <colgroup><col class="grid_4"><col></colgroup>
                <tbody>
                <tr>
                    <th scope="row"><label for="target_type">발송 대상</label></th>
                    <td>
                        <select name="target_type" id="target_type" onchange="rb_notification_target_change(this.value)">
                            <option value="member">아이디 개별·단체 발송</option>
                            <option value="level">레벨별 발송</option>
                            <option value="all">전체회원 발송</option>
                        </select>
                    </td>
                </tr>
                <tr id="target_member_row">
                    <th scope="row"><label for="target_members">회원 아이디</label></th>
                    <td><input type="text" name="target_members" id="target_members" value="" class="frm_input" size="60" autocomplete="off" placeholder="예: member1, member2"></td>
                </tr>
                <tr id="target_level_row" style="display:none">
                    <th scope="row">회원 레벨</th>
                    <td>
                        <?php for ($level=1; $level<=10; $level++) { ?>
                        <label style="margin-right:12px"><input type="checkbox" name="target_levels[]" value="<?php echo $level; ?>"> <?php echo $level; ?>레벨</label>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="noti_content">내용</label></th>
                    <td><textarea name="noti_content" id="noti_content" required class="required" rows="6"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="noti_link">링크 URL</label></th>
                    <td><input type="text" name="noti_link" id="noti_link" class="frm_input" size="100" maxlength="1000" placeholder="비워두면 알림 내용만 표시됩니다."></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit">공지 알림 발송</button></div>
    </form>
</section>

<section id="rb_notification_list">
    <h2 class="h2_frm">알림 내역</h2>
    <div class="local_ov01 local_ov">
        <a href="./notification_form.php" class="ov_listall">전체목록</a>
        <span class="btn_ov01"><span class="ov_txt">검색 </span><span class="ov_num"><?php echo number_format($total_count); ?>건</span></span>
        <span class="btn_ov01"><span class="ov_txt">미확인 </span><span class="ov_num"><?php echo number_format($unread_count); ?>건</span></span>
    </div>
    <form class="local_sch01 local_sch" method="get">
        <select name="sfl">
            <option value="noti_recv_mb_id"<?php echo get_selected($sfl, 'noti_recv_mb_id'); ?>>수신 아이디</option>
            <option value="noti_send_mb_id"<?php echo get_selected($sfl, 'noti_send_mb_id'); ?>>발신 아이디</option>
            <option value="noti_content"<?php echo get_selected($sfl, 'noti_content'); ?>>내용</option>
        </select>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input">
        <select name="sca">
            <option value="">전체 종류</option>
            <?php foreach ($filter_categories as $code => $label) { ?>
            <option value="<?php echo $code; ?>"<?php echo get_selected($sca, $code); ?>><?php echo $label; ?></option>
            <?php } ?>
        </select>
        <button type="submit" class="btn_submit">검색</button>
        <button type="submit" form="rb_notification_list_form" class="btn btn_02" style="float:right">선택삭제</button>
    </form>

    <form id="rb_notification_list_form" method="post" action="./notification_list_update.php" onsubmit="return rb_notification_list_check(this);">
        <input type="hidden" name="token" value="<?php echo $admin_token; ?>">
        <input type="hidden" name="qstr" value="<?php echo get_text($qstr_noti); ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption>알림 내역</caption>
                <thead><tr>
                    <th scope="col"><input type="checkbox" id="noti_chkall" onclick="rb_notification_check_all(this)"></th>
                    <th scope="col" id="noti_category">종류</th><th scope="col" id="noti_recv">받음</th><th scope="col" id="noti_send">보냄</th>
                    <th scope="col" id="noti_content">내용</th><th scope="col" id="noti_state">상태</th><th scope="col" id="noti_date">발송일</th>
                </tr></thead>
                <tbody>
                <?php $i=0; if ($result) { while ($row=sql_fetch_array($result)) {
                    $recv_mb = get_member($row['noti_recv_mb_id'], 'mb_id, mb_nick, mb_email, mb_homepage');
                    $recv_sideview = !empty($recv_mb['mb_id'])
                        ? get_sideview($recv_mb['mb_id'], get_text($recv_mb['mb_nick']), $recv_mb['mb_email'], $recv_mb['mb_homepage'])
                        : get_text($row['noti_recv_mb_id']);

                    $send_sideview = '시스템';
                    if ($row['noti_send_mb_id'] !== 'system-msg') {
                        $send_mb = get_member($row['noti_send_mb_id'], 'mb_id, mb_nick, mb_email, mb_homepage');
                        $send_sideview = !empty($send_mb['mb_id'])
                            ? get_sideview($send_mb['mb_id'], get_text($send_mb['mb_nick']), $send_mb['mb_email'], $send_mb['mb_homepage'])
                            : get_text($row['noti_send_mb_id']);
                    }
                    $content_one_line = trim((string) preg_replace('/\s+/u', ' ', preg_replace('/<br\s*\/?>/i', ' ', (string) $row['noti_content'])));
                    $valid_link = $row['noti_link'] && preg_match('#^(https?://|/)#i', $row['noti_link']);
                ?>
                <tr>
                    <td class="td_chk"><input type="checkbox" name="noti_id[]" value="<?php echo (int) $row['noti_id']; ?>"></td>
                    <td headers="noti_category" class="td_category"><?php echo isset($categories[$row['noti_category']]) ? $categories[$row['noti_category']] : '기타'; ?></td>
                    <td headers="noti_recv" class="td_name sv_use" style="text-align:center !important"><div style="display:inline-block;text-align:left"><?php echo $recv_sideview; ?></div></td>
                    <td headers="noti_send" class="td_name sv_use" style="text-align:center !important"><div style="display:inline-block;text-align:left"><?php echo $send_sideview; ?></div></td>
                    <td headers="noti_content" class="td_left" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:420px">
                        <?php if ($valid_link) { ?><a href="<?php echo get_text($row['noti_link']); ?>" target="_blank" rel="noopener"><?php } ?><?php echo get_text($content_one_line); ?><?php if ($valid_link) { ?></a><?php } ?>
                    </td>
                    <td headers="noti_state" class="td_mng"><?php echo $row['noti_read_at'] ? '읽음' : '읽지 않음'; ?></td>
                    <td headers="noti_date" class="td_datetime"><?php echo get_text($row['noti_created_at']); ?></td>
                </tr>
                <?php $i++; }} if (!$i) { ?><tr><td colspan="7" class="empty_table">알림 내역이 없습니다.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './notification_form.php?'.$qstr_noti.'&amp;page='); ?>
</section>

<script>
function rb_notification_floating_toggle() {
    var use = document.querySelector('input[name="notification_floating_use"]:checked');
    var position = document.querySelector('input[name="notification_floating_position"]:checked');
    var detailRows = document.querySelectorAll('.notification_floating_detail');
    var enabled = use && use.value === '1';
    for (var i = 0; i < detailRows.length; i++) {
        detailRows[i].style.display = enabled ? '' : 'none';
    }

    var offsetRow = document.getElementById('notification_floating_offset_row');
    var offsetInput = document.getElementById('notification_floating_offset');
    var usesOffset = enabled && (!position || position.value !== 'center');
    if (offsetRow) offsetRow.style.display = usesOffset ? '' : 'none';
    if (offsetInput) offsetInput.disabled = !usesOffset;
}
document.addEventListener('DOMContentLoaded', function () {
    var floatingInputs = document.querySelectorAll('input[name="notification_floating_use"], input[name="notification_floating_position"]');
    for (var i = 0; i < floatingInputs.length; i++) {
        floatingInputs[i].addEventListener('change', rb_notification_floating_toggle);
    }
    rb_notification_floating_toggle();
});
function rb_notification_target_change(value) {
    document.getElementById('target_member_row').style.display = value === 'member' ? '' : 'none';
    document.getElementById('target_level_row').style.display = value === 'level' ? '' : 'none';
}
function rb_notification_send_check(form) {
    if (form.target_type.value === 'member' && !form.target_members.value.trim()) {
        alert('회원 아이디를 입력해 주세요.'); form.target_members.focus(); return false;
    }
    if (form.target_type.value === 'level' && !form.querySelector('input[name="target_levels[]"]:checked')) {
        alert('발송할 회원 레벨을 선택해 주세요.'); return false;
    }
    var target = form.target_type.options[form.target_type.selectedIndex].text;
    return confirm(target + ' 대상으로 공지 알림을 발송하시겠습니까?');
}
function rb_notification_list_check(form) {
    if (!is_checked('noti_id[]')) { alert('삭제할 알림을 선택해 주세요.'); return false; }
    return confirm('선택한 알림을 삭제하시겠습니까?');
}
function rb_notification_check_all(source) {
    var checks = document.getElementsByName('noti_id[]');
    for (var i=0; i<checks.length; i++) checks[i].checked = source.checked;
}
</script>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
