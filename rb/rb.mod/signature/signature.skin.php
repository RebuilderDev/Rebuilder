<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
if(isset($sb['sb_use']) && $sb['sb_use'] == 1) {
    $sb_is = sb_is($view['mb_id']);
}
?>


<?php if($view['mb_id']) { ?>
    <div class="writer_prof">
        <ul class="writer_prof_ul1">
            <li class="writer_prof_li_prof">
                <dd class="writer_prof_li_prof_img"><?php echo get_member_profile_img($view['mb_id']) ?></dd>
                <dd class="writer_prof_li_prof_txt">
                <span class="prof_nick"><?php echo $view['name'] ?></span>
                @<?php echo $view['mb_id'] ?>　<?php if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { // 구독 사용시 ?><?php echo sb_cnt($view['mb_id']) ?><?php } ?>
                </dd>
                <div class="cb"></div>
            </li>
            <?php if ($is_signature && $signature) { ?>
            <li class="writer_prof_li_txt">
                <?php echo $signature ?>
            </li>
            <?php } ?>

        </ul>
        <ul class="writer_prof_ul2">

            <?php if($is_member) { ?>
            <a class="fl_btns" href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $view['mb_id'] ?>">
            <?php } else { ?>
            <a class="fl_btns" href="javascript:alert('로그인 후 이용해주세요.');">
            <?php } ?>
               <img src="<?php echo $board_skin_url ?>/img/ico_home.svg">
               <span class="tooltips">미니홈</span>
            </a>


            <a class="fl_btns" href="<?php echo G5_BBS_URL ?>/memo_form.php?me_recv_mb_id=<?php echo $view['mb_id'] ?>" onclick="win_memo(this.href); return false;">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="vertical-align:middle;" aria-hidden="true"><g id="mail_line" fill="none"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z"/><path fill="#09244BFF" d="M20 4a2 2 0 0 1 1.995 1.85L22 6v12a2 2 0 0 1-1.85 1.995L20 20H4a2 2 0 0 1-1.995-1.85L2 18V6a2 2 0 0 1 1.85-1.995L4 4zm0 3.414-6.94 6.94a1.5 1.5 0 0 1-2.12 0L4 7.414V18h16zM18.586 6H5.414L12 12.586z"/></g></svg>
               <span class="tooltips">쪽지</span>
            </a>

            <?php
                if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { // 구독 사용시
                    $sb_mb_id = $view['mb_id'];
                    include_once(G5_PATH.'/rb/rb.mod/subscribe/subscribe.skin.php');
                }
            ?>

        </ul>
        <div class="cb"></div>
    </div>
<?php } ?>
