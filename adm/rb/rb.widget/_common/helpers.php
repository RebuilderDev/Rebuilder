<?php
if (!function_exists('rb_fetch_all')) {
    function rb_fetch_all($res){
        $out = array();
        if(!$res) return $out;
        while($r = sql_fetch_array($res)) $out[] = $r;
        return $out;
    }
}
if (!function_exists('rb_sql_cnt')) {
    function rb_sql_cnt($sql){
        $row = sql_fetch($sql);
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
}
if (!function_exists('rb_nf')) {
    function rb_nf($n){ return number_format((int)$n); }
}
if (!function_exists('rb_pill')) {
    function rb_pill($t){ return '<span class="rb-pill">'.$t.'</span>'; }
}
if (!function_exists('rb_plain')) {
    function rb_plain($s){
        $s = strip_tags((string)$s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }
}
if (!function_exists('rb_preview')) {
    function rb_preview($s, $len = 99){
        $cut = cut_str($s, $len);
        return $cut . (($cut !== $s) ? '…' : '');
    }
}

/**
 * g5_new 기반 최신 글/댓글 공용 추출
 * @param bool $only_comments  true: 댓글만, false: 원글만
 * @param int  $limit
 * @return array
 */
if (!function_exists('rb_fetch_recent_from_new')) {
    function rb_fetch_recent_from_new($only_comments, $limit){
        global $g5;
        $cond = $only_comments ? "a.wr_id <> a.wr_parent" : "a.wr_id = a.wr_parent";
        $rows = rb_fetch_all(sql_query("
          SELECT a.bo_table,
                 ".($only_comments ? "a.wr_id, a.wr_parent" : "a.wr_parent AS wr_id, a.wr_parent").",
                 a.mb_id, a.bn_datetime, a.bn_id,
                 b.bo_subject
          FROM {$g5['board_new_table']} a
          JOIN {$g5['board_table']} b ON b.bo_table = a.bo_table
          WHERE {$cond}
          ORDER BY a.bn_id DESC
          LIMIT ".($limit * 3)
        ));

        $out = array();
        foreach ($rows as $r) {
            $wt = $g5['write_prefix'].$r['bo_table'];
            if ($only_comments) {
                $roww = sql_fetch("SELECT wr_id, wr_parent, wr_content, wr_name FROM {$wt} WHERE wr_id = '{$r['wr_id']}'");
                if(!$roww) continue;
                $preview = rb_preview(rb_plain($roww['wr_content'] ?? ''), 80);
                $out[] = array(
                    'bo_table'   => $r['bo_table'],
                    'bo_subject' => $r['bo_subject'],
                    'subject'    => $preview,
                    'link'       => get_pretty_url($r['bo_table'], $roww['wr_parent']).'#c_'.$roww['wr_id'],
                    'time'       => substr($r['bn_datetime'], 0, 16),
                    '_ts'        => $r['bn_datetime'],
                    'mb_id'      => (string)$r['mb_id'],
                    'wr_name'    => (string)($roww['wr_name'] ?? '')
                );
            } else {
                $roww = sql_fetch("SELECT wr_id, wr_subject, wr_name FROM {$wt} WHERE wr_id = '{$r['wr_id']}'");
                if(!$roww) continue;
                $subject = $roww['wr_subject'] ? conv_subject($roww['wr_subject'], 100) : '(제목없음)';
                $out[] = array(
                    'bo_table'   => $r['bo_table'],
                    'bo_subject' => $r['bo_subject'],
                    'subject'    => $subject,
                    'link'       => get_pretty_url($r['bo_table'], $roww['wr_id']),
                    'time'       => substr($r['bn_datetime'], 0, 16),
                    '_ts'        => $r['bn_datetime'],
                    'mb_id'      => (string)$r['mb_id'],
                    'wr_name'    => (string)($roww['wr_name'] ?? '')
                );
            }
            if (count($out) >= $limit) break;
        }
        usort($out, function($a,$b){ return strcmp($b['_ts'], $a['_ts']); });
        return $out;
    }
}
