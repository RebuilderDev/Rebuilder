<?php
$sub_menu = '100280';
include_once('./_common.php');
include_once(G5_PATH.'/rb/rb.lib/rb_theme_package.lib.php');

header('Cache-Control: no-store');
$mode = isset($_POST['mode']) && is_string($_POST['mode']) ? $_POST['mode'] : '';
// 이전 패널의 내보내기 주소도 같은 권한 검사와 다운로드 처리를 사용한다.
if(in_array($mode,array('export','publications'),true)) rb_tp_export_request();
try {
    if ($is_admin !== 'super' || $_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('최고관리자만 사용할 수 있습니다.');
    if (!isset($_SESSION['rb_theme_package_token'],$_POST['token']) || !is_string($_POST['token'])
        || !hash_equals($_SESSION['rb_theme_package_token'],$_POST['token'])) throw new RuntimeException('화면을 새로고침한 뒤 다시 시도해 주세요.');
    @set_time_limit(0);
    if(in_array($mode,array('inspect','install'),true) && ($versionError=rb_tp_install_version_message())!=='') throw new RuntimeException($versionError);
    $folder=isset($_POST['folder']) && is_string($_POST['folder']) ? $_POST['folder'] : '';
    if(!rb_tp_slug($folder)) throw new RuntimeException('FTP로 올린 테마 폴더를 선택해 주세요.');
    $path=G5_PATH.'/theme/'.$folder;
    if(!is_dir($path) || !rb_tp_under($path,G5_PATH.'/theme')) throw new RuntimeException('테마 폴더가 없습니다. FTP 업로드 완료 후 새로고침해 주세요.');
    if(is_file($path.'/rb-package.json')) throw new RuntimeException('이미 설치된 테마입니다. 다시 설치할 필요가 없습니다.');
    if($mode==='inspect') {
        rb_tp_check_theme_name($folder);
        list($package,$manifest)=rb_tp_open($path); $package->close();
        $choices=array(); $optional=isset($manifest['scope']) && $manifest['scope']==='main-design';
        $moduleUses=$optional?array():rb_tp_reference_modules($manifest['data']);
        $categories=array(); $pageCatalogs=array();
        if($optional) {
            $catalogs=array(); $categories=rb_tp_board_categories();
            foreach(array('group','content') as $kind)
                if(!empty($manifest['refs'][$kind])) $pageCatalogs[$kind]=(object)rb_tp_catalog($kind);
            foreach(rb_tp_module_connections($manifest['data']) as $module) {
                if(!$module['available'] || !$module['slots']) continue;
                foreach($module['slots'] as &$slot) {
                    if(!isset($catalogs[$slot['kind']])) $catalogs[$slot['kind']]=rb_tp_catalog($slot['kind']);
                    $slot['options']=(object)$catalogs[$slot['kind']];
                }
                unset($slot); $choices[]=$module;
            }
        }
        foreach($optional?array():$manifest['refs'] as $kind=>$refs) {
            if(!$refs) continue;
            $catalog=rb_tp_catalog($kind);
            foreach($refs as $source=>$unused) $choices[]=array('kind'=>$kind,'source'=>(string)$source,
                'label'=>isset($manifest['reference_titles'][$kind][$source]) && is_string($manifest['reference_titles'][$kind][$source])?$manifest['reference_titles'][$kind][$source]
                    :(isset($manifest['boards'][$source]['title']) && $kind==='board'?$manifest['boards'][$source]['title']:(string)$source),
                'options'=>(object)$catalog,'selected'=>!$optional && isset($catalog[$source])?(string)$source:'',
                'modules'=>isset($moduleUses[$kind][$source])?$moduleUses[$kind][$source]:array());
        }
        $_SESSION['rb_theme_package_checked']=array('folder'=>$folder,'hash'=>hash_file('sha256',$path.'/rb-package/manifest.json'),'time'=>time());
        $out=array('ok'=>true,'name'=>$manifest['name'],'theme'=>$folder,'choices'=>$choices,'optional_connections'=>$optional,
            'module_connections'=>$optional,'board_categories'=>(object)$categories,'page_catalogs'=>(object)$pageCatalogs,'shop_enabled'=>rb_tp_shop_enabled(),
            'layout_preview'=>$optional?rb_tp_layout_preview($manifest['data'],isset($manifest['reference_titles'])?$manifest['reference_titles']:array()):array());
    } elseif($mode==='install') {
        $checked=isset($_SESSION['rb_theme_package_checked'])?$_SESSION['rb_theme_package_checked']:array();
        if(empty($checked['hash']) || $checked['folder']!==$folder || time()-$checked['time']>3600
            || !hash_equals($checked['hash'],hash_file('sha256',$path.'/rb-package/manifest.json'))) throw new RuntimeException('설치 정보가 변경되었습니다. 다시 확인해 주세요.');
        $maps=array();
        if(isset($_POST['maps'])) {
            if(!is_string($_POST['maps'])) throw new RuntimeException('연결 정보를 확인해 주세요.');
            $maps=json_decode($_POST['maps'],true);
            // common.php가 POST에 추가한 SQL 이스케이프를 한 번 해제한다.
            // 이미 정상 JSON인 환경에서는 값 안의 역슬래시를 그대로 유지한다.
            if(json_last_error()!==JSON_ERROR_NONE) $maps=json_decode(stripslashes($_POST['maps']),true);
        }
        if(!is_array($maps)) throw new RuntimeException('연결 정보를 확인해 주세요.');
        $out=rb_tp_install($path,$folder,$maps); $out['ok']=true;
        unset($_SESSION['rb_theme_package_checked']);
    } else throw new RuntimeException('잘못된 요청입니다.');
    header('Content-Type: application/json; charset=utf-8');
    echo rb_tp_json($out);
} catch(Throwable $e) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo rb_tp_json(array('ok'=>false,'message'=>$e->getMessage()));
}
