<?php
$sub_menu = '000000';
include_once('./_common.php');
include_once('./rb_license.lib.php');

auth_check_menu($auth, $sub_menu, "w");
$rb_admin_style_version = @filemtime(__DIR__.'/css/style.css');
add_stylesheet('<link rel="stylesheet" href="./css/style.css?ver='.(int) $rb_admin_style_version.'">', 0);

$g5['title'] = '빌더설정';
include_once (G5_ADMIN_PATH.'/admin.head.php');

// 설치여부 (테이블조회)
$rbx = sql_fetch(" select COUNT(*) AS cnt FROM information_schema.TABLES WHERE `TABLE_NAME` = 'rb_builder' AND TABLE_SCHEMA = '".G5_MYSQL_DB."' ");
$is_rb = $rbx['cnt'];
$rb_license_client = rb_license_client_get();
$rb_license_is_clone = !empty($rb_license_client['registered_at'])
    && isset($rb_license_client['registration_status'])
    && $rb_license_client['registration_status'] === 'clone_pending';
$rb_license_environment_labels = array(
    'local' => '로컬환경',
    'temporary' => '임시도메인',
    'production' => '운영도메인',
    'unknown' => '확인 전',
);
$rb_license_state_labels = array(
    'active' => '적용',
    'not_required' => '테스트 사용',
    'required' => '라이선스 필요',
    'pending' => '확인 중',
    'suspended' => '중지',
    'released' => '해제',
);
?>

<?php if($rbx['cnt'] > 0) { ?>

<?php
$sql = " select * from rb_builder limit 1";
$bu = sql_fetch($sql);

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_rb0">빌더정보</a></li>
    <li><a href="#anc_rb1">로고설정</a></li>
    <li><a href="#anc_rb2">회사정보</a></li>
    <li><a href="#anc_rb3">로딩인디케이터</a></li>
    <li><a href="#anc_rb6">시스템 알림</a></li>
    <li><a href="#anc_rb5">모바일설정</a></li>
    <li><a href="#anc_rb7">미니홈설정</a></li>
    <li><a href="#anc_rb4">운영채널</a></li>
</ul>';
?>


<section id="anc_rb0">
        <h2 class="h2_frm">빌더정보</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row">빌더버전</th>
                        <td colspan="3">
                            <?php echo RB_VER ?>
                        </td>

                    </tr>
                    <tr>
                        <th scope="row">설치 인증</th>
                        <td colspan="3">
                            <?php if (empty($rb_license_client['registered_at']) || $rb_license_is_clone) { ?>
                                <?php if ($rb_license_is_clone) { ?>
                                    <?php echo help('복제된 설치환경입니다.<br>복제본에서 사용할 용도의 새 설치 토큰을 등록해 주세요.') ?>
                                <?php } else { ?>
                                    <?php echo help('빌더 2.2.7 최초 설치 또는 업데이트에 설치 토큰이 필요합니다.<br>발급받은 토큰을 입력한 뒤 등록해 주세요.') ?>
                                <?php } ?>
                                <form action="./rb_license_register.php" method="post" class="rb-license-token-form">
                                    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                    <input type="text" name="install_token" value="" class="frm_input" maxlength="80" autocomplete="off" placeholder="설치 토큰 입력" required>
                                    <button type="submit" class="btn_submit btn">설치 토큰 등록</button>
                                </form>
                            <?php } else { ?>
                                <strong>설치 인증 완료</strong>
                                <span style="margin-left:10px;">
                                    환경: <?php echo isset($rb_license_environment_labels[$rb_license_client['environment_type']]) ? $rb_license_environment_labels[$rb_license_client['environment_type']] : '확인 전'; ?> /
                                    라이선스: <?php echo isset($rb_license_state_labels[$rb_license_client['license_state']]) ? $rb_license_state_labels[$rb_license_client['license_state']] : '확인 중'; ?>
                                </span>
                                <?php if (!empty($rb_license_client['status_notice'])) { ?>
                                    <div class="local_desc01 local_desc" style="margin-top:10px;">
                                        <?php echo htmlspecialchars($rb_license_client['status_notice'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">DB업데이트</th>
                        <td colspan="3">
                            <?php echo help('설치 인증을 확인한 뒤 필요한 DB 구조를 받아 자동으로 설치·업데이트합니다.') ?>
                            <a href="./rb_db_update.php" class="btn_frmline">DB 설치 및 업데이트</a>
                        </td>

                    </tr>

                </tbody>
            </table>
    </div>
</section>

<form name="bu_form" id="bu_form" action="./rb_form_update.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="token" value="" id="token">
    <input type="hidden" name="install" value="1" id="install">

    <section id="anc_rb1">
        <h2 class="h2_frm">로고설정</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row">로고 PC</th>
                        <td>
                            <?php echo help('PC 버전 로고이미지를 등록하세요.') ?>
                            <input type="file" name="bu_logo_pc">
                            <?php
                            $lp_str = "";
                            $lpimg = G5_DATA_PATH."/logos/pc";
                            if (file_exists($lpimg)) {
                                $size = @getimagesize($lpimg);
                                if($size[0] && $size[0] > 400)
                                    $width = 400;
                                else
                                    $width = $size[0];

                                echo '<input type="checkbox" name="bu_logo_pc_del" value="1" id="bu_logo_pc_del"> <label for="bu_logo_pc_del">삭제</label>';
                                $lpimg_str = '<img src="'.G5_DATA_URL.'/logos/pc?ver='.G5_SERVER_TIME.'" width="'.$width.'">';
                            }
                            if (isset($lpimg_str) && $lpimg_str) {
                                echo '<br><span style="margin-top:20px; background-color:#f1f1f1; padding:10px 20px 10px 20px; display:inline-block; box-sizing:border-box;">';
                                echo $lpimg_str;
                                echo '</span>';
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">로고 PC (화이트)</th>
                        <td>
                            <?php echo help('PC 버전 로고이미지(화이트)를 등록하세요.<br>어두운 백그라운드가 사용될 때 변경 됩니다.') ?>
                            <input type="file" name="bu_logo_pc_w">
                            <?php
                            $lpw_str = "";
                            $lpwimg = G5_DATA_PATH."/logos/pc_w";
                            if (file_exists($lpwimg)) {
                                $size = @getimagesize($lpwimg);
                                if($size[0] && $size[0] > 400)
                                    $width = 400;
                                else
                                    $width = $size[0];

                                echo '<input type="checkbox" name="bu_logo_pc_w_del" value="1" id="bu_logo_pc_w_del"> <label for="bu_logo_pc_w_del">삭제</label>';
                                $lpwimg_str = '<img src="'.G5_DATA_URL.'/logos/pc_w?ver='.G5_SERVER_TIME.'" width="'.$width.'">';
                            }
                            if (isset($lpwimg_str) && $lpwimg_str) {
                                echo '<br><span style="margin-top:20px; background-color:#f1f1f1; padding:10px 20px 10px 20px; display:inline-block; box-sizing:border-box;">';
                                echo $lpwimg_str;
                                echo '</span>';
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">로고 Mobile</th>
                        <td>
                            <?php echo help('Mobile 버전 로고이미지를 등록하세요.') ?>
                            <input type="file" name="bu_logo_mo">
                            <?php
                            $lm_str = "";
                            $lmimg = G5_DATA_PATH."/logos/mo";
                            if (file_exists($lmimg)) {
                                $size = @getimagesize($lmimg);
                                if($size[0] && $size[0] > 400)
                                    $width = 400;
                                else
                                    $width = $size[0];

                                echo '<input type="checkbox" name="bu_logo_mo_del" value="1" id="bu_logo_mo_del"> <label for="bu_logo_mo_del">삭제</label>';
                                $lmimg_str = '<img src="'.G5_DATA_URL.'/logos/mo?ver='.G5_SERVER_TIME.'" width="'.$width.'">';
                            }
                            if (isset($lmimg_str) && $lmimg_str) {
                                echo '<br><span style="margin-top:20px; background-color:#f1f1f1; padding:10px 20px 10px 20px; display:inline-block; box-sizing:border-box;">';
                                echo $lmimg_str;
                                echo '</span>';
                            }
                            ?>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row">로고 Mobile (화이트)</th>
                        <td>
                            <?php echo help('Mobile 버전 로고이미지(화이트)를 등록하세요.<br>어두운 백그라운드가 사용될 때 변경 됩니다.') ?>
                            <input type="file" name="bu_logo_mo_w">
                            <?php
                            $lmw_str = "";
                            $lmwimg = G5_DATA_PATH."/logos/mo_w";
                            if (file_exists($lmwimg)) {
                                $size = @getimagesize($lmwimg);
                                if($size[0] && $size[0] > 400)
                                    $width = 400;
                                else
                                    $width = $size[0];

                                echo '<input type="checkbox" name="bu_logo_mo_w_del" value="1" id="bu_logo_mo_w_del"> <label for="bu_logo_mo_w_del">삭제</label>';
                                $lmwimg_str = '<img src="'.G5_DATA_URL.'/logos/mo_w?ver='.G5_SERVER_TIME.'" width="'.$width.'">';
                            }
                            if (isset($lmwimg_str) && $lmwimg_str) {
                                echo '<br><span style="margin-top:20px; background-color:#f1f1f1; padding:10px 20px 10px 20px; display:inline-block; box-sizing:border-box;">';
                                echo $lmwimg_str;
                                echo '</span>';
                            }
                            ?>
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
    </section>



    <section id="anc_rb2">
        <h2 class="h2_frm">하단 회사정보</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">회사명(사이트명)</th>
                        <td>
                            <input type="text" name="bu_1" value="<?php echo isset($bu['bu_1']) ? get_sanitize_input($bu['bu_1']) : ''; ?>" id="bu_1" class="frm_input" size="40">
                        </td>
                        <th scope="row">대표자명</th>
                        <td>
                            <input type="text" name="bu_2" value="<?php echo isset($bu['bu_2']) ? get_sanitize_input($bu['bu_2']) : ''; ?>" id="bu_2" class="frm_input" size="40">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">전화번호</th>
                        <td>
                            <input type="text" name="bu_3" value="<?php echo isset($bu['bu_3']) ? get_sanitize_input($bu['bu_3']) : ''; ?>" id="bu_3" class="frm_input" size="40">
                        </td>
                        <th scope="row">팩스번호</th>
                        <td>
                            <input type="text" name="bu_4" value="<?php echo isset($bu['bu_4']) ? get_sanitize_input($bu['bu_4']) : ''; ?>" id="bu_4" class="frm_input" size="40">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">사업자등록번호</th>
                        <td>
                            <input type="text" name="bu_5" value="<?php echo isset($bu['bu_5']) ? get_sanitize_input($bu['bu_5']) : ''; ?>" id="bu_5" class="frm_input" size="40">
                        </td>
                        <th scope="row">통신판매업신고번호</th>
                        <td>
                            <input type="text" name="bu_6" value="<?php echo isset($bu['bu_6']) ? get_sanitize_input($bu['bu_6']) : ''; ?>" id="bu_6" class="frm_input" size="40">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">부가통신사업자번호</th>
                        <td>
                            <input type="text" name="bu_7" value="<?php echo isset($bu['bu_7']) ? get_sanitize_input($bu['bu_7']) : ''; ?>" id="bu_7" class="frm_input" size="40">
                        </td>
                        <th scope="row">기타등록번호</th>
                        <td>
                            <input type="text" name="bu_8" value="<?php echo isset($bu['bu_8']) ? get_sanitize_input($bu['bu_8']) : ''; ?>" id="bu_8" class="frm_input" size="40">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">우편번호</th>
                        <td>
                            <input type="text" name="bu_9" value="<?php echo isset($bu['bu_9']) ? get_sanitize_input($bu['bu_9']) : ''; ?>" id="bu_9" class="frm_input" size="40">
                        </td>
                        <th scope="row">사업장주소</th>
                        <td>
                            <input type="text" name="bu_10" value="<?php echo isset($bu['bu_10']) ? get_sanitize_input($bu['bu_10']) : ''; ?>" id="bu_10" class="frm_input" size="40">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">개인정보책임자(이메일)</th>
                        <td>
                            <input type="text" name="bu_11" value="<?php echo isset($bu['bu_11']) ? get_sanitize_input($bu['bu_11']) : ''; ?>" id="bu_11" class="frm_input" size="40">
                        </td>
                        <th scope="row">카피라이트</th>
                        <td>
                            <input type="text" name="bu_12" value="<?php echo isset($bu['bu_12']) ? get_sanitize_input($bu['bu_12']) : ''; ?>" id="bu_12" class="frm_input" size="40">
                        </td>
                    </tr>



                </tbody>
            </table>
        </div>
    </section>


    <section id="anc_rb3">
        <h2 class="h2_frm">로딩인디케이터</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">사용여부</th>
                        <td colspan="3">
                        <?php echo help('사용시 로딩 스피너를 표기하며<br>DOM을 포함한 모든 페이지가 준비 되면 사라집니다.') ?>
                        <input type="radio" name="bu_load" value="" id="bu_load0" <?php echo empty($bu['bu_load']) || $bu['bu_load'] == '' ? 'checked' : ''; ?>> <label for="bu_load0">사용안함</label>
                        <input type="radio" name="bu_load" value="1" id="bu_load1" <?php echo isset($bu['bu_load']) && $bu['bu_load'] == 1 ? 'checked' : ''; ?>> <label for="bu_load1">전체 사용</label>
                        <input type="radio" name="bu_load" value="2" id="bu_load2" <?php echo isset($bu['bu_load']) && $bu['bu_load'] == 2 ? 'checked' : ''; ?>> <label for="bu_load2">메인만 사용</label>
                        <div style="margin-top:10px;">
                            <input type="checkbox" name="bu_module_spinner_use" value="1" id="bu_module_spinner_use" <?php echo !empty($bu['bu_module_spinner_use']) ? 'checked' : ''; ?>>
                            <label for="bu_module_spinner_use">모듈 로딩스피너 사용</label>
                        </div>
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
    </section>


    <section id="anc_rb6">
        <h2 class="h2_frm">시스템 알림</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">수신여부</th>
                        <td colspan="3">
                        <?php echo help('관리자의 시스템 알림 수신 여부를 설정할 수 있습니다.<br>신규 게시물 등록, 주문 접수, 회원가입 등 웹사이트에서 일어나는 주요 활동 알림입니다.') ?>
                        <input type="checkbox" name="bu_systemmsg_use" value="1" id="bu_systemmsg_use" <?php echo isset($bu['bu_systemmsg_use']) && $bu['bu_systemmsg_use'] ? 'checked' : ''; ?>> <label for="bu_systemmsg_use">수신함</label>
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
    </section>


    <section id="anc_rb5">
        <h2 class="h2_frm">모바일설정</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">Viewport</th>
                        <td colspan="3">
                        <?php echo help('빌더의 기본 뷰포트 값은 0.9 입니다. 값이 없으면 0.9 로 적용되며,<br>/theme/테마폴더/head.sub.php 파일의 meta name="viewport" 값이 변경 됩니다.<br>숫자가 작을수록 오브젝트의 크기가 축소되며, 1이 정비율 입니다.<br>커스텀 테마를 사용하시는 경우 적용이 되지않을 수 있습니다.') ?>
                        <input type="text" name="bu_viewport" value="<?php echo isset($bu['bu_viewport']) ? get_sanitize_input($bu['bu_viewport']) : ''; ?>" id="bu_viewport" class="frm_input" size="10">
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </section>

    <section id="anc_rb7">
        <h2 class="h2_frm">미니홈설정</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">미니홈 메인설정</th>
                        <td colspan="3">
                        <?php echo help('미니홈 메인에 표기할 컨텐츠를 지정할 수 있습니다.<br>포인트 현황은 획득한 포인트만 기준으로 하며 관리자의 경우 제외 됩니다.') ?>
                        <input type="checkbox" name="bu_mini_use1" value="1" id="bu_mini_use1" <?php echo isset($bu['bu_mini_use1']) && $bu['bu_mini_use1'] ? 'checked' : ''; ?>> <label for="bu_mini_use1">게시물/댓글 현황</label>
                        <input type="checkbox" name="bu_mini_use2" value="1" id="bu_mini_use2" <?php echo isset($bu['bu_mini_use2']) && $bu['bu_mini_use2'] ? 'checked' : ''; ?>> <label for="bu_mini_use2">포인트 획득 현황</label>
                        <input type="checkbox" name="bu_mini_use3" value="1" id="bu_mini_use3" <?php echo isset($bu['bu_mini_use3']) && $bu['bu_mini_use3'] ? 'checked' : ''; ?>> <label for="bu_mini_use3">사용자 최신글</label>
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
    </section>



    <section id="anc_rb4">
        <h2 class="h2_frm">운영채널</h2>
        <?php echo $pg_anchor ?>

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col class="grid_4">
                    <col>
                    <col class="grid_4">
                    <col>
                </colgroup>
                <tbody>

                    <tr>
                        <th scope="row">카카오채널 URL</th>
                        <td>
                            <input type="text" name="bu_sns1" value="<?php echo isset($bu['bu_sns1']) ? get_sanitize_input($bu['bu_sns1']) : ''; ?>" id="bu_sns1" class="frm_input" size="70">
                        </td>
                        <th scope="row">카카오채널 상담 URL</th>
                        <td>
                            <input type="text" name="bu_sns2" value="<?php echo isset($bu['bu_sns2']) ? get_sanitize_input($bu['bu_sns2']) : ''; ?>" id="bu_sns2" class="frm_input" size="70">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">유튜브 URL</th>
                        <td>
                            <input type="text" name="bu_sns3" value="<?php echo isset($bu['bu_sns3']) ? get_sanitize_input($bu['bu_sns3']) : ''; ?>" id="bu_sns3" class="frm_input" size="70">
                        </td>
                        <th scope="row">인스타그램 URL</th>
                        <td>
                            <input type="text" name="bu_sns4" value="<?php echo isset($bu['bu_sns4']) ? get_sanitize_input($bu['bu_sns4']) : ''; ?>" id="bu_sns4" class="frm_input" size="70">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">페이스북 URL</th>
                        <td>
                            <input type="text" name="bu_sns5" value="<?php echo isset($bu['bu_sns5']) ? get_sanitize_input($bu['bu_sns5']) : ''; ?>" id="bu_sns5" class="frm_input" size="70">
                        </td>
                        <th scope="row">트위터 URL</th>
                        <td>
                            <input type="text" name="bu_sns6" value="<?php echo isset($bu['bu_sns6']) ? get_sanitize_input($bu['bu_sns6']) : ''; ?>" id="bu_sns6" class="frm_input" size="70">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">네이버블로그 URL</th>
                        <td>
                            <input type="text" name="bu_sns7" value="<?php echo isset($bu['bu_sns7']) ? get_sanitize_input($bu['bu_sns7']) : ''; ?>" id="bu_sns7" class="frm_input" size="70">
                        </td>
                        <th scope="row">텔레그램 URL</th>
                        <td>
                            <input type="text" name="bu_sns8" value="<?php echo isset($bu['bu_sns8']) ? get_sanitize_input($bu['bu_sns8']) : ''; ?>" id="bu_sns8" class="frm_input" size="70">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">SIR URL</th>
                        <td>
                            <input type="text" name="bu_sns9" value="<?php echo isset($bu['bu_sns9']) ? get_sanitize_input($bu['bu_sns9']) : ''; ?>" id="bu_sns9" class="frm_input" size="70">
                        </td>
                        <th scope="row">기타 URL</th>
                        <td>
                            <input type="text" name="bu_sns10" value="<?php echo isset($bu['bu_sns10']) ? get_sanitize_input($bu['bu_sns10']) : ''; ?>" id="bu_sns10" class="frm_input" size="70">
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </section>

    <div class="btn_fixed_top">
        <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
    </div>

</form>

<?php } else { ?>
<section>
    <h2 class="h2_frm">그누보드 리빌더</h2>
    <div class="local_desc01 local_desc">

        그누보드 리빌더를 사용해주셔서 고맙습니다.<br><br>
        리빌더는 그누보드의 기능을 모두 그대로 사용하면서 폴더의 추가만으로<br>
        손쉽게 웹사이트를 완성하고 다양한 편의기능을 사용할 수 있습니다.<br><br>

        <b>본 페이지는 테이블이 설치되면 더이상 볼 수 없습니다.</b>
    </div>
</section>

<section>

    <h2 class="h2_frm">빌더 설치안내 및 주의사항</h2>

    <div class="local_desc01 local_desc">

        빌더 구동에 필요한 테이블이 설치 됩니다.<br><br>
        rb_ 로 시작하는 동일한 테이블명이 있는경우 테이블 생성이 되지 않을 수 있으며<br>
        성능 보장을 위해 가급적 PHP7.X ~ PHP8.X 버전을 사용해주세요.<br>
        <strong>설치 토큰 등록 후 [DB 설치 및 업데이트]를 실행해 주세요.</strong><br><br>

        테이블 설치 후 <b>환경설정 > 테마설정</b> 메뉴에서<br>
        <b>rb.Basic 테마를 적용</b> 해주시고<br>
        테마적용 직후 뜨는 팝업창에서 <b>[확인]</b> 을 클릭합니다.<br><br>

        [확인] 을 클릭하지 못하였다면 <b>환경설정 > 기본환경설정</b> 메뉴에서<br>
        <b>[테마 스킨설정 가져오기], [테마 회원스킨설정 가져오기]</b> 를 클릭하신 후<br>
        반드시 <b>[확인]</b> 을 클릭 해주세요.<br><br>

        설치가 완료 되었다면, <b>관리자모드 > 게시판관리</b> 에서<br>
        게시판의 스킨을 <b>rb.XXX 로 변경</b> 합니다.<br><br>

        메인페이지로 이동 후 <b>[모듈추가]</b> 버튼을 통해 메인페이지에 출력될 모듈을 구성 합니다.



    </div>

</section>

<section>
    <?php if (empty($rb_license_client['registered_at'])) { ?>
    <form name="rb_form" id="rb_form" action="./rb_license_register.php" method="post">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <h2 class="h2_frm">라이선스 정책</h2>

        <div class="local_desc01 local_desc">
<textarea name="mb_signature" readonly style="height:200px;padding:20px;box-sizing:border-box">
리빌더(REBUILDER) 소프트웨어 이용 및 라이선스 정책

본 정책은 리빌더 소프트웨어와 관련 업데이트, 부가기능 및 부가서비스의 설치·복제·사용 조건을 정합니다. 소프트웨어를 다운로드하거나 설치·업데이트·사용하면 본 정책에 동의한 것으로 봅니다.

1. 소프트웨어의 성격
리빌더는 그누보드 기반에서 동작하는 별도의 확장 소프트웨어입니다. 그누보드의 라이선스는 별도로 적용됩니다.

2. 적용 버전과 기존 설치
빌더 2.2.7 이상을 새로 설치하거나 이전 버전에서 2.2.7 이상으로 업데이트하는 경우 설치 토큰 및 신규 라이선스 정책이 적용됩니다.
2.2.6.3 이하 버전을 그대로 사용하는 기존 설치는 종전의 무료 사용 상태를 유지합니다. 다만 2.2.7 이상으로 업데이트하면 기존 라이선스 키는 설치 인증수단으로 사용할 수 없고 새 설치 토큰 등록이 필요합니다.

3. 설치 토큰
빌더 2.2.7 이상의 최초 설치와 2.2.7로의 최초 업데이트에는 회원 계정에서 발급한 일회용 설치 토큰이 반드시 필요합니다.
설치 토큰은 회원 계정과 하나의 빌더 설치본을 연결하는 확인값이며 운영 도메인 라이선스 자체는 아닙니다.
토큰 등록이 완료된 설치본에 한하여 설치·업데이트에 필요한 DB 구조가 제공됩니다.

4. 테스트 환경과 운영 도메인
로컬 환경과 허용된 호스팅 임시도메인에서는 라이선스를 사용하지 않고 설치·테스트할 수 있습니다.
운영 도메인이 연결되면 설치 토큰에서 선택한 용도와 같은 종류의 보유 라이선스가 적용됩니다.
자사용에는 자사용 라이선스, 고객에게 제작·납품·이전하는 설치에는 납품용 라이선스가 필요합니다.

5. 라이선스 사용 단위
별도의 표시가 없는 라이선스는 1도메인 1카피를 기본 단위로 합니다.
구매 또는 관리자가 지급한 이용권 수량만큼 운영 도메인을 활성화할 수 있습니다.
이용권의 종류·수량·유효기간·판매조건은 구매 시 표시된 내용을 따릅니다.

6. 복제 및 이전 설치
설치된 빌더를 다른 서버나 별도 환경으로 복제하면 새로운 설치환경으로 확인될 수 있습니다.
복제본에서 사용할 용도의 새 설치 토큰을 등록해야 하며 운영 도메인으로 사용하는 경우 해당 용도의 사용 가능한 라이선스가 필요할 수 있습니다.

7. 부가기능 및 부가서비스
부가기능, 테마 및 부가서비스는 빌더 라이선스와 별도의 판매·이용조건이 적용될 수 있습니다.
허용된 범위와 수량을 초과해 복제·공유·재판매하거나 제3자가 내려받도록 배포할 수 없습니다.

8. 설치 확인정보의 처리
설치 인증, 복제 확인, 라이선스 적용 및 업데이트 제공을 위해 설치 식별값, 회원 식별정보, 도메인, 접속 IP, 빌더·그누보드·PHP 버전, 서버환경 구분용 해시값과 확인일시가 처리될 수 있습니다.
설치 토큰 원문과 설치 인증 비밀값 원문은 인증 서버 DB에 저장하지 않습니다.

9. 금지행위
- 설치 토큰, 설치 확인 또는 라이선스 검증을 삭제·변조·우회하는 행위
- 허용 수량을 초과하여 설치·복제·활성화하거나 인증정보를 공유하는 행위
- 리빌더 또는 구성요소를 무단 재배포·재판매·대여·리스하거나 공개 배포하는 행위
- 소스코드를 역공학·디컴파일·역어셈블하는 행위
- 저작권·라이선스 표시를 제거하거나 권리자의 권리를 침해하는 행위
- 관계 법령에 위반되는 불법·유해 사이트의 제작 또는 운영에 사용하는 행위

10. 업데이트와 서비스
공급자는 보안·기능개선·정책변경을 위해 소프트웨어와 설치·라이선스 체계를 업데이트할 수 있습니다.
인터넷 또는 인증 서버 장애, 호스팅 보안설정, CA 인증서 문제 등 외부 환경으로 설치 확인이나 업데이트가 일시적으로 지연될 수 있습니다.

11. 권리와 책임
리빌더 소프트웨어의 지적재산권은 공급자에게 귀속되며 사용자는 허용된 범위의 사용권만 취득합니다.
소프트웨어는 현재 상태로 제공되며 관련 법령이 허용하는 범위에서 특정 목적 적합성이나 무중단·무오류 동작을 보증하지 않습니다.

12. 위반과 이용 제한
정책 위반, 결제 취소·환불, 부정 사용 또는 관계 법령 위반이 확인되면 해당 이용권·활성화·기술지원 또는 업데이트 제공이 제한되거나 철회될 수 있습니다.

13. 정책 변경
정책 변경은 정책 페이지를 통해 공지하며 관계 법령에서 별도 동의가 필요한 경우 해당 절차를 따릅니다.

14. 불법사이트 규제정책
리빌더는 그누보드를 기반으로 한 웹 빌더로, 누구나 손쉽게 웹사이트를 구축할 수 있도록 제공되고 있습니다.
일부 사용자에 의해 불법 또는 사회적으로 부적절한 사이트 제작에 사용되는 사례가 확인되고 있어 이에 대한 정책을 아래와 같이 안내합니다.

[사용 제한 대상 사이트 유형 예시]
- 불법 도박 사이트
- 불법 사행성 콘텐츠 운영 사이트
- 피싱 및 사기성 사이트
- 허가되지 않은 음란물 및 성인 콘텐츠 제공 사이트(불법 촬영물 포함)
- 불법 저작물 공유 및 다운로드 사이트
- 의약품·의료기기 불법 판매 사이트
- 마약, 대포폰, 불법 총기 등 관련 거래 사이트
- 기타 관계 법령에 위반되는 사이트

위와 같은 사이트에 리빌더를 사용하는 경우 라이선스 해지 및 사용 제한 조치가 즉시 이루어질 수 있으며, 리빌더를 통해 제작된 사이트의 법적 책임과 운영에 대한 모든 책임은 사용자 본인에게 있습니다.

최종 수정일: 2026년 8월 18일
적용 버전: 빌더 2.2.7 이상
</textarea>

<br><br>
            <input type="checkbox" value="1" id="agrees">
            <label for="agrees">상기 내용을 모두 확인하였으며, 라이선스 정책에 동의 합니다.</label>
        </div>

        <h2 class="h2_frm">설치 토큰</h2>

        <div class="rb-license-token-form">
            <input type="text" name="install_token" id="install_token" value="" class="frm_input" maxlength="80" autocomplete="off" placeholder="설치 토큰 입력" required style="width:100%;max-width:640px;">
        </div>

        <div class="btn_confirm01 btn_confirm rb-license-token-actions">
            <input type="submit" value="설치 토큰 등록" class="btn_submit btn">
        </div>
    </form>
    <?php } else { ?>
    <h2 class="h2_frm">설치 인증 완료</h2>
    <div class="local_desc01 local_desc">
        설치 토큰 등록이 완료되었습니다. 아래 버튼을 눌러 빌더 DB를 설치해 주세요.
    </div>
    <div class="btn_confirm01 btn_confirm">
        <a href="./rb_db_update.php" class="btn_submit btn">DB 설치 및 업데이트</a>
    </div>
    <?php } ?>
</section>

<?php if (empty($rb_license_client['registered_at'])) { ?>
<script>
        $(document).ready(function() {
            $("#rb_form").on("submit", function(event) {
                if (confirm("상기 주의사항 및 라이선스 정책을 확인해주세요.\n설치 토큰을 등록하시겠습니까?")) {
                    if (!$("#agrees").is(":checked")) {
                        alert("라이선스 정책에 동의 하셔야 빌더를 사용할 수 있습니다.");
                        event.preventDefault();
                    }
                } else {
                    event.preventDefault();
                }
            });
        });
</script>
<?php } ?>
<?php } ?>


<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
