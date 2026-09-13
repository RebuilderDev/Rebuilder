<?php
$sub_menu = '000100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
include_once(G5_PATH.'/rb/rb.config/layout.cache.php');

// The front panel and this form share the same tables and field names.
// Save all visible settings together; omitted fields and disabled scopes are preserved.
function rbcfg_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rbcfg_q($sql) {
    $result = sql_query($sql, false);
    if (!$result) throw new RuntimeException('설정을 처리하지 못했습니다. 빌더설정의 DB업데이트와 데이터베이스 상태를 확인해 주세요.');
    return $result;
}
function rbcfg_rows($sql) {
    $result = sql_query($sql, false); $rows = array();
    while ($result && $row = sql_fetch_array($result)) $rows[] = $row;
    return $rows;
}
function rbcfg_set($values) {
    $parts = array();
    foreach ($values as $key => $value) $parts[] = '`'.$key."`='".sql_real_escape_string((string)$value)."'";
    return implode(', ', $parts);
}
function rbcfg_field($label, $type, $default, $extra = array()) {
    return array_merge(array('label'=>$label, 'type'=>$type, 'default'=>$default), $extra);
}
function rbcfg_dirs($directory, $shop = false) {
    $options = array(''=>'기본값 사용');
    if (defined('G5_THEME_PATH') && function_exists('rb_skin_dir')) {
        foreach (rb_skin_dir($directory, G5_THEME_PATH.($shop ? '/shop/' : '/')) as $name) $options[$name] = $name;
    }
    return $options;
}
function rbcfg_fields($shop_enabled) {
    $widths = array(''=>'기본값 (1400px)', '1400'=>'1400px', '1280'=>'1280px', '1024'=>'1024px', '960'=>'960px', '750'=>'750px');
    $groups = array('common'=>array(
        'co_color'=>rbcfg_field('강조컬러 설정 (공용)', 'color', '#aa20ff', array('help'=>'버튼, 뱃지, 오버 등 강조되는 컬러를 설정할 수 있습니다.')),
        'co_header'=>rbcfg_field('헤더컬러 설정 (공용)', 'color', '#ffffff', array('help'=>'헤더 배경색의 실제 밝기를 자동으로 계산합니다.<br>밝은 배경에는 강조컬러와 기본 로고, 어두운 배경에는 흰색 텍스트와 흰색 로고가 적용됩니다.<br>투명도가 포함된 색상도 화면에 표시되는 색상을 기준으로 판별합니다.')),
        'co_main_bg'=>rbcfg_field('메인 배경컬러 설정', 'color', '#ffffff', array('help'=>'메인페이지 백그라운드 컬러를 설정할 수 있습니다.')),
        'co_sub_bg'=>rbcfg_field('서브 배경컬러 설정', 'color', '#ffffff', array('help'=>'서브페이지 전체 백그라운드 컬러를 설정할 수 있습니다.')),
        'co_gap_pc'=>rbcfg_field('모듈간격 설정 (공용)', 'number', '0', array('min'=>0, 'max'=>30, 'step'=>5, 'unit'=>'px', 'help'=>'각 모듈들의 간격을 일괄 설정할 수 있습니다.<br>설정된 간격은 모바일 및 모바일 헤더 영역에 동일하게 적용 됩니다.<br>삽입된 모듈간의 간격을 일괄 조정할 수 있어요. 설정된 간격은 모바일 및 모바일 헤더 영역에 동일하게 적용 되요.')),
        'co_tb_width'=>rbcfg_field('상단/하단 가로폭 설정 (공용)', 'select', '1400', array('options'=>$widths + array('100'=>'100%'), 'help'=>'상단/하단, 메인 및 서브 컨텐츠 영역의 가로폭을 설정해주세요.<br>설정이 없는 경우 1400px 으로 고정 됩니다.')),
        'co_main_width'=>rbcfg_field('메인 가로폭 설정 (공용)', 'select', '1400', array('options'=>$widths)),
        'co_sub_width'=>rbcfg_field('서브 가로폭 설정 (공용)', 'select', '1400', array('options'=>$widths)),
        'co_font'=>rbcfg_field('웹폰트 설정 (공용)', 'select', 'Pretendard', array('options'=>rbcfg_dirs('rb.fonts'), 'help'=>'선택하신 폰트가 웹사이트 전체에 적용 됩니다.<br>웹폰트 세트를 자유롭게 추가할 수 있습니다.')),
    ));
    foreach (array('general', 'market') as $scope) {
        if ($scope === 'market' && !$shop_enabled) continue;
        $suffix = $scope === 'market' ? '_shop' : '';
        $fields = array();
        foreach (array('co_layout'=>'메인 레이아웃', 'co_layout_hd'=>'헤더 레이아웃', 'co_layout_ft'=>'푸터 레이아웃') as $key=>$label) {
            $fields[$key.$suffix] = rbcfg_field($label, 'select', $key==='co_layout_hd' && $suffix ? 'basic_row' : 'basic', array('options'=>rbcfg_dirs(str_replace('co_', 'rb.', $key), (bool)$suffix), 'help'=>$key==='co_layout' ? '메인, 헤더, 푸터 레이아웃을 설정 합니다.<br>레아아웃 세트는 자유롭게 추가할 수 있습니다.' : ''));
        }
        if ($suffix) $fields['co_menu_shop'] = rbcfg_field('마켓 헤더 메뉴설정', 'select', '0', array('options'=>array('0'=>'기본', '1'=>'카테고리', '2'=>'카테고리+기본'), 'help'=>'헤더 메뉴 구성을 상품 카테고리로 자동설정 할 수 있어요.'));
        foreach (array('co_padding_top'=>'메인 상단 여백', 'co_padding_top_sub'=>'서브 상단 여백', 'co_padding_btm'=>'메인 하단 여백', 'co_padding_btm_sub'=>'서브 하단 여백') as $key=>$label) {
            $fields[$key.$suffix] = rbcfg_field($label.' (PC)', 'number', '', array('unit'=>'px', 'blank'=>true, 'help'=>'PC버전 상/하단 여백을 설정할 수 있습니다.<br>0을 입력하시는 경우 여백이 제거 됩니다. (설정값-모듈간격)<br>값이 없으면 기본값이 들어갑니다.'));
        }
        $groups[$scope] = $fields;
    }
    return $groups;
}
function rbcfg_aos_fields($shop = false) {
    $suffix = $shop ? '_shop' : '';
    $motions = array(''=>'모션선택');
    foreach (array('fade','fade-up','fade-down','fade-left','fade-right','fade-up-right','fade-up-left','fade-down-right','fade-down-left','flip-up','flip-down','flip-left','flip-right','zoom-in','zoom-in-up','zoom-in-down','zoom-in-left','zoom-in-right','zoom-out','zoom-out-up','zoom-out-down','zoom-out-left','zoom-out-right') as $motion) $motions[$motion] = $motion;
    $fields = array(
        'rb_aos_use'=>rbcfg_field('AOS 사용여부', 'select', '0', array('options'=>array('0'=>'사용안함', '1'=>'사용함'), 'help'=>'모듈에 AOS를 일괄 적용 합니다.<br>AOS는 메인, 그룹, 일반 페이지에 적용 됩니다.<br>개별 모듈에 적용된 AOS가 우선 적용됩니다.<br>페이지를 스크롤할 때, 각 모듈에 부드러운 애니메이션을 추가할 수 있어요.')),
        'rb_aos_motion'=>rbcfg_field('AOS 모션타입', 'select', 'fade-up', array('options'=>$motions)),
        'rb_aos_delay'=>rbcfg_field('AOS 지연시간', 'number', '50', array('min'=>0, 'max'=>1000, 'step'=>50, 'unit'=>'ms')),
        'rb_aos_duration'=>rbcfg_field('AOS 모션속도', 'number', '500', array('min'=>0, 'max'=>1000, 'step'=>50, 'unit'=>'ms')),
        'rb_aos_once'=>rbcfg_field('AOS 옵션', 'select', '0', array('options'=>array('0'=>'스크롤할 때마다 적용', '1'=>'스크롤시 최초 1회만 적용'))),
    );
    $out = array(); foreach ($fields as $key=>$field) $out[$key.$suffix] = $field;
    return $out;
}
function rbcfg_values($fields, $stored) {
    $values = array();
    foreach ($fields as $key=>$field) $values[$key] = isset($stored[$key]) ? (string)$stored[$key] : (string)$field['default'];
    return $values;
}
function rbcfg_validate($fields, $posted, $stored) {
    $values = array();
    foreach ($fields as $key=>$field) {
        if (!array_key_exists($key, $posted) || !is_scalar($posted[$key])) throw new RuntimeException($field['label'].' 값을 확인해 주세요.');
        $value = trim((string)$posted[$key]);
        // Preserve existing custom values when saving unrelated controls.
        if ($field['type'] !== 'number' && isset($stored[$key]) && $value === (string)$stored[$key]) { $values[$key] = $value; continue; }
        if ($field['type'] === 'select' && !array_key_exists($value, $field['options'])) throw new RuntimeException($field['label'].' 선택값이 올바르지 않습니다.');
        if ($field['type'] === 'number' && !($value === '' && !empty($field['blank']))) {
            if (!preg_match('/^-?\d{1,9}$/D', $value) || (isset($field['min']) && (int)$value < $field['min']) || (isset($field['max']) && (int)$value > $field['max'])) throw new RuntimeException($field['label'].' 입력 범위를 확인해 주세요.');
            if (isset($field['step']) && (((int)$value - ($field['min'] ?? 0)) % $field['step']) !== 0) throw new RuntimeException($field['label'].'은 '.$field['step'].' 단위로 입력해 주세요.');
        }
        if ($field['type'] === 'color') {
            if ($value === '') $value = $field['default'];
            if (!preg_match('/^(?:#[0-9a-f]{3,4}|#[0-9a-f]{6}|#[0-9a-f]{8}|transparent)$/iD', $value)) throw new RuntimeException($field['label'].'은 컬러피커에서 선택해 주세요.');
        }
        $values[$key] = $value;
    }
    return $values;
}
function rbcfg_check_columns($table, $values) {
    $columns = array_column(rbcfg_rows('SHOW COLUMNS FROM `'.$table.'`'), 'Field');
    foreach (array_keys($values) as $key) if (!in_array($key, $columns, true)) throw new RuntimeException('빌더설정 > DB업데이트를 먼저 실행해 주세요.');
}
function rbcfg_save_theme($theme, $values, $stored) {
    $values['co_datetime'] = G5_TIME_YMDHIS;
    $values['co_ip'] = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
    rbcfg_check_columns('rb_config', $values);
    if (!empty($stored['co_id'])) {
        rbcfg_q('UPDATE rb_config SET '.rbcfg_set($values)." WHERE co_theme='".sql_real_escape_string($theme)."'");
    } else {
        // Required legacy columns have no DB default on some installations.
        $values += array('co_theme'=>$theme, 'co_sub_width'=>'1400', 'co_main_width'=>'1400', 'co_tb_width'=>'1400', 'co_footer'=>'0');
        rbcfg_q('INSERT INTO rb_config SET '.rbcfg_set($values));
    }
    rb_layout_cache_delete_all();
}
function rbcfg_save_aos($table, $scope, $values) {
    $suffix = $scope === 'market' ? '_shop' : '';
    $map = array('use'=>'ra_use', 'motion'=>'ra_aos', 'delay'=>'ra_delay', 'duration'=>'ra_duration', 'once'=>'ra_once');
    $row = array(); foreach ($map as $key=>$column) $row[$column] = $values['rb_aos_'.$key.$suffix];
    $row['ra_updated_at'] = G5_TIME_YMDHIS;
    rbcfg_check_columns($table, $row);
    $current = sql_fetch('SELECT ra_id FROM `'.$table."` WHERE ra_type='".$scope."' LIMIT 1", false);
    if ($current) rbcfg_q('UPDATE `'.$table.'` SET '.rbcfg_set($row)." WHERE ra_type='".$scope."'");
    else {
        $row += array('ra_type'=>$scope, 'ra_offset'=>'-300', 'ra_easing'=>'ease', 'ra_mirror'=>'0', 'ra_anchor_placement'=>'top-center');
        rbcfg_q('INSERT INTO `'.$table.'` SET '.rbcfg_set($row));
    }
}
function rbcfg_save_all($theme, $groups, $posted, $stored, $aos_table, $aos_ready) {
    $values = array(); $aos_values = array();
    // Validate every tab before writing anything.
    foreach ($groups as $scope=>$fields) {
        $values += rbcfg_validate($fields, $posted, $stored);
        if ($scope !== 'common' && $aos_ready) {
            $suffix = $scope === 'market' ? '_shop' : '';
            $row = sql_fetch('SELECT * FROM `'.$aos_table."` WHERE ra_type='".$scope."' LIMIT 1", false);
            $current = array();
            foreach (array('use'=>'ra_use','motion'=>'ra_aos','delay'=>'ra_delay','duration'=>'ra_duration','once'=>'ra_once') as $key=>$column) {
                if (isset($row[$column])) $current['rb_aos_'.$key.$suffix] = $row[$column];
            }
            $aos_values[$scope] = rbcfg_validate(rbcfg_aos_fields((bool)$suffix), $posted, $current);
        }
    }
    rbcfg_check_columns('rb_config', $values);
    rbcfg_q('START TRANSACTION');
    try {
        foreach ($aos_values as $scope=>$aos) rbcfg_save_aos($aos_table, $scope, $aos);
        // rb_config may use MyISAM. Update it once, after the transactional AOS writes.
        rbcfg_save_theme($theme, $values, $stored);
        rbcfg_q('COMMIT');
    } catch (Throwable $e) { sql_query('ROLLBACK', false); throw $e; }
}
function rbcfg_render_rows($fields, $values) {
    foreach ($fields as $key=>$field) {
        $value = $values[$key];
        echo '<tr><th scope="row"><label for="'.rbcfg_h($key).'">'.rbcfg_h($field['label']).'</label></th><td>';
        if (!empty($field['help'])) echo help($field['help']);
        if ($field['type'] === 'select') {
            $options = $field['options'];
            if (!array_key_exists($value, $options)) $options = array($value=>$value.' (현재 설정)') + $options;
            echo '<select name="'.rbcfg_h($key).'" id="'.rbcfg_h($key).'">';
            foreach ($options as $option=>$label) echo '<option value="'.rbcfg_h($option).'"'.((string)$option===(string)$value ? ' selected' : '').'>'.rbcfg_h($label).'</option>';
            echo '</select>';
        } else {
            if ($field['type']==='color') echo '<div class="color_set_wrap square" data-native-ui>';
            echo '<input type="'.($field['type']==='number' ? 'number' : 'text').'" class="frm_input'.($field['type']==='color' ? ' coloris mod_co_color' : '').'" name="'.rbcfg_h($key).'" id="'.rbcfg_h($key).'" value="'.rbcfg_h($value).'" size="20"';
            foreach (array('min','max','step') as $attr) if (isset($field[$attr])) echo ' '.$attr.'="'.rbcfg_h($field[$attr]).'"';
            if ($field['type']==='color') echo ' maxlength="20" autocomplete="off"';
            echo '>'.(!empty($field['unit']) ? ' '.rbcfg_h($field['unit']) : '');
            if ($field['type']==='color') echo '</div>';
        }
        echo '</td></tr>';
    }
}

$rbcfg_shop = defined('G5_USE_SHOP') && G5_USE_SHOP;
$rbcfg_theme = isset($config['cf_theme']) ? (string)$config['cf_theme'] : '';
if ($rbcfg_theme === '' || !defined('G5_THEME_PATH')) alert('빌더에서 사용할 테마를 먼저 적용해 주세요.', G5_ADMIN_URL.'/theme.php');
$rbcfg_groups = rbcfg_fields($rbcfg_shop);
$rbcfg_stored = sql_fetch("SELECT * FROM rb_config WHERE co_theme='".sql_real_escape_string($rbcfg_theme)."' LIMIT 1", false);
if (!is_array($rbcfg_stored)) $rbcfg_stored = array();
$rbcfg_aos_table = (defined('RB_TABLE_PREFIX') ? RB_TABLE_PREFIX : 'rb_').'aos';
$rbcfg_aos_ready = (bool)sql_query('SELECT ra_type FROM `'.$rbcfg_aos_table.'` LIMIT 0', false);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_demo();
    check_admin_token();
    if (!isset($_POST['co_theme']) || !is_string($_POST['co_theme']) || $_POST['co_theme'] !== $rbcfg_theme) alert('적용 테마가 변경되었습니다. 화면을 새로고침해 주세요.');
    if (!isset($_POST['config_scope']) || $_POST['config_scope'] !== 'all') alert('환경설정 화면을 새로고침한 후 다시 저장해 주세요.');
    try {
        rbcfg_save_all($rbcfg_theme, $rbcfg_groups, $_POST, $rbcfg_stored, $rbcfg_aos_table, $rbcfg_aos_ready);
    } catch (Throwable $e) { alert($e->getMessage()); }
    goto_url('./config_form.php');
}

add_stylesheet('<link rel="stylesheet" href="./css/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/rb/rb.config/coloris/coloris.css">', 0);
add_javascript('<script src="'.G5_URL.'/rb/rb.config/coloris/coloris.js"></script>', 0);
$g5['title'] = '환경설정';
include_once(G5_ADMIN_PATH.'/admin.head.php');
$rbcfg_token = get_admin_token();
$rbcfg_titles = array('common'=>'공용 설정', 'general'=>'커뮤니티 설정', 'market'=>'마켓 설정');
$pg_anchor = '<ul class="anchor">';
foreach ($rbcfg_groups as $scope=>$fields) {
    $pg_anchor .= '<li><a href="#config_'.$scope.'">'.rbcfg_h($rbcfg_titles[$scope]).'</a></li>';
}
$pg_anchor .= '<li><a href="#config_tools">사이트 관리</a></li></ul>';
?>
<style>
#rbcfg_form input.coloris.mod_co_color,
#rbcfg_form input.coloris.mod_co_color:focus {
    background: transparent !important;
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
}
</style>
<div class="local_desc01 local_desc"><p>웹사이트의 전반적인 환경설정 입니다.<br>현재 적용 테마: <strong><?php echo rbcfg_h($rbcfg_theme); ?></strong>. 프런트 환경설정과 같은 값이 적용됩니다. 각 탭에서 수정한 내용은 확인 버튼을 누르면 함께 저장됩니다.</p></div>
<form id="rbcfg_form" action="./config_form.php" method="post">
    <input type="hidden" name="token" value="<?php echo rbcfg_h($rbcfg_token); ?>">
    <input type="hidden" name="co_theme" value="<?php echo rbcfg_h($rbcfg_theme); ?>">
    <input type="hidden" name="config_scope" value="all">
<?php foreach ($rbcfg_groups as $scope=>$fields) { ?>
<section id="config_<?php echo $scope; ?>">
    <h2 class="h2_frm"><?php echo rbcfg_h($rbcfg_titles[$scope]); ?></h2>
    <?php echo $pg_anchor; ?>
        <div class="tbl_frm01 tbl_wrap"><table><caption><?php echo rbcfg_h($rbcfg_titles[$scope]); ?></caption><colgroup><col class="grid_4"><col></colgroup><tbody>
            <?php rbcfg_render_rows($fields, rbcfg_values($fields, $rbcfg_stored)); ?>
            <?php if ($scope !== 'common' && $rbcfg_aos_ready) {
                $suffix = $scope==='market' ? '_shop' : '';
                $aos_row = sql_fetch('SELECT * FROM `'.$rbcfg_aos_table."` WHERE ra_type='".$scope."' LIMIT 1", false);
                $aos_current = array(); foreach (array('use'=>'ra_use','motion'=>'ra_aos','delay'=>'ra_delay','duration'=>'ra_duration','once'=>'ra_once') as $key=>$column) if (isset($aos_row[$column])) $aos_current['rb_aos_'.$key.$suffix] = $aos_row[$column];
                $aos_fields = rbcfg_aos_fields((bool)$suffix); rbcfg_render_rows($aos_fields, rbcfg_values($aos_fields, $aos_current));
            } ?>
        </tbody></table></div>
        <?php if ($scope !== 'common' && !$rbcfg_aos_ready) { ?><div class="local_desc01 local_desc"><p>AOS 설정은 빌더설정의 DB업데이트 후 사용할 수 있습니다.</p></div><?php } ?>
</section>
<?php } ?>
<section id="config_tools">
    <h2 class="h2_frm">사이트 관리</h2>
    <?php echo $pg_anchor; ?>
    <div class="tbl_frm01 tbl_wrap"><table><caption>사이트맵 및 캐시 관리</caption><colgroup><col class="grid_4"><col></colgroup><tbody>
        <tr><th scope="row">사이트맵(XML)</th><td><?php echo help('버튼을 클릭하시면 루트에 sitemap.xml 파일이 생성 됩니다.<br>생성 완료 시 사이트맵 다운로드 버튼이 활성화 되며, 빌더설정 > SEO관리 robots.txt 에 자동으로 등록 됩니다.'); ?><button type="button" id="rbcfg_sitemap" class="btn btn_02">사이트맵 생성</button> <a id="rbcfg_sitemap_download" class="btn btn_02" href="<?php echo rbcfg_h(G5_URL.'/rb/sitemap.php?download=1'); ?>" download="sitemap.xml" hidden>사이트맵 내려받기</a></td></tr>
        <tr><th scope="row">캐시 삭제</th><td><?php echo help('저장된 캐시를 삭제합니다. 메인 레이아웃 캐시는 비로그인 방문 시 다시 생성됩니다.'); ?><button type="button" id="rbcfg_cache" class="btn btn_02">캐시 삭제</button></td></tr>
    </tbody></table></div>
    <div class="local_desc01 local_desc" id="rbcfg_tool_status" role="status" hidden><p></p></div>
</section>
<div class="local_desc01 local_desc"><p>서브페이지 개별설정은 각 페이지에서 설정해주세요.</p></div>
<div class="btn_fixed_top btn_confirm"><input type="submit" value="확인" class="btn_submit btn" accesskey="s"></div>
</form>
<script>
$(function () {
    if (typeof Coloris === 'function') {
        Coloris({el:'.coloris', format:'hex', formatToggle:false, alpha:true, clearButton:true, closeButton:true, closeLabel:'닫기', swatches:['#AA20FF','#FFC700','#00A3FF','#8ED100','#FF5A5A','#25282B','#FFFFFF00','#FFFFFF']});
    }
    var toolStatus = document.getElementById('rbcfg_tool_status');
    function message(text) { toolStatus.hidden=false; toolStatus.querySelector('p').textContent=text; }
    async function confirmed(text) { return typeof window.rb_confirm === 'function' ? await window.rb_confirm(text) : window.confirm(text); }
    async function runTool(button, url, body, isJson) {
        button.disabled=true;
        try {
            var response=await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body});
            if(!response.ok)throw new Error('요청을 처리하지 못했습니다. 관리자 로그인 상태를 확인해 주세요.');
            var result=isJson?await response.json():(await response.text()).trim();
            if(isJson ? !result.success : result!=='ok')throw new Error(isJson?(result.msg||'사이트맵 생성에 실패했습니다.'):result);
            return result;
        } finally { button.disabled=false; }
    }
    document.getElementById('rbcfg_sitemap').addEventListener('click',async function(){
        try { message('사이트맵을 생성하고 있습니다.');await runTool(this,<?php echo json_encode(G5_URL.'/rb/sitemap.php'); ?>,'',true);document.getElementById('rbcfg_sitemap_download').hidden=false;message('사이트맵을 생성했습니다. 내려받기 버튼으로 XML 파일을 저장할 수 있습니다.'); } catch(e) { message(e.message); }
    });
    document.getElementById('rbcfg_cache').addEventListener('click',async function(){
        if(!await confirmed('저장된 캐시를 삭제하시겠습니까? 방문 시 필요한 캐시가 다시 생성됩니다.'))return;
        try { await runTool(this,<?php echo json_encode(G5_URL.'/rb/rb.config/ajax.clear_cache.php'); ?>,'act=clear',false);message('캐시를 삭제했습니다.'); } catch(e) { message(e.message); }
    });
    // Warn only when navigating away with edits that have not been submitted.
    var changed=false, submitted=false;
    $('#rbcfg_form').on('input change',function(){changed=true;}).on('submit',function(){submitted=true;});
    window.addEventListener('beforeunload',function(event){if(changed&&!submitted){event.preventDefault();event.returnValue='';}});
});
</script>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
