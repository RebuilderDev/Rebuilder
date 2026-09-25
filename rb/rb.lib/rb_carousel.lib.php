<?php
if (!defined('_GNUBOARD_')) exit;

function rb_carousel_mobile_fields()
{
    return array(
        'main_align'=>array('정렬','align'), 'main_weight'=>array('굵기','weight'),
        'main_color'=>array('컬러','color'), 'main_size'=>array('크기','number',12,80),
        'sub_align'=>array('정렬','align'), 'sub_weight'=>array('굵기','weight'),
        'sub_color'=>array('컬러','color'), 'sub_size'=>array('크기','number',12,50), 'sub_margin'=>array('상단간격','number',0,200),
        'btn_bg_color'=>array('배경 컬러','color'), 'btn_text_color'=>array('텍스트 컬러','color'),
        'btn_border_color'=>array('테두리 컬러','color'), 'btn_border'=>array('테두리 두께','number',0,20),
        'btn_padding'=>array('상하여백','number',0,100), 'btn_padding_lr'=>array('좌우여백','number',0,100),
        'btn_margin'=>array('상단간격','number',0,200), 'btn_size'=>array('크기','number',12,50),
        'btn_radius'=>array('모서리 라운드','number',0,100), 'btn_align'=>array('정렬','align'), 'btn_weight'=>array('텍스트 굵기','weight')
    );
}

// 빈 값은 기존 반응형 CSS를 사용한다. 0은 지정된 모바일 값으로 보존한다.
function rb_carousel_mobile_settings($raw, $strict=false)
{
    if(is_string($raw)) $raw=$raw===''?array():json_decode($raw,true);
    if(!is_array($raw)) {
        if($strict) throw new InvalidArgumentException('Mobile 설정 형식을 확인해 주세요.');
        return array();
    }
    $clean=array();
    foreach(rb_carousel_mobile_fields() as $key=>$field) {
        if(!isset($raw[$key]) || $raw[$key]==='') continue;
        $value=is_scalar($raw[$key]) && !is_bool($raw[$key])?trim((string)$raw[$key]):null;
        if($value==='') continue;
        $valid=false;
        if($value!==null) switch($field[1]) {
            case 'number': $valid=preg_match('/\A[0-9]+\z/',$value) && (int)$value>=$field[2] && (int)$value<=$field[3]; if($valid) $value=(int)$value; break;
            case 'color': $valid=(bool)preg_match('/\A#(?:[a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})\z/i',$value); break;
            case 'align': $valid=in_array($value,array('left','center','right'),true); break;
            case 'weight': $valid=in_array($value,array('font-R','font-B','font-H'),true); break;
        }
        if($valid) $clean[$key]=$value;
        elseif($strict) throw new InvalidArgumentException('Mobile '.$field[0].' 값을 확인해 주세요.');
    }
    return $clean;
}

// DB 구조 변경은 공식 API를 사용하는 관리자 DB 업데이트에서만 처리한다.
function rb_carousel_has_mobile_column()
{
    $columns=sql_query("SHOW COLUMNS FROM `rb_theme_carousel` LIKE 'mobile_settings'",false);
    return $columns && (bool)sql_fetch_array($columns);
}

function rb_carousel_mobile_attributes($raw)
{
    $values=rb_carousel_mobile_settings($raw); if(!$values) return '';
    $fields=rb_carousel_mobile_fields(); $style=array();
    foreach($values as $key=>$value) $style[]='--rb-carousel-'.str_replace('_','-',$key).':'.$value.($fields[$key][1]==='number'?'px':'');
    return ' data-rb-carousel-mobile="'.implode(' ',array_keys($values)).'" style="'.htmlspecialchars(implode(';',$style),ENT_QUOTES,'UTF-8').'"';
}

function rb_carousel_mobile_controls($section)
{
    $fields=rb_carousel_mobile_fields();
    $rows=array(
        'main'=>array(array('main_align','main_weight'),array('main_color','main_size')),
        'sub'=>array(array('sub_align','sub_weight'),array('sub_color','sub_size'),array('sub_margin')),
        'btn'=>array(array('btn_bg_color','btn_text_color'),array('btn_border_color','btn_border'),
            array('btn_padding','btn_padding_lr'),array('btn_margin'),array('btn_size','btn_radius'),array('btn_align','btn_weight'))
    );
    if(!isset($rows[$section])) return;
    foreach($rows[$section] as $row) {
        echo count($row)>1?'<div class="cm_field_row">':'<div>';
        foreach($row as $key) {
            $field=$fields[$key];
            $id='cm_mobile_'.$key;
            $attr=' id="'.$id.'" name="mobile_settings['.$key.']" data-cm-mobile-field="'.$key.'"';
            echo '<div>';
            if($field[1]==='number') {
                echo '<span class="cm_field_label"><span id="'.$id.'_label">'.$field[0].'</span> <span id="'.$id.'_val" class="font-B">자동</span><span id="'.$id.'_unit" hidden>px</span>';
                echo '<button type="button" class="cm_mobile_range_reset" data-cm-mobile-reset="'.$key.'" aria-label="'.$field[0].' 자동 설정으로 되돌리기" hidden>자동</button></span>';
                echo '<div id="'.$id.'_range" class="rb_range_item" data-cm-mobile-range="'.$key.'" data-min="'.$field[2].'" data-max="'.$field[3].'"></div>';
                echo '<input type="hidden"'.$attr.' value="">';
                echo '</div>';
                continue;
            }
            echo '<label class="cm_field_label" for="'.$id.'">'.$field[0].'</label>';
            if($field[1]==='align' || $field[1]==='weight') {
                echo '<select class="select"'.$attr.'><option value="">자동</option>';
                $options=$field[1]==='align'?array('left'=>'좌측','center'=>'중앙','right'=>'우측'):array('font-R'=>'Regular','font-B'=>'Bold','font-H'=>'Heavy');
                foreach($options as $value=>$text) echo '<option value="'.$value.'">'.$text.'</option>';
                echo '</select>';
            } else echo '<div class="cm_color_row"><span class="cm_color_swatch" id="'.$id.'_swatch"></span><input type="text" class="coloris-modal"'.$attr.' value="" placeholder="자동" autocomplete="off"></div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
