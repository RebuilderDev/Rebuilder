<?php
if (!defined('_GNUBOARD_')) exit;

// 이름/경로와 배포 계보를 분리한다. 식별정보는 제작자 인증이나 재배포 권한을 뜻하지 않는다.
function rb_tp_identity_valid($value)
{
    return is_array($value) && isset($value['id'],$value['release'],$value['revision'],$value['folder']) && rb_tp_slug($value['folder'])
        && is_string($value['id']) && preg_match('/\A[a-f0-9]{32}\z/',$value['id'])
        && is_string($value['release']) && preg_match('/\A[a-f0-9]{32}\z/',$value['release'])
        && is_int($value['revision']) && $value['revision']>0;
}
function rb_tp_publications($theme)
{
    if (!rb_tp_slug($theme)) throw new RuntimeException('테마 폴더명을 확인해 주세요.');
    $file=G5_DATA_PATH.'/rb.theme-publish/'.$theme.'.json'; $out=array();
    if (is_file($file)) {
        $out=json_decode(file_get_contents($file),true);
        if (!is_array($out)) throw new RuntimeException('보관된 테마 배포 정보를 읽을 수 없습니다.');
        foreach($out as $id=>$row) if(!rb_tp_identity_valid($row) || $id!==$row['id'] || !isset($row['name'],$row['folders'])
            || !is_string($row['name']) || !is_array($row['folders'])) throw new RuntimeException('보관된 테마 배포 정보를 확인해 주세요.');
    }
    $installed=rb_tp_state($theme,true);
    if (isset($installed['identity']) && rb_tp_identity_valid($installed['identity'])) {
        $identity=$installed['identity']; $id=$identity['id'];
        if (!isset($out[$id])) $out[$id]=array_merge($identity,array('name'=>$installed['name'],'folders'=>array($theme)));
        elseif ($identity['revision']>$out[$id]['revision']) $out[$id]['revision']=$identity['revision'];
    }
    return $out;
}
function rb_tp_export($theme,$name,$zipfile,$publication=array())
{
    if (!rb_tp_slug($theme) || !is_dir(G5_PATH.'/theme/'.$theme)) throw new RuntimeException('테마를 찾을 수 없습니다.');
    $mode=isset($publication['mode'])?$publication['mode']:'new';
    if (!in_array($mode,array('new','update'),true)) throw new RuntimeException('배포 방식을 선택해 주세요.');
    $lock='rb-publish-'.substr(hash('sha256',G5_PATH.'|'.$theme),0,30);
    $locked=rb_tp_rows('SELECT GET_LOCK('.rb_tp_q($lock).',10) AS ok');
    if (empty($locked[0]['ok'])) throw new RuntimeException('테마 내보내기가 진행 중입니다. 잠시 후 다시 시도해 주세요.');
    try {
        $publications=rb_tp_publications($theme);
        if ($mode==='update') {
            $id=isset($publication['id'])?$publication['id']:'';
            if (!is_string($id) || !isset($publications[$id])) throw new RuntimeException('업데이트할 기존 배포를 선택해 주세요.');
            $previous=$publications[$id];
            $name=$previous['folder'];
            $identity=array('id'=>$id,'release'=>bin2hex(random_bytes(16)),'revision'=>$previous['revision']+1,'folder'=>$name);
        } else {
            $name=is_string($name)?trim($name):'';
            if(in_array(strtolower($name),rb_tp_used_publication_folders($theme,$publications),true))
                throw new RuntimeException('새 테마는 현재 테마 또는 기존 배포와 다른 폴더명을 입력해 주세요. 같은 테마를 수정해 배포할 때는 업데이트를 선택하세요.');
            $identity=array('id'=>bin2hex(random_bytes(16)),'release'=>bin2hex(random_bytes(16)),'revision'=>1,'folder'=>$name);
        }
        $m=rb_tp_export_archive($theme,$name,$zipfile,$identity,$mode);
        $folders=isset($previous)?$previous['folders']:array(); $folders[]=$m['package_folder'];
        $publications[$identity['id']]=array_merge($identity,array('name'=>$m['name'],'folders'=>array_values(array_unique($folders))));
        $file=G5_DATA_PATH.'/rb.theme-publish/'.$theme.'.json';
        if (!is_dir(dirname($file)) && !mkdir(dirname($file),0755,true)) throw new RuntimeException('테마 배포 정보를 저장할 수 없습니다.');
        $tmp=tempnam(dirname($file),'.publish-');
        if ($tmp===false) throw new RuntimeException('테마 배포 정보를 저장할 수 없습니다.');
        try {
            $bytes=rb_tp_json($publications);
            if (file_put_contents($tmp,$bytes)!==strlen($bytes) || !rename($tmp,$file)) throw new RuntimeException('테마 배포 정보를 저장할 수 없습니다.');
        } finally { if(is_file($tmp)) unlink($tmp); }
        return $m;
    } finally { sql_query('SELECT RELEASE_LOCK('.rb_tp_q($lock).')',false); }
}
function rb_tp_used_publication_folders($theme,$publications)
{
    $used=array($theme);
    foreach($publications as $row) $used[]=$row['folder'];
    foreach((array)glob(G5_PATH.'/theme/*',GLOB_ONLYDIR) as $dir) $used[]=basename($dir);
    return array_values(array_unique(array_map('strtolower',$used)));
}
function rb_tp_release_path($relative,$theme)
{
    if (!rb_tp_path($relative)) throw new RuntimeException('테마 업데이트 파일 경로 오류');
    $allowed=strpos($relative,'theme/'.$theme.'/')===0 || strpos($relative,'data/'.$theme.'/package/')===0;
    foreach(array('widget','banner') as $kind) {
        $root=$kind==='widget'?'rb/rb.widget/':'rb/rb.mod/banner/skin/';
        if (strpos($relative,$root.$theme.'_')===0 || strpos($relative,$root.$theme.'/')===0) $allowed=true;
    }
    if (!$allowed || preg_match('~^theme/[^/]+/(?:rb-package/|rb-package\.json$)~',$relative))
        throw new RuntimeException('다른 테마 또는 설치 정보는 업데이트할 수 없습니다.');
    $dest=strpos($relative,'data/')===0?G5_DATA_PATH.'/'.substr($relative,5):G5_PATH.'/'.$relative;
    // 중간 디렉터리 링크를 통한 경로 이탈도 검사한다.
    $root=strpos($relative,'data/')===0?G5_DATA_PATH:G5_PATH; $part=$root;
    $tail=substr($dest,strlen($root)+1);
    foreach(explode('/',$tail) as $piece) {
        $part.='/'.$piece;
        if(is_link($part) || (file_exists($part) && !rb_tp_under($part,$root))) throw new RuntimeException('업데이트 경로에 링크가 있습니다.');
    }
    return $dest;
}
function rb_tp_release_material($m,$theme,$state)
{
    $source=$m['source_theme']; $sourcePath=rtrim((string)parse_url($m['source_url'],PHP_URL_PATH),'/');
    $replace=array($m['source_url'].'/theme/'.$source.'/'=>G5_URL.'/theme/'.$theme.'/',
        $sourcePath.'/theme/'.$source.'/'=>parse_url(G5_URL,PHP_URL_PATH).'/theme/'.$theme.'/',
        '/theme/'.$source.'/'=>'/theme/'.$theme.'/', $m['source_data_url'].'/'.$source.'/'=>G5_DATA_URL.'/'.$theme.'/');
    foreach(isset($m['skin_paths'])?$m['skin_paths']:array() as $from=>$to) {
        if(!rb_tp_path(rtrim($from,'/')) || !rb_tp_path(rtrim($to,'/'))) throw new RuntimeException('스킨 자료 경로 오류');
        $replace[$m['source_url'].'/'.$from]=G5_URL.'/theme/'.$theme.'/'.$to;
        $replace[$sourcePath.'/'.$from]=parse_url(G5_URL,PHP_URL_PATH).'/theme/'.$theme.'/'.$to;
        $replace['/'.$from]='/theme/'.$theme.'/'.$to;
    }
    $targets=array(); $dependencies=isset($state['dependencies'])?$state['dependencies']:array();
    foreach($m['files'] as $entry=>$info) if(strpos($entry,'theme/')===0) $targets[$entry]='theme/'.$theme.'/'.substr($entry,6);
    foreach(array('widget','banner') as $kind) {
        if(!isset($m['deps'][$kind]) || !is_array($m['deps'][$kind])) throw new RuntimeException('위젯/배너 스킨 정보 오류');
        $used=array();
        foreach($m['deps'][$kind] as $old=>$key) {
            if(!rb_tp_dependency_valid($kind,$old) || !preg_match('/\A[a-f0-9]{16}\z/',$key)) throw new RuntimeException('의존 파일 경로 오류');
            $name=isset($m['dependency_names'][$kind][$key])?$m['dependency_names'][$kind][$key]:basename($old);
            $dest=rb_tp_dependency_path($kind,$theme,$name);
            // 이전 설치본의 모듈은 평면 경로를 사용한다. 파일 업데이트에서 그 연결을 바꾸지 않는다.
            $legacyRoot=$kind==='widget'?'rb/rb.widget/':'rb/rb.mod/banner/skin/';
            if(!isset($dependencies[$kind][$dest])) foreach(isset($dependencies[$kind])?$dependencies[$kind]:array() as $existing=>$info) {
                if(isset($info['name']) && $info['name']===$name && rb_tp_dependency_valid($kind,$existing)
                    && strpos($existing,$legacyRoot.$theme.'_')===0) { $dest=$existing; break; }
            }
            if(isset($used[strtolower($dest)])) throw new RuntimeException('중복된 위젯/배너 스킨 이름입니다.');
            $used[strtolower($dest)]=true; $dependencies[$kind][$dest]=array('name'=>$name);
            $prefix='deps/'.$kind.'/'.$key.'/'; $required=$kind==='widget'?'widget.php':'banner.skin.php';
            if(!isset($m['files'][$prefix.$required])) throw new RuntimeException('위젯/배너 스킨 파일이 누락되었습니다.');
            $replace[$old]=$dest; $replace[substr($old,3)]=substr($dest,3);
            foreach($m['files'] as $entry=>$info) if(strpos($entry,$prefix)===0) $targets[$entry]=$dest.'/'.substr($entry,strlen($prefix));
        }
    }
    foreach($m['assets'] as $rel=>$entry) {
        if(!rb_tp_path($rel) || !isset($m['files'][$entry]) || strpos($entry,'assets/')!==0) throw new RuntimeException('이미지 정보 오류');
        $newrel=$theme.'/package/'.substr($entry,7); $targets[$entry]='data/'.$newrel;
        $replace[$m['source_data_url'].'/'.$rel]=G5_DATA_URL.'/'.$newrel;
        $replace[parse_url($m['source_data_url'],PHP_URL_PATH).'/'.$rel]=parse_url(G5_DATA_URL,PHP_URL_PATH).'/'.$newrel;
    }
    foreach($targets as $entry=>$dest) rb_tp_release_path($dest,$theme);
    $replace[rtrim($m['source_url'],'/').'/']=rtrim(G5_URL,'/').'/';
    return array('targets'=>$targets,'replace'=>$replace,'dependencies'=>$dependencies);
}
function rb_tp_release_bytes($zip,$m,$entry,$material,$maps)
{
    $bytes=$zip->getFromName($entry);
    if(!is_string($bytes) || !hash_equals($m['files'][$entry]['sha256'],hash('sha256',$bytes))) throw new RuntimeException('업로드 파일이 변경되었습니다. 다시 확인해 주세요.');
    if(isset($m['asset_links'][$entry])) $bytes=strtr($bytes,$m['asset_links'][$entry]);
    $bytes=rb_tp_replace($bytes,$material['replace']);
    $bytes=preg_replace_callback('/([?&]|&amp;)(bo_table|co_id|fr_id|po_id|ca_id|gr_id|it_id|ev_id)=([A-Za-z0-9_]+)(?=[&#\s"\'<>]|$)/',function($a) use($maps) {
        $kinds=array('bo_table'=>'board','co_id'=>'content','fr_id'=>'form','po_id'=>'poll','ca_id'=>'category','gr_id'=>'group','it_id'=>'item','ev_id'=>'event');
        $kind=$kinds[$a[2]];
        return $a[1].$a[2].'='.(isset($maps[$kind][$a[3]])?$maps[$kind][$a[3]]:$a[3]);
    },$bytes);
    return $entry==='theme/readme.txt'?rb_tp_theme_readme($bytes,$m['name']):$bytes;
}
// FTP 덮어쓰기는 사용자가 결정한다. 여기서는 설치된 테마의 경로 연결만 다시 반영한다.
function rb_tp_update_files($folder)
{
    if(!rb_tp_slug($folder)) throw new RuntimeException('테마 폴더명을 확인해 주세요.');
    $lock='rb-theme-'.substr(hash('sha256',G5_PATH),0,30);
    $locked=rb_tp_rows('SELECT GET_LOCK('.rb_tp_q($lock).',10) AS ok');
    if(empty($locked[0]['ok'])) throw new RuntimeException('다른 테마 설치가 진행 중입니다.');
    $zip=null;
    try {
        list($zip,$m)=rb_tp_open(G5_PATH.'/theme/'.$folder,true);
        $state=rb_tp_state($folder,true);
        rb_tp_check_update_identity($m,$state);
        $material=rb_tp_release_material($m,$folder,$state);
        $maps=isset($state['source_maps'])?$state['source_maps']:array();
        foreach($material['targets'] as $entry=>$relative) {
            $dest=rb_tp_release_path($relative,$folder);
            if(rb_tp_text_file($entry)) {
                if($m['files'][$entry]['size']>8*1024*1024) throw new RuntimeException('편집 가능한 소스 파일은 8MB 이내여야 합니다.');
                $bytes=rb_tp_release_bytes($zip,$m,$entry,$material,$maps);
                $stream=fopen('php://temp','w+b'); fwrite($stream,$bytes); rewind($stream);
                $hash=hash('sha256',$bytes);
            } else { $stream=$zip->stream($entry); $hash=$m['files'][$entry]['sha256']; }
            if(is_file($dest) && hash_equals($hash,hash_file('sha256',$dest))) { if(is_resource($stream)) fclose($stream); continue; }
            rb_tp_replace_file($dest,$stream,$hash);
        }
        $state['identity']=$m['identity']; $state['name']=$m['name']; $state['dependencies']=$material['dependencies'];
        $bytes=rb_tp_json($state); $stream=fopen('php://temp','w+b'); fwrite($stream,$bytes); rewind($stream);
        rb_tp_replace_file(G5_PATH.'/theme/'.$folder.'/rb-package.json',$stream,hash('sha256',$bytes));
        rb_tp_state($folder,true);
        return array('theme'=>$folder,'name'=>$m['name']);
    } finally { if($zip) $zip->close(); sql_query('SELECT RELEASE_LOCK('.rb_tp_q($lock).')',false); }
}
function rb_tp_check_update_identity($m,$state)
{
    if(empty($m['identity']) || empty($state['identity']) || !rb_tp_identity_valid($m['identity']) || !rb_tp_identity_valid($state['identity'])
        || $m['identity']['id']!==$state['identity']['id'] || empty($m['delivery']) || $m['delivery']!=='update')
        throw new RuntimeException('이 폴더에 설치된 테마의 업데이트 자료가 아닙니다. 새 테마는 다른 폴더에 설치해 주세요.');
    if($m['identity']['revision']<$state['identity']['revision']) throw new RuntimeException('현재 설치된 배포보다 이전 버전입니다.');
}
function rb_tp_replace_file($dest,$stream,$hash)
{
    if(!is_resource($stream)) throw new RuntimeException('업로드 파일을 읽을 수 없습니다.');
    $tmp=false;
    try {
        $dir=dirname($dest);
        if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('파일 저장 폴더를 만들 수 없습니다.');
        if(!is_writable($dir)) throw new RuntimeException('PHP 파일 쓰기 권한을 확인해 주세요.');
        $tmp=tempnam($dir,'.rb-write-');
        if($tmp===false) throw new RuntimeException('파일 저장 공간을 확인해 주세요.');
        $handle=fopen($tmp,'wb');
        if(!$handle) throw new RuntimeException('파일 쓰기 권한을 확인해 주세요.');
        try { if(stream_copy_to_stream($stream,$handle)===false) throw new RuntimeException('파일 저장에 실패했습니다.'); }
        finally { fclose($handle); }
        fclose($stream);
        if(!hash_equals($hash,hash_file('sha256',$tmp))) throw new RuntimeException('FTP 파일이 변경되었거나 저장 공간이 부족합니다. 다시 업로드해 주세요.');
        @chmod($tmp,is_file($dest)?(fileperms($dest)&0777):0644);
        if(!@rename($tmp,$dest)) throw new RuntimeException('파일을 반영할 수 없습니다. 쓰기 권한을 확인해 주세요.');
    } finally { if(is_resource($stream)) fclose($stream); if($tmp!==false && is_file($tmp)) unlink($tmp); }
}
