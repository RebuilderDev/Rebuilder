<?php
$sub_menu = "000290";
require_once './_common.php';

check_demo();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

check_admin_token();

$ms_id = isset($_POST['ms_id']) ? (int)$_POST['ms_id'] : 0;
if (!$ms_id) {
    alert('메뉴 세트 정보가 올바르지 않습니다.', './rb_menu_list.php');
}

// 이전 메뉴정보 삭제 (선택 세트만)
$sql = " delete from {$rb['menu_table']} where ms_id = '{$ms_id}' ";
sql_query($sql);

$count        = isset($_POST['code']) ? count($_POST['code']) : 0;
$has_depth    = isset($_POST['depth']) && is_array($_POST['depth']); // 새 3차 로직 사용 여부

// 3차 로직용 상태 변수
$top_code = '';   // 현재 1차 메뉴의 me_code (길이 2)
$mid_code = '';   // 현재 2차 메뉴의 me_code (길이 4)
// 2차까지만 쓰던 기존 로직용
$group_code   = null;
$primary_code = null;

for ($i = 0; $i < $count; $i++) {
    $_POST = array_map_deep('trim', $_POST);

    if (preg_match('/^javascript/i', preg_replace('/[ ]{1,}|[\t]/', '', $_POST['me_link'][$i]))) {
        $_POST['me_link'][$i] = G5_URL;
    }

    $_POST['me_link'][$i] = is_array($_POST['me_link'])
        ? clean_xss_tags(clean_xss_attributes(preg_replace('/[ ]{2,}|[\t]/', '', $_POST['me_link'][$i]), 1))
        : '';
    $_POST['me_link'][$i] = html_purifier($_POST['me_link'][$i]);

    $code    = is_array($_POST['code'])    ? strip_tags($_POST['code'][$i])    : '';
    $me_name = is_array($_POST['me_name']) ? strip_tags($_POST['me_name'][$i]) : '';
    $me_link = (preg_match('/^javascript/i', $_POST['me_link'][$i]) || preg_match('/script:/i', $_POST['me_link'][$i]))
        ? G5_URL
        : strip_tags(clean_xss_attributes($_POST['me_link'][$i]));

    if (!$code || !$me_name || !$me_link) {
        continue;
    }

    // ==============================
    // 1) me_code 생성
    // ==============================
    if ($has_depth) {
        // --- 새 1/2/3차 로직 ---
        $depth = isset($_POST['depth'][$i]) ? (int)$_POST['depth'][$i] : 1;
        if ($depth < 1 || $depth > 3) $depth = 1;

        if ($depth === 1) {
            // 1차: 길이 2
            $sql = "
                select MAX(SUBSTRING(me_code,1,2)) as max_me_code
                  from {$rb['menu_table']}
                 where ms_id = '{$ms_id}'
                   and LENGTH(me_code) = '2'
            ";
            $row = sql_fetch($sql);

            $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                ? $row['max_me_code']
                : 0;

            $me2 = (int)base_convert($max, 36, 10);
            $me2 += 36;
            $me2  = base_convert((string)$me2, 10, 36);

            $top_code = $me2;
            $mid_code = '';
            $me_code  = $top_code;

        } elseif ($depth === 2) {
            // 2차: 길이 4 (상위 2자리: $top_code)
            if ($top_code === '') {
                // 바로 위에 1차가 없으면 방어적으로 1차 취급
                $sql = "
                    select MAX(SUBSTRING(me_code,1,2)) as max_me_code
                      from {$rb['menu_table']}
                     where ms_id = '{$ms_id}'
                       and LENGTH(me_code) = '2'
                ";
                $row = sql_fetch($sql);

                $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                    ? $row['max_me_code']
                    : 0;

                $me2 = (int)base_convert($max, 36, 10);
                $me2 += 36;
                $me2  = base_convert((string)$me2, 10, 36);

                $top_code = $me2;
            }

            $sql = "
                select MAX(SUBSTRING(me_code,3,2)) as max_me_code
                  from {$rb['menu_table']}
                 where ms_id = '{$ms_id}'
                   and SUBSTRING(me_code,1,2) = '{$top_code}'
            ";
            $row = sql_fetch($sql);

            $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                ? $row['max_me_code']
                : 0;

            $sub2 = (int)base_convert($max, 36, 10);
            $sub2 += 36;
            $sub2  = base_convert((string)$sub2, 10, 36);

            $mid_code = $top_code.$sub2;
            $me_code  = $mid_code;

        } else {
            // 3차: 길이 6 (상위 4자리: $mid_code)
            if ($mid_code === '') {
                // 바로 위에 2차가 없으면, 2차 생성 후 3차를 다는 방식으로도 확장 가능하지만
                // 일단은 방어적으로 스킵
                // continue; 로 그냥 건너뛰어도 됨
                // 여기서는 2차처럼 한 번 더 만들어주고 그 밑에 3차를 붙이는 식으로 처리 가능
                // 우선은 parent가 없으면 2차로 강등
                $depth = 2;

                // 2차 로직 재호출처럼 처리
                if ($top_code === '') {
                    $sql = "
                        select MAX(SUBSTRING(me_code,1,2)) as max_me_code
                          from {$rb['menu_table']}
                         where ms_id = '{$ms_id}'
                           and LENGTH(me_code) = '2'
                    ";
                    $row = sql_fetch($sql);

                    $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                        ? $row['max_me_code']
                        : 0;

                    $me2 = (int)base_convert($max, 36, 10);
                    $me2 += 36;
                    $me2  = base_convert((string)$me2, 10, 36);

                    $top_code = $me2;
                }

                $sql = "
                    select MAX(SUBSTRING(me_code,3,2)) as max_me_code
                      from {$rb['menu_table']}
                     where ms_id = '{$ms_id}'
                       and SUBSTRING(me_code,1,2) = '{$top_code}'
                ";
                $row = sql_fetch($sql);

                $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                    ? $row['max_me_code']
                    : 0;

                $sub2 = (int)base_convert($max, 36, 10);
                $sub2 += 36;
                $sub2  = base_convert((string)$sub2, 10, 36);

                $mid_code = $top_code.$sub2;
                $me_code  = $mid_code;
            } else {
                $sql = "
                    select MAX(SUBSTRING(me_code,5,2)) as max_me_code
                      from {$rb['menu_table']}
                     where ms_id = '{$ms_id}'
                       and SUBSTRING(me_code,1,4) = '{$mid_code}'
                ";
                $row = sql_fetch($sql);

                $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                    ? $row['max_me_code']
                    : 0;

                $sub3 = (int)base_convert($max, 36, 10);
                $sub3 += 36;
                $sub3  = base_convert((string)$sub3, 10, 36);

                $me_code = $mid_code.$sub3;
            }
        }

    } else {
        // --- 기존 1/2차 로직 (depth[] 없을 때) ---
        $sub_code = '';
        if ($group_code == $code) {
            $sql = "
                select MAX(SUBSTRING(me_code,3,2)) as max_me_code
                  from {$rb['menu_table']}
                 where ms_id = '{$ms_id}'
                   and SUBSTRING(me_code,1,2) = '{$primary_code}'
            ";
            $row = sql_fetch($sql);

            $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                ? $row['max_me_code']
                : 0;

            $sub_code = (int)base_convert($max, 36, 10);
            $sub_code += 36;
            $sub_code = base_convert((string)$sub_code, 10, 36);

            $me_code = $primary_code . $sub_code;
        } else {
            $sql = "
                select MAX(SUBSTRING(me_code,1,2)) as max_me_code
                  from {$rb['menu_table']}
                 where ms_id = '{$ms_id}'
                   and LENGTH(me_code) = '2'
            ";
            $row = sql_fetch($sql);

            $max = (isset($row['max_me_code']) && $row['max_me_code'] !== '')
                ? $row['max_me_code']
                : 0;

            $me_code = (int)base_convert($max, 36, 10);
            $me_code += 36;
            $me_code = base_convert((string)$me_code, 10, 36);

            $group_code   = $code;
            $primary_code = $me_code;
        }

        // 기존 방식에서는 top/mid 상태도 맞춰두면 좋음 (추후 혼합 사용 대비)
        $top_code = substr($me_code, 0, 2);
        if (strlen($me_code) >= 4)
            $mid_code = substr($me_code, 0, 4);
    }

    // ==============================
    // 2) 메뉴 등록
    // ==============================
    $sql = "
        insert into {$rb['menu_table']}
            set ms_id          = '{$ms_id}',
                me_code        = '" . $me_code . "',
                me_name        = '" . $me_name . "',
                me_link        = '" . $me_link . "',
                me_target      = '" . sql_real_escape_string(strip_tags($_POST['me_target'][$i])) . "',
                me_order       = '" . sql_real_escape_string(strip_tags($_POST['me_order'][$i])) . "',
                me_use         = '" . sql_real_escape_string(strip_tags($_POST['me_use'][$i])) . "',
                me_mobile_use  = '" . sql_real_escape_string(strip_tags($_POST['me_mobile_use'][$i])) . "',
                me_level       = '" . sql_real_escape_string(strip_tags($_POST['me_level'][$i])) . "',
                me_level_opt   = '" . sql_real_escape_string(strip_tags($_POST['me_level_opt'][$i])) . "'
    ";
    sql_query($sql);
}

run_event('admin_rb_menu_list_update');

goto_url('./rb_menu_list.php?ms_id='.$ms_id);
