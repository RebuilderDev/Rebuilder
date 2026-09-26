<?php
if (!defined('_GNUBOARD_')) exit;

// include 식은 실행하지 않고 문자열/경로 상수/dirname/단순 경로 변수만 해석한다.
function rb_tp_include_tokens($code)
{
    $out=array(); $offset=0; $line=1;
    foreach(token_get_all($code) as $token) {
        $text=is_array($token)?$token[1]:$token;
        $id=is_array($token)?$token[0]:null;
        if(!in_array($id,array(T_WHITESPACE,T_COMMENT,T_DOC_COMMENT),true))
            $out[]=array('id'=>$id,'text'=>$text,'start'=>$offset,'end'=>$offset+strlen($text),'line'=>$line);
        $offset+=strlen($text);
        $line+=substr_count($text,"\n");
    }
    return $out;
}
function rb_tp_include_expression($tokens,$from)
{
    $depth=0; $out=array();
    for($i=$from;$i<count($tokens);$i++) {
        $t=$tokens[$i]; $s=$t['text'];
        if($depth===0 && ($s===';' || $s===',' || $s===')' || $t['id']===T_CLOSE_TAG
            || in_array($t['id'],array(T_LOGICAL_OR,T_LOGICAL_AND),true))) break;
        if($s==='(') $depth++;
        if($s===')') $depth--;
        if($depth<0 || $s==='{') break;
        $out[]=$t;
    }
    return $out;
}
function rb_tp_include_value($tokens,$file,$theme,$variables,$pattern=false)
{
    $i=0; $read=null; $atom=null;
    $paths=array('G5_PATH'=>G5_PATH,'G5_THEME_PATH'=>G5_PATH.'/theme/'.$theme,'G5_DATA_PATH'=>G5_DATA_PATH,
        'G5_LIB_PATH'=>G5_PATH.'/lib','G5_BBS_PATH'=>G5_PATH.'/bbs','G5_ADMIN_PATH'=>G5_PATH.'/adm',
        'G5_PLUGIN_PATH'=>G5_PATH.'/plugin','G5_SHOP_PATH'=>G5_PATH.'/shop','G5_MOBILE_PATH'=>G5_PATH.'/mobile',
        'G5_EXTEND_PATH'=>G5_PATH.'/extend',
        'G5_THEME_MOBILE_PATH'=>G5_PATH.'/theme/'.$theme.'/mobile','DIRECTORY_SEPARATOR'=>'/',
        'G5_URL'=>G5_URL,'G5_THEME_URL'=>G5_URL.'/theme/'.$theme,'G5_DATA_URL'=>G5_DATA_URL);
    $atom=function()use(&$i,&$read,&$atom,$tokens,$file,$paths,$variables,$pattern) {
        if(!isset($tokens[$i])) return null;
        $t=$tokens[$i++]; $s=$t['text'];
        if($s==='(') {
            $value=$read();
            if(!isset($tokens[$i]) || $tokens[$i++]['text']!==')') return null;
            return $value;
        }
        if($t['id']===T_CONSTANT_ENCAPSED_STRING) {
            $value=substr($s,1,-1);
            return $s[0]==="'"?str_replace(array("\\'","\\\\"),array("'","\\"),$value):stripcslashes($value);
        }
        if($t['id']===T_DIR) return str_replace('\\','/',dirname($file));
        if($t['id']===T_FILE) return str_replace('\\','/',$file);
        if($t['id']===T_VARIABLE) return isset($variables[$s])?$variables[$s]:($pattern?'*':null);
        if($t['id']===T_STRING && isset($paths[$s])) return $paths[$s];
        if($t['id']===T_STRING && strtolower($s)==='dirname' && isset($tokens[$i]) && $tokens[$i++]['text']==='(') {
            $value=$read(); $levels=1;
            if(isset($tokens[$i]) && $tokens[$i]['text']===',') {
                $i++; if(!isset($tokens[$i]) || $tokens[$i]['id']!==T_LNUMBER) return null;
                $levels=(int)$tokens[$i++]['text'];
            }
            if($value===null || $levels<1 || $levels>30 || !isset($tokens[$i]) || $tokens[$i++]['text']!==')') return null;
            return str_replace('\\','/',dirname($value,$levels));
        }
        if($pattern && $s==='"') {
            $value='';
            while(isset($tokens[$i]) && $tokens[$i]['text']!=='"') {
                $part=$tokens[$i++];
                if($part['id']===T_ENCAPSED_AND_WHITESPACE) $value.=$part['text'];
                elseif($part['id']===T_VARIABLE) $value.=isset($variables[$part['text']])?$variables[$part['text']]:'*';
            }
            if(!isset($tokens[$i])) return null;
            $i++; return $value;
        }
        if($pattern && $t['id']===T_STRING && isset($tokens[$i]) && $tokens[$i]['text']==='(') {
            // urlencode 등은 실행하지 않는다. 앞쪽 URL/폴더를 유지하고 반환값만 미정으로 표시한다.
            $depth=0;
            do {
                $part=$tokens[$i++]['text'];
                if($part==='(') $depth++; elseif($part===')') $depth--;
            } while(isset($tokens[$i]) && $depth>0);
            return $depth===0?'*':null;
        }
        return null;
    };
    $read=function()use(&$i,&$read,&$atom,$tokens) {
        $value=$atom();
        while(isset($tokens[$i]) && $tokens[$i]['text']==='.') {
            $i++; $part=$atom();
            $value=$value===null || $part===null?null:$value.$part;
        }
        return $value;
    };
    $value=$read();
    return $i===count($tokens)?$value:null;
}
function rb_tp_original_dependencies($m)
{
    return isset($m['dependency_layout']) && $m['dependency_layout']==='original';
}
function rb_tp_original_file($m,$entry)
{
    return rb_tp_original_dependencies($m) && (strpos($entry,'deps/')===0 || strpos($entry,'user/')===0);
}
function rb_tp_user_path($path)
{
    if(!rb_tp_path($path)) return false;
    // 인증/운영 데이터는 사용자 코드의 참조가 있어도 패키지에 포함하지 않는다.
    if(preg_match('~(?:^|/)(?:\.[^/]+|dbconfig\.php|config\.php|shop\.config\.php|rb-package(?:\.json)?|node_modules|tests|sessions?|cache|logs?|backups?)(?:/|$)~i',$path)) return false;
    if(preg_match('~\A(?:data/(?:member|file|editor|qa|session|cache|rb\.backup|rb\.theme-publish)/|install/)~i',$path)) return false;
    if(preg_match('~(?:^|/)(?:[^/]*(?:credential|secret|password)[^/]*|composer\.lock|package-lock\.json)$~i',$path)) return false;
    return (bool)preg_match('/\.(php|inc|css|scss|sass|less|js|mjs|json|html?|txt|md|svg|xml|png|jpe?g|gif|webp|ico|avif|woff2?|ttf|otf|eot|mp4|webm|mp3|wav|ogg|pdf|map)$/i',$path);
}
function rb_tp_runtime_reference($path)
{
    // 그누보드의 초기화 코드/기본 라이브러리는 받는 사이트에 설치된 코드를 사용한다.
    if(preg_match('~\A(?:common|_common|config|shop\.config|head\.sub|tail\.sub)\.php\z~i',$path)) return true;
    if(preg_match('~\A(?:bbs|adm|shop|mobile|mobile/shop)/(?:_common|_head|_tail|head|tail|shop\.head|shop\.tail)\.php\z~i',$path)) return true;
    // 빌더/그누보드 기본 확장은 설치 대상의 런타임을 사용한다. 사용자 확장은 이름으로 추측하지 않는다.
    if(strpos($path,'extend/')===0 && in_array(substr($path,7),array(
        'rb_admin.extend.php','rb_asset_version.extend.php','rb_bbs.extend.php','rb_business_console.extend.php',
        'rb_business_order.extend.php','rb_core.extend.php','rb_custom_css.extend.php','rb_license.extend.php',
        'rb_memo.extend.php','rb_notification_admin.extend.php','rb_notification.extend.php','rb_shop.extend.php',
        'rb_theme_package.extend.php','rb_theme.extend.php','default.config.php','g5_54version_update.extend.php',
        'shop.extend.php','sms5.extend.php','social_login.extend.php','smarteditor_upload_extend.php','version.extend.php'),true)) return true;
    return (bool)preg_match('~\Alib/(?:common|latest|outlogin|poll|visit|connect|popular|thumbnail|mailer|captcha|member|register|search|content|editor|shop|shop\.data|shop\.extra|uri|hook|json|naver_syndi|icode\.sms|icode\.lms|sms|syndication)\.lib\.php\z~i',$path);
}
function rb_tp_php_name_token($token)
{
    if($token['id']===T_STRING || $token['id']===T_NS_SEPARATOR) return true;
    foreach(array('T_NAME_QUALIFIED','T_NAME_FULLY_QUALIFIED','T_NAME_RELATIVE') as $name)
        if(defined($name) && $token['id']===constant($name)) return true;
    return false;
}
function rb_tp_php_names($tokens)
{
    // PHP 7의 분리된 네임스페이스 토큰도 PHP 8과 같은 이름 토큰으로 정리한다.
    $out=array();
    foreach($tokens as $token) {
        $last=count($out)-1;
        if($last>=0 && rb_tp_php_name_token($token) && rb_tp_php_name_token($out[$last])
            && (substr($out[$last]['text'],-1)==='\\' || $token['text']==='\\')) {
            $out[$last]['text'].=$token['text']; $out[$last]['end']=$token['end'];
        } else $out[]=$token;
    }
    return $out;
}
function rb_tp_php_declarations($tokens)
{
    $symbols=array('function'=>array(),'class'=>array(),'constant'=>array());
    $parameters=array(); $namespace=''; $namespaceEnd=null; $depth=0; $classDepths=array(); $pendingClass=false;
    $classTokens=array(T_CLASS,T_INTERFACE,T_TRAIT);
    if(defined('T_ENUM')) $classTokens[]=constant('T_ENUM');
    foreach($tokens as $i=>$token) {
        $text=$token['text']; $id=$token['id'];
        if($id===T_NAMESPACE) {
            $namespace='';
            for($j=$i+1;isset($tokens[$j]) && rb_tp_php_name_token($tokens[$j]);$j++) $namespace.=$tokens[$j]['text'];
            $namespace=trim($namespace,'\\');
            $namespaceEnd=isset($tokens[$j]) && $tokens[$j]['text']==='{'?$depth+1:null;
        }
        if(in_array($id,$classTokens,true) && (!isset($tokens[$i-1]) || $tokens[$i-1]['text']!=='::')) {
            $pendingClass=true;
            if(isset($tokens[$i+1]) && $tokens[$i+1]['id']===T_STRING)
                $symbols['class'][]=ltrim($namespace.'\\'.$tokens[$i+1]['text'],'\\');
        }
        if($id===T_FUNCTION && (!isset($tokens[$i-1]) || $tokens[$i-1]['id']!==T_USE)) {
            $j=$i+1; if(isset($tokens[$j]) && $tokens[$j]['text']==='&') $j++;
            if(isset($tokens[$j]) && $tokens[$j]['id']===T_STRING) {
                if(!$classDepths) $symbols['function'][]=ltrim($namespace.'\\'.$tokens[$j]['text'],'\\');
                $j++;
            }
            if(isset($tokens[$j]) && $tokens[$j]['text']==='(') {
                $level=1;
                for($j++;isset($tokens[$j]) && $level>0;$j++) {
                    if($tokens[$j]['text']==='(') $level++;
                    elseif($tokens[$j]['text']===')') $level--;
                    elseif($level===1 && $tokens[$j]['id']===T_VARIABLE) $parameters[$j]=true;
                }
            }
        }
        if($id===T_CONST && !$classDepths && (!isset($tokens[$i-1]) || $tokens[$i-1]['id']!==T_USE)
            && isset($tokens[$i+1]) && $tokens[$i+1]['id']===T_STRING)
            $symbols['constant'][]=ltrim($namespace.'\\'.$tokens[$i+1]['text'],'\\');
        if($id===T_STRING && strtolower($text)==='define' && isset($tokens[$i+2])
            && $tokens[$i+1]['text']==='(' && $tokens[$i+2]['id']===T_CONSTANT_ENCAPSED_STRING) {
            $name=substr($tokens[$i+2]['text'],1,-1);
            if(preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/',$name)) $symbols['constant'][]=$name;
        }
        if($text==='{') { $depth++; if($pendingClass) { $classDepths[]=$depth; $pendingClass=false; } }
        elseif($text==='}') {
            if($classDepths && end($classDepths)===$depth) array_pop($classDepths);
            if($namespaceEnd===$depth) { $namespace=''; $namespaceEnd=null; }
            $depth--;
        }
    }
    return array('symbols'=>$symbols,'parameters'=>$parameters);
}
function rb_tp_extend_index()
{
    $index=array(); $dir=G5_PATH.'/extend';
    if(!is_dir($dir)) return $index;
    // 정의를 읽기만 한다. 확장 코드를 실행하거나 훅을 호출하지 않는다.
    foreach(new DirectoryIterator($dir) as $file) {
        $relative='extend/'.$file->getFilename();
        if(!$file->isFile() || $file->isLink() || !preg_match('/\.php$/i',$file->getFilename())
            || rb_tp_runtime_reference($relative) || !rb_tp_user_path($relative)) continue;
        if(!rb_tp_under($file->getPathname(),G5_PATH) || $file->getSize()>8*1024*1024) continue;
        $declarations=rb_tp_php_declarations(rb_tp_php_names(rb_tp_include_tokens(file_get_contents($file->getPathname()))));
        foreach($declarations['symbols'] as $kind=>$names) foreach($names as $name) {
            $key=$kind==='constant'?$name:strtolower($name);
            $index[$kind][$key][$relative]=true;
        }
    }
    return $index;
}
function rb_tp_extend_references($tokens,$index,$context,$add,&$warnings)
{
    $namespace=''; $imports=array('function'=>array(),'class'=>array(),'constant'=>array());
    $resolve=function($kind,$name,$line)use($index,$context,$add,&$warnings,&$namespace,&$imports) {
        $name=ltrim($name,'\\'); if($name==='') return;
        $key=$kind==='constant'?$name:strtolower($name);
        $candidates=array();
        if(isset($imports[$kind][$key])) $candidates[]=$imports[$kind][$key];
        if($namespace!=='') $candidates[]=$namespace.'\\'.$name;
        $candidates[]=$name;
        foreach($candidates as $candidate) {
            $candidate=$kind==='constant'?$candidate:strtolower($candidate);
            if(empty($index[$kind][$candidate])) continue;
            $paths=array_keys($index[$kind][$candidate]);
            if(count($paths)>1) $warnings[]=$context.':'.$line.' · 같은 이름의 확장 정의가 여러 파일에 있습니다: '.$name;
            else $add($paths[0],$context.':'.$line,true);
            break;
        }
    };
    foreach($tokens as $i=>$token) {
        $prev=isset($tokens[$i-1])?$tokens[$i-1]:array('id'=>null,'text'=>'');
        $next=isset($tokens[$i+1])?$tokens[$i+1]:array('id'=>null,'text'=>'');
        if($token['id']===T_NAMESPACE) {
            $namespace=$next['text']==='{'?'':trim($next['text'],'\\');
        }
        if($token['id']===T_USE && rb_tp_php_name_token($next)) {
            $alias=basename(str_replace('\\','/',$next['text']));
            if(isset($tokens[$i+3]) && $tokens[$i+2]['id']===T_AS) $alias=$tokens[$i+3]['text'];
            $imports['class'][strtolower($alias)]=trim($next['text'],'\\');
        } elseif($token['id']===T_USE && in_array($next['id'],array(T_FUNCTION,T_CONST),true) && isset($tokens[$i+2])) {
            $kind=$next['id']===T_FUNCTION?'function':'constant'; $name=$tokens[$i+2]['text'];
            $alias=basename(str_replace('\\','/',$name));
            if(isset($tokens[$i+4]) && $tokens[$i+3]['id']===T_AS) $alias=$tokens[$i+4]['text'];
            $imports[$kind][$kind==='constant'?$alias:strtolower($alias)]=trim($name,'\\');
        }
        if(rb_tp_php_name_token($token)) {
            if($next['text']==='(' && !in_array($prev['text'],array('->','?->','::'),true)
                && !in_array($prev['id'],array(T_FUNCTION,T_NEW),true))
                $resolve('function',$token['text'],$token['line']);
            if($next['text']==='::' || in_array($prev['id'],array(T_NEW,T_EXTENDS,T_IMPLEMENTS,T_INSTANCEOF,T_USE),true))
                $resolve('class',$token['text'],$token['line']);
            if(!in_array($prev['text'],array('->','?->','::'),true)) $resolve('constant',$token['text'],$token['line']);
        } elseif($token['id']===T_CONSTANT_ENCAPSED_STRING) {
            // function_exists/class_exists 및 문자열 콜백의 고정된 이름도 연결한다.
            $name=str_replace('\\\\','\\',substr($token['text'],1,-1));
            if(preg_match('/\A\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*\z/',$name))
                foreach(array('function','class','constant') as $kind) $resolve($kind,$name,$token['line']);
        }
    }
}
function rb_tp_user_absolute($path)
{
    $base=str_replace('\\','/',realpath(G5_PATH));
    $path=preg_replace('~/+~','/',str_replace('\\','/',$path)); $parts=array();
    foreach(explode('/',$path) as $part) {
        if($part==='.') continue;
        if($part==='..') { if(!$parts) return false; array_pop($parts); }
        else $parts[]=$part;
    }
    $path=implode('/',$parts);
    foreach(array($base,rtrim(preg_replace('~/+~','/',str_replace('\\','/',G5_PATH)),'/')) as $root)
        if(stripos($path,$root.'/')===0) return substr($path,strlen($root)+1);
    return false;
}
function rb_tp_reference_path($value,$file)
{
    $value=html_entity_decode(trim($value),ENT_QUOTES,'UTF-8');
    if($value==='' || strpos($value,"\0")!==false) return false;
    $value=str_replace('\\','/',$value);
    if(preg_match('~^(?:https?:)?//~i',$value)) {
        $url=strpos($value,'//')===0?parse_url(G5_URL,PHP_URL_SCHEME).':'.$value:$value;
        $host=parse_url($url,PHP_URL_HOST); $path=parse_url($url,PHP_URL_PATH);
        $base=rtrim((string)parse_url(G5_URL,PHP_URL_PATH),'/');
        if(!is_string($host) || strcasecmp($host,(string)parse_url(G5_URL,PHP_URL_HOST))!==0
            || !is_string($path) || strpos($path,$base.'/')!==0) return false;
        $value=G5_PATH.'/'.rawurldecode(substr($path,strlen($base)+1));
    } elseif(preg_match('~^[a-z][a-z0-9+.-]*:~i',$value) && !preg_match('~^[a-z]:/~i',$value)) return false;
    $value=preg_replace('/[?#].*$/','',$value);
    if(preg_match('~^(?:[a-z]:/|/)~i',$value)) {
        $relative=rb_tp_user_absolute($value);
        if($relative!==false) return $relative;
        // URL 루트 경로와 파일시스템 절대 경로를 구분한다.
        if($value[0]!=='/' || is_file($value)) return false;
        $urlBase=rtrim((string)parse_url(G5_URL,PHP_URL_PATH),'/');
        if($urlBase!=='' && strpos($value,$urlBase.'/')===0) $value=substr($value,strlen($urlBase));
        return rb_tp_user_absolute(G5_PATH.$value);
    }
    $relative=rb_tp_user_absolute(dirname($file).'/'.$value);
    if($relative!==false && (is_file(G5_PATH.'/'.$relative) || strpos($relative,'*')!==false)) return $relative;
    $rootRelative=rb_tp_user_absolute(G5_PATH.'/'.$value);
    if($rootRelative!==false && is_file(G5_PATH.'/'.$rootRelative)) return $rootRelative;
    return $relative;
}
function rb_tp_external_reference($value)
{
    if(!is_string($value)) return false;
    $value=html_entity_decode(trim($value),ENT_QUOTES,'UTF-8');
    if(strpos($value,'//')===0) $value=parse_url(G5_URL,PHP_URL_SCHEME).':'.$value;
    if(preg_match('~\Ahttps?://~i',$value)) {
        $host=parse_url($value,PHP_URL_HOST);
        return is_string($host) && strpos($host,'*')===false && strcasecmp($host,(string)parse_url(G5_URL,PHP_URL_HOST))!==0;
    }
    // 요청 본문/메모리/외부 스트림은 배포할 로컬 사용자 파일이 아니다.
    return (bool)preg_match('~\A(?:php|data|https?|ftp|ftps|s3|gs)://~i',$value) && !preg_match('~\Ahttps?://~i',$value);
}
function rb_tp_collect_user_files(&$files,$theme)
{
    $paths=array(); $queue=array(); $warnings=array(); $seen=array();
    $extendIndex=rb_tp_extend_index();
    foreach($files as $entry=>$path) if(is_file($path)) {
        $paths[str_replace('\\','/',realpath($path))]=$entry;
        if(strpos($entry,'deps/')===0 && rb_tp_text_file($entry)) $queue[]=$entry;
    }
    $add=function($relative,$context,$required)use(&$files,&$paths,&$queue,&$warnings) {
        if(rb_tp_runtime_reference($relative)) return;
        if(!rb_tp_user_path($relative)) {
            if($required) $warnings[]=$context.' · 자동 수집 제외: '.$relative;
            return;
        }
        $file=G5_PATH.'/'.$relative;
        if(!is_file($file)) {
            if($required) $warnings[]=$context.' · 참조 파일 없음: '.$relative;
            return;
        }
        // realpath로 내부 별칭을 따라가서 원래 경로를 잃지 않도록 중간 링크도 거부한다.
        $part=G5_PATH;
        foreach(explode('/',$relative) as $piece) { $part.='/'.$piece; if(is_link($part)) throw new RuntimeException('참조 파일 경로에 링크가 있습니다: '.$relative); }
        if(!rb_tp_under($file,G5_PATH)) throw new RuntimeException('참조 파일이 사이트 폴더 밖을 가리킵니다.');
        $absolute=str_replace('\\','/',realpath($file));
        if(isset($paths[$absolute]) && preg_match('~^(?:theme|deps|user)/~',$paths[$absolute])) return;
        if(count($files)>=20000) throw new RuntimeException('참조 파일은 전체 20,000개 이내여야 합니다.');
        $entry='user/'.$relative; $files[$entry]=$file; $paths[$absolute]=$entry;
        if(rb_tp_text_file($entry) || preg_match('/\.inc$/i',$entry)) $queue[]=$entry;
    };
    $follow=function($value,$file,$context,$required)use($add,&$warnings) {
        if(rb_tp_external_reference($value)) return;
        if(!is_string($value) || $value==='') {
            if($required) $warnings[]=$context.' · 실행 중 결정되는 경로: 외부 참조 파일을 직접 확인해 주세요.';
            return;
        }
        $relative=rb_tp_reference_path($value,$file);
        if($relative===false) {
            if($required) $warnings[]=$context.' · 사이트 밖 또는 확인할 수 없는 참조: '.$value;
            return;
        }
        if(preg_match('~\Adata/(?:cache|session)/~',$relative)) return;
        if(strpos($relative,'*')!==false) {
            // 경로의 고정된 폴더가 명확할 때만 변수 부분에 해당하는 파일을 수집한다.
            $fixed=substr($relative,0,strpos($relative,'*'));
            if(substr_count(trim($fixed,'/'),'/')<1 || !preg_match('/\.(php|inc|css|js|mjs|html?|png|jpe?g|gif|webp|svg)$/i',$relative)) {
                if($required) $warnings[]=$context.' · 실행 중 결정되는 경로: '.$value;
                return;
            }
            $matches=glob(G5_PATH.'/'.$relative,GLOB_NOSORT);
            foreach($matches?:array() as $match) {
                $matched=rb_tp_user_absolute($match);
                if($matched!==false) $add($matched,$context,$required);
            }
            if(!$matches && $required) $warnings[]=$context.' · 참조 파일을 찾지 못했습니다: '.$value;
            return;
        }
        $add($relative,$context,$required);
    };
    for($cursor=0;$cursor<count($queue);$cursor++) {
        $entry=$queue[$cursor]; if(isset($seen[$entry])) continue; $seen[$entry]=true;
        $file=$files[$entry]; if(filesize($file)>8*1024*1024) throw new RuntimeException('참조 소스는 8MB 이내여야 합니다: '.$entry);
        $code=file_get_contents($file); $relative=rb_tp_user_absolute($file); $scan=$code;
        if(preg_match('/\.(php|inc)$/i',$entry)) {
            $tokens=rb_tp_php_names(rb_tp_include_tokens($code)); $variables=array(); $scan='';
            $declarations=rb_tp_php_declarations($tokens);
            rb_tp_extend_references($tokens,$extendIndex,$relative,$add,$warnings);
            foreach($tokens as $i=>$token) {
                $context=$relative.':'.$token['line'];
                if(isset($declarations['parameters'][$i])) { $variables[$token['text']]='*'; continue; }
                if($token['id']===T_VARIABLE && isset($tokens[$i+1]) && $tokens[$i+1]['text']==='=') {
                    $value=rb_tp_include_value(rb_tp_include_expression($tokens,$i+2),$file,$theme,$variables);
                    if($value===null) $value=rb_tp_include_value(rb_tp_include_expression($tokens,$i+2),$file,$theme,$variables,true);
                    if(array_key_exists($token['text'],$variables) && $variables[$token['text']]!==$value
                        && !(rb_tp_external_reference($variables[$token['text']]) && rb_tp_external_reference($value))) $value=null;
                    $variables[$token['text']]=$value;
                }
                $include=in_array($token['id'],array(T_INCLUDE,T_INCLUDE_ONCE,T_REQUIRE,T_REQUIRE_ONCE),true);
                $read=$token['id']===T_STRING && in_array(strtolower($token['text']),array('file_get_contents','readfile','fopen'),true)
                    && isset($tokens[$i+1]) && $tokens[$i+1]['text']==='(';
                if($include || $read) {
                    $expression=rb_tp_include_expression($tokens,$i+($include?1:2));
                    $value=rb_tp_include_value($expression,$file,$theme,$variables);
                    if($value===null) $value=rb_tp_include_value($expression,$file,$theme,$variables,true);
                    $follow($value,$file,$context,true);
                }
                if($token['id']===T_INLINE_HTML) $scan.=$token['text']."\n";
                if($token['id']===T_CONSTANT_ENCAPSED_STRING) {
                    $literal=rb_tp_include_value(array($token),$file,$theme,$variables);
                    $scan.=$literal."\n";
                    if(is_string($literal) && preg_match('~^[^\s<>"\']+\.(?:css|js|mjs|png|jpe?g|gif|webp|svg|woff2?)(?:[?#].*)?$~i',$literal))
                        $follow($literal,$file,$context,false);
                }
            }
        }
        // HTML 리소스, CSS url/import, JS import/fetch의 정적인 참조도 재귀 수집한다.
        preg_match_all('~(?:\b(?:src|href|poster)\s*=\s*["\']|\burl\(\s*["\']?|@import\s*["\']|\b(?:from|import)\s*["\']|\b(?:fetch|import)\(\s*["\'])([^\s"\'<>\)]+)~i',$scan,$refs);
        foreach(array_unique($refs[1]) as $value) $follow($value,$file,$relative,false);
    }
    return array_values(array_unique($warnings));
}
