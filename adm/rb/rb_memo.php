<?php
include_once('./_common.php');
if (!$is_admin) exit; // 필요에 따라 권한 체크

$memo_dir = G5_DATA_PATH.'/rb_memo';
if (!is_dir($memo_dir)) @mkdir($memo_dir, G5_DIR_PERMISSION, true);

$memo_file = $memo_dir.'/'.$member['mb_id'].'-memo.txt';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$index = isset($_POST['index']) ? (int)$_POST['index'] : 0;

// 메모 불러오기
function rb_memo_load($file) {
    if (!file_exists($file)) return array();
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return $lines ? $lines : array();
}

// 메모 저장하기
function rb_memo_save($file, $list) {
    file_put_contents($file, implode(PHP_EOL, $list));
}

if ($mode === 'add' && $content !== '') {
    $list = rb_memo_load($memo_file);
    $line = date('Y-m-d H:i:s').'|'.$content;
    array_unshift($list, $line); // 최신을 위로
    rb_memo_save($memo_file, $list);
    echo json_encode(array('result'=>'ok','list'=>$list));
    exit;
}

if ($mode === 'delete') {
    $list = rb_memo_load($memo_file);
    if (isset($list[$index])) {
        unset($list[$index]);
        $list = array_values($list);
        rb_memo_save($memo_file, $list);
    }
    echo json_encode(array('result'=>'ok','list'=>$list));
    exit;
}

if ($mode === 'list') {
    $list = rb_memo_load($memo_file);
    echo json_encode(array('result'=>'ok','list'=>$list));
    exit;
}

echo json_encode(array('result'=>'error'));
