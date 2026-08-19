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
