<?php
if (!defined('_GNUBOARD_')) exit;

// 테마와 무관한 현재 노드의 설정이며, 상위 노드에서 상속하지 않는다.
function rb_subtitle_hidden($node)
{
    if (!is_string($node) || $node === '') return false;
    $result = sql_query("SELECT t_code FROM rb_subtitle_hide WHERE t_code='".sql_real_escape_string($node)."' LIMIT 1", false);
    return $result && (bool)sql_fetch_array($result);
}

function rb_subtitle_save($node, $hidden)
{
    if (!is_string($node) || $node === '' || mb_strlen($node, 'UTF-8') > 100 || preg_match('/[\x00-\x1f\x7f]/', $node)
        || !in_array($hidden, array('0', '1'), true)) {
        throw new RuntimeException('서브 타이틀 설정을 확인해 주세요.');
    }
    // DB 구조는 공식 API의 DB 업데이트에서만 만든다. 이전 DB에서는 기본 노출을 유지한다.
    $columns = sql_query("SHOW COLUMNS FROM rb_subtitle_hide LIKE 't_code'", false);
    if (!$columns || !sql_fetch_array($columns)) {
        if ($hidden === '0') return;
        throw new RuntimeException('빌더 DB 업데이트가 필요합니다.');
    }
    $code = sql_real_escape_string($node);
    $sql = $hidden === '1'
        ? "INSERT IGNORE INTO rb_subtitle_hide (t_code) VALUES ('{$code}')"
        : "DELETE FROM rb_subtitle_hide WHERE t_code='{$code}'";
    if (!sql_query($sql, false)) throw new RuntimeException('서브 타이틀 설정을 저장하지 못했습니다.');
}
