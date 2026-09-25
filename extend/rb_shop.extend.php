<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// shop.extend.php가 코어 라이브러리를 읽은 다음, 구버전에 없는 함수만 보완한다.
// 이 파일이 먼저 로드되므로 여기서 즉시 선언하면 최신 코어와 함수명이 충돌한다.
add_event('common_header', 'rb_shop_category_compat', 0);
function rb_shop_category_compat()
{
    if (function_exists('get_shop_category_menu_groups')) return;
    function get_shop_category_menu_groups($ca_id)
    {
        global $g5;
        $ca_id=(string)$ca_id;
        if (!preg_match('/\A(?:[A-Za-z0-9]{2}){0,5}\z/',$ca_id)) return array();
        $length=strlen($ca_id);
        $queries=$length>=8
            ? array(array('5단계 분류',substr($ca_id,0,8),10),array('4단계 분류',substr($ca_id,0,6),8))
            : array(array('하위 분류',$ca_id,$length+2));
        $read=function($prefix,$depth) use($g5) {
            $sql="SELECT * FROM {$g5['g5_shop_category_table']} WHERE ca_use='1'";
            if ($prefix!=='') $sql.=" AND ca_id LIKE '".sql_real_escape_string($prefix)."%'";
            $result=sql_query($sql.' AND length(ca_id)='.(int)$depth.' ORDER BY ca_order,ca_id');
            $rows=array();
            while ($row=sql_fetch_array($result)) $rows[]=$row;
            return $rows;
        };
        foreach ($queries as $query) {
            $rows=$read($query[1],$query[2]);
            if (!$rows && $length<8) {
                $query[0]='현재 단계 분류';
                $rows=$read(substr($ca_id,0,-2),$length);
            }
            if ($rows) return array(array('label'=>$query[0],'categories'=>$rows));
        }
        return array();
    }
}

// 크롭옵션을 사용하기위해 별도함수 사용
function rb_it_image($it_id, $width, $height=0, $anchor=false, $img_id='', $img_alt='', $is_crop=true)
{
    global $g5;

    if(!$it_id || !$width)
        return '';

    $row = get_shop_item($it_id, true);

    if(!$row['it_id'])
        return '';

    $filename = $thumb = $img = '';

    $img_width = 0;
    for($i=1;$i<=10; $i++) {
        $file = G5_DATA_PATH.'/item/'.$row['it_img'.$i];
        if(is_file($file) && $row['it_img'.$i]) {
            $size = @getimagesize($file);
            if(! isset($size[2]) || $size[2] < 1 || $size[2] > 3)
                continue;

            $filename = basename($file);
            $filepath = dirname($file);
            $img_width = $size[0];
            $img_height = $size[1];

            break;
        }
    }

    if($img_width && !$height) {
        $height = round(($width * $img_height) / $img_width);
    }

    if($filename) {
        //thumbnail($filename, $source_path, $target_path, $thumb_width, $thumb_height, $is_create, $is_crop=false, $crop_mode='center', $is_sharpen=true, $um_value='80/0.5/3')
        $thumb = thumbnail($filename, $filepath, $filepath, $width, $height, false, $is_crop, 'center', false, $um_value='80/0.5/3');
    }

    if($thumb) {
        $file_url = str_replace(G5_PATH, G5_URL, $filepath.'/'.$thumb);
        $img = '<img src="'.$file_url.'" width="'.$width.'" height="'.$height.'" alt="'.$img_alt.'"';
    } else {
        $img = '<img src="'.G5_SHOP_URL.'/img/no_image.gif" width="'.$width.'"';
        if($height)
            $img .= ' height="'.$height.'"';
        $img .= ' alt="'.$img_alt.'"';
    }

    if($img_id)
        $img .= ' id="'.$img_id.'"';
    $img .= '>';

    if($anchor)
        $img = $img = '<a href="'.shop_item_url($it_id).'">'.$img.'</a>';

    return run_replace('get_it_image_tag', $img, $thumb, $it_id, $width, $height, $anchor, $img_id, $img_alt, $is_crop);
}

// 상품이미지 썸네일 생성
function rb_it_thumbnail($img, $width, $height=0, $id='', $is_crop=true)
{
    $str = '';

    if ( $replace_tag = run_replace('get_it_thumbnail_tag', $str, $img, $width, $height, $id, $is_crop) ){
        return $replace_tag;
    }

    $file = G5_DATA_PATH.'/item/'.$img;
    if(is_file($file))
        $size = @getimagesize($file);

    if (! (isset($size) && is_array($size)))
        return '';

    if($size[2] < 1 || $size[2] > 3)
        return '';

    $img_width = $size[0];
    $img_height = $size[1];
    $filename = basename($file);
    $filepath = dirname($file);

    if($img_width && !$height) {
        $height = round(($width * $img_height) / $img_width);
    }

    $thumb = thumbnail($filename, $filepath, $filepath, $width, $height, false, $is_crop, 'center', false, $um_value='80/0.5/3');

    if($thumb) {
        $file_url = str_replace(G5_PATH, G5_URL, $filepath.'/'.$thumb);
        $str = '<img src="'.$file_url.'" width="'.$width.'" height="'.$height.'"';
        if($id)
            $str .= ' id="'.$id.'"';
        $str .= ' alt="">';
    }

    return $str;
}

function get_star2($score2)
{
    $star2 = round($score2, 1);
    if ($star2 < 0) $star2 = 0;
    return $star2;
}

// 별 카운트
function get_star_image2($it_id)
{
    global $g5;

    $sql2 = "select (SUM(is_score) / COUNT(*)) as score from {$g5['g5_shop_item_use_table']} where it_id = '$it_id' and is_confirm = 1 ";
    $row2 = sql_fetch($sql2);

    return get_star2($row2['score']);
}


// 패턴의 내용대로 해당 디렉토리에서 정렬하여 <select> 태그에 적용할 수 있게 반환
function rb_list_skin_options($pattern, $dirname='./', $sval='')
{
    $str = '<option value="">출력 스킨을 선택하세요.</option>'.PHP_EOL;

    unset($arr);
    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if (preg_match("/$pattern/", $file, $matches)) {
            $arr[] = $matches[0];
        }
    }
    closedir($handle);

    sort($arr);
    foreach($arr as $value) {
        if($value == $sval)
            $selected = ' selected="selected"';
        else
            $selected = '';

        $str .= '<option value="'.$value.'"'.$selected.'>'.$value.'</option>'.PHP_EOL;
    }

    return $str;
}

// 파일이 있는지 검사
function rb_list_skin_options_chk($pattern, $dirname = './', $selected_value = '')
{
    $file_exists = false;
    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if (preg_match("/$pattern/", $file) && $file == $selected_value) {
            $file_exists = true;
            break;
        }
    }
    closedir($handle);

    return $file_exists;
}

// 카테고리명 출력
function get_category_name($ca_id) {
    global $g5;
    $sql = "SELECT ca_name FROM {$g5['g5_shop_category_table']} WHERE ca_id = '".sql_real_escape_string($ca_id)."'";
    $row = sql_fetch($sql);
    return isset($row['ca_name']) ? $row['ca_name'] : $ca_id;
}
