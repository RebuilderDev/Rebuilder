<?php
$sub_menu = '';
require_once './_common.php';
@require_once './safe_check.php';

if (function_exists('social_log_file_delete')) {
    // 소셜로그인 디버그 파일 24시간 지난 것은 삭제
    social_log_file_delete(86400);
}

$g5['title'] = '관리자메인';
require_once './admin.head.php';


$addtional_content_before = run_replace('adm_index_addtional_content_before', '', $is_admin, $auth, $member);
if ($addtional_content_before) echo $addtional_content_before;

/* ===== 위젯 공통 ===== */
if (!defined('RBW_DIR')) define('RBW_DIR', G5_ADMIN_PATH.'/rb/rb.widget');
if (!defined('RBW_URL')) define('RBW_URL', G5_ADMIN_URL.'/rb/rb.widget');
require_once RBW_DIR.'/_common/helpers.php';
require_once RBW_DIR.'/_common/widgets_boot.php';

/* 1) 카탈로그 스캔 */
$rbw_catalog = [];
foreach (glob(RBW_DIR.'/*/meta.php') as $metaFile) {
    $meta = @include $metaFile;
    if (is_array($meta) && !empty($meta['key'])) {
        $rbw_catalog[$meta['key']] = $meta;
    }
}

/* 2) 활성 위젯 로드 */
$active_widgets = rb_fetch_all(sql_query("
    SELECT * FROM rb_admin_widget
    WHERE aw_enabled = 1
    ORDER BY aw_sort ASC, aw_id ASC
"));

/* 3) 렌더 함수 */
function rbw_render_widget_row($w, $catalog) {
    $key = $w['aw_key'];
    if (!isset($catalog[$key])) return '';
    $meta_name = isset($catalog[$key]['name']) ? (string)$catalog[$key]['name'] : $key;
    $path = RBW_DIR."/{$key}/widget.php";
    if (!is_file($path)) return '';

    extract($GLOBALS, EXTR_SKIP);

    $conf = [];
    if (!empty($w['aw_conf'])) {
        $tmp = json_decode($w['aw_conf'], true);
        if (is_array($tmp)) $conf = $tmp;
    }

    $span = isset($w['aw_span']) ? (int)$w['aw_span'] : 1;
    if ($span < 1) $span = 1;
    if ($span > 4) $span = 4;
    $span_label = 'S';
    if ($span >= 4) $span_label = 'L';
    else if ($span >= 2) $span_label = 'M';

    ob_start();
    echo '<div class="rb-card-wrap" data-aw-id="'.(int)$w['aw_id'].'" data-aw-span="'.$span.'" data-aw-key="'.htmlspecialchars($key).'" data-aw-name="'.htmlspecialchars($meta_name).'">';
    echo '<div class="rbw-item-tools"><select class="rbw-span-select" data-aw-id="'.(int)$w['aw_id'].'">';
    echo '<option value="1"'.get_selected($span_label, 'S', true).'>S</option>';
    echo '<option value="2"'.get_selected($span_label, 'M', true).'>M</option>';
    echo '<option value="4"'.get_selected($span_label, 'L', true).'>L</option>';
    echo '</select></div>';
    include $path;
    echo '</div>';
    return ob_get_clean();
}

/* 4) 영역 HTML */
$render_main = '';
$added_keys = [];
foreach ($active_widgets as $w) {
    $html = rbw_render_widget_row($w, $rbw_catalog);
    $render_main .= $html;
    $added_keys[$w['aw_key']] = true;
}
?>

<style>
    #container_title {display: none;}
    #container .container_wr {padding: 30px 0px 30px 0px !important;background-color: transparent; border-radius: 10px;}
    @media all and (max-width:768px) {
        #container .container_wr {padding: 20px 0px 20px 0px !important;}
    }
</style>

<div class="rb-wrap">
    <div class="rb-grid" id="rbw-area-main">
        <?php
      echo $render_main ?: '<div style="background-color:#f9f9f9; padding:50px 0px; text-align:center; width:100%; display:block; border-radius:10px;">위젯을 추가해보세요!<br>추가된 위젯은 드래그 앤 드롭으로 위치변경이 가능해요.<br></div>';
    ?>
    </div>
</div>

<div class="rbw-toolbar">
    <button type="button" class="btn rb-badge" id="rbw-add-widget">위젯추가</button>
</div>

<div id="rbw-modal">
    <div class="rbw-modal-body">
        <h3>위젯 추가</h3>
        <p class="rbw-modal-guide">추가할 위젯의 크기를 먼저 선택한 뒤 위젯을 추가하세요.</p>
        <div class="rbw-span-radio">
            <label><input type="radio" name="rbw_span" value="1" checked> S</label>
            <label><input type="radio" name="rbw_span" value="2"> M</label>
            <label><input type="radio" name="rbw_span" value="4"> L</label>
        </div>
        <ul id="rbw-widget-catalog">
            <?php
              $count = 0;
              foreach ($rbw_catalog as $key=>$m) {
                  if (isset($added_keys[$key])) continue;
                  $count++;
              ?>
            <li data-key="<?php echo $key; ?>">
                <div>
                    <strong><?php echo get_text($m['name']); ?></strong>
                    <span>(<?php echo get_text($key); ?>)</span>
                </div>
                <button type="button" class="rbw-add" data-key="<?php echo $key; ?>">추가</button>
            </li>
            <?php } ?>

            <?php if ($count === 0) { ?>
            <div id="rbw-widget-empty" style="text-align:center; color:#888; background:#f9f9f9; padding:20px 0">
                모든 위젯을 사용중입니다.
            </div>
            <?php } ?>
        </ul>
        <div style="margin-top:12px; text-align:right;">
            <button type="button" id="rbw-modal-close" class="rbw-add">닫기</button>
        </div>
    </div>
</div>

<script src="<?php echo G5_ADMIN_URL; ?>/rb/rb.widget/sortable.min.js"></script>

<script>
    const RBW_AJAX = "<?php echo G5_ADMIN_URL; ?>/rb/rb.widget/rb_widget_api.php";

    // 안전 JSON 파서
    async function parseJSON(res) {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            return {
                ok: false,
                msg: '저장에 문제가 있습니다.',
                detail: text
            };
        }
    }

    // 모달 토글
    const modal = document.getElementById('rbw-modal');
    document.getElementById('rbw-add-widget').onclick = () => {
        modal.style.display = 'flex';
    };
    document.getElementById('rbw-modal-close').onclick = () => {
        modal.style.display = 'none';
    };

    // 카탈로그 → 추가
    document.querySelectorAll('#rbw-widget-catalog .rbw-add').forEach(btn => {
        btn.addEventListener('click', async () => {
            const key = btn.dataset.key;
            const checkedSpan = document.querySelector('input[name="rbw_span"]:checked');
            const span = checkedSpan ? checkedSpan.value : '1';
            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('key', key);
            fd.append('span', span);

            try {
                const res = await fetch(RBW_AJAX, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const j = await parseJSON(res);
                if (j.ok) location.reload();
                else alert(j.msg || '위젯 추가 실패');
            } catch (e) {
                console.error(e);
                alert('저장에 문제가 있습니다.');
            }
        });
    });

    // 순서 저장
    function saveOrder() {
        const mainIds = Array.from(document.querySelectorAll('#rbw-area-main .rb-card-wrap')).map(el => el.dataset.awId);

        const fd = new FormData();
        fd.append('action', 'sort');
        fd.append('main', JSON.stringify(mainIds));

        fetch(RBW_AJAX, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(parseJSON)
            .then(j => {
                if (!j.ok) {
                    alert(j.msg || '위젯 순서 저장에 실패했습니다.');
                    return;
                }
                if (Array.isArray(j.moved_forbidden) && j.moved_forbidden.length) {
                    console.warn('Area-forbidden:', j.moved_forbidden);
                }
            })
            .catch(() => alert('저장에 문제가 있습니다.'));
    }

    // 디바운스
    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    }
    const saveOrderDebounced = debounce(saveOrder, 400);

    // Sortable 적용
    <?php if(IS_MOBILE()) { ?>
    <?php } else { ?>

    function makeSortable(containerId) {
        const el = document.getElementById(containerId);
        if (!el) return;
        new Sortable(el, {
            group: 'rbw',
            animation: 150,
            draggable: '.rb-card-wrap',
            onEnd: saveOrderDebounced
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        makeSortable('rbw-area-main');

        document.querySelectorAll('.rb-card-wrap').forEach((wrap) => {
            const tools = wrap.querySelector('.rbw-item-tools');
            const hd = wrap.querySelector('.rb-card .rb-hd');
            const closeBtn = wrap.querySelector('.rb-card .rb-close');
            const card = wrap.querySelector('.rb-card');
            const btnWrap = wrap.querySelector('.rb-card .rb-m-btn-wrap');
            const widgetName = wrap.dataset.awName || '';
            const widgetKey = wrap.dataset.awKey || '';
            if (!tools || !hd) return;
            if (closeBtn && closeBtn.parentNode === hd) {
                hd.insertBefore(tools, closeBtn);
            } else {
                hd.appendChild(tools);
            }

            if (card) {
                let footer = wrap.querySelector('.rbw-card-footer');
                if (!footer) {
                    footer = document.createElement('div');
                    footer.className = 'rbw-card-footer';
                    footer.innerHTML = '<div class="rbw-card-meta"></div><div class="rbw-card-actions"></div>';
                    card.appendChild(footer);
                }

                const metaEl = footer.querySelector('.rbw-card-meta');
                const actionsEl = footer.querySelector('.rbw-card-actions');
                if (metaEl) {
                    metaEl.textContent = widgetName ? (widgetName + ' (' + widgetKey + ')') : widgetKey;
                }
                if (btnWrap && actionsEl && btnWrap.parentNode !== actionsEl) {
                    actionsEl.appendChild(btnWrap);
                }
            }
        });
    });
    <?php } ?>

    // 스타일
    const style = document.createElement('style');
    style.textContent = `
  .rb-card-wrap { cursor: move; }
  .rb-card-wrap .rb-hd, .rb-card-wrap .rb-ttl { cursor: move; }
  .rb-card-wrap a, .rb-card-wrap button { cursor: auto; }
`;
    document.head.appendChild(style);

    // 위젯 삭제
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('rb-close')) {
            const wrap = e.target.closest('.rb-card-wrap');
            if (!wrap) return;
            const aw_id = wrap.dataset.awId;

            if (typeof rb_confirm === 'function') {
                rb_confirm("이 위젯을 삭제할까요?").then(async (confirmed) => {
                    if (!confirmed) return;
                    await removeWidget(aw_id, wrap);
                });
            } else {
                if (confirm("이 위젯을 삭제할까요?")) {
                    removeWidget(aw_id, wrap);
                }
            }
        }
    });

    // 삭제 함수
    async function removeWidget(aw_id, wrap) {
        const fd = new FormData();
        fd.append('action', 'remove');
        fd.append('aw_id', aw_id);

        try {
            const res = await fetch(RBW_AJAX, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const j = await parseJSON(res);
            if (j.ok) {
                wrap.remove();
                saveOrderDebounced();
                location.reload();
            } else {
                alert(j.msg || '위젯 삭제에 실패했습니다.');
            }
        } catch (err) {
            console.error(err);
            alert('삭제에 문제가 있습니다.');
        }
    }

    document.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('rbw-span-select')) return;
        const aw_id = e.target.dataset.awId;
        const span = e.target.value || '1';
        const wrap = e.target.closest('.rb-card-wrap');
        const fd = new FormData();
        fd.append('action', 'update_span');
        fd.append('aw_id', aw_id);
        fd.append('span', span);

        try {
            const res = await fetch(RBW_AJAX, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const j = await parseJSON(res);
            if (j.ok) {
                if (wrap) wrap.dataset.awSpan = span;
            } else {
                alert(j.msg || '위젯 너비 변경에 실패했습니다.');
            }
        } catch (err) {
            console.error(err);
            alert('저장에 문제가 있습니다.');
        }
    });
</script>

<?php
$addtional_content_after = run_replace('adm_index_addtional_content_after', '', $is_admin, $auth, $member);
if ($addtional_content_after) echo $addtional_content_after;

require_once './admin.tail.php';
