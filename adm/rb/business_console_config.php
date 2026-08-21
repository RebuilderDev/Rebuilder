<?php
$sub_menu = '000999';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') alert('최고관리자만 접근할 수 있습니다.');
include_once(G5_PATH.'/rb/rb.console/console.lib.php');
$installed = rb_console_table_exists('rb_business_console_config');
if (!$installed) {
    alert('비즈니스 콘솔 기능을 사용하려면 빌더설정 > DB업데이트를 먼저 실행해 주세요.', './rb_form.php');
}
$bc = rb_console_config(true);
$partner_policy = isset($bc['bc_partner_policy']) && $bc['bc_partner_policy'] === 'approved' ? 'approved' : 'all';
$business_features = rb_console_business_features($bc);
$g5['title'] = '비즈니스 콘솔 설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<form method="post" action="./business_console_config_update.php">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
<section><h2 class="h2_frm">콘솔 기본 설정</h2><div class="tbl_frm01 tbl_wrap"><table><tbody>
<tr><th scope="row">사용 여부</th><td><input type="radio" name="bc_enabled" id="bc_enabled_1" value="1" <?php echo $bc['bc_enabled'] ? 'checked' : ''; ?>> <label for="bc_enabled_1">사용</label>　<input type="radio" name="bc_enabled" id="bc_enabled_0" value="0" <?php echo !$bc['bc_enabled'] ? 'checked' : ''; ?>> <label for="bc_enabled_0">중지</label></td></tr>
<tr><th scope="row"><label for="bc_name">콘솔 이름</label></th><td><input class="frm_input required" required maxlength="80" size="40" id="bc_name" name="bc_name" value="<?php echo rb_console_h($bc['bc_name']); ?>"></td></tr>
<tr><th scope="row"><label for="bc_min_level">최소 회원레벨</label></th><td><?php echo get_member_level_select('bc_min_level', 1, 10, (int) $bc['bc_min_level']); ?></td></tr>
<tr><th scope="row"><label for="bc_default_route">기본 화면</label></th><td><?php echo help('기본값 dashboard. 설치된 부가기능의 route ID를 지정할 수 있습니다.'); ?><input class="frm_input" maxlength="80" id="bc_default_route" name="bc_default_route" value="<?php echo rb_console_h($bc['bc_default_route']); ?>"></td></tr>
<tr><th scope="row">포인트 표시</th><td><input type="checkbox" name="bc_show_point" id="bc_show_point" value="1" <?php echo $bc['bc_show_point'] ? 'checked' : ''; ?>> <label for="bc_show_point">대시보드와 상단에 보유 포인트 표시</label></td></tr>
<tr><th scope="row"><label for="bc_partner_policy">입점사 메뉴 노출</label></th><td><select name="bc_partner_policy" id="bc_partner_policy"><option value="approved" <?php echo get_selected($partner_policy, 'approved'); ?>>승인된 입점사만</option><option value="all" <?php echo get_selected($partner_policy, 'all'); ?>>모두 노출</option></select></td></tr>
<tr><th scope="row"><label for="bc_support_url">고객지원 URL</label></th><td><input class="frm_input" size="70" maxlength="500" id="bc_support_url" name="bc_support_url" value="<?php echo rb_console_h($bc['bc_support_url']); ?>"></td></tr>
<tr><th scope="row"><label for="bc_notice">상단 공지</label></th><td><textarea name="bc_notice" id="bc_notice" rows="5"><?php echo rb_console_h($bc['bc_notice']); ?></textarea></td></tr>
</tbody></table></div></section>
<section>
<h2 class="h2_frm">설치된 비즈니스 기능</h2>
<div class="tbl_head01 tbl_wrap"><table>
<thead><tr><th>기능</th><th>콘솔 연동 내용</th><th>설치상태</th><th>관리</th></tr></thead>
<tbody>
<?php foreach ($business_features as $feature) {
    $feature_installed = !empty($feature['installed']);
    $feature_manage_url = isset($feature['manage_url']) ? (string) $feature['manage_url'] : '';
    $feature_manage_file = $feature_manage_url ? G5_ADMIN_PATH.'/rb/'.basename($feature_manage_url) : '';
?>
<tr>
    <td><?php echo rb_console_h(isset($feature['name']) ? $feature['name'] : ''); ?></td>
    <td class="td_left"><?php echo rb_console_h(isset($feature['description']) ? $feature['description'] : ''); ?></td>
    <td><?php echo $feature_installed ? '설치됨' : '미설치'; ?></td>
    <td class="td_mng"><?php if ($feature_installed && $feature_manage_url && is_file($feature_manage_file)) { ?><a class="btn btn_03" href="<?php echo rb_console_h($feature_manage_url); ?>"><?php echo rb_console_h(isset($feature['manage_label']) ? $feature['manage_label'] : '관리'); ?></a><?php } ?></td>
</tr>
<?php } ?>
</tbody>
</table></div>
</section>
<div class="btn_fixed_top">
    <a href="<?php echo G5_URL; ?>/rb/business.php" target="_blank" class="btn btn_02">콘솔 보기</a>
    <button type="submit" class="btn_submit btn">저장</button>
</div></form>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
