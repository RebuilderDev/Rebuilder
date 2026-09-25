<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit;
if (empty($_SESSION['rb_theme_package_token'])) $_SESSION['rb_theme_package_token']=bin2hex(random_bytes(32));
$rb_tp_ftp_files=glob(G5_PATH.'/theme/*/rb-package/manifest.json');
$rb_tp_pending=array();
foreach((array)$rb_tp_ftp_files as $rb_tp_f) {
    $rb_tp_dir=dirname(dirname($rb_tp_f)); if(is_link($rb_tp_dir)) continue; $rb_tp_n=basename($rb_tp_dir);
    $rb_tp_meta=filesize($rb_tp_f)<8*1024*1024?json_decode(file_get_contents($rb_tp_f),true):array();
    if(is_file($rb_tp_dir.'/rb-package.json')) {
        $rb_tp_installed=json_decode(file_get_contents($rb_tp_dir.'/rb-package.json'),true);
        if(empty($rb_tp_meta['identity']['release']) || empty($rb_tp_meta['delivery']) || $rb_tp_meta['delivery']!=='update'
            || (isset($rb_tp_installed['identity']['release']) && $rb_tp_installed['identity']['release']===$rb_tp_meta['identity']['release'])) continue;
    }
    $rb_tp_label=isset($rb_tp_meta['name']) && is_string($rb_tp_meta['name'])?trim($rb_tp_meta['name']):'';
    $rb_tp_pending[$rb_tp_n]=$rb_tp_label!=='' && strcasecmp($rb_tp_label,$rb_tp_n)!==0?$rb_tp_label.' · '.$rb_tp_n:$rb_tp_n;
}
?>
<?php if(isset($_GET['installed']) && is_string($_GET['installed']) && in_array($_GET['installed'],$theme,true)) { ?>
<p class="local_desc01 local_desc" role="status">테마 설치가 완료되었습니다. 아래 설치된 테마 목록에서 <strong>테마적용</strong>을 눌러 주세요.</p>
<?php } ?>
<?php if ($rb_tp_pending) { ?>
<section class="local_desc01 local_desc" id="rb-theme-install" style="background:#fff; border:0; padding:0; margin-top:0; margin-bottom:20px;" data-token="<?php echo htmlspecialchars($_SESSION['rb_theme_package_token'],ENT_QUOTES,'UTF-8'); ?>" data-install-version-error="<?php echo htmlspecialchars(rb_tp_install_version_message(),ENT_QUOTES,'UTF-8'); ?>">
    <h2 style="margin-top:0;">설치할 수 있는 테마가 있습니다.</h2>
    <p style="margin-bottom:20px;"><strong>아래 설치할 테마 폴더를 선택 하신 후 설치내용 확인을 클릭하세요.</strong></p>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>설치할 테마 선택</caption>
            <colgroup><col class="grid_4"><col></colgroup>
            <tbody>
                <tr>
                    <th scope="row"><label for="rb-tp-file">업로드된 테마</label></th>
                    <td>
                        <select id="rb-tp-file"><option value="">테마 폴더 선택</option>
                        <?php foreach($rb_tp_pending as $rb_tp_n=>$rb_tp_label) { ?>
                            <option value="<?php echo htmlspecialchars($rb_tp_n,ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars($rb_tp_label,ENT_QUOTES,'UTF-8'); ?></option>
                        <?php } ?></select>
                        <button type="button" class="btn btn_02" id="rb-tp-check">설치 내용 확인</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <p id="rb-tp-status" role="status" aria-live="polite" style="margin:20px 0; line-height:1.6;" hidden></p>
    <div id="rb-tp-details" hidden>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>테마 설치 확인 내용</caption>
                <colgroup><col class="grid_4" style="width:240px;"><col></colgroup>
                <tbody>
                    <tr><th scope="row">테마명</th><td id="rb-tp-name"></td></tr>
                    <tr><th scope="row"><label for="rb-tp-theme">설치 폴더명</label></th><td><input id="rb-tp-theme" class="frm_input" readonly></td></tr>
                    <tr><th scope="row">설치 안내</th><td id="rb-tp-install-guide">현재 업로드된 폴더명을 기준으로 설치합니다. 폴더명을 바꾸셨다면 새로고침 후 다시 선택하세요. 이름 충돌은 설치 전에 자동 확인합니다.</td></tr>
                    <tr id="rb-tp-map-guide"><th scope="row">메인 모듈 연결 (선택)</th><td>아래는 일반 메인·쇼핑몰 메인 모듈에서 표시할 데이터입니다.<br>
                        각 항목에 사용하는 모듈을 표시합니다. 연결하지 않아도 설치할 수 있으며, 설치 후 모듈 설정에서 지정할 수 있습니다.<br>
                        상품 모듈은 분류 미지정 시 전체 상품을 표시합니다.<br>
                        기존 게시판·분류·설문·상품 등의 운영 설정과 게시판 스킨 설정은 변경하지 않습니다.</td></tr>
                    <tr id="rb-tp-shop-guide" hidden><th scope="row">쇼핑몰 미사용</th><td>이 사이트는 쇼핑몰을 사용하지 않아 쇼핑몰 메인·상품 모듈의 연결 항목을 표시하지 않습니다. 테마의 디자인 자료는 보관하며, 쇼핑몰 기능을 자동으로 활성화하지 않습니다.</td></tr>
                </tbody>
                <tbody id="rb-tp-maps"></tbody>
            </table>
        </div>
        <div class="btn_confirm" style="margin-top:20px;"><button type="button" class="btn btn_03" id="rb-tp-install">테마 설치</button></div>
    </div>
    <template id="rb-tp-empty-help"><?php echo help('지금 연결할 항목이 없어도 설치할 수 있습니다.'); ?></template>
    <template id="rb-tp-legacy-help"><?php echo help('이전 배포 자료입니다. 제작 사이트에서 테마를 다시 내보내면 연결 없이 설치할 수 있습니다.'); ?></template>
</section>
<script src="<?php echo G5_ADMIN_URL; ?>/theme_package.js?v=2277-module-connections"></script>
<?php } ?>
<?php unset($rb_tp_dir,$rb_tp_ftp_files,$rb_tp_f,$rb_tp_n,$rb_tp_meta,$rb_tp_label,$rb_tp_installed,$rb_tp_pending); ?>
