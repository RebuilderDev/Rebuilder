<?php
include_once("../_common.php");

$did = isset($_GET['did']) ? preg_replace('/[^0-9]/', '', (string)$_GET['did']) : '';
if ($did === '') {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// // 파일 경로 후보(신규/기존)
$cands = array(
    G5_DATA_PATH . '/rb_display/' . $did, // // 신규 폴더(권장)
    G5_DATA_PATH . '/banners/' . $did     // // 기존 폴더(호환)
);

$path = '';
for ($i = 0; $i < count($cands); $i++) {
    if (is_file($cands[$i])) {
        $path = $cands[$i];
        break;
    }
}

if ($path === '') {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// // MIME
$info = @getimagesize($path);
$mime = (is_array($info) && isset($info['mime'])) ? $info['mime'] : 'application/octet-stream';

// // 캐시
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=300');
header('Pragma: public');

$fp = @fopen($path, 'rb');
if (!$fp) {
    header('HTTP/1.1 404 Not Found');
    exit;
}
fpassthru($fp);
fclose($fp);
exit;
