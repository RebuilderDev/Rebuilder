<?php if (!auth_check_menu($auth, '200200', 'r', true)) { ?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl">최근 포인트 발생내역</div>
    <div class="rb-m-btn-wrap"><a class="btn rb-badge" href="./point_list.php">더보기</a></div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-point-list">
    <?php if (count($point_rows) === 0) { ?>
      <div class="rb-small"><?php echo $RB_EMPTY_TEXT; ?></div>
    <?php } else { foreach ($point_rows as $r) {
        $nick = $r['mb_nick'] ?: $r['mb_id'];
        $amt  = (int)$r['po_point']; $plus = $amt >= 0;
    ?>
    <div class="rb-row">
      <div class="rb-avatar" aria-hidden="true"><?php echo get_member_profile_img($r['mb_id']); ?></div>
      <div class="rb-point-col">
        <div class="rb-title">
          <?php echo get_text($nick) ?>
          <span class="<?php echo $plus ? 'rb-amt-plus' : 'rb-amt-minus'; ?>">
            <strong><?php echo rb_nf(abs($amt)); ?>P <?php echo $plus ? '+' : '-'; ?></strong>
          </span>
        </div>
        <div class="rb-sub"><?php echo $r['po_datetime'] ?></div>
      </div>
    </div>
    <?php } } ?>
  </div>
</div>
<?php } ?>
