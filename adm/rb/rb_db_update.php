<?php
$sub_menu = '000000';
include_once('./_common.php');
include_once('./rb_license.lib.php');

auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$message = '';
$success = false;
$is_ajax = isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1';
$rb_db_update_ajax_ob_level = ob_get_level();
if ($is_ajax) {
    ob_start();
    rb_license_ajax_guard('DB 설치 및 업데이트', $rb_db_update_ajax_ob_level);
}
$result_view = isset($_GET['result']) ? (string) $_GET['result'] : '';
$result_session_key = 'ss_rb_db_update_result';

if ($result_view === '1') {
    $stored_result = get_session($result_session_key);
    set_session($result_session_key, '');
    if (is_array($stored_result)) {
        $success = !empty($stored_result['success']);
        $message = isset($stored_result['message']) ? (string) $stored_result['message'] : '';
    } else {
        $message = 'DB 업데이트 결과를 확인할 수 없습니다.';
    }
} elseif ($result_view === 'ajax_error') {
    $stage = isset($_GET['stage']) && $_GET['stage'] === 'registration' ? '인증 토큰 등록' : 'DB 설치 및 업데이트';
    $status = isset($_GET['status']) ? (int) $_GET['status'] : 0;
    $reason = isset($_GET['reason']) ? (string) $_GET['reason'] : '';
    $http_reasons = array(
        403 => '설치 서버가 요청 접근을 거절했습니다',
        404 => '설치 서버에서 요청한 PHP 파일을 찾지 못했습니다',
        500 => '설치 서버에서 PHP 요청 처리 중 오류가 발생했습니다',
        502 => '설치 서버가 PHP 처리 응답을 받지 못했습니다 (Bad Gateway)',
        503 => '설치 서버가 현재 요청을 처리할 수 없는 상태입니다',
        504 => '설치 서버의 PHP 응답 대기 시간이 초과되었습니다 (Gateway Timeout)',
    );
    if ($reason === 'timeout') {
        $cause = '요청의 응답 대기 시간이 초과되었습니다';
    } elseif (isset($http_reasons[$status])) {
        $cause = $http_reasons[$status];
    } elseif ($status >= 400 && $status <= 599) {
        $cause = '설치 서버가 HTTP 오류 응답을 반환했습니다';
    } elseif ($reason === 'invalid_response') {
        $cause = '설치 서버가 정상적인 JSON 처리 결과를 반환하지 않았습니다';
    } else {
        $cause = '브라우저가 설치 서버의 응답을 받지 못했습니다';
    }
    $message = $stage.' 실패: '.$cause.'.'.($status >= 100 && $status <= 599 ? ' [HTTP '.$status.']' : ' [response_unavailable]');
} else {
    try {
        $client = rb_license_client_get();

        if (isset($client['error'])) {
            $message = $client['error'];
        } elseif (empty($client['registered_at'])) {
            $message = '인증 토큰을 먼저 등록해 주세요.';
        } else {
            // 도메인 변경과 복제 여부를 먼저 확인한 뒤 인증된 설치에만 DB 구조를 요청합니다.
            $check = rb_license_check_remote();
            if (empty($check['success'])) {
                $message = '설치 인증상태 확인 실패: '.(isset($check['message']) ? $check['message'] : '공식 인증 서버의 처리 결과가 없습니다.');
            } elseif (isset($check['data']['state']) && $check['data']['state'] === 'clone_pending') {
                $message = isset($check['data']['notice']) ? $check['data']['notice'] : '복제된 설치환경입니다. 빌더설정에서 새 인증 토큰을 등록해 주세요.';
            } else {
                $response = rb_license_fetch_schema();
                if (empty($response['success'])) {
                    $message = 'DB 설치 구조 수신 실패: '.(isset($response['message']) ? $response['message'] : '공식 인증 서버의 처리 결과가 없습니다.');
                } else {
                    $applied = rb_license_apply_remote_schema($response['data']);
                    $success = !empty($applied['success']);
                    $message = isset($applied['message']) ? $applied['message'] : 'DB 업데이트를 완료하지 못했습니다.';
                    if ($success) {
                        foreach (array('seo', 'banners', 'logos') as $directory) {
                            $path = G5_DATA_PATH.'/'.$directory;
                            if (!is_dir($path)) {
                                @mkdir($path, G5_DIR_PERMISSION, true);
                            }
                            if (is_dir($path)) {
                                @chmod($path, G5_DIR_PERMISSION);
                            }
                        }
                    }
                }
            }
        }
    } catch (Throwable $error) {
        $failure = rb_license_exception_result($error, 'DB 설치 및 업데이트');
        $success = false;
        $message = $failure['message'];
    }
}

if ($is_ajax) {
    $show_result = isset($_REQUEST['show_result']) ? (string) $_REQUEST['show_result'] : '';
    if ($show_result === '1' || ($show_result === 'error' && !$success)) {
        $result_message = $message;
        if ($success && isset($_REQUEST['result_context']) && (string) $_REQUEST['result_context'] === 'token_update') {
            $result_message = '토큰 등록 및 업데이트가 완료되었습니다.';
        }
        set_session($result_session_key, array(
            'success' => $success,
            'message' => $result_message,
        ));
    }
    while (ob_get_level() > $rb_db_update_ajax_ob_level) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => (bool) $success,
        'message' => (string) $message,
    ), JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
    exit;
}

$g5['title'] = 'DB 설치 및 업데이트';
include_once('../admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<?php if (!$success) { ?>
<div class="btn_confirm01 btn_confirm">
    <a href="./rb_form.php" class="btn_frmline">빌더설정으로 이동</a>
</div>
<?php } ?>

<?php
include_once('../admin.tail.php');
