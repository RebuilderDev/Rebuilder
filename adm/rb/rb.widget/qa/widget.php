<?php if (!auth_check_menu($auth, '300500', 'r', true)) { ?>
<div class="rb-card">
  <div class="rb-hd">
    <div class="rb-ttl">최근 1:1 문의</div>
    <div class="rb-m-btn-wrap">
      <a class="btn rb-badge rb-set-btn" href="<?php echo G5_ADMIN_URL ?>/qa_config.php">설정</a>
      <a class="btn rb-badge" href="<?php echo G5_BBS_URL ?>/qalist.php" target="_blank">더보기</a>
    </div>
    <button class="rb-close" type="button" aria-label="삭제">×</button>
  </div>
  <div class="rb-kanban">
    <?php if (count($qa_rows) === 0) { ?>
      <div class="rb-small"><?php echo $RB_EMPTY_TEXT; ?></div>
    <?php } else { foreach ($qa_rows as $q) {
        $state = (isset($q['qa_status']) && $q['qa_status']) ? '완료' : '접수';
        $state_class = (isset($q['qa_status']) && $q['qa_status']) ? '' : 'new_qa';
    ?>
      <div class="kb-item">
        <div class="kb-title"><a href="<?php echo G5_BBS_URL ?>/qaview.php?qa_id=<?php echo $q['qa_id'] ?>" target="_blank"><?php echo get_text($q['qa_subject']); ?></a></div>
        <div class="rb-badge <?php echo $state_class ?>"><?php echo $state; ?></div>
      </div>
      <div class="kb-meta"><?php echo substr($q['qa_datetime'],0,16); ?></div>
    <?php } } ?>
  </div>
</div>
<?php } ?>
