/* 구 코어는 성공 시 빈 본문을 반환하고, 신 코어는 주문별 토큰을 함께 반환한다. */
function rb_shop_order_state_accept(xhr) {
    var form = document.forms.forderform;
    if (form && form.elements.g5_checkout_id) {
        if (typeof g5_order_state_accept !== 'function') {
            return '결제 요청 확인 파일을 불러오지 못했습니다. 새로고침 후 다시 시도해 주세요.';
        }
        return g5_order_state_accept(xhr);
    }
    return '';
}
