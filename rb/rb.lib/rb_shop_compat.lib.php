<?php
if (!defined('_GNUBOARD_')) exit;

// 코어의 주문 보안 함수는 흉내 내어 선언하지 않는다. 설치된 PG 전체와 함께 사용한다.
function rb_shop_native_order_security()
{
    static $native = null;
    if ($native !== null) return $native;
    $file = G5_LIB_PATH.'/shop_order_access.lib.php';
    if (!is_file($file)) return $native = false;
    foreach (array('shop_order_compat.lib.php', 'shop_order_fields.lib.php', 'shop_order_state.lib.php') as $dependency) {
        if (!is_file(G5_LIB_PATH.'/'.$dependency)) {
            alert('쇼핑몰 주문 모듈의 파일 구성을 확인해 주세요.');
            exit;
        }
    }
    require_once $file;
    return $native = true;
}

function rb_shop_checkout_fields($id, $personal = false)
{
    rb_shop_order_schema_check();
    $native = rb_shop_native_order_security();
    add_javascript('<script src="'.G5_URL.'/js/rb.shop-compat.js?v=2.2.7.7"></script>', 0);
    if ($native) {
        add_javascript('<script src="'.G5_URL.'/js/shop.order-state.js"></script>', 0);
        return shop_order_checkout_fields((string)$id, $personal);
    }
    if (!preg_match('/\A[0-9]{1,20}\z/', (string)$id)) return '';
    global $member;
    $map = get_session('ss_rb_checkouts');
    if (!is_array($map)) $map = array();
    foreach ($map as $key => $entry) if ($entry['expires'] <= time()) unset($map[$key]);
    if (count($map) >= 30) { reset($map); unset($map[key($map)]); }
    $nonce = get_random_token_string(32);
    $map[(string)$id] = array('nonce'=>$nonce, 'expires'=>time()+7200, 'personal'=>(bool)$personal,
        'member'=>isset($member['mb_id']) ? (string)$member['mb_id'] : '',
        'cart'=>(string)get_session(get_session('ss_direct') ? 'ss_cart_direct' : 'ss_cart_id'),
        'direct'=>(bool)get_session('ss_direct'));
    set_session('ss_rb_checkouts', $map);
    return '<input type="hidden" name="rb_checkout_id" value="'.get_text($id).'">'.
        '<input type="hidden" name="rb_checkout_nonce" value="'.get_text($nonce).'">';
}

function rb_shop_legacy_checkout_check($personal = false)
{
    global $member;
    $id = (string)get_session($personal ? 'ss_personalpay_id' : 'ss_order_id');
    $map = get_session('ss_rb_checkouts');
    $posted = isset($_POST['rb_checkout_id']) || isset($_POST['rb_checkout_nonce']);
    if (!$posted && (!is_array($map) || !isset($map[$id]))) return; // 기존 스킨은 코어의 세션·PG 검증 유지
    $entry = is_array($map) && isset($map[$id]) ? $map[$id] : null;
    $owner = isset($member['mb_id']) ? (string)$member['mb_id'] : '';
    $valid = $entry && $entry['expires'] > time() && $entry['personal'] === (bool)$personal && $entry['member'] === $owner;
    if ($valid && !$personal) {
        $valid = $entry['direct'] === (bool)get_session('ss_direct')
            && $entry['cart'] === (string)get_session(get_session('ss_direct') ? 'ss_cart_direct' : 'ss_cart_id');
    }
    if ($posted) {
        $valid = $valid && isset($_POST['rb_checkout_id'], $_POST['rb_checkout_nonce'])
            && is_string($_POST['rb_checkout_id']) && is_string($_POST['rb_checkout_nonce'])
            && $_POST['rb_checkout_id'] === $id && slow_equals($entry['nonce'], $_POST['rb_checkout_nonce']);
    }
    if (!$valid) { alert('주문서가 만료되었거나 변경되었습니다. 주문서를 다시 열어 주세요.'); exit; }
}

function rb_shop_order_prepare()
{
    rb_shop_order_schema_check();
    if (rb_shop_native_order_security()) shop_order_state_prepare(false);
    else rb_shop_legacy_checkout_check(false);
}

function rb_shop_order_password($id)
{
    return rb_shop_native_order_security() ? shop_order_access_password((string)$id) : null;
}

function rb_shop_order_finalizing()
{
    if (rb_shop_native_order_security()) shop_order_state_finalizing();
}

function rb_shop_order_forget($id)
{
    if (rb_shop_native_order_security()) shop_order_access_forget((string)$id);
    $map = get_session('ss_rb_checkouts');
    if (is_array($map)) { unset($map[(string)$id]); set_session('ss_rb_checkouts', $map); }
}

// 조회 URL을 검증하는 설치 코어와 같은 UID 형식을 사용한다.
function rb_shop_uid($type, $id, $time, $ip)
{
    return function_exists('get_shop_uid') ? get_shop_uid($type, $id, $time, $ip) : md5($id.$time.$ip);
}

function rb_shop_complete_time_assignment($status)
{
    global $g5;
    if (!rb_shop_table_has_column($g5['g5_shop_cart_table'], 'ct_complete_time')) return '';
    $sql = function_exists('get_cart_complete_time_sql') ? get_cart_complete_time_sql($status)
        : ($status === '완료' ? "CASE WHEN ct_status='완료' THEN ct_complete_time ELSE '".G5_TIME_YMDHIS."' END" : 'NULL');
    return 'ct_complete_time='.$sql.', ';
}

function rb_shop_pg_columns($kcp, $lg)
{
    global $g5;
    $sql = '';
    foreach (array('od_kcp_site_cd'=>$kcp, 'od_lg_mid'=>$lg) as $column=>$value) {
        if (rb_shop_table_has_column($g5['g5_shop_order_table'], $column)) {
            $sql .= $column."='".sql_real_escape_string($value)."', ";
        }
    }
    return $sql;
}

// 신 코어의 입금 통보 검증에 필요한 컬럼을 조용히 생략하지 않는다.
// 구 코어에는 신규 DB 구조를 요구하지 않는다.
function rb_shop_order_schema_check()
{
    global $g5, $default;
    if (!defined('G5_GNUBOARD_VER')) return;
    $required = array();
    if (version_compare(G5_GNUBOARD_VER, '5.6.38', '>=')) $required[] = 'od_kcp_site_cd';
    if (version_compare(G5_GNUBOARD_VER, '5.6.40', '>=')) $required[] = 'od_lg_mid';
    foreach ($required as $column) {
        if (!rb_shop_table_has_column($g5['g5_shop_order_table'], $column)) {
            alert('쇼핑몰 DB 업데이트가 필요합니다. 관리자에게 문의해 주세요.');
            exit;
        }
    }
    if (!empty($_POST['inicis_pro']) && !is_file(G5_SHOP_PATH.'/inicis/pro/pay_result.php')) {
        alert('설치된 쇼핑몰에서 지원하는 결제수단으로 다시 주문해 주세요.');
        exit;
    }
}

function rb_shop_toss_providers()
{
    return function_exists('shop_order_toss_providers') ? shop_order_toss_providers()
        : array('TOSSPAY', 'NAVERPAY', 'SAMSUNGPAY', 'APPLEPAY', 'LPAY', 'KAKAOPAY', 'PINPAY', 'PAYCO', 'SSG');
}

function rb_shop_category_path($ca_id, $separator = ' &gt; ')
{
    if (function_exists('get_shop_category_path')) return get_shop_category_path($ca_id, $separator);
    global $g5;
    static $category_cache = array(); // 카테고리명 캐시
    static $path_cache = array();     // 경로 캐시

    if (!preg_match('/\A(?:[A-Za-z0-9]{2}){1,5}\z/', (string)$ca_id)) return '';

    // 동일한 separator로 이미 조회한 경로가 있으면 캐시에서 반환
    $cache_key = $ca_id . '|' . $separator;
    if (isset($path_cache[$cache_key])) {
        return $path_cache[$cache_key];
    }

    $path_arr = array();
    $ca_id_len = strlen($ca_id);

    // 카테고리 ID를 2자리씩 분할하여 각 단계의 카테고리명을 조회
    for ($i = 2; $i <= $ca_id_len; $i += 2) {
        $current_ca_id = substr($ca_id, 0, $i);

        // 캐시에 없으면 DB 조회
        if (!isset($category_cache[$current_ca_id])) {
            $sql = " select ca_name from {$g5['g5_shop_category_table']} where ca_id = '$current_ca_id' ";
            $row = sql_fetch($sql);
            if ($row) {
                $category_cache[$current_ca_id] = $row['ca_name'];
            } else {
                $category_cache[$current_ca_id] = '';
            }
        }

        if ($category_cache[$current_ca_id]) {
            $path_arr[] = $category_cache[$current_ca_id];
        }
    }

    $result = implode($separator, $path_arr);
    $path_cache[$cache_key] = $result; // 결과를 캐시에 저장

    return $result;
}

function rb_shop_check_skin_dir($skin, $label, $mobile = false)
{
    if (function_exists('check_shop_skin_dir')) return check_shop_skin_dir($skin, $label, $mobile);
    if ($skin === '') return;
    global $config;
    $path = $mobile ? G5_MOBILE_PATH.'/'.G5_SKIN_DIR : G5_SKIN_PATH;
    $skins = get_skin_dir('shop', $path);
    if (defined('G5_THEME_PATH') && !empty($config['cf_theme'])) {
        $theme_path = ($mobile ? G5_THEME_MOBILE_PATH : G5_THEME_PATH).'/'.G5_SKIN_DIR;
        foreach (get_skin_dir('shop', $theme_path) as $directory) $skins[] = 'theme/'.$directory;
    }
    if (!is_string($skin) || strpos($skin, '..') !== false || strpos($skin, "\\") !== false
        || !is_include_path_check($skin, 1) || !in_array($skin, $skins, true)) {
        alert($label.'을 올바르게 선택해 주십시오.');
        exit;
    }
}



// 설정 키 => 표시명, PG 요청 코드, CSS 클래스, 기존 주문 결제수단 값(선택).
function rb_legacy_shop_easypay_catalog($pg)
{
    $catalog = array(
        'kcp' => array(
            'nhnkcp_payco' => array('PAYCO', 'payco', 'PAYCO'),
            'nhnkcp_naverpay' => array('네이버페이', 'naverpay', 'naverpay_icon'),
            'nhnkcp_kakaopay' => array('카카오페이', 'kakaopay', 'kakaopay_icon'),
            'nhnkcp_applepay' => array('애플페이', 'applepay', 'applepay_icon'),
        ),
        'inicis' => array(
            'inicis_samsungpay' => array('삼성페이', 'samsungpay', 'samsungpay_icon', '삼성페이'),
            'inicis_lpay' => array('L.pay', 'lpay', 'lpay_icon', 'lpay'),
            'inicis_kakaopay' => array('카카오페이', 'inicis_kakaopay', 'kakaopay_icon', 'inicis_kakaopay'),
        ),
        'toss' => array(
            'toss_tosspay' => array('토스페이', 'TOSSPAY', 'tosspay_icon'),
            'toss_naverpay' => array('네이버페이', 'NAVERPAY', 'naverpay_icon'),
            'toss_samsungpay' => array('삼성페이', 'SAMSUNGPAY', 'samsungpay_icon'),
            'toss_applepay' => array('애플페이', 'APPLEPAY', 'applepay_icon'),
            'toss_lpay' => array('L.pay', 'LPAY', 'lpay_icon'),
            'toss_kakaopay' => array('카카오페이', 'KAKAOPAY', 'kakaopay_icon'),
            'toss_pinpay' => array('핀페이', 'PINPAY', 'pinpay_icon'),
            'toss_payco' => array('PAYCO', 'PAYCO', 'PAYCO'),
            'toss_ssgpay' => array('SSG페이', 'SSG', 'ssgpay_icon'),
        ),
        'nicepay' => array(
            'nicepay_samsungpay' => array('삼성페이', 'nice_samsungpay', 'samsungpay_icon'),
            'nicepay_naverpay' => array('네이버페이', 'nice_naverpay', 'naverpay_icon'),
            'nicepay_kakaopay' => array('카카오페이', 'nice_kakaopay', 'kakaopay_icon'),
            'nicepay_applepay' => array('애플페이', 'nice_applepay', 'applepay_icon'),
            'nicepay_paycopay' => array('PAYCO', 'nice_paycopay', 'PAYCO'),
            'nicepay_skpay' => array('SK페이', 'nice_skpay', 'skpay_icon'),
            'nicepay_ssgpay' => array('SSG페이', 'nice_ssgpay', 'ssgpay_icon'),
            'nicepay_lpay' => array('L.pay', 'nice_lpay', 'lpay_icon'),
        ),
    );
    return isset($catalog[$pg]) ? $catalog[$pg] : array();
}

function rb_legacy_shop_easypay_legacy_keys()
{
    return array('inicis_samsungpay' => 'de_samsung_pay_use', 'inicis_lpay' => 'de_inicis_lpay_use', 'inicis_kakaopay' => 'de_inicis_kakaopay_use');
}

// 첫 설정 저장 전에는 기존 개별 설정과 토스 PAYCO 사용 상태를 승계한다.
function rb_legacy_shop_easypay_normalize($settings)
{
    $services = !empty($settings['de_easy_pay_services']) ? explode(',', $settings['de_easy_pay_services']) : array();
    if (!in_array('inicis_configured', $services, true)) {
        foreach (rb_legacy_shop_easypay_legacy_keys() as $key => $legacy) {
            if (!empty($settings[$legacy])) {
                $services[] = $key;
                if ($settings['de_pg_service'] === 'inicis') $settings['de_easy_pay_use'] = 1;
            }
        }
    }
    if (!in_array('toss_configured', $services, true) && $settings['de_pg_service'] === 'toss' && !empty($settings['de_easy_pay_use']) && !array_intersect(array_keys(rb_legacy_shop_easypay_catalog('toss')), $services)) {
        $services[] = 'toss_payco';
    }
    $settings['de_easy_pay_services'] = implode(',', array_unique($services));
    foreach (rb_legacy_shop_easypay_legacy_keys() as $key => $legacy) {
        $settings[$legacy] = (int) (in_array($key, $services, true) && ($settings['de_pg_service'] !== 'inicis' || !empty($settings['de_easy_pay_use'])));
    }
    return $settings;
}

function rb_legacy_shop_easypay_enabled($key)
{
    global $default;
    return !empty($default['de_easy_pay_use']) && in_array($key, explode(',', $default['de_easy_pay_services']), true);
}

function rb_legacy_shop_easypay_available($key, $mobile)
{
    global $default;
    if (strpos($key, 'applepay') !== false) {
        // KCP/NICEPAY와 동일하게 iOS 모바일 브라우저에만 제공한다.
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return $mobile && preg_match('/iPhone|iPad|iPod/i', $ua);
    }
    // 삼성페이는 PC에서 휴대폰 인증으로 연결할 수 있다. 구 INIpay만 모바일 전용이다.
    return $key !== 'inicis_samsungpay' || $mobile || !empty($default['de_inicis_pro_use']);
}

function rb_legacy_shop_easypay_button($key, $provider, $mobile, $money = false)
{
    $label = $provider[0];
    $pay_code = $provider[1];
    $css_class = $provider[2];
    $value = isset($provider[3]) ? $provider[3] : '간편결제';
    $legacy_ids = array('inicis_samsungpay' => 'samsungpay', 'inicis_lpay' => 'inicislpay');
    $id = 'od_settle_'.(isset($legacy_ids[$key]) ? $legacy_ids[$key] : $key);
    $attrs = isset($provider[3]) ? ' data-case="'.$pay_code.'"' : '';
    if ($money) {
        $attrs .= ' data-money="1"';
    }

    $html = '<input type="radio" id="'.$id.'" name="od_settle_case"'
        .' data-pay="'.$pay_code.'" value="'.$value.'"'.$attrs.'> '
        .'<label for="'.$id.'" class="'.$css_class.' '.$key.' lb_icon"'
        .' title="'.$label.'">'.$label.'</label>';

    return $mobile ? '<li>'.$html.'</li>' : $html;
}

function rb_legacy_shop_easypay_buttons($mobile = false)
{
    global $default;
    $buttons = array();
    $pg = $default['de_pg_service'];
    foreach (rb_legacy_shop_easypay_catalog($pg) as $key => $provider) {
        if (!rb_legacy_shop_easypay_enabled($key) || !rb_legacy_shop_easypay_available($key, $mobile)) continue;
        $buttons[$key] = rb_legacy_shop_easypay_button($key, $provider, $mobile);
    }
    if ($pg === 'lg' && !empty($default['de_easy_pay_use'])) {
        $buttons['paynow'] = rb_legacy_shop_easypay_button('easy_pay', array('PAYNOW', '', 'PAYNOW'), $mobile);
    }
    if (is_use_easypay('global_nhnkcp')) {
        $buttons['nhnkcp_naverpay'] = rb_legacy_shop_easypay_button('nhnkcp_naverpay', array('네이버페이', 'naverpay', 'naverpay_icon'), $mobile);
    }
    if (isset($buttons['nhnkcp_naverpay'])) {
        $services = explode(',', $default['de_easy_pay_services']);
        if (in_array('used_nhnkcp_naverpay_point', $services, true)) {
            $buttons['nhnkcp_naverpay'] = rb_legacy_shop_easypay_button('nhnkcp_naverpay', array('네이버페이 카드결제', 'naverpay', 'naverpay_icon nhnkcp_icon nhnkcp_card'), $mobile);
            $buttons['nhnkcp_naverpay_money'] = rb_legacy_shop_easypay_button('nhnkcp_naverpay_money', array('네이버페이 머니/포인트', 'naverpay', 'naverpay_icon nhnkcp_icon nhnkcp_money'), $mobile, true);
        }
    }
    return $buttons;
}


function rb_shop_easypay_buttons($mobile = false)
{
    if (function_exists('shop_easypay_buttons')) return shop_easypay_buttons($mobile);
    global $default;
    $original = $default;
    // 구 코어의 개별 이니시스 설정과 통합 간편결제 설정을 모두 승계한다.
    $default = rb_legacy_shop_easypay_normalize($default);
    try { return rb_legacy_shop_easypay_buttons($mobile); }
    finally { $default = $original; }
}
