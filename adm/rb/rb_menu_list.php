<?php
$sub_menu = "000290";
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

/* =======================
 *  테이블 생성 (없으면)
 * ======================= */

// 메뉴 세트 테이블
if (!sql_query(" DESCRIBE {$rb['menu_set_table']} ", false)) {
    sql_query("
        CREATE TABLE IF NOT EXISTS `{$rb['menu_set_table']}` (
            `ms_id` int(11) NOT NULL AUTO_INCREMENT,
            `ms_name` varchar(255) NOT NULL DEFAULT '',
            `ms_is_default` tinyint(4) NOT NULL DEFAULT '0',
            `ms_order` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`ms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
    ", true);
}

// 메뉴 항목 테이블
if (!sql_query(" DESCRIBE {$rb['menu_table']} ", false)) {
    sql_query("
        CREATE TABLE IF NOT EXISTS `{$rb['menu_table']}` (
            `me_id` int(11) NOT NULL AUTO_INCREMENT,
            `ms_id` int(11) NOT NULL DEFAULT '0',
            `me_code` varchar(255) NOT NULL DEFAULT '',
            `me_name` varchar(255) NOT NULL DEFAULT '',
            `me_link` varchar(255) NOT NULL DEFAULT '',
            `me_target` varchar(255) NOT NULL DEFAULT '',
            `me_order` int(11) NOT NULL DEFAULT '0',
            `me_use` tinyint(4) NOT NULL DEFAULT '0',
            `me_mobile_use` tinyint(4) NOT NULL DEFAULT '0',
            `me_level` tinyint(4) NOT NULL DEFAULT '1',
            `me_level_opt` tinyint(4) NOT NULL DEFAULT '1',
            PRIMARY KEY (`me_id`),
            KEY `ms_id` (`ms_id`),
            KEY `me_code` (`me_code`),
            KEY `me_order` (`me_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
    ", true);
}

// 리빌더 설정 테이블에 메뉴세트를 담을 필드 추가
$col = sql_fetch("SHOW COLUMNS FROM `rb_config` LIKE 'co_menu_set' ");
if (!isset($col['Field']) || $col['Field'] !== 'co_menu_set') {
    sql_query("
        ALTER TABLE `rb_config`
        ADD `co_menu_set` int(11) NOT NULL DEFAULT '0' COMMENT '메뉴 세트 ID'
    ", true);
}

/* ======================================
 *  최초 1회: 기본 세트 생성 + g5_menu 복사
 * ====================================== */

// 원본 그누보드 메뉴 테이블 이름 (없으면 기본값)
if (!isset($g5['menu_table'])) {
    $g5['menu_table'] = G5_TABLE_PREFIX.'menu';
}

$_row = sql_fetch(" select count(*) as cnt from {$rb['menu_set_table']} ");
if (!(int)$_row['cnt']) {
    // 1) 기본 메뉴 세트 생성
    sql_query("
        insert into {$rb['menu_set_table']}
            set ms_name = '기본 메뉴',
                ms_is_default = '1',
                ms_order = 10
    ");
    $_default_ms_id = sql_insert_id();

    // 2) 원본 g5_menu가 존재하면 내용 복사
    if (isset($g5['menu_table']) && sql_query(" DESCRIBE {$g5['menu_table']} ", false)) {

        $_org_cnt = sql_fetch(" select count(*) as cnt from {$g5['menu_table']} ");
        if ((int)$_org_cnt['cnt'] > 0) {
            $_src_res = sql_query(" select * from {$g5['menu_table']} order by me_id ");
            while ($_src = sql_fetch_array($_src_res)) {

                $me_code       = sql_real_escape_string($_src['me_code']);
                $me_name       = sql_real_escape_string($_src['me_name']);
                $me_link       = sql_real_escape_string($_src['me_link']);
                $me_target     = sql_real_escape_string($_src['me_target']);
                $me_order      = (int)$_src['me_order'];
                $me_use        = (int)$_src['me_use'];
                $me_mobile_use = (int)$_src['me_mobile_use'];
                $me_level      = (int)$_src['me_level'];
                $me_level_opt  = isset($_src['me_level_opt']) ? (int)$_src['me_level_opt'] : 1;

                sql_query("
                    insert into {$rb['menu_table']}
                        set ms_id          = '{$_default_ms_id}',
                            me_code        = '{$me_code}',
                            me_name        = '{$me_name}',
                            me_link        = '{$me_link}',
                            me_target      = '{$me_target}',
                            me_order       = '{$me_order}',
                            me_use         = '{$me_use}',
                            me_mobile_use  = '{$me_mobile_use}',
                            me_level       = '{$me_level}',
                            me_level_opt   = '{$me_level_opt}'
                ");
            }
        }
    }
}

/* ==============================
 *  메뉴 세트 POST 액션 처리부
 * ============================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_act']) && $_POST['set_act']) {

    check_demo();

    $set_act     = $_POST['set_act'];
    $_rb_ms_id   = isset($_POST['ms_id']) ? (int) $_POST['ms_id'] : 0;
    $_rb_ms_name = isset($_POST['ms_name']) ? trim($_POST['ms_name']) : '';

    if ($set_act === 'add') {

        if ($_rb_ms_name === '') {
            alert('메뉴 세트 이름을 입력해 주세요.', './rb_menu_list.php');
        }

        $_rb_ms_name = strip_tags($_rb_ms_name);
        $_rb_ms_name = sql_real_escape_string($_rb_ms_name);

        $_order_row = sql_fetch(" select max(ms_order) as max_order from {$rb['menu_set_table']} ");
        $_order     = (int) $_order_row['max_order'] + 10;

        sql_query("
            insert into {$rb['menu_set_table']}
                set ms_name = '{$_rb_ms_name}',
                    ms_order = '{$_order}'
        ");

        $_new_id = sql_insert_id();
        goto_url('./rb_menu_list.php?ms_id='.$_new_id);

    } elseif ($set_act === 'rename') {

        if (!$_rb_ms_id) {
            alert('잘못된 메뉴 세트입니다.', './rb_menu_list.php');
        }

        if ($_rb_ms_name === '') {
            alert('메뉴 세트 이름을 입력해 주세요.', './rb_menu_list.php?ms_id='.$_rb_ms_id);
        }

        $_rb_ms_name = strip_tags($_rb_ms_name);
        $_rb_ms_name = sql_real_escape_string($_rb_ms_name);

        sql_query("
            update {$rb['menu_set_table']}
               set ms_name = '{$_rb_ms_name}'
             where ms_id   = '{$_rb_ms_id}'
        ");

        goto_url('./rb_menu_list.php?ms_id='.$_rb_ms_id);

    } elseif ($set_act === 'delete') {

        if (!$_rb_ms_id) {
            alert('잘못된 메뉴 세트입니다.', './rb_menu_list.php');
        }

        $_cnt = sql_fetch(" select count(*) as cnt from {$rb['menu_set_table']} ");
        if ((int)$_cnt['cnt'] <= 1) {
            alert('최소 1개의 메뉴 세트가 필요합니다. 삭제할 수 없습니다.', './rb_menu_list.php?ms_id='.$_rb_ms_id);
        }

        // 해당 세트의 메뉴 삭제
        sql_query(" delete from {$rb['menu_table']} where ms_id = '{$_rb_ms_id}' ");

        // 세트 삭제
        sql_query(" delete from {$rb['menu_set_table']} where ms_id = '{$_rb_ms_id}' ");

        // 남은 세트 중 하나로 이동
        $_next = sql_fetch(" select ms_id from {$rb['menu_set_table']} order by ms_order, ms_id limit 1 ");
        $_next_id = isset($_next['ms_id']) ? $_next['ms_id'] : 0;

        goto_url('./rb_menu_list.php?ms_id='.$_next_id);
    }
}

/* ==============================
 *  화면에 뿌릴 데이터 준비
 * ============================== */

// 현재 선택된 메뉴 세트 (GET 우선) - 다른 include와 충돌 피하기 위해 별도 변수 사용
$_rb_ms_id = isset($_GET['ms_id']) ? (int) $_GET['ms_id'] : 0;

// GET으로 들어온 ms_id가 실제 존재하는지 확인
if ($_rb_ms_id) {
    $_chk = sql_fetch(" select ms_id from {$rb['menu_set_table']} where ms_id = '{$_rb_ms_id}' ");
    if (!isset($_chk['ms_id']) || !$_chk['ms_id']) {
        $_rb_ms_id = 0;
    }
}

// 유효한 ms_id가 없으면 기본 세트로 대체
if (!$_rb_ms_id) {
    $_def = sql_fetch("
        select ms_id
          from {$rb['menu_set_table']}
         order by ms_is_default desc, ms_order, ms_id
         limit 1
    ");
    $_rb_ms_id = isset($_def['ms_id']) ? (int)$_def['ms_id'] : 0;
}

// 전체 메뉴 세트 목록
$_set_result = sql_query("
    select *
      from {$rb['menu_set_table']}
     order by ms_order, ms_id
");

// 선택된 세트의 메뉴 목록
$_sql    = " select * from {$rb['menu_table']} where ms_id = '{$_rb_ms_id}' order by me_id ";
$_result = sql_query($_sql);

/* ==============================
 *  화면 출력
 * ============================== */

$g5['title'] = "메뉴설정";
require_once '../admin.head.php';

$colspan       = 9;
$sub_menu_info = '';
?>

<style>
/* 3차 전용 추가 스타일 */
#menulist .sub_menu_class.depth3 { padding-left: 45px; background-position: 25px 15px; }
button.btn_add_submenu3 {background: #ff3061;}
</style>

<div class="local_desc01 local_desc">
    <p>
        <strong>주의!</strong> 메뉴설정 작업 후 반드시 <strong>확인</strong>을 누르셔야 저장됩니다.<br>
        <strong>주의!</strong> 메뉴 세트를 변경하면 아래 목록이 해당 세트의 메뉴로 바뀝니다.<br>
        <strong>예시!</strong> 게시판 : /게시판ID, 상품목록 : /shop/list-카테고리번호, 내용관리 : /content/내용관리ID<br>
    </p>
</div>

<!-- 메뉴 세트 선택 / 관리 -->
<div class="local_ov01 local_ov">
    <form id="frm_menu_set_select" method="get">
        <label for="rb_ms_id_select">메뉴 세트</label>
        <select name="ms_id" id="rb_ms_id_select" onchange="this.form.submit();">
            <?php
            for ($i = 0; $_set = sql_fetch_array($_set_result); $i++) {
                $_option_ms_id = (int)$_set['ms_id'];
                $_selected = ($_option_ms_id === (int)$_rb_ms_id) ? ' selected="selected"' : '';
            ?>
                <option value="<?php echo $_option_ms_id; ?>"<?php echo $_selected; ?>>
                    <?php echo get_text($_set['ms_name']); ?>
                </option>
            <?php
            }
            ?>
        </select>
        <button type="button" class="btn btn_02" onclick="return rb_add_set();">세트추가</button>
        <button type="button" class="btn btn_02" onclick="return rb_rename_set();">이름변경</button>
        <button type="button" class="btn btn_01" onclick="return rb_delete_set();">세트삭제</button>
    </form>
</div>

<!-- 세트 액션용 숨은 폼 -->
<form id="frm_menu_set" method="post" action="./rb_menu_list.php">
    <input type="hidden" name="set_act" value="">
    <input type="hidden" name="ms_id" value="<?php echo (int)$_rb_ms_id; ?>">
    <input type="hidden" name="ms_name" value="">
</form>

<form name="fmenulist" id="fmenulist" method="post" action="./rb_menu_list_update.php" onsubmit="return fmenulist_submit(this);">
    <input type="hidden" name="token" value="">
    <input type="hidden" name="ms_id" value="<?php echo (int)$_rb_ms_id; ?>">

    <div id="menulist" class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?> 목록</caption>
            <thead>
                <tr>
                    <th scope="col" style="min-width: 170px;">메뉴</th>
                    <th scope="col">링크</th>
                    <th scope="col">새창</th>
                    <th scope="col">순서</th>
                    <th scope="col">PC사용</th>
                    <th scope="col">모바일사용</th>
                    <th scope="col" colspan="2">권한</th>
                    <th scope="col">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $_row = sql_fetch_array($_result); $i++) {
                    $bg             = 'bg'.($i % 2);
                    $sub_menu_class = '';
                    $sub_menu_info  = '';
                    $sub_menu_ico   = '';

                    // 뎁스 계산: 2글자=1차, 4글자=2차, 6글자=3차
                    $depth = (int)(strlen($_row['me_code']) / 2);
                    if ($depth < 1) $depth = 1;
                    if ($depth > 3) $depth = 3;

                    if (strlen($_row['me_code']) == 4) {
                        $sub_menu_class = ' sub_menu_class';
                        $sub_menu_info  = '<span class="sound_only">'.get_text($_row['me_name']).'의 서브</span>';
                        $sub_menu_ico   = '<span class="sub_menu_ico"></span>';
                    }

                    // 3차 메뉴용 추가 클래스 (원하면 CSS에서 더 들여쓰기 적용)
                    if (strlen($_row['me_code']) == 6) {
                        $sub_menu_class = ' sub_menu_class depth3';
                    }

                    $search  = array('"', "'");
                    $replace = array('&#034;', '&#039;');
                    $me_name = str_replace($search, $replace, $_row['me_name']);
                ?>
                    <tr class="<?php echo $bg; ?> menu_list menu_group_<?php echo substr($_row['me_code'], 0, 2); ?>">
                        <td class="td_category<?php echo $sub_menu_class; ?>">
                            <!-- 3차 지원용: depth 전송 -->
                            <input type="hidden" name="depth[]" value="<?php echo $depth; ?>">

                            <input type="hidden" name="code[]" value="<?php echo substr($_row['me_code'], 0, 2); ?>">
                            <label for="me_name_<?php echo $i; ?>" class="sound_only">
                                <?php echo $sub_menu_info; ?> 메뉴<strong class="sound_only"> 필수</strong>
                            </label>
                            <input type="text" name="me_name[]" value="<?php echo get_sanitize_input($me_name); ?>" id="me_name_<?php echo $i; ?>" required class="required tbl_input full_input">
                        </td>
                        <td>
                            <label for="me_link_<?php echo $i; ?>" class="sound_only">링크<strong class="sound_only"> 필수</strong></label>
                            <input type="text" name="me_link[]" value="<?php echo $_row['me_link']; ?>" id="me_link_<?php echo $i; ?>" required class="required tbl_input full_input">
                        </td>
                        <td class="td_mng">
                            <label for="me_target_<?php echo $i; ?>" class="sound_only">새창</label>
                            <select name="me_target[]" id="me_target_<?php echo $i; ?>">
                                <option value="self"  <?php echo get_selected($_row['me_target'], 'self', true); ?>>사용안함</option>
                                <option value="blank" <?php echo get_selected($_row['me_target'], 'blank', true); ?>>사용함</option>
                            </select>
                        </td>
                        <td class="td_num">
                            <label for="me_order_<?php echo $i; ?>" class="sound_only">순서</label>
                            <input type="text" name="me_order[]" value="<?php echo $_row['me_order']; ?>" id="me_order_<?php echo $i; ?>" class="tbl_input" size="5">
                        </td>
                        <td class="td_mng">
                            <label for="me_use_<?php echo $i; ?>" class="sound_only">PC사용</label>
                            <select name="me_use[]" id="me_use_<?php echo $i; ?>">
                                <option value="1" <?php echo get_selected($_row['me_use'], '1', true); ?>>사용함</option>
                                <option value="0" <?php echo get_selected($_row['me_use'], '0', true); ?>>사용안함</option>
                            </select>
                        </td>
                        <td class="td_mng">
                            <label for="me_mobile_use_<?php echo $i; ?>" class="sound_only">모바일사용</label>
                            <select name="me_mobile_use[]" id="me_mobile_use_<?php echo $i; ?>">
                                <option value="1" <?php echo get_selected($_row['me_mobile_use'], '1', true); ?>>사용함</option>
                                <option value="0" <?php echo get_selected($_row['me_mobile_use'], '0', true); ?>>사용안함</option>
                            </select>
                        </td>
                        <td class="td_num">
                            <label for="me_level_<?php echo $i; ?>" class="sound_only">권한</label>
                            <?php echo get_member_level_select('me_level[]', 1, $member['mb_level'], $_row['me_level']); ?>
                        </td>
                        <td class="td_mng" style="min-width:150px;">
                            <label for="me_level_opt_<?php echo $i; ?>" class="sound_only">옵션</label>
                            <select id="me_level_opt_<?php echo $i; ?>" name="me_level_opt[]">
                                <option value="1" <?php echo (isset($_row['me_level_opt']) && $_row['me_level_opt'] == "1") ? 'selected' : ''; ?>>레벨 부터 접근가능</option>
                                <option value="2" <?php echo (isset($_row['me_level_opt']) && $_row['me_level_opt'] == "2") ? 'selected' : ''; ?>>레벨만 접근가능</option>
                            </select>
                        </td>
                        <td class="td_mng">
                            <?php if (strlen($_row['me_code']) == 2) { ?>
                                <!-- 1차 메뉴: 2차 추가 -->
                                <button type="button" class="btn_add_submenu btn_03">추가</button>
                            <?php } elseif (strlen($_row['me_code']) == 4) { ?>
                                <!-- 2차 메뉴: 3차 추가 -->
                                <button type="button" class="btn_add_submenu3 btn_03">추가</button>
                            <?php } ?>
                            <button type="button" class="btn_del_menu btn_02">삭제</button>
                        </td>
                    </tr>
                <?php
                }

                if ($i == 0) {
                    echo '<tr id="empty_menu_list"><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <button type="button" onclick="return add_menu();" class="btn btn_02">메뉴추가<span class="sound_only"> 새창</span></button>
        <input type="submit" name="act_button" value="확인" class="btn_submit btn">
    </div>
</form>

<script>
    // ===== 메뉴 세트 관리 JS =====
    function rb_add_set() {
        var name = prompt("추가할 메뉴 세트 이름을 입력하세요.", "");
        if (!name) return false;

        var f = document.getElementById("frm_menu_set");
        if (!f) return false;

        f.set_act.value = "add";
        f.ms_name.value = name;
        f.submit();
        return false;
    }

    function rb_rename_set() {
        var sel = document.getElementById("rb_ms_id_select");
        if (!sel || !sel.value) {
            alert("변경할 메뉴 세트가 없습니다.");
            return false;
        }

        var current = sel.options[sel.selectedIndex].text;
        var name = prompt("메뉴 세트 이름을 변경하세요.", current);
        if (!name) return false;

        var f = document.getElementById("frm_menu_set");
        if (!f) return false;

        f.set_act.value = "rename";
        f.ms_id.value   = sel.value;
        f.ms_name.value = name;
        f.submit();
        return false;
    }

    function rb_delete_set() {
        var sel = document.getElementById("rb_ms_id_select");
        if (!sel || !sel.value) {
            alert("삭제할 메뉴 세트가 없습니다.");
            return false;
        }

        if (!confirm("선택한 메뉴 세트를 삭제하시겠습니까?\n(해당 세트의 메뉴도 함께 삭제됩니다.)")) {
            return false;
        }

        var f = document.getElementById("frm_menu_set");
        if (!f) return false;

        f.set_act.value = "delete";
        f.ms_id.value   = sel.value;
        f.submit();
        return false;
    }

    // ===== 메뉴 리스트 JS (원본 거의 그대로 + 3차추가) =====
    $(function() {
        // 2차 추가
        $(document).on("click", ".btn_add_submenu", function() {
            var code = $(this).closest("tr").find("input[name='code[]']").val().substr(0, 2);
            add_submenu(code);
        });

        // 3차 추가
        $(document).on("click", ".btn_add_submenu3", function() {
            var code = $(this).closest("tr").find("input[name='code[]']").val().substr(0, 2);
            add_submenu3(code);
        });

        $(document).on("click", ".btn_del_menu", function() {
            if (!confirm("메뉴를 삭제하시겠습니까?\n메뉴 삭제후 메뉴설정의 확인 버튼을 눌러 메뉴를 저장해 주세요."))
                return false;

            var $tr = $(this).closest("tr");
            if ($tr.find("td.sub_menu_class").length > 0) {
                $tr.remove();
            } else {
                var code = $(this).closest("tr").find("input[name='code[]']").val().substr(0, 2);
                $("tr.menu_group_" + code).remove();
            }

            if ($("#menulist tr.menu_list").length < 1) {
                var list = "<tr id=\"empty_menu_list\"><td colspan=\"<?php echo $colspan; ?>\" class=\"empty_table\">자료가 없습니다.</td></tr>\n";
                $("#menulist table tbody").append(list);
            } else {
                $("#menulist tr.menu_list").each(function(index) {
                    $(this).removeClass("bg0 bg1")
                        .addClass("bg" + (index % 2));
                });
            }
        });
    });

    function add_menu() {
        var max_code = base_convert(0, 10, 36);
        $("#menulist tr.menu_list").each(function() {
            var me_code = $(this).find("input[name='code[]']").val().substr(0, 2);
            if (max_code < me_code)
                max_code = me_code;
        });

        var url = "./rb_menu_form.php?code=" + max_code + "&new=new";
        window.open(url, "add_menu", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
        return false;
    }

    function add_submenu(code) {
        // 2차 메뉴 추가 (depth=2 힌트 전달)
        var url = "./rb_menu_form.php?code=" + code + "&depth=2";
        window.open(url, "add_menu", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
        return false;
    }

    function add_submenu3(code) {
        // 3차 메뉴 추가 (depth=3 힌트 전달)
        var url = "./rb_menu_form.php?code=" + code + "&depth=3";
        window.open(url, "add_menu", "left=100,top=100,width=550,height=650,scrollbars=yes,resizable=yes");
        return false;
    }

    function base_convert(number, frombase, tobase) {
        return parseInt(number + '', frombase | 0).toString(tobase | 0);
    }

    function fmenulist_submit(f) {
        var me_links = document.getElementsByName('me_link[]');
        var reg = /^javascript/i;

        for (var i = 0; i < me_links.length; i++) {
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
require_once '../admin.tail.php';
