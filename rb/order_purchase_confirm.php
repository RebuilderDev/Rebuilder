<?php
ob_start();
include_once('./_common.php');

function rb_purchase_confirm_json($ok, $message, $data = array(), $status = 200)
{
    if (ob_get_length()) ob_clean();
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array(
        'ok' => (bool) $ok,
        'message' => (string) $message,
    ), (array) $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '') !== 'POST') {
    rb_purchase_confirm_json(false, '잘못된 요청입니다.', array(), 405);
}

if (!$is_member || empty($member['mb_id'])) {
    rb_purchase_confirm_json(false, '구매자 본인만 구매를 확정할 수 있습니다.', array(), 403);
}

if (!function_exists('rb_shop_purchase_confirm_enabled') || !rb_shop_purchase_confirm_enabled()) {
    rb_purchase_confirm_json(false, '구매 확정 기능을 사용하지 않습니다.', array(), 403);
}

$od_id = isset($_POST['od_id']) ? preg_replace('/[^0-9A-Za-z_-]/', '', (string) $_POST['od_id']) : '';
$token = isset($_POST['token']) ? trim((string) $_POST['token']) : '';
if ($od_id === '' || $token === '') {
    rb_purchase_confirm_json(false, '구매 확정 요청정보가 올바르지 않습니다.', array(), 422);
}

$session_key = 'rb_purchase_confirm_'.$od_id;
$session_token = (string) get_session($session_key);
if ($session_token === '' || !hash_equals($session_token, $token)) {
    rb_purchase_confirm_json(false, '요청이 만료되었습니다. 화면을 새로고침한 뒤 다시 시도해 주세요.', array(), 419);
}
set_session($session_key, '');

if (!function_exists('rb_shop_confirm_purchase')) {
    rb_purchase_confirm_json(false, '구매 확정 기능을 사용할 수 없습니다.', array(), 503);
}

$result = rb_shop_confirm_purchase($od_id, $member['mb_id']);
if (empty($result['ok'])) {
    rb_purchase_confirm_json(false, isset($result['message']) ? $result['message'] : '구매 확정에 실패했습니다.', array(), 422);
}

rb_purchase_confirm_json(true, '구매가 확정되었습니다.', array(
    'review_url' => isset($result['review_url']) ? $result['review_url'] : '',
    'confirmed_count' => isset($result['confirmed_count']) ? (int) $result['confirmed_count'] : 0,
    'order_complete' => !empty($result['order_complete']),
));
