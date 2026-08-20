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
    $message = 'DB 업데이트 요청을 처리하지 못했습니다. 잠시 후 다시 시도해 주세요.';
} else {
    $client = rb_license_client_get();

    if (empty($client['registered_at'])) {
        $message = '설치 토큰을 먼저 등록해 주세요.';
    } else {
        // 도메인 변경과 복제 여부를 먼저 확인한 뒤 인증된 설치에만 DB 구조를 요청합니다.
        $check = rb_license_check_remote();
        if (empty($check['success'])) {
            $message = isset($check['message']) ? $check['message'] : '설치 인증상태를 확인하지 못했습니다.';
        } elseif (isset($check['data']['state']) && $check['data']['state'] === 'clone_pending') {
            $message = isset($check['data']['notice']) ? $check['data']['notice'] : '복제된 설치환경입니다. 빌더설정에서 새 설치 토큰을 등록해주세요.';
        } else {
            $response = rb_license_fetch_schema();
            if (empty($response['success'])) {
                $message = isset($response['message']) ? $response['message'] : 'DB 업데이트 구조를 받지 못했습니다.';
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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => (bool) $success,
        'message' => (string) $message,
    ), JSON_UNESCAPED_UNICODE);
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
