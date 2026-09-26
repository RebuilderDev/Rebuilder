<?php
if (!defined('_GNUBOARD_') || $is_admin !== 'super') exit;
if (empty($_SESSION['rb_theme_package_token'])) $_SESSION['rb_theme_package_token']=bin2hex(random_bytes(32));
$rb_tp_ftp_files=glob(G5_PATH.'/theme/*/rb-package/manifest.json');
$rb_tp_pending=array();
foreach((array)$rb_tp_ftp_files as $rb_tp_f) {
    $rb_tp_dir=dirname(dirname($rb_tp_f)); if(is_link($rb_tp_dir)) continue; $rb_tp_n=basename($rb_tp_dir);
    // 설치된 테마는 FTP로 파일만 갱신하며, 배포 버전이 달라도 다시 설치하지 않는다.
    if(is_file($rb_tp_dir.'/rb-package.json')) continue;
    $rb_tp_meta=filesize($rb_tp_f)<8*1024*1024?json_decode(file_get_contents($rb_tp_f),true):array();
    $rb_tp_label=isset($rb_tp_meta['name']) && is_string($rb_tp_meta['name'])?trim($rb_tp_meta['name']):'';
    $rb_tp_pending[$rb_tp_n]=$rb_tp_label!=='' && strcasecmp($rb_tp_label,$rb_tp_n)!==0?$rb_tp_label.' · '.$rb_tp_n:$rb_tp_n;
}
?>
<?php if(isset($_GET['installed']) && is_string($_GET['installed']) && in_array($_GET['installed'],$theme,true) && $_GET['installed']!==$config['cf_theme']) { ?>
<p class="local_desc01 local_desc" role="status">테마 설치가 완료되었습니다. 아래 설치된 테마 목록에서 <strong>테마적용</strong>을 눌러 주세요.</p>
<?php } ?>
<?php if(isset($_GET['installed'])) { ?>
<script>
(function () {
    var url = new URL(window.location.href);
    url.searchParams.delete('installed');
    window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
}());
</script>
<?php } ?>
<?php if ($rb_tp_pending) { ?>
<style>
/* 테마 설치 UI에만 적용하며, 관리자 확장의 버튼 테마보다 우선한다. */
#container #rb-theme-install #rb-tp-layouts {margin-top:24px; min-width:0; max-width:100%; container:rb-tp-layouts / inline-size;}
#container #rb-theme-install .rb-tp-preview-body {display:flex; flex-wrap:nowrap; gap:24px; align-items:flex-start;}
#container #rb-theme-install .rb-tp-preview-canvas {flex:1 1 640px; min-width:640px; max-width:1025px; padding:0; box-sizing:border-box; overflow-x:auto;}
#container #rb-theme-install .rb-tp-preview-sidebar {flex:0 0 340px; min-width:340px; position:sticky; top:120px;}
#container #rb-theme-install .rb-tp-editor {box-sizing:border-box; min-width:0; padding:20px; border:1px solid #d6dce1; border-radius:10px; background:#fff;}
#container #rb-theme-install .rb-tp-layout-region {margin-bottom:28px; min-width:640px;}
#container #rb-theme-install .rb-tp-layout-position {margin-bottom:20px; padding:20px 50px; background:#f0f5f9; border:0; border-radius:10px;}
#container #rb-theme-install .rb-tp-module-wrap {flex:0 0 var(--rb-tp-module-width); max-width:var(--rb-tp-module-width); min-width:0; padding:8px; box-sizing:border-box;}
#container #rb-theme-install .rb-tp-page-connection {display:flex; flex-wrap:wrap; align-items:center; gap:8px; min-width:0;}
#container #rb-theme-install #rb-tp-layouts select,
#container #rb-theme-install #rb-tp-layouts .ui-select-control {max-width:100%; box-sizing:border-box;}
#container #rb-theme-install .rb-tp-tabs {display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;}
#container #rb-theme-install .rb-tp-tab {
    display:inline-flex; align-items:center; justify-content:center; box-sizing:border-box;
    width:auto; height:36px; min-height:36px; margin:0; padding:0 14px;
    border:1px solid #d6dce1 !important; border-radius:8px;
    background:#fff !important; color:#000 !important;
    font-family:inherit; font-size:13px; font-weight:600; line-height:1.4;
    text-align:center; white-space:nowrap; cursor:pointer;
    box-shadow:none !important; filter:none !important; transition:none;
}
#container #rb-theme-install .rb-tp-tab[aria-selected="true"] {
    padding-right:15px; padding-left:15px;
    background:#000 !important; color:#fff !important; border:0 !important;
}
#container #rb-theme-install .rb-tp-tab:focus-visible {outline:2px solid #3f72d4; outline-offset:2px;}
#container #rb-theme-install .rb-tp-connection-block {padding:16px; background:#fff; border:1px solid #d6dce1; border-radius:6px;}
#container #rb-theme-install .rb-tp-module-label {
    display:flex; align-items:center; justify-content:center; width:100%; min-width:0;
    height:auto; min-height:100px; box-sizing:border-box; margin:0; padding:20px;
    border:0 !important; border-radius:10px; background:#fff !important; color:#344054 !important;
    text-align:center; white-space:normal; font:inherit; box-shadow:none !important;
}
#container #rb-theme-install div.rb-tp-module-label {background:transparent !important; opacity:0.45;}
#container #rb-theme-install .rb-tp-module-button,
#container #rb-theme-install .rb-tp-module-button:hover,
#container #rb-theme-install .rb-tp-module-button:focus,
#container #rb-theme-install .rb-tp-module-button:active {
    background:#fff !important; filter:none !important; cursor:pointer;
}
#container #rb-theme-install #rb-tp-install {
    display:flex; align-items:center; justify-content:center; width:100%; height:50px;
    box-sizing:border-box; text-align:center; background:#000 !important; color:#fff !important;
}
/* Rb어드민 메뉴 폭을 제외한 실제 미리보기 영역의 너비를 기준으로 배치한다. */
@container rb-tp-layouts (max-width:1040px) {
    #container #rb-theme-install .rb-tp-preview-body {display:block;}
    #container #rb-theme-install .rb-tp-preview-canvas {width:100%; min-width:0; max-width:100%;}
    #container #rb-theme-install .rb-tp-preview-sidebar {position:static; width:100%; min-width:0; margin-top:20px;}
    #container #rb-theme-install .rb-tp-layout-region {min-width:0;}
    #container #rb-theme-install .rb-tp-layout-position {padding:20px;}
}
@container rb-tp-layouts (max-width:600px) {
    #container #rb-theme-install .rb-tp-module-wrap {flex-basis:100%; max-width:100%;}
    #container #rb-theme-install .rb-tp-layout-position {padding:12px;}
    #container #rb-theme-install .rb-tp-tab {max-width:100%; height:auto; min-height:36px; padding-top:8px; padding-bottom:8px; white-space:normal; overflow-wrap:break-word;}
}
/* 컨테이너 쿼리를 지원하지 않는 브라우저에서도 모바일에서 가로 넘침을 방지한다. */
@supports not (container-type:inline-size) {
    @media (max-width:1280px) {
        #container #rb-theme-install .rb-tp-preview-body {display:block;}
        #container #rb-theme-install .rb-tp-preview-canvas {width:100%; min-width:0; max-width:100%;}
        #container #rb-theme-install .rb-tp-preview-sidebar {position:static; width:100%; min-width:0; margin-top:20px;}
        #container #rb-theme-install .rb-tp-layout-region {min-width:0;}
        #container #rb-theme-install .rb-tp-layout-position {padding:20px;}
    }
    @media (max-width:600px) {
        #container #rb-theme-install .rb-tp-module-wrap {flex-basis:100%; max-width:100%;}
        #container #rb-theme-install .rb-tp-layout-position {padding:12px;}
        #container #rb-theme-install .rb-tp-tab {max-width:100%; height:auto; min-height:36px; padding-top:8px; padding-bottom:8px; white-space:normal; overflow-wrap:break-word;}
    }
}
@media (max-width:1024px) {
    #container #rb-theme-install .rb-tp-preview-body {display:block; padding-bottom:calc(var(--rb-tp-editor-height, 50vh) + 20px);}
    #container #rb-theme-install .rb-tp-preview-canvas {width:100%; min-width:0; max-width:100%;}
    #container #rb-theme-install .rb-tp-layout-region {min-width:0;}
    #container #rb-theme-install .rb-tp-module-wrap {flex-basis:100%; max-width:100%;}
    #container #rb-theme-install .rb-tp-preview-sidebar {
        position:fixed; z-index:1000; top:auto; right:0; bottom:0; left:0;
        display:flex; flex-direction:column; width:auto; min-width:0; max-height:50vh;
        box-sizing:border-box; margin:0; padding:12px 16px calc(12px + env(safe-area-inset-bottom, 0px));
        border-top:1px solid #d6dce1; background:#fff;
    }
    #container #rb-theme-install .rb-tp-editor {flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain;}
    #container #rb-theme-install .rb-tp-preview-sidebar > .rb-tp-install-actions {flex:0 0 auto; margin:10px 0 0 !important;}
}
</style>
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
                    <tr id="rb-tp-shop-guide" hidden><th scope="row">마켓 미사용</th><td>이 사이트는 마켓을 사용하지 않아 마켓 메인·상품 모듈의 연결 항목을 표시하지 않습니다. 테마의 디자인 자료는 보관하며, 마켓 기능을 자동으로 활성화하지 않습니다.</td></tr>
                </tbody>
                <tbody id="rb-tp-maps"></tbody>
            </table>
        </div>
        <div id="rb-tp-layouts" hidden></div>
        <div class="btn_confirm" style="margin-top:20px;"><button type="button" class="btn btn_03" id="rb-tp-install">테마 설치</button></div>
    </div>
    <template id="rb-tp-empty-help"><?php echo help('지금 연결할 항목이 없어도 설치할 수 있습니다.'); ?></template>
    <template id="rb-tp-legacy-help"><?php echo help('이전 배포 자료입니다. 제작 사이트에서 테마를 다시 내보내면 연결 없이 설치할 수 있습니다.'); ?></template>
</section>
<script src="<?php echo G5_ADMIN_URL; ?>/theme_package.js?v=2276-responsive-tab-style"></script>
<?php } ?>
<?php unset($rb_tp_dir,$rb_tp_ftp_files,$rb_tp_f,$rb_tp_n,$rb_tp_meta,$rb_tp_label,$rb_tp_pending); ?>
