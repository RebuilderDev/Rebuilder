<?php if (!defined('_GNUBOARD_')) exit; ?>

<div class="rb_profile_sidebar">

    <div class="rb_prof rb_prof_new">

        <?php
        $todays = date('Y-m-d');
        $is_special    = ($mb['mb_id'] == 'master' || $mb['mb_id'] == 'false9');
        $rb_chat_available = false;
        if ($is_member
            && function_exists('user_online')
            && isset($chat_set) && is_array($chat_set)
            && isset($chat_set['ch_use']) && (int) $chat_set['ch_use'] === 1
            && is_file(G5_PATH.'/rb/chat_form.php')) {
            $rb_chat_level = isset($chat_set['ch_level']) ? (int) $chat_set['ch_level'] : 0;
            $rb_chat_available = (int) $member['mb_level'] >= $rb_chat_level
                && (int) $mb['mb_level'] >= $rb_chat_level;
        }
        ?>

        <div class="rb_sb_prof_wrap">

            <div class="rb_sb_prof_wrap_inner">

                <div class="rb_sb_prof_wrap_image">

                    <!-- 프로필 이미지 -->
                    <div class="rb_sb_img_wrap">
                        <span id="prof_image_ch"><?php echo get_member_profile_img($mb['mb_id']); ?></span>

                        <?php if ($mb['mb_id'] == $member['mb_id']) { ?>
                        <button type="button" id="prof_ch_btn" class="rb_sb_img_edit" title="프로필 사진 변경">
                            <svg width="14" height="14" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.58597 1.1C9.93996 0.746476 10.4136 0.538456 10.9134 0.516981C11.4132 0.495507 11.903 0.662139 12.286 0.984002L12.414 1.101L14.314 3H17C17.5044 3.00009 17.9901 3.19077 18.3599 3.53384C18.7297 3.8769 18.9561 4.34702 18.994 4.85L19 5V7.686L20.9 9.586C21.2538 9.94004 21.462 10.4139 21.4834 10.9139C21.5049 11.414 21.3381 11.9039 21.016 12.287L20.899 12.414L18.999 14.314V17C18.9991 17.5046 18.8086 17.9906 18.4655 18.3605C18.1224 18.7305 17.6521 18.9572 17.149 18.995L17 19H14.315L12.415 20.9C12.0609 21.2538 11.5871 21.462 11.087 21.4835C10.587 21.505 10.097 21.3382 9.71397 21.016L9.58697 20.9L7.68697 19H4.99997C4.49539 19.0002 4.0094 18.8096 3.63942 18.4665C3.26944 18.1234 3.04281 17.6532 3.00497 17.15L2.99997 17V14.314L1.09997 12.414C0.746165 12.06 0.537968 11.5861 0.516492 11.0861C0.495016 10.586 0.661821 10.0961 0.98397 9.713L1.09997 9.586L2.99997 7.686V5C3.00006 4.4956 3.19074 4.00986 3.53381 3.64009C3.87687 3.27032 4.34699 3.04383 4.84997 3.006L4.99997 3H7.68597L9.58597 1.1ZM11 8C10.2043 8 9.44126 8.31607 8.87865 8.87868C8.31604 9.44129 7.99997 10.2044 7.99997 11C7.99997 11.7957 8.31604 12.5587 8.87865 13.1213C9.44126 13.6839 10.2043 14 11 14C11.7956 14 12.5587 13.6839 13.1213 13.1213C13.6839 12.5587 14 11.7957 14 11C14 10.2044 13.6839 9.44129 13.1213 8.87868C12.5587 8.31607 11.7956 8 11 8Z" fill="#fff" />
                            </svg>
                        </button>
                        <input type="file" id="prof_image_ch_input" accept="image/*" style="display:none;">
                        <script>
                        $(document).ready(function() {
                            $('#prof_ch_btn').on('click', function() { $('#prof_image_ch_input').click(); });
                            $('#prof_image_ch_input').on('change', function(e) {
                                var file = e.target.files[0];
                                if (!file) return;
                                var formData = new FormData();
                                formData.append('profile_image', file);
                                $.ajax({
                                    url: '<?php echo G5_URL; ?>/rb/rb.lib/ajax.upload_prof_image.php',
                                    type: 'POST', data: formData, contentType: false, processData: false,
                                    success: function(res) {
                                        var d = JSON.parse(res);
                                        if (d.success) { $('#prof_image_ch').html('<img src="'+d.image_url+'" alt="profile_image">'); location.reload(); }
                                        else alert(d.message);
                                    }
                                });
                            });
                        });
                        </script>
                        <?php } ?>
                    </div>

                </div>

                <div class="rb_mo_wrap_left">

                    <!-- 닉네임 + 레벨 -->
                    <div class="rb_sb_nick_wrap">
                        <strong class="rb_sb_nick font-B"><?php echo $mb['mb_nick']; ?></strong>
                        <span class="rb_sb_level"><?php echo $mb['mb_level']; ?> Lv</span>
                    </div>

                </div>

            </div>

            <!-- 자기소개 -->
            <?php
            if (!empty($mb['mb_profile'])) {
            ?>
                <div class="rb_sb_bio"><?php echo nl2br($mb['mb_profile']); ?></div>
            <?php
            } elseif (!empty($mb['mb_signature'])) {
            ?>
                <div class="rb_sb_bio"><?php echo nl2br($mb['mb_signature']); ?></div>
            <?php
            }
            ?>


            <?php if ($mb['mb_id'] != $member['mb_id']) { ?>
                <div class="commu_btn">

                    <a href="<?php echo G5_BBS_URL; ?>/memo_form.php?me_recv_mb_id=<?php echo $mb['mb_id']; ?>" class="editor_bbs_btn" onclick="win_memo(this.href); return false;" title="쪽지발송">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><g id="mail_line" fill="none"><path d="M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z"/><path fill="#09244BFF" d="M20 4a2 2 0 0 1 1.995 1.85L22 6v12a2 2 0 0 1-1.85 1.995L20 20H4a2 2 0 0 1-1.995-1.85L2 18V6a2 2 0 0 1 1.85-1.995L4 4zm0 3.414-6.94 6.94a1.5 1.5 0 0 1-2.12 0L4 7.414V18h16zM18.586 6H5.414L12 12.586z"/></g></svg>
                    <span>쪽지쓰기</span>
                    </a>

                    <?php if ($rb_chat_available) { ?>
                    <a href="<?php echo G5_URL; ?>/rb/chat_form.php?me_recv_mb_id=<?php echo $mb['mb_id']; ?>" class="editor_bbs_btn" onclick="win_chat(this.href); return false;" title="대화하기">
                    <img src="<?php echo G5_THEME_URL; ?>/rb.img/icon/ico_chat.svg">
                    <span>대화하기</span>
                    </a>
                    <?php } ?>

                </div>
            <?php } ?>


            <!-- 통계 -->
            <div class="rb_sb_stats">
                <span>게시물<strong><?php echo number_format(wr_cnt($mb['mb_id'], 'w')); ?></strong></span>
                <span>댓글<strong><?php echo number_format(wr_cnt($mb['mb_id'], 'c')); ?></strong></span>
            </div>



        </div>
        <!-- /rb_sb_prof_wrap -->



    </div>
    <!-- /rb_prof -->

    <!-- 사이드 네비 -->
    <nav class="rb_sidebar_nav">
        <?php $base = G5_URL . '/rb/home.php?mb_id=' . $mb['mb_id']; ?>
        <a href="<?php echo $base; ?>"<?php if ($ca == '') echo ' class="on"'; ?>>홈</a>
        <a href="<?php echo $base; ?>&ca=bbs"<?php if ($ca == 'bbs') echo ' class="on"'; ?>>새글</a>
        <a href="<?php echo $base; ?>&ca=comment"<?php if ($ca == 'comment') echo ' class="on"'; ?>>새댓글</a>

        <?php if (isset($sb['sb_use']) && $sb['sb_use'] == 1 && ($mb['mb_id'] == $member['mb_id'] || $is_admin)) { ?>
        <a href="<?php echo $base; ?>&ca=fw"<?php if ($ca == 'fw') echo ' class="on"'; ?>>구독자</a>
        <a href="<?php echo $base; ?>&ca=fn"<?php if ($ca == 'fn') echo ' class="on"'; ?>>내 구독</a>
        <?php } ?>
    </nav>

</div>
<!-- /rb_profile_sidebar -->

<div class="rb_profile_content">

        <div class="rb_prof_btn">
            <div id="bo_v_share">
                <ul class="copy_urls">
                    <li>
                        <a class="fl_btns" id="data-copy" title="공유링크 복사" href="javascript:void(0);">
                            <img src="<?php echo G5_THEME_URL; ?>/rb.img/icon/ico_link.svg">
                        </a>
                        <input type="hidden" id="data-area" value="<?php echo G5_URL; ?>/rb/home.php?mb_id=<?php echo $mb['mb_id']; ?>">
                        <script>
                        $(document).ready(function() {
                            $('#data-copy').click(function() {
                                $('#data-area').attr('type', 'text').select();
                                document.execCommand('copy');
                                $('#data-area').attr('type', 'hidden');
                                alert('미니홈 링크가 복사 되었습니다.');
                            });
                        });
                        </script>

                        <?php if ($mb['mb_id'] == $member['mb_id']) { ?>
                            <a class="fl_btns fl_btns_txt" href="<?php echo G5_BBS_URL; ?>/member_confirm.php?url=<?php echo G5_BBS_URL; ?>/register_form.php">정보수정</a>
                        <?php } else { ?>
                            <?php if (isset($sb['sb_use']) && $sb['sb_use'] == 1) {
                                $sb_mb_id = $mb['mb_id'];
                                include_once(G5_PATH . '/rb/rb.mod/subscribe/subscribe_my.skin.php');
                            } ?>
                        <?php } ?>
                    </li>
                </ul>
            </div>
        </div>

        <?php include 'home.skin.body.php'; ?>
    </div>
