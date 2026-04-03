<?php if(defined('G5_USE_SHOP') && G5_USE_SHOP && !empty($g5['g5_shop_item_qa_table'])){
  if (!auth_check_menu($auth, '400660', 'r', true)) {
    $inq_total      = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_item_qa_table']}");
    $inq_unanswered = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_item_qa_table']} WHERE iq_answer IS NULL OR iq_answer = ''");
?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl"><strong>상품문의</strong></div>
    <div class="rb-m-btn-wrap"><a class="btn rb-badge" href="<?php echo G5_ADMIN_URL ?>/shop_admin/itemqalist.php">더보기</a></div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-small">전체 <strong><?php echo rb_nf($inq_total) ?></strong>건</div>
  <div class="rb-metrics">
    <div class="left"><?php echo rb_pill('답변없는 문의'); ?></div>
    <div class="right rb-number"><strong>+<?php echo rb_nf($inq_unanswered) ?></strong></div>
  </div>
  <div class="rb-mini">
    <div class="rb-table-wrap">
      <table class="rb-table">
        <thead>
          <tr>
            <th class="rb-col-title">제목</th>
            <th class="rb-col-state">상태</th>
            <th class="rb-col-dt">작성일시</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$inq_list){ ?>
          <tr><td colspan="3"><?php echo $RB_EMPTY_TEXT; ?></td></tr>
        <?php } else { foreach($inq_list as $r){ $answered = trim((string)$r['iq_answer']) !== ''; ?>
          <tr>
            <td class="rb-col-title"><a href="<?php echo G5_ADMIN_URL ?>/shop_admin/itemqaform.php?w=u&iq_id=<?php echo get_text($r['iq_id']); ?>"><?php echo get_text($r['iq_subject']); ?></a></td>
            <td class="rb-col-state"><?php echo $answered ? '<span class="rb-badge-ok">완료</span>' : '<span class="rb-badge-wait">미답변</span>'; ?></td>
            <td class="rb-col-dt"><?php echo substr($r['iq_time'],0,16); ?></td>
          </tr>
        <?php }} ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } } ?>
