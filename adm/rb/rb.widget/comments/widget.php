<?php if (!auth_check_menu($auth, '300100', 'r', true)) { ?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl">댓글</div>
    <div class="rb-m-btn-wrap">
      <a class="btn rb-badge rb-set-btn" href="<?php echo G5_ADMIN_URL ?>/board_list.php">설정</a>
      <a class="btn rb-badge" href="<?php echo G5_URL ?>/rb/new.php?view=c" target="_blank">더보기</a>
    </div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-small">전체 <?php echo rb_nf($cmt_total) ?>건</div>
  <div class="rb-metrics">
    <div class="left"><?php echo rb_pill('오늘'); ?></div>
    <div class="right"><strong>+<?php echo rb_nf($cmt_today) ?></strong></div>
  </div>
  <div class="rb-mini">
    <div class="rb-table-wrap">
      <table class="rb-table">
        <thead>
          <tr>
            <th class="rb-col-nick">작성자</th>
            <th class="rb-col-title">댓글내용</th>
            <th class="rb-col-dt">작성일시</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$cmt_list){ ?>
          <tr><td colspan="4"><?php echo $RB_EMPTY_TEXT; ?></td></tr>
        <?php } else { foreach($cmt_list as $r){
          $display_name = get_text($r['wr_name'] ?: $r['mb_id']);
          $mb_nicks = get_sideview($r['mb_id'], $display_name, '', '');
        ?>
          <tr>
            <td class="rb-col-nick td_mbname sv_use">
              <div class="po_rel" onclick="location.href='<?php echo G5_ADMIN_URL ?>/member_form.php?w=u&mb_id=<?php echo $r['mb_id'] ?>';"><?php echo $mb_nicks; ?></div>
            </td>
            <td class="rb-col-title"><a href="<?php echo $r['link']; ?>" target="_blank" rel="noopener"><?php echo get_text($r['subject']); ?></a></td>
            <td class="rb-col-dt"><?php echo $r['time']; ?></td>
          </tr>
        <?php }} ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } ?>
