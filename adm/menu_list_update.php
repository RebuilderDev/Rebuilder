<?php
$sub_menu = "100290";
require_once './_common.php';

check_demo();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

check_admin_token();

$_POST = array_map_deep('trim', $_POST);

$count = isset($_POST['code']) && is_array($_POST['code']) ? count($_POST['code']) : 0;
$records = array();
$previous_depth = 0;
$has_first = false;
$has_second = false;

for ($i = 0; $i < $count; $i++) {

    $raw_link = isset($_POST['me_link'][$i]) ? (string)$_POST['me_link'][$i] : '';
    $raw_link = preg_replace('/[ ]{2,}|[\t]/', '', $raw_link);

    if (preg_match('/^javascript/i', preg_replace('/[ ]{1,}|[\t]/', '', $raw_link))) {
        $raw_link = G5_URL;
    }

    $raw_link = clean_xss_tags(clean_xss_attributes($raw_link, 1), 1);
    $raw_link = html_purifier($raw_link);

    $old_code = isset($_POST['code'][$i]) ? (string)$_POST['code'][$i] : '';
    $me_name = isset($_POST['me_name'][$i]) ? (string)$_POST['me_name'][$i] : '';

    $old_code = strtolower(preg_replace('/[^0-9a-zA-Z]/', '', strip_tags($old_code)));

    $me_name = strip_tags($me_name);

    $me_link = (preg_match('/^javascript/i', $raw_link) || preg_match('/script:/i', $raw_link)) ? G5_URL : strip_tags(clean_xss_attributes($raw_link));

    if (!$old_code || !$me_name || !$me_link) {
        continue;
    }

    $fallback_depth = strlen($old_code) === 6 ? 2 : (strlen($old_code) === 4 ? 1 : 0);
    $depth = isset($_POST['menu_depth'][$i]) ? (int) $_POST['menu_depth'][$i] : $fallback_depth;
    if ($depth < 0 || $depth > 2) alert('메뉴 단계 정보가 올바르지 않습니다.');
    if (!$records && $depth !== 0) alert('첫 번째 메뉴는 1차 메뉴여야 합니다.');
    if ($records && $depth > $previous_depth + 1) alert('메뉴 단계는 한 번에 한 단계씩만 내려갈 수 있습니다.');
    if ($depth === 1 && !$has_first) alert('2차 메뉴의 상위 1차 메뉴를 찾을 수 없습니다.');
    if ($depth === 2 && !$has_second) alert('3차 메뉴의 상위 2차 메뉴를 찾을 수 없습니다.');

    if ($depth === 0) {
        $has_first = true;
        $has_second = false;
    } elseif ($depth === 1) {
        $has_second = true;
    }
    $previous_depth = $depth;

    $target = isset($_POST['me_target'][$i]) && $_POST['me_target'][$i] === 'blank' ? 'blank' : 'self';
    $records[] = array(
        'old_code' => $old_code,
        'depth' => $depth,
        'me_name' => $me_name,
        'me_link' => $me_link,
        'me_target' => $target,
        'me_use' => !empty($_POST['me_use'][$i]) ? 1 : 0,
        'me_mobile_use' => !empty($_POST['me_mobile_use'][$i]) ? 1 : 0,
        'me_level' => max(1, min(10, isset($_POST['me_level'][$i]) ? (int) $_POST['me_level'][$i] : 1)),
        'me_level_opt' => isset($_POST['me_level_opt'][$i]) && (int) $_POST['me_level_opt'][$i] === 2 ? 2 : 1,
    );
}

function rb_admin_menu_code_segment($number)
{
    $segment = base_convert((string) max(1, (int) $number), 10, 36);
    return str_pad(substr($segment, -2), 2, '0', STR_PAD_LEFT);
}

function rb_admin_menu_allocate_code($prefix, $old_code, &$used)
{
    $preferred = strlen($old_code) >= 2 ? substr($old_code, -2) : '';
    $candidate = preg_match('/^[0-9a-z]{2}$/', $preferred) ? $prefix.$preferred : '';
    if ($candidate !== '' && empty($used[$candidate])) {
        $used[$candidate] = true;
        return $candidate;
    }
    for ($i = 1; $i < 1296; $i++) {
        $candidate = $prefix.rb_admin_menu_code_segment($i);
        if (empty($used[$candidate])) {
            $used[$candidate] = true;
            return $candidate;
        }
    }
    return '';
}

$used_codes = array();
$parent_code = '';
$second_code = '';
$orders = array(0, 0, 0);
foreach ($records as $index => $record) {
    $depth = (int) $record['depth'];
    $prefix = $depth === 0 ? '' : ($depth === 1 ? $parent_code : $second_code);
    $code = rb_admin_menu_allocate_code($prefix, $record['old_code'], $used_codes);
    if ($code === '' || strlen($code) !== ($depth + 1) * 2) alert('메뉴 코드를 생성할 수 없습니다. 같은 단계의 메뉴 수를 확인해 주세요.');
    if ($depth === 0) {
        $parent_code = $code;
        $second_code = '';
        $orders[0]++;
        $orders[1] = $orders[2] = 0;
    } elseif ($depth === 1) {
        $second_code = $code;
        $orders[1]++;
        $orders[2] = 0;
    } else {
        $orders[2]++;
    }
    $records[$index]['code'] = $code;
    $records[$index]['me_order'] = ($orders[$depth] - 1) * 10;
}

// 검증과 코드 재구성이 끝난 뒤 기존 메뉴를 교체합니다.
sql_query(" delete from {$g5['menu_table']} ");

foreach ($records as $record) {
    $sql = " insert into {$g5['menu_table']}
                set me_code         = '" . sql_real_escape_string($record['code']) . "',
                    me_name         = '" . sql_real_escape_string($record['me_name']) . "',
                    me_link         = '" . sql_real_escape_string($record['me_link']) . "',
                    me_target       = '" . sql_real_escape_string($record['me_target']) . "',
                    me_order        = '" . (int) $record['me_order'] . "',
                    me_use          = '" . (int) $record['me_use'] . "',
                    me_mobile_use   = '" . (int) $record['me_mobile_use'] . "',
                    me_level        = '" . (int) $record['me_level'] . "',
                    me_level_opt    = '" . (int) $record['me_level_opt'] . "' ";
    sql_query($sql);
}

run_event('admin_menu_list_update');

goto_url('./menu_list.php');

