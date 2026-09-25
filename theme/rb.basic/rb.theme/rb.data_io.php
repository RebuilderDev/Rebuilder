<?php
include_once '../../../common.php';
header('Content-Type: application/json; charset=utf-8');
http_response_code(410);
echo json_encode(array('status'=>'error','msg'=>'설정 JSON 불러오기는 종료되었습니다. 테마설정 패널에서 ZIP을 내보내고, FTP 업로드 후 관리자 테마설정에서 설치해 주세요.'), JSON_UNESCAPED_UNICODE);
