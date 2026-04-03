<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl">접속자 <span class="rb-small">현재접속 <strong><?php echo rb_nf($connect_now) ?></strong></span></div>
    <div class="rb-m-btn-wrap">
      <a class="btn rb-badge rb-set-btn" href="<?php echo G5_ADMIN_URL ?>/visit_list.php">집계</a>
      <a class="btn rb-badge" href="<?php echo G5_BBS_URL ?>/current_connect.php" target="_blank">현재</a>
    </div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-small">
    누적 <?php echo rb_nf($visit_acc) ?> &nbsp;&nbsp;
    오늘 <?php echo rb_nf($visit_today) ?> &nbsp;&nbsp;
    어제 <?php echo rb_nf($visit_yesterday) ?> &nbsp;&nbsp;
    최대 <?php echo rb_nf($visit_max_cnt) ?>
  </div>
  <div class="rb-metrics">
    <div class="left"><?php echo rb_pill('오늘'); ?></div>
    <div class="right"><strong>+<?php echo rb_nf($visit_today) ?></strong></div>
  </div>
  <div class="rb-mini">
    <div class="rb-table-wrap">
      <table class="rb-table">
        <thead>
          <tr>
            <th class="rb-col-ip">IP</th>
            <th class="rb-col-dt">접속일시</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$visit_list){ ?>
          <tr><td colspan="3"><?php echo $RB_EMPTY_TEXT; ?></td></tr>
        <?php } else { foreach($visit_list as $r){
          $ref = $r['vi_referer']; $ref_host = '';
          if ($ref) { $p = @parse_url($ref); $ref_host = isset($p['host']) ? $p['host'] : $ref; }
        ?>
          <tr>
            <td class="rb-col-ip"><?php echo $r['vi_ip']; ?></td>
            <td class="rb-col-dt"><?php echo $r['vi_date']; ?> <?php echo substr($r['vi_time'],0,5); ?></td>
          </tr>
        <?php }} ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
