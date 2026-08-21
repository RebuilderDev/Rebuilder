<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 일반상품은 기존 장바구니를 사용하고 예약·파일·미디어 상품은
 * 영카트의 바로구매 장바구니를 유형별로 격리해서 사용한다.
 */
function rb_shop_order_type_meta($type = null)
{
    $types = array(
        0 => array('id'=>0, 'key'=>'general', 'label'=>'일반상품', 'short_label'=>'일반', 'available'=>true),
        1 => array('id'=>1, 'key'=>'reservation', 'label'=>'예약상품', 'short_label'=>'예약', 'available'=>function_exists('rb_reservation_is_enabled') && rb_reservation_is_enabled()),
        2 => array('id'=>2, 'key'=>'file', 'label'=>'파일상품', 'short_label'=>'파일', 'available'=>function_exists('rb_file_is_enabled') && rb_file_is_enabled()),
        3 => array('id'=>3, 'key'=>'media', 'label'=>'미디어상품', 'short_label'=>'미디어', 'available'=>function_exists('rb_media_is_enabled') && rb_media_is_enabled()),
    );

    if ($type === null) return $types;
    $type = (int) $type;
    return isset($types[$type]) ? $types[$type] : array('id'=>$type, 'key'=>'unknown', 'label'=>'알 수 없는 상품', 'short_label'=>'기타', 'available'=>false);
}

function rb_shop_is_special_item_type($type)
{
    return in_array((int) $type, array(1, 2, 3), true);
}

/**
 * 특수상품은 상품 DB에 무료배송 타입을 저장하지 않지만 장바구니 계산에서는
 * 쇼핑몰 기본 배송비를 상속하지 않도록 무배송 스냅샷으로 정규화한다.
 */
function rb_shop_normalize_special_cart_item($item)
{
    if (!is_array($item) || !isset($item['it_types']) || !rb_shop_is_special_item_type($item['it_types'])) {
        return $item;
    }

    $item['it_sc_type'] = 1;
    $item['it_sc_method'] = 0;
    $item['it_sc_price'] = 0;
    $item['it_sc_minimum'] = 0;
    $item['it_sc_qty'] = 0;
    return $item;
}

function rb_shop_special_type_available($type)
{
    $meta = rb_shop_order_type_meta($type);
    return !empty($meta['available']);
}

function rb_shop_table_has_column($table, $column)
{
    static $cache = array();
    $key = $table.':'.$column;
    if (isset($cache[$key])) return $cache[$key];
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$table) || !preg_match('/^[A-Za-z0-9_]+$/', (string)$column)) {
        return false;
    }
    $row = sql_fetch("SHOW COLUMNS FROM `{$table}` LIKE '".sql_real_escape_string($column)."'", false);
    return $cache[$key] = isset($row['Field']) && $row['Field'] === $column;
}

if (!function_exists('rb_shop_order_has_shipping_items')) {
    function rb_shop_order_has_shipping_items($order_id, $selected_only = false)
    {
        global $g5;
        $order_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$order_id);
        if ($order_id === '') return false;

        $selected_sql = $selected_only ? " AND c.ct_select='1'" : '';
        $type_sql = rb_shop_table_has_column($g5['g5_shop_item_table'], 'it_types')
            ? "COALESCE(NULLIF(i.it_types,''),'0')='0'"
            : '1=1';
        $row = sql_fetch("SELECT COUNT(*) AS shipping_cnt
                           FROM {$g5['g5_shop_cart_table']} c
                           LEFT JOIN {$g5['g5_shop_item_table']} i ON i.it_id=c.it_id
                          WHERE c.od_id='".sql_real_escape_string($order_id)."'
                            {$selected_sql} AND c.io_type='0' AND {$type_sql}", false);
        return !empty($row['shipping_cnt']);
    }
}

function rb_shop_order_type_from_cart($cart_id, $selected_only = false)
{
    global $g5;
    $cart_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $cart_id);
    if ($cart_id === '') return rb_shop_order_type_meta(-1);

    // DB 업데이트 전에는 모든 기존 상품을 일반상품으로 취급해 기존 주문을 보호한다.
    if (!rb_shop_table_has_column($g5['g5_shop_item_table'], 'it_types')) {
        $selected_sql = $selected_only ? " AND ct_select='1'" : '';
        $row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_cart_table']}
                           WHERE od_id='".sql_real_escape_string($cart_id)."' AND io_type='0' {$selected_sql}", false);
        return !empty($row['cnt']) ? rb_shop_order_type_meta(0) : rb_shop_order_type_meta(-1);
    }

    $selected_sql = $selected_only ? " AND c.ct_select='1'" : '';
    $result = sql_query("SELECT DISTINCT COALESCE(NULLIF(i.it_types,''),'0') AS item_type
                         FROM {$g5['g5_shop_cart_table']} c
                         LEFT JOIN {$g5['g5_shop_item_table']} i ON i.it_id=c.it_id
                         WHERE c.od_id='".sql_real_escape_string($cart_id)."'
                           AND c.io_type='0' {$selected_sql}", false);
    $found = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) $found[(int) $row['item_type']] = true;
    }
    if (count($found) !== 1) {
        return array('id'=>-1, 'key'=>count($found) ? 'mixed' : 'empty', 'label'=>count($found) ? '혼합주문' : '상품 없음', 'short_label'=>count($found) ? '혼합' : '-', 'available'=>false);
    }
    $ids = array_keys($found);
    return rb_shop_order_type_meta((int) $ids[0]);
}

function rb_shop_order_type_from_order($od_id)
{
    return rb_shop_order_type_from_cart($od_id, false);
}

/**
 * 영카트 기본 주문금액 계산에서 제외되는 비즈니스 상품 추가금액 SQL.
 * 파일·미디어 선택금액은 수량별, 예약 날짜·시즌·이용자 옵션은 예약 설정 기준으로 계산한다.
 */
if (!function_exists('rb_shop_order_extra_amount_sql')) {
    function rb_shop_order_extra_amount_sql($alias = '')
    {
        global $g5;

        $alias = preg_replace('/[^A-Za-z0-9_]/', '', (string)$alias);
        $p = $alias !== '' ? $alias.'.' : '';
        $cart_table = $g5['g5_shop_cart_table'];
        $parts = array();

        if (rb_shop_table_has_column($cart_table, 'ct_file_price')) {
            $parts[] = "COALESCE({$p}ct_file_price,0) * COALESCE({$p}ct_qty,1)";
        }
        if (rb_shop_table_has_column($cart_table, 'ct_media_price')) {
            $parts[] = "COALESCE({$p}ct_media_price,0) * COALESCE({$p}ct_qty,1)";
        }

        $reservation_columns = array(
            'ct_types', 'ct_opt_opt', 'ct_date_extra_price', 'ct_date_extra_price2',
            'ct_user_pri1', 'ct_user_qty1', 'ct_user_pri2', 'ct_user_qty2',
            'ct_user_pri3', 'ct_user_qty3',
        );
        $reservation_ready = true;
        foreach ($reservation_columns as $column) {
            if (!rb_shop_table_has_column($cart_table, $column)) {
                $reservation_ready = false;
                break;
            }
        }

        if ($reservation_ready) {
            $reservation = "COALESCE({$p}ct_date_extra_price,0)
                          + COALESCE({$p}ct_date_extra_price2,0)
                          + (COALESCE({$p}ct_user_pri1,0) * COALESCE({$p}ct_user_qty1,0) * IF(COALESCE({$p}ct_opt_opt,0)=1,COALESCE({$p}ct_qty,1),1))
                          + (COALESCE({$p}ct_user_pri2,0) * COALESCE({$p}ct_user_qty2,0) * IF(COALESCE({$p}ct_opt_opt,0)=1,COALESCE({$p}ct_qty,1),1))
                          + (COALESCE({$p}ct_user_pri3,0) * COALESCE({$p}ct_user_qty3,0) * IF(COALESCE({$p}ct_opt_opt,0)=1,COALESCE({$p}ct_qty,1),1))";
            $parts[] = "IF(COALESCE({$p}ct_types,0)=1,({$reservation}),0)";
        }

        if (!$parts) return '0';

        // 선택옵션 행(io_type=1)은 기본 영카트 io_price 계산만 사용한다.
        return "IF(COALESCE({$p}io_type,0)=1,0,(".implode(' + ', $parts)."))";
    }
}

/** 영카트 주문정보에 비즈니스 상품의 실제 추가금액을 합산한다. */
if (!function_exists('rb_shop_get_order_info')) {
    function rb_shop_get_order_info($od_id)
    {
        global $g5;

        $od_id = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$od_id);
        if ($od_id === '') return false;

        $info = get_order_info($od_id);
        if (!$info) return false;

        $type = rb_shop_order_type_from_order($od_id);
        if (!rb_shop_is_special_item_type(isset($type['id']) ? $type['id'] : 0)) {
            return $info;
        }

        $extra_sql = rb_shop_order_extra_amount_sql('c');
        if ($extra_sql === '0') return $info;

        $active = "'주문','입금','준비','배송','완료'";
        $cancelled = "'취소','반품','품절'";
        $row = sql_fetch("SELECT
                            COALESCE(SUM(CASE WHEN c.ct_status IN ({$active}) THEN {$extra_sql} ELSE 0 END),0) AS active_extra,
                            COALESCE(SUM(CASE WHEN c.ct_status IN ({$cancelled}) THEN {$extra_sql} ELSE 0 END),0) AS cancel_extra,
                            COALESCE(SUM(CASE WHEN c.ct_status IN ({$active}) AND COALESCE(c.ct_notax,0)=0 THEN {$extra_sql} ELSE 0 END),0) AS tax_extra,
                            COALESCE(SUM(CASE WHEN c.ct_status IN ({$active}) AND COALESCE(c.ct_notax,0)=1 THEN {$extra_sql} ELSE 0 END),0) AS free_extra
                         FROM {$g5['g5_shop_cart_table']} c
                        WHERE c.od_id='".sql_real_escape_string($od_id)."'", false);

        $active_extra = isset($row['active_extra']) ? (int)$row['active_extra'] : 0;
        $cancel_extra = isset($row['cancel_extra']) ? (int)$row['cancel_extra'] : 0;
        if ($active_extra === 0 && $cancel_extra === 0) return $info;

        $info['od_cart_price'] = (int)$info['od_cart_price'] + $active_extra + $cancel_extra;
        $info['od_cancel_price'] = (int)$info['od_cancel_price'] + $cancel_extra;
        $info['od_misu'] = (int)$info['od_misu'] + $active_extra;

        $order = sql_fetch("SELECT od_tax_flag FROM {$g5['g5_shop_order_table']} WHERE od_id='".sql_real_escape_string($od_id)."'", false);
        $tax_extra = isset($row['tax_extra']) ? (int)$row['tax_extra'] : 0;
        $free_extra = isset($row['free_extra']) ? (int)$row['free_extra'] : 0;
        $tax_total = (int)$info['od_tax_mny'] + (int)$info['od_vat_mny'];

        if (!empty($order['od_tax_flag'])) {
            $tax_total += $tax_extra;
            $info['od_free_mny'] = (int)$info['od_free_mny'] + $free_extra;
            if ($tax_total < 0) {
                $info['od_free_mny'] += $tax_total;
                $tax_total = 0;
            }
        } else {
            $tax_total += (int)$info['od_free_mny'] + $tax_extra + $free_extra;
            $info['od_free_mny'] = 0;
        }
        $info['od_tax_mny'] = (int)round($tax_total / 1.1);
        $info['od_vat_mny'] = $tax_total - $info['od_tax_mny'];

        return $info;
    }
}

/** 주문 DB를 실제 비즈니스 상품금액으로 동기화하고 과소결제 상태의 이용권 발급을 막는다. */
if (!function_exists('rb_shop_sync_order_info')) {
    function rb_shop_sync_order_info($od_id)
    {
        global $g5;

        $od_id = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$od_id);
        if ($od_id === '') return false;
        $info = rb_shop_get_order_info($od_id);
        if (!$info) return false;

        $order = sql_fetch("SELECT od_status FROM {$g5['g5_shop_order_table']} WHERE od_id='".sql_real_escape_string($od_id)."'", false);
        $sql = "UPDATE {$g5['g5_shop_order_table']} SET
                    od_cart_price='".(int)$info['od_cart_price']."',
                    od_cart_coupon='".(int)$info['od_cart_coupon']."',
                    od_coupon='".(int)$info['od_coupon']."',
                    od_send_coupon='".(int)$info['od_send_coupon']."',
                    od_cancel_price='".(int)$info['od_cancel_price']."',
                    od_send_cost='".(int)$info['od_send_cost']."',
                    od_misu='".(int)$info['od_misu']."',
                    od_tax_mny='".(int)$info['od_tax_mny']."',
                    od_vat_mny='".(int)$info['od_vat_mny']."',
                    od_free_mny='".(int)$info['od_free_mny']."'";

        // 실제 결제액이 부족한데 입금으로 기록된 경우 권한이 발급되지 않도록 주문으로 되돌린다.
        if ((int)$info['od_misu'] > 0 && isset($order['od_status']) && $order['od_status'] === '입금') {
            $sql .= ", od_status='주문'";
            sql_query("UPDATE {$g5['g5_shop_cart_table']} SET ct_status='주문'
                        WHERE od_id='".sql_real_escape_string($od_id)."' AND ct_status='입금'", false);
        }
        $sql .= " WHERE od_id='".sql_real_escape_string($od_id)."'";
        sql_query($sql, false);
        return $info;
    }
}

/** 코어 결제·입금 처리 뒤 기본 재계산으로 추가금이 사라지지 않게 마지막에 다시 검증한다. */
if (!function_exists('rb_shop_register_order_sync')) {
    function rb_shop_register_order_sync()
    {
        if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') return;

        $ids = array();
        foreach (array('od_id', 'order_no', 'no_oid', 'LGD_OID', 'oid') as $key) {
            if (!isset($_REQUEST[$key])) continue;
            $values = is_array($_REQUEST[$key]) ? $_REQUEST[$key] : array($_REQUEST[$key]);
            foreach ($values as $value) {
                $value = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$value);
                if ($value !== '') $ids[$value] = true;
            }
        }
        $session_order_id = function_exists('get_session') ? get_session('ss_order_id') : '';
        $session_order_id = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$session_order_id);
        if ($session_order_id !== '') $ids[$session_order_id] = true;
        if (!$ids) return;

        register_shutdown_function(function () use ($ids) {
            foreach (array_keys($ids) as $order_id) {
                $type = rb_shop_order_type_from_order($order_id);
                if (!rb_shop_is_special_item_type(isset($type['id']) ? $type['id'] : 0)) continue;
                rb_shop_sync_order_info($order_id);
                if (function_exists('rb_file_issue_order_downloads')) rb_file_issue_order_downloads($order_id);
                if (function_exists('rb_media_issue_order_rights')) rb_media_issue_order_rights($order_id);
                if (function_exists('rb_reservation_issue_order')) {
                    rb_reservation_issue_order($order_id);
                    if (function_exists('rb_reservation_sync_order_status')) rb_reservation_sync_order_status($order_id);
                }
            }
        });
    }
}
add_event('common_header', 'rb_shop_register_order_sync', 999);

function rb_shop_order_types_from_orders($order_ids)
{
    global $g5;

    $ids = array();
    foreach ((array) $order_ids as $order_id) {
        $order_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $order_id);
        if ($order_id !== '') $ids[$order_id] = true;
    }
    $ids = array_keys($ids);
    if (!$ids) return array();

    $types = array();
    if (!rb_shop_table_has_column($g5['g5_shop_item_table'], 'it_types')) {
        foreach ($ids as $order_id) $types[$order_id] = rb_shop_order_type_meta(0);
        return $types;
    }

    $quoted_ids = array();
    foreach ($ids as $order_id) $quoted_ids[] = "'".sql_real_escape_string($order_id)."'";
    $type_sql = "COALESCE(NULLIF(i.it_types,''),'0')";
    $result = sql_query("SELECT c.od_id,
                                COUNT(DISTINCT {$type_sql}) AS type_count,
                                MIN(CAST({$type_sql} AS UNSIGNED)) AS item_type
                           FROM {$g5['g5_shop_cart_table']} c
                           LEFT JOIN {$g5['g5_shop_item_table']} i ON i.it_id=c.it_id
                          WHERE c.od_id IN (".implode(',', $quoted_ids).") AND c.io_type='0'
                          GROUP BY c.od_id", false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $order_id = (string) $row['od_id'];
            if ((int) $row['type_count'] === 1) {
                $types[$order_id] = rb_shop_order_type_meta((int) $row['item_type']);
            } else {
                $types[$order_id] = array('id'=>-1, 'key'=>'mixed', 'label'=>'혼합주문', 'short_label'=>'혼합', 'available'=>false);
            }
        }
    }
    return $types;
}

if (!function_exists('rb_shop_resolve_order_status')) {
    /**
     * 한 주문서에 여러 상품/입점사가 섞여도 가장 덜 진행된 유효 상태를 주문서 상태로 사용한다.
     */
    function rb_shop_resolve_order_status($od_id)
    {
        global $g5;
        $od_id = preg_replace('/[^0-9A-Za-z_-]/', '', (string)$od_id);
        if ($od_id === '') return '';

        $row = sql_fetch("SELECT COUNT(*) AS total_count,
                                SUM(CASE WHEN ct_status='주문' THEN 1 ELSE 0 END) AS order_count,
                                SUM(CASE WHEN ct_status='입금' THEN 1 ELSE 0 END) AS paid_count,
                                SUM(CASE WHEN ct_status='준비' THEN 1 ELSE 0 END) AS ready_count,
                                SUM(CASE WHEN ct_status='배송' THEN 1 ELSE 0 END) AS shipping_count,
                                SUM(CASE WHEN ct_status='완료' THEN 1 ELSE 0 END) AS complete_count,
                                SUM(CASE WHEN ct_status IN ('취소','반품','품절') THEN 1 ELSE 0 END) AS cancelled_count
                           FROM {$g5['g5_shop_cart_table']}
                          WHERE od_id='".sql_real_escape_string($od_id)."'", false);
        $total = isset($row['total_count']) ? (int)$row['total_count'] : 0;
        if ($total < 1) return '';
        if ((int)$row['cancelled_count'] === $total) return '취소';

        foreach (array('주문'=>'order_count', '입금'=>'paid_count', '준비'=>'ready_count', '배송'=>'shipping_count', '완료'=>'complete_count') as $status=>$key) {
            if (!empty($row[$key])) return $status;
        }
        return '';
    }
}

function rb_shop_special_order_statuses($type)
{
    return rb_shop_is_special_item_type($type)
        ? array('주문', '입금', '완료', '취소')
        : array('주문', '입금', '준비', '배송', '완료', '취소', '반품', '품절');
}

/** 주문 상세 화면의 상품·이용자 명칭을 주문유형에 맞춰 공통으로 제공한다. */
function rb_shop_order_view_labels($type)
{
    $labels = array(
        1 => array('item_list'=>'예약상품 목록', 'items'=>'예약상품', 'customer_section'=>'예약자 정보', 'customer'=>'예약자'),
        2 => array('item_list'=>'파일상품 목록', 'items'=>'파일상품', 'customer_section'=>'구매자 정보', 'customer'=>'구매자'),
        3 => array('item_list'=>'미디어상품 목록', 'items'=>'미디어상품', 'customer_section'=>'이용자 정보', 'customer'=>'이용자'),
    );
    $type = (int) $type;
    return isset($labels[$type]) ? $labels[$type] : array(
        'item_list'=>'주문상품 목록',
        'items'=>'주문상품',
        'customer_section'=>'주문자/배송지 정보',
        'customer'=>'주문하신 분',
    );
}

function rb_shop_request_item_types()
{
    global $g5;
    $posted = isset($_POST['it_id']) ? $_POST['it_id'] : array();
    if (!is_array($posted)) $posted = array($posted);

    $ids = array();
    array_walk_recursive($posted, function ($value) use (&$ids) {
        $value = preg_replace('/[^0-9A-Za-z_-]/', '', (string) $value);
        if ($value !== '') $ids[$value] = true;
    });
    if (!$ids) return array();
    if (!rb_shop_table_has_column($g5['g5_shop_item_table'], 'it_types')) return array(0);

    $escaped = array();
    foreach (array_keys($ids) as $id) $escaped[] = "'".sql_real_escape_string($id)."'";
    $result = sql_query("SELECT it_id, COALESCE(NULLIF(it_types,''),'0') AS item_type
                         FROM {$g5['g5_shop_item_table']}
                         WHERE it_id IN (".implode(',', $escaped).")", false);
    $types = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) $types[(int) $row['item_type']] = true;
    }
    return array_keys($types);
}

function rb_shop_enforce_special_direct_checkout()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
    if ($script !== 'cartupdate.php' || strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') return;

    $act = isset($_POST['act']) ? (string) $_POST['act'] : '';
    if (in_array($act, array('buy', 'alldelete', 'seldelete', 'optionmod'), true)) return;

    $types = rb_shop_request_item_types();
    $special = array_values(array_filter($types, 'rb_shop_is_special_item_type'));
    if (!$special) return;

    if (count($types) !== 1 || count($special) !== 1) {
        alert('예약·파일·미디어 상품은 다른 유형의 상품과 함께 주문할 수 없습니다.');
    }
    $type = (int) $special[0];
    if (!rb_shop_special_type_available($type)) {
        $meta = rb_shop_order_type_meta($type);
        alert($meta['label'].' 기능을 현재 사용할 수 없습니다.');
    }

    // 영카트의 ss_cart_direct를 그대로 사용하되 일반 장바구니와 섞이지 않게 한다.
    $_POST['sw_direct'] = 1;
    $_REQUEST['sw_direct'] = 1;
    set_session('ss_rb_direct_order_type', (string) $type);
}
add_event('common_header', 'rb_shop_enforce_special_direct_checkout', 1);

function rb_shop_special_buy_only_front()
{
    global $it;
    if (!isset($it['it_id'], $it['it_types']) || !rb_shop_is_special_item_type($it['it_types'])) return;
    $meta = rb_shop_order_type_meta((int) $it['it_types']);
    $available = !empty($meta['available']);
    ?>
    <script>
    (function () {
        function applySpecialPurchase() {
            var forms = document.querySelectorAll('form[name="fitem"], #fitem');
            document.querySelectorAll('.sit_btn_cart, #sit_btn_cart').forEach(function (button) {
                button.remove();
            });
            forms.forEach(function (form) {
                var direct = form.querySelector('input[name="sw_direct"]');
                if (direct) direct.value = '1';
                form.setAttribute('data-rb-order-type', <?php echo json_encode($meta['key']); ?>);
            });
            <?php if (!$available) { ?>
            document.querySelectorAll('.sit_btn_buy, #sit_btn_buy').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    alert(<?php echo json_encode($meta['label'].' 기능을 현재 사용할 수 없습니다.'); ?>);
                });
            });
            <?php } ?>
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applySpecialPurchase);
        else applySpecialPurchase();
    }());
    </script>
    <?php
}
add_event('tail_sub', 'rb_shop_special_buy_only_front', 5);
