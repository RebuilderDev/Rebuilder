<?php
if(!defined('_GNUBOARD_'))exit; // 개별 페이지 접근 불가

add_replace('admin_menu', 'add_admin_rb_menu', 0, 1); // 관리자 메뉴를 추가함

function add_admin_rb_menu($admin_menu){ // 메뉴추가
    
    $admin_menu['menu000'][] =  array('000290', '메뉴 설정', G5_ADMIN_URL . '/rb/rb_menu_list.php',   'rb_config');

    $admin_menu['menu000'][] = array('000000', '　', G5_ADMIN_URL, 'rb_config');
    return $admin_menu;
}


// rb 메뉴 설정을 위한 상수 정의
$rb['menu_set_table'] = 'rb_menu_set';
$rb['menu_table'] = 'rb_menu';


// 현재 설정된 메뉴세트 가져오기
$rb_menu_set_row = sql_fetch(" select co_menu_set from `rb_config` where co_id = 1 ");
$rb_config['menu_set'] = isset($rb_menu_set_row['co_menu_set']) ? (int)$rb_menu_set_row['co_menu_set'] : 0;


/**
 * RB 메뉴 전용 메뉴 트리 (1~3차) 가져오기
 *
 * @param int  $ms_id      메뉴 세트 ID (rb_menu_set.ms_id)
 * @param int  $use_mobile 0: PC용 me_use / 1: 모바일용 me_mobile_use
 * @param bool $is_cache   true 시 static 캐시 사용
 * @return array
 */
function get_rb_menu_db($ms_id, $use_mobile = 0, $is_cache = false)
{
    global $rb;

    static $cache = array();

    $ms_id = (int)$ms_id;
    if (!$ms_id) {
        // 필요하면 여기서 기본 세트를 찾아서 대입해도 됨.
        // 지금은 ms_id 없으면 빈 배열 리턴.
        return array();
    }

    // 캐시 키: 세트 + 모바일 여부
    $key = md5($ms_id.'|'.$use_mobile);

    // 훅/필터용 (원하면 안 써도 됨)
    if (function_exists('run_replace')) {
        $cache = run_replace('get_rb_menu_db_cache', $cache, $ms_id, $use_mobile, $is_cache);
        if ($is_cache && isset($cache[$key])) {
            return $cache[$key];
        }
    } else if ($is_cache && isset($cache[$key])) {
        return $cache[$key];
    }

    $where_use = $use_mobile ? "me_mobile_use = '1'" : "me_use = '1'";

    // 훅/필터용 (원하면 안 써도 됨)
    if (function_exists('run_replace')) {
        if (!($cache[$key] = run_replace('get_rb_menu_db', array(), $ms_id, $use_mobile))) {
            $cache[$key] = array();
        } else {
            return $cache[$key];
        }
    } else {
        $cache[$key] = array();
    }

    // ========= 1차 메뉴 (me_code 길이 2) =========
    $sql = "
        select *
          from {$rb['menu_table']}
         where ms_id = '{$ms_id}'
           and {$where_use}
           and length(me_code) = 2
         order by me_order, me_id
    ";
    $result = sql_query($sql, false);

    for ($i = 0; $row = sql_fetch_array($result); $i++) {

        // 링크 원본/짧은주소
        $row['ori_me_link'] = $row['me_link'];
        if (function_exists('short_url_clean')) {
            $row['me_link'] = short_url_clean($row['me_link']);
        }

        $row['sub'] = array(); // 2차 컨테이너

        $cache[$key][$i] = $row;

        // ========= 2차 메뉴 (me_code 길이 4) =========
        $sql2 = "
            select *
              from {$rb['menu_table']}
             where ms_id = '{$ms_id}'
               and {$where_use}
               and length(me_code) = 4
               and substring(me_code, 1, 2) = '{$row['me_code']}'
             order by me_order, me_id
        ";
        $result2 = sql_query($sql2);
        for ($k = 0; $row2 = sql_fetch_array($result2); $k++) {

            $row2['ori_me_link'] = $row2['me_link'];
            if (function_exists('short_url_clean')) {
                $row2['me_link'] = short_url_clean($row2['me_link']);
            }

            $row2['sub'] = array(); // 3차 컨테이너

            $cache[$key][$i]['sub'][$k] = $row2;

            // ========= 3차 메뉴 (me_code 길이 6) =========
            $sql3 = "
                select *
                  from {$rb['menu_table']}
                 where ms_id = '{$ms_id}'
                   and {$where_use}
                   and length(me_code) = 6
                   and substring(me_code, 1, 4) = '{$row2['me_code']}'
                 order by me_order, me_id
            ";
            $result3 = sql_query($sql3);
            for ($j = 0; $row3 = sql_fetch_array($result3); $j++) {

                $row3['ori_me_link'] = $row3['me_link'];
                if (function_exists('short_url_clean')) {
                    $row3['me_link'] = short_url_clean($row3['me_link']);
                }

                $cache[$key][$i]['sub'][$k]['sub'][$j] = $row3;
            }
        }
    }

    return $cache[$key];
}

function rb_menu_set_list($ms_id = 0)
{
    global $rb;

    $ms_id = (int) $ms_id;
    $str   = '';

    $sql = " select ms_id, ms_name, ms_is_default
               from {$rb['menu_set_table']}
           order by ms_order, ms_id ";
    $result = sql_query($sql);

    for ($i = 0; $row = sql_fetch_array($result); $i++) {

        $id   = (int)$row['ms_id'];
        $name = get_text($row['ms_name']);

        // 기본 세트 표시를 하고 싶다면 (선택사항)
        // if ($row['ms_is_default']) {
        //     $name .= ' (기본)';
        // }

        if ($ms_id === $id) {
            $str .= '<option value="'.$id.'" selected="selected">'.$name.'</option>';
        } else {
            $str .= '<option value="'.$id.'">'.$name.'</option>';
        }
    }

    return $str;
}
