<?php
$sub_menu = "100290";
require_once './_common.php';

if ($is_admin != 'super') {
    alert_close('최고관리자만 접근 가능합니다.');
}

$g5['title'] = '메뉴 추가';
require_once G5_PATH . '/head.sub.php';

$new    = isset($_GET['new']) ? clean_xss_tags($_GET['new'], 1, 1) : '';
$code   = isset($_GET['code']) ? (string)preg_replace('/[^0-9a-zA-Z]/', '', $_GET['code']) : '';

// // 기존 로직 유지(상위 추가 시 list.php에서 max_code를 넘겨줌)
if ($new == 'new' || !$code) {
    $code = (int)base_convert(substr($code, 0, 2), 36, 10);
    $code += 36;
    $code = base_convert((string)$code, 10, 36);
}

$selects_lev = get_member_level_select('me_level[]', 1, $member['mb_level'], '');
$selects_lev = str_replace("\n", "", $selects_lev);
$selects_lev = str_replace("'", "\'", $selects_lev);
?>

<div id="menu_frm" class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <form name="fmenuform" id="fmenuform" class="new_win_con">

        <div class="new_win_desc">
            <label for="me_type">대상선택</label>
            <select name="me_type" id="me_type">
                <option value="">직접입력</option>
                <option value="group">게시판그룹</option>
                <option value="board">게시판</option>
                <option value="content">내용관리</option>
            </select>
        </div>

        <div id="menu_result"></div>

    </form>

</div>

<script>
$(function() {
    $("#menu_result").load("./menu_form_search.php");

    function link_checks_all_chage() {
        var $links = $(opener.document).find("#menulist input[name='me_link[]']");
        var $o_link = $(".td_mngsmall input[name='link[]']");
        var hrefs = [];
        var menu_exist = false;

        if ($links.length) {
            $links.each(function() {
                hrefs.push($(this).val());
            });

            $o_link.each(function() {
                if ($.inArray($(this).val(), hrefs) != -1) {
                    $(this).closest("tr").find("td:eq(0)").addClass("exist_menu_link");
                    menu_exist = true;
                }
            });
        }

        if (menu_exist) $(".menu_exists_tip").show();
        else $(".menu_exists_tip").hide();
    }

    function menu_result_change(type) {
        var dfd = new $.Deferred();

        $("#menu_result").empty().load("./menu_form_search.php", { type: type }, function() {
            dfd.resolve('Finished');
        });

        return dfd.promise();
    }

    $("#me_type").on("change", function() {
        var type = $(this).val();
        var promise = menu_result_change(type);

        promise.done(function() {
            link_checks_all_chage(type);
        });
    });

    $(document).on("click", "#add_manual", function() {
        var me_name = $.trim($("#me_name").val());
        var me_link = $.trim($("#me_link").val());

        add_menu_list(me_name, me_link, "<?php echo $code; ?>", "<?php echo $new; ?>");
    });

    $(document).on("click", ".add_select", function() {
        var me_name = $.trim($(this).siblings("input[name='subject[]']").val());
        var me_link = $.trim($(this).siblings("input[name='link[]']").val());

        add_menu_list(me_name, me_link, "<?php echo $code; ?>", "<?php echo $new; ?>");
    });
});

function htmlEscape(str) {
    str = String(str || "");
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function add_menu_list(name, link, parentCode, newFlag) {
    var $menulist = $("#menulist", opener.document);
    var ms = new Date().getTime();

    name = htmlEscape(name);
    link = htmlEscape(link);

    parentCode = String(parentCode || "");
    newFlag = String(newFlag || "");

    // // 2자리 suffix(10,20,30...a0,b0...) 생성
    function rbPad2(s) {
        s = String(s || "");
        return (s.length >= 2) ? s.slice(-2) : ("0" + s).slice(-2);
    }

    function rbNextChildCode(prefix) {
        var plen = prefix.length;
        var nextDec = 36; // // '10'

        $menulist.find("input[name='code[]']").each(function() {
            var c = String($(this).val() || "");
            if (!c) return;

            // // prefix 아래 자식만(prefix + 2자리)
            if (c.indexOf(prefix) === 0 && c.length === plen + 2) {
                var suf = c.substr(plen, 2);
                var dec = parseInt(suf, 36);
                if (!isNaN(dec) && dec >= nextDec) nextDec = dec + 36;
            }
        });

        return prefix + rbPad2(nextDec.toString(36));
    }

    // // 최종 생성될 me_code
    var newCode = "";

    if (newFlag === "new") {
        // // 상위(2자리)는 list.php에서 max_code 기반으로 이미 다음값이 parentCode로 들어옴
        // // 하지만 중복 방지를 위해 실제로 비어있으면 계산
        if (parentCode.length === 2) {
            newCode = parentCode;
        } else {
            // // fallback
            var maxDec = 0;
            $menulist.find("input[name='code[]']").each(function() {
                var c = String($(this).val() || "");
                if (c.length === 2) {
                    var d = parseInt(c, 36);
                    if (!isNaN(d) && d > maxDec) maxDec = d;
                }
            });
            newCode = rbPad2((maxDec + 36).toString(36));
        }
    } else {
        // // 서브: 부모가 2자리면 4자리, 부모가 4자리면 6자리
        if (!(parentCode.length === 2 || parentCode.length === 4)) {
            alert("부모 코드가 올바르지 않습니다.");
            return false;
        }
        newCode = rbNextChildCode(parentCode);
    }

    // // 표시 클래스
    var tdClass = "td_category";
    if (newCode.length === 4) tdClass += " sub_menu_class";
    if (newCode.length === 6) tdClass += " sub_menu_class sub_menu_class_3";

    // // 그룹 클래스는 항상 1차(앞2자리)
    var group2 = newCode.substr(0, 2);

    var selectsLev = '<?php echo $selects_lev; ?>';

    var list = "<tr class=\"menu_list menu_group_" + group2 + "\">";

    list += "<td class=\"" + tdClass + "\">";
    list += "<label for=\"me_name_" + ms + "\" class=\"sound_only\">메뉴<strong class=\"sound_only\"> 필수</strong></label>";
    list += "<input type=\"hidden\" name=\"code[]\" value=\"" + newCode + "\">";
    list += "<input type=\"text\" name=\"me_name[]\" value=\"" + name + "\" id=\"me_name_" + ms + "\" required class=\"required frm_input full_input\">";
    list += "</td>";

    list += "<td>";
    list += "<label for=\"me_link_" + ms + "\" class=\"sound_only\">링크<strong class=\"sound_only\"> 필수</strong></label>";
    list += "<input type=\"text\" name=\"me_link[]\" value=\"" + link + "\" id=\"me_link_" + ms + "\" required class=\"required frm_input full_input\">";
    list += "</td>";

    list += "<td class=\"td_mng\">";
    list += "<label for=\"me_target_" + ms + "\" class=\"sound_only\">새창</label>";
    list += "<select name=\"me_target[]\" id=\"me_target_" + ms + "\">";
    list += "<option value=\"self\">사용안함</option>";
    list += "<option value=\"blank\">사용함</option>";
    list += "</select>";
    list += "</td>";

    list += "<td class=\"td_numsmall\">";
    list += "<label for=\"me_order_" + ms + "\" class=\"sound_only\">순서<strong class=\"sound_only\"> 필수</strong></label>";
    list += "<input type=\"text\" name=\"me_order[]\" value=\"0\" id=\"me_order_" + ms + "\" required class=\"required frm_input\" size=\"5\">";
    list += "</td>";

    list += "<td class=\"td_mngsmall\">";
    list += "<label for=\"me_use_" + ms + "\" class=\"sound_only\">PC사용</label>";
    list += "<select name=\"me_use[]\" id=\"me_use_" + ms + "\">";
    list += "<option value=\"1\">사용함</option>";
    list += "<option value=\"0\">사용안함</option>";
    list += "</select>";
    list += "</td>";

    list += "<td class=\"td_mngsmall\">";
    list += "<label for=\"me_mobile_use_" + ms + "\" class=\"sound_only\">모바일사용</label>";
    list += "<select name=\"me_mobile_use[]\" id=\"me_mobile_use_" + ms + "\">";
    list += "<option value=\"1\">사용함</option>";
    list += "<option value=\"0\">사용안함</option>";
    list += "</select>";
    list += "</td>";

    list += "<td class=\"td_num\">";
    list += "<label for=\"me_level_" + ms + "\" class=\"sound_only\">접근권한</label>";
    list += selectsLev;
    list += "</td>";

    list += "<td class=\"td_mng\" style=\"min-width:150px;\">";
    list += "<label for=\"me_level_opt_" + ms + "\" class=\"sound_only\">옵션</label>";
    list += "<select id=\"me_level_opt\" name=\"me_level_opt[]\">";
    list += "<option value=\"1\">레벨 부터 접근가능</option>";
    list += "<option value=\"2\">레벨만 접근가능</option>";
    list += "</select>";
    list += "</td>";

    list += "<td class=\"td_mng\">";
    // // 1차/2차만 추가 버튼
    if (newCode.length === 2 || newCode.length === 4) {
        list += "<button type=\"button\" class=\"btn_add_submenu btn_03\">추가</button>\n";
    }
    list += "<button type=\"button\" class=\"btn_del_menu btn_02\">삭제</button>";
    list += "</td>";
    list += "</tr>";

    // // 삽입 위치: 부모 아래(부모 prefix 마지막 항목 아래)
    var $insertAfter = null;

    if (newFlag === "new") {
        $insertAfter = $menulist.find("tr.menu_list:last");
    } else {
        var p = parentCode;
        var $desc = $menulist.find("tr.menu_list").filter(function() {
            var c = String($(this).find("input[name='code[]']").val() || "");
            return (c && c.indexOf(p) === 0);
        });

        if ($desc.length > 0) $insertAfter = $desc.last();
        else $insertAfter = $menulist.find("tr.menu_list:last");
    }

    if ($menulist.find("#empty_menu_list").length > 0) {
        $menulist.find("#empty_menu_list").remove();
    }

    if ($insertAfter && $insertAfter.length > 0) {
        $insertAfter.after(list);
    } else {
        $menulist.find("table tbody").append(list);
    }

    $menulist.find("tr.menu_list").each(function(index) {
        $(this).removeClass("bg0 bg1").addClass("bg" + (index % 2));
    });

    window.close();
}
</script>

<?php
require_once G5_PATH . '/tail.sub.php';
