(function () {
    'use strict';
    var root = document.getElementById('rb-theme-install');
    if (!root) return;
    var file = document.getElementById('rb-tp-file');
    var status = document.getElementById('rb-tp-status');
    var details = document.getElementById('rb-tp-details');
    var mappings = document.getElementById('rb-tp-maps');
    var check = document.getElementById('rb-tp-check');
    var install = document.getElementById('rb-tp-install');
    var busy = false, updating = false, optionalConnections = false, moduleConnections = false;
    function moduleLabel(label, module) {
        var title = document.createElement('b'), location = document.createElement('span');
        title.style.cssText = 'display:block; margin-bottom:6px;';
        title.textContent = module.title || module.type + ' 모듈 ' + module.id;
        location.className = 'frm_info'; location.style.cssText = 'display:block; padding:0;';
        location.textContent = module.area + ' · ' + module.type;
        var position = document.createElement('span'); position.style.cssText = 'display:block; margin-top:2px;';
        position.textContent = '모듈 ' + module.id;
        if (module.tabs && module.tabs.length) position.textContent += ' · 탭 ' + module.tabs.join(', ');
        location.append(position); label.append(title, location);
    }
    function connectionRow(module, index, categories) {
        var row = document.createElement('tr'), heading = document.createElement('th'), cell = document.createElement('td');
        heading.scope = 'row'; moduleLabel(heading, module);
        module.slots.forEach(function (slot, slotIndex) {
            var block = document.createElement('div'), label = document.createElement('label'), select = document.createElement('select');
            if (slot.tab) {
                block.style.cssText = 'display:inline-block; vertical-align:top; background:#f0f5f9; padding:16px; margin:0 16px 16px 0;';
                var tabTitle = document.createElement('strong');
                tabTitle.style.cssText = 'display:block; margin-bottom:12px;';
                tabTitle.textContent = '탭 ' + slot.tab; block.append(tabTitle);
            }
            select.id = 'rb-tp-module-' + index + '-' + slotIndex;
            select.dataset.connection = slot.key;
            label.htmlFor = select.id; label.style.cssText = 'display:block; margin-bottom:8px;';
            label.textContent = '표시할 ' + ({board:'게시판', poll:'설문', category:'상품 분류'}[slot.kind]);
            block.append(label);
            if (!Object.keys(slot.options).length) block.append(document.getElementById('rb-tp-empty-help').content.cloneNode(true));
            select.add(new Option('설치 후 모듈에서 설정', ''));
            Object.keys(slot.options).forEach(function (id) { select.add(new Option(slot.options[id] + ' (' + id + ')', id)); });
            block.append(select);
            if (slot.kind === 'board') {
                var categoryWrap = document.createElement('div'), categoryLabel = document.createElement('label'), category = document.createElement('select');
                categoryWrap.style.cssText = 'margin-top:16px;';
                category.id = select.id + '-category'; category.dataset.categoryFor = slot.key;
                categoryLabel.htmlFor = category.id; categoryLabel.style.cssText = 'display:block; margin-bottom:8px;';
                categoryLabel.textContent = '게시판 카테고리';
                function refreshCategories() {
                    category.replaceChildren(); category.add(new Option('전체', ''));
                    (categories[select.value] || []).forEach(function (name) { category.add(new Option(name, name)); });
                    category.disabled = !select.value || !(categories[select.value] || []).length;
                }
                select.addEventListener('change', refreshCategories); refreshCategories();
                categoryWrap.append(categoryLabel, category); block.append(categoryWrap);
            }
            cell.append(block);
        });
        row.append(heading, cell); return row;
    }
    function request(mode, extra) {
        var body = new URLSearchParams({mode: mode, token: root.dataset.token, folder: file.value});
        Object.keys(extra || {}).forEach(function (key) { body.set(key, extra[key]); });
        return fetch('./theme_package.php', {method: 'POST', credentials: 'same-origin', body: body})
            .then(function (response) { return response.json(); })
            .then(function (data) { if (!data.ok) throw new Error(data.message || '처리에 실패했습니다.'); return data; });
    }
    function working(value) { busy = value; check.disabled = value; install.disabled = value; file.disabled = value; if (value) status.hidden = false; }
    file.addEventListener('change', function () { details.hidden = true; status.textContent = ''; status.hidden = true; });
    check.addEventListener('click', function () {
        if (root.dataset.installVersionError) { window.alert(root.dataset.installVersionError); return; }
        if (busy || !file.value) { if (!file.value) { status.hidden = false; status.textContent = 'FTP로 올린 테마 폴더를 선택해 주세요.'; } return; }
        working(true); details.hidden = true; status.textContent = '테마 파일과 메인 모듈을 확인하고 있습니다…';
        request('inspect').then(function (data) {
            document.getElementById('rb-tp-name').textContent = data.name;
            document.getElementById('rb-tp-theme').value = data.theme;
            updating = data.updating;
            optionalConnections = data.optional_connections === true;
            moduleConnections = data.module_connections === true;
            install.textContent = updating ? '업데이트 반영' : '테마 설치';
            document.getElementById('rb-tp-install-guide').textContent = updating
                ? 'FTP로 올린 파일의 경로 연결을 반영합니다. 기존 모듈·섹션·스킨 선택·캐러셀·배너 설정과 개별 CSS는 유지합니다.'
                : '현재 업로드된 폴더명을 기준으로 설치합니다. 폴더명을 바꾸셨다면 새로고침 후 다시 선택하세요.';
            document.getElementById('rb-tp-map-guide').hidden = updating || !data.choices.length;
            document.getElementById('rb-tp-shop-guide').hidden = updating || data.shop_enabled !== false;
            mappings.replaceChildren();
            var labels = {board: '게시판', content: '내용 페이지', form: '폼', poll: '설문', category: '상품 분류', group: '게시판 그룹', item: '상품', event: '이벤트'};
            var currentArea = '';
            data.choices.forEach(function (choice, index) {
                if (moduleConnections) {
                    if (currentArea !== choice.area) {
                        var areaRow = document.createElement('tr'), areaHeading = document.createElement('th');
                        areaHeading.colSpan = 2; areaHeading.scope = 'colgroup';
                        areaHeading.style.cssText = 'background:#f0f5f9; padding:16px;';
                        areaHeading.textContent = choice.area + ' 모듈'; areaRow.append(areaHeading); mappings.append(areaRow);
                        currentArea = choice.area;
                    }
                    mappings.append(connectionRow(choice, index, data.board_categories || {})); return;
                }
                var row = document.createElement('tr'), heading = document.createElement('th'), cell = document.createElement('td');
                var label = document.createElement('label'), select = document.createElement('select');
                heading.scope = 'row';
                select.id = 'rb-tp-map-' + index; select.dataset.kind = choice.kind; select.dataset.source = choice.source;
                label.htmlFor = select.id;
                label.style.cssText = 'display:block; line-height:1.6; word-break:break-word;';
                (choice.modules || []).forEach(function (module) {
                    var usage = document.createElement('span'), title = document.createElement('b'), location = document.createElement('span');
                    usage.style.cssText = 'display:block;';
                    if (label.childNodes.length) usage.style.marginTop = '12px';
                    title.style.cssText = 'display:block; margin-bottom:6px;';
                    title.textContent = module.title || module.type + ' 모듈 ' + module.id;
                    location.className = 'frm_info'; location.style.cssText = 'display:block; padding:0;';
                    location.textContent = module.area + ' · ' + module.type;
                    var position = document.createElement('span'); position.style.cssText = 'display:block; margin-top:2px;';
                    position.textContent = '모듈 ' + module.id;
                    if (module.tabs && module.tabs.length) position.textContent += ' · 탭 ' + module.tabs.join(', ');
                    location.append(position);
                    usage.append(title, location); label.append(usage);
                });
                if (!label.childNodes.length) label.textContent = '이전 배포 자료의 연결 항목';
                var dataLabel = document.createElement('span');
                dataLabel.style.cssText = 'display:block; margin-bottom:8px;';
                dataLabel.textContent = '표시할 ' + (labels[choice.kind] || choice.kind);
                select.add(new Option(optionalConnections ? '설치 후 모듈에서 설정' : '연결할 항목 선택', ''));
                Object.keys(choice.options).forEach(function (id) { select.add(new Option(choice.options[id] + ' (' + id + ')', id)); });
                select.value = choice.selected;
                heading.append(label); cell.append(dataLabel);
                if (!Object.keys(choice.options).length) {
                    var helpTemplate = document.getElementById(optionalConnections ? 'rb-tp-empty-help' : 'rb-tp-legacy-help');
                    cell.append(helpTemplate.content.cloneNode(true));
                }
                cell.append(select);
                row.append(heading, cell);
                mappings.append(row);
            });
            details.hidden = false; status.textContent = updating ? '업로드 자료를 확인했습니다. 업데이트 반영을 눌러 주세요.'
                : (data.choices.length ? (optionalConnections ? '필요한 메인 모듈만 연결하거나, 바로 테마 설치를 눌러 주세요.' : '이전 배포 자료의 연결 정보를 확인한 뒤 설치해 주세요.') : '테마 자료를 확인했습니다. 테마 설치를 눌러 주세요.');
        }).catch(function (e) { status.textContent = e.message; }).finally(function () { working(false); });
    });
    install.addEventListener('click', function () {
        if (root.dataset.installVersionError) { window.alert(root.dataset.installVersionError); return; }
        if (busy) return;
        var maps = moduleConnections ? {modules:{}} : {}, missing = false;
        if (moduleConnections) {
            mappings.querySelectorAll('select[data-connection]').forEach(function (select) {
                maps.modules[select.dataset.connection] = {target:select.value, category:''};
            });
            mappings.querySelectorAll('select[data-category-for]').forEach(function (select) {
                maps.modules[select.dataset.categoryFor].category = select.value;
            });
        } else mappings.querySelectorAll('select').forEach(function (select) {
            if (!select.value && !optionalConnections) missing = true;
            if (!maps[select.dataset.kind]) maps[select.dataset.kind] = {};
            maps[select.dataset.kind][select.dataset.source] = select.value;
        });
        if (missing) { status.textContent = '연결할 항목을 모두 선택해 주세요.'; return; }
        working(true); status.textContent = updating ? '업데이트 파일의 경로 연결을 반영하고 있습니다…' : '테마를 설치하고 있습니다. 완료될 때까지 기다려 주세요…';
        request('install', {theme: document.getElementById('rb-tp-theme').value, maps: JSON.stringify(maps)})
            .then(function (data) {
                if (data.updating) {
                    details.hidden = true; status.textContent = '업데이트 반영 완료: ' + data.theme + '. 기존 설정을 유지했습니다.';
                    file.remove(file.selectedIndex); file.value = '';
                    if (file.options.length === 1) { root.before(status); root.remove(); }
                }
                else { status.textContent = '설치 완료: ' + data.theme + '. 아래 목록에서 테마적용을 눌러 주세요.'; window.location.href = './theme.php?installed=' + encodeURIComponent(data.theme); }
            })
            .catch(function (e) { status.textContent = e.message + ' 설치 목록을 확인한 뒤 다시 시도해 주세요.'; })
            .finally(function () { working(false); });
    });
}());
