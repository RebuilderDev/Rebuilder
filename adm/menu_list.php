<?php
$sub_menu = "100290";
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

// 메뉴테이블 생성
if (!isset($g5['menu_table'])) {
    die('<meta charset="utf-8">dbconfig.php 파일에 <strong>$g5[\'menu_table\'] = G5_TABLE_PREFIX.\'menu\';</strong> 를 추가해 주세요.');
}

if (!sql_query(" DESCRIBE {$g5['menu_table']} ", false)) {
    sql_query(
        " CREATE TABLE IF NOT EXISTS `{$g5['menu_table']}` (
                  `me_id` int(11) NOT NULL AUTO_INCREMENT,
                  `me_code` varchar(255) NOT NULL DEFAULT '',
                  `me_name` varchar(255) NOT NULL DEFAULT '',
                  `me_link` varchar(255) NOT NULL DEFAULT '',
                  `me_target` varchar(255) NOT NULL DEFAULT '0',
                  `me_order` int(11) NOT NULL DEFAULT '0',
                  `me_use` tinyint(4) NOT NULL DEFAULT '0',
                  `me_mobile_use` tinyint(4) NOT NULL DEFAULT '0',
                  `me_level` tinyint(4) NOT NULL DEFAULT '1',
                  `me_level_opt` tinyint(4) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`me_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ",
        true
    );
}

// // 기존처럼 me_id 정렬 유지(표기 방식 유지)
$sql = " select * from {$g5['menu_table']} order by me_id ";
$result = sql_query($sql);

$g5['title'] = "메뉴설정";
require_once './admin.head.php';

$colspan = 9;
$sub_menu_info = '';
?>

<style>
#menulist tr.menu_list[data-depth="1"] td.td_category {padding-left:25px !important;background-position:5px 15px}
#menulist tr.menu_list[data-depth="2"] td.td_category {padding-left:45px !important;background-position:25px 15px}
#menulist tr.rb-menu-drag-source {display:none !important}
#menulist .menu_move {display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;padding:0;border:0;background:transparent;color:#7b8794;cursor:move;cursor:grab;font-size:17px;touch-action:none}
#menulist .menu_move:active {cursor:grabbing}
#menulist .menu_move:hover,#menulist .menu_move:focus {color:#20252b}
body.rb-menu-drag-active {cursor:grabbing !important;user-select:none !important}
.rb-menu-drag-preview {position:fixed;z-index:999999;width:220px;padding:10px 13px;box-sizing:border-box;background:var(--rb-adm-body-bg,#fff);border:1px solid var(--rb-adm-td-border-color,#d1d6db);border-radius:6px;box-shadow:0 8px 20px rgba(0,0,0,.14);color:var(--rb-adm-input-color,#000);font-family:'font-B',sans-serif;pointer-events:none}
.rb-menu-drop-line {display:none;position:fixed;z-index:999998;height:3px;background:#7699c1;border-radius:2px;pointer-events:none}
#menulist tr.rb-menu-promote-row td {height:48px;padding:0 15px;border:1px dashed #7699c1;background:rgba(118,153,193,.08);color:#7699c1;font-family:'font-B',sans-serif;text-align:center}
#menulist tr.rb-menu-promote-row.active td {background:rgba(118,153,193,.24)}
#menulist tr.rb-menu-drop-inside td {background:rgba(118,153,193,.20) !important}
</style>

<div class="local_desc01 local_desc">
    <p>
    <strong>주의!</strong> 메뉴설정 작업 후 반드시 <strong>확인</strong>을 누르셔야 저장됩니다.<br>
    <strong>이동!</strong> 행 사이의 파란 선은 같은 단계의 순서 변경, 메뉴의 파란 면은 하위 편입입니다. 2·3차 메뉴는 드래그 중 테이블 위·아래에 표시되는 승격 영역에 놓으면 1차 메뉴로 이동합니다.<br>
    <strong>주의!</strong> 짧은 주소를 사용중이신 경우 짧은주소 형식에 맞게 링크를 설정해주세요.<br>
    <strong>예시!</strong> 게시판 : /게시판ID, 상품목록 : /shop/list-카테고리번호, 내용관리 : /content/내용관리ID<br>
    </p>
</div>

<form name="fmenulist" id="fmenulist" method="post" action="./menu_list_update.php" onsubmit="return fmenulist_submit(this);">
    <input type="hidden" name="token" value="">

    <div id="menulist" class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
                <tr>
                    <th scope="col">이동</th>
                    <th scope="col">메뉴</th>
                    <th scope="col">링크</th>
                    <th scope="col">새창</th>
                    <th scope="col">PC사용</th>
                    <th scope="col">모바일사용</th>
                    <th scope="col" colspan="2">권한</th>
                    <th scope="col">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    $bg = 'bg' . ($i % 2);

                    $me_code = isset($row['me_code']) ? trim((string)$row['me_code']) : '';
                    $me_len = strlen($me_code);
                    $menu_depth = $me_len === 6 ? 2 : ($me_len === 4 ? 1 : 0);

                    $sub_menu_class = '';
                    $sub_menu_info = '';
                    $sub_menu_ico = '';

                    // // 2차
                    if ($me_len == 4) {
                        $sub_menu_class = ' sub_menu_class';
                        $sub_menu_info = '<span class="sound_only">' . $row['me_name'] . '의 서브</span>';
                        $sub_menu_ico = '<span class="sub_menu_ico"></span>';
                    }

                    // // 3차
                    if ($me_len == 6) {
                        $sub_menu_class = ' sub_menu_class sub_menu_class_3';
                        $sub_menu_info = '<span class="sound_only">' . $row['me_name'] . '의 서브</span>';
                        $sub_menu_ico = '<span class="sub_menu_ico"></span>';
                    }

                    $search  = array('"', "'");
                    $replace = array('&#034;', '&#039;');
                    $me_name = str_replace($search, $replace, (string)$row['me_name']);
                ?>
                    <tr class="<?php echo $bg; ?> menu_list menu_group_<?php echo substr($me_code, 0, 2); ?>" data-depth="<?php echo $menu_depth; ?>">
                        <td class="td_mng">
                            <input type="hidden" name="me_order[]" value="<?php echo get_sanitize_input((string)$row['me_order']); ?>" id="me_order_<?php echo $i; ?>">
                            <button type="button" class="menu_move" title="드래그하여 메뉴 이동" aria-label="드래그하여 메뉴 이동"><i class="fa fa-arrows" aria-hidden="true"></i></button>
                        </td>
                        <td class="td_category<?php echo $sub_menu_class; ?>">
                            <?php echo $sub_menu_ico; ?>
                            <input type="hidden" name="code[]" value="<?php echo get_sanitize_input($me_code); ?>">
                            <input type="hidden" name="menu_depth[]" value="<?php echo $menu_depth; ?>">
                            <label for="me_name_<?php echo $i; ?>" class="sound_only"><?php echo $sub_menu_info; ?> 메뉴<strong class="sound_only"> 필수</strong></label>
                            <input type="text" name="me_name[]" value="<?php echo get_sanitize_input($me_name); ?>" id="me_name_<?php echo $i; ?>" required class="required tbl_input full_input">
                        </td>
                        <td>
                            <label for="me_link_<?php echo $i; ?>" class="sound_only">링크<strong class="sound_only"> 필수</strong></label>
                            <input type="text" name="me_link[]" value="<?php echo get_sanitize_input((string)$row['me_link']); ?>" id="me_link_<?php echo $i; ?>" required class="required tbl_input full_input">
                        </td>
                        <td class="td_mng">
                            <label for="me_target_<?php echo $i; ?>" class="sound_only">새창</label>
                            <select name="me_target[]" id="me_target_<?php echo $i; ?>">
                                <option value="self" <?php echo get_selected($row['me_target'], 'self', true); ?>>사용안함</option>
                                <option value="blank" <?php echo get_selected($row['me_target'], 'blank', true); ?>>사용함</option>
                            </select>
                        </td>
                        <td class="td_mng">
                            <label for="me_use_<?php echo $i; ?>" class="sound_only">PC사용</label>
                            <select name="me_use[]" id="me_use_<?php echo $i; ?>">
                                <option value="1" <?php echo get_selected($row['me_use'], '1', true); ?>>사용함</option>
                                <option value="0" <?php echo get_selected($row['me_use'], '0', true); ?>>사용안함</option>
                            </select>
                        </td>
                        <td class="td_mng">
                            <label for="me_mobile_use_<?php echo $i; ?>" class="sound_only">모바일사용</label>
                            <select name="me_mobile_use[]" id="me_mobile_use_<?php echo $i; ?>">
                                <option value="1" <?php echo get_selected($row['me_mobile_use'], '1', true); ?>>사용함</option>
                                <option value="0" <?php echo get_selected($row['me_mobile_use'], '0', true); ?>>사용안함</option>
                            </select>
                        </td>
                        <td class="td_num">
                            <label for="me_level_<?php echo $i; ?>" class="sound_only">권한</label>
                            <?php echo get_member_level_select('me_level[]', 1, $member['mb_level'], $row['me_level']); ?>
                        </td>
                        <td class="td_mng" style="min-width:150px;">
                            <label for="me_level_opt_<?php echo $i; ?>" class="sound_only">옵션</label>
                            <select id="me_level_opt_<?php echo $i; ?>" name="me_level_opt[]">
                                <option value="1" <?php if (isset($row['me_level_opt']) && $row['me_level_opt'] == "1") { ?>selected<?php } ?>>레벨 부터 접근가능</option>
                                <option value="2" <?php if (isset($row['me_level_opt']) && $row['me_level_opt'] == "2") { ?>selected<?php } ?>>레벨만 접근가능</option>
                            </select>
                        </td>

                        <td class="td_mng">
                            <?php if ($me_len == 2 || $me_len == 4) { ?>
                                <button type="button" class="btn_add_submenu btn_03 ">추가</button>
                            <?php } ?>
                            <button type="button" class="btn_del_menu btn_02">삭제</button>
                        </td>
                    </tr>
                <?php
                }

                if ($i == 0) {
                    echo '<tr id="empty_menu_list"><td colspan="' . $colspan . '" class="empty_table">자료가 없습니다.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <button type="button" onclick="return add_menu();" class="btn btn_02">메뉴추가<span class="sound_only"> 새창</span></button>
        <input type="submit" name="act_button" value="확인" class="btn_submit btn ">
    </div>

</form>

<script>
var rbMenuDragState = null;
var rbMenuPreview = null;
var rbMenuDropLine = null;
var rbMenuPromoteTopRow = null;
var rbMenuPromoteBottomRow = null;

function rbMenuDepth(row) {
    var depth = parseInt(row && row.getAttribute("data-depth"), 10);
    return isNaN(depth) ? 0 : Math.max(0, Math.min(2, depth));
}

function rbMenuSegment(number) {
    var segment = Math.max(1, number).toString(36);
    return segment.length < 2 ? "0" + segment : segment.slice(-2);
}

function rbMenuNextCode(prefix, used) {
    for (var i = 1; i < 1296; i++) {
        var code = prefix + rbMenuSegment(i);
        if (!used[code]) return code;
    }
    return "";
}

function rbMenuSetDepth(row, depth) {
    depth = Math.max(0, Math.min(2, parseInt(depth, 10) || 0));
    row.setAttribute("data-depth", depth);
    var input = row.querySelector("input[name='menu_depth[]']");
    if (input) input.value = depth;
    var cell = row.querySelector("td.td_category");
    if (cell) {
        cell.classList.toggle("sub_menu_class", depth > 0);
        cell.classList.toggle("sub_menu_class_3", depth === 2);
    }
    var add = row.querySelector(".btn_add_submenu");
    if (!add && depth < 2) {
        var remove = row.querySelector(".btn_del_menu");
        if (remove) {
            add = document.createElement("button");
            add.type = "button";
            add.className = "btn_add_submenu btn_03";
            add.textContent = "추가";
            remove.parentNode.insertBefore(add, remove);
        }
    }
    if (add) add.hidden = depth >= 2;
}

function rbMenuHasParent(row, depth) {
    if (depth === 0) return true;
    var previous = row.previousElementSibling;
    while (previous) {
        if (previous.classList.contains("menu_list")) {
            var previousDepth = rbMenuDepth(previous);
            if (previousDepth < depth) return previousDepth === depth - 1;
        }
        previous = previous.previousElementSibling;
    }
    return false;
}

function rbMenuRefreshStructure() {
    var rows = Array.prototype.slice.call(document.querySelectorAll("#menulist tbody tr.menu_list"));
    var used = {};
    var parentCode = "";
    var secondCode = "";
    var siblingOrders = [0, 0, 0];

    rows.forEach(function(row, index) {
        var depth = rbMenuDepth(row);
        if (index === 0) depth = 0;
        if (depth > 0 && !rbMenuHasParent(row, depth)) depth--;
        if (depth > 0 && !rbMenuHasParent(row, depth)) depth = 0;
        rbMenuSetDepth(row, depth);

        var codeInput = row.querySelector("input[name='code[]']");
        var oldCode = codeInput ? String(codeInput.value || "") : "";
        var preferred = oldCode.length >= 2 ? oldCode.slice(-2) : "";
        var prefix = depth === 0 ? "" : (depth === 1 ? parentCode : secondCode);
        var candidate = /^[0-9a-z]{2}$/i.test(preferred) ? prefix + preferred.toLowerCase() : "";
        if (!candidate || used[candidate]) candidate = rbMenuNextCode(prefix, used);
        if (codeInput) codeInput.value = candidate;
        used[candidate] = true;

        if (depth === 0) {
            parentCode = candidate;
            secondCode = "";
            siblingOrders[0]++;
            siblingOrders[1] = siblingOrders[2] = 0;
        } else if (depth === 1) {
            secondCode = candidate;
            siblingOrders[1]++;
            siblingOrders[2] = 0;
        } else {
            siblingOrders[2]++;
        }

        var orderInput = row.querySelector("input[name='me_order[]']");
        if (orderInput) orderInput.value = (siblingOrders[depth] - 1) * 10;
        row.className = row.className.replace(/\bmenu_group_[0-9a-z]+\b/gi, "").replace(/\bbg[01]\b/g, "").replace(/\s+/g, " ").trim();
        row.classList.add("menu_group_" + candidate.slice(0, 2));
        row.classList.add("bg" + (index % 2));
    });
}

function rbMenuIsDraggedRow(row) {
    return !!(rbMenuDragState && rbMenuDragState.rows.indexOf(row) !== -1);
}

function rbMenuSubtreeRows(row) {
    var depth = rbMenuDepth(row);
    var rows = [row];
    var next = row.nextElementSibling;
    while (next && next.classList.contains("menu_list") && rbMenuDepth(next) > depth) {
        rows.push(next);
        next = next.nextElementSibling;
    }
    return rows;
}

function rbMenuSubtreeBoundary(row, depth) {
    var boundary = row ? row.nextElementSibling : null;
    while (boundary) {
        if (rbMenuIsDraggedRow(boundary)) {
            boundary = boundary.nextElementSibling;
            continue;
        }
        if (boundary.classList.contains("menu_list") && rbMenuDepth(boundary) > depth) {
            boundary = boundary.nextElementSibling;
            continue;
        }
        break;
    }
    return boundary;
}

function rbMenuAncestorAtDepth(row, depth) {
    var current = row;
    while (current) {
        if (!rbMenuIsDraggedRow(current) && current.classList.contains("menu_list") && rbMenuDepth(current) === depth) return current;
        current = current.previousElementSibling;
    }
    return null;
}

function rbMenuSameDepthTarget(row, depth, mode) {
    var rowDepth;
    var current;
    var first = null;
    var last = null;

    if (!row || rbMenuIsDraggedRow(row)) return null;
    rowDepth = rbMenuDepth(row);
    if (rowDepth > depth) return rbMenuAncestorAtDepth(row, depth);
    if (rowDepth === depth) return row;

    current = row.nextElementSibling;
    while (current && current.classList.contains("menu_list") && rbMenuDepth(current) > rowDepth) {
        if (!rbMenuIsDraggedRow(current) && rbMenuDepth(current) === depth) {
            if (!first) first = current;
            last = current;
        }
        current = current.nextElementSibling;
    }
    return mode === "before" ? first : last;
}

function rbMenuLastSameDepthRow(depth) {
    var rows = document.querySelectorAll("#menulist tbody tr.menu_list");
    var last = null;
    Array.prototype.forEach.call(rows, function(row) {
        if (!rbMenuIsDraggedRow(row) && rbMenuDepth(row) === depth) last = row;
    });
    return last;
}

function rbMenuLastVisibleRow() {
    var rows = document.querySelectorAll("#menulist tbody tr.menu_list");
    var last = null;
    Array.prototype.forEach.call(rows, function(row) {
        if (!rbMenuIsDraggedRow(row)) last = row;
    });
    return last;
}

function rbMenuFirstVisibleRow() {
    var rows = document.querySelectorAll("#menulist tbody tr.menu_list");
    for (var i = 0; i < rows.length; i++) {
        if (!rbMenuIsDraggedRow(rows[i])) return rows[i];
    }
    return null;
}

function rbMenuEnsureDragUi() {
    if (!rbMenuPreview) {
        rbMenuPreview = document.createElement("div");
        rbMenuPreview.className = "rb-menu-drag-preview";
        document.body.appendChild(rbMenuPreview);
    }
    if (!rbMenuDropLine) {
        rbMenuDropLine = document.createElement("div");
        rbMenuDropLine.className = "rb-menu-drop-line";
        document.body.appendChild(rbMenuDropLine);
    }
}

function rbMenuCreatePromoteRow(position, text) {
    var row = document.createElement("tr");
    var cell = document.createElement("td");
    var headers = document.querySelectorAll("#menulist thead th");
    var columnCount = 0;
    Array.prototype.forEach.call(headers, function(header) {
        columnCount += parseInt(header.getAttribute("colspan"), 10) || 1;
    });
    if (!columnCount) columnCount = 9;
    row.className = "rb-menu-promote-row rb-menu-promote-" + position;
    row.setAttribute("data-promote-position", position);
    cell.colSpan = columnCount;
    cell.textContent = text;
    row.appendChild(cell);
    return row;
}

function rbMenuShowPromoteRows() {
    var tbody = document.querySelector("#menulist tbody");
    if (!tbody) return;
    rbMenuPromoteTopRow = rbMenuCreatePromoteRow("top", "여기에 놓으면 1차 메뉴 맨 위로 승격됩니다.");
    rbMenuPromoteBottomRow = rbMenuCreatePromoteRow("bottom", "여기에 놓으면 1차 메뉴 맨 아래로 승격됩니다.");
    tbody.insertBefore(rbMenuPromoteTopRow, tbody.firstChild);
    tbody.appendChild(rbMenuPromoteBottomRow);
}

function rbMenuRemovePromoteRows() {
    if (rbMenuPromoteTopRow && rbMenuPromoteTopRow.parentNode) rbMenuPromoteTopRow.parentNode.removeChild(rbMenuPromoteTopRow);
    if (rbMenuPromoteBottomRow && rbMenuPromoteBottomRow.parentNode) rbMenuPromoteBottomRow.parentNode.removeChild(rbMenuPromoteBottomRow);
    rbMenuPromoteTopRow = null;
    rbMenuPromoteBottomRow = null;
}

function rbMenuClearDropVisual() {
    if (rbMenuDragState && rbMenuDragState.insideTarget) {
        rbMenuDragState.insideTarget.classList.remove("rb-menu-drop-inside");
        rbMenuDragState.insideTarget = null;
    }
    if (rbMenuDragState) rbMenuDragState.plan = null;
    if (rbMenuDropLine) rbMenuDropLine.style.display = "none";
    if (rbMenuPromoteTopRow) rbMenuPromoteTopRow.classList.remove("active");
    if (rbMenuPromoteBottomRow) rbMenuPromoteBottomRow.classList.remove("active");
}

function rbMenuPositionPreview(event) {
    if (!rbMenuPreview) return;
    rbMenuPreview.style.left = (event.clientX + 14) + "px";
    rbMenuPreview.style.top = (event.clientY + 14) + "px";
}

function rbMenuShowDropLine(target, mode) {
    var table = document.querySelector("#menulist table");
    var tbody = document.querySelector("#menulist tbody");
    var tableRect;
    var boundary;
    var lastVisible;
    var y;

    if (!rbMenuDropLine || !table || !tbody || !target) return;
    tableRect = table.getBoundingClientRect();
    if (mode === "before") {
        y = target.getBoundingClientRect().top;
    } else {
        boundary = rbMenuSubtreeBoundary(target, rbMenuDepth(target));
        lastVisible = rbMenuLastVisibleRow();
        y = boundary ? boundary.getBoundingClientRect().top : (lastVisible ? lastVisible.getBoundingClientRect().bottom : target.getBoundingClientRect().bottom);
    }
    rbMenuDropLine.style.left = tableRect.left + "px";
    rbMenuDropLine.style.top = (y - 1) + "px";
    rbMenuDropLine.style.width = tableRect.width + "px";
    rbMenuDropLine.style.display = "block";
}

function rbMenuBeginDrag(row, handle, event) {
    var rows;
    var startDepth;
    var maxRelativeDepth = 0;
    var nameInput;

    if (rbMenuDragState) return;
    rows = rbMenuSubtreeRows(row);
    startDepth = rbMenuDepth(row);
    rows.forEach(function(child) {
        maxRelativeDepth = Math.max(maxRelativeDepth, rbMenuDepth(child) - startDepth);
    });

    var tbody = document.querySelector("#menulist tbody");
    var tbodyRect = tbody ? tbody.getBoundingClientRect() : null;
    rbMenuDragState = {
        row: row,
        rows: rows,
        startDepth: startDepth,
        maxRelativeDepth: maxRelativeDepth,
        pointerId: event.pointerId,
        handle: handle,
        plan: null,
        insideTarget: null,
        originalTbodyBottom: tbodyRect ? tbodyRect.bottom : event.clientY
    };

    rbMenuEnsureDragUi();
    rbMenuClearDropVisual();
    nameInput = row.querySelector("input[name='me_name[]']");
    rbMenuPreview.textContent = nameInput && nameInput.value ? nameInput.value : "메뉴";
    rbMenuPreview.style.display = "block";
    if (startDepth > 0) rbMenuShowPromoteRows();
    rbMenuPositionPreview(event);
    rows.forEach(function(child) { child.classList.add("rb-menu-drag-source"); });
    document.body.classList.add("rb-menu-drag-active");
    if (handle.setPointerCapture) {
        try { handle.setPointerCapture(event.pointerId); } catch (ignore) {}
    }
}

function rbMenuDragMove(event) {
    var state = rbMenuDragState;
    var element;
    var row;
    var rect;
    var ratio;
    var mode;
    var target;
    var desiredDepth;
    var table;
    var tbody;
    var tableRect;
    var tbodyRect;
    var bottomLimit;
    var lastVisible;
    var visibleBottom;
    var promoteRow;

    if (!state || event.pointerId !== state.pointerId) return;
    event.preventDefault();
    rbMenuPositionPreview(event);
    if (event.clientY < 70) window.scrollBy(0, -14);
    else if (event.clientY > window.innerHeight - 70) window.scrollBy(0, 14);

    element = document.elementFromPoint(event.clientX, event.clientY);
    rbMenuClearDropVisual();

    promoteRow = element && element.closest ? element.closest("tr.rb-menu-promote-row") : null;
    if (state.startDepth > 0 && promoteRow && promoteRow.closest("#menulist")) {
        promoteRow.classList.add("active");
        state.plan = {mode: promoteRow.getAttribute("data-promote-position") === "top" ? "promote-top" : "promote-bottom", desiredDepth: 0};
        return;
    }

    row = element && element.closest ? element.closest("tr.menu_list") : null;
    if (!row || !row.closest("#menulist") || rbMenuIsDraggedRow(row)) {
        table = document.querySelector("#menulist table");
        tbody = document.querySelector("#menulist tbody");
        if (!table || !tbody) return;
        tableRect = table.getBoundingClientRect();
        tbodyRect = tbody.getBoundingClientRect();
        lastVisible = rbMenuLastVisibleRow();
        visibleBottom = lastVisible ? lastVisible.getBoundingClientRect().bottom : tbodyRect.bottom;
        bottomLimit = Math.max(state.originalTbodyBottom + 60, visibleBottom + 80);
        if (event.clientX >= tableRect.left && event.clientX <= tableRect.right && event.clientY >= visibleBottom - 18 && event.clientY <= bottomLimit) {
            target = rbMenuLastSameDepthRow(state.startDepth);
            if (target) {
                state.plan = {mode: "after", target: target, desiredDepth: state.startDepth};
                rbMenuShowDropLine(target, "after");
            }
        }
        return;
    }
    rect = row.getBoundingClientRect();
    ratio = rect.height ? (event.clientY - rect.top) / rect.height : 0.5;

    /* 편입은 행 중앙의 면에 놓았을 때만 허용한다. */
    if (ratio >= 0.30 && ratio <= 0.70) {
        desiredDepth = rbMenuDepth(row) + 1;
        if (desiredDepth + state.maxRelativeDepth <= 2) {
            row.classList.add("rb-menu-drop-inside");
            state.insideTarget = row;
            state.plan = {mode: "inside", target: row, desiredDepth: desiredDepth};
        }
        return;
    }

    /* 위·아래 선은 시작 단계와 같은 단계의 순서만 바꾼다. */
    mode = ratio < 0.5 ? "before" : "after";
    target = rbMenuSameDepthTarget(row, state.startDepth, mode);
    if (!target) return;
    state.plan = {mode: mode, target: target, desiredDepth: state.startDepth};
    rbMenuShowDropLine(target, mode);
}

function rbMenuFinishDrag(applyPlan) {
    var state = rbMenuDragState;
    var plan;
    var tbody;
    var reference = null;
    var depthDelta;

    if (!state) return;
    plan = applyPlan ? state.plan : null;
    rbMenuClearDropVisual();
    state.rows.forEach(function(row) { row.classList.remove("rb-menu-drag-source"); });

    if (plan) {
        tbody = document.querySelector("#menulist tbody");
        if (plan.mode === "promote-top") {
            reference = rbMenuFirstVisibleRow() || rbMenuPromoteBottomRow;
        } else if (plan.mode === "promote-bottom") {
            reference = rbMenuPromoteBottomRow;
        } else if (plan.mode === "inside") {
            reference = rbMenuSubtreeBoundary(plan.target, rbMenuDepth(plan.target));
        } else if (plan.mode === "before") {
            reference = plan.target;
        } else {
            reference = rbMenuSubtreeBoundary(plan.target, rbMenuDepth(plan.target));
        }

        state.rows.forEach(function(row) {
            if (row.parentNode) row.parentNode.removeChild(row);
        });
        depthDelta = plan.desiredDepth - state.startDepth;
        state.rows.forEach(function(row) {
            rbMenuSetDepth(row, rbMenuDepth(row) + depthDelta);
            tbody.insertBefore(row, reference);
        });
        rbMenuRefreshStructure();
    }

    document.body.classList.remove("rb-menu-drag-active");
    if (rbMenuPreview) rbMenuPreview.style.display = "none";
    if (rbMenuDropLine) rbMenuDropLine.style.display = "none";
    rbMenuRemovePromoteRows();
    if (state.handle && state.handle.releasePointerCapture) {
        try { state.handle.releasePointerCapture(state.pointerId); } catch (ignore) {}
    }
    rbMenuDragState = null;
}

function rbMenuInitDragDrop() {
    document.addEventListener("pointerdown", function(event) {
        var handle = event.target.closest ? event.target.closest(".menu_move") : null;
        var row;
        if (!handle || (event.pointerType === "mouse" && event.button !== 0)) return;
        row = handle.closest("tr.menu_list");
        if (!row) return;
        event.preventDefault();
        rbMenuBeginDrag(row, handle, event);
    }, false);
    document.addEventListener("pointermove", rbMenuDragMove, {passive:false});
    document.addEventListener("pointerup", function(event) {
        if (rbMenuDragState && event.pointerId === rbMenuDragState.pointerId) rbMenuFinishDrag(true);
    }, false);
    document.addEventListener("pointercancel", function(event) {
        if (rbMenuDragState && event.pointerId === rbMenuDragState.pointerId) rbMenuFinishDrag(false);
    }, false);
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape" && rbMenuDragState) rbMenuFinishDrag(false);
    }, false);
    window.addEventListener("blur", function() {
        if (rbMenuDragState) rbMenuFinishDrag(false);
    });
}

$(function() {
    $(document).on("click", ".btn_add_submenu", function() {
        // // 기존 substr(0,2) 제거: 부모코드(2자리/4자리) 그대로 넘김
        var code = String($(this).closest("tr").find("input[name='code[]']").val() || "");
        if (!code) return false;
        add_submenu(code);
    });

    $(document).on("click", ".btn_del_menu", function() {
        if (!confirm("메뉴를 삭제하시겠습니까?\n메뉴 삭제후 메뉴설정의 확인 버튼을 눌러 메뉴를 저장해 주세요."))
            return false;

        var $tr = $(this).closest("tr");
        var code = String($tr.find("input[name='code[]']").val() || "");
        var len = code.length;

        if (!code) {
            $tr.remove();
            rbMenuRefreshStructure();
            return false;
        }

        // // 1차(2) 삭제: 하위 전부(prefix)
        // // 2차(4) 삭제: 하위(3차) 전부(prefix)
        // // 3차(6) 삭제: 자기만
        if (len === 2 || len === 4) {
            $("#menulist tr.menu_list").each(function() {
                var c = String($(this).find("input[name='code[]']").val() || "");
                if (c && c.indexOf(code) === 0) {
                    $(this).remove();
                }
            });
        } else {
            $tr.remove();
        }

        if ($("#menulist tr.menu_list").length < 1) {
            var list = "<tr id=\"empty_menu_list\"><td colspan=\"<?php echo $colspan; ?>\" class=\"empty_table\">자료가 없습니다.</td></tr>\n";
            $("#menulist table tbody").append(list);
        } else {
            rbMenuRefreshStructure();
        }
    });

    rbMenuInitDragDrop();
});

function add_menu() {
    var max_code = base_convert(0, 10, 36);

    $("#menulist tr.menu_list").each(function() {
        var c = String($(this).find("input[name='code[]']").val() || "");
        if (c.length === 2) {
            if (max_code < c) max_code = c;
        }
    });

    var url = "./menu_form.php?code=" + max_code + "&new=new";
    window.open(url, "add_menu", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
    return false;
}

function add_submenu(code) {
    var url = "./menu_form.php?code=" + encodeURIComponent(code);
    window.open(url, "add_menu", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
    return false;
}

function base_convert(number, frombase, tobase) {
    return parseInt(number + '', frombase | 0).toString(tobase | 0);
}

function fmenulist_submit(f) {
    rbMenuRefreshStructure();
    var me_links = document.getElementsByName('me_link[]');
    var reg = /^javascript/;

    for (i = 0; i < me_links.length; i++) {
        if (reg.test(me_links[i].value)) {
            alert('링크에 자바스크립트문을 입력할수 없습니다.');
            me_links[i].focus();
            return false;
        }
    }

    return true;
}
</script>

<?php
require_once './admin.tail.php';
