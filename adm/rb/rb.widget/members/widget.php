<?php if (!auth_check_menu($auth, '200100', 'r', true)) { ?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl">신규회원</div>
    <div class="rb-m-btn-wrap">
      <a class="btn rb-badge rb-set-btn" href="<?php echo G5_ADMIN_URL ?>/member_list.php">더보기</a>
    </div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-small">
    전체회원 <?php echo rb_nf($total_member) ?>명
    &nbsp;&nbsp;차단 <?php echo rb_nf($intercept_member) ?>명
    &nbsp;&nbsp;탈퇴 <?php echo rb_nf($leave_member) ?>명
  </div>
  <div class="rb-metrics">
    <div class="left"><?php echo rb_pill('오늘'); ?></div>
    <div class="right"><strong>+<?php echo rb_nf($new_member_today) ?></strong></div>
  </div>
  <div class="rb-mini">
    <div class="rb-table-wrap">
      <table class="rb-table">
        <thead>
          <tr>
            <th class="rb-col-nick">닉네임</th>
            <th class="rb-col-email">Email</th>
            <th class="rb-col-dt">가입일시</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$new_member_list){ ?>
          <tr><td colspan="3"><?php echo $RB_EMPTY_TEXT; ?></td></tr>
        <?php } else { foreach($new_member_list as $r){
          $mb_nicks = get_sideview($r['mb_id'], $r['mb_nick'], '', '');
        ?>
          <tr>
            <td class="rb-col-nick td_mbname sv_use"><div class="po_rel" onclick="location.href='<?php echo G5_ADMIN_URL ?>/member_form.php?w=u&mb_id=<?php echo $r['mb_id'] ?>';"><?php echo $mb_nicks; ?></div></td>
            <td class="rb-col-email"><?php echo $r['mb_email']; ?></td>
            <td class="rb-col-dt"><?php echo date('Y-m-d H:i', strtotime($r['mb_datetime'])); ?></td>
          </tr>
        <?php }} ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } ?>
