<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_PATH.'/rb/rb.lib/rb_theme_package.lib.php');

// 게시판 DB와 사이트 공통 스킨 설정을 덮어쓰지 않고 현재 테마에만 적용한다.
function rb_tp_apply_skins()
{
    global $config,$board,$bo_table,$theme_config;
    if (defined('G5_IS_ADMIN') && G5_IS_ADMIN) return;
    $state=rb_tp_state(isset($config['cf_theme'])?$config['cf_theme']:'');
    if (!$state) return;
    if (!empty($bo_table) && isset($state['boards'][$bo_table])) {
        foreach (array('bo_skin','bo_mobile_skin') as $key) $board[$key]=$state['boards'][$bo_table][$key];
        foreach(rb_tp_board_display_fields() as $key) if(isset($state['boards'][$bo_table][$key])) $board[$key]=$state['boards'][$bo_table][$key];
        $skin=$board[G5_IS_MOBILE?'bo_mobile_skin':'bo_skin'];
        $GLOBALS['board_skin_path']=get_skin_path('board',$skin);
        $GLOBALS['board_skin_url']=get_skin_url('board',$skin);
    }
    foreach (array('member','new','search','connect','faq') as $kind) {
        foreach (array('cf_'.$kind.'_skin','cf_mobile_'.$kind.'_skin') as $key)
            if (!empty($state['skins']['config'][$key])) $config[$key]=$state['skins']['config'][$key];
        $key='cf_'.(G5_IS_MOBILE?'mobile_':'').$kind.'_skin';
        if (!empty($config[$key])) {
            $GLOBALS[$kind.'_skin_path']=get_skin_path($kind,$config[$key]);
            $GLOBALS[$kind.'_skin_url']=get_skin_url($kind,$config[$key]);
        }
    }
}
add_event('common_header','rb_tp_apply_skins',1);

function rb_tp_qa_skins($qa)
{
    global $config;
    if (defined('G5_IS_ADMIN') && G5_IS_ADMIN) return $qa;
    $state=rb_tp_state(isset($config['cf_theme'])?$config['cf_theme']:'');
    foreach(array('qa_skin','qa_mobile_skin') as $key) if(!empty($state['skins']['qa'][$key])) $qa[$key]=$state['skins']['qa'][$key];
    return $qa;
}
add_replace('get_qa_config','rb_tp_qa_skins',1,1);
