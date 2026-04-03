<?php
// 공통 상수/설정
$RB_LIST_LIMIT  = 5;
$RB_EMPTY_TEXT  = '자료가 없습니다.';
$new_point_rows = 5;
$new_write_rows = 5;

$today             = G5_TIME_YMD;
$member_level_max  = ($is_admin!='super') ? (int)$member['mb_level'] : 100;

// 회원 집계
$sql_common_m = " FROM {$g5['member_table']} WHERE 1 ";
if($is_admin!='super') $sql_common_m .= " AND mb_level <= '{$member_level_max}' ";
$total_member     = rb_sql_cnt("SELECT COUNT(*) AS cnt {$sql_common_m}");
$leave_member     = rb_sql_cnt("SELECT COUNT(*) AS cnt {$sql_common_m} AND mb_leave_date <> ''");
$intercept_member = rb_sql_cnt("SELECT COUNT(*) AS cnt {$sql_common_m} AND mb_intercept_date <> ''");
$new_member_today = rb_sql_cnt("SELECT COUNT(*) AS cnt {$sql_common_m} AND LEFT(mb_datetime,10) = '{$today}'");

// 게시물/댓글 집계
$tot_bc = sql_fetch("
  SELECT SUM(bo_count_write) AS posts, SUM(bo_count_comment) AS cmts
  FROM {$g5['board_table']}
");
$post_total = (int)($tot_bc['posts'] ?? 0);
$cmt_total  = (int)($tot_bc['cmts']  ?? 0);

$today_tot = sql_fetch("
  SELECT
    SUM(CASE WHEN wr_id = wr_parent THEN 1 ELSE 0 END) AS posts_today,
    SUM(CASE WHEN wr_id <> wr_parent THEN 1 ELSE 0 END) AS cmts_today
  FROM {$g5['board_new_table']}
  WHERE LEFT(bn_datetime,10) = '{$today}'
");
$post_today = (int)($today_tot['posts_today'] ?? 0);
$cmt_today  = (int)($today_tot['cmts_today']  ?? 0);

// 현재접속
$connect_now = 0;
if (!empty($g5['login_table'])) {
    $connect_now = rb_sql_cnt("
        SELECT COUNT(*) AS cnt
        FROM {$g5['login_table']}
        WHERE mb_id <> '{$config['cf_admin']}'
    ");
}

// 방문 집계
$visit_acc = $visit_today = $visit_yesterday = $visit_week = $visit_month = 0;
$visit_max_cnt = 0; $visit_max_date = '';
if (!empty($g5['visit_table'])) {
    $visit_acc = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['visit_table']}");
    $visit_today = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['visit_table']} WHERE vi_date = '{$today}'");
    $yesterday = date('Y-m-d', G5_SERVER_TIME - 86400);
    $visit_yesterday = rb_sql_cnt("SELECT COUNT(*) AS cnt FROM {$g5['visit_table']} WHERE vi_date = '{$yesterday}'");
    $visit_week = rb_sql_cnt("
        SELECT COUNT(*) AS cnt
        FROM {$g5['visit_table']}
        WHERE YEARWEEK(vi_date, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $visit_month = rb_sql_cnt("
        SELECT COUNT(*) AS cnt
        FROM {$g5['visit_table']}
        WHERE DATE_FORMAT(vi_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    ");
    $mx = sql_fetch("
        SELECT vi_date, COUNT(*) AS cnt
        FROM {$g5['visit_table']}
        GROUP BY vi_date
        ORDER BY cnt DESC
        LIMIT 1
    ");
    if ($mx) { $visit_max_cnt = (int)$mx['cnt']; $visit_max_date = $mx['vi_date']; }
}

// 우측: 최근 포인트 (N+1 제거)
$point_rows = array();
if (!auth_check_menu($auth, '200200', 'r', true)) {
    $point_rows = rb_fetch_all(sql_query("
      SELECT p.*, m.mb_nick
      FROM {$g5['point_table']} p
      LEFT JOIN {$g5['member_table']} m ON m.mb_id = p.mb_id
      ORDER BY p.po_id DESC
      LIMIT 6
    "));
}

// 우측: 1:1 문의
$qa_rows = array();
if (!empty($g5['qa_content_table'])) {
    $qa_rows = rb_fetch_all(sql_query("
      SELECT qa_id, qa_subject, qa_datetime, qa_status
      FROM {$g5['qa_content_table']} WHERE qa_type = '0'
      ORDER BY qa_id DESC
      LIMIT 8
    "));
}

// 카드 하단 목록 데이터
$new_member_list = rb_fetch_all(sql_query("
  SELECT mb_id, mb_name, mb_nick, mb_email, mb_datetime
  FROM {$g5['member_table']}
  ".($is_admin!='super' ? "WHERE mb_level <= '{$member_level_max}' " : "")."
  ORDER BY mb_datetime DESC
  LIMIT {$RB_LIST_LIMIT}
"));

$visit_list = array();
if (!empty($g5['visit_table'])) {
    $visit_list = rb_fetch_all(sql_query("
        SELECT vi_ip, vi_time, vi_date, vi_referer
        FROM {$g5['visit_table']}
        WHERE vi_date='{$today}'
        ORDER BY vi_time DESC
        LIMIT {$RB_LIST_LIMIT}
    "));
}

$cmt_list  = rb_fetch_recent_from_new(true,  $RB_LIST_LIMIT);
$post_list = rb_fetch_recent_from_new(false, $RB_LIST_LIMIT);

// 상품문의
$inq_list = array();
if (defined('G5_USE_SHOP') && G5_USE_SHOP && !empty($g5['g5_shop_item_qa_table'])) {
  $inq_list = rb_fetch_all(sql_query("
    SELECT iq_id, iq_subject, iq_name, iq_time, iq_answer
    FROM {$g5['g5_shop_item_qa_table']}
    ORDER BY iq_id DESC
    LIMIT {$RB_LIST_LIMIT}
  "));
}

// 주문
$od_list = array();
if (defined('G5_USE_SHOP') && G5_USE_SHOP && !empty($g5['g5_shop_order_table'])) {
  $od_list = rb_fetch_all(sql_query("
    SELECT od_id, od_name, od_hp, od_status, od_time, od_cart_price
    FROM {$g5['g5_shop_order_table']}
    ORDER BY od_id DESC
    LIMIT {$RB_LIST_LIMIT}
  "));
}
