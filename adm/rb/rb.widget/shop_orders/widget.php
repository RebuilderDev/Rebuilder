<?php if(defined('G5_USE_SHOP') && G5_USE_SHOP){
  if (!auth_check_menu($auth, '400400', 'r', true)) {
    $od_total = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_order_table']}");
    $od_today = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_order_table']} WHERE LEFT(od_time,10)='{$today}'");
    // 통계
    $od_stats = array('주문'=>0,'입금'=>0,'배송'=>0,'취소'=>0,'반품'=>0,'환불'=>0,'완료'=>0,'준비'=>0,'품절'=>0);
    $res = sql_query("SELECT od_status, COUNT(*) AS cnt FROM {$g5['g5_shop_order_table']} GROUP BY od_status");
    while($r = sql_fetch_array($res)) $od_stats[$r['od_status']] = (int)$r['cnt'];
?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl"><strong>주문</strong></div>
    <div class="rb-m-btn-wrap">
      <a class="btn rb-badge rb-set-btn" href="<?php echo G5_ADMIN_URL ?>/shop_admin/configform.php">설정</a>
      <a class="btn rb-badge" href="<?php echo G5_ADMIN_URL ?>/shop_admin/orderlist.php">더보기</a>
    </div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-small">
    <?php foreach($od_stats as $k=>$v) echo $k.' <strong>'.rb_nf($v).'</strong> '; ?>
  </div>
  <div class="rb-metrics">
    <div class="left"><?php echo rb_pill('오늘'); ?></div>
    <div class="right rb-number"><strong>+<?php echo rb_nf($od_today) ?></strong></div>
  </div>
  <div class="rb-mini">
    <div class="rb-table-wrap">
      <table class="rb-table">
        <thead>
          <tr>
            <th class="rb-col-price">주문자</th>
            <th class="rb-col-state">상태</th>
            <th class="rb-col-price">금액</th>
            <th class="rb-col-dt">주문일시</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$od_list){ ?>
          <tr><td colspan="5"><?php echo $RB_EMPTY_TEXT; ?></td></tr>
        <?php } else {
          foreach($od_list as $r){
            $status_map = array('주문'=>'order','입금'=>'paid','준비'=>'ready','배송'=>'shipping','완료'=>'done','취소'=>'cancel','반품'=>'return','품절'=>'soldout');
            $st_txt = get_text($r['od_status']); $st_key = $status_map[$st_txt] ?? 'etc';
        ?>
          <tr>
            <td class="rb-col-price"><a href="<?php echo G5_ADMIN_URL ?>/shop_admin/orderform.php?od_id=<?php echo get_text($r['od_id']); ?>"><?php echo get_text($r['od_name']); ?></a></td>
            <td class="rb-col-state"><span class="rb-status rb-status-<?php echo $st_key; ?>"><?php echo $st_txt; ?></span></td>
            <td class="rb-col-price"><?php echo number_format((int)$r['od_cart_price']); ?></td>
            <td class="rb-col-dt"><?php echo substr($r['od_time'],0,16); ?></td>
          </tr>
        <?php } } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php } } ?>
