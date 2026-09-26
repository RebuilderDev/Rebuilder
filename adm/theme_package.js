(function () {
    'use strict';
    var root = document.getElementById('rb-theme-install');
    if (!root) return;
    var file = document.getElementById('rb-tp-file');
    var status = document.getElementById('rb-tp-status');
    var details = document.getElementById('rb-tp-details');
    var mappings = document.getElementById('rb-tp-maps');
    var layouts = document.getElementById('rb-tp-layouts');
    var check = document.getElementById('rb-tp-check');
    var install = document.getElementById('rb-tp-install');
    var busy = false, updating = false, optionalConnections = false, moduleConnections = false;
    function moduleTitle(module) {
        var type = (module.type || '모듈').replace('(단일)', '').replace('(탭)', ' 탭');
        return module.title ? module.title + ' (' + type + ')' : type;
    }
    function moduleLabel(label, module) {
        var title = document.createElement('b');
        title.style.cssText = 'display:block; line-height:1.5; color:inherit;';
        title.textContent = moduleTitle(module); label.append(title);
    }
    function connectionFields(cell, module, index, categories) {
        cell.style.overflowX = 'auto';
        var tabs = null, tabPanels = [], tabButtons = [];
        if (module.slots.some(function (slot) { return slot.tab; })) {
            tabs = document.createElement('div'); tabs.setAttribute('role', 'tablist');
            tabs.setAttribute('aria-label', '모듈 탭 연결');
            tabs.style.cssText = 'display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;'; cell.append(tabs);
        }
        module.slots.forEach(function (slot, slotIndex) {
            var block = document.createElement('div'), label = document.createElement('label'), select = document.createElement('select');
            block.style.cssText = 'padding:16px; background:#f0f5f9;';
            if (tabs) {
                var tab = document.createElement('button'); tab.type = 'button';
                tab.id = 'rb-tp-tab-' + index + '-' + slotIndex;
                block.id = tab.id + '-panel'; block.setAttribute('role', 'tabpanel'); block.setAttribute('aria-labelledby', tab.id);
                tab.setAttribute('role', 'tab'); tab.setAttribute('aria-controls', block.id);
                tab.textContent = '탭 ' + slot.tab;
                function activate() {
                    tabPanels.forEach(function (panel, i) {
                        var active = i === slotIndex; panel.hidden = !active;
                        tabButtons[i].className = active ? 'btn btn_03' : 'btn btn_02';
                        tabButtons[i].setAttribute('aria-selected', active ? 'true' : 'false');
                        tabButtons[i].tabIndex = active ? 0 : -1;
                    });
                }
                tab.addEventListener('click', activate);
                tab.addEventListener('keydown', function (event) {
                    var next = slotIndex;
                    if (event.key === 'ArrowRight') next = (slotIndex + 1) % tabButtons.length;
                    else if (event.key === 'ArrowLeft') next = (slotIndex + tabButtons.length - 1) % tabButtons.length;
                    else if (event.key === 'Home') next = 0;
                    else if (event.key === 'End') next = tabButtons.length - 1;
                    else return;
                    event.preventDefault(); tabButtons[next].click(); tabButtons[next].focus();
                });
                tabPanels.push(block); tabButtons.push(tab); tabs.append(tab);
                block.hidden = slotIndex !== 0;
                tab.className = slotIndex === 0 ? 'btn btn_03' : 'btn btn_02';
                tab.setAttribute('aria-selected', slotIndex === 0 ? 'true' : 'false'); tab.tabIndex = slotIndex === 0 ? 0 : -1;
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
    }
    function connectionRow(module, index, categories) {
        var row = document.createElement('tr'), heading = document.createElement('th'), cell = document.createElement('td');
        heading.scope = 'row'; moduleLabel(heading, module);
        connectionFields(cell, module, index, categories);
        row.append(heading, cell); return row;
    }
    function renderLayoutPreview(data) {
        var choices = Object.create(null), panels = Object.create(null), buttons = Object.create(null);
        data.choices.forEach(function (choice, index) { choices[choice.key] = {module:choice, index:index}; });
        var help = document.createElement('p'); help.className = 'frm_info';
        help.style.cssText = 'display:block; margin:0 0 16px; padding:0; line-height:1.6;';
        [
            '적용될 테마의 레이아웃입니다.',
            '현재 운영 상황에 맞게 각 영역을 선택하여 연결할 게시판 또는 분류 등을 지정할 수 있습니다.',
            '지금 연결하지 않아도 테마 설치 후 모듈 설정에서 연결할 수 있습니다.',
            '배너와 위젯은 영역만 표시하며, 실제 콘텐츠와 높이는 표시하지 않습니다.'
        ].forEach(function (line, index) {
            if (index) help.append(document.createElement('br'));
            help.append(document.createTextNode(line));
        });
        var body = document.createElement('div'), canvas = document.createElement('div'), editor = document.createElement('aside');
        body.style.cssText = 'display:flex; flex-wrap:wrap; gap:24px; align-items:flex-start;';
        canvas.style.cssText = 'flex:1 1 600px; min-width:0; overflow-x:auto;';
        editor.style.cssText = 'flex:0 1 340px; min-width:0; max-width:100%; box-sizing:border-box; position:sticky; top:120px; padding:20px; border:1px solid #d6dce1; background:#fff;';
        editor.setAttribute('aria-label', '선택한 모듈 연결 설정');
        var empty = document.createElement('p'); empty.className = 'frm_info'; empty.style.cssText = 'margin:0; padding:0;';
        empty.textContent = data.choices.length ? '연결할 모듈 박스를 선택하세요.' : '지금 연결할 모듈이 없습니다. 바로 테마를 설치할 수 있습니다.';
        editor.append(empty); body.append(canvas, editor); layouts.append(help, body);
        function choose(key) {
            if (busy) return;
            empty.hidden = true; empty.style.display = 'none';
            Object.keys(panels).forEach(function (id) {
                var active = id === key; panels[id].hidden = !active;
                buttons[id].setAttribute('aria-pressed', active ? 'true' : 'false');
                buttons[id].parentNode.style.borderColor = active ? '#3f72d4' : 'transparent';
            });
        }
        function moduleBox(node, parentWidth) {
            var width = node.unit === 'px' ? node.width / parentWidth * 100 : node.width;
            width = Math.max(0.1, Math.min(100, width));
            var wrap = document.createElement('div'), box = document.createElement('div');
            wrap.style.cssText = 'flex:0 0 ' + width + '%; max-width:' + width + '%; min-width:0; padding:8px; box-sizing:border-box;';
            box.style.cssText = 'height:100%; box-sizing:border-box; border:2px solid transparent; border-radius:10px; background:#fff;';
            wrap.dataset.moduleKey = node.key;
            var entry = choices[node.key], button = document.createElement(entry && !panels[node.key] ? 'button' : 'div');
            button.style.cssText = 'display:flex; align-items:center; width:100%; min-width:0; min-height:100px; box-sizing:border-box; padding:20px; border:0; border-radius:10px; background:#fff; color:#344054; text-align:left; overflow-wrap:anywhere; font:inherit;';
            var name = document.createElement('strong');
            name.style.cssText = 'display:block; line-height:1.5; color:inherit;';
            name.textContent = moduleTitle(node); button.append(name);
            if (entry && !panels[node.key]) {
                button.type = 'button'; button.style.cursor = 'pointer'; button.setAttribute('aria-pressed', 'false');
                var panel = document.createElement('div'); panel.hidden = true;
                panel.id = 'rb-tp-editor-' + entry.index; button.setAttribute('aria-controls', panel.id);
                var heading = document.createElement('div'); heading.style.marginBottom = '20px'; moduleLabel(heading, entry.module);
                panel.append(heading); connectionFields(panel, entry.module, entry.index, data.board_categories || {});
                panels[node.key] = panel; buttons[node.key] = button; editor.append(panel);
                function refresh() {
                    var count = Array.prototype.filter.call(panel.querySelectorAll('select[data-connection]'), function (select) { return !!select.value; }).length;
                    name.style.color = count ? '#247044' : 'inherit';
                }
                panel.addEventListener('change', refresh); refresh();
                button.addEventListener('click', function () { choose(node.key); });
            }
            box.append(button);
            if (node.children && node.children.length) {
                var nested = document.createElement('div'); nested.style.cssText = 'margin:0 8px 8px; padding:8px; border-radius:10px; background:#f0f5f9;';
                nested.append(grid(node.children, parentWidth * width / 100)); box.append(nested);
            }
            wrap.append(box); return wrap;
        }
        function grid(nodes, parentWidth, alignSections) {
            var row = document.createElement('div'); row.style.cssText = 'display:flex; flex-wrap:wrap; align-items:stretch; margin:-8px;';
            var outside = [];
            function appendOutside() {
                if (!outside.length) return;
                var group = document.createElement('div');
                group.style.cssText = 'flex:0 0 100%; min-width:0; padding:8px 28px; box-sizing:border-box;';
                group.append(grid(outside, parentWidth)); row.append(group); outside = [];
            }
            nodes.forEach(function (node) {
                if (node.kind !== 'section') {
                    if (alignSections) outside.push(node);
                    else row.append(moduleBox(node, parentWidth));
                    return;
                }
                appendOutside();
                var outer = document.createElement('div'), section = document.createElement('section'), title = document.createElement('strong');
                outer.style.cssText = 'flex:0 0 100%; min-width:0; padding:8px; box-sizing:border-box;';
                outer.dataset.sectionId = node.id;
                section.style.cssText = 'padding:20px; border:0; border-radius:0; background:#f0f5f9;';
                title.style.cssText = 'display:block; margin-bottom:16px; line-height:1.5; overflow-wrap:anywhere; color:inherit;';
                title.textContent = node.title ? node.title + ' (섹션)' : '섹션';
                section.append(title, grid(node.children || [], parentWidth)); outer.append(section); row.append(outer);
            });
            appendOutside();
            return row;
        }
        data.layout_preview.forEach(function (area) {
            var region = document.createElement('section');
            region.style.cssText = 'margin-bottom:28px; min-width:640px;';
            area.layouts.forEach(function (layout) {
                var heading = document.createElement('h3');
                heading.style.cssText = 'margin:0 0 14px; padding:0; font-size:14px; font-weight:600; line-height:1.5; color:#344054;';
                heading.textContent = layout.title || (area.shop ? '마켓 (메인)' : '일반 (메인)');
                region.append(heading);
                var position = document.createElement(layout.active ? 'div' : 'details');
                position.style.cssText = 'margin-bottom:20px; padding:20px; background:#f0f5f9; border:0; border-radius:10px;';
                if (!layout.active) {
                    var label = document.createElement('summary');
                    label.style.cssText = 'margin-bottom:14px; line-height:1.5; cursor:pointer;';
                    label.textContent = '다른 레이아웃 · ' + (layout.name || '이름 없음'); position.append(label);
                }
                position.append(grid(layout.nodes, area.width, true)); region.append(position);
            });
            canvas.append(region);
        });
        var remaining = data.choices.filter(function (choice) { return !panels[choice.key]; });
        if (remaining.length) {
            var extra = document.createElement('div'), extraTitle = document.createElement('strong');
            extraTitle.textContent = '기타 배치'; extraTitle.style.cssText = 'display:block; margin:12px 0;';
            extra.append(extraTitle, grid(remaining.map(function (choice) {
                return {kind:'module',key:choice.key,id:choice.id,title:choice.title,type:choice.type,width:100,unit:'%',children:[]};
            }), 1280)); canvas.append(extra);
        }
        layouts.hidden = false;
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
        working(true); details.hidden = true; status.textContent = '테마 파일과 모듈을 확인하고 있습니다…';
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
            layouts.replaceChildren(); layouts.hidden = true;
            var labels = {board: '게시판', content: '내용 페이지', form: '폼', poll: '설문', category: '상품 분류', group: '게시판 그룹', item: '상품', event: '이벤트'};
            var currentArea = '';
            var visual = !updating && moduleConnections && Array.isArray(data.layout_preview);
            if (visual) renderLayoutPreview(data);
            (visual ? [] : data.choices).forEach(function (choice, index) {
                if (moduleConnections) {
                    if (currentArea !== choice.area) {
                        var areaRow = document.createElement('tr'), areaHeading = document.createElement('th');
                        areaHeading.colSpan = 2; areaHeading.scope = 'colgroup';
                        areaHeading.style.cssText = 'background:#f0f5f9; padding:16px;';
                        areaHeading.textContent = choice.area.replace('쇼핑몰', '마켓') + ' 모듈'; areaRow.append(areaHeading); mappings.append(areaRow);
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
                    location.textContent = module.area.replace('쇼핑몰', '마켓') + ' · ' + module.type;
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
                : (data.choices.length ? (optionalConnections ? '필요한 모듈만 연결하거나, 바로 테마 설치를 눌러 주세요.' : '이전 배포 자료의 연결 정보를 확인한 뒤 설치해 주세요.') : '테마 자료를 확인했습니다. 테마 설치를 눌러 주세요.');
        }).catch(function (e) { status.textContent = e.message; }).finally(function () { working(false); });
    });
    install.addEventListener('click', function () {
        if (root.dataset.installVersionError) { window.alert(root.dataset.installVersionError); return; }
        if (busy) return;
        var maps = moduleConnections ? {modules:{}} : {}, missing = false;
        if (moduleConnections) {
            details.querySelectorAll('select[data-connection]').forEach(function (select) {
                maps.modules[select.dataset.connection] = {target:select.value, category:''};
            });
            details.querySelectorAll('select[data-category-for]').forEach(function (select) {
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
