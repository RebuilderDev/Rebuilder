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
$sfl = isset($_GET['sfl']) ? (string) $_GET['sfl'] : 'noti_recv_mb_id';
$stx = isset($_GET['stx']) ? trim((string) $_GET['stx']) : '';
$sca = isset($_GET['sca']) ? (string) $_GET['sca'] : '';
$read_state = isset($_GET['read_state']) ? (string) $_GET['read_state'] : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$allowed_fields = array('noti_recv_mb_id', 'noti_send_mb_id', 'noti_title', 'noti_content');
if (!in_array($sfl, $allowed_fields, true)) $sfl = 'noti_recv_mb_id';
if (!isset($categories[$sca])) $sca = '';
if (!in_array($read_state, array('', 'unread', 'read'), true)) $read_state = '';

$where = " WHERE 1=1 ";
if ($stx !== '') $where .= " AND `{$sfl}` LIKE '%".sql_real_escape_string($stx)."%' ";
if ($sca !== '') $where .= " AND noti_category='".sql_real_escape_string($sca)."' ";
if ($read_state === 'unread') $where .= " AND noti_read_at IS NULL ";
if ($read_state === 'read') $where .= " AND noti_read_at IS NOT NULL ";

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
$qstr_noti = 'sfl='.urlencode($sfl).'&amp;stx='.urlencode($stx).'&amp;sca='.urlencode($sca).'&amp;read_state='.urlencode($read_state);
?>

<section id="rb_notification_send">
    <h2 class="h2_frm">공지 발송</h2>
    <div class="local_desc01 local_desc">
        <p>관리자가 보내는 알림은 공지로 저장됩니다. 여러 아이디는 쉼표 또는 공백으로 구분해 입력할 수 있습니다.</p>
    </div>
    <form action="./notification_update.php" method="post" onsubmit="return rb_notification_send_check(this);">
        <input type="hidden" name="token" value="<?php echo $admin_token; ?>">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>공지 발송</caption>
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
                    <th scope="row"><label for="noti_title">제목</label></th>
                    <td><input type="text" name="noti_title" id="noti_title" required class="frm_input required" size="80" maxlength="255"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="noti_content">내용</label></th>
                    <td><textarea name="noti_content" id="noti_content" required class="required" rows="6"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="noti_link">연결 주소</label></th>
                    <td><input type="text" name="noti_link" id="noti_link" class="frm_input" size="100" maxlength="1000" placeholder="비워두면 알림 내용만 표시됩니다."></td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit">공지 발송</button></div>
    </form>
</section>

<section id="rb_notification_list">
    <h2 class="h2_frm">알림 내역</h2>
    <form class="local_sch01 local_sch" method="get">
        <select name="sfl">
            <option value="noti_recv_mb_id"<?php echo get_selected($sfl, 'noti_recv_mb_id'); ?>>수신 아이디</option>
            <option value="noti_send_mb_id"<?php echo get_selected($sfl, 'noti_send_mb_id'); ?>>발신 아이디</option>
            <option value="noti_title"<?php echo get_selected($sfl, 'noti_title'); ?>>제목</option>
            <option value="noti_content"<?php echo get_selected($sfl, 'noti_content'); ?>>내용</option>
        </select>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input">
        <select name="sca">
            <option value="">전체 종류</option>
            <?php foreach ($categories as $code => $label) { ?>
            <option value="<?php echo $code; ?>"<?php echo get_selected($sca, $code); ?>><?php echo $label; ?></option>
            <?php } ?>
        </select>
        <select name="read_state">
            <option value="">전체 상태</option>
            <option value="unread"<?php echo get_selected($read_state, 'unread'); ?>>읽지 않음</option>
            <option value="read"<?php echo get_selected($read_state, 'read'); ?>>읽음</option>
        </select>
        <button type="submit" class="btn_submit">검색</button>
        <a href="./notification_form.php" class="ov_listall">전체목록</a>
        <span class="btn_ov01"><span class="ov_txt">검색 </span><span class="ov_num"><?php echo number_format($total_count); ?>건</span></span>
        <span class="btn_ov01"><span class="ov_txt">전체 미확인 </span><span class="ov_num"><?php echo number_format($unread_count); ?>건</span></span>
    </form>

    <form method="post" action="./notification_list_update.php" onsubmit="return rb_notification_list_check(this);">
        <input type="hidden" name="token" value="<?php echo $admin_token; ?>">
        <input type="hidden" name="qstr" value="<?php echo get_text($qstr_noti); ?>">
        <input type="hidden" name="page" value="<?php echo $page; ?>">
        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption>알림 내역</caption>
                <thead><tr>
                    <th scope="col"><input type="checkbox" id="noti_chkall" onclick="rb_notification_check_all(this)"></th>
                    <th scope="col" id="noti_category">종류</th><th scope="col" id="noti_recv">받음</th><th scope="col" id="noti_send">보냄</th>
                    <th scope="col" id="noti_title">제목</th><th scope="col" id="noti_content">내용</th><th scope="col" id="noti_state">상태</th><th scope="col" id="noti_date">발송일</th>
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
                    <td headers="noti_title" class="td_center"><strong><?php echo get_text($row['noti_title']); ?></strong></td>
                    <td headers="noti_content" class="td_left" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:420px">
                        <?php if ($valid_link) { ?><a href="<?php echo get_text($row['noti_link']); ?>" target="_blank" rel="noopener"><?php } ?><?php echo get_text($content_one_line); ?><?php if ($valid_link) { ?></a><?php } ?>
                    </td>
                    <td headers="noti_state" class="td_mng"><?php echo $row['noti_read_at'] ? '읽음' : '읽지 않음'; ?></td>
                    <td headers="noti_date" class="td_datetime"><?php echo get_text($row['noti_created_at']); ?></td>
                </tr>
                <?php $i++; }} if (!$i) { ?><tr><td colspan="8" class="empty_table">알림 내역이 없습니다.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
        <div class="btn_fixed_top"><button type="submit" class="btn btn_02">선택삭제</button></div>
    </form>
    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './notification_form.php?'.$qstr_noti.'&amp;page='); ?>
</section>

<script>
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
    return confirm(target + ' 대상으로 공지를 발송하시겠습니까?');
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
