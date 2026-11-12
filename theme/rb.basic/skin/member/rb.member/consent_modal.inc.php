<?php if (!defined('_GNUBOARD_')) exit; ?>
<div id="consentDialog" hidden>
  <div class="cd-card" role="dialog" aria-modal="true" aria-labelledby="consentDialogTitle" aria-describedby="consentDialogBody">
    <header class="cd-head">
      <h3 id="consentDialogTitle" class="cd-title">안내</h3>
    </header>
    <div id="consentDialogBody" class="cd-body" tabindex="0"></div>
    <footer class="cd-actions">
      <button type="button" class="cd-agree main_rb_bg">동의합니다</button>
      <button type="button" class="cd-close">닫기</button>
    </footer>
  </div>
</div>

<style>
#consentDialog {
  position:fixed;
  inset:0;
  z-index:1000000;
  display:flex;
  align-items:center;
  justify-content:center;
  box-sizing:border-box;
  padding:12px;
  background:rgba(0,0,0,.45);
}
#consentDialog[hidden] { display:none; }
#consentDialog .cd-card {
  display:flex;
  flex-direction:column;
  width:100%;
  max-width:400px;
  max-height:100%;
  background:#fff;
  border-radius:12px;
  overflow:hidden;
}
#consentDialog .cd-head { flex-shrink:0; padding:16px; }
#consentDialog .cd-title { margin:0; font-size:18px; font-weight:bold; color:#222; }
#consentDialog .cd-body {
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
#consentDialog .cd-actions { flex-shrink:0; display:flex; gap:8px; justify-content:flex-end; padding:12px 16px 16px; }
#consentDialog .cd-agree { padding:8px 14px; color:#fff; border-radius:8px; }
#consentDialog .cd-close { padding:8px 14px; border:1px solid #ccc; background:#fff; color:#111; border-radius:8px; }
</style>

<script>
(function() {
  const dlg = document.getElementById('consentDialog');
  if (!dlg) return;

  const body = document.getElementById('consentDialogBody');
  const title = document.getElementById('consentDialogTitle');
  const agreeButton = dlg.querySelector('.cd-agree');
  const closeButton = dlg.querySelector('.cd-close');
  let opener = null;

  // 가입 폼과 별도로 표시해 상위 레이아웃의 위치 및 잘림 영향을 피합니다.
  document.body.appendChild(dlg);

  function closeDialog() {
    if (dlg.hidden) return;
    dlg.hidden = true;
    if (opener) opener.focus({preventScroll:true});
    opener = null;
  }

  document.addEventListener('click', function(event) {
    const trigger = event.target.closest('.js-open-consent');
    if (!trigger) return;

    const selector = trigger.getAttribute('data-template');
    const template = selector ? document.querySelector(selector) : null;
    opener = trigger;
    title.textContent = trigger.getAttribute('data-title') || '안내';
    body.innerHTML = template ? template.innerHTML : '';
    dlg.hidden = false;
    body.scrollTop = 0;
    closeButton.focus({preventScroll:true});
  });

  closeButton.addEventListener('click', closeDialog);
  dlg.addEventListener('click', function(event) {
    if (event.target === dlg) closeDialog();
  });

  agreeButton.addEventListener('click', function() {
    if (!opener) return;
    const selector = opener.getAttribute('data-check');
    const groupSelector = opener.getAttribute('data-check-group');

    if (groupSelector) {
      document.querySelectorAll(groupSelector).forEach(function(checkbox) {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change', {bubbles:true}));
      });
    }

    const checkbox = selector ? document.querySelector(selector) : null;
    if (checkbox) {
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', {bubbles:true}));
    }
    closeDialog();
  });

  document.addEventListener('keydown', function(event) {
    if (dlg.hidden) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeDialog();
      return;
    }
    if (event.key !== 'Tab') return;

    const focusable = dlg.querySelectorAll('[tabindex="0"], a[href], button:not([disabled])');
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement;
    if (!dlg.contains(active) || (event.shiftKey ? active === first : active === last)) {
      event.preventDefault();
      (event.shiftKey ? last : first).focus();
    }
  });
})();
</script>
