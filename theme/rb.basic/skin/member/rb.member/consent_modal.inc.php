<?php if (!defined('_GNUBOARD_')) exit; ?>
<div id="rb-consent-dialogs">
<?php foreach ($rb_consent_dialogs as $rb_consent_key => $rb_consent) {
    $rb_consent_id = 'rb-consent-' . $rb_consent_key;
    $rb_consent_title = htmlspecialchars($rb_consent['title'], ENT_QUOTES, 'UTF-8');
?>
  <div id="<?php echo $rb_consent_id; ?>" class="rb-consent-dialog" tabindex="-1" role="dialog" aria-labelledby="<?php echo $rb_consent_id; ?>-title" aria-describedby="<?php echo $rb_consent_id; ?>-body">
    <div class="cd-card">
      <header class="cd-head">
        <h3 id="<?php echo $rb_consent_id; ?>-title" class="cd-title"><?php echo $rb_consent_title; ?></h3>
      </header>
      <div id="<?php echo $rb_consent_id; ?>-body" class="cd-body" tabindex="0"><?php echo $rb_consent['body']; ?></div>
      <footer class="cd-actions">
        <button type="button" class="cd-agree main_rb_bg" data-check="<?php echo $rb_consent['check']; ?>"<?php if (isset($rb_consent['group'])) { ?> data-check-group="<?php echo $rb_consent['group']; ?>"<?php } ?> hidden>동의합니다</button>
        <a class="cd-close" href="#rb-consent-link-<?php echo $rb_consent_key; ?>">닫기</a>
      </footer>
    </div>
  </div>
<?php } ?>
</div>

<style>
#fregisterform .js-open-consent { touch-action:manipulation; z-index:1; scroll-margin-top:90px; }
.rb-consent-dialog {
  position:fixed;
  inset:0;
  z-index:1000000;
  display:none;
  align-items:center;
  justify-content:center;
  box-sizing:border-box;
  padding:12px;
  background:rgba(0,0,0,.45);
}
.rb-consent-dialog:target { display:flex; }
.rb-consent-dialog .cd-card {
  display:flex;
  flex-direction:column;
  width:100%;
  max-width:400px;
  max-height:100%;
  background:#fff;
  border-radius:12px;
  overflow:hidden;
}
.rb-consent-dialog .cd-head { flex-shrink:0; padding:16px; }
.rb-consent-dialog .cd-title { margin:0; font-size:18px; font-weight:bold; color:#222; }
.rb-consent-dialog .cd-body {
  min-height:0;
  max-height:500px;
  overflow-y:auto;
  overscroll-behavior:contain;
  padding:16px;
  border-top:1px solid #e6e6e9;
  border-bottom:1px solid #e6e6e9;
  line-height:1.6;
  font-size:14px;
  color:#222;
}
.rb-consent-dialog .cd-actions { flex-shrink:0; display:flex; gap:8px; justify-content:flex-end; padding:12px 16px 16px; }
.rb-consent-dialog .cd-agree { padding:8px 14px; color:#fff; border-radius:8px; }
.rb-consent-dialog .cd-close { padding:8px 14px; border:1px solid #ccc; background:#fff; color:#111; border-radius:8px; }
.rb-consent-dialog .cd-agree[hidden] { display:none; }
</style>

<script>
(function() {
  const dialogs = document.getElementById('rb-consent-dialogs');
  const form = document.getElementById('fregisterform');
  if (!dialogs || !form) return;

  // 링크의 기본 이동과 :target으로 열고, 스크립트는 동의와 포커스를 처리합니다.
  document.body.appendChild(dialogs);
  let activeDialog = null;

  function syncDialog() {
    const nextDialog = dialogs.querySelector('.rb-consent-dialog:target');
    if (activeDialog === nextDialog) return;

    if (activeDialog) {
      activeDialog.removeAttribute('aria-modal');
      if (!nextDialog) {
        const openerId = activeDialog.querySelector('.cd-close').hash.slice(1);
        const opener = document.getElementById(openerId);
        if (opener) opener.focus({preventScroll:true});
      }
    }

    activeDialog = nextDialog;
    if (activeDialog) {
      activeDialog.setAttribute('aria-modal', 'true');
      activeDialog.querySelector('.cd-body').scrollTop = 0;
      activeDialog.querySelector('.cd-close').focus({preventScroll:true});
    }
  }

  function closeDialog(dialog) {
    window.location.replace(dialog.querySelector('.cd-close').href);
  }

  dialogs.querySelectorAll('.rb-consent-dialog').forEach(function(dialog) {
    const agreeButton = dialog.querySelector('.cd-agree');
    const closeLink = dialog.querySelector('.cd-close');
    agreeButton.hidden = false;

    closeLink.addEventListener('click', function(event) {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      closeDialog(dialog);
    });

    dialog.addEventListener('click', function(event) {
      if (event.target === dialog) closeDialog(dialog);
    });

    agreeButton.addEventListener('click', function() {
      const group = agreeButton.dataset.checkGroup;
      if (group) form.querySelectorAll(group).forEach(checkConsent);

      const checkbox = form.querySelector(agreeButton.dataset.check);
      if (checkbox) checkConsent(checkbox);
      closeDialog(dialog);
    });
  });

  function checkConsent(checkbox) {
    checkbox.checked = true;
    checkbox.dispatchEvent(new Event('change', {bubbles:true}));
  }

  window.addEventListener('hashchange', syncDialog);
  syncDialog();

  document.addEventListener('keydown', function(event) {
    if (!activeDialog) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeDialog(activeDialog);
      return;
    }
    if (event.key !== 'Tab') return;

    const focusable = activeDialog.querySelectorAll('[tabindex="0"], a[href], button:not([hidden]):not([disabled])');
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const focused = document.activeElement;
    if (!activeDialog.contains(focused) || (event.shiftKey ? focused === first : focused === last)) {
      event.preventDefault();
      (event.shiftKey ? last : first).focus();
    }
  });
})();
</script>
