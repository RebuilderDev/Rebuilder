<?php
if (!defined('_GNUBOARD_')) exit;
include_once(__DIR__.'/rb_theme_release.lib.php');

/* 패키지 설치는 새 테마의 소스와 디자인 자료만 추가한다. 적용 시 스킨 설정 저장/복원은 별도로 처리한다. */
function rb_tp_tables()
{
    return array('rb_module'=>array('md_theme','md_id'), 'rb_module_shop'=>array('md_theme','md_id'),
        'rb_section'=>array('sec_theme','sec_id'), 'rb_section_shop'=>array('sec_theme','sec_id'),
        'rb_theme'=>array('theme_key',''), 'rb_theme_carousel'=>array('cf_theme','id'), 'rb_config'=>array('co_theme','co_id'));
}
function rb_tp_state($theme,$refresh=false)
{
    static $cache=array();
    if (!rb_tp_slug($theme)) return array();
    if ($refresh || !array_key_exists($theme,$cache)) {
        $file=G5_PATH.'/theme/'.$theme.'/rb-package.json';
        $state=is_file($file)?json_decode(file_get_contents($file),true):null;
        $cache[$theme]=is_array($state) && isset($state['format']) && $state['format']==='rebuilder-installed'?$state:array();
    }
    return $cache[$theme];
}
function rb_tp_save_aos($theme,$type,$settings)
{
    if(!rb_tp_state($theme)) return false;
    if(!in_array($type,array('general','market'),true)) throw new RuntimeException('동작 효과 설정 구분을 확인해 주세요.');
    $lock='rb-theme-'.substr(hash('sha256',G5_PATH),0,30);
    $locked=rb_tp_rows('SELECT GET_LOCK('.rb_tp_q($lock).',10) AS ok');
    if(empty($locked[0]['ok'])) throw new RuntimeException('테마 설정을 저장 중입니다. 잠시 후 다시 시도해 주세요.');
    try {
        $state=rb_tp_state($theme,true);
        if(!$state) throw new RuntimeException('설치된 테마 정보를 확인해 주세요.');
        $state['aos'][$type]=$settings;
        $bytes=rb_tp_json($state); $stream=fopen('php://temp','w+b'); fwrite($stream,$bytes); rewind($stream);
        rb_tp_replace_file(G5_PATH.'/theme/'.$theme.'/rb-package.json',$stream,hash('sha256',$bytes));
        rb_tp_state($theme,true);
        return true;
    } finally { sql_query('SELECT RELEASE_LOCK('.rb_tp_q($lock).')',false); }
}
function rb_tp_query($sql)
{
    $result = sql_query($sql, false);
    if (!$result) throw new RuntimeException('테마 자료 처리에 실패했습니다. DB 구조와 권한을 확인해 주세요.');
    return $result;
}
function rb_tp_rows($sql)
{
    $out = array(); $result = rb_tp_query($sql);
    while ($row = sql_fetch_array($result)) $out[] = $row;
    return $out;
}
function rb_tp_q($s) { return $s===null ? 'NULL' : "'".sql_real_escape_string((string)$s)."'"; }
function rb_tp_board_display_fields()
{
    return array('bo_gallery_cols','bo_gallery_width','bo_gallery_height','bo_mobile_gallery_width','bo_mobile_gallery_height',
        'bo_image_width','bo_subject_len','bo_mobile_subject_len','bo_page_rows','bo_mobile_page_rows','bo_table_width','bo_new','bo_hot',
        'bo_use_list_view','bo_use_list_content','bo_use_sideview','bo_use_good','bo_use_nogood','bo_use_signature');
}
function rb_tp_shop_backup_write($file, $bytes)
{
    $dir=dirname($file);
    if (!is_dir($dir) && !@mkdir($dir,0755,true) && !is_dir($dir))
        throw new RuntimeException('테마 적용 전 쇼핑몰 스킨 설정을 보관할 폴더를 만들 수 없습니다.');
    $tmp=tempnam($dir,'.theme-shop-');
    if ($tmp===false) throw new RuntimeException('쇼핑몰 스킨 설정 보관 공간을 확인해 주세요.');
    try {
        if (file_put_contents($tmp,$bytes)!==strlen($bytes) || !rename($tmp,$file))
            throw new RuntimeException('쇼핑몰 스킨 설정을 보관하지 못했습니다. 테마는 변경하지 않았습니다.');
    } finally { if (is_file($tmp)) unlink($tmp); }
}
// 관리자 테마 적용/해제에서만 호출한다. 코어의 shop.config.php는 기존 DB 설정을 그대로 읽는다.
function rb_tp_apply_theme($theme)
{
    global $g5;
    if (!is_string($theme) || ($theme!=='' && (!rb_tp_path($theme) || strpos($theme,'/')!==false
        || !is_dir(G5_PATH.'/theme/'.$theme) || !rb_tp_under(G5_PATH.'/theme/'.$theme,G5_PATH.'/theme'))))
        throw new RuntimeException('테마 폴더명을 확인해 주세요.');
    $state=rb_tp_state($theme);
    $file=G5_DATA_PATH.'/rb.backup/theme-shop.json';
    // 패키지 스킨을 쓰지 않은 기존 테마와 쇼핑몰 미사용 사이트는 종전 처리만 실행한다.
    if (!defined('G5_USE_SHOP') || !G5_USE_SHOP || empty($g5['g5_shop_default_table'])
        || (empty($state['skins']['shop']) && !is_file($file))) {
        rb_tp_query("UPDATE `{$g5['config_table']}` SET cf_theme=".rb_tp_q($theme));
        return;
    }
    $lock='rb_theme_apply_'.substr(hash('sha256',G5_PATH),0,32);
    $locked=rb_tp_rows('SELECT GET_LOCK('.rb_tp_q($lock).',10) AS acquired');
    if (empty($locked[0]['acquired'])) throw new RuntimeException('다른 테마 적용이 진행 중입니다. 잠시 후 다시 시도해 주세요.');
    $before=null; $backupChanged=false; $shopChanged=false; $restore=array();
    try {
        if (defined('G5_USE_SHOP') && G5_USE_SHOP && !empty($g5['g5_shop_default_table'])) {
            $keys=array('de_shop_skin','de_shop_mobile_skin');
            $rows=rb_tp_rows("SELECT de_shop_skin,de_shop_mobile_skin FROM `{$g5['g5_shop_default_table']}` LIMIT 1");
            if (!$rows) throw new RuntimeException('쇼핑몰 스킨 설정을 찾을 수 없습니다.');
            $current=$rows[0]; $baseline=$current; $saved=array(); $overrides=array();
            if (is_file($file)) {
                $before=file_get_contents($file); $saved=json_decode($before,true);
                if (!is_array($saved) || !isset($saved['format'],$saved['original'],$saved['applied'])
                    || $saved['format']!=='rebuilder-shop-skins' || !is_array($saved['original']) || !is_array($saved['applied']))
                    throw new RuntimeException('보관된 쇼핑몰 스킨 설정을 읽을 수 없어 테마 변경을 중단했습니다.');
                foreach ($saved['original'] as $key=>$value) {
                    if (!in_array($key,$keys,true) || !is_string($value) || !isset($saved['applied'][$key]) || !is_string($saved['applied'][$key]))
                        throw new RuntimeException('보관된 쇼핑몰 스킨 설정을 확인해 주세요.');
                    // 테마 사용 중 관리자가 따로 저장한 설정은 이전 값으로 덮어쓰지 않는다.
                    if ($current[$key]===$saved['applied'][$key]) $baseline[$key]=$value;
                }
            }
            foreach ($keys as $key) if (!empty($state['skins']['shop'][$key])) {
                $skin=$state['skins']['shop'][$key];
                if (!is_string($skin) || strpos($skin,'theme/')!==0 || !rb_tp_path(substr($skin,6)))
                    throw new RuntimeException('테마의 쇼핑몰 스킨 경로를 확인해 주세요.');
                $root=G5_PATH.'/theme/'.$theme;
                $path=$root.'/'.($key==='de_shop_mobile_skin'?'mobile/':'').'skin/shop/'.substr($skin,6);
                if (!is_dir($path) || !rb_tp_under($path,$root)) throw new RuntimeException('테마의 쇼핑몰 스킨 폴더가 없습니다: '.$skin);
                $overrides[$key]=$skin;
            }
            $next=array_merge($baseline,$overrides); $updates=array();
            foreach ($keys as $key) if ($current[$key]!==$next[$key]) {
                $updates[]='`'.$key.'`='.rb_tp_q($next[$key]);
                $restore[]='`'.$key.'`='.rb_tp_q($current[$key]);
            }
            if ($overrides || $before!==null) {
                $backup=array('format'=>'rebuilder-shop-skins','theme'=>$theme,
                    'original'=>array_intersect_key($baseline,$overrides),'applied'=>$overrides);
                $bytes=rb_tp_json($backup);
                if ($bytes!==$before) { rb_tp_shop_backup_write($file,$bytes); $backupChanged=true; }
            }
            if ($updates) {
                rb_tp_query("UPDATE `{$g5['g5_shop_default_table']}` SET ".implode(',',$updates));
                $shopChanged=true;
            }
        }
        rb_tp_query("UPDATE `{$g5['config_table']}` SET cf_theme=".rb_tp_q($theme));
    } catch (Throwable $e) {
        // MyISAM을 사용하는 구버전도 설정 변경 실패 시 이전 값으로 되돌린다.
        if ($shopChanged && !sql_query("UPDATE `{$g5['g5_shop_default_table']}` SET ".implode(',',$restore),false))
            throw new RuntimeException('테마 적용 실패 후 쇼핑몰 스킨 복구에 실패했습니다. 보관된 설정을 확인해 주세요.',0,$e);
        if ($backupChanged) {
            if ($before!==null) rb_tp_shop_backup_write($file,$before);
            elseif (!unlink($file)) throw new RuntimeException('테마 적용 실패 후 스킨 설정 보관 파일을 정리하지 못했습니다.',0,$e);
        }
        throw $e;
    } finally { sql_query('SELECT RELEASE_LOCK('.rb_tp_q($lock).')',false); }
}
function rb_tp_json($value)
{
    $s = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($s === false) throw new RuntimeException('설정의 문자 인코딩을 확인해 주세요.');
    return $s;
}
function rb_tp_archive_support()
{
    $disabled=array_map('trim',explode(',',strtolower((string)ini_get('disable_classes'))));
    return array('zip'=>class_exists('ZipArchive') && !in_array('ziparchive',$disabled,true),
        'phar'=>class_exists('PharData') && !in_array('phardata',$disabled,true));
}
function rb_tp_slug($s)
{
    return is_string($s) && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,39}\z/D', $s) && strpos($s, '..') === false;
}
function rb_tp_path($s)
{
    if (!is_string($s) || $s === '' || strlen($s) > 220 || preg_match('~[\\\\:\x00-\x1f\x7f]~', $s) || $s[0] === '/') return false;
    foreach (explode('/', $s) as $part) {
        if ($part === '' || $part === '.' || $part === '..' || preg_match('/[. ]$/', $part)
            || preg_match('/\A(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|$)/i', $part)) return false;
    }
    return true;
}
function rb_tp_under($file, $root)
{
    $p = realpath($file); $r = realpath($root);
    return $p !== false && $r !== false && !is_link($file)
        && strpos(str_replace('\\', '/', $p), rtrim(str_replace('\\', '/', $r), '/').'/') === 0;
}
function rb_tp_text_file($path)
{
    return preg_match('/\.(php|css|js|mjs|json|webmanifest|html?|txt|svg|xml)$/i', $path);
}
function rb_tp_walk_values($value, $callback)
{
    if (is_array($value)) {
        foreach ($value as $k=>$v) $value[$k] = rb_tp_walk_values($v, $callback);
        return $value;
    }
    return is_string($value) ? call_user_func($callback, $value) : $value;
}
function rb_tp_tree(&$files, $dir, $prefix, $exclude = array())
{
    if (!is_dir($dir)) throw new RuntimeException('필요한 폴더가 없습니다: '.$prefix);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir)+1));
        foreach ($exclude as $excluded) if ($rel===$excluded || strpos($rel,$excluded.'/')===0) continue 2;
        if ($file->isLink()) throw new RuntimeException('심볼릭 링크는 테마 패키지에 포함할 수 없습니다.');
        if (!$file->isFile()) continue;
        if (preg_match('~(^|/)(\.git|node_modules|tests|\.env|rb-package)(/|$)~', $rel)) continue;
        if (!rb_tp_path($rel)) throw new RuntimeException('배포할 수 없는 파일 경로입니다: '.$rel);
        if (!preg_match('/\.(php|css|scss|sass|less|js|mjs|json|webmanifest|html?|txt|md|svg|xml|png|jpe?g|gif|webp|ico|avif|woff2?|ttf|otf|eot|mp4|webm|mp3|wav|ogg|pdf|map)$/i', $rel)
            && !preg_match('~(^|/)(LICENSE|COPYING|README|\.htaccess)$~i',$rel)) continue;
        if ($prefix === 'theme' && (preg_match('/^[^\/]+_\d{8}_\d{6}\.json$/', $rel) || $rel === 'rb-package.json')) continue;
        $files[$prefix.'/'.$rel] = $file->getPathname();
    }
}
function rb_tp_dependency(&$files, &$deps, $kind, $source)
{
    if (isset($deps[$kind][$source])) return $deps[$kind][$source];
    if (!rb_tp_path($source)) throw new RuntimeException('잘못된 의존 파일 경로입니다.');
    $key = substr(hash('sha256', $source), 0, 16);
    rb_tp_tree($files, G5_PATH.'/'.$source, 'deps/'.$kind.'/'.$key);
    $deps[$kind][$source] = $key;
    return $key;
}
function rb_tp_dependency_name($kind,$source,$theme,$installed)
{
    if (isset($installed['dependencies'][$kind][$source]['name'])) return $installed['dependencies'][$kind][$source]['name'];
    // 이전 설치본의 원래 이름도 남아 있는 설치 자료로 확인한다. 이름만 보고 접두사를 추측해 제거하지 않는다.
    $manifest=G5_PATH.'/theme/'.$theme.'/rb-package/manifest.json';
    if ($installed && is_file($manifest) && filesize($manifest)<=8*1024*1024) {
        $old=json_decode(file_get_contents($manifest),true);
        if (isset($old['deps'][$kind]) && is_array($old['deps'][$kind])) foreach($old['deps'][$kind] as $path=>$key) {
            $legacy=substr($theme,0,24).'__'.substr(basename($path),0,32);
            if (preg_match('/\A'.preg_quote($legacy,'/').'(?:_(?:[2-9]|[1-9][0-9]+))?\z/',basename($source)))
                return isset($old['dependency_names'][$kind][$key])?$old['dependency_names'][$kind][$key]:basename($path);
        }
    }
    return basename($source);
}
function rb_tp_dependency_path($kind,$theme,$name)
{
    $roots=array('widget'=>'rb/rb.widget/','banner'=>'rb/rb.mod/banner/skin/');
    if (!isset($roots[$kind])) throw new RuntimeException('의존 파일 종류를 확인해 주세요.');
    $label=$kind==='widget'?'위젯':'배너 스킨';
    if (!is_string($name) || !preg_match('/\A[A-Za-z0-9_.-]+\z/',$name) || !rb_tp_path($name))
        throw new RuntimeException($label.'의 원래 폴더명을 확인해 주세요.');
    $path=$roots[$kind].$theme.'/'.$name;
    if (!rb_tp_path($path))
        throw new RuntimeException($label.'의 원래 폴더명을 확인해 주세요.');
    return $path;
}
function rb_tp_dependency_valid($kind,$path)
{
    if(!is_string($path) || !rb_tp_path($path)) return false;
    if($kind==='widget') return (bool)preg_match('~\Arb/rb\.widget/[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)?\z~',$path);
    return $kind==='banner' && preg_match('~\Arb/rb\.mod/banner/skin/[A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)?\z~',$path);
}
function rb_tp_skin_devices()
{
    // 접속한 관리자 기기가 아니라 common.php의 테마/사이트 사용기기 설정을 따른다.
    foreach (array('G5_THEME_DEVICE','G5_SET_DEVICE') as $key) {
        $device=defined($key)?constant($key):'';
        if ($device==='pc' || $device==='mobile') return array('pc'=>$device==='pc','mobile'=>$device==='mobile');
    }
    return array('pc'=>true,'mobile'=>!defined('G5_USE_MOBILE') || G5_USE_MOBILE);
}
function rb_tp_skin(&$files, $theme, $kind, $skin, $mobile = false, &$paths = null, &$sources = null)
{
    $skin=trim($skin);
    if ($skin === '') return '';
    $base = $mobile ? 'mobile/' : '';
    $label=($mobile?'모바일':'PC').' '.$kind.' 스킨';
    if (!rb_tp_path($skin)) throw new RuntimeException($label.' 설정 경로가 올바르지 않습니다: '.$skin);
    if (strpos($skin, 'theme/') === 0) {
        $rel = substr($skin, 6);
        $dir = G5_PATH.'/theme/'.$theme.'/'.$base.'skin/'.$kind.'/'.$rel;
        if (!is_dir($dir) && $mobile && is_dir(G5_PATH.'/theme/'.$theme.'/skin/'.$kind.'/'.$rel))
            $dir = G5_PATH.'/theme/'.$theme.'/skin/'.$kind.'/'.$rel;
    } else {
        $rel = $skin;
        $dir = G5_PATH.'/'.$base.'skin/'.$kind.'/'.$skin;
    }
    if (!is_dir($dir)) {
        throw new RuntimeException('사용 중인 '.$label.' 폴더가 없습니다: /'.substr($dir,strlen(G5_PATH)+1).' (설정: '.$skin.')');
    }
    if (!rb_tp_path($rel) || !rb_tp_under($dir, G5_PATH)) throw new RuntimeException($label.' 경로를 확인해 주세요: '.$skin);
    $prefix='theme/'.$base.'skin/'.$kind.'/'.$rel;
    $skinFiles=array(); rb_tp_tree($skinFiles,$dir,$prefix);
    if (is_array($sources) && isset($sources[$prefix]) && $sources[$prefix]!==realpath($dir)) {
        $previous=array(); foreach($files as $entry=>$path) if(strpos($entry,$prefix.'/')===0) $previous[$entry]=hash_file('sha256',$path);
        $current=array(); foreach($skinFiles as $entry=>$path) $current[$entry]=hash_file('sha256',$path);
        ksort($previous); ksort($current);
        if ($previous!==$current) throw new RuntimeException('서로 다른 공용/테마 '.$kind.' 스킨이 같은 이름('.$rel.')으로 사용 중입니다. 사용할 스킨을 하나로 통일해 주세요.');
    }
    // 사용하지 않는 동명 스킨의 파일과 섞이지 않도록 선택된 폴더 전체를 기준으로 저장한다.
    foreach(array_keys($files) as $entry) if(strpos($entry,$prefix.'/')===0) unset($files[$entry]);
    $files=array_merge($files,$skinFiles);
    if (is_array($sources)) $sources[$prefix]=realpath($dir);
    if(is_array($paths) && strpos($skin,'theme/')!==0) $paths[$base.'skin/'.$kind.'/'.$skin.'/']=$base.'skin/'.$kind.'/'.$rel.'/';
    return 'theme/'.$rel;
}
function rb_tp_tab_list($json)
{
    // 빌더의 드래그 정렬은 숫자 ID를 JSON 숫자로 저장할 수 있다. 문자열 ID의 앞자리 0은 유지한다.
    $tabs=is_string($json)?json_decode($json,false,512,JSON_BIGINT_AS_STRING):null;
    if (!is_array($tabs)) throw new RuntimeException('탭 모듈 설정이 올바르지 않습니다.');
    foreach ($tabs as &$tab) {
        if (!is_string($tab) && !is_int($tab)) throw new RuntimeException('탭 항목 형식이 올바르지 않습니다.');
        $tab=(string)$tab;
    }
    unset($tab);
    return $tabs;
}
function rb_tp_main_data($data)
{
    // 서브페이지 배치와 그 하위 중첩 배치는 같은 접두어를 사용한다.
    // 메인의 숫자 배치 번호와 사용자 정의 메인 배치 이름은 그대로 유지한다.
    foreach (array('rb_module'=>'md','rb_module_shop'=>'md','rb_section'=>'sec','rb_section_shop'=>'sec') as $table=>$prefix) {
        $data[$table]=array_values(array_filter($data[$table],function($row) use($prefix) {
            $layout=(string)$row[$prefix.'_layout'];
            return !preg_match('~\A(?:rb_(?:bo|co|fr|ca|it|ev)_(?:top|btm)_|rb_gr_|rb_sidemenu(?:_shop)?(?:-|\z))~',$layout);
        }));
    }
    return $data;
}
function rb_tp_main_settings($data)
{
    // 테마 공통 설정 중 서브페이지 전용 값은 배포하지 않는다.
    foreach($data['rb_config'] as &$row) {
        foreach(array_keys($row) as $key)
            if(preg_match('~\Aco_(?:sub(?:_|$)|side(?:menu|_skin)(?:_|$)|padding_(?:top|btm)_sub(?:_|$)|topvisual(?:_|$))~',$key)) unset($row[$key]);
    }
    unset($row);
    // 메인 캐러셀 이미지/문구는 유지하되 서브페이지 공통 배경 지정은 제외한다.
    foreach($data['rb_theme_carousel'] as &$row) unset($row['is_sub']);
    unset($row);
    return $data;
}
function rb_tp_refs($data)
{
    $refs = array('board'=>array(), 'content'=>array(), 'form'=>array(), 'poll'=>array(), 'category'=>array(), 'group'=>array(), 'item'=>array(), 'event'=>array());
    foreach (array('rb_module','rb_module_shop','rb_section','rb_section_shop') as $t) {
        $shop=substr($t,-5)==='_shop';
        foreach ($data[$t] as $r) {
            $layout = isset($r['md_layout']) ? $r['md_layout'] : $r['sec_layout'];
            if (preg_match('/^rb_(bo|co|fr|ca|it|ev)_(?:top|btm)_'.($shop?'(?:shop_)?':'').'([a-zA-Z0-9_]+)/', $layout, $m)) {
                $kind = array('bo'=>'board','co'=>'content','fr'=>'form','ca'=>'category','it'=>'item','ev'=>'event'); $refs[$kind[$m[1]]][$m[2]] = true;
            }
            if(preg_match('/^rb_gr_([A-Za-z0-9_]+)/',$layout,$m)) $refs['group'][$m[1]]=true;
            if (!empty($r['md_bo_table'])) $refs['board'][$r['md_bo_table']] = true;
            if (!empty($r['md_poll_id'])) $refs['poll'][$r['md_poll_id']] = true;
            if (isset($r['md_type']) && $r['md_type']==='item' && !empty($r['md_sca'])) $refs['category'][$r['md_sca']] = true;
            foreach (array('md_tab_list'=>'board','md_item_tab_list'=>'category') as $field=>$kind) {
                if (empty($r[$field])) continue;
                $tabs = rb_tp_tab_list($r[$field]);
                foreach ($tabs as $tab) {
                    $parts = explode('||', $tab, 2);
                    if ($parts[0] !== '') $refs[$kind][$parts[0]] = true;
                }
            }
        }
    }
    return $refs;
}
function rb_tp_install_version_message($version=null)
{
    if($version===null) $version=defined('RB_VER')?(string)RB_VER:'';
    if(is_string($version) && preg_match('/\A[0-9]+(?:\.[0-9]+){2,3}\z/',$version) && version_compare($version,'2.2.7.6','>=')) return '';
    return '현재 빌더의 버전이 '.($version!==''?$version:'확인되지 않는 상태').' 입니다.'."\n".'테마 설치는 2.2.7.6 버전부터 가능합니다.';
}
function rb_tp_shop_enabled()
{
    return defined('G5_USE_SHOP') && G5_USE_SHOP;
}
function rb_tp_board_categories()
{
    global $g5;
    $out=array();
    foreach(rb_tp_rows("SELECT bo_table,bo_use_category,bo_category_list FROM `{$g5['board_table']}`") as $row) {
        $out[$row['bo_table']]=empty($row['bo_use_category'])?array():array_values(array_filter(explode('|',$row['bo_category_list']),'strlen'));
    }
    return $out;
}
function rb_tp_module_connections($data)
{
    // 같은 원본 게시판을 사용하더라도 모듈 번호와 탭 위치별로 독립적인 연결을 만든다.
    $main=rb_tp_main_data($data); $out=array();
    $types=array('latest'=>'최신글(단일)','tab'=>'최신글(탭)','poll'=>'설문','item'=>'상품(단일)','item_tab'=>'상품(탭)');
    foreach(array('rb_module'=>'일반 메인','rb_module_shop'=>'쇼핑몰 메인') as $table=>$area) foreach($main[$table] as $row) {
        $type=$row['md_type']; if(!isset($types[$type])) continue;
        $key=$table.':'.$row['md_id']; $slots=array();
        if($type==='tab' || $type==='item_tab') {
            $field=$type==='tab'?'md_tab_list':'md_item_tab_list';
            foreach(!empty($row[$field])?rb_tp_tab_list($row[$field]):array() as $index=>$tab) {
                $parts=explode('||',$tab,2);
                $slots[]=array('key'=>$key.':'.$index,'kind'=>$type==='tab'?'board':'category','tab'=>$index+1,
                    'source'=>$parts[0],'category'=>isset($parts[1])?$parts[1]:'');
            }
        } else {
            $field=$type==='latest'?'md_bo_table':($type==='poll'?'md_poll_id':'md_sca');
            $slots[]=array('key'=>$key.':0','kind'=>$type==='latest'?'board':($type==='poll'?'poll':'category'),'tab'=>0,
                'source'=>isset($row[$field])?(string)$row[$field]:'','category'=>$type==='latest' && isset($row['md_sca'])?$row['md_sca']:'');
        }
        $out[]=array('key'=>$key,'table'=>$table,'id'=>(string)$row['md_id'],'area'=>$area,'type'=>$types[$type],'module_type'=>$type,
            'title'=>isset($row['md_title'])?trim(strip_tags($row['md_title'])):'','slots'=>$slots,
            'available'=>rb_tp_shop_enabled() || ($table!=='rb_module_shop' && $type!=='item' && $type!=='item_tab'));
    }
    return $out;
}
function rb_tp_layout_preview($data)
{
    // 설치 전에는 테마 PHP를 실행하지 않고 저장된 배치 정보로만 구성도를 만든다.
    $data=rb_tp_main_data($data); $areas=array();
    $config=isset($data['rb_config'][0])?$data['rb_config'][0]:array();
    $types=array('latest'=>'최신글(단일)','tab'=>'최신글(탭)','poll'=>'설문','item'=>'상품(단일)',
        'item_tab'=>'상품(탭)','widget'=>'위젯','banner'=>'배너');
    foreach(array('rb_module'=>'일반 메인','rb_module_shop'=>'쇼핑몰 메인') as $table=>$title) {
        $shop=$table==='rb_module_shop';
        if($shop && !rb_tp_shop_enabled()) continue;
        $sectionTable=$shop?'rb_section_shop':'rb_section'; $groups=array();
        foreach(array($table=>'md',$sectionTable=>'sec') as $source=>$prefix) foreach($data[$source] as $row) {
            $name=isset($row[$prefix.'_layout_name'])?(string)$row[$prefix.'_layout_name']:'';
            $layout=(string)$row[$prefix.'_layout'];
            $groups[$name][$layout][$prefix][]=$row;
        }
        $selected=isset($config[$shop?'co_layout_shop':'co_layout'])?(string)$config[$shop?'co_layout_shop':'co_layout']:'';
        uksort($groups,function($a,$b) use($selected) {
            if((string)$a===$selected) return (string)$b===$selected?0:-1;
            if((string)$b===$selected) return 1;
            return strnatcmp((string)$a,(string)$b);
        });
        $layouts=array();
        foreach($groups as $name=>$positions) {
            $owners=array();
            foreach($positions as $layout=>$rows) foreach(isset($rows['md'])?$rows['md']:array() as $row)
                $owners[$layout.'-'.$row['md_id']]=true;
            $build=function($layout,$depth=0) use(&$build,$positions,$table,$types) {
                if($depth>30 || !isset($positions[$layout])) return array();
                $rows=$positions[$layout]; $items=array(); $sections=array(); $inside=array();
                foreach(isset($rows['sec'])?$rows['sec']:array() as $row) {
                    $uid=isset($row['sec_uid'])?(string)$row['sec_uid']:'';
                    if($uid!=='') $sections[$uid]=true;
                }
                foreach(isset($rows['md'])?$rows['md']:array() as $row) {
                    $type=isset($row['md_type'])?(string)$row['md_type']:'';
                    $unit=isset($row['md_size']) && $row['md_size']==='px'?'px':'%';
                    $width=isset($row['md_width']) && is_numeric($row['md_width']) && $row['md_width']>0?(float)$row['md_width']:100;
                    $node=array('kind'=>'module','key'=>$table.':'.$row['md_id'],'id'=>(string)$row['md_id'],
                        'title'=>isset($row['md_title'])?trim(strip_tags($row['md_title'])):'',
                        'type'=>isset($types[$type])?$types[$type]:'모듈','module_type'=>$type,
                        'order'=>isset($row['md_order_id'])?(int)$row['md_order_id']:0,
                        'width'=>min($width,$unit==='px'?10000:100),'unit'=>$unit,
                        'children'=>$build($layout.'-'.$row['md_id'],$depth+1));
                    $uid=isset($row['md_sec_uid'])?(string)$row['md_sec_uid']:'';
                    if($uid!=='' && isset($sections[$uid])) $inside[$uid][]=$node;
                    else $items[]=$node;
                }
                $sort=function($a,$b) {
                    return $a['order']===$b['order']?((int)$a['id']<=>(int)$b['id']):($a['order']<=>$b['order']);
                };
                foreach(isset($rows['sec'])?$rows['sec']:array() as $row) {
                    $uid=isset($row['sec_uid'])?(string)$row['sec_uid']:'';
                    // 같은 UID의 섹션이 남아 있어도 모듈 연결 입력은 한 번만 표시한다.
                    $children=isset($inside[$uid])?$inside[$uid]:array(); unset($inside[$uid]); usort($children,$sort);
                    $items[]=array('kind'=>'section','id'=>(string)$row['sec_id'],
                        'title'=>isset($row['sec_title'])?trim(strip_tags($row['sec_title'])):'',
                        'order'=>isset($row['sec_order_id'])?(int)$row['sec_order_id']:0,
                        'width'=>100,'unit'=>'%','children'=>$children);
                }
                usort($items,$sort); return $items;
            };
            uksort($positions,'strnatcmp');
            foreach($positions as $layout=>$unused) {
                if(isset($owners[$layout])) continue;
                $layouts[]=array('name'=>(string)$name,'position'=>(string)$layout,
                    'active'=>(string)$name===$selected,'nodes'=>$build($layout));
            }
        }
        if($layouts) $areas[]=array('title'=>$title,'shop'=>$shop,'layouts'=>$layouts,
            'width'=>isset($config['co_main_width']) && is_numeric($config['co_main_width']) && $config['co_main_width']>0
                ?max(320,min(4000,(float)$config['co_main_width'])):1280);
    }
    return $areas;
}
function rb_tp_module_mappings($data,$input)
{
    if(!is_array($input)) throw new RuntimeException('모듈 연결 정보 형식이 올바르지 않습니다.');
    $out=array(); $known=array(); $catalogs=array(); $categories=rb_tp_board_categories();
    foreach(rb_tp_module_connections($data) as $module) {
        $type=$module['module_type']; $row=array(); $tabs=array();
        foreach($module['slots'] as $slot) {
            $key=$slot['key']; $known[$key]=true;
            $selection=isset($input[$key])?$input[$key]:array();
            if(!is_array($selection) || (isset($selection['target']) && !is_string($selection['target']))
                || (isset($selection['category']) && !is_string($selection['category'])))
                throw new RuntimeException('모듈 연결 정보 형식이 올바르지 않습니다.');
            $target=isset($selection['target'])?$selection['target']:''; $category=isset($selection['category'])?$selection['category']:'';
            if(!$module['available'] && ($target!=='' || $category!=='')) throw new RuntimeException('쇼핑몰 미사용 사이트에서는 쇼핑몰 모듈을 연결할 수 없습니다.');
            if($target!=='') {
                if(!isset($catalogs[$slot['kind']])) $catalogs[$slot['kind']]=rb_tp_catalog($slot['kind']);
                if(!isset($catalogs[$slot['kind']][$target])) throw new RuntimeException('모듈에 연결할 항목이 없거나 변경되었습니다. 설치 내용을 다시 확인해 주세요.');
            }
            if($category!=='' && ($slot['kind']!=='board' || $target==='' || !isset($categories[$target]) || !in_array($category,$categories[$target],true)))
                throw new RuntimeException('선택한 게시판의 카테고리를 확인해 주세요.');
            if($type==='tab' || $type==='item_tab') {
                if($target!=='') $tabs[]=$target.($category!==''?'||'.$category:'');
            } elseif($type==='latest') { $row['md_bo_table']=$target; $row['md_sca']=$category; }
            elseif($type==='poll') $row['md_poll_id']=$target!==''?$target:'0';
            else $row['md_sca']=$target;
        }
        if($type==='tab' || $type==='item_tab') $row[$type==='tab'?'md_tab_list':'md_item_tab_list']=rb_tp_json($tabs);
        $out[$module['table']][$module['id']]=$row;
    }
    if(array_diff_key($input,$known)) throw new RuntimeException('설치 자료에 없는 모듈 연결입니다. 설치 내용을 다시 확인해 주세요.');
    return $out;
}
function rb_tp_reference_modules($data)
{
    // 설치 자료에 있는 모듈에서 계산하므로 이미 내보낸 ZIP도 사용 위치를 표시할 수 있다.
    $main=rb_tp_main_data($data); $out=array();
    $types=array('latest'=>'최신글(단일)','tab'=>'최신글(탭)','poll'=>'설문','item'=>'상품(단일)','item_tab'=>'상품(탭)');
    foreach(array('rb_module'=>'일반 메인','rb_module_shop'=>'쇼핑몰 메인') as $table=>$area) {
        foreach($main[$table] as $row) {
            $single=array('rb_module'=>array(),'rb_module_shop'=>array(),'rb_section'=>array(),'rb_section_shop'=>array());
            $single[$table]=array($row);
            $module=array('area'=>$area,'type'=>isset($types[$row['md_type']])?$types[$row['md_type']]:'모듈',
                'title'=>isset($row['md_title'])?trim(strip_tags($row['md_title'])):'','id'=>(string)$row['md_id']);
            $tabPositions=array();
            $tabField=$row['md_type']==='tab'?'md_tab_list':($row['md_type']==='item_tab'?'md_item_tab_list':'');
            if($tabField!=='' && !empty($row[$tabField])) foreach(rb_tp_tab_list($row[$tabField]) as $index=>$tab) {
                $parts=explode('||',$tab,2); $tabPositions[$parts[0]][]=$index+1;
            }
            foreach(rb_tp_refs($single) as $kind=>$refs) foreach($refs as $source=>$unused) {
                $use=$module; $use['tabs']=isset($tabPositions[$source])?$tabPositions[$source]:array();
                $out[$kind][$source][]=$use;
            }
        }
    }
    return $out;
}
function rb_tp_catalog($kind)
{
    global $g5;
    if(in_array($kind,array('category','item','event'),true) && !rb_tp_shop_enabled()) return array();
    $defs = array('board'=>array($g5['board_table'],'bo_table','bo_subject'),
        'content'=>array($g5['content_table'],'co_id','co_subject'),
        'poll'=>array($g5['poll_table'],'po_id','po_subject'));
    if (!empty($g5['g5_shop_category_table'])) $defs['category'] = array($g5['g5_shop_category_table'],'ca_id','ca_name');
    if (!empty($g5['group_table'])) $defs['group'] = array($g5['group_table'],'gr_id','gr_subject');
    if (!empty($g5['g5_shop_item_table'])) $defs['item'] = array($g5['g5_shop_item_table'],'it_id','it_name');
    if (!empty($g5['g5_shop_event_table'])) $defs['event'] = array($g5['g5_shop_event_table'],'ev_id','ev_subject');
    $defs['form'] = array('rb_form','fr_id','fr_subject');
    if (!isset($defs[$kind])) return array();
    list($table,$id,$title) = $defs[$kind];
    $res = sql_query("SELECT `$id` AS id, `$title` AS title FROM `$table`", false);
    $out = array(); if ($res) while ($r = sql_fetch_array($res)) $out[(string)$r['id']] = $r['title'];
    return $out;
}
function rb_tp_asset(&$files, &$manifest, $rel, $depth=0)
{
    if(isset($manifest['assets'][$rel])) return;
    if($depth>20 || count($manifest['assets'])>20000) throw new RuntimeException('이미지/CSS 참조가 너무 많습니다.');
    if(!rb_tp_path($rel) || !preg_match('/\.(png|jpe?g|gif|webp|svg|ico|avif|woff2?|ttf|otf|eot|css|mp4|webm|mp3|wav|ogg)$/i',$rel)) return;
    $path=G5_DATA_PATH.'/'.$rel;
    if(!is_file($path) || !rb_tp_under($path,G5_DATA_PATH)) throw new RuntimeException('사용 중인 이미지/자료가 없습니다: '.$rel);
    $entry='assets/'.substr(hash('sha256',$rel),0,16).'/'.basename($rel);
    $files[$entry]=$path; $manifest['assets'][$rel]=$entry;
    if(strtolower(pathinfo($path,PATHINFO_EXTENSION))!=='css') return;
    if(filesize($path)>8*1024*1024) throw new RuntimeException('CSS 파일은 8MB 이내여야 합니다.');
    preg_match_all('~url\(\s*["\']?([^\s"\')]+)|@import\s+["\']([^"\']+)~i',file_get_contents($path),$matches,PREG_SET_ORDER);
    foreach($matches as $match) {
        $url=!empty($match[1])?$match[1]:$match[2];
        $resolve=$url;
        if(strpos($resolve,G5_DATA_URL.'/')===0) $resolve=parse_url(G5_DATA_URL,PHP_URL_PATH).substr($resolve,strlen(G5_DATA_URL));
        if(preg_match('~^(?:[a-z]+:|//|\#)~i',$resolve)) continue;
        $suffix=''; $raw=preg_split('/(?=[?#])/',$resolve,2); if(isset($raw[1])) $suffix=$raw[1];
        if(substr($raw[0],0,1)==='/') {
            $prefix=rtrim(parse_url(G5_DATA_URL,PHP_URL_PATH),'/').'/';
            if(strpos($raw[0],$prefix)!==0) continue;
            $relative=substr($raw[0],strlen($prefix));
        } else $relative=dirname($rel).'/'.$raw[0];
        $parts=array(); foreach(explode('/',rawurldecode($relative)) as $part) {
            if($part==='.' || $part==='') continue;
            if($part==='..') { if(!$parts) throw new RuntimeException('CSS 자료 경로가 사이트 밖을 가리킵니다.'); array_pop($parts); }
            else $parts[]=$part;
        }
        $linked=implode('/',$parts); rb_tp_asset($files,$manifest,$linked,$depth+1);
        if(isset($manifest['assets'][$linked])) $manifest['asset_links'][$entry][$url]=G5_DATA_URL.'/'.$linked.$suffix;
    }
}
function rb_tp_visual_code($kind,$id)
{
    $prefix=array('board'=>'bo-table-','content'=>'content-','form'=>'form-','group'=>'group-','item'=>'item-','event'=>'event-','category'=>'shop-list-');
    return $prefix[$kind].($kind==='category'?implode('-',str_split((string)$id,2)):$id);
}
function rb_tp_theme_readme($bytes,$name)
{
    $line='Theme Name: '.$name;
    return preg_match('/^[ \t]*Theme Name[ \t]*:/mi',$bytes)
        ? preg_replace('/^[ \t]*Theme Name[ \t]*:[^\r\n]*/mi',$line,$bytes) : $line."\n".$bytes;
}
function rb_tp_export_archive($theme, $name, $zipfile, $identity, $delivery)
{
    global $g5, $config, $default, $rb_builder, $rb_aos, $rb_aos_shop;
    $archiveSupport=rb_tp_archive_support();
    if (!$archiveSupport['zip'] && !$archiveSupport['phar']) throw new RuntimeException('내보내기에는 PHP ZIP 또는 Phar 확장이 필요합니다.');
    if (!rb_tp_slug($theme) || !is_dir(G5_PATH.'/theme/'.$theme)) throw new RuntimeException('테마를 찾을 수 없습니다.');
    $name=is_string($name)?trim($name):'';
    if (!rb_tp_slug($name) || !rb_tp_path($name)) throw new RuntimeException('테마명은 영문·숫자로 시작하는 40자 이내의 폴더명으로 입력해 주세요. 영문·숫자·점·밑줄·하이픈만 사용할 수 있으며 예약된 폴더명은 사용할 수 없습니다.');
    $files = array(); $deps = array('widget'=>array(), 'banner'=>array()); $data = array(); $css = array(); $skinPaths=array(); $skinSources=array();
    $installed=rb_tp_state($theme);
    $devices=rb_tp_skin_devices();
    rb_tp_tree($files, G5_PATH.'/theme/'.$theme, 'theme', $devices['mobile']?array():array('mobile/skin'));
    // 원본 테마는 변경하지 않고 ZIP 안의 표시 이름만 배포할 이름으로 만든다.
    $readme=isset($files['theme/readme.txt'])?$files['theme/readme.txt']:'';
    if ($readme!=='' && filesize($readme)>8*1024*1024) throw new RuntimeException('테마 설명 파일은 8MB 이내여야 합니다.');
    $contents=array('theme/readme.txt'=>rb_tp_theme_readme($readme!==''?file_get_contents($readme):'',$name));
    $files['theme/readme.txt']=$readme;
    foreach (rb_tp_tables() as $table=>$def) $data[$table] = !rb_tp_shop_enabled() && in_array($table,array('rb_module_shop','rb_section_shop'),true)
        ? array() : rb_tp_rows("SELECT * FROM `$table` WHERE `{$def[0]}` = ".rb_tp_q($theme));
    $data=rb_tp_main_settings(rb_tp_main_data($data));
    foreach (array('rb_module','rb_module_shop','rb_section','rb_section_shop') as $table) {
        $module = strpos($table, 'rb_module') === 0; $prefix = $module ? 'md' : 'sec';
        foreach ($data[$table] as &$row) {
            unset($row[$prefix.'_ip']);
            $filename = ($module ? 'mod' : 'sec').(substr($table,-5) === '_shop' ? '_shop' : '').'_'
                .preg_replace('~[^A-Za-z0-9_\-]~','-', $row[$prefix.'_layout']).'_'.$row[$prefix.'_id'].'.css';
            if (is_file(G5_DATA_PATH.'/rb_custom_css/'.$filename)) {
                $entry = 'custom/'.$filename; $files[$entry] = G5_DATA_PATH.'/rb_custom_css/'.$filename;
                $css[] = array('entry'=>$entry,'table'=>$table,'id'=>$row[$prefix.'_id'],'layout'=>$row[$prefix.'_layout']);
            }
            if (!$module) continue;
            foreach(array('md_bo_table'=>'latest','md_tab_list'=>'tab','md_poll_id'=>'poll','md_item_tab_list'=>'item_tab') as $field=>$type)
                if(isset($row[$field]) && $row['md_type']!==$type) $row[$field]=$field==='md_poll_id'?'0':'';
            foreach(array('md_tab_list','md_item_tab_list') as $field)
                if(!empty($row[$field])) $row[$field]=rb_tp_json(rb_tp_tab_list($row[$field]));
            if ($row['md_type'] === 'widget' && !empty($row['md_widget'])) {
                if (!rb_tp_dependency_valid('widget','rb/'.$row['md_widget'])) throw new RuntimeException('위젯 폴더를 확인해 주세요.');
                rb_tp_dependency($files,$deps,'widget','rb/'.$row['md_widget']);
            }
            if ($row['md_type'] === 'banner') {
                $skin = !empty($row['md_banner_skin']) ? $row['md_banner_skin'] : 'rb.mod/banner/skin/rb.banner';
                if(!rb_tp_dependency_valid('banner','rb/'.$skin)) throw new RuntimeException('배너 스킨 폴더를 확인해 주세요.');
                rb_tp_dependency($files,$deps,'banner','rb/'.$skin);
                $row['md_banner_skin'] = $skin;
            }
            foreach (array('md_skin'=>'latest','md_tab_skin'=>'latest_tabs','md_poll'=>'poll') as $field=>$kind) {
                $types=array('md_skin'=>'latest','md_tab_skin'=>'tab','md_poll'=>'poll');
                if ($row['md_type']!==$types[$field]) continue;
                $skin=!empty($row[$field])?$row[$field]:'basic';
                $pcSkin=$devices['pc']?rb_tp_skin($files,$theme,$kind,$skin,false,$skinPaths,$skinSources):'';
                $mobileSkin=$devices['mobile']?rb_tp_skin($files,$theme,$kind,$skin,true,$skinPaths,$skinSources):'';
                $row[$field]=$pcSkin!==''?$pcSkin:$mobileSkin;
            }
        }
        unset($row);
    }
    $refs = rb_tp_refs($data); $referenceTitles = array('board'=>array());
    // 로고/메뉴 버튼/동작 효과 중 화면에 필요한 값만 테마별로 보관한다.
    $display=array();
    foreach(array('bu_mobile_menu_position','bu_mobile_menu_icon','bu_mobile_menu_icon_svg') as $key)
        if(isset($rb_builder[$key])) $display[$key]=$rb_builder[$key];
    if(isset($installed['builder_display'])) $display=array_merge($display,$installed['builder_display']);
    foreach(array('pc','mo') as $device) if(!$installed && !empty($rb_builder['bu_logo_'.$device]) && !empty($rb_builder['bu_logo_'.$device.'_w'])) {
        foreach(array('','_w') as $suffix) {
            $file=G5_DATA_PATH.'/logos/'.$device.$suffix;
            if(!is_file($file)) throw new RuntimeException('설정된 로고 파일이 없습니다: '.$device.$suffix);
            $files['theme/rb.img/logos/'.$device.$suffix.'.png']=$file;
        }
    }
    $aos=array('general'=>is_array($rb_aos)?$rb_aos:array(),'market'=>is_array($rb_aos_shop)?$rb_aos_shop:array());
    if(isset($installed['aos'])) $aos=$installed['aos'];
    $skins=array('config'=>array(),'shop'=>array(),'qa'=>array());
    foreach(array('member','new','search','connect','faq') as $kind) foreach(array(false,true) as $mobile) {
        if (!$devices[$mobile?'mobile':'pc']) continue;
        $key='cf_'.($mobile?'mobile_':'').$kind.'_skin';
        $skin=isset($installed['skins']['config'][$key])?$installed['skins']['config'][$key]:(isset($config[$key])?$config[$key]:'');
        $exportSkin=rb_tp_skin($files,$theme,$kind,$skin,$mobile,$skinPaths,$skinSources);
        if ($exportSkin!=='') $skins['config'][$key]=$exportSkin;
    }
    foreach(array('de_shop_skin'=>false,'de_shop_mobile_skin'=>true) as $key=>$mobile) {
        if(!rb_tp_shop_enabled()) continue;
        if (!$devices[$mobile?'mobile':'pc']) continue;
        $skin=isset($installed['skins']['shop'][$key])?$installed['skins']['shop'][$key]:(isset($default[$key])?$default[$key]:'');
        $exportSkin=rb_tp_skin($files,$theme,'shop',$skin,$mobile,$skinPaths,$skinSources);
        if ($exportSkin!=='') $skins['shop'][$key]=$exportSkin;
    }
    if (function_exists('get_qa_config')) {
        $qa=get_qa_config();
        foreach(array('qa_skin'=>false,'qa_mobile_skin'=>true) as $key=>$mobile) {
            if (!$devices[$mobile?'mobile':'pc']) continue;
            $skin=isset($installed['skins']['qa'][$key])?$installed['skins']['qa'][$key]:(isset($qa[$key])?$qa[$key]:'');
            $exportSkin=rb_tp_skin($files,$theme,'qa',$skin,$mobile,$skinPaths,$skinSources);
            if ($exportSkin!=='') $skins['qa'][$key]=$exportSkin;
        }
    }
    foreach ($refs['board'] as $id=>$unused) {
        $rows = rb_tp_rows("SELECT bo_subject FROM `{$g5['board_table']}` WHERE bo_table=".rb_tp_q($id));
        if (!$rows) throw new RuntimeException('연결된 게시판이 없습니다: '.$id);
        // 메인 최신글의 연결 안내에 쓸 이름만 보관한다. 게시판 설정은 배포하지 않는다.
        $referenceTitles['board'][$id]=$rows[0]['bo_subject'];
    }
    $banners = array();
    foreach (array_merge($data['rb_module'],$data['rb_module_shop']) as $r) {
        if ($r['md_type'] !== 'banner' || empty($r['md_banner'])) continue;
        $where = $r['md_banner'] === '개별출력' ? 'bn_id='.rb_tp_q($r['md_banner_id']) : 'bn_position='.rb_tp_q($r['md_banner']);
        $bannerRows=rb_tp_rows('SELECT * FROM rb_banner WHERE '.$where);
        if($r['md_banner']==='개별출력' && !$bannerRows) throw new RuntimeException('사용 중인 개별 배너가 없습니다.');
        foreach ($bannerRows as $b) {
            $id = $b['bn_id']; $b['bn_hit'] = '0'; $banners[$id] = $b;
            foreach (array('rb_display','banners') as $folder) {
                if (is_file(G5_DATA_PATH.'/'.$folder.'/'.$id)) $files['banner/'.$id.'/'.$folder] = G5_DATA_PATH.'/'.$folder.'/'.$id;
            }
            if (is_file(G5_DATA_PATH.'/rb_display_design/'.$id)) $files['banner/'.$id.'/design_image']=G5_DATA_PATH.'/rb_display_design/'.$id;
            elseif (is_dir(G5_DATA_PATH.'/rb_display_design/'.$id)) rb_tp_tree($files,G5_DATA_PATH.'/rb_display_design/'.$id,'banner/'.$id.'/design');
        }
    }
    foreach ($data['rb_theme_carousel'] as $r) {
        if (empty($r['image_path'])) continue;
        $p = basename($r['image_path']);
        if (!is_file(G5_DATA_PATH.'/'.$theme.'/carousel_img/'.$p)) throw new RuntimeException('캐러셀 이미지가 없습니다: '.$p);
        $files['carousel/'.$p] = G5_DATA_PATH.'/'.$theme.'/carousel_img/'.$p;
    }
    $manifest = array('format'=>'rebuilder-theme','version'=>1,'name'=>mb_substr(trim($name),0,80),'identity'=>$identity,'delivery'=>$delivery,
        'source_theme'=>$theme,'source_url'=>G5_URL,'source_data_url'=>G5_DATA_URL,'scope'=>'main-design',
        'data'=>$data,'boards'=>array(),'refs'=>$refs,'reference_titles'=>$referenceTitles,'deps'=>$deps,'banners'=>array_values($banners),'css'=>$css,'assets'=>array(),'topvisual'=>array(),'skins'=>$skins,'skin_paths'=>$skinPaths,
        'builder_display'=>$display,'aos'=>$aos);
    // DB/HTML/CSS 안에서 사용하는 사이트 내부의 정적 파일만 수집한다.
    $scanAssets=function($scan) use(&$files,&$manifest) {
    $scan = str_replace('\\/', '/', $scan);
    preg_match_all('~(?:'.preg_quote(G5_DATA_URL,'~').'|'.preg_quote(parse_url(G5_DATA_URL,PHP_URL_PATH),'~').')/[^\s"\'<>\\\\)]+~u', $scan, $matches);
    foreach (array_unique($matches[0]) as $url) {
        $path = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'), PHP_URL_PATH);
        $rel = rawurldecode(substr($path, strlen(parse_url(G5_DATA_URL,PHP_URL_PATH))+1));
        rb_tp_asset($files,$manifest,$rel);
    }
    };
    $scanAssets(rb_tp_json($manifest));
    foreach ($files as $entry=>$p) if (rb_tp_text_file($entry)) {
        if((isset($contents[$entry])?strlen($contents[$entry]):filesize($p))>8*1024*1024) throw new RuntimeException('편집 가능한 소스 파일은 8MB 이내여야 합니다: '.$entry);
        $scanAssets(isset($contents[$entry])?$contents[$entry]:file_get_contents($p));
    }
    $manifest['files'] = array(); $total = 0;
    foreach ($files as $entry=>$p) {
        $size = isset($contents[$entry])?strlen($contents[$entry]):filesize($p); $total += $size;
        if ($size > 512*1024*1024 || $total > 2*1024*1024*1024 || count($files)>20000) throw new RuntimeException('패키지는 파일 20,000개, 전체 2GB 이내여야 합니다.');
        $manifest['files'][$entry] = array('size'=>$size,'sha256'=>isset($contents[$entry])?hash('sha256',$contents[$entry]):hash_file('sha256',$p));
    }
    // PC에서 압축을 풀고 이 폴더 자체를 /theme/에 업로드한다. 설치 서버는 ZIP을 읽지 않는다.
    $folder=$name;
    $manifest['version']=2;
    $manifest['package_folder']=$folder;
    $manifest['dependency_paths']=array('widget'=>array(),'banner'=>array());
    $manifest['dependency_names']=array('widget'=>array(),'banner'=>array());
    foreach($deps as $kind=>$items) foreach($items as $old=>$key) {
        $original=rb_tp_dependency_name($kind,$old,$theme,$installed);
        $path=rb_tp_dependency_path($kind,$folder,$original);
        $label=$kind==='widget'?'위젯':'배너 스킨';
        if (in_array(strtolower($path),array_map('strtolower',$manifest['dependency_paths'][$kind]),true))
            throw new RuntimeException('원래 이름이 같은 '.$label.'이 여러 폴더에서 사용 중입니다: '.$original);
        $manifest['dependency_names'][$kind][$key]=$original;
        $manifest['dependency_paths'][$kind][$key]=$path;
    }
    $prefix='theme/'.$folder.'/rb-package/';
    $archivePath=function($entry) use($folder,$prefix,$manifest) {
        if(strpos($entry,'theme/')===0) return 'theme/'.$folder.'/'.substr($entry,6);
        if(preg_match('~^deps/(widget|banner)/([a-f0-9]{16})/(.+)$~',$entry,$parts))
            return $manifest['dependency_paths'][$parts[1]][$parts[2]].'/'.$parts[3];
        return $prefix.$entry;
    };
    if($archiveSupport['zip']) {
        $zip = new ZipArchive();
        if ($zip->open($zipfile,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('ZIP을 만들 수 없습니다.');
        foreach ($files as $entry=>$p) {
            $added=isset($contents[$entry])?$zip->addFromString($archivePath($entry),$contents[$entry]):$zip->addFile($p,$archivePath($entry));
            if (!$added) throw new RuntimeException('파일을 압축할 수 없습니다.');
        }
        $zip->addFromString($prefix.'manifest.json',rb_tp_json($manifest));
        $zip->addFromString($prefix.'.htaccess',"Require all denied\n");
        if (!$zip->close()) throw new RuntimeException('ZIP 저장에 실패했습니다.');
    } else {
        if(is_file($zipfile)) unlink($zipfile);
        $zip=new PharData($zipfile,0,null,Phar::ZIP);
        foreach($files as $entry=>$p) {
            if(isset($contents[$entry])) $zip->addFromString($archivePath($entry),$contents[$entry]);
            else $zip->addFile($p,$archivePath($entry));
        }
        $zip->addFromString($prefix.'manifest.json',rb_tp_json($manifest));
        $zip->addFromString($prefix.'.htaccess',"Require all denied\n");
        unset($zip);
    }
    return $manifest;
}

class RbThemePackageDirectory
{
    public $numFiles=0;
    private $root;
    private $entries=array();
    private $paths=array();
    private $bases=array();
    public function __construct($folder,$updating=false) {
        $this->root=$folder;
        if(!rb_tp_slug(basename($folder))) throw new RuntimeException('업로드 폴더명을 확인해 주세요.');
        $manifest=$folder.'/rb-package/manifest.json';
        if(!is_file($manifest) || !rb_tp_under($manifest,$folder) || filesize($manifest)>8*1024*1024) throw new RuntimeException('테마 설치 정보가 없습니다.');
        $m=json_decode(file_get_contents($manifest),true);
        if(!is_array($m) || !isset($m['version']) || $m['version']!==2) throw new RuntimeException('이전 보관형 패키지입니다. 최신 내보내기로 다시 다운로드해 주세요.');
        if($updating) {
            if(!isset($m['files'],$m['dependency_paths']) || !is_array($m['files']) || count($m['files'])>20000) throw new RuntimeException('업데이트 자료 형식 오류');
            $this->entries[]='manifest.json'; $this->paths['manifest.json']=$manifest; $this->bases['manifest.json']=$folder;
            // FTP 덮어쓰기 후 남은 사용자 파일은 업데이트 목록과 별개로 유지한다.
            foreach($m['files'] as $entry=>$info) {
                if(!rb_tp_path($entry) || $entry==='manifest.json') throw new RuntimeException('업데이트 자료 경로 오류');
                $base=$folder;
                if(strpos($entry,'theme/')===0) $path=$folder.'/'.substr($entry,6);
                elseif(preg_match('~^deps/(widget|banner)/([a-f0-9]{16})/(.+)$~',$entry,$parts)) {
                    $dep=isset($m['dependency_paths'][$parts[1]][$parts[2]])?$m['dependency_paths'][$parts[1]][$parts[2]]:'';
                    if(!rb_tp_dependency_valid($parts[1],$dep)) throw new RuntimeException('위젯/배너 스킨 경로 오류');
                    $base=G5_PATH.'/'.$dep; $path=$base.'/'.$parts[3];
                } else $path=$folder.'/rb-package/'.$entry;
                $this->entries[]=$entry; $this->paths[$entry]=$path; $this->bases[$entry]=$base;
            }
            $this->numFiles=count($this->entries); return;
        }
        $this->scan($folder,'theme/',true);
        if(!isset($m['dependency_paths']) || !is_array($m['dependency_paths'])) throw new RuntimeException('위젯/스킨 경로 정보가 없습니다.');
        foreach(array('widget','banner') as $kind) {
            if(!isset($m['dependency_paths'][$kind]) || !is_array($m['dependency_paths'][$kind])) throw new RuntimeException('위젯/스킨 경로 정보 오류');
            foreach($m['dependency_paths'][$kind] as $key=>$path) {
                if(!preg_match('/\A[a-f0-9]{16}\z/',$key) || !rb_tp_dependency_valid($kind,$path)) throw new RuntimeException('위젯/스킨 경로 오류');
                $this->scan(G5_PATH.'/'.$path,'deps/'.$kind.'/'.$key.'/',false);
            }
        }
        $this->numFiles=count($this->entries);
    }
    private function scan($base,$prefix,$theme) {
        if(!is_dir($base) || !rb_tp_under($base,G5_PATH)) throw new RuntimeException('필요한 폴더를 업로드해 주세요: '.basename($base));
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $f) {
            if($f->isLink()) throw new RuntimeException('심볼릭 링크는 설치할 수 없습니다.');
            if(!$f->isFile()) continue;
            $relative=str_replace('\\','/',substr($f->getPathname(),strlen($base)+1));
            if($theme && $relative==='rb-package/.htaccess') continue;
            $entry=$theme && strpos($relative,'rb-package/')===0?substr($relative,11):$prefix.$relative;
            if(isset($this->paths[$entry]) || count($this->entries)>=20001) throw new RuntimeException('중복 파일 또는 파일 개수 초과');
            $this->entries[]=$entry; $this->paths[$entry]=$f->getPathname(); $this->bases[$entry]=$base;
        }
    }
    public function statIndex($i) { return $this->statName($this->entries[$i]); }
    public function statName($name) {
        if(!rb_tp_path($name)) return false;
        if(!isset($this->paths[$name])) return false;
        $path=$this->path($name); $base=$this->bases[$name];
        if(!rb_tp_under($path,$base) || !is_file($path)) return false;
        return array('name'=>$name,'size'=>filesize($path));
    }
    private function path($name) { return $this->paths[$name]; }
    public function getFromName($name) { return $this->statName($name)?file_get_contents($this->path($name)):false; }
    public function stream($name) { return $this->statName($name)?fopen($this->path($name),'rb'):false; }
    public function checksum($name) { return $this->statName($name)?hash_file('sha256',$this->path($name)):false; }
    public function getExternalAttributesIndex($i,&$opsys,&$attr) { $opsys=0; $attr=0; }
    public function close() {}
}
function rb_tp_open($file,$updating=false)
{
    if(!is_dir($file)) throw new RuntimeException('PC에서 압축을 푼 테마 폴더를 FTP로 업로드해 주세요.');
    $zip = new RbThemePackageDirectory($file,$updating);
    try {
        if ($zip->numFiles > 20001) throw new RuntimeException('패키지 파일 개수가 너무 많습니다.');
        $seen = array(); $total = 0;
        for ($i=0; $i<$zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if(!$stat) throw new RuntimeException('FTP 파일이 누락되었습니다. 업로드 완료 후 다시 확인해 주세요.');
            $path = $stat['name'];
            if (!rb_tp_path($path) || isset($seen[strtolower($path)]) || $stat['size']>512*1024*1024)
                throw new RuntimeException('중복되거나 안전하지 않은 압축 경로입니다.');
            $opsys=0; $attr=0; $zip->getExternalAttributesIndex($i,$opsys,$attr);
            if (($attr>>16 & 0170000) === 0120000) throw new RuntimeException('심볼릭 링크는 설치할 수 없습니다.');
            $seen[strtolower($path)] = true; $total += $stat['size'];
            if ($total > 2*1024*1024*1024) throw new RuntimeException('패키지 용량은 2GB 이내여야 합니다.');
        }
        $stat = $zip->statName('manifest.json');
        if (!$stat || $stat['size']>8*1024*1024) throw new RuntimeException('리빌더 테마 패키지가 아닙니다.');
        $m = json_decode($zip->getFromName('manifest.json'),true);
        if (!is_array($m) || !isset($m['format'],$m['version'],$m['source_theme'],$m['source_url'],$m['source_data_url'])
            || $m['format']!=='rebuilder-theme' || $m['version']!==2 || !rb_tp_slug($m['source_theme'])) throw new RuntimeException('지원하지 않는 패키지 형식입니다.');
        foreach (array('files','data','refs','deps','boards','banners','css','assets','skins') as $key)
            if (!isset($m[$key]) || !is_array($m[$key])) throw new RuntimeException('패키지 정보가 불완전합니다.');
        if(isset($m['identity']) && !rb_tp_identity_valid($m['identity'])) throw new RuntimeException('테마 배포 식별정보가 올바르지 않습니다.');
        if(isset($m['delivery']) && !in_array($m['delivery'],array('new','update'),true)) throw new RuntimeException('배포 방식 정보 오류');
        if (count($m['files'])+1 !== $zip->numFiles) throw new RuntimeException('패키지 파일 목록이 일치하지 않습니다.');
        foreach ($m['files'] as $entry=>$info) {
            if (!rb_tp_path($entry) || !preg_match('~^(theme|deps/(widget|banner)/[a-f0-9]{16}|assets/[a-f0-9]{16}|carousel|custom|topvisual|banner/[0-9]+)/~',$entry))
                throw new RuntimeException('허용하지 않는 패키지 경로입니다.');
            $s = $zip->statName($entry);
            if (!$s || !isset($info['size'],$info['sha256']) || $s['size']!==$info['size']
                || !hash_equals($info['sha256'],$zip->checksum($entry))) throw new RuntimeException('파일이 손상되었거나 FTP 업로드가 끝나지 않았습니다: '.$entry);
        }
        if (!isset($m['files']['theme/theme.config.php'],$m['files']['theme/head.php'],$m['files']['theme/tail.php'])) throw new RuntimeException('테마 필수 파일이 없습니다.');
        foreach (rb_tp_tables() as $table=>$def) {
            if (!isset($m['data'][$table]) || !is_array($m['data'][$table]) || count($m['data'][$table])>10000) throw new RuntimeException('테마 데이터 형식 오류');
            $ids = array();
            foreach ($m['data'][$table] as $r) {
                if (!is_array($r) || !isset($r[$def[0]]) || $r[$def[0]]!==$m['source_theme']) throw new RuntimeException('다른 테마의 데이터가 포함되어 있습니다.');
                foreach ($r as $k=>$v) if (!preg_match('/\A[a-z][a-z0-9_]*\z/',$k) || (!is_scalar($v) && $v!==null)) throw new RuntimeException('설정 필드 형식 오류');
                if ($def[1]!=='') {
                    if (!isset($r[$def[1]]) || !preg_match('/\A[1-9][0-9]*\z/',(string)$r[$def[1]]) || isset($ids[$r[$def[1]]])) throw new RuntimeException('중복된 데이터 번호입니다.');
                    $ids[$r[$def[1]]] = true;
                }
            }
        }
        if (count($m['data']['rb_config'])!==1 || count($m['data']['rb_theme'])!==1) throw new RuntimeException('테마 환경설정을 먼저 저장해 주세요.');
        // 연결 대상은 배치에서 다시 계산한다. 게시판 스킨 설정과는 별개다.
        $m['refs'] = rb_tp_refs($m['data']);
        if (!isset($m['name']) || !is_string($m['name']) || !is_string($m['source_url']) || !is_string($m['source_data_url'])
            || !filter_var($m['source_url'],FILTER_VALIDATE_URL) || !filter_var($m['source_data_url'],FILTER_VALIDATE_URL)) throw new RuntimeException('원본 사이트 주소/테마 이름 오류');
        foreach(array('config','shop','qa') as $kind) {
            if (!isset($m['skins'][$kind]) || !is_array($m['skins'][$kind])) throw new RuntimeException('기본 스킨 정보 오류');
            foreach($m['skins'][$kind] as $skin) if(!is_string($skin) || strpos($skin,'theme/')!==0 || !rb_tp_path(substr($skin,6))) throw new RuntimeException('기본 스킨 경로 오류');
        }
        if (isset($m['scope']) && $m['scope']==='main-design'
            && ($m['boards'] || !empty($m['topvisual']) || rb_tp_main_data($m['data'])!==$m['data']))
            throw new RuntimeException('메인 디자인 자료에 게시판 설정 또는 서브페이지 배치가 포함되어 있습니다. 테마를 다시 내보내 주세요.');
        // 이전 메인 디자인 패키지에 남아 있는 서브 공통 설정도 설치 시 전달하지 않는다.
        if(isset($m['scope']) && $m['scope']==='main-design') $m['data']=rb_tp_main_settings($m['data']);
        // 이전 배포본의 스킨 프로필만 호환한다. 새 배포본은 이 정보를 저장하지 않는다.
        foreach($m['boards'] as $id=>$profile) {
            if (!isset($profile['bo_skin'],$profile['bo_mobile_skin'])) throw new RuntimeException('게시판 스킨 정보 누락');
            foreach(array('bo_skin','bo_mobile_skin') as $key) {
                $skin=$m['boards'][$id][$key];
                if(!is_string($skin) || strpos($skin,'theme/')!==0 || !rb_tp_path(substr($skin,6))) throw new RuntimeException('게시판 스킨 경로 오류');
            }
        }
        return array($zip,$m);
    } catch (Throwable $e) { $zip->close(); throw $e; }
}
function rb_tp_mappings($m, $input)
{
    $out = array(); $optional=isset($m['scope']) && $m['scope']==='main-design';
    foreach ($m['refs'] as $kind=>$refs) {
        if (!$refs) { $out[$kind]=array(); continue; }
        $available = rb_tp_catalog($kind); $used = array(); $out[$kind] = array();
        foreach ($refs as $source=>$unused) {
            if(isset($input[$kind][$source]) && !is_string($input[$kind][$source])) throw new RuntimeException('연결 정보 형식이 올바르지 않습니다.');
            $dest = isset($input[$kind][$source]) ? $input[$kind][$source] : ($optional?'':(string)$source);
            if($optional && $dest==='') { $out[$kind][$source]=''; continue; }
            if (!isset($available[$dest])) throw new RuntimeException('연결할 '.$kind.' 항목을 선택해 주세요: '.$source);
            if (!$optional && isset($used[$dest]) && $used[$dest] !== (string)$source) throw new RuntimeException('서로 다른 페이지는 각각 다른 대상에 연결해 주세요.');
            $used[$dest] = (string)$source; $out[$kind][$source] = $dest;
        }
    }
    return $out;
}
function rb_tp_page_layout($layout, $maps, $shop=false)
{
    if(preg_match('/^rb_gr_([A-Za-z0-9_]+)$/',$layout,$m)) {
        if(!isset($maps['group'][$m[1]])) throw new RuntimeException('그룹 연결이 누락되었습니다.');
        return 'rb_gr_'.$maps['group'][$m[1]];
    }
    return preg_replace_callback('/^rb_(bo|co|fr|ca|it|ev)_(top|btm)_'.($shop?'(shop_)?':'()').'([A-Za-z0-9_]+)/',function($m) use($maps) {
        $k=array('bo'=>'board','co'=>'content','fr'=>'form','ca'=>'category','it'=>'item','ev'=>'event');
        if (!isset($maps[$k[$m[1]]][$m[4]])) throw new RuntimeException('페이지 연결이 누락되었습니다.');
        return 'rb_'.$m[1].'_'.$m[2].'_'.$m[3].$maps[$k[$m[1]]][$m[4]];
    },$layout);
}
function rb_tp_layout($layout, $rows, $ids, $maps, $depth=0, $shop=false)
{
    if ($depth>30) throw new RuntimeException('모듈 중첩 구조를 확인해 주세요.');
    foreach ($rows as $r) {
        $prefix = $r['md_layout'].'-'.$r['md_id'];
        if ($layout === $prefix) return rb_tp_layout($r['md_layout'],$rows,$ids,$maps,$depth+1,$shop).'-'.$ids[$r['md_id']];
    }
    // 원본 부모가 없는 중첩 데이터는 조용히 누락하지 않는다.
    if (preg_match('/-[0-9]+$/',$layout)) throw new RuntimeException('부모 모듈이 없는 배치입니다: '.$layout);
    return rb_tp_page_layout($layout,$maps,$shop);
}
function rb_tp_replace($value, $replace)
{
    // JSON 안에 이스케이프된 URL도 같은 규칙으로 변환한다.
    $all = $replace;
    foreach ($replace as $a=>$b) $all[str_replace('/','\\/',$a)] = str_replace('/','\\/',$b);
    return strtr($value,$all);
}
function rb_tp_insert($table, $row, $pk, &$journal)
{
    $cols = array(); foreach (rb_tp_rows("SHOW COLUMNS FROM `$table`") as $c) $cols[$c['Field']] = true;
    // 이 컬럼은 기본값 없는 NOT NULL이다. 생략된 서브 폭은 빌더 기본값을 사용한다.
    if($table==='rb_config' && !array_key_exists('co_sub_width',$row)) $row['co_sub_width']='';
    if ($pk !== '') unset($row[$pk]);
    $sets = array();
    foreach ($row as $key=>$v) {
        if(!preg_match('/\A[a-z][a-z0-9_]*\z/',$key)) throw new RuntimeException('데이터 필드 이름 오류');
        if (!isset($cols[$key])) throw new RuntimeException('빌더 DB 업데이트가 필요합니다: '.$table.'.'.$key);
        if (!is_scalar($v) && $v!==null) throw new RuntimeException('데이터 형식이 올바르지 않습니다.');
        $sets[] = '`'.$key.'`='.rb_tp_q($v);
    }
    rb_tp_query("INSERT INTO `$table` SET ".implode(',',$sets));
    $id = $pk !== '' ? (string)sql_insert_id() : $row['theme_key'];
    $journal[] = array($table,$pk !== '' ? $pk : 'theme_key',$id);
    return $id;
}
function rb_tp_write($file, $bytes, &$files, &$dirs, $expectedSize=null)
{
    $dir = dirname($file); $missing = array();
    while (!is_dir($dir)) { $missing[]=$dir; $parent=dirname($dir); if ($parent===$dir) throw new RuntimeException('저장 경로 오류'); $dir=$parent; }
    if (is_link($dir)) throw new RuntimeException('링크 경로에는 설치할 수 없습니다.');
    if(realpath($dir)!==realpath(G5_PATH) && realpath($dir)!==realpath(G5_DATA_PATH)
        && !rb_tp_under($dir,G5_PATH) && !rb_tp_under($dir,G5_DATA_PATH)) throw new RuntimeException('사이트 밖의 경로에는 설치할 수 없습니다.');
    foreach (array_reverse($missing) as $p) { if (!mkdir($p,0755)) throw new RuntimeException('폴더 생성 권한을 확인해 주세요.'); $dirs[]=$p; }
    if (file_exists($file) || is_link($file)) throw new RuntimeException('기존 파일을 덮어쓸 수 없습니다.');
    $h = fopen($file,'x+b'); if (!$h) throw new RuntimeException('파일 저장 권한을 확인해 주세요.');
    $files[]=$file;
    if(is_resource($bytes)) { $written=stream_copy_to_stream($bytes,$h); fclose($bytes); }
    else { $expectedSize=strlen($bytes); $written=fwrite($h,$bytes); }
    fclose($h);
    if ($written!==$expectedSize) throw new RuntimeException('파일 저장 공간을 확인해 주세요.');
}
function rb_tp_install($zipfile, $requested, $input)
{
    if (!rb_tp_slug($requested)) throw new RuntimeException('테마 폴더명은 영문·숫자·점·밑줄·하이픈으로 40자 이내로 입력해 주세요.');
    if($requested!==basename($zipfile) || !rb_tp_under($zipfile,G5_PATH.'/theme')) throw new RuntimeException('현재 업로드된 테마 폴더를 선택해 주세요.');
    if(is_file($zipfile.'/rb-package.json')) throw new RuntimeException('이미 설치된 테마입니다.');
    list($zip,$m) = rb_tp_open($zipfile);
    $maps=rb_tp_mappings($m,$input);
    $moduleMaps=isset($m['scope']) && $m['scope']==='main-design' && array_key_exists('modules',$input)
        ? rb_tp_module_mappings($m['data'],$input['modules']):array();
    $journal=array(); $written=array(); $dirs=array(); $backups=array(); $locked=false;
    $lock='rb-theme-'.substr(hash('sha256',G5_PATH),0,30);
    try {
        $lockrow=rb_tp_rows('SELECT GET_LOCK('.rb_tp_q($lock).',10) AS ok');
        if (!$lockrow || (int)$lockrow[0]['ok']!==1) throw new RuntimeException('다른 테마 설치가 진행 중입니다. 잠시 후 다시 시도해 주세요.');
        $locked=true; $theme=$requested;
        rb_tp_check_theme_name($theme);
        $source=$m['source_theme']; $replace=array();
        $sourcePath=parse_url($m['source_url'],PHP_URL_PATH); $sourcePath=rtrim((string)$sourcePath,'/');
        $replace[$m['source_url'].'/theme/'.$source.'/']=G5_URL.'/theme/'.$theme.'/';
        $replace[$sourcePath.'/theme/'.$source.'/']=parse_url(G5_URL,PHP_URL_PATH).'/theme/'.$theme.'/';
        $replace['/theme/'.$source.'/']='/theme/'.$theme.'/';
        $replace[$m['source_data_url'].'/'.$source.'/']=G5_DATA_URL.'/'.$theme.'/';
        foreach(isset($m['skin_paths'])?$m['skin_paths']:array() as $from=>$to) {
            if(!rb_tp_path(rtrim($from,'/')) || !rb_tp_path(rtrim($to,'/'))) throw new RuntimeException('스킨 자료 경로 오류');
            $replace[$m['source_url'].'/'.$from]=G5_URL.'/theme/'.$theme.'/'.$to;
            $replace[$sourcePath.'/'.$from]=parse_url(G5_URL,PHP_URL_PATH).'/theme/'.$theme.'/'.$to;
            $replace['/'.$from]='/theme/'.$theme.'/'.$to;
        }
        $targets=array(); $inPlace=array(); $dependencies=array('widget'=>array(),'banner'=>array());
        foreach($m['files'] as $entry=>$info) if(strpos($entry,'theme/')===0) {
            $targets[$entry]=G5_PATH.'/theme/'.$theme.'/'.substr($entry,6);
            $inPlace[$entry]=G5_PATH.'/theme';
        }
        foreach(array('widget','banner') as $kind) {
            if (!isset($m['deps'][$kind]) || !is_array($m['deps'][$kind])) throw new RuntimeException('위젯/스킨 정보가 없습니다.');
            foreach($m['deps'][$kind] as $old=>$key) {
                if(!rb_tp_dependency_valid($kind,$old) || !preg_match('/\A[a-f0-9]{16}\z/',$key)) throw new RuntimeException('의존 파일 경로 오류');
                if (!isset($m['dependency_paths'][$kind][$key])) throw new RuntimeException('위젯/스킨 업로드 경로가 없습니다.');
                $uploaded=$m['dependency_paths'][$kind][$key];
                $original=isset($m['dependency_names'][$kind][$key])?$m['dependency_names'][$kind][$key]:basename($old);
                $dest=rb_tp_dependency_path($kind,$theme,$original);
                $label=$kind==='widget'?'위젯':'배너 스킨';
                if (in_array(strtolower($dest),array_map('strtolower',array_keys($dependencies[$kind])),true))
                    throw new RuntimeException('같은 이름의 '.$label.'이 중복 지정되었습니다: '.$original);
                if ($dest!==$uploaded && (file_exists(G5_PATH.'/'.$dest) || is_link(G5_PATH.'/'.$dest)))
                    throw new RuntimeException('설치할 '.$label.' 폴더가 이미 있습니다: '.$dest.'. 테마 폴더명을 확인해 주세요.');
                $dependencies[$kind][$dest]=array('name'=>$original);
                $replace[$old]=$dest; $replace[substr($old,3)]=substr($dest,3);
                $prefix='deps/'.$kind.'/'.$key.'/'; $required=$kind==='widget'?'widget.php':'banner.skin.php';
                if(!isset($m['files'][$prefix.$required])) throw new RuntimeException('위젯/배너 스킨 파일이 누락되었습니다.');
                foreach($m['files'] as $entry=>$info) if(strpos($entry,$prefix)===0) {
                    $targets[$entry]=G5_PATH.'/'.$dest.'/'.substr($entry,strlen($prefix));
                    if ($dest===$uploaded) $inPlace[$entry]=G5_PATH.'/'.($kind==='widget'?'rb/rb.widget':'rb/rb.mod/banner/skin');
                }
            }
        }
        foreach($m['assets'] as $rel=>$entry) {
            if(!rb_tp_path($rel) || !isset($m['files'][$entry]) || strpos($entry,'assets/')!==0) throw new RuntimeException('이미지 정보 오류');
            $newrel=$theme.'/package/'.substr($entry,7);
            $targets[$entry]=G5_DATA_PATH.'/'.$newrel;
            $replace[$m['source_data_url'].'/'.$rel]=G5_DATA_URL.'/'.$newrel;
            $replace[parse_url($m['source_data_url'],PHP_URL_PATH).'/'.$rel]=parse_url(G5_DATA_URL,PHP_URL_PATH).'/'.$newrel;
        }
        foreach($m['files'] as $entry=>$info) if(strpos($entry,'carousel/')===0) $targets[$entry]=G5_DATA_PATH.'/'.$theme.'/carousel_img/'.substr($entry,9);
        // 사이트 내부 링크만 새 주소로 변경한다. 외부 링크는 그대로 둔다.
        $replace[rtrim($m['source_url'],'/').'/']=rtrim(G5_URL,'/').'/';
        $convert=function($v) use($replace,$maps) {
            $v=rb_tp_replace($v,$replace);
            return preg_replace_callback('/([?&]|&amp;)(bo_table|co_id|fr_id|po_id|ca_id|gr_id|it_id|ev_id)=([A-Za-z0-9_]+)(?=[&#\s"\'<>]|$)/',function($a) use($maps) {
                $kinds=array('bo_table'=>'board','co_id'=>'content','fr_id'=>'form','po_id'=>'poll','ca_id'=>'category','gr_id'=>'group','it_id'=>'item','ev_id'=>'event'); $kind=$kinds[$a[2]];
                return $a[1].$a[2].'='.(isset($maps[$kind][$a[3]]) && $maps[$kind][$a[3]]!==''?$maps[$kind][$a[3]]:$a[3]);
            },$v);
        };
        $bannerIds=array(); $groups=array();
        foreach(array_merge($m['data']['rb_module'],$m['data']['rb_module_shop']) as $module)
            if($module['md_type']==='banner' && !empty($module['md_banner']) && $module['md_banner']!=='개별출력')
                $groups[$module['md_banner']]='tp_'.substr(hash('sha256',$theme.'|'.$module['md_banner']),0,24);
        foreach($m['banners'] as $row) {
            if(!isset($row['bn_id'],$row['bn_position']) || !ctype_digit((string)$row['bn_id'])) throw new RuntimeException('배너 정보 오류');
            $old=(string)$row['bn_id']; $group=$row['bn_position'];
            if($group!=='개별출력') { $groups[$group]='tp_'.substr(hash('sha256',$theme.'|'.$group),0,24); $row['bn_position']=$groups[$group]; }
            $row=rb_tp_walk_values($row,$convert); $row['bn_hit']='0';
            $new=rb_tp_insert('rb_banner',$row,'bn_id',$journal); $bannerIds[$old]=$new;
            foreach($m['files'] as $entry=>$info) {
                $prefix='banner/'.$old.'/'; if(strpos($entry,$prefix)!==0) continue;
                $tail=substr($entry,strlen($prefix));
                if($tail==='rb_display' || $tail==='banners') $targets[$entry]=G5_DATA_PATH.'/'.$tail.'/'.$new;
                elseif($tail==='design_image') $targets[$entry]=G5_DATA_PATH.'/rb_display_design/'.$new;
                elseif(strpos($tail,'design/')===0) $targets[$entry]=G5_DATA_PATH.'/rb_display_design/'.$new.'/'.substr($tail,7);
            }
        }
        $ids=array(); $sectionKeys=array(); $sectionUids=array();
        // 먼저 번호를 할당하고, 중첩 배치·섹션 참조를 새 번호로 연결한다.
        foreach(rb_tp_tables() as $table=>$def) {
            $ids[$table]=array();
            foreach($m['data'][$table] as $row) {
                $old=$def[1]!=='' ? $row[$def[1]] : $source;
                $row=rb_tp_walk_values($row,$convert); $row[$def[0]]=$theme;
                if(isset($row['image_path'])) $row['image_path']=basename($row['image_path']);
                $new=rb_tp_insert($table,$row,$def[1],$journal); $ids[$table][$old]=$new;
                if(strpos($table,'rb_section')===0) {
                    if(!empty($row['sec_key'])) $sectionKeys[$table][$row['sec_key']]='tp'.bin2hex(random_bytes(12));
                    if(!empty($row['sec_uid'])) $sectionUids[$table][$row['sec_uid']]='tp'.bin2hex(random_bytes(16));
                }
            }
        }
        foreach(array('rb_module','rb_module_shop','rb_section','rb_section_shop') as $table) {
            $module=strpos($table,'rb_module')===0; $p=$module?'md':'sec'; $shop=substr($table,-5)==='_shop';
            $mt=$shop?'rb_module_shop':'rb_module'; $st=$shop?'rb_section_shop':'rb_section';
            foreach($m['data'][$table] as $r) {
                $row=rb_tp_walk_values($r,$convert); $row[$p.'_theme']=$theme;
                $row[$p.'_layout']=rb_tp_layout($r[$p.'_layout'],$m['data'][$mt],$ids[$mt],$maps,0,$shop);
                foreach(array('key','uid') as $suffix) {
                    $field=$module?'md_sec_'.$suffix:'sec_'.$suffix;
                    if(empty($r[$field])) continue;
                    $map=$suffix==='key'?$sectionKeys:$sectionUids;
                    if(!isset($map[$st][$r[$field]])) throw new RuntimeException('섹션 연결 정보가 누락되었습니다.');
                    $row[$field]=$map[$st][$r[$field]];
                }
                if($module) {
                    if($r['md_type']==='item' && !empty($r['md_sca'])) $row['md_sca']=$maps['category'][$r['md_sca']];
                    if(!empty($r['md_bo_table'])) $row['md_bo_table']=$maps['board'][$r['md_bo_table']];
                    if(!empty($r['md_poll_id'])) $row['md_poll_id']=$maps['poll'][$r['md_poll_id']];
                    if($r['md_type']==='latest' && empty($row['md_bo_table'])) $row['md_sca']='';
                    if($r['md_type']==='poll' && empty($row['md_poll_id'])) $row['md_poll_id']='0';
                    foreach(array('md_tab_list'=>'board','md_item_tab_list'=>'category') as $field=>$kind) if(!empty($r[$field])) {
                        $tabs=array();
                        foreach(rb_tp_tab_list($r[$field]) as $tab) {
                            $parts=explode('||',$tab,2);
                            if(!isset($maps[$kind][$parts[0]])) throw new RuntimeException('탭 항목의 연결 정보가 없습니다.');
                            if($maps[$kind][$parts[0]]==='') continue;
                            $tabs[]=$maps[$kind][$parts[0]].(isset($parts[1])?'||'.$parts[1]:'');
                        }
                        $row[$field]=rb_tp_json($tabs);
                    }
                    if(isset($moduleMaps[$table][$r['md_id']])) $row=array_merge($row,$moduleMaps[$table][$r['md_id']]);
                    if($r['md_type']==='banner') {
                        if(!empty($r['md_banner_id'])) {
                            if($r['md_banner']==='개별출력' && !isset($bannerIds[$r['md_banner_id']])) throw new RuntimeException('배너 연결 누락');
                            $row['md_banner_id']=isset($bannerIds[$r['md_banner_id']])?$bannerIds[$r['md_banner_id']]:'0';
                        }
                        if(isset($groups[$r['md_banner']])) $row['md_banner']=$groups[$r['md_banner']];
                    }
                }
                unset($row[$p.'_id']); $sets=array();
                foreach($row as $key=>$v) $sets[]='`'.$key.'`='.rb_tp_q($v);
                rb_tp_query("UPDATE `$table` SET ".implode(',',$sets)." WHERE `{$p}_id`=".rb_tp_q($ids[$table][$r[$p.'_id']])." AND `{$p}_theme`=".rb_tp_q($theme));
            }
        }
        $custom=array();
        foreach($m['css'] as $c) {
            if(!isset($ids[$c['table']][$c['id']],$m['files'][$c['entry']]) || strpos($c['entry'],'custom/')!==0) throw new RuntimeException('개별 CSS 연결 오류');
            $module=strpos($c['table'],'rb_module')===0; $shop=substr($c['table'],-5)==='_shop'; $mt=$shop?'rb_module_shop':'rb_module';
            $layout=rb_tp_layout($c['layout'],$m['data'][$mt],$ids[$mt],$maps,0,$shop); $id=$ids[$c['table']][$c['id']];
            $filename=($module?'mod':'sec').($shop?'_shop':'').'_'.preg_replace('~[^A-Za-z0-9_\-]~','-',$layout).'_'.$id.'.css';
            $targets[$c['entry']]=G5_DATA_PATH.'/rb_custom_css/'.$filename;
            $custom[$c['entry']]=array('[data-layout="'.$c['layout'].'"]'=>'[data-layout="'.$layout.'"]','[data-id="'.$c['id'].'"]'=>'[data-id="'.$id.'"]',
                "[data-layout='".$c['layout']."']"=>"[data-layout='".$layout."']", "[data-id='".$c['id']."']"=>"[data-id='".$id."']");
        }
        $state=array('format'=>'rebuilder-installed','version'=>1,'name'=>$m['name'],'boards'=>array(),'source_theme'=>$source,'skins'=>$m['skins'],
            'builder_display'=>isset($m['builder_display'])?$m['builder_display']:array(),'aos'=>isset($m['aos'])?$m['aos']:array(),'dependencies'=>$dependencies);
        if(isset($m['scope']) && $m['scope']==='main-design') $state['scope']='main-design';
        foreach($m['boards'] as $id=>$profile) {
            if(!isset($maps['board'][$id])) throw new RuntimeException('게시판 프로필 연결 누락');
            $state['boards'][$maps['board'][$id]]=$profile;
        }
        $state['topvisual']=array();
        foreach(isset($m['topvisual'])?$m['topvisual']:array() as $row) {
            $old=$row['v_code']; $code=$old;
            foreach(array('board','content','form','group','item','event','category') as $kind) foreach($maps[$kind] as $from=>$to)
                if($old===rb_tp_visual_code($kind,$from)) $code=rb_tp_visual_code($kind,$to);
            $new='tp'.substr(hash('sha256',$theme),0,16).'-'.$code;
            if(strlen($new)>100 || !rb_tp_path($new)) throw new RuntimeException('서브상단 배치 경로가 너무 깁니다.');
            $row=rb_tp_walk_values($row,$convert); $row['v_code']=$new;
            rb_tp_insert('rb_topvisual',$row,'v_id',$journal); $state['topvisual'][$code]=$new;
            foreach(array('jpg','txt') as $ext) if(isset($m['files']['topvisual/'.$old.'.'.$ext]))
                $targets['topvisual/'.$old.'.'.$ext]=G5_DATA_PATH.'/topvisual/'.$new.'.'.$ext;
        }
        // ZIP 내부의 선언되지 않은 파일도 설치하지 않는다.
        if(count($targets)!==count($m['files'])) throw new RuntimeException('연결되지 않은 패키지 파일이 있습니다.');
        // 업로드한 테마는 제자리에서 경로만 조정한다. 완료 표시는 모든 처리가 끝난 뒤 기록한다.
        foreach($targets as $entry=>$dest) {
            if(!rb_tp_text_file($entry)) {
                if(isset($inPlace[$entry])) {
                    if(!hash_equals($m['files'][$entry]['sha256'],$zip->checksum($entry))) throw new RuntimeException('FTP 파일이 설치 중 변경되었습니다.');
                    continue;
                }
                rb_tp_write($dest,$zip->stream($entry),$written,$dirs,$m['files'][$entry]['size']);
                if(!hash_equals($m['files'][$entry]['sha256'],hash_file('sha256',$dest))) throw new RuntimeException('FTP 파일이 설치 중 변경되었습니다.');
                continue;
            }
            if($m['files'][$entry]['size']>8*1024*1024) throw new RuntimeException('편집 가능한 소스 파일은 8MB 이내여야 합니다: '.$entry);
            $bytes=$zip->getFromName($entry);
            if(!is_string($bytes) || !hash_equals($m['files'][$entry]['sha256'],hash('sha256',$bytes))) throw new RuntimeException('FTP 파일이 설치 중 변경되었습니다.');
            if(isset($m['asset_links'][$entry])) $bytes=strtr($bytes,$m['asset_links'][$entry]);
            if(rb_tp_text_file($entry)) $bytes=$convert($bytes);
            if(isset($custom[$entry])) $bytes=strtr($bytes,$custom[$entry]);
            if ($entry==='theme/readme.txt') $bytes=rb_tp_theme_readme($bytes,str_replace(array("\r","\n"),' ',$m['name']));
            if(isset($inPlace[$entry])) rb_tp_update_uploaded($dest,$bytes,$m['files'][$entry]['sha256'],$backups,$inPlace[$entry]);
            else rb_tp_write($dest,$bytes,$written,$dirs);
        }
        if(isset($m['identity'])) $state['identity']=$m['identity'];
        $state['source_maps']=$maps;
        rb_tp_write(G5_PATH.'/theme/'.$theme.'/rb-package.json',rb_tp_json($state),$written,$dirs);
        rb_tp_state($theme,true);
        return array('theme'=>$theme,'name'=>$m['name']);
    } catch(Throwable $e) {
        // MyISAM도 지원한다. 이번 설치가 새로 만든 행/파일만 보상 삭제한다.
        foreach(array_reverse($journal) as $j) sql_query('DELETE FROM `'.$j[0].'` WHERE `'.$j[1].'`='.rb_tp_q($j[2]),false);
        foreach(array_reverse($written) as $p) if(is_file($p)) @unlink($p);
        foreach(array_reverse($dirs) as $p) if(is_dir($p)) @rmdir($p);
        foreach($backups as $file=>$backup) if(!copy($backup,$file)) throw new RuntimeException('설치 오류 후 테마 파일 복구에 실패했습니다: '.basename($file),0,$e);
        throw $e;
    } finally {
        foreach($backups as $backup) if(is_file($backup)) unlink($backup);
        $zip->close();
        if($locked) sql_query('SELECT RELEASE_LOCK('.rb_tp_q($lock).')',false);
    }
}

function rb_tp_check_theme_name($theme)
{
    global $config;
    if(!rb_tp_slug($theme)) throw new RuntimeException('테마 폴더명을 확인해 주세요.');
    $exists=isset($config['cf_theme']) && $config['cf_theme']===$theme;
    if(is_dir(G5_DATA_PATH.'/'.$theme)) $exists=true;
    foreach(rb_tp_tables() as $table=>$def)
        if(rb_tp_rows("SELECT `{$def[0]}` FROM `$table` WHERE `{$def[0]}`=".rb_tp_q($theme).' LIMIT 1')) $exists=true;
    if($exists) throw new RuntimeException('현재 폴더명('.$theme.')은 기존 테마에서 사용 중입니다. 업로드할 테마 폴더를 다른 이름으로 변경한 뒤 다시 선택해 주세요.');
}

function rb_tp_update_uploaded($file,$bytes,$expectedHash,&$backups,$root=null)
{
    if($root===null) $root=G5_PATH.'/theme';
    if(!rb_tp_under($file,$root)) throw new RuntimeException('테마/위젯 파일 경로 오류');
    $handle=fopen($file,'r+b');
    if(!$handle) throw new RuntimeException('업로드한 테마 파일의 쓰기 권한을 확인해 주세요.');
    try {
        if(!flock($handle,LOCK_EX)) throw new RuntimeException('테마 파일을 잠글 수 없습니다.');
        $original=stream_get_contents($handle);
        if(!is_string($original) || !hash_equals($expectedHash,hash('sha256',$original))) throw new RuntimeException('FTP 파일이 설치 중 변경되었습니다.');
        if($bytes===$original) return;
        $backup=tempnam(sys_get_temp_dir(),'rb-theme-original-');
        if(!$backup) throw new RuntimeException('테마 파일 복구용 임시 공간이 없습니다.');
        $backups[$file]=$backup;
        if(file_put_contents($backup,$original)!==strlen($original)) {
            unset($backups[$file]); unlink($backup); throw new RuntimeException('테마 파일 복구용 저장 공간이 부족합니다.');
        }
        rewind($handle);
        if(!ftruncate($handle,0) || fwrite($handle,$bytes)!==strlen($bytes) || !fflush($handle)) throw new RuntimeException('테마 파일 저장에 실패했습니다.');
    } finally { flock($handle,LOCK_UN); fclose($handle); }
}
