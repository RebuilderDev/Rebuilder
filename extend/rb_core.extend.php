<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 500 에러가 나오시는 경우 아래 코드를 주석해제하셔서
// 보이는 에러구문을 알려주세요.

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

define('RB_VER',  '2.2.7'); // 버전
define('RB_TABLE_PREFIX', 'rb_'); // 리빌더 접두사

// 헤더 배경색을 실제 표시색으로 합성한 뒤 검정/흰색 중 대비가 높은 색을 선택한다.
if (!function_exists('rb_header_color_info')) {
    function rb_header_color_info($color)
    {
        $hex = strtolower(ltrim(trim((string) $color), '#'));

        if (strlen($hex) === 3 || strlen($hex) === 4) {
            $expanded = '';
            for ($i = 0, $length = strlen($hex); $i < $length; $i++) {
                $expanded .= $hex[$i] . $hex[$i];
            }
            $hex = $expanded;
        }

        if (!preg_match('/^[0-9a-f]{6}([0-9a-f]{2})?$/', $hex)) {
            $hex = 'ffffff';
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));
        $alpha = strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1;

        // 투명 헤더는 기존 화면 배경인 흰색 위에 합성하여 최초 표시색을 계산한다.
        $red = ($red * $alpha) + (255 * (1 - $alpha));
        $green = ($green * $alpha) + (255 * (1 - $alpha));
        $blue = ($blue * $alpha) + (255 * (1 - $alpha));

        $channels = array($red, $green, $blue);
        foreach ($channels as $key => $channel) {
            $channel /= 255;
            $channels[$key] = $channel <= 0.04045
                ? $channel / 12.92
                : pow(($channel + 0.055) / 1.055, 2.4);
        }

        $luminance = (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
        $black_contrast = ($luminance + 0.05) / 0.05;
        $white_contrast = 1.05 / ($luminance + 0.05);
        $text = $black_contrast >= $white_contrast ? 'black' : 'white';

        return array(
            'color' => '#' . $hex,
            'alpha' => $alpha,
            'luminance' => $luminance,
            'text' => $text,
        );
    }
}

if (!function_exists('rb_header_logo_url')) {
    function rb_header_logo_url($device, $use_white)
    {
        global $rb_builder;

        $device = $device === 'mo' ? 'mo' : 'pc';
        $suffix = $use_white ? '_w' : '';
        $normal_key = 'bu_logo_' . $device;
        $white_key = $normal_key . '_w';
        $has_logo_pair = !empty($rb_builder[$normal_key]) && !empty($rb_builder[$white_key]);

        if ($has_logo_pair) {
            return G5_URL . '/data/logos/' . $device . $suffix;
        }

        return G5_THEME_URL . '/rb.img/logos/' . $device . $suffix . '.png';
    }
}

// 검색 조건이 없는 알림·바로가기 진입에서도 관리 목록의 검색어 보존 변수를 보장한다.
if (!isset($save_stx) || !is_scalar($save_stx)) {
    $save_stx = (isset($_REQUEST['save_stx']) && is_scalar($_REQUEST['save_stx']))
        ? (string) $_REQUEST['save_stx']
        : '';
}
$save_stx = (string) $save_stx;

/*********************************************/

$rb_config_col = sql_fetch (" select * from rb_config  "); // 환경설정 테이블 1차조회

if (!isset($rb_config_col['co_theme'])) { // 컬럼이 없으면 추가
    sql_query("alter table rb_config add column co_theme varchar(100) default ''");
}
if (empty($rb_config_col['co_theme'])) { // 환경설정 테이블에 테마명이 비어있는 경우 주입
    sql_query("update rb_config set co_theme = '" . addslashes($config['cf_theme']) . "'");
    $rb_config['co_theme'] = $config['cf_theme'];
}

$rb_config = sql_fetch (" select * from rb_config where co_theme = '{$config['cf_theme']}' "); // 환경설정 테이블 조회
$rb_builder = sql_fetch (" select * from rb_builder "); // 빌더설정 테이블 조회

$rb_core['theme'] = !empty($config['cf_theme']) ? $config['cf_theme'] : ''; // 테마
$rb_core['layout'] = !empty($rb_config['co_layout']) ? $rb_config['co_layout'] : ''; // 레이아웃(메인)
$rb_core['layout_hd'] = !empty($rb_config['co_layout_hd']) ? $rb_config['co_layout_hd'] : ''; // 레이아웃(헤더)
$rb_core['layout_ft'] = !empty($rb_config['co_layout_ft']) ? $rb_config['co_layout_ft'] : ''; // 레이아웃(푸터)
$rb_core['color'] = !empty($rb_config['co_color']) ? 'co_'.$rb_config['co_color'] : ''; // 강조컬러
$rb_core['header'] = !empty($rb_config['co_header']) ? 'co_header_'.$rb_config['co_header'] : ''; // 헤더스타일
$rb_header_color_info = rb_header_color_info(isset($rb_config['co_header']) ? $rb_config['co_header'] : '');
$rb_core['header_txt'] = $rb_header_color_info['text']; // 헤더 대비색
$rb_core['header_logo_white'] = $rb_header_color_info['text'] === 'white' ? 1 : 0; // 헤더 로고 종류
$rb_core['font'] = !empty($rb_config['co_font']) ? $rb_config['co_font'] : ''; // 폰트스타일
$rb_core['sub_width'] = !empty($rb_config['co_sub_width']) ? $rb_config['co_sub_width'] : "1400"; // 서브가로사이즈
$rb_core['main_width'] = !empty($rb_config['co_main_width']) ? $rb_config['co_main_width'] : "1400"; // 메인가로사이즈
$rb_core['tb_width'] = !empty($rb_config['co_tb_width']) ? $rb_config['co_tb_width'] : "1400"; // 상단, 하단 가로사이즈
$rb_core['padding_top'] = !empty($rb_config['co_main_padding_top']) ? $rb_config['co_main_padding_top'] : "0"; // 상단, 하단 가로사이즈

/* 2.1.4 { */
$rb_core['gap_pc'] = !empty($rb_config['co_gap_pc']) ? $rb_config['co_gap_pc'] : '0'; // 간격
$rb_core['inner_padding_pc'] = !empty($rb_config['co_inner_padding_pc']) ? $rb_config['co_inner_padding_pc'] : '0'; // 내부여백
/* } */


/* 2.2 { */
$rb_core['side_skin'] = !empty($rb_config['co_side_skin']) ? $rb_config['co_side_skin'] : ''; // 사이드메뉴 스킨
$rb_core['side_skin_shop'] = !empty($rb_config['co_side_skin_shop']) ? $rb_config['co_side_skin_shop'] : ''; // 사이드메뉴 스킨 (마켓)
$rb_core['sidemenu'] = !empty($rb_config['co_sidemenu']) ? $rb_config['co_sidemenu'] : ''; // 사이드메뉴 여부, 위치
$rb_core['sidemenu_shop'] = !empty($rb_config['co_sidemenu_shop']) ? $rb_config['co_sidemenu_shop'] : ''; // 사이드메뉴 여부, 위치 (마켓)
$rb_core['sidemenu_width'] = !empty($rb_config['co_sidemenu_width']) ? $rb_config['co_sidemenu_width'] : '200'; // 사이드메뉴 가로크기
$rb_core['sidemenu_width_shop'] = !empty($rb_config['co_sidemenu_width_shop']) ? $rb_config['co_sidemenu_width_shop'] : '200'; // 사이드메뉴 가로크기 (마켓)
$rb_core['sidemenu_padding'] = !empty($rb_config['co_sidemenu_padding']) ? $rb_config['co_sidemenu_padding'] : '0'; // 사이드메뉴 여백
$rb_core['sidemenu_padding_shop'] = !empty($rb_config['co_sidemenu_padding_shop']) ? $rb_config['co_sidemenu_padding_shop'] : '0'; // 사이드메뉴 여백 (마켓)
$rb_core['sidemenu_hide'] = !empty($rb_config['co_sidemenu_hide']) ? $rb_config['co_sidemenu_hide'] : '0'; // 사이드메뉴 숨김
$rb_core['sidemenu_hide_shop'] = !empty($rb_config['co_sidemenu_hide_shop']) ? $rb_config['co_sidemenu_hide_shop'] : '0'; // 사이드메뉴 숨김 (마켓)
$rb_core['menu_shop'] = !empty($rb_config['co_menu_shop']) ? $rb_config['co_menu_shop'] : '0'; // 마켓 메뉴설정

// 현재 페이지 URL로 v_code 변환
$rb_page_url = $_SERVER['REQUEST_URI'];
$rb_page_urls = urldecode($rb_page_url);
$rb_page_urls = explode('?', $rb_page_urls)[0]; // 쿼리스트링 제거
$rb_page_urls = preg_replace('/\.php$/', '', $rb_page_urls);
$rb_page_urls = str_replace('/', '-', $rb_page_urls);
$rb_page_urls = ltrim($rb_page_urls, '-');


// 카테고리
if (strpos($_SERVER['REQUEST_URI'], '/adm/') === false) {
    if (isset($ca_id) && preg_match('/^\d+$/', $ca_id) && isset($w) && $w != "u") {
        $cate_id = implode('-', str_split($ca_id, 2)); // ex: 101010 → 10-10-10
        $rb_page_urls = 'shop-list-' . $cate_id;
    }
}

if (isset($bo_table) && $bo_table) {
    $rb_page_urls = 'bo-table-' . $bo_table;
} else if (isset($co_id) && $co_id) {
    $rb_page_urls = 'content-' . $co_id;
} else if (isset($gr_id) && $gr_id) {
    $rb_page_urls = 'group-' . $gr_id;
} else if (isset($it_id) && is_scalar($it_id) && (string) $it_id !== '' && isset($w) && $w != "u") {
    $rb_page_urls = 'item-' . (string) $it_id;
} else if(isset($ev_id) && $ev_id) {
    $rb_page_urls = 'event-' . $ev_id;
} else if(isset($fr_id) && $fr_id) {
    $rb_page_urls = 'form-' . $fr_id;
}


// 노드 신규등록
rb_auto_insert_node_if_inherited($rb_page_urls);

$rb_page_sql = "SELECT * FROM rb_topvisual WHERE v_code = '{$rb_page_urls}'";
$rb_v_info = sql_fetch($rb_page_sql);

// 상위노드 검색
function rb_has_topvisual_all($v_code) {
    $parts = explode('-', $v_code);

    // 가장 긴 prefix부터 하나씩 줄여가며 탐색
    for ($i = count($parts) - 1; $i > 0; $i--) {
        $parent_code = implode('-', array_slice($parts, 0, $i));

        $sql = "SELECT COUNT(*) as cnt FROM rb_topvisual
                WHERE v_code = '{$parent_code}' AND co_topvisual_all = 1";
        $row = sql_fetch($sql);

        if (isset($row['cnt']) && $row['cnt'] > 0) {
            return true;
        }
    }

    return false;
}

// 노드가 등록되지않은 경우 인서트 (접속 기준 상위 노드를 찾는다)
function rb_auto_insert_node_if_inherited($v_code, $table = 'rb_topvisual') {
    // 1. 현재 노드가 DB에 존재하는지 확인
    $chk_sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE v_code = '{$v_code}'";
    $chk = sql_fetch($chk_sql);

    if (isset($chk['cnt']) && $chk['cnt'] > 0) {
        return; // 이미 존재 → 등록 안함
    }

    $rb_page_url = $_SERVER['REQUEST_URI'];

    // 2. 상위 노드 중 co_topvisual_all = 1 인 노드 찾기
    $parts = explode('-', $v_code);
    for ($i = count($parts) - 1; $i > 0; $i--) {
        $parent_code = implode('-', array_slice($parts, 0, $i));

        $parent_sql = "SELECT * FROM {$table}
                       WHERE v_code = '{$parent_code}'
                         AND co_topvisual_all = 1
                       LIMIT 1";
        $parent = sql_fetch($parent_sql);

        if ($parent) {
            $v_url = $rb_page_url;
            $v_use = 1;
            $v_time = G5_TIME_YMDHIS;

            // 기본 필드
            $columns = ['v_code', 'v_name', 'v_url', 'v_use', 'co_topvisual_all', 'v_time'];
            $values  = ["'{$v_code}'", "''", "'{$v_url}'", "'{$v_use}'", "'0'", "'{$v_time}'"];

            // 스타일도 상속할 경우
            if (isset($parent['co_topvisual_style_all']) && intval($parent['co_topvisual_style_all']) === 1) {
                $style_fields = [
                    'co_topvisual_mt',
                    'co_topvisual_height',
                    'co_topvisual_width',
                    'co_topvisual_bl',
                    'co_topvisual_border',
                    'co_topvisual_radius',
                    'co_topvisual_m_color',
                    'co_topvisual_m_size',
                    'co_topvisual_m_font',
                    'co_topvisual_m_align',
                    'co_topvisual_s_color',
                    'co_topvisual_s_size',
                    'co_topvisual_s_font',
                    'co_topvisual_s_align',
                    'co_topvisual_bg_color',
                    'co_topvisual_main'
                ];

                foreach ($style_fields as $field) {
                    $columns[] = $field;
                    $values[]  = "'" . addslashes($parent[$field] ?? '') . "'";
                }
            }

            // INSERT 쿼리 실행
            $insert_sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ")
                           VALUES (" . implode(', ', $values) . ")";
            sql_query($insert_sql);

            // 파일 복사: /data/topvisual/{parent_code}.txt / .jpg → {v_code}.txt / .jpg
            $topvisual_path = G5_DATA_PATH . '/topvisual';
            $exts = ['txt', 'jpg'];

            foreach ($exts as $ext) {
                $source = "{$topvisual_path}/{$parent_code}.{$ext}";
                $target = "{$topvisual_path}/{$v_code}.{$ext}";

                if (file_exists($source)) {
                    @copy($source, $target);
                }
            }
            break;
        }
    }
}


// 상위노드명 출력
function rb_get_inherited_topvisual_node($v_code, $table = 'rb_topvisual') {
    $parts = explode('-', $v_code);

    for ($i = count($parts) - 1; $i > 0; $i--) {
        $parent_code = implode('-', array_slice($parts, 0, $i));

        $sql = "SELECT v_code, v_name, v_url FROM {$table}
                WHERE v_code = '{$parent_code}' AND co_topvisual_all = 1 LIMIT 1";
        $row = sql_fetch($sql);

        if ($row) {
            return $row; // 전체 정보 반환
        }
    }

    return null;
}


// 기본 조건: 현재 노드가 사용 또는 전체사용일 때
if (isset($rb_v_info['v_use']) && in_array(intval($rb_v_info['v_use']), [1, 2])) {
    $should_show_visual = true;
}

// 추가 조건: 상위 노드 중 co_topvisual_all = 1이 있는 경우
elseif (rb_has_topvisual_all($rb_page_urls)) {
    $should_show_visual = true;
}

$rb_v_info['topvisual_mt'] = !empty($rb_v_info['co_topvisual_mt']) ? $rb_v_info['co_topvisual_mt'] : '0'; // 상단영역 상단여백
$rb_v_info['topvisual_height'] = !empty($rb_v_info['co_topvisual_height']) ? $rb_v_info['co_topvisual_height'] : '200'; // 상단영역 세로크기
$rb_v_info['topvisual_width'] = !empty($rb_v_info['co_topvisual_width']) ? $rb_v_info['co_topvisual_width'] : ''; // 상단영역 가로크기
$rb_v_info['topvisual_bl'] = isset($rb_v_info['co_topvisual_bl']) ? $rb_v_info['co_topvisual_bl'] : '10'; // 상단영역 블라인드

$rb_v_info['topvisual_border'] = isset($rb_v_info['co_topvisual_border']) ? $rb_v_info['co_topvisual_border'] : '0'; // 상단영역 테두리
$rb_v_info['topvisual_radius'] = isset($rb_v_info['co_topvisual_radius']) ? $rb_v_info['co_topvisual_radius'] : '10'; // 상단영역 라운드

$rb_v_info['topvisual_m_color'] = !empty($rb_v_info['co_topvisual_m_color']) ? $rb_v_info['co_topvisual_m_color'] : '#ffffff'; // 상단영역 메인워딩 컬러
$rb_v_info['topvisual_m_size'] = !empty($rb_v_info['co_topvisual_m_size']) ? $rb_v_info['co_topvisual_m_size'] : '20'; // 상단영역 메인워딩 사이즈
$rb_v_info['topvisual_m_font'] = !empty($rb_v_info['co_topvisual_m_font']) ? $rb_v_info['co_topvisual_m_font'] : 'font-B'; // 상단영역 메인워딩 두께
$rb_v_info['topvisual_m_align'] = !empty($rb_v_info['co_topvisual_m_align']) ? $rb_v_info['co_topvisual_m_align'] : 'left'; // 상단영역 메인워딩 정렬

$rb_v_info['topvisual_s_color'] = !empty($rb_v_info['co_topvisual_s_color']) ? $rb_v_info['co_topvisual_s_color'] : '#ffffff'; // 상단영역 서브워딩 컬러
$rb_v_info['topvisual_s_size'] = !empty($rb_v_info['co_topvisual_s_size']) ? $rb_v_info['co_topvisual_s_size'] : '16'; // 상단영역 서브워딩 사이즈
$rb_v_info['topvisual_s_font'] = !empty($rb_v_info['co_topvisual_s_font']) ? $rb_v_info['co_topvisual_s_font'] : 'font-R'; // 상단영역 서브워딩 두께
$rb_v_info['topvisual_s_align'] = !empty($rb_v_info['co_topvisual_s_align']) ? $rb_v_info['co_topvisual_s_align'] : 'left'; // 상단영역 서브워딩 정렬

$rb_v_info['topvisual_bg_color'] = !empty($rb_v_info['co_topvisual_bg_color']) ? $rb_v_info['co_topvisual_bg_color'] : '#f9f9f9';
$rb_v_info['topvisual_style_all'] = !empty($rb_v_info['co_topvisual_style_all']) ? $rb_v_info['co_topvisual_style_all'] : '0';

/* } */

/* 2.2.3 { */

$rb_core['padding_top'] = isset($rb_config['co_padding_top']) ? $rb_config['co_padding_top'] : '';
$rb_core['padding_top_sub'] = isset($rb_config['co_padding_top_sub']) ? $rb_config['co_padding_top_sub'] : '';
$rb_core['padding_top_shop'] = isset($rb_config['co_padding_top_shop']) ? $rb_config['co_padding_top_shop'] : '';
$rb_core['padding_top_sub_shop'] = isset($rb_config['co_padding_top_sub_shop']) ? $rb_config['co_padding_top_sub_shop'] : '';

$rb_core['padding_btm'] = isset($rb_config['co_padding_btm']) ? $rb_config['co_padding_btm'] : '';
$rb_core['padding_btm_sub'] = isset($rb_config['co_padding_btm_sub']) ? $rb_config['co_padding_btm_sub'] : '';
$rb_core['padding_btm_shop'] = isset($rb_config['co_padding_btm_shop']) ? $rb_config['co_padding_btm_shop'] : '';
$rb_core['padding_btm_sub_shop'] = isset($rb_config['co_padding_btm_sub_shop']) ? $rb_config['co_padding_btm_sub_shop'] : '';

// 여백 인라인 스타일
function rb_inline_padding($arr, $key, $prop = null) {
    if (!is_array($arr) || !is_string($key) || $key === '') return '';

    $v = array_key_exists($key, $arr) ? $arr[$key] : '';
    if ($v === '' || $v === null || !is_scalar($v)) return '';

    $v = trim((string)$v);
    if ($v === '' || !is_numeric($v)) return '';

    $n = (float)$v;
    if ($n < 0) return '';

    // 방향 결정
    $css = '';
    if ($prop !== null && $prop !== '') {
        switch (strtolower(trim($prop))) {
            case 'top':    $css = 'padding-top';    break;
            case 'bottom': $css = 'padding-bottom'; break;
            case 'left':   $css = 'padding-left';   break;
            case 'right':  $css = 'padding-right';  break;
            default: return ''; // 잘못된 값이면 출력 안 함
        }
    } else {
        $tokens = preg_split('/[_\-]+/', strtolower($key)) ?: array(strtolower($key));
        if     (in_array('bottom', $tokens, true) || in_array('btm', $tokens, true)) $css = 'padding-bottom';
        elseif (in_array('left',   $tokens, true))                                   $css = 'padding-left';
        elseif (in_array('right',  $tokens, true))                                   $css = 'padding-right';
        elseif (in_array('top',    $tokens, true))                                   $css = 'padding-top';
        else return ''; // 방향 못 찾으면 출력 안 함
    }

    return $css . ':' . (int)$n . 'px;';
}

/* } */


//영카트 사용여부
$rb_core['layout_shop'] = !empty($rb_config['co_layout_shop']) ? $rb_config['co_layout_shop'] : ''; // 레이아웃(메인)
$rb_core['layout_hd_shop'] = !empty($rb_config['co_layout_hd_shop']) ? $rb_config['co_layout_hd_shop'] : ''; // 레이아웃(헤더)
$rb_core['layout_ft_shop'] = !empty($rb_config['co_layout_ft_shop']) ? $rb_config['co_layout_ft_shop'] : ''; // 레이아웃(푸터)
//$rb_core['padding_top_shop'] = !empty($rb_config['co_main_padding_top_shop']) ? $rb_config['co_main_padding_top_shop'] : "0";

$rb_core['color'] = str_replace('#', '', $rb_core['color']); // # 제거 2.1.4
$rb_core['header'] = str_replace('#', '', $rb_core['header']); // # 제거 2.1.4

if(isset($rb_core['tb_width']) && $rb_core['tb_width'] == "100") {
    $tb_width_inner = "100%";
    $tb_width_padding = "padding:0px 40px";
} else {
    $tb_width_inner = $rb_core['tb_width']."px";
    $tb_width_padding = "";
}

if(isset($rb_core['main_width']) && $rb_core['main_width'] == "100") {
    $rb_main_wide = "rb_main_wide";
} else {
    $rb_main_wide = "";
}

if(isset($rb_core['sub_width']) && $rb_core['sub_width'] == "100") {
    $rb_sub_wide = "rb_sub_wide";
} else {
    $rb_sub_wide = "";
}

// SEO설정
$sql_seo = " select * from rb_seo limit 1";
$seo = sql_fetch($sql_seo);

/* 2.2.4 { */
$rb_core['main_bg'] = !empty($rb_config['co_main_bg']) ? $rb_config['co_main_bg'] : '#ffffff'; // 메인배경컬러
$rb_core['sub_bg'] = !empty($rb_config['co_sub_bg']) ? $rb_config['co_sub_bg'] : '#ffffff'; // 서브배경컬러
$rb_core['gap_mo'] = !empty($rb_config['co_gap_mo']) ? $rb_config['co_gap_mo'] : '0'; // 간격

function rb_member_level_select($name, $start_id = 0, $end_id = 10, $selected = "", $event = "")
{
    global $g5;

    $str = "\n<select class=\"select select_tiny\" style=\"margin-left:0px\" id=\"{$name}\" name=\"{$name}\"";
    if ($event) {
        $str .= " $event";
    }
    $str .= ">\n";

    $str .= '<option value=""';
    $str .= ' selected="selected"';
    $str .= ">레벨</option>\n";

    for ($i = $start_id; $i <= $end_id; $i++) {
        $str .= '<option value="' . $i . '"';
        if ($i == $selected) {
            $str .= ' selected="selected"';
        }
        $str .= ">{$i}</option>\n";
    }
    $str .= "</select>\n";
    return $str;
}
/* } */

/*********************************************/


// SIR @트리플님 코드적용 // 출처 : https://sir.kr/g5_tip/21657
add_event('tail_sub', 'prism_tail_sub', G5_HOOK_DEFAULT_PRIORITY);
add_replace('html_purifier_result', 'prism_html_purifier_result', 10, 3);

// 비에디터 게시판 글쓰기 textarea의 최초 높이도 내용에 맞춰 계산한다.
add_event('tail_sub', 'rb_board_plain_textarea_autoheight_tail', G5_HOOK_DEFAULT_PRIORITY);
function rb_board_plain_textarea_autoheight_tail()
{
    global $bo_table, $is_dhtml_editor;

    $script_name = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
    if ($script_name !== 'write.php' || empty($bo_table) || !empty($is_dhtml_editor)) return;

    echo <<<'RBHTML'
<script>
(function () {
    'use strict';

    var textarea;
    var resizeTimer;

    function resizeContentTextarea() {
        if (!textarea || !document.documentElement.contains(textarea)) {
            var form = document.getElementById('fwrite');
            textarea = form ? form.querySelector('textarea#wr_content') : null;
        }
        if (!textarea || textarea.offsetParent === null) return;

        var computed = window.getComputedStyle ? window.getComputedStyle(textarea) : null;
        var minHeight = computed ? parseFloat(computed.minHeight) : 0;
        if (!isFinite(minHeight) || minHeight < 300) minHeight = 300;

        textarea.style.overflowY = 'hidden';
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(textarea.scrollHeight, minHeight) + 'px';
    }

    function scheduleResize() {
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(function () {
                resizeContentTextarea();
                window.requestAnimationFrame(resizeContentTextarea);
            });
        } else {
            setTimeout(resizeContentTextarea, 0);
        }
    }

    function init() {
        var form = document.getElementById('fwrite');
        textarea = form ? form.querySelector('textarea#wr_content') : null;
        if (!textarea || textarea.getAttribute('data-rb-autoheight') === '1') return;

        textarea.setAttribute('data-rb-autoheight', '1');
        textarea.addEventListener('input', resizeContentTextarea);
        textarea.addEventListener('change', resizeContentTextarea);
        scheduleResize();
        setTimeout(resizeContentTextarea, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.addEventListener('load', scheduleResize);
    window.addEventListener('pageshow', scheduleResize);
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resizeContentTextarea, 80);
    });
})();
</script>
RBHTML;
}

// 게시판 글쓰기 검증 실패 시 화면만 이동하거나 작성 버튼이 잠기는 현상을 방지한다.
add_event('tail_sub', 'rb_board_write_submit_guard_tail', G5_HOOK_DEFAULT_PRIORITY);
function rb_board_write_submit_guard_tail()
{
    global $bo_table;

    $script_name = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
    if ($script_name !== 'write.php' || empty($bo_table)) return;

    echo <<<'RBHTML'
<script>
(function () {
    'use strict';

    var form = document.getElementById('fwrite');
    if (!form || form.getAttribute('data-rb-submit-guard') === '1') return;

    form.setAttribute('data-rb-submit-guard', '1');

    var submitButton = form.querySelector('#btn_submit, button[type="submit"], input[type="submit"]');
    var submitLocked = false;
    var invalidNoticePending = false;
    var lastScrollX = window.pageXOffset || document.documentElement.scrollLeft || 0;
    var lastScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;

    function rememberScroll() {
        lastScrollX = window.pageXOffset || document.documentElement.scrollLeft || 0;
        lastScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
    }

    function restoreScroll() {
        window.scrollTo(lastScrollX, lastScrollY);
    }

    function restoreSubmitButton() {
        submitLocked = false;
        if (submitButton) submitButton.disabled = false;
    }

    function focusWithoutScroll(field) {
        if (!field || typeof field.focus !== 'function') return;

        try {
            field.focus({preventScroll: true});
        } catch (ignore) {
            field.focus();
        }
        restoreScroll();
    }

    function showValidationMessage(field) {
        var message = field && field.validationMessage
            ? field.validationMessage
            : '필수 입력항목을 확인해 주세요.';
        var afterClose = function () {
            focusWithoutScroll(field);
        };

        if (typeof window.alert === 'function' && window.alert.length > 1) {
            window.alert(message, afterClose);
        } else {
            window.alert(message);
            afterClose();
        }
    }

    form.addEventListener('invalid', function (event) {
        rememberScroll();
        event.preventDefault();
        restoreSubmitButton();

        if (invalidNoticePending) return;
        invalidNoticePending = true;

        window.setTimeout(function () {
            invalidNoticePending = false;
            restoreScroll();
            showValidationMessage(event.target);
        }, 0);
    }, true);

    if (submitButton) {
        submitButton.addEventListener('pointerdown', rememberScroll, true);
        submitButton.addEventListener('mousedown', rememberScroll, true);
        submitButton.addEventListener('touchstart', rememberScroll, true);
        submitButton.addEventListener('click', rememberScroll, true);
    }

    if (typeof window.fwrite_submit === 'function' && !window.fwrite_submit.rbSubmitGuard) {
        var originalSubmit = window.fwrite_submit;

        window.fwrite_submit = function () {
            if (submitLocked) return false;

            rememberScroll();

            var result = false;
            try {
                result = originalSubmit.apply(this, arguments);
            } catch (error) {
                restoreSubmitButton();
                restoreScroll();
                if (window.console && typeof window.console.error === 'function') {
                    window.console.error('게시글 작성 검증 중 오류가 발생했습니다.', error);
                }
                window.alert('작성 내용을 처리하지 못했습니다. 입력 내용을 확인한 뒤 다시 시도해 주세요.');
                return false;
            }

            if (result !== true) {
                restoreSubmitButton();
                window.setTimeout(restoreScroll, 0);
                if (window.requestAnimationFrame) window.requestAnimationFrame(restoreScroll);
                return false;
            }

            submitLocked = true;
            if (submitButton) submitButton.disabled = true;
            return true;
        };
        window.fwrite_submit.rbSubmitGuard = true;
    }

    window.addEventListener('pageshow', restoreSubmitButton);
}());
</script>
RBHTML;
}

function prism_script(){
    add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/rb/rb.mod/prism/prism.css">', -2);
    $sh = '<script src="'.G5_URL.'/rb/rb.mod/prism/prism.js"></script>'.PHP_EOL;
    $sh .= '<script>var is_SyntaxHighlighter = true;</script>';
    add_javascript($sh, 0);
}
function prism_tail_sub(){
    global $wr_id;
    if($_SERVER['SCRIPT_NAME'] != '/bbs/board.php' || !$wr_id) return;
    prism_script();
}
function PrismJS($m) {
    $str = isset($m[3]) ? $m[3] : '';
    if(!$str)
        return;
    $str = stripslashes($str);
    $str = preg_replace("/(<br>|<br \/>|<br\/>|<p>)/i", "\n", $str);
    $str = preg_replace("/(<div>|<\/div>|<\/p>)/i", "", $str);
    $str = str_replace(" ", " ", $str);
    $str = str_replace("/</", "<", $str);
    $str = str_replace("/[/", "&lsqb;", $str);
    $str = str_replace("/{/", "&lcub;", $str);
    if(!$str)
        return;
    //$brush = isset($m[2]) ? strtolower(trim($m[2])) : 'html';
    $brush = 'php';
    //prism_script();
    return '<div class="line-numbers"><pre><code class="language-'.$brush.'">'.$str.'</code></pre></div>'.PHP_EOL;
}
function prism_html_purifier_result($str){
    $content = preg_replace_callback("/(\[code\]|\[code=(.*)\])(.*)\[\/code\]/iUs", "PrismJS", $str); // PrismJS
    return $content;
}

// 문자 발송 함수 @SIR 플래토님 코드사용 출처 : https://sir.kr/g5_tip/8262
function smsSend($sHp, $rHp, $msg) {
    global $g5, $config;
    $rtn = "";
    try {
        $send_hp = str_replace("-","",$sHp); // - 제거
        $recv_hp = str_replace("-","",$rHp); // - 제거
        $SMS = new SMS; // SMS 객체 생성
        $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);
        $SMS->Add($recv_hp, $send_hp, $config['cf_icode_id'], iconv("utf-8", "euc-kr", stripslashes($msg)), "");
        $SMS->Send();
        $rtn = true;
    }
    catch(Exception $e) {
        alert("처리중 문제가 발생했습니다.".$e->getMessage());
        $rtn = false;
    }
    return $rtn;
}

// 새글에 NEW 아이콘
function get_new_ico($bo_table, $ca_name) {

    global $g5;


    $new_icon = '';
    $bbs = sql_fetch("select * from {$g5['board_table']} where bo_table = '{$bo_table}'");


    if($bbs && isset($bbs['bo_table'])) {
        $write_table = $g5['write_prefix'].$bbs['bo_table'];
        if(isset($ca_name) && !empty($ca_name)) {
            $time = sql_fetch("select * from {$write_table} where wr_is_comment = 0 and ca_name = '{$ca_name}' order by wr_id desc limit 1");
        } else {
            $time = sql_fetch("select * from {$write_table} where wr_is_comment = 0 order by wr_id desc limit 1");
        }
    }


    if (isset($bbs['bo_new']) && isset($time['wr_datetime']) && $time['wr_datetime'] >= date("Y-m-d H:i:s", G5_SERVER_TIME - ($bbs['bo_new'] * 3600))) {
        $new_icon = '<span class="gnb_new_ico">n</span>';
    }


    return $new_icon;
}


// 전체 URL (SEO)
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $requestUri = $_SERVER['REQUEST_URI'];

    return $protocol . $host . $requestUri;
}


// 안읽은 쪽지
if ($is_member) {
    if( isset($member['mb_memo_cnt']) ){
        $memo_not_read = $member['mb_memo_cnt'];
    } else {
        $memo_not_read = get_memo_not_read($member['mb_id']);
    }
}

// 날자계산
function passing_time($datetime) {
	$time_lag = time() - strtotime($datetime);

	if($time_lag < 60) {
		$posting_time = "방금";
	} else if($time_lag >= 60 and $time_lag < 3600) {
		$posting_time = floor($time_lag/60)."분 전";
	} else if($time_lag >= 3600 and $time_lag < 86400) {
		$posting_time = floor($time_lag/3600)."시간 전";
	} else if($time_lag >= 86400 and $time_lag < 2419200) {
		$posting_time = floor($time_lag/86400)."일 전";
	} else {
		$posting_time = date("Y.m.d", strtotime($datetime));
	}

	return $posting_time;
}

// 날자계산2
function passing_time2($datetime) {
	$time_lag = time() - strtotime($datetime);

	if($time_lag < 60) {
		$posting_time = "방금<br>".date("Y.m.d", strtotime($datetime));
	} else if($time_lag >= 60 and $time_lag < 3600) {
		$posting_time = floor($time_lag/60)."분 전<br>".date("Y.m.d", strtotime($datetime));
	} else if($time_lag >= 3600 and $time_lag < 86400) {
		$posting_time = floor($time_lag/3600)."시간 전<br>".date("Y.m.d", strtotime($datetime));
	} else if($time_lag >= 86400 and $time_lag < 2419200) {
		$posting_time = floor($time_lag/86400)."일 전<br>".date("Y.m.d", strtotime($datetime));
	} else {
        $posting_time = "오래 전<br>".date("Y.m.d", strtotime($datetime));
	}

	return $posting_time;
}

// 날자계산3
function passing_time3($datetime) {
	$time_lag = time() - strtotime($datetime);

	if($time_lag < 60) {
		$posting_time = "방금";
	} else if($time_lag >= 60 and $time_lag < 3600) {
		$posting_time = floor($time_lag/60)."분 전";
	} else if($time_lag >= 3600 and $time_lag < 86400) {
		$posting_time = floor($time_lag/3600)."시간 전";
	} else if($time_lag >= 86400 and $time_lag < 2419200) {
		$posting_time = floor($time_lag/86400)."일 전";
	} else {
        $posting_time = "오래 전";
	}

	return $posting_time;
}

// 회원 게시물 갯수
function wr_cnt($mb_id, $type){
    global $g5;

    $wr_sum = 0;
    $sql = sql_query(" select bo_table from {$g5['board_table']} ");

    while($row = sql_fetch_array($sql)) {

        $write_table = $g5['write_prefix'] . $row['bo_table'];

        if($type == "w") {
            $sql2 = " select count(*) as cnt from {$write_table} where mb_id = '{$mb_id}' and wr_is_comment = 0 ";
        } else if ($type == "c") {
            $sql2 = " select count(*) as cnt from {$write_table} where mb_id = '{$mb_id}' and wr_is_comment = 1 ";
        }
        $wr = sql_fetch($sql2);
        $wr_sum += $wr['cnt'];
    }

    return $wr_sum;
}

//스크랩 갯수
function rb_get_scrap_count($mb_id)
{
    global $g5;

    $mb_id = trim((string)$mb_id);
    if ($mb_id === '') return 0;

    // // SQL 인젝션 방지
    if (function_exists('sql_escape_string')) {
        $mb_id_esc = sql_escape_string($mb_id);
    } else {
        // // 혹시 모를 환경 대비(그누보드엔 보통 sql_escape_string 존재)
        $mb_id_esc = addslashes($mb_id);
    }

    $sql = " select count(*) as cnt
             from {$g5['scrap_table']}
             where mb_id = '{$mb_id_esc}' ";

    $row = sql_fetch($sql);
    return isset($row['cnt']) ? (int)$row['cnt'] : 0;
}

// 생성된 게시판 목록조회
function rb_board_list($bo_tables) {
    global $g5;

    $str = ""; // 초기화

    $sql = " select bo_table, bo_subject from {$g5['board_table']} group by bo_table order by bo_table asc";
    $result = sql_query($sql);

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        if($bo_tables == $row['bo_table']) {
            $str .= "<option value='$row[bo_table]' selected";
        } else {
            $str .= "<option value='$row[bo_table]'";
        }

        $str .= ">$row[bo_subject] ($row[bo_table])</option>";
    }
    return $str;
}

// 카테고리 목록조회
function rb_sca_list($md_bo_table, $md_sca) {
    global $g5;

    $str = ""; // 초기화

    $res_ca = sql_fetch (" select bo_category_list from {$g5['board_table']} where bo_table = '{$md_bo_table}' and bo_use_category = '1' ");
    $cat = $res_ca['bo_category_list'];
    $cat_opt = explode("|", $cat);

    if($cat) {
        foreach($cat_opt as $option):
            if($md_sca == $option) {
                $str .= "<option value='$option' selected>$option</option>";
            } else {
                $str .= "<option value='$option'>$option</option>";
            }
        endforeach;
    }

    return $str;
}


// 생성된 투표 목록조회
function rb_poll_list($poll_id) {
    global $g5;
    $sql = " select po_id, po_subject from {$g5['poll_table']} where po_use = '1' order by po_id asc";
    $result = sql_query($sql);

    $str = ""; // 초기화

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        if($poll_id == $row['po_id']) {
            $str .= "<option value='$row[po_id]' selected";
        } else {
            $str .= "<option value='$row[po_id]'";
        }

        $str .= ">$row[po_subject]</option>";
    }
    return $str;
}


// 생성된 배너그룹 목록조회
function rb_banner_group_list($bn_position) {
    $sql = " select bn_position from rb_banner where bn_position NOT IN ('개별출력', '미출력') group by bn_position order by bn_position asc ";
    $result = sql_query($sql);

    $str = ""; // 초기화

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        if($bn_position == $row['bn_position']) {
            $str .= "<option value='$row[bn_position]' selected";
        } else {
            $str .= "<option value='$row[bn_position]'";
        }

        $str .= ">$row[bn_position]</option>";
    }
    return $str;
}

// 생성된 배너 목록조회
function rb_banner_list($bn_position) {
    $sql = " select bn_id, bn_position, bn_alt from rb_banner where bn_position NOT IN ('미출력') group by bn_position order by bn_id asc";
    $result = sql_query($sql);

    $str = ""; // 초기화

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        if($bn_position == $row['bn_position']) {
            $str .= "<option value='$row[bn_position]' selected";
        } else {
            $str .= "<option value='$row[bn_position]'";
        }

        $str .= ">$row[bn_position]</option>";
    }
    return $str;
}

// 생성된 개별출력 배너 목록조회
function rb_banner_id_list($bn_id) {
    $sql = " select bn_id, bn_position, bn_alt from rb_banner where bn_position = '개별출력' order by bn_id asc";
    $result = sql_query($sql);

    $str = ""; // 초기화

    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        if($bn_id == $row['bn_id']) {
            $str .= "<option value='$row[bn_id]' selected";
        } else {
            $str .= "<option value='$row[bn_id]'";
        }

        $str .= ">$row[bn_alt] ($row[bn_id]) </option>";
    }
    return $str;
}



// 배너가 있는지 검사
function rb_banner_select_is($bn_position) {
    $sql = sql_fetch("SELECT COUNT(*) as cnt FROM rb_banner where bn_position NOT IN ('미출력') AND bn_position = '{$bn_position}'");
    $str = "false"; // 기본값 설정

    if ($sql['cnt'] > 0) {
        $str = "true";
    }

    return $str;
}


// 디렉토리 조회
function rb_dir_select($skin_gubun, $selected = '')
{
    global $config;

    $str = "";

    $skins = array();


        $dirs = rb_skin_dir($skin_gubun, G5_THEME_PATH . '/');
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'theme/' . $dir;
            }
        }


    $skins = array_merge($skins, rb_skin_dir($skin_gubun));


    for ($i = 0; $i < count($skins); $i++) {

        if (preg_match('#^theme/(.+)$#', $skins[$i], $match)) {
            $text = $match[1];
        }

        if(strpos($skins[$i], "theme/") !== false) {
            if (!isset($str)) {
                $str = '';
            }
            $str .= option_selected($text, $selected, $text);
        }
    }

    return $str;
}


// 디렉토리 조회 (영카트)
function rb_dir_select_shop($skin_gubun, $selected = '')
{
    global $config;

    $str = "";

    $skins = array();


        $dirs = rb_skin_dir($skin_gubun, G5_THEME_PATH . '/shop/');
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'theme/' . $dir;
            }
        }


    $skins = array_merge($skins, rb_skin_dir($skin_gubun));


    for ($i = 0; $i < count($skins); $i++) {

        if (preg_match('#^theme/(.+)$#', $skins[$i], $match)) {
            $text = $match[1];
        }

        if(strpos($skins[$i], "theme/") !== false) {
            if (!isset($str)) {
                $str = '';
            }
            $str .= option_selected($text, $selected, $text);
        }
    }

    return $str;
}



// 스킨디렉토리에 지정한 스킨이 있는지 여부 검사
function rb_skin_select_is($skin_gubun, $selected = '')
{
    global $config;

    $skins = array();
    $str = "false"; // 기본값 설정

    if (defined('G5_THEME_PATH') && $config['cf_theme']) {
        $dirs = rb_skin_dir($skin_gubun, G5_THEME_PATH . '/' . G5_SKIN_DIR);
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'theme/' . $dir;
            }
        }
    }

    $skins = array_merge($skins, rb_skin_dir($skin_gubun));

    for ($i = 0; $i < count($skins); $i++) {
        if (strpos($skins[$i], "theme/") !== false) {
            if ($skins[$i] == $selected) {
                $str = "true";
            }
        }
    }

    return $str;
}




// 스킨디렉토리 조회
function rb_skin_select($skin_gubun, $selected = '')
{
    global $config;

    $str = "";

    $skins = array();

    if (defined('G5_THEME_PATH') && $config['cf_theme']) {
        $dirs = rb_skin_dir($skin_gubun, G5_THEME_PATH . '/' . G5_SKIN_DIR);
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'theme/' . $dir;
            }
        }
    }

    $skins = array_merge($skins, rb_skin_dir($skin_gubun));


    for ($i = 0; $i < count($skins); $i++) {

        if (preg_match('#^theme/(.+)$#', $skins[$i], $match)) {
            $text = $match[1];
        }

        if(strpos($skins[$i], "theme/") !== false) {
            $str .= option_selected($skins[$i], $selected, $text);
        }
    }

    return $str;
}



// 스킨디렉토리 조회 내부함수
function rb_skin_dir($skin, $skin_path = G5_SKIN_PATH)
{
    global $g5;

    $result_array = array();

    $dirname = $skin_path . '/' . $skin . '/';
    if (!is_dir($dirname)) {
        return array();
    }

    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($dirname . $file)) {
            $result_array[] = $file;
        }
    }
    closedir($handle);
    sort($result_array);

    return $result_array;
}



// 위젯디렉토리에 지정한 위젯이 있는지 여부 검사
function rb_widget_select_is($skin_gubun, $selected = '')
{
    global $config;

    $skins = array();
    $str = "false"; // 기본값 설정

    $dirs = rb_widget_dir($skin_gubun, G5_PATH . '/rb');
    if (!empty($dirs)) {
        foreach ($dirs as $dir) {
            $skins[] = 'rb.widget/' . $dir;
        }
    }

    $skins = array_merge($skins, rb_skin_dir($skin_gubun));

    for ($i = 0; $i < count($skins); $i++) {
        if (strpos($skins[$i], "rb.widget/") !== false) {
            if ($skins[$i] == $selected) {
                $str = "true";
                break; // 선택된 스킨을 찾으면 반복문을 종료
            }
        }
    }

    return $str;
}



// 위젯디렉토리 조회
function rb_widget_select($skin_gubun, $selected = '')
{
    global $config;

    $str = "";

    $skins = array();


        $dirs = rb_widget_dir($skin_gubun, G5_PATH . '/rb');
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'rb.widget/' . $dir;
            }
        }


    $skins = array_merge($skins, rb_widget_dir($skin_gubun));


    for ($i = 0; $i < count($skins); $i++) {

        if (preg_match('#^rb.widget/(.+)$#', $skins[$i], $match)) {
            $text = $match[1];
        }

        if(strpos($skins[$i], "rb.widget/") !== false) {
            $str .= option_selected($skins[$i], $selected, $text);
        }
    }

    return $str;
}

// 위젯디렉토리 조회 내부함수
function rb_widget_dir($skin, $skin_path = G5_SKIN_PATH)
{
    global $g5;

    $result_array = array();

    $dirname = $skin_path . '/' . $skin . '/';
    if (!is_dir($dirname)) {
        return array();
    }

    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($dirname . $file)) {
            $result_array[] = $file;
        }
    }
    closedir($handle);
    sort($result_array);

    return $result_array;
}



// 배너스킨 디렉토리 조회
function rb_banner_skin_select($skin_gubun, $selected = '')
{
    global $config;

    $str = "";

    $skins = array();


        $dirs = rb_banner_dir($skin_gubun, G5_PATH . '/rb');
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $skins[] = 'rb.mod/banner/skin/' . $dir;
            }
        }


    $skins = array_merge($skins, rb_widget_dir($skin_gubun));


    for ($i = 0; $i < count($skins); $i++) {

        if (preg_match('#^rb.mod/banner/skin/(.+)$#', $skins[$i], $match)) {
            $text = $match[1];
        }

        if(strpos($skins[$i], "rb.mod/banner/skin/") !== false) {
            $str .= option_selected($skins[$i], $selected, $text);
        }
    }

    return $str;
}

// 배너 스킨 디렉토리 조회 내부함수
function rb_banner_dir($skin, $skin_path = G5_SKIN_PATH)
{
    global $g5;

    $result_array = array();

    $dirname = $skin_path . '/' . $skin . '/';
    if (!is_dir($dirname)) {
        return array();
    }

    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($dirname . $file)) {
            $result_array[] = $file;
        }
    }
    closedir($handle);
    sort($result_array);

    return $result_array;
}

// 바이트 환산
function byteFormat($bytes, $unit = "", $decimals = 2) {
    $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);
    $value = 0;
    if ($bytes > 0) {
        // unit이 비어있으면 자동으로 적절한 단위 선택
        if (empty($unit) || !array_key_exists($unit, $units)) {
            $pow = floor(log($bytes)/log(1024));
            $unit = array_search($pow, $units);
        }
        $value = ($bytes/pow(1024,floor($units[$unit])));
    }
    if (!is_numeric($decimals) || $decimals < 0) {
        $decimals = 2;
    }
    return sprintf('%.' . $decimals . 'f '.$unit, $value);
}

// 최신글 함수(메인용)
function rb_latest($bo_table, $skin_dir='', $rows=10, $subject_len=40, $cache_time=1, $options='', $md_sca='', $md_order='', $rb_module_table='', $is_notice=0)
{
    global $g5;

    if (!$skin_dir) $skin_dir = 'basic';
    $time_unit = 3600;  // 1시간으로 고정

    if(preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            if(!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if(G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
        }
    }

    $caches = false;

    if(G5_USE_CACHE) {
        $cache_file_name = "latest-{$bo_table}-{$skin_dir}-{$md_sca}-{$md_order}-{$rows}-{$subject_len}-{$is_notice}-".g5_cache_secret_key();
        $caches = g5_get_cache($cache_file_name, (int) $time_unit * (int) $cache_time);
        $cache_list = isset($caches['list']) ? $caches['list'] : array();
        g5_latest_cache_data($bo_table, $cache_list);
    }

    if( $caches === false ){

        $list = array();

        $board = get_board_db($bo_table, true);
        if( ! $board ){
            return '';
        }

        $bo_subject = get_text($board['bo_subject']);
        $tmp_write_table = $g5['write_prefix'] . $bo_table;
        $notice_ids = array_filter(array_map('trim', explode(',', $board['bo_notice'])));
        $notice_ids_str = count($notice_ids) ? implode(',', $notice_ids) : '';



        if ($is_notice == 1 && $notice_ids_str) {
            // 공지글 상단 고정, 정렬옵션 상관없이 항상 위에!
            $sql = "SELECT * FROM {$tmp_write_table} WHERE wr_is_comment = 0";
            if($md_sca) $sql .= " AND ca_name = '{$md_sca}' ";
            // 공지글이면 0, 아니면 1 → 무조건 공지글이 먼저!
            $sql .= " ORDER BY (CASE WHEN wr_id IN ($notice_ids_str) THEN 0 ELSE 1 END), ";
            if($md_order) {
                $sql .= " {$md_order} ";   // 일반글은 정렬옵션 적용
            } else {
                $sql .= " wr_num ";
            }
            $sql .= " LIMIT 0, {$rows} ";

            $result = sql_query($sql);
            for ($i=0; $row = sql_fetch_array($result); $i++) {
                unset($row['wr_password']);
                $row['wr_email'] = '';
                if (strstr($row['wr_option'], 'secret')){
                    $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                    $row['file'] = array('count'=>0);
                }
                $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
                $list[$i]['first_file_thumb'] = (isset($row['wr_file']) && $row['wr_file']) ? get_board_file_db($bo_table, $row['wr_id'], 'bf_file, bf_content', "and bf_type in (1, 2, 3, 18) ", true) : array('bf_file'=>'', 'bf_content'=>'');
                $list[$i]['bo_table'] = $bo_table;
                $list[$i]['is_notice'] = in_array($row['wr_id'], $notice_ids) ? true : false;
                if(! isset($list[$i]['icon_file'])) $list[$i]['icon_file'] = '';
            }
        } else {
            // 그냥 최신글 LIMIT개
            $sql = "SELECT * FROM {$tmp_write_table} WHERE wr_is_comment = 0 ";
            if($md_sca) $sql .= " AND ca_name = '{$md_sca}' ";
            if($md_order) {
                $sql .= " ORDER BY {$md_order} ";
            } else {
                $sql .= " ORDER BY wr_num ";
            }
            $sql .= " LIMIT 0, {$rows} ";

            $result = sql_query($sql);
            for ($i=0; $row = sql_fetch_array($result); $i++) {
                unset($row['wr_password']);
                $row['wr_email'] = '';
                if (strstr($row['wr_option'], 'secret')){
                    $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                    $row['file'] = array('count'=>0);
                }
                $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
                $list[$i]['first_file_thumb'] = (isset($row['wr_file']) && $row['wr_file']) ? get_board_file_db($bo_table, $row['wr_id'], 'bf_file, bf_content', "and bf_type in (1, 2, 3, 18) ", true) : array('bf_file'=>'', 'bf_content'=>'');
                $list[$i]['bo_table'] = $bo_table;
                $list[$i]['is_notice'] = in_array($row['wr_id'], $notice_ids) ? true : false;
                if(! isset($list[$i]['icon_file'])) $list[$i]['icon_file'] = '';
            }
        }

        g5_latest_cache_data($bo_table, $list);

        if(G5_USE_CACHE) {
            $caches = array(
                'list' => $list,
                'bo_subject' => sql_escape_string($bo_subject),
            );
            g5_set_cache($cache_file_name, $caches, (int) $time_unit * (int) $cache_time);
        }
    } else {
        $list = $cache_list;
        $bo_subject = (is_array($caches) && isset($caches['bo_subject'])) ? $caches['bo_subject'] : '';
    }

    ob_start();
    $rb_module_table = isset($rb_module_table) ? $rb_module_table : '';
    include $latest_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}




function rb_latest_tabs($skin_dir = '', $json_list = '', $rows = 10, $subject_len = 40, $cache_time = 1, $options = '', $md_order = '', $rb_module_table = '', $is_notice = 0) {
    global $g5;

    if (!$skin_dir) $skin_dir = 'basic';
    if (!$json_list) return '';

    $time_unit = 3600; // 1시간

    if (preg_match('#^theme/(.+)$#', $skin_dir, $match)) {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_THEME_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest_tabs/'.$match[1];
            if (!is_dir($latest_skin_path))
                $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest_tabs/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        } else {
            $latest_skin_path = G5_THEME_PATH.'/'.G5_SKIN_DIR.'/latest_tabs/'.$match[1];
            $latest_skin_url = str_replace(G5_PATH, G5_URL, $latest_skin_path);
        }
        $skin_dir = $match[1];
    } else {
        if (G5_IS_MOBILE) {
            $latest_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest_tabs/'.$skin_dir;
            $latest_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest_tabs/'.$skin_dir;
        } else {
            $latest_skin_path = G5_SKIN_PATH.'/latest_tabs/'.$skin_dir;
            $latest_skin_url  = G5_SKIN_URL.'/latest_tabs/'.$skin_dir;
        }
    }

    $tab_items = json_decode($json_list, true);
    if (!is_array($tab_items) || !count($tab_items)) return '';

    $tabs = [];
    foreach ($tab_items as $item) {
        if (strpos($item, '||') !== false) {
            list($bo_table, $md_sca) = explode('||', $item);
        } else {
            $bo_table = $item;
            $md_sca = '';
        }

        $board = get_board_db($bo_table, true);
        if (!$board) continue;

        $bo_subject = get_text($board['bo_subject']);

        $list = [];
        $tmp_write_table = $g5['write_prefix'] . $bo_table;

        // 공지글 wr_id 추출
        $notice_ids = array_filter(array_map('trim', explode(',', $board['bo_notice'])));
        $notice_ids_str = count($notice_ids) ? implode(',', $notice_ids) : '';

        if ($is_notice == 1 && $notice_ids_str) {
            // 공지글 상단고정, 나머지는 정렬옵션
            $sql = "SELECT * FROM {$tmp_write_table} WHERE wr_is_comment = 0";
            if($md_sca) $sql .= " AND ca_name = '{$md_sca}' ";
            // 공지글은 0, 그 외는 1로 두고 정렬옵션으로 나머지 정렬
            $sql .= " ORDER BY (CASE WHEN wr_id IN ($notice_ids_str) THEN 0 ELSE 1 END), ";
            if($md_order) {
                $sql .= " {$md_order} ";
            } else {
                $sql .= " wr_num ";
            }
            $sql .= " LIMIT 0, {$rows} ";
        } else {
            // 그냥 최신글 LIMIT개
            $sql = "SELECT * FROM {$tmp_write_table} WHERE wr_is_comment = 0 ";
            if($md_sca) $sql .= " AND ca_name = '{$md_sca}' ";
            if($md_order) {
                $sql .= " ORDER BY {$md_order} ";
            } else {
                $sql .= " ORDER BY wr_num ";
            }
            $sql .= " LIMIT 0, {$rows} ";
        }

        $result = sql_query($sql);

        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            unset($row['wr_password']);
            $row['wr_email'] = '';
            if (strstr($row['wr_option'], 'secret')) {
                $row['wr_content'] = $row['wr_link1'] = $row['wr_link2'] = '';
                $row['file'] = array('count'=>0);
            }
            $list[$i] = get_list($row, $board, $latest_skin_url, $subject_len);
            $list[$i]['first_file_thumb'] = (isset($row['wr_file']) && $row['wr_file']) ? get_board_file_db($bo_table, $row['wr_id'], 'bf_file, bf_content', "and bf_type in (1, 2, 3, 18) ", true) : array('bf_file'=>'', 'bf_content'=>'');
            $list[$i]['bo_table'] = $bo_table;
            $list[$i]['is_notice'] = in_array($row['wr_id'], $notice_ids) ? true : false;
            if (!isset($list[$i]['icon_file'])) $list[$i]['icon_file'] = '';
        }

        $tabs[] = [
            'bo_table' => $bo_table,
            'sca' => $md_sca,
            'bo_subject' => $bo_subject,
            'list' => $list
        ];
    }

    ob_start();
    $rb_module_table = isset($rb_module_table) ? $rb_module_table : '';
    include $latest_skin_path.'/latest.tabs.skin.php';
    $content = ob_get_clean();

    return $content;
}



// 파일업로드
function rb_upload_files($srcfile, $destfile, $dir)
{
    if ($destfile == "") return false;
    // 업로드 한후 , 퍼미션을 변경함
    @move_uploaded_file($srcfile, $dir.'/'.$destfile);
    @chmod($dir.'/'.$destfile, G5_FILE_PERMISSION);
    return true;
}

// 배너출력
function rb_banners($position, $bnid='', $skin='', $order='')
{
    global $g5, $rb_core;

    if($skin == "") {
        $skin_path = G5_PATH.'/rb/rb.mod/banner/skin/rb.banner/banner.skin.php';
    } else {
        $skin_path = G5_PATH.'/rb/'.$skin.'/banner.skin.php';
    }


    if(file_exists($skin_path)) {


        // 배너 출력

        if($position == "개별출력") {
            if(IS_MOBILE()) {
                $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('mobile', 'both') and bn_id = '$bnid' order by bn_order, bn_id desc ";
            } else {
                $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('pc', 'both') and bn_id = '$bnid' order by bn_order, bn_id desc ";
            }
            $result = sql_query($sql);
        } else {
            if($order == "rand()") {
                if(IS_MOBILE()) {
                    $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('mobile', 'both') order by rand() ";
                } else {
                    $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('pc', 'both') order by rand() ";
                }
                $result = sql_query($sql);
            } else {
                if(IS_MOBILE()) {
                    $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('mobile', 'both') order by bn_order, bn_id desc ";
                } else {
                    $sql = " select * from rb_banner where '".G5_TIME_YMDHIS."' between bn_begin_time and bn_end_time and bn_position = '$position' and bn_device IN ('pc', 'both') order by bn_order, bn_id desc ";
                }
                $result = sql_query($sql);
            }
        }


        include $skin_path;
    } else {
        echo '<p>'.str_replace(G5_PATH.'/', '', $skin_path).' 경로에 스킨 파일이 존재하지 않습니다.</p>';
    }
}


// 게시글보기 파일 썸네일 리턴
function rb_get_file_thumbnail($file, $wr_id){

    if( ! is_array($file) ) return '';

    if( preg_match('/(\.jpg|\.jpeg|\.gif|\.png|\.bmp|\.webp)$/i', $file['file']) && $contents = run_replace('get_file_thumbnail_tags', '', $file) ){
        return $contents;
    } else if ($file['view']) {
        return rb_get_view_thumbnail($file['view'], "", $wr_id);
    }

    return $file['view'];
}

// 게시글보기 썸네일 생성
function rb_get_view_thumbnail($contents = '', $thumb_width = 0, $wr_id = '')
{
    global $board, $config;

    if (!$thumb_width)
        $thumb_width = $board['bo_image_width'];

    // $contents 중 img 태그 추출
    $matches = get_editor_image($contents, true);

    if(empty($matches))
        return $contents;

    $extensions = array(1=>'gif', 2=>'jpg', 3=>'png', 18=>'webp');

    for($i=0; $i<count($matches[1]); $i++) {

        $img = $matches[1][$i];
        $img_tag = isset($matches[0][$i]) ? $matches[0][$i] : '';

        preg_match("/src=[\'\"]?([^>\'\"]+[^>\'\"]+)/i", $img, $m);
        $src = isset($m[1]) ? $m[1] : '';
        preg_match("/style=[\"\']?([^\"\'>]+)/i", $img, $m);
        $style = isset($m[1]) ? $m[1] : '';
        preg_match("/width:\s*(\d+)px/", $style, $m);
        $width = isset($m[1]) ? $m[1] : '';
        preg_match("/height:\s*(\d+)px/", $style, $m);
        $height = isset($m[1]) ? $m[1] : '';
        preg_match("/alt=[\"\']?([^\"\']*)[\"\']?/", $img, $m);
        $alt = isset($m[1]) ? get_text($m[1]) : '';

        // 이미지 path 구함
        $p = parse_url($src);
        if(strpos($p['path'], '/'.G5_DATA_DIR.'/') != 0)
            $data_path = preg_replace('/^\/.*\/'.G5_DATA_DIR.'/', '/'.G5_DATA_DIR, $p['path']);
        else
            $data_path = $p['path'];

        $srcfile = G5_PATH.$data_path;

        if(is_file($srcfile)) {
            $size = @getimagesize($srcfile);
            if(empty($size))
                continue;

            $file_ext = $extensions[$size[2]];
            if (!$file_ext) continue;

            // jpg 이면 exif 체크
            if( $file_ext === 'jpg' && function_exists('exif_read_data')) {
                $degree = 0;
                $exif = @exif_read_data($srcfile);
                if(!empty($exif['Orientation'])) {
                    switch($exif['Orientation']) {
                        case 8:
                            $degree = 90;
                            break;
                        case 3:
                            $degree = 180;
                            break;
                        case 6:
                            $degree = -90;
                            break;
                    }

                    // 세로사진의 경우 가로, 세로 값 바꿈
                    if($degree == 90 || $degree == -90) {
                        $tmp = $size;
                        $size[0] = $tmp[1];
                        $size[1] = $tmp[0];
                    }
                }
            }

            // Animated GIF 체크
            $is_animated = false;
            if($file_ext === 'gif') {
                $is_animated = is_animated_gif($srcfile);

                if($replace_content = run_replace('thumbnail_is_animated_gif_content', '', $contents, $srcfile, $is_animated, $img_tag, $data_path, $size)){

                    $contents = $replace_content;
                    continue;
                }
            }

            // 원본 width가 thumb_width보다 작다면
            if($size[0] <= $thumb_width)
                continue;

            // 썸네일 높이
            $thumb_height = round(($thumb_width * $size[1]) / $size[0]);
            $filename = basename($srcfile);
            $filepath = dirname($srcfile);

            // 썸네일 생성
            if(!$is_animated)
                $thumb_file = thumbnail($filename, $filepath, $filepath, $thumb_width, $thumb_height, false);
            else
                $thumb_file = $filename;

            if(!$thumb_file)
                continue;

            if ($width) {
                $thumb_tag = '<img src="'.G5_URL.str_replace($filename, $thumb_file, $data_path).'" alt="'.$alt.'" width="'.$width.'" height="'.$height.'"/>';
            } else {
                $thumb_tag = '<img src="'.G5_URL.str_replace($filename, $thumb_file, $data_path).'" alt="'.$alt.'"/>';
            }

            // $img_tag에 editor 경로가 있으면 원본보기 링크 추가
            if(strpos($img_tag, G5_DATA_DIR.'/'.G5_EDITOR_DIR) && preg_match("/\.({$config['cf_image_extension']})$/i", $filename)) {
                $imgurl = str_replace(G5_URL, "", $src);

                if(isset($board['bo_lightbox']) && $board['bo_lightbox'] == 1) {
                    $attr_href = G5_URL.str_replace($filename, $thumb_file, $data_path);
                    $thumb_tag = '<a href="'.$attr_href.'" data-fslightbox="gallery_'.$wr_id.'">'.$thumb_tag.'</a>';
                } else {
                    $attr_href = run_replace('thumb_view_image_href', G5_BBS_URL.'/view_image.php?fn='.urlencode($imgurl), $filename, '', $width, $height, $alt);
                    $thumb_tag = '<a href="'.$attr_href.'" target="_blank" class="view_image">'.$thumb_tag.'</a>';
                }
            }

            $contents = str_replace($img_tag, $thumb_tag, $contents);
        }
    }

    return run_replace('get_view_thumbnail', $contents);
}


// 사이드뷰 추가 @Leegun 님께서 도움 주셨습니다.
add_replace('member_sideview_items', function ($sideview, $data = []) {
    global $g5;

    // $data 배열에서 mb_id를 가져옵니다.
    if (isset($data['mb_id']) && $data['mb_id']) {

        // 미니홈 메뉴 항목 생성
        $my_menu = ['my' => '<a href="' . G5_URL . '/rb/home.php?mb_id=' . $data['mb_id'] . '" rel="nofollow">미니홈</a>'];

        // 기존 메뉴 항목 앞에 새로운 메뉴 항목 추가
        $sideview['menus'] = $my_menu + $sideview['menus'];
    }
    return $sideview;

}, G5_HOOK_DEFAULT_PRIORITY, 2);


// 사용하지 않는 페이지 리다이렉트 처리
$redirect_map = [
    '/bbs/new.php' => '/rb/new.php',
];

// 현재 페이지의 URL을 확인 (쿼리 스트링을 제외한 경로)
$current_urls = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 쿼리 스트링을 확인 (파라미터)
$query_string = $_SERVER['QUERY_STRING'];

// 만약 현재 URL이 배열의 키에 있으면, 해당 값으로 리다이렉트
if (array_key_exists($current_urls, $redirect_map)) {
    // 리다이렉트할 URL에 쿼리 스트링을 붙여서 처리
    $redirect_urls = $redirect_map[$current_urls];

    // 쿼리 스트링이 존재하면 '?'를 붙여서 추가
    if (!empty($query_string)) {
        $redirect_urls .= '?' . $query_string;
    }

    // 리다이렉트 실행
    header('Location: ' . $redirect_urls);
    exit; // 리다이렉트 후 스크립트 실행 중지
}

// 3뎁스 함수
function rb_menu_db_3d($use_mobile=0, $is_cache=false){
    global $g5;

    static $cache = array();

    // // 캐시 훅(유지)
    $cache = run_replace('rb_menu_db_3d_cache', $cache, $use_mobile, $is_cache);

    $key = md5($use_mobile . '_3d');

    if ($is_cache && isset($cache[$key])) {
        return $cache[$key];
    }

    $where = $use_mobile ? "me_mobile_use = '1'" : "me_use = '1'";

    // // 데이터 훅(유지)
    if (!($cache[$key] = run_replace('rb_menu_db_3d', array(), $use_mobile))) {

        // // 1차
        $sql = " select *
                from {$g5['menu_table']}
                where $where
                and length(me_code) = '2'
                order by me_order, me_id ";
        $result = sql_query($sql, false);

        for ($i=0; $row=sql_fetch_array($result); $i++) {

            $row['ori_me_link'] = $row['me_link'];
            $row['me_link'] = short_url_clean($row['me_link']);
            $row['sub'] = array();
            $cache[$key][$i] = $row;

            // // 2차
            $sql2 = " select *
                    from {$g5['menu_table']}
                    where $where
                    and length(me_code) = '4'
                    and substring(me_code, 1, 2) = '{$row['me_code']}'
                    order by me_order, me_id ";
            $result2 = sql_query($sql2);

            for ($k=0; $row2=sql_fetch_array($result2); $k++) {

                $row2['ori_me_link'] = $row2['me_link'];
                $row2['me_link'] = short_url_clean($row2['me_link']);
                $row2['sub'] = array();
                $cache[$key][$i]['sub'][$k] = $row2;

                // // 3차
                $sql3 = " select *
                        from {$g5['menu_table']}
                        where $where
                        and length(me_code) = '6'
                        and substring(me_code, 1, 4) = '{$row2['me_code']}'
                        order by me_order, me_id ";
                $result3 = sql_query($sql3);

                for ($j=0; $row3=sql_fetch_array($result3); $j++) {
                    $row3['ori_me_link'] = $row3['me_link'];
                    $row3['me_link'] = short_url_clean($row3['me_link']);
                    $cache[$key][$i]['sub'][$k]['sub'][$j] = $row3;
                }
            }
        }
    }

    return $cache[$key];
}


// AOS
$rb_aos_tbl = RB_TABLE_PREFIX.'aos';
$rb_aos_exists = false;

$tbl_esc = sql_real_escape_string($rb_aos_tbl);
$row_aos = sql_fetch("SHOW TABLES LIKE '{$tbl_esc}'", false);
if ($row_aos) $rb_aos_exists = true;

if (!function_exists('rb_aos_row_map')) {
    function rb_aos_row_map($row) {
        if (!is_array($row) || empty($row)) return array();

        return array(
            'use' => isset($row['ra_use']) ? (int)$row['ra_use'] : 0,
            'aos' => isset($row['ra_aos']) ? (string)$row['ra_aos'] : '',
            'offset' => isset($row['ra_offset']) ? (string)$row['ra_offset'] : '',
            'delay' => isset($row['ra_delay']) ? (string)$row['ra_delay'] : '',
            'duration' => isset($row['ra_duration']) ? (string)$row['ra_duration'] : '',
            'easing' => isset($row['ra_easing']) ? (string)$row['ra_easing'] : '',
            'mirror' => isset($row['ra_mirror']) ? (string)$row['ra_mirror'] : '',
            'once' => isset($row['ra_once']) ? (string)$row['ra_once'] : '',
            'anchor_placement' => isset($row['ra_anchor_placement']) ? (string)$row['ra_anchor_placement'] : ''
        );
    }
}

// 결과 배열(없으면 빈배열)
$rb_aos = array();       // 일반용
$rb_aos_shop = array();  // 마켓용

// use 기본은 0 (데이터 없으면 미사용)
$rb_aos_use = 0;
$rb_aos_use_shop = 0;

// 값 변수는 기본 빈값(데이터 없으면 빈값 유지)
$rb_aos_motion = '';
$rb_aos_offset = '';
$rb_aos_delay = '';
$rb_aos_duration = '';
$rb_aos_easing = '';
$rb_aos_mirror = '';
$rb_aos_once = '';
$rb_aos_anchor = '';

$rb_aos_motion_shop = '';
$rb_aos_offset_shop = '';
$rb_aos_delay_shop = '';
$rb_aos_duration_shop = '';
$rb_aos_easing_shop = '';
$rb_aos_mirror_shop = '';
$rb_aos_once_shop = '';
$rb_aos_anchor_shop = '';

if ($rb_aos_exists) {
    // // 일반용
    $row_general = sql_fetch("SELECT * FROM `{$rb_aos_tbl}` WHERE ra_type='general' LIMIT 1", false);
    if (is_array($row_general) && !empty($row_general)) {
        $rb_aos = rb_aos_row_map($row_general);
    }

    // // 마켓용
    $row_market = sql_fetch("SELECT * FROM `{$rb_aos_tbl}` WHERE ra_type='market' LIMIT 1", false);
    if (is_array($row_market) && !empty($row_market)) {
        $rb_aos_shop = rb_aos_row_map($row_market);
    }

    // // use만 기본 0에서 덮기
    $rb_aos_use = isset($rb_aos['use']) ? (int)$rb_aos['use'] : 0;
    $rb_aos_use_shop = isset($rb_aos_shop['use']) ? (int)$rb_aos_shop['use'] : 0;

    // // 값은 "DB에 값이 있을 때만" 변수에 세팅 (없으면 빈값 유지)
    if (isset($rb_aos['aos']) && trim((string)$rb_aos['aos']) !== '') $rb_aos_motion = trim((string)$rb_aos['aos']);
    if (isset($rb_aos['offset']) && (string)$rb_aos['offset'] !== '') $rb_aos_offset = (string)$rb_aos['offset'];
    if (isset($rb_aos['delay']) && (string)$rb_aos['delay'] !== '') $rb_aos_delay = (string)$rb_aos['delay'];
    if (isset($rb_aos['duration']) && (string)$rb_aos['duration'] !== '') $rb_aos_duration = (string)$rb_aos['duration'];
    if (isset($rb_aos['easing']) && trim((string)$rb_aos['easing']) !== '') $rb_aos_easing = trim((string)$rb_aos['easing']);
    if (isset($rb_aos['mirror']) && (string)$rb_aos['mirror'] !== '') $rb_aos_mirror = (string)$rb_aos['mirror'];
    if (isset($rb_aos['once']) && (string)$rb_aos['once'] !== '') $rb_aos_once = (string)$rb_aos['once'];
    if (isset($rb_aos['anchor_placement']) && trim((string)$rb_aos['anchor_placement']) !== '') $rb_aos_anchor = trim((string)$rb_aos['anchor_placement']);

    if (isset($rb_aos_shop['aos']) && trim((string)$rb_aos_shop['aos']) !== '') $rb_aos_motion_shop = trim((string)$rb_aos_shop['aos']);
    if (isset($rb_aos_shop['offset']) && (string)$rb_aos_shop['offset'] !== '') $rb_aos_offset_shop = (string)$rb_aos_shop['offset'];
    if (isset($rb_aos_shop['delay']) && (string)$rb_aos_shop['delay'] !== '') $rb_aos_delay_shop = (string)$rb_aos_shop['delay'];
    if (isset($rb_aos_shop['duration']) && (string)$rb_aos_shop['duration'] !== '') $rb_aos_duration_shop = (string)$rb_aos_shop['duration'];
    if (isset($rb_aos_shop['easing']) && trim((string)$rb_aos_shop['easing']) !== '') $rb_aos_easing_shop = trim((string)$rb_aos_shop['easing']);
    if (isset($rb_aos_shop['mirror']) && (string)$rb_aos_shop['mirror'] !== '') $rb_aos_mirror_shop = (string)$rb_aos_shop['mirror'];
    if (isset($rb_aos_shop['once']) && (string)$rb_aos_shop['once'] !== '') $rb_aos_once_shop = (string)$rb_aos_shop['once'];
    if (isset($rb_aos_shop['anchor_placement']) && trim((string)$rb_aos_shop['anchor_placement']) !== '') $rb_aos_anchor_shop = trim((string)$rb_aos_shop['anchor_placement']);
}

// // rb_display 우선, 없으면 기존 banners 폴더 fallback (기존 유지)
if (!function_exists('rb_display_file_path')) {
    function rb_display_file_path($id) {
        $id = preg_replace('/[^0-9]/', '', (string)$id);
        if ($id === '') return '';
        $cands = array(
            G5_DATA_PATH . '/rb_display/' . $id,
            G5_DATA_PATH . '/banners/' . $id
        );
        for ($i = 0; $i < count($cands); $i++) {
            if (is_file($cands[$i])) return $cands[$i];
        }
        return '';
    }
}
if (!function_exists('rb_design_bg_exists')) {
    function rb_design_bg_exists($id) {
        $id = preg_replace('/[^0-9]/', '', (string)$id);
        if ($id === '') return false;
        return is_file(G5_DATA_PATH . '/rb_display_design/' . $id);
    }
}
if (!function_exists('rb_int_range')) {
    function rb_int_range($v, $min, $max, $def) {
        $n = (int)$v;
        if ($n < $min || $n > $max) return (int)$def;
        return $n;
    }
}
if (!function_exists('rb_ratio_sanitize')) {
    function rb_ratio_sanitize($r) {
        $r = trim((string)$r);
        if ($r === '') return '16 / 9';
        $r = preg_replace('/[^0-9\.\/\s]/', '', $r);
        $r = trim(preg_replace('/\s+/', ' ', $r));
        if ($r === '') return '16 / 9';
        return $r;
    }
}
if (!function_exists('rb_safe_admin_html')) {
    function rb_safe_admin_html($html) {
        $html = (string)$html;
        $html = preg_replace('#<\s*script[^>]*>.*?<\s*/\s*script>#is', '', $html);
        $html = preg_replace('/on[a-z]+\s*=\s*([\'"]).*?\1/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        return $html;
    }
}
?>
