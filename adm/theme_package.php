<?php
$sub_menu = '100280';
include_once('./_common.php');
include_once(G5_PATH.'/rb/rb.lib/rb_theme_package.lib.php');

header('Cache-Control: no-store');
$mode = isset($_POST['mode']) && is_string($_POST['mode']) ? $_POST['mode'] : '';
$download_id=isset($_POST['download_id']) && is_string($_POST['download_id']) && preg_match('/\A[a-f0-9]{32}\z/',$_POST['download_id']) ? $_POST['download_id'] : '';
try {
    if ($is_admin !== 'super' || $_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('최고관리자만 사용할 수 있습니다.');
    if (!isset($_SESSION['rb_theme_package_token'],$_POST['token']) || !is_string($_POST['token'])
        || !hash_equals($_SESSION['rb_theme_package_token'],$_POST['token'])) throw new RuntimeException('화면을 새로고침한 뒤 다시 시도해 주세요.');
    @set_time_limit(0);
    if ($mode==='publications') {
        $theme=isset($_POST['theme'])?$_POST['theme']:'';
        if(!is_string($theme) || $theme!==$config['cf_theme']) throw new RuntimeException('현재 적용된 테마를 확인해 주세요.');
        $publications=rb_tp_publications($theme);
        header('Content-Type: application/json; charset=utf-8');
        echo rb_tp_json(array('ok'=>true,'publications'=>array_values($publications),'used_folders'=>rb_tp_used_publication_folders($theme,$publications))); exit;
    }
    if ($mode === 'export') {
        $theme = isset($_POST['theme']) ? $_POST['theme'] : '';
        if(!is_string($theme) || $theme!==$config['cf_theme']) throw new RuntimeException('현재 적용된 테마만 내보낼 수 있습니다.');
        $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
        if ($name === '') throw new RuntimeException('배포할 테마 이름을 입력해 주세요.');
        // 압축이 오래 걸려도 같은 관리자의 다른 요청이 세션 잠금에 막히지 않게 한다.
        if(session_status()===PHP_SESSION_ACTIVE) session_write_close();
        $tmp = tempnam(sys_get_temp_dir(),'rb-theme-');
        if (!$tmp || !rename($tmp,$tmp.'.zip')) throw new RuntimeException('임시 저장 공간을 확인해 주세요.');
        $tmp .= '.zip';
        try {
            $publication=array('mode'=>isset($_POST['publication_mode']) && is_string($_POST['publication_mode'])?$_POST['publication_mode']:'new',
                'id'=>isset($_POST['publication_id']) && is_string($_POST['publication_id'])?$_POST['publication_id']:'');
            $package=rb_tp_export($theme,$name,$tmp,$publication);
            if($download_id!=='') setcookie('rb_theme_export_'.$download_id,'ready',array('expires'=>time()+300,'path'=>'/',
                'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>false,'samesite'=>'Lax'));
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$package['package_folder'].'_'.date('Ymd_His').'.zip"');
            header('Content-Length: '.filesize($tmp));
            while (ob_get_level()) ob_end_clean();
            readfile($tmp);
        } finally { if (is_file($tmp)) unlink($tmp); }
        exit;
    }
    if(in_array($mode,array('inspect','install'),true) && ($versionError=rb_tp_install_version_message())!=='') throw new RuntimeException($versionError);
    $folder=isset($_POST['folder']) && is_string($_POST['folder']) ? $_POST['folder'] : '';
    if(!rb_tp_slug($folder)) throw new RuntimeException('FTP로 올린 테마 폴더를 선택해 주세요.');
    $path=G5_PATH.'/theme/'.$folder;
    if(!is_dir($path) || !rb_tp_under($path,G5_PATH.'/theme')) throw new RuntimeException('테마 폴더가 없습니다. FTP 업로드 완료 후 새로고침해 주세요.');
    $updating=is_file($path.'/rb-package.json');
    if($mode==='inspect') {
        if(!$updating) rb_tp_check_theme_name($folder);
        list($package,$manifest)=rb_tp_open($path,$updating); $package->close();
        if($updating) rb_tp_check_update_identity($manifest,rb_tp_state($folder,true));
        $choices=array(); $optional=isset($manifest['scope']) && $manifest['scope']==='main-design';
        $moduleUses=$updating || $optional?array():rb_tp_reference_modules($manifest['data']);
        $categories=array();
        if(!$updating && $optional) {
            $catalogs=array(); $categories=rb_tp_board_categories();
            foreach(rb_tp_module_connections($manifest['data']) as $module) {
                if(!$module['available'] || !$module['slots']) continue;
                foreach($module['slots'] as &$slot) {
                    if(!isset($catalogs[$slot['kind']])) $catalogs[$slot['kind']]=rb_tp_catalog($slot['kind']);
                    $slot['options']=(object)$catalogs[$slot['kind']];
                }
                unset($slot); $choices[]=$module;
            }
        }
        foreach($updating || $optional?array():$manifest['refs'] as $kind=>$refs) {
            if(!$refs) continue;
            $catalog=rb_tp_catalog($kind);
            foreach($refs as $source=>$unused) $choices[]=array('kind'=>$kind,'source'=>(string)$source,
                'label'=>isset($manifest['reference_titles'][$kind][$source]) && is_string($manifest['reference_titles'][$kind][$source])?$manifest['reference_titles'][$kind][$source]
                    :(isset($manifest['boards'][$source]['title']) && $kind==='board'?$manifest['boards'][$source]['title']:(string)$source),
                'options'=>(object)$catalog,'selected'=>!$optional && isset($catalog[$source])?(string)$source:'',
                'modules'=>isset($moduleUses[$kind][$source])?$moduleUses[$kind][$source]:array());
        }
        $_SESSION['rb_theme_package_checked']=array('folder'=>$folder,'hash'=>hash_file('sha256',$path.'/rb-package/manifest.json'),'time'=>time());
        $out=array('ok'=>true,'name'=>$manifest['name'],'theme'=>$folder,'choices'=>$choices,'updating'=>$updating,'optional_connections'=>$optional,
            'module_connections'=>$optional,'board_categories'=>(object)$categories,'shop_enabled'=>rb_tp_shop_enabled(),
            'layout_preview'=>!$updating && $optional?rb_tp_layout_preview($manifest['data']):array());
    } elseif($mode==='install') {
        $checked=isset($_SESSION['rb_theme_package_checked'])?$_SESSION['rb_theme_package_checked']:array();
        if(empty($checked['hash']) || $checked['folder']!==$folder || time()-$checked['time']>3600
            || !hash_equals($checked['hash'],hash_file('sha256',$path.'/rb-package/manifest.json'))) throw new RuntimeException('설치 정보가 변경되었습니다. 다시 확인해 주세요.');
        $maps=isset($_POST['maps']) && is_string($_POST['maps']) ? json_decode($_POST['maps'],true):array();
        if(!is_array($maps)) throw new RuntimeException('연결 정보를 확인해 주세요.');
        $out=$updating?rb_tp_update_files($folder):rb_tp_install($path,$folder,$maps); $out['ok']=true; $out['updating']=$updating;
        unset($_SESSION['rb_theme_package_checked']);
    } else throw new RuntimeException('잘못된 요청입니다.');
    header('Content-Type: application/json; charset=utf-8');
    echo rb_tp_json($out);
} catch(Throwable $e) {
    http_response_code(400);
    if($mode==='export') {
        header('Content-Type: text/html; charset=utf-8');
        if($download_id!=='') {
            $message=json_encode(array('type'=>'rb-theme-export','id'=>$download_id,'ok'=>false,'message'=>$e->getMessage()),
                JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);
            echo '<!doctype html><meta charset="utf-8"><script>window.parent.postMessage('.$message.',window.location.origin);</script>';
            exit;
        }
        echo '<!doctype html><html lang="ko"><meta charset="utf-8"><title>테마 내보내기</title><body style="font-family:sans-serif;padding:32px"><h1>테마를 내보내지 못했습니다</h1><p>'
            .htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').'</p><p>문제를 확인한 뒤 테마설정 패널에서 다시 시도해 주세요.</p></body></html>';
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo rb_tp_json(array('ok'=>false,'message'=>$e->getMessage()));
}
