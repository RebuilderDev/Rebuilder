<?php
include_once("../_common.php");

$did = isset($_GET['did']) ? preg_replace('/[^0-9]/', '', (string)$_GET['did']) : '';
if ($did === '') exit;

$path = G5_DATA_PATH . '/rb_display_design/' . $did;
if (!is_file($path)) exit;

// // MIME 추정
$mime = 'application/octet-stream';
$info = @getimagesize($path);
if (is_array($info) && isset($info['mime']) && $info['mime']) $mime = $info['mime'];

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=60');

$fp = @fopen($path, 'rb');
if (!$fp) exit;
fpassthru($fp);
@fclose($fp);
exit;
?>
