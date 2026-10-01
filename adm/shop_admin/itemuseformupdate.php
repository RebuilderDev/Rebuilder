<?php
$sub_menu = '400650';
include_once('./_common.php');

check_demo();

if ($w == 'd')
    auth_check_menu($auth, $sub_menu, "d");
else
    auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

$posts = array();
$check_keys = array('is_subject', 'is_content', 'is_confirm', 'is_reply_subject', 'is_reply_content', 'is_id');

foreach($check_keys as $key){

    if( in_array($key, array('is_content', 'is_reply_content')) ){
        $posts[$key] = isset($_POST[$key]) ? $_POST[$key] : '';
    } else if( $key === 'is_id' ) {
        $posts[$key] = isset($_POST[$key]) ? (int) $_POST[$key] : 0;
    } else {
        $posts[$key] = isset($_POST[$key]) ? addslashes(clean_xss_tags(stripslashes($_POST[$key]), 1, 1)) : '';
    }
}

if ($w == "u")
{
    // 5.6.12 등 답변 컬럼이 없는 코어는 권한·토큰 검증을 마친 저장 요청에서만 보완한다.
    $reply_columns = array('is_reply_subject'=>"VARCHAR(255) NOT NULL DEFAULT ''",
        'is_reply_content'=>'TEXT NOT NULL', 'is_reply_name'=>"VARCHAR(255) NOT NULL DEFAULT ''");
    $reply_add = array();
    foreach ($reply_columns as $column=>$definition) {
        if (!rb_core_table_has_column($g5['g5_shop_item_use_table'], $column)) {
            $reply_add[] = 'ADD COLUMN `'.$column.'` '.$definition;
        }
    }
    if ($reply_add && !sql_query('ALTER TABLE `'.$g5['g5_shop_item_use_table'].'` '.implode(', ', $reply_add), false)) {
        alert('사용후기 답변 저장에 필요한 DB 구조를 추가하지 못했습니다. DB 권한을 확인해 주세요.');
    }
    $sql = "update {$g5['g5_shop_item_use_table']}
               set is_subject = '".$posts['is_subject']."',
                   is_content = '".$posts['is_content']."',
                   is_confirm = '".$posts['is_confirm']."',
                   is_reply_subject = '".$posts['is_reply_subject']."',
                   is_reply_content = '".$posts['is_reply_content']."',
                   is_reply_name = '".$member['mb_nick']."'
             where is_id = '".$posts['is_id']."'";
    sql_query($sql);
    run_event('shop_admin_item_use_updated', $posts['is_id']);

    if( isset($_POST['it_id']) ) {
        update_use_cnt($_POST['it_id']);
        update_use_avg($_POST['it_id']);
    }

    /* 20250612 리빌더 { */
    if ($posts['is_confirm'] == 1) {

        if($_POST['mb_id']) {

            $od_al = "[".$_POST['it_name']."] 상품 구매후기에 답변이 등록 되었습니다.";
            memo_auto_send($od_al, shop_item_url($_POST['it_id']), $_POST['mb_id'], "system-msg");

        }

    }
    /* } */

    goto_url("./itemuseform.php?w=$w&amp;is_id=$is_id&amp;sca=$sca&amp;$qstr");
}
else
{
    alert();
}
