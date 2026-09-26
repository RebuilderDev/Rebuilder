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
    var installActions = install.parentNode, installHome = installActions.parentNode;
    installActions.classList.add('rb-tp-install-actions');
    var previewSidebarObserver = null;
    var layoutReviews = [];
    var busy = false, optionalConnections = false, moduleConnections = false;
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
            tabs.className = 'rb-tp-tabs'; cell.append(tabs);
        }
        module.slots.forEach(function (slot, slotIndex) {
            var block = document.createElement('div'), label = document.createElement('label'), select = document.createElement('select');
            block.className = 'rb-tp-connection-block';
            if (tabs) {
                var tab = document.createElement('button'); tab.type = 'button';
                tab.id = 'rb-tp-tab-' + index + '-' + slotIndex;
                block.id = tab.id + '-panel'; block.setAttribute('role', 'tabpanel'); block.setAttribute('aria-labelledby', tab.id);
                tab.setAttribute('role', 'tab'); tab.setAttribute('aria-controls', block.id);
                tab.textContent = '탭 ' + slot.tab;
                function activate() {
                    tabPanels.forEach(function (panel, i) {
                        var active = i === slotIndex; panel.hidden = !active;
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
                tab.className = 'btn rb-tp-tab';
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
        var pageFields = Object.create(null), pageFieldIndex = 0;
        data.choices.forEach(function (choice, index) { choices[choice.key] = {module:choice, index:index}; });
        var help = document.createElement('p'); help.id = 'rb-tp-layout-guide'; help.className = 'frm_info';
        help.style.cssText = 'display:block; margin:0 0 16px; padding:0; line-height:1.6;';
        [
            '적용될 테마의 레이아웃입니다.',
            '현재 운영 상황에 맞게 각 영역을 선택하여 연결할 게시판 또는 분류 등을 지정할 수 있습니다.',
            '일반 페이지와 게시판 그룹은 각 레이아웃 위에서 연결할 대상을 선택할 수 있습니다.',
            '지금 연결하지 않아도 테마 설치 후 모듈 설정에서 연결할 수 있습니다.',
            '배너와 위젯은 영역만 표시하며, 실제 콘텐츠와 높이는 표시하지 않습니다.'
        ].forEach(function (line, index) {
            if (index) help.append(document.createElement('br'));
            help.append(document.createTextNode(line));
        });
        var body = document.createElement('div'), canvas = document.createElement('div'), sidebar = document.createElement('div'), editor = document.createElement('aside');
        body.className = 'rb-tp-preview-body';
        canvas.className = 'rb-tp-preview-canvas';
        sidebar.className = 'rb-tp-preview-sidebar';
        editor.className = 'rb-tp-editor';
        editor.setAttribute('aria-label', '선택한 모듈 연결 설정');
        var empty = document.createElement('p'); empty.className = 'frm_info'; empty.style.cssText = 'margin:0; padding:0;';
        empty.textContent = data.choices.length ? '연결할 모듈 박스를 선택하세요.' : '지금 연결할 모듈이 없습니다. 바로 테마를 설치할 수 있습니다.';
        installActions.style.marginTop = '10px';
        editor.append(empty); sidebar.append(editor, installActions); body.append(canvas, sidebar); layouts.append(help, body);
        if (window.ResizeObserver) {
            previewSidebarObserver = new ResizeObserver(function () {
                body.style.setProperty('--rb-tp-editor-height', Math.ceil(sidebar.getBoundingClientRect().height) + 'px');
            });
            previewSidebarObserver.observe(sidebar);
        }
        var layoutTabs = document.createElement('div'), regions = Object.create(null);
        layoutTabs.setAttribute('role', 'tablist'); layoutTabs.setAttribute('aria-label', '레이아웃 구분');
        layoutTabs.className = 'rb-tp-tabs';
        canvas.append(layoutTabs);
        function activateLayout(index) {
            layoutReviews[index].visited = true;
            layoutReviews.forEach(function (review, i) {
                var active = i === index;
                review.panel.hidden = !active;
                review.panel.style.display = active ? 'block' : 'none';
                // 선택 상태만 변경하고 관리자 UI가 추가한 클래스는 유지한다.
                review.button.setAttribute('aria-selected', active ? 'true' : 'false');
                review.button.tabIndex = active ? 0 : -1;
                review.marker.style.display = review.visited ? 'none' : 'inline-block';
            });
            empty.textContent = layoutReviews[index].panel.querySelector('[data-module-key] button[aria-controls]')
                ? '연결할 모듈 박스를 선택하세요.' : '이 페이지에는 연결할 모듈이 없습니다.';
            choose(null);
        }
        function layoutRegion(key, title, connection) {
            if (regions[key]) return regions[key];
            var region = document.createElement('section'), tab = document.createElement('button'), marker = document.createElement('span'), index = layoutReviews.length;
            region.className = 'rb-tp-layout-region';
            tab.type = 'button'; tab.id = 'rb-tp-layout-tab-' + index; tab.textContent = title;
            tab.className = 'btn rb-tp-tab';
            marker.setAttribute('aria-hidden', 'true');
            marker.style.cssText = 'display:inline-block; width:6px; height:6px; margin-left:6px; border-radius:50%; background:#ff4242; vertical-align:middle;';
            tab.append(marker);
            tab.setAttribute('role', 'tab'); tab.setAttribute('aria-controls', tab.id + '-panel');
            region.id = tab.id + '-panel'; region.hidden = true;
            region.setAttribute('role', 'tabpanel'); region.setAttribute('aria-labelledby', tab.id);
            layoutReviews.push({button:tab, panel:region, marker:marker, visited:false});
            tab.addEventListener('click', function () { if (!busy) activateLayout(index); });
            tab.addEventListener('keydown', function (event) {
                if (busy) return;
                var next = index;
                if (event.key === 'ArrowRight') next = (index + 1) % layoutReviews.length;
                else if (event.key === 'ArrowLeft') next = (index + layoutReviews.length - 1) % layoutReviews.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = layoutReviews.length - 1;
                else return;
                event.preventDefault(); activateLayout(next); layoutReviews[next].button.focus();
            });
            if (connection) {
                var layoutHeader = document.createElement('div');
                layoutHeader.style.cssText = 'display:flex; justify-content:flex-start; margin-bottom:14px;';
                pageConnection(layoutHeader, connection); region.append(layoutHeader);
            }
            regions[key] = region; layoutTabs.append(tab); canvas.append(region); return region;
        }
        function pageConnection(region, connection) {
            if (!connection || ['group', 'content'].indexOf(connection.kind) === -1) return;
            var kind = connection.kind, source = connection.source, key = kind + ':' + source;
            var options = (data.page_catalogs || {})[kind] || {};
            var block = document.createElement('div'), label = document.createElement('label'), select = document.createElement('select');
            block.className = 'rb-tp-page-connection';
            select.id = 'rb-tp-page-' + pageFieldIndex++;
            select.dataset.pageKind = kind; select.dataset.pageSource = source;
            label.htmlFor = select.id;
            label.style.cssText = 'font-weight:normal; white-space:nowrap;';
            label.textContent = (kind === 'group' ? '연결할 그룹' : '연결할 일반 페이지') + ' (선택)';
            select.setAttribute('aria-describedby', 'rb-tp-layout-guide');
            select.add(new Option('선택 안 함 · 원본 ID 유지 (' + source + ')', ''));
            Object.keys(options).forEach(function (id) { select.add(new Option(options[id] + ' (' + id + ')', id)); });
            if (!pageFields[key]) pageFields[key] = [];
            pageFields[key].push(select);
            select.addEventListener('change', function () {
                // 같은 원본 페이지의 다른 레이아웃과 마켓 배치도 동일한 대상에 연결한다.
                pageFields[key].forEach(function (field) { field.value = select.value; });
            });
            block.append(label, select); region.append(block);
        }
        function choose(key) {
            if (busy) return;
            empty.hidden = key !== null; empty.style.display = key === null ? '' : 'none';
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
            wrap.className = 'rb-tp-module-wrap';
            wrap.style.setProperty('--rb-tp-module-width', width + '%');
            box.style.cssText = 'height:100%; box-sizing:border-box; border:2px solid transparent; border-radius:10px; background:#fff; cursor:default;';
            wrap.dataset.moduleKey = node.key;
            var entry = choices[node.key], button = document.createElement(entry && !panels[node.key] ? 'button' : 'div');
            button.className = 'rb-tp-module-label';
            var name = document.createElement('strong');
            name.style.cssText = 'display:block; min-width:0; line-height:1.5; color:inherit; word-break:keep-all; overflow-wrap:break-word;';
            name.textContent = moduleTitle(node); button.append(name);
            if (entry && !panels[node.key]) {
                button.type = 'button'; button.classList.add('rb-tp-module-button'); button.setAttribute('aria-pressed', 'false');
                var panel = document.createElement('div'); panel.hidden = true;
                panel.id = 'rb-tp-editor-' + entry.index; button.setAttribute('aria-controls', panel.id);
                var heading = document.createElement('div'); heading.style.marginBottom = '20px'; moduleLabel(heading, entry.module);
                panel.append(heading); connectionFields(panel, entry.module, entry.index, data.board_categories || {});
                panels[node.key] = panel; buttons[node.key] = button; editor.append(panel);
                function refresh() {
                    var count = Array.prototype.filter.call(panel.querySelectorAll('select[data-connection]'), function (select) { return !!select.value; }).length;
                    name.style.color = count ? '#3f72d4' : 'inherit';
                }
                panel.addEventListener('change', refresh); refresh();
                box.style.cursor = 'pointer';
                box.addEventListener('click', function (event) {
                    // 박스의 빈 공간도 선택하되, 중첩된 다른 모듈의 클릭은 처리하지 않는다.
                    if (event.target.closest('[data-module-key]') !== wrap) return;
                    choose(node.key);
                });
            } else {
                // 중첩된 연결 가능 모듈은 흐려지지 않도록 이 영역의 표시만 반투명하게 한다.
                box.style.background = 'rgba(255,255,255,0.45)';
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
            area.layouts.forEach(function (layout) {
                var region = layoutRegion((area.shop ? 'shop:' : 'general:') + layout.position,
                    layout.title || (area.shop ? '마켓 (메인)' : '일반 (메인)'), layout.connection);
                var position = document.createElement(layout.active ? 'div' : 'details');
                position.className = 'rb-tp-layout-position';
                if (!layout.active) {
                    var label = document.createElement('summary');
                    label.style.cssText = 'margin-bottom:14px; line-height:1.5; cursor:pointer;';
                    label.textContent = '다른 레이아웃 · ' + (layout.name || '이름 없음'); position.append(label);
                }
                position.append(grid(layout.nodes, area.width, true)); region.append(position);
            });
        });
        var remaining = data.choices.filter(function (choice) { return !panels[choice.key]; });
        if (remaining.length) {
            var extra = layoutRegion('other', '기타 배치', null);
            extra.append(grid(remaining.map(function (choice) {
                return {kind:'module',key:choice.key,id:choice.id,title:choice.title,type:choice.type,width:100,unit:'%',children:[]};
            }), 1280));
        }
        if (layoutReviews.length) activateLayout(0);
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
    function showError(message) {
        if (message.indexOf('빌더 DB 업데이트가 필요합니다') === 0) message = '빌더 DB 업데이트가 필요합니다.';
        status.hidden = false; status.textContent = message; window.alert(message);
    }
    file.addEventListener('change', function () { details.hidden = true; status.textContent = ''; status.hidden = true; });
    check.addEventListener('click', function () {
        if (root.dataset.installVersionError) { window.alert(root.dataset.installVersionError); return; }
        if (busy || !file.value) { if (!file.value) showError('FTP로 올린 테마 폴더를 선택해 주세요.'); return; }
        working(true); details.hidden = true; status.textContent = '테마 파일과 모듈을 확인하고 있습니다…';
        request('inspect').then(function (data) {
            document.getElementById('rb-tp-name').textContent = data.name;
            document.getElementById('rb-tp-theme').value = data.theme;
            optionalConnections = data.optional_connections === true;
            moduleConnections = data.module_connections === true;
            document.getElementById('rb-tp-install-guide').textContent = '현재 업로드된 폴더명을 기준으로 설치합니다. 폴더명을 바꾸셨다면 새로고침 후 다시 선택하세요.';
            var hasPageConnections = Object.keys(data.page_catalogs || {}).length > 0;
            document.getElementById('rb-tp-shop-guide').hidden = data.shop_enabled !== false;
            mappings.replaceChildren();
            layoutReviews = [];
            if (previewSidebarObserver) { previewSidebarObserver.disconnect(); previewSidebarObserver = null; }
            // 확인을 반복하거나 이전 배포 자료로 바꿔도 동일한 설치 버튼과 이벤트를 유지한다.
            installHome.append(installActions); installActions.style.marginTop = '20px';
            layouts.replaceChildren(); layouts.hidden = true;
            var labels = {board: '게시판', content: '내용 페이지', form: '폼', poll: '설문', category: '상품 분류', group: '게시판 그룹', item: '상품', event: '이벤트'};
            var currentArea = '';
            var visual = moduleConnections && Array.isArray(data.layout_preview);
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
            details.hidden = false; status.textContent = layoutReviews.length ? '각 페이지 탭을 확인한 뒤 테마 설치를 눌러 주세요. 연결 설정은 선택 사항입니다.'
                : (data.choices.length || hasPageConnections ? (optionalConnections ? '필요한 배치·모듈만 연결하거나, 바로 테마 설치를 눌러 주세요.' : '이전 배포 자료의 연결 정보를 확인한 뒤 설치해 주세요.') : '테마 자료를 확인했습니다. 테마 설치를 눌러 주세요.');
        }).catch(function (e) { showError(e.message); }).finally(function () { working(false); });
    });
    install.addEventListener('click', async function () {
        if (root.dataset.installVersionError) { window.alert(root.dataset.installVersionError); return; }
        if (busy) return;
        var unreviewed = layoutReviews.filter(function (review) { return !review.visited; });
        if (unreviewed.length) {
            showError('확인하지않은 페이지가 있습니다.\n각 페이지를 확인해주세요.'); unreviewed[0].button.focus(); return;
        }
        var maps = moduleConnections ? {modules:{}} : {}, missing = false;
        if (moduleConnections) {
            details.querySelectorAll('select[data-page-kind]').forEach(function (select) {
                if (!maps[select.dataset.pageKind]) maps[select.dataset.pageKind] = Object.create(null);
                maps[select.dataset.pageKind][select.dataset.pageSource] = select.value;
            });
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
        if (missing) { showError('연결할 항목을 모두 선택해 주세요.'); return; }
        working(true);
        try {
            if (!await window.rb_confirm('테마를 설치하시겠습니까?\n테마 설치후 모듈설정에서 추가 매핑(연결)이 가능합니다.')) return;
            status.textContent = '테마를 설치하고 있습니다. 완료될 때까지 기다려 주세요…';
            var data = await request('install', {theme: document.getElementById('rb-tp-theme').value, maps: JSON.stringify(maps)});
            status.textContent = '설치 완료: ' + data.theme + '. 아래 목록에서 테마적용을 눌러 주세요.'; window.location.href = './theme.php?installed=' + encodeURIComponent(data.theme);
        } catch (e) {
            showError(e.message + ' 설치 목록을 확인한 뒤 다시 시도해 주세요.');
        } finally {
            working(false);
        }
    });
}());
