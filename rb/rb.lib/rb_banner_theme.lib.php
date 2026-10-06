<?php
if (!defined('_GNUBOARD_')) exit;

// 배너 ID의 소속만 별도 기록한다. 출력그룹명과 DB 구조는 변경하지 않는다.
function rb_banner_theme_registry($refresh=false)
{
    static $cache=null;
    if($refresh || $cache===null) {
        $file=G5_DATA_PATH.'/rb.banner-themes.json';
        $cache=array('format'=>'rebuilder-banner-themes','owners'=>array(),'scoped'=>array(),'retired'=>array());
        if(is_file($file)) {
            $value=json_decode(file_get_contents($file),true);
            if(!is_array($value) || !isset($value['format'],$value['owners'],$value['scoped'])
                || $value['format']!==$cache['format'] || !is_array($value['owners']) || !is_array($value['scoped']))
                throw new RuntimeException('배너 테마 기록을 읽을 수 없습니다.');
            $cache=$value;
            if(!isset($cache['retired'])) $cache['retired']=array();
            if(!is_array($cache['retired'])) throw new RuntimeException('배너 테마 기록을 읽을 수 없습니다.');
        }
    }
    return $cache;
}
function rb_banner_theme_save($owners,$installedTheme=null)
{
    foreach($owners as $id=>$theme)
        if(!ctype_digit((string)$id) || (int)$id<1 || ($theme!==null && $theme!=='*' && !preg_match('/\A[A-Za-z0-9_.-]+\z/D',(string)$theme)))
            throw new RuntimeException('배너 테마 정보가 올바르지 않습니다.');
    if($installedTheme!==null && !preg_match('/\A[A-Za-z0-9_.-]+\z/D',$installedTheme))
        throw new RuntimeException('배너 테마 정보가 올바르지 않습니다.');
    $lock=fopen(G5_DATA_PATH.'/rb.banner-themes.lock','c');
    if(!$lock) throw new RuntimeException('data 폴더의 쓰기 권한을 확인해 주세요.');
    $tmp=false;
    try {
        if(!flock($lock,LOCK_EX)) throw new RuntimeException('배너 테마 기록을 저장할 수 없습니다.');
        $state=rb_banner_theme_registry(true);
        // 재설치는 이번에 받은 배너만 연결한다. 이전 배너 행/이미지는 삭제하지 않는다.
        if($installedTheme!==null)
            foreach($state['owners'] as $id=>$owner) if($owner===$installedTheme) {
                unset($state['owners'][$id]); $state['retired'][$id]=true;
            }
        foreach($owners as $id=>$theme) {
            unset($state['retired'][$id]);
            if($theme===null) unset($state['owners'][$id]);
            else $state['owners'][$id]=$theme;
        }
        if($installedTheme!==null) $state['scoped'][$installedTheme]=true;
        $bytes=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $tmp=tempnam(G5_DATA_PATH,'.rb-banner-');
        if($bytes===false || $tmp===false || file_put_contents($tmp,$bytes)!==strlen($bytes))
            throw new RuntimeException('배너 테마 기록을 저장할 수 없습니다.');
        chmod($tmp,0644);
        if(!rename($tmp,G5_DATA_PATH.'/rb.banner-themes.json')) throw new RuntimeException('배너 테마 기록을 저장할 수 없습니다.');
        rb_banner_theme_registry(true);
    } finally {
        if($tmp!==false && is_file($tmp)) unlink($tmp);
        flock($lock,LOCK_UN); fclose($lock);
    }
}
function rb_banner_theme_usage($refresh=false)
{
    static $cache=null;
    if(!$refresh && $cache!==null) return $cache;
    $cache=array(); $state=rb_banner_theme_registry();
    foreach(array('rb_module','rb_module_shop') as $table) {
        $result=sql_query("SELECT DISTINCT b.bn_id, m.md_theme
            FROM rb_banner AS b INNER JOIN `$table` AS m
            ON m.md_type = 'banner' AND m.md_theme <> '' AND (
                (m.md_banner = '개별출력' AND b.bn_position = '개별출력' AND m.md_banner_id = b.bn_id)
                OR (m.md_banner NOT IN ('', '개별출력', '미출력') AND m.md_banner = b.bn_position)
            )",false);
        if(!$result) continue;
        while($row=sql_fetch_array($result)) {
            $id=(int)$row['bn_id']; $theme=(string)$row['md_theme'];
            // 설치한 테마는 ID 기록으로 구분한다. 같은 그룹명을 쓰는 타 테마와 섞지 않는다.
            if(isset($state['owners'][$id]) || isset($state['retired'][$id]) || !empty($state['scoped'][$theme])) continue;
            $cache[$id][$theme]=true;
        }
    }
    foreach($state['owners'] as $id=>$theme) if($theme!=='*') $cache[(int)$id]=array($theme=>true);
    return $cache;
}
function rb_banner_theme_sql($theme,$legacyGlobal=false)
{
    if(!is_string($theme) || $theme==='') return '';
    $state=rb_banner_theme_registry(); $allowed=array(); $other=array_map('intval',array_keys($state['retired']));
    foreach($state['owners'] as $id=>$owner) {
        if($owner===$theme || $owner==='*') $allowed[]=(int)$id;
        else $other[]=(int)$id;
    }
    if(!empty($state['scoped'][$theme])) return $allowed?' AND bn_id IN ('.implode(',',$allowed).')':' AND 1 = 0';
    // 소속 기록이 없던 기존 테마의 직접 출력은 기존 공용 배너 사용을 유지한다.
    if($legacyGlobal) return $other?' AND bn_id NOT IN ('.implode(',',$other).')':'';
    $used=rb_banner_theme_usage(); $excluded=$other;
    foreach($used as $id=>$themes) if(!isset($themes[$theme])) $excluded[]=(int)$id;
    return $excluded?' AND bn_id NOT IN ('.implode(',',array_unique($excluded)).')':'';
}
function rb_banner_module_code($module)
{
    $args=array();
    foreach(array('md_banner','md_banner_id','md_banner_skin','md_order_banner') as $key)
        $args[]=var_export(isset($module[$key])?(string)$module[$key]:'',true);
    $args[]=var_export(isset($module['md_theme'])?(string)$module['md_theme']:null,true);
    return '<?php echo rb_banners('.implode(',',$args).'); ?>';
}
