<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_PATH.'/rb/rb.lib/rb_core_compat.lib.php';

// 첨부파일 저장은 설치 코어의 함수를 우선 사용하고 구 코어만 보완합니다.
require_once G5_PATH.'/rb/rb.lib/rb_attachment_compat.lib.php';

// 모든 코어 extend가 로드된 뒤 보완해야 신버전의 함수와 중복 선언되지 않는다.
add_event('common_header', 'rb_core_compat_shop_init', 0);
function rb_core_compat_shop_init()
{
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return;
    require_once G5_PATH.'/rb/rb.lib/rb_shop_compat.lib.php';
    if (!function_exists('shop_validate_cart_rows')) {
        require_once G5_PATH.'/rb/rb.lib/rb_cart_compat.lib.php';
    }
    // 구 코어의 임시저장도 현재 주문서/장바구니에 결합한다.
    if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'ajax.orderdatasave.php'
        && !rb_shop_native_order_security()) {
        rb_shop_legacy_checkout_check(!empty($_POST['pp_id']));
    }
}
