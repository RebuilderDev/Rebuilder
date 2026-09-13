<?php
include_once('../../common.php');
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

if (!$is_member) {
    send_json_response(false, '회원만 이용하실 수 있습니다.');
}

// JSON 응답 함수
function send_json_response($success, $message, $image_url = '', $image_data = '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => $success, 'message' => $message, 'image_url' => $image_url, 'image_data' => $image_data]);
    exit;
}

// 요청 방식과 파일 확인
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        send_json_response(false, '파일 업로드가 완료되지 않았습니다. 파일 용량과 네트워크 연결을 확인해주세요.');
    }
    $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';

    if (empty($mb_id)) {
        send_json_response(false, '회원 아이디가 없습니다.');
    }

    // 회원 ID 앞 두 글자로 디렉토리 생성
    $first_two_chars = substr($mb_id, 0, 2);
    $upload_dir = G5_DATA_PATH . "/member_image/$first_two_chars";

    // 디렉토리 존재 확인 및 생성
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, G5_DIR_PERMISSION, true) && !is_dir($upload_dir)) {
            send_json_response(false, '디렉토리를 생성할 수 없습니다.');
        }
        chmod($upload_dir, G5_DIR_PERMISSION);
    }

    // 파일 확장자 확인
    $allowed_ext = ['gif', 'jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        send_json_response(false, '허용되지 않은 파일 형식입니다.');
    }

    // 용량 제한 검사
    if ($file['size'] > $config['cf_member_img_size']) {
        send_json_response(false, '파일 크기가 너무 큽니다. 최대 ' . number_format($config['cf_member_img_size']) . ' 바이트까지 업로드 가능합니다.');
    }

    // 파일명 지정 (고정된 GIF 이름 사용)
    $new_filename = get_mb_icon_name($mb_id).'.gif';
    $upload_path = "$upload_dir/$new_filename";

    // 기존 사진을 덮어쓰기 전에 실제 이미지 형식을 확인합니다.
    $size = @getimagesize($file['tmp_name']);
    if (!$size || !in_array($size[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
        send_json_response(false, '잘못된 이미지 파일입니다.');
    }

    // 파일 업로드
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        send_json_response(false, '파일 업로드 실패');
    }

    // 섬네일 생성 (원본 크기보다 크면 생성)
    if ($size[0] > $config['cf_member_img_width'] || $size[1] > $config['cf_member_img_height']) {
        $thumb = thumbnail($new_filename, $upload_dir, $upload_dir, $config['cf_member_img_width'], $config['cf_member_img_height'], true, true);
        if ($thumb) {
            // 애니메이션 GIF는 thumbnail()이 원본 파일명을 그대로 반환합니다.
            if ($thumb !== $new_filename) {
                @unlink($upload_path);
                if (!rename("$upload_dir/$thumb", $upload_path)) {
                    send_json_response(false, '프로필 사진을 저장하지 못했습니다.');
                }
            }
        } else {
            @unlink($upload_path);
            send_json_response(false, '섬네일 생성 실패');
        }
    }
    @chmod($upload_path, G5_FILE_PERMISSION);

    // PWA의 이미지 캐시를 거치지 않고 저장된 최종 사진을 표시할 수 있도록 함께 반환합니다.
    $image_content = file_get_contents($upload_path);
    if ($image_content === false) {
        send_json_response(false, '저장된 프로필 사진을 읽지 못했습니다.');
    }
    $image_data = 'data:'.image_type_to_mime_type($size[2]).';base64,'.base64_encode($image_content);
    $image_url = G5_DATA_URL . "/member_image/$first_two_chars/$new_filename?v=".G5_SERVER_TIME;
    send_json_response(true, '파일 업로드 성공', $image_url, $image_data);
} else {
    send_json_response(false, '잘못된 요청');
}
