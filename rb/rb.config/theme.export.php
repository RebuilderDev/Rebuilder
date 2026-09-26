<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') return;
include_once(G5_PATH.'/rb/rb.lib/rb_theme_package.lib.php');
if (empty($_SESSION['rb_theme_package_token'])) $_SESSION['rb_theme_package_token']=bin2hex(random_bytes(32));
$rb_theme_export_support=rb_tp_archive_support();
$rb_theme_export_zip=$rb_theme_export_support['zip'];
$rb_theme_export_phar=$rb_theme_export_support['phar'];
$rb_theme_export_tokenizer=$rb_theme_export_support['tokenizer'];
$rb_theme_export_available=$rb_theme_export_zip || $rb_theme_export_phar;
$rb_theme_export_error='';
try { $rb_theme_publications=rb_tp_publications($config['cf_theme']); }
catch (Throwable $e) { $rb_theme_publications=array(); $rb_theme_export_error=$e->getMessage(); }
?>
<section class="rb_config_sec rb-theme-export" aria-labelledby="rb-theme-export-title">
    <h6 id="rb-theme-export-title" class="font-B">테마 내보내기</h6>
    <p class="rb-theme-export-description">현재 테마 폴더내 모든 파일과, 모듈에 적용된 위젯 파일을 저장합니다.<br>환경설정, 모듈설정, 섹션설정, 캐러셀, 배너 설정 데이터 및 이미지 파일을 저장하고, 일반페이지, 그룹페이지의 모듈설정도 함께 저장 됩니다. 위젯에서 참조하는 extend 파일도 검사하여 저장하며 서브페이지의 특정 노드에 설정된 모듈이나 사이드, 상단영역 설정 데이터는 저장되지 않습니다.</p>
    <div id="rb-theme-export-requirements" class="rb-theme-export-requirements">
        <p class="font-B">테마 내보내기를 사용할 수 <?php echo $rb_theme_export_available?'있습니다.':'없습니다.'; ?></p>
        <p>
            <?php foreach (array('ZIP'=>$rb_theme_export_zip,'Phar'=>$rb_theme_export_phar,'Tokenizer'=>$rb_theme_export_tokenizer) as $module_name=>$module_available) { ?>
                <?php if ($module_name==='Phar') echo ' 또는 '; elseif ($module_name==='Tokenizer') echo '<br>자동 파일 수집 (선택) : '; ?>
                <span class="rb-theme-export-module"><?php echo $module_name; ?>
                    <svg class="rb-theme-export-check <?php echo $module_available?'is-available':'is-unavailable'; ?>" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="<?php echo $module_available?'사용 가능':'사용 불가'; ?>">
                        <path d="<?php echo $module_available?'M3 8l3 3 7-7':'M4 4l8 8M12 4l-8 8'; ?>"></path>
                    </svg>
                </span>
            <?php } ?>
        </p>
    </div>
    <div class="rb-theme-export-fields">
        <fieldset id="rb-theme-export-mode">
            <legend class="font-B">배포 방식</legend>
            <div class="rb-theme-export-mode-option">
                <input type="radio" name="rb_theme_export_mode" id="rb-theme-export-mode-new" value="new" checked><label for="rb-theme-export-mode-new">새 테마로 배포</label>
            </div>
            <div id="rb-theme-export-mode-update-option" class="rb-theme-export-mode-option"<?php echo $rb_theme_publications?'':' hidden'; ?>>
                <input type="radio" name="rb_theme_export_mode" id="rb-theme-export-mode-update" value="update"<?php echo $rb_theme_publications?'':' disabled'; ?>><label for="rb-theme-export-mode-update">기존 테마 업데이트로 배포</label>
            </div>
        </fieldset>
        <div id="rb-theme-export-series-field" hidden>
            <label for="rb-theme-export-series" class="font-B">업데이트할 기존 배포</label>
            <select id="rb-theme-export-series" class="select">
                <?php foreach($rb_theme_publications as $rb_theme_publication) { ?>
                <option value="<?php echo htmlspecialchars($rb_theme_publication['id'],ENT_QUOTES,'UTF-8'); ?>" data-folder="<?php echo htmlspecialchars($rb_theme_publication['folder'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars($rb_theme_publication['name'].' · 배포 '.$rb_theme_publication['revision'],ENT_QUOTES,'UTF-8'); ?></option>
                <?php } ?>
            </select>
        </div>
        <label for="rb-theme-export-name" class="font-B">배포할테마명(영문)</label>
        <p id="rb-theme-export-mode-guide" class="rb-theme-export-description">새 테마는 현재 테마 또는 기존 배포와 다른 폴더명을 입력하세요.</p>
        <p id="rb-theme-export-name-guide" class="rb-theme-export-description">테마폴더 내 readme.txt 파일의 저작 정보를 수정해주시고, screenshot.png 파일을 교체해주세요.</p>
        <input type="text" id="rb-theme-export-name" maxlength="40" placeholder="테마폴더명 (영문) 을 설정하세요." spellcheck="false" autocapitalize="none" autocomplete="off" aria-describedby="rb-theme-export-mode-guide rb-theme-export-name-guide">
        <button type="button" id="rb-theme-export" class="font-B" aria-describedby="rb-theme-export-requirements"<?php echo $rb_theme_export_available && $rb_theme_export_error===''?'':' disabled'; ?>>테마 ZIP 내보내기</button>
        <div id="rb-theme-export-dependency-notice" role="status" aria-labelledby="rb-theme-export-dependency-title" hidden>
            <p id="rb-theme-export-dependency-title" class="font-B">추가 파일 확인</p>
            <p>자동으로 확인하지 못한 로컬 참조가 있습니다.<br>배포 전에 아래 파일을 확인해 주세요.</p>
            <details>
                <summary id="rb-theme-export-dependency-count"></summary>
                <ul id="rb-theme-export-dependency-list"></ul>
            </details>
        </div>
        <p id="rb-theme-export-status" role="status" aria-live="polite"<?php echo $rb_theme_export_error!==''?' data-state="error"':''; ?>><?php echo htmlspecialchars($rb_theme_export_error,ENT_QUOTES,'UTF-8'); ?></p>
    </div>
</section>
<iframe id="rb-theme-export-frame" name="rb_theme_export_download" title="테마 파일 다운로드" hidden></iframe>
<script>
(function () {
    var button = document.getElementById('rb-theme-export');
    var nameInput = document.getElementById('rb-theme-export-name');
    var exportMode = document.getElementById('rb-theme-export-mode');
    var newMode = document.getElementById('rb-theme-export-mode-new');
    var updateMode = document.getElementById('rb-theme-export-mode-update');
    var series = document.getElementById('rb-theme-export-series');
    var usedFolders = <?php echo json_encode(rb_tp_used_publication_folders($config['cf_theme'],$rb_theme_publications)); ?>;
    function selectedMode() { return updateMode.checked ? 'update' : 'new'; }
    function updateAvailability() {
        var available = series.options.length > 0;
        document.getElementById('rb-theme-export-mode-update-option').hidden = !available;
        updateMode.disabled = !available;
        if (!available) newMode.checked = true;
    }
    function modeGuide() {
        var updating = selectedMode() === 'update';
        if (nameInput.getAttribute('aria-invalid') === 'true') {
            nameInput.removeAttribute('aria-invalid');
            document.getElementById('rb-theme-export-status').textContent = '';
        }
        nameInput.readOnly = updating;
        if (updating && series.selectedIndex >= 0) nameInput.value = series.options[series.selectedIndex].dataset.folder;
        document.getElementById('rb-theme-export-series-field').hidden = !updating;
        document.getElementById('rb-theme-export-mode-guide').textContent = updating
            ? '업데이트는 기존 배포의 테마 폴더명을 유지합니다. 폴더명은 변경할 수 없습니다.'
            : '새 테마는 현재 테마 또는 기존 배포와 다른 폴더명을 입력하세요.';
    }
    exportMode.addEventListener('change', modeGuide);
    series.addEventListener('change', modeGuide);
    var status = document.getElementById('rb-theme-export-status');
    var frame = document.getElementById('rb-theme-export-frame');
    var canExport = <?php echo $rb_theme_export_available && $rb_theme_export_error===''?'true':'false'; ?>;
    var canScanDependencies = <?php echo $rb_theme_export_tokenizer?'true':'false'; ?>;
    var downloadId = '', cookieName = '', timer = null, started = 0, busy = false, confirming = false;
    function finish(ok, message) {
        if (!busy) return;
        busy = false;
        clearInterval(timer);
        document.cookie = cookieName + '=; Max-Age=0; Path=/; SameSite=Lax';
        button.disabled = !canExport;
        button.classList.remove('is-loading');
        button.removeAttribute('aria-busy');
        button.textContent = '테마 ZIP 내보내기';
        nameInput.readOnly = selectedMode() === 'update';
        exportMode.disabled = false; series.disabled = false;
        status.dataset.state = ok ? 'success' : 'error';
        status.textContent = message;
        if (ok) {
            var body = new URLSearchParams({mode:'publications', token:<?php echo json_encode($_SESSION['rb_theme_package_token']); ?>, theme:<?php echo json_encode($config['cf_theme']); ?>});
            fetch(<?php echo json_encode(G5_URL.'/rb/rb.config/ajax.theme_export.php'); ?>,{method:'POST',credentials:'same-origin',body:body})
                .then(function (response) { return response.json(); }).then(function (data) {
                    if (!data.ok) return;
                    var selected = series.value; series.replaceChildren(); usedFolders = data.used_folders;
                    data.publications.forEach(function (item) { var option = new Option(item.name + ' · 배포 ' + item.revision,item.id); option.dataset.folder = item.folder; series.add(option); });
                    updateAvailability();
                    if (selectedMode() === 'new' && data.publications.length) series.value = data.publications[data.publications.length - 1].id;
                    else if (selected && data.publications.some(function (item) { return item.id === selected; })) series.value = selected;
                    modeGuide();
                    var exported = data.publications.find(function (item) { return item.folder === nameInput.value; });
                    var warnings = exported && Array.isArray(exported.warnings) ? exported.warnings : [];
                    var notice = document.getElementById('rb-theme-export-dependency-notice');
                    var list = document.getElementById('rb-theme-export-dependency-list');
                    list.replaceChildren();
                    notice.hidden = !warnings.length;
                    document.getElementById('rb-theme-export-dependency-count').textContent = '확인할 항목 ' + warnings.length + '개';
                    warnings.forEach(function (warning) {
                        var item = document.createElement('li'), file = document.createElement('span'), reason = document.createElement('span');
                        var parts = String(warning).split(' · ');
                        file.className = 'rb-theme-export-dependency-file'; file.textContent = parts.shift();
                        reason.textContent = parts.join(' · ').replace(/실행 중 결정되는 경로: \*$/, '파일 경로를 코드에서 확인할 수 없습니다.');
                        item.append(file, reason); list.append(item);
                    });
                }).catch(function () {});
        }
    }
    function checkReady() {
        var ready = document.cookie.split(';').some(function (value) { return value.trim() === cookieName + '=ready'; });
        if (ready) finish(true, '압축이 완료되어 다운로드를 시작했습니다. 저장 상태는 브라우저 다운로드 목록에서 확인하세요.');
        return ready;
    }
    window.addEventListener('message', function (event) {
        if (!busy || event.origin !== window.location.origin || event.source !== frame.contentWindow) return;
        var data = event.data;
        if (data && data.type === 'rb-theme-export' && data.id === downloadId && data.ok === false)
            finish(false, data.message || '내보내기에 실패했습니다. 다시 시도해 주세요.');
    });
    frame.addEventListener('load', function () {
        if (!busy) return;
        try { if (frame.contentWindow.location.href === 'about:blank') return; } catch (e) {}
        setTimeout(function () {
            if (busy && !checkReady()) finish(false, '서버가 다운로드를 완료하지 못했습니다. 로그인 상태와 서버 응답을 확인한 뒤 다시 시도해 주세요.');
        }, 100);
    });
    function filterName() {
        var value = nameInput.value, caret = nameInput.selectionStart;
        var filtered = value.replace(/[^A-Za-z0-9_.-]/g, '');
        if (value !== filtered) {
            nameInput.value = filtered;
            if (caret !== null) { caret = value.slice(0, caret).replace(/[^A-Za-z0-9_.-]/g, '').length; nameInput.setSelectionRange(caret, caret); }
        }
    }
    nameInput.addEventListener('compositionend', filterName);
    nameInput.addEventListener('input', function (event) {
        if (!event.isComposing) filterName();
        if (nameInput.getAttribute('aria-invalid') === 'true') {
            nameInput.removeAttribute('aria-invalid');
            document.getElementById('rb-theme-export-status').textContent = '';
        }
    });
    button.addEventListener('click', function () {
        if (busy || confirming || !canExport) return;
        if (selectedMode() === 'update' && !series.value) { status.textContent = '먼저 새 테마로 배포한 뒤 업데이트할 배포를 선택해 주세요.'; return; }
        var name = nameInput.value.trim();
        if (selectedMode() === 'new' && usedFolders.indexOf(name.toLowerCase()) !== -1) {
            status.dataset.state = 'error'; status.textContent = '새 테마는 현재 테마 또는 기존 배포와 다른 폴더명을 입력해 주세요.';
            nameInput.setAttribute('aria-invalid', 'true'); nameInput.focus(); return;
        }
        if (!/^[A-Za-z0-9][A-Za-z0-9_.-]{0,39}$/.test(name) || name.indexOf('..') !== -1 || /\.$/.test(name)
            || /^(con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|$)/i.test(name)) {
            status.dataset.state = 'error'; status.textContent = '테마명은 영문·숫자로 시작하는 40자 이내의 폴더명으로 입력해 주세요. 영문·숫자·점·밑줄·하이픈만 사용할 수 있으며 예약된 폴더명은 사용할 수 없습니다.';
            nameInput.setAttribute('aria-invalid', 'true'); nameInput.focus(); return;
        }
        var publicationMode = selectedMode(), publicationId = series.value;
        var message = (publicationMode === 'update' ? '기존 테마 업데이트로 내보냅니다.' : '새 테마로 내보냅니다.')
            + ' (' + name + ')\n테마를 내보내시겠습니까?';
        if (!canScanDependencies) message = 'Tokenizer 확장이 서버에 없습니다.\n위젯 등에서 rb, theme 폴더외에 다른 폴더의 파일을 참조한다면 수동으로 파일을 추가해주세요.\n확인을 클릭하시면 내보내기가 시작됩니다.';
        confirming = true;
        var confirmation = typeof rb_confirm === 'function' ? rb_confirm(message) : Promise.resolve(window.confirm(message));
        confirmation.then(function (ok) {
        confirming = false;
        if (!ok) return;
        nameInput.removeAttribute('aria-invalid'); status.dataset.state = 'info';
        var random = new Uint8Array(16);
        window.crypto.getRandomValues(random);
        downloadId = Array.prototype.map.call(random, function (value) { return ('0' + value.toString(16)).slice(-2); }).join('');
        cookieName = 'rb_theme_export_' + downloadId;
        document.getElementById('rb-theme-export-dependency-notice').hidden = true;
        busy = true; started = Date.now();
        button.disabled = true; nameInput.readOnly = true;
        exportMode.disabled = true; series.disabled = true;
        button.classList.add('is-loading'); button.setAttribute('aria-busy', 'true');
        button.textContent = '테마 압축 중…';
        status.textContent = '테마 파일을 모아 압축하고 있습니다. 완료되면 다운로드가 시작됩니다. 용량에 따라 시간이 걸릴 수 있습니다. 압축중에 페이지를 이동하지 마세요.';
        timer = setInterval(function () {
            if (!checkReady() && Date.now() - started > 30 * 60 * 1000)
                finish(false, '서버 응답이 지연되고 있습니다. 다운로드 목록과 서버 상태를 확인한 뒤 다시 시도해 주세요.');
        }, 500);
        var form = document.createElement('form');
        form.method = 'post'; form.target = frame.name; form.action = <?php echo json_encode(G5_URL.'/rb/rb.config/ajax.theme_export.php'); ?>;
        var values = {mode: 'export', publication_mode: publicationMode, publication_id: publicationId, download_id: downloadId, theme: <?php echo json_encode($config['cf_theme']); ?>, name: name, token: <?php echo json_encode($_SESSION['rb_theme_package_token']); ?>};
        Object.keys(values).forEach(function (key) { var input = document.createElement('input'); input.type='hidden'; input.name=key; input.value=values[key]; form.appendChild(input); });
        document.body.appendChild(form);
        try { form.submit(); } catch (e) { finish(false, '다운로드 요청을 보내지 못했습니다. 다시 시도해 주세요.'); }
        form.remove();
        }).catch(function () { confirming = false; if (busy) finish(false, '내보내기 요청을 보내지 못했습니다. 다시 시도해 주세요.'); });
    });
}());
</script>
