<?php
$sub_menu = '';
include_once('./_common.php');

$g5['title'] = 'DB 관리툴';
include_once(G5_ADMIN_PATH.'/admin.head.php');

$mode  = isset($_GET['mode']) ? trim((string)$_GET['mode']) : 'tables';
$table = isset($_GET['table']) ? trim((string)$_GET['table']) : '';

$sfl = isset($_GET['sfl']) ? trim((string)$_GET['sfl']) : 'table_name';
$stx = isset($_GET['stx']) ? trim((string)$_GET['stx']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$per_page = 50;

if (!in_array($mode, array('tables', 'columns', 'data', 'data_edit', 'data_add'), true)) $mode = 'tables';
if (!in_array($sfl, array('table_name', 'engine', 'comment'), true)) $sfl = 'table_name';

if (!function_exists('rb_is_safe_ident')) {
    function rb_is_safe_ident($s) {
        $s = (string)$s;
        if ($s === '') return false;
        return (bool)preg_match('/^[a-zA-Z0-9_]+$/', $s);
    }
}

if (!function_exists('rb_build_qs')) {
    function rb_build_qs($params, $amp = true) {
        $pairs = array();
        foreach ((array)$params as $k => $v) {
            if ($v === null) continue;
            $v = (string)$v;
            if ($v === '') continue;
            $pairs[] = rawurlencode((string)$k).'='.rawurlencode($v);
        }
        $glue = $amp ? '&amp;' : '&';
        return implode($glue, $pairs);
    }
}

if (!function_exists('rb_dbtool_type_presets')) {
    function rb_dbtool_type_presets() {
        return array(
            'varchar_255' => array('label' => '문자열 (VARCHAR 255)', 'type' => 'VARCHAR(255)'),
            'char_20' => array('label' => '짧은코드 (CHAR 20)', 'type' => 'CHAR(20)'),
            'int_11' => array('label' => '정수 (INT 11)', 'type' => 'INT(11)'),
            'bigint_20' => array('label' => '큰숫자 (BIGINT 20)', 'type' => 'BIGINT(20)'),
            'decimal_10_2' => array('label' => '금액/소수 (DECIMAL 10,2)', 'type' => 'DECIMAL(10,2)'),
            'tinyint_1' => array('label' => '참/거짓 (TINYINT 1)', 'type' => 'TINYINT(1)'),
            'text' => array('label' => '긴글 (TEXT)', 'type' => 'TEXT'),
            'mediumtext' => array('label' => '아주긴글 (MEDIUMTEXT)', 'type' => 'MEDIUMTEXT'),
            'date' => array('label' => '날짜 (DATE)', 'type' => 'DATE'),
            'datetime' => array('label' => '일시 (DATETIME)', 'type' => 'DATETIME'),
            'timestamp' => array('label' => '타임스탬프 (TIMESTAMP)', 'type' => 'TIMESTAMP'),
            'custom' => array('label' => '직접입력', 'type' => ''),
        );
    }
}

if (!function_exists('rb_dbtool_type_preset_key')) {
    function rb_dbtool_type_preset_key($type) {
        $type = strtoupper(trim((string)$type));
        foreach (rb_dbtool_type_presets() as $key => $preset) {
            if ($key === 'custom') continue;
            if (strtoupper((string)$preset['type']) === $type) return $key;
        }
        return 'custom';
    }
}

if (!function_exists('rb_dbtool_input_meta')) {
    function rb_dbtool_input_meta($type) {
        $type = strtolower(trim((string)$type));

        if (strpos($type, 'text') !== false || strpos($type, 'blob') !== false) {
            return array('kind' => 'textarea', 'input_type' => 'text', 'placeholder' => '긴 내용을 입력하세요');
        }
        if (strpos($type, 'date') === 0 && strpos($type, 'datetime') === false) {
            return array('kind' => 'input', 'input_type' => 'text', 'placeholder' => '예: 2026-04-03');
        }
        if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
            return array('kind' => 'input', 'input_type' => 'text', 'placeholder' => '예: 2026-04-03 14:30:00');
        }
        if (strpos($type, 'tinyint(1)') !== false) {
            return array('kind' => 'input', 'input_type' => 'number', 'placeholder' => '0 또는 1');
        }
        if (preg_match('/(int|decimal|float|double|numeric|real)/', $type)) {
            return array('kind' => 'input', 'input_type' => 'number', 'placeholder' => '숫자만 입력');
        }
        if (preg_match('/char|varchar/', $type)) {
            return array('kind' => 'input', 'input_type' => 'text', 'placeholder' => '문자열 입력');
        }

        return array('kind' => 'input', 'input_type' => 'text', 'placeholder' => '값을 입력하세요');
    }
}

$qstr_common = rb_build_qs(array('sfl' => $sfl, 'stx' => $stx), true);

// 지원 엔진/문자셋(자주 쓰는 후보만)
$supported_engines = array();
$res_eng = sql_query("show engines");
while ($er = sql_fetch_array($res_eng)) {
    $name = trim((string)($er['Engine'] ?? ''));
    $sup  = strtoupper(trim((string)($er['Support'] ?? '')));
    if ($name === '') continue;
    if ($sup === 'YES' || $sup === 'DEFAULT') $supported_engines[$name] = true;
}

$supported_charsets = array();
$res_cs = sql_query("show character set");
while ($cr = sql_fetch_array($res_cs)) {
    $cs = trim((string)($cr['Charset'] ?? ''));
    if ($cs === '') continue;
    $supported_charsets[$cs] = array(
        'default_collation' => (string)($cr['Default collation'] ?? ''),
        'description'       => (string)($cr['Description'] ?? '')
    );
}

$top_collations = array();
$res_top_col = sql_query("SELECT TABLE_COLLATION, COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() GROUP BY TABLE_COLLATION ORDER BY cnt DESC LIMIT 2");
while ($tc = sql_fetch_array($res_top_col)) {
    if (!empty($tc['TABLE_COLLATION'])) $top_collations[] = (string)$tc['TABLE_COLLATION'];
}

$all_collations = array();
$res_col = sql_query("show collation");
while ($col = sql_fetch_array($res_col)) {
    $cname = trim((string)($col['Collation'] ?? ''));
    if ($cname !== '') $all_collations[] = $cname;
}

$engine_candidates  = array('InnoDB', 'MyISAM', 'Aria', 'MEMORY');
$charset_candidates = array('utf8mb4', 'utf8', 'euckr');

$engine_options = array();
foreach ($engine_candidates as $e) {
    if (isset($supported_engines[$e])) $engine_options[] = $e;
}
if (count($engine_options) === 0 && count($supported_engines) > 0) {
    foreach ($supported_engines as $k => $v) { $engine_options[] = $k; break; }
}

$charset_options = array();
foreach ($charset_candidates as $c) {
    if (isset($supported_charsets[$c])) $charset_options[] = $c;
}
if (count($charset_options) === 0) {
    if (isset($supported_charsets['utf8mb4'])) $charset_options[] = 'utf8mb4';
    else if (isset($supported_charsets['utf8'])) $charset_options[] = 'utf8';
    else {
        foreach ($supported_charsets as $k => $v) { $charset_options[] = $k; break; }
    }
}

// 테이블 목록
$res_all = sql_query("show table status");
if (!$res_all) {
    alert('테이블 목록을 가져오지 못했습니다.\\n'.(function_exists('sql_error') ? sql_error() : ''));
}

$tables = array();
$total_in_schema = 0;

while ($r = sql_fetch_array($res_all)) {
    $total_in_schema++;

    $tname = isset($r['Name']) ? (string)$r['Name'] : '';
    if ($tname === '') continue;

    if ($stx !== '') {
        $needle = (string)$stx;

        if ($sfl === 'table_name') {
            if (stripos($tname, $needle) === false) continue;
        } else if ($sfl === 'engine') {
            if (stripos((string)($r['Engine'] ?? ''), $needle) === false) continue;
        } else if ($sfl === 'comment') {
            if (stripos((string)($r['Comment'] ?? ''), $needle) === false) continue;
        }
    }

    $tables[] = $r;
}

$total_count = count($tables);

// 선택 테이블 검증
$selected_table_ok = false;
if ($table !== '' && rb_is_safe_ident($table)) $selected_table_ok = true;

// 컬럼 목록
$columns = array();
$column_count = 0;
$pk_cols = array();
$has_pk = false;

if (in_array($mode, array('columns','data','data_edit','data_add'), true) && $selected_table_ok) {
    $colres = sql_query("show full columns from `{$table}`");
    if ($colres) {
        while ($c = sql_fetch_array($colres)) {
            $columns[] = $c;
            if ((string)($c['Key'] ?? '') === 'PRI') {
                $pk_cols[] = (string)($c['Field'] ?? '');
            }
        }
        $column_count = count($columns);
        $has_pk = (count($pk_cols) > 0);
    }
}

// 데이터 목록/단건 로드
$data_rows = array();
$data_total = 0;
$edit_row = null;

if (in_array($mode, array('data','data_edit'), true) && $selected_table_ok) {
    // 총건수(대용량 대비 최소한의 페이징만)
    $cnt = sql_fetch("select count(*) as cnt from `{$table}`");
    $data_total = isset($cnt['cnt']) ? (int)$cnt['cnt'] : 0;

    $offset = ($page - 1) * $per_page;
    if ($offset < 0) $offset = 0;

    $order_by = '';
    if ($has_pk) {
        $ob = array();
        foreach ($pk_cols as $p) $ob[] = "`{$p}` asc";
        $order_by = ' order by '.implode(',', $ob);
    } else {
        // PK 없으면 첫 컬럼 기준
        if (isset($columns[0]['Field'])) $order_by = " order by `".(string)$columns[0]['Field']."` asc";
    }

    $rs = sql_query("select * from `{$table}`{$order_by} limit {$offset}, {$per_page}");
    while ($r = sql_fetch_array($rs)) $data_rows[] = $r;
}

if ($mode === 'data_edit' && $selected_table_ok) {
    // PK로 단건 조회
    if (!$has_pk) {
        alert('이 테이블은 PK가 없어 수정 기능을 사용할 수 없습니다.');
    }
    $where = array();
    foreach ($pk_cols as $p) {
        $v = isset($_GET['pk'][$p]) ? (string)$_GET['pk'][$p] : '';
        $v = sql_escape_string($v);
        $where[] = "`{$p}`='{$v}'";
    }
    $wsql = implode(' and ', $where);
    $edit_row = sql_fetch("select * from `{$table}` where {$wsql} limit 1");
    if (!$edit_row) {
        alert('데이터를 찾을 수 없습니다.');
    }
}

// 상단 탭(테이블관리만)
$tab_tables = ($mode === 'tables') ? 'style="background-color:#ff4081"' : '';
$listall = '<a href="./rb_dbtool.php" class="ov_listall" '.$tab_tables.'>테이블 관리 ('.number_format($total_in_schema).')</a>';

// 이 페이지 전용 토큰(ajax/팝업용)
$admin_token_js = '';
if (function_exists('get_session')) {
    $admin_token_js = (string)get_session('rb_dbtool_admin_token');
}
if ($admin_token_js === '') {
    try {
        $admin_token_js = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $admin_token_js = md5(uniqid((string)mt_rand(), true));
    }
    if (function_exists('set_session')) {
        set_session('rb_dbtool_admin_token', (string)$admin_token_js);
    }
}

// 보안 인증(세션) 상태 - 토큰 파일이 살아있는지는 update에서 최종 판단
$rb_auth_ok = (function_exists('get_session') && ((string)get_session('rb_dbtool_auth_ok') === '1'));
?>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    <?php if ($selected_table_ok && in_array($mode, array('columns','data','data_edit','data_add'), true)) { ?>
    <span class="btn_ov01">
        <span class="ov_txt">선택 테이블</span>
        <span class="ov_num"><?php echo get_text($table); ?></span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">컬럼 수</span>
        <span class="ov_num"><?php echo number_format((int)$column_count); ?>개</span>
    </span>
    <?php } ?>
</div>

<form name="flist" class="local_sch01 local_sch" method="get">
<input type="hidden" name="mode" value="tables">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="table_name"<?php echo get_selected($sfl, 'table_name', true); ?>>테이블명</option>
    <option value="engine"<?php echo get_selected($sfl, 'engine', true); ?>>엔진</option>
    <option value="comment"<?php echo get_selected($sfl, 'comment', true); ?>>코멘트</option>
</select>
<label for="stx" class="sound_only">검색어</label>
<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input">
<input type="submit" value="검색" class="btn_submit">
</form>

<?php if ($mode === 'tables') { ?>

<div class="local_desc01 local_desc">
    <p>
        테이블 및 컬럼, 컬럼데이터는 생성, 수정, 삭제시 DB접속정보와 토큰을 입력하셔야 합니다.<br>
        토큰은 /data/rb_dbtool/ 폴더에 생성일시_난수.txt 파일로 생성 됩니다. 생성 시 선택한 유효기간 동안 사용할 수 있습니다.
    </p>
</div>

<div id="sct" class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> - 테이블 목록</caption>
    <thead>
    <tr>
        <th scope="col">테이블명</th>
        <th scope="col">엔진</th>
        <th scope="col">데이터</th>
        <th scope="col">크기</th>
        <th scope="col">코멘트</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    foreach ($tables as $row) {
        $tname = isset($row['Name']) ? (string)$row['Name'] : '';
        if ($tname === '') continue;

        $bg = 'bg'.($i%2);

        $engine = get_text($row['Engine'] ?? '');
        $rows_cnt = (int)($row['Rows'] ?? 0);
        $data_len = (int)($row['Data_length'] ?? 0);
        $idx_len  = (int)($row['Index_length'] ?? 0);
        $size = $data_len + $idx_len;
        $size_txt = number_format((float)$size / 1024 / 1024, 2).' MB';
        $comment = get_text($row['Comment'] ?? '');

        $col_href = $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'columns','table'=>$tname,'sfl'=>$sfl,'stx'=>$stx), true);
        $data_href = $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'data','table'=>$tname,'page'=>1,'sfl'=>$sfl,'stx'=>$stx), true);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_code text-left"><?php echo get_text($tname); ?></td>
        <td><?php echo $engine ? $engine : '-'; ?></td>
        <td><?php echo number_format($rows_cnt); ?></td>
        <td><?php echo $size_txt; ?></td>
        <td><?php echo $comment ? $comment : '-'; ?></td>
        <td class="td_mng" nowrap>
            <a href="<?php echo $col_href; ?>" class="btn btn_03">컬럼</a>
            <a href="<?php echo $data_href; ?>" class="btn btn_03">데이터</a>
            <?php if ($is_admin === 'super') { ?>
                <a href="javascript:void(0);" class="btn btn_02"
                   onclick="return rbDoDropTable(<?php echo htmlspecialchars(json_encode($tname, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                   삭제
                </a>
            <?php } ?>
        </td>
    </tr>
    <?php
        $i++;
    }
    if ($i === 0) echo '<tr><td colspan="6" class="empty_table">데이터가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<?php if ($is_admin === 'super') { ?>
<form name="fcreate" method="post" action="./rb_dbtool_update.php" autocomplete="off" data-rb-auth="1">
<input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
<input type="hidden" name="action" value="create_table">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">

<div class="tbl_frm01 tbl_wrap">
    <h2 class="h2_frm">테이블 생성</h2>
    <table>
    <caption>테이블 생성</caption>
    <tbody>
    <tr>
        <th scope="row">테이블명</th>
        <td>
            <?php echo help('영문/숫자/_ 만 입력하세요.') ?>
            <input type="text" name="new_table" value="" class="frm_input required" required>
        </td>
    </tr>
    <tr>
        <th scope="row">기본 컬럼</th>
        <td>
            <?php echo help('체크시 코유키로 사용하는 (자동증가) id 컬럼을 자동으로 생성합니다.') ?>
            <input type="checkbox" name="with_id" id="with_id" value="1" checked><label for="with_id">id INT AUTO_INCREMENT PK</label>
        </td>
    </tr>
    <tr>
        <th scope="row">엔진</th>
        <td>
            <?php echo help('현재 DB에서 지원하는 엔진 중 자주 사용하는 항목입니다.') ?>
            <select name="engine">
                <?php
                $default_engine = isset($supported_engines['InnoDB']) ? 'InnoDB' : (isset($engine_options[0]) ? $engine_options[0] : '');
                foreach ($engine_options as $e) {
                    echo '<option value="'.get_text($e).'"'.get_selected($default_engine, $e, true).'>'.get_text($e).'</option>';
                }
                ?>
            </select>

        </td>
    </tr>
    <tr>
    <th scope="row">콜레이션</th>
        <td>
            <?php echo help('현재 DB에서 많이 사용중인 콜레이션이 표시됩니다.<br>내용을 지우면 콜레이션을 검색할 수 있습니다.') ?>
            <div style="position:relative; max-width:400px;">
                <input type="text" id="collation_search" placeholder="콜레이션 검색" class="frm_input" autocomplete="off" style="width:100%;">
                <input type="hidden" name="charset" id="collation_value" value="<?php echo get_text(!empty($top_collations[0]) ? $top_collations[0] : 'utf8mb4_general_ci'); ?>">
                <div id="collation_dropdown" style="border-radius:6px; margin-top:5px; display:none; position:absolute; z-index:999; background:#fff; border:1px solid #ccc; max-height:200px; overflow-y:auto; width:100%; box-sizing:border-box;"></div>
            </div>
            <script>
            (function() {
                var allCollations = <?php echo json_encode(array_values(array_merge($top_collations, array_diff($all_collations, $top_collations))), JSON_UNESCAPED_UNICODE); ?>;
                var topCollations = <?php echo json_encode($top_collations, JSON_UNESCAPED_UNICODE); ?>;

                var searchEl = document.getElementById('collation_search');
                var valueEl  = document.getElementById('collation_value');
                var dropdown = document.getElementById('collation_dropdown');
                if (!searchEl || !valueEl || !dropdown) return;

                // 초기값 표시
                searchEl.value = valueEl.value;

                function renderList(keyword) {
                    dropdown.innerHTML = '';
                    var kw = keyword.toLowerCase();
                    var count = 0;
                    for (var i = 0; i < allCollations.length; i++) {
                        var col = allCollations[i];
                        if (kw && col.toLowerCase().indexOf(kw) === -1) continue;
                        var item = document.createElement('div');
                        var isTop = topCollations.indexOf(col) !== -1;
                        item.style.cssText = 'padding:5px 8px; cursor:pointer; font-size:13px;' + (isTop ? 'font-weight:normal;' : '');
                        item.textContent = col;
                        item.setAttribute('data-value', col);
                        item.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            valueEl.value = this.getAttribute('data-value');
                            searchEl.value = this.getAttribute('data-value');
                            dropdown.style.display = 'none';
                        });
                        item.addEventListener('mouseover', function() { this.style.background = '#f0f5f9'; });
                        item.addEventListener('mouseout',  function() { this.style.background = ''; });
                        dropdown.appendChild(item);
                        count++;
                        if (count >= 200) break;
                    }
                    dropdown.style.display = count > 0 ? 'block' : 'none';
                }

                searchEl.addEventListener('focus', function() { renderList(this.value); });
                searchEl.addEventListener('input', function() { renderList(this.value); });
                searchEl.addEventListener('blur',  function() {
                    setTimeout(function() { dropdown.style.display = 'none'; }, 150);
                });
            })();
            </script>
        </td>
    </tr>
    <tr>
        <th scope="row">코멘트</th>
        <td>
            <input type="text" name="comment" value="" class="frm_input" placeholder="테이블 코멘트(선택)">
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="">
    <input type="submit" value="테이블 생성" class="btn_submit btn">
</div>
</form>
<?php } ?>

<?php } else if ($mode === 'columns' && $selected_table_ok) { ?>

<div class="btn_fixed_top">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'tables','sfl'=>$sfl,'stx'=>$stx), true); ?>" class="btn btn_02">목록</a>
</div>

<?php if ($is_admin === 'super') { ?>
<form name="fcolumnlist" method="post" action="./rb_dbtool_update.php" autocomplete="off" data-rb-auth="1">
<input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
<input type="hidden" name="action" value="update_columns">
<input type="hidden" name="table" value="<?php echo get_text($table); ?>">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">
<?php } ?>

<div id="sct" class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> - 컬럼 목록</caption>
    <thead>
    <tr>
        <th scope="col">컬럼</th>
        <th scope="col">PK/AUTO</th>
        <th scope="col">타입선택</th>
        <th scope="col">직접입력</th>
        <th scope="col">NULL</th>
        <th scope="col">DEFAULT</th>
        <th scope="col">코멘트</th>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    $type_presets = rb_dbtool_type_presets();
    foreach ($columns as $c) {
        $bg = 'bg'.($i%2);

        $field = (string)($c['Field'] ?? '');
        $type  = (string)($c['Type'] ?? '');
        $null  = (string)($c['Null'] ?? '');
        $def   = isset($c['Default']) ? (string)$c['Default'] : '';
        $comm  = (string)($c['Comment'] ?? '');
        $extra = (string)($c['Extra'] ?? '');
        $key   = (string)($c['Key'] ?? '');

        $is_super = ($is_admin === 'super');
        $lock = false;
        if (stripos($extra, 'auto_increment') !== false) $lock = true;
        $type_preset_key = rb_dbtool_type_preset_key($type);
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_code">
            <?php if ($is_super) { ?>
                <input type="hidden" name="col_name[<?php echo $i; ?>]" value="<?php echo get_text($field); ?>">
            <?php } ?>
            <?php echo get_text($field); ?>
        </td>
        <td>
        <?php if ($key === 'PRI') { ?>PK <?php } ?>
        <?php if ($lock) { ?> AUTO<?php } ?>
        </td>
        <td>
            <?php if ($is_super) { ?>
                <select name="col_type_preset[<?php echo $i; ?>]" class="rb_dbtool_type_preset" data-target="col_type_custom_<?php echo $i; ?>" <?php echo $lock ? 'disabled' : ''; ?>>
                    <?php
                    foreach ($type_presets as $preset_key => $preset) {
                        echo '<option value="'.get_text($preset_key).'"'.get_selected($type_preset_key, $preset_key, true).'>'.get_text($preset['label']).'</option>';
                    }
                    ?>
                </select>
                <?php if ($lock) { ?>
                    <input type="hidden" name="col_type_preset[<?php echo $i; ?>]" value="<?php echo get_text($type_preset_key); ?>">
                <?php } ?>
            <?php } else { ?>
                <?php echo get_text($type_preset_key); ?>
            <?php } ?>
        </td>
        <td>
            <?php if ($is_super) { ?>
                <input type="text" name="col_type[<?php echo $i; ?>]" id="col_type_custom_<?php echo $i; ?>" value="<?php echo get_text($type); ?>" class="tbl_input rb_dbtool_type_custom" <?php echo ($type_preset_key !== 'custom') ? 'style="display:none;"' : ''; ?> <?php echo $lock ? 'readonly' : ''; ?>>
            <?php } else { ?>
                <?php echo get_text($type); ?>
            <?php } ?>
        </td>
        <td>
            <?php if ($is_super) { ?>
                <select name="col_null[<?php echo $i; ?>]" <?php echo $lock ? 'disabled' : ''; ?>>
                    <option value="NO"<?php echo get_selected($null, 'NO', true); ?>>NO</option>
                    <option value="YES"<?php echo get_selected($null, 'YES', true); ?>>YES</option>
                </select>
                <?php if ($lock) { ?>
                    <input type="hidden" name="col_null[<?php echo $i; ?>]" value="<?php echo get_text($null); ?>">
                <?php } ?>
            <?php } else { ?>
                <?php echo get_text($null); ?>
            <?php } ?>
        </td>
        <td>
            <?php if ($is_super) { ?>
                <input type="text" name="col_default[<?php echo $i; ?>]" value="<?php echo get_text($def); ?>" class="tbl_input" placeholder="예: NULL / CURRENT_TIMESTAMP / 값" <?php echo $lock ? 'readonly' : ''; ?>>
            <?php } else { ?>
                <?php echo get_text($def); ?>
            <?php } ?>
        </td>
        <td>
            <?php if ($is_super) { ?>
                <input type="text" name="col_comment[<?php echo $i; ?>]" value="<?php echo get_text($comm); ?>" class="tbl_input">
            <?php } else { ?>
                <?php echo get_text($comm); ?>
            <?php } ?>
        </td>
        <td class="td_mng" nowrap>
            <?php if ($is_super) { ?>
                <a href="javascript:void(0);" class="btn btn_02" onclick="return rbDoDropColumn('<?php echo get_text($table); ?>','<?php echo get_text($field); ?>');">삭제</a>
            <?php } else { ?>
                -
            <?php } ?>
        </td>
    </tr>
    <?php
        $i++;
    }
    if ($i === 0) echo '<tr><td colspan="8" class="empty_table">데이터가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<?php if ($is_admin === 'super') { ?>
<div class="">
    <input type="submit" value="일괄수정" class="btn_submit btn">
</div>
</form>
<?php } ?>

<?php if ($is_admin === 'super') { ?>
<form name="faddcol" method="post" action="./rb_dbtool_update.php" autocomplete="off" data-rb-auth="1">
<input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
<input type="hidden" name="action" value="add_column">
<input type="hidden" name="table" value="<?php echo get_text($table); ?>">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">

<div class="tbl_frm01 tbl_wrap">
    <h2 class="h2_frm">컬럼생성</h2>
    <table>
    <caption>컬럼 생성</caption>
    <tbody>
    <tr>
        <th scope="row">컬럼명</th>
        <td colspan="3"><input type="text" name="new_col" value="" class="frm_input required" required></td>
    </tr>
    <tr>
        <th scope="row">타입</th>
        <td colspan="3">
            <select name="new_type_preset" id="new_type_preset" class="frm_input rb_dbtool_type_preset" data-target="new_type_custom_row" style="width:100%; max-width:520px;">
                <?php
                foreach (rb_dbtool_type_presets() as $preset_key => $preset) {
                    $selected = ($preset_key === 'varchar_255') ? ' selected' : '';
                    echo '<option value="'.get_text($preset_key).'"'.$selected.'>'.get_text($preset['label']).'</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    <tr id="new_type_custom_row" style="display:none;">
        <th scope="row">직접입력</th>
        <td colspan="3">
            <input type="text" name="new_type" id="new_type_custom" value="VARCHAR(255)" class="frm_input required rb_dbtool_type_custom" required style="width:100%; max-width:520px;">
        </td>
    </tr>
    <tr>
        <th scope="row">NULL 허용</th>
        <td colspan="3">
            <label><input type="checkbox" name="new_null" value="1"> NULL 허용</label>
            <div class="frm_info" style="margin-top:8px;">
                값을 비워둘 수 있게 합니다. 체크하지 않으면 반드시 값이 있어야 합니다.
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row">옵션</th>
        <td colspan="3">
            <label style="margin-right:15px;"><input type="checkbox" name="new_pk" value="1"> 기본키(PK)</label>
            <label style="margin-right:15px;"><input type="checkbox" name="new_auto_increment" value="1"> AUTO_INCREMENT</label>
            <label style="margin-right:15px;"><input type="checkbox" name="new_unique" value="1"> UNIQUE</label>
            <label><input type="checkbox" name="new_index" value="1"> INDEX</label>
            <div class="frm_info" style="margin-top:8px;">
                기본키(PK): 각 행을 고유하게 구분하는 대표 키입니다. 테이블당 1개만 가능합니다.<br>
                AUTO_INCREMENT: 저장할 때마다 1씩 자동 증가합니다. 보통 숫자형 PK에서 사용합니다.<br>
                UNIQUE: 중복값을 허용하지 않습니다. 아이디, 코드값 같은 항목에 적합합니다.<br>
                INDEX: 검색 속도를 높이기 위한 인덱스입니다. 조회가 많은 컬럼에 사용합니다.
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row">DEFAULT</th>
        <td colspan="3">
            <input type="text" name="new_default" id="new_default" value="" class="frm_input" placeholder="예: NULL / CURRENT_TIMESTAMP / 값" style="width:100%; max-width:520px;">
            <div class="rb_dbtool_quick_btns">
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="">비움</button>
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="NULL">NULL</button>
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="CURRENT_TIMESTAMP">CURRENT_TIMESTAMP</button>
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="0">0</button>
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="1">1</button>
                <button type="button" class="btn btn_02" data-default-target="new_default" data-default-value="''">빈문자열</button>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row">코멘트</th>
        <td colspan="3"><input type="text" name="new_comment" value="" class="frm_input" style="width:100%; max-width:520px;"></td>
    </tr>
    <tr>
        <th scope="row">위치</th>
        <td colspan="3">
            <select name="new_after">
                <option value="">맨 뒤(기본)</option>
                <option value="FIRST">맨 앞(FIRST)</option>
                <?php
                foreach ($columns as $c) {
                    $f = (string)($c['Field'] ?? '');
                    if ($f === '') continue;
                    echo '<option value="'.get_text($f).'">AFTER '.get_text($f).'</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="">
    <input type="submit" value="생성" class="btn_submit btn">
</div>
</form>
<?php } ?>

<?php } else if ($mode === 'data' && $selected_table_ok) { ?>

<div class="btn_fixed_top">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'tables','sfl'=>$sfl,'stx'=>$stx), true); ?>" class="btn btn_02">목록</a>
    <?php if ($is_admin === 'super') { ?>
        <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'data_add','table'=>$table,'sfl'=>$sfl,'stx'=>$stx), true); ?>" class="btn btn_02">추가</a>
    <?php } ?>
</div>


<div class="local_desc01 local_desc">
    <p>
        컬럼이 많은 경우 일부 컬럼만 표기합니다.<br>
        자동증가 및 고유 컬럼은 수정하실 수 없습니다.
    </p>
</div>


<div id="sct" class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> - 데이터 목록</caption>
    <thead>
    <tr>
        <?php
        $show_cols = array();
        foreach ($columns as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === '') continue;
            $show_cols[] = $f;
            if (count($show_cols) >= 8) break;
        }
        foreach ($show_cols as $f) {
            echo '<th scope="col">'.get_text($f).'</th>';
        }
        ?>
        <th scope="col">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    foreach ($data_rows as $r) {
        $bg = 'bg'.($i%2);
    ?>
    <tr class="<?php echo $bg; ?>">
        <?php
        foreach ($show_cols as $f) {
            $v = isset($r[$f]) ? (string)$r[$f] : '';
            $short = $v;
            if (mb_strlen($short, 'UTF-8') > 80) $short = mb_substr($short, 0, 80, 'UTF-8').'...';
            echo '<td>'.get_text($short).'</td>';
        }
        ?>
        <td class="td_mng" nowrap>
            <?php if ($is_admin === 'super' && $has_pk) { ?>
                <?php
                $pk_q = array();
                foreach ($pk_cols as $p) {
                    $pk_q['pk['.$p.']'] = isset($r[$p]) ? (string)$r[$p] : '';
                }
                $edit_href = $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array_merge(array('mode'=>'data_edit','table'=>$table,'page'=>$page,'sfl'=>$sfl,'stx'=>$stx), $pk_q), true);
                ?>
                <a href="<?php echo $edit_href; ?>" class="btn btn_03">수정</a>
                <a href="javascript:void(0);" class="btn btn_02" onclick="return rbDoDeleteRow('<?php echo get_text($table); ?>', <?php echo htmlspecialchars(json_encode($pk_q), ENT_QUOTES, 'UTF-8'); ?>);">삭제</a>
            <?php } else { ?>
                -
            <?php } ?>
        </td>
    </tr>
    <?php
        $i++;
    }
    if ($i === 0) echo '<tr><td colspan="'.(count($show_cols)+1).'" class="empty_table">데이터가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<?php
$total_page = ($data_total > 0) ? (int)ceil($data_total / $per_page) : 1;
if ($total_page < 1) $total_page = 1;
echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    "{$_SERVER['SCRIPT_NAME']}?".rb_build_qs(array('mode'=>'data','table'=>$table,'sfl'=>$sfl,'stx'=>$stx), true)."&amp;page="
);
?>

<?php } else if ($mode === 'data_add' && $selected_table_ok) { ?>

<?php if ($is_admin !== 'super') { alert('최고관리자만 사용할 수 있습니다.'); } ?>

<div class="btn_fixed_top">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'data','table'=>$table,'page'=>1,'sfl'=>$sfl,'stx'=>$stx), true); ?>" class="btn btn_02">목록</a>
</div>

<form name="fdataadd" method="post" action="./rb_dbtool_update.php" autocomplete="off" data-rb-auth="1">
<input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
<input type="hidden" name="action" value="data_insert">
<input type="hidden" name="table" value="<?php echo get_text($table); ?>">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>데이터 추가</caption>
    <tbody>
    <?php
    foreach ($columns as $c) {
        $f = (string)($c['Field'] ?? '');
        if ($f === '') continue;

        $type = strtolower((string)($c['Type'] ?? ''));
        $extra = strtolower((string)($c['Extra'] ?? ''));
        $is_ai = (strpos($extra, 'auto_increment') !== false);

        // auto_increment는 입력 제외
        if ($is_ai) continue;

        $input_meta = rb_dbtool_input_meta($type);
    ?>
    <tr>
        <th scope="row"><?php echo get_text($f); ?></th>
        <td>
            <span style="display:inline-block; min-width:90px; color:#888; white-space:nowrap;"><?php echo get_text($c['Type'] ?? ''); ?></span>
        </td>
        <td>
            <?php if ($input_meta['kind'] === 'textarea') { ?>
                <textarea name="row[<?php echo get_text($f); ?>]" class="frm_input" style="width:100%;height:120px;" placeholder="<?php echo get_text($input_meta['placeholder']); ?>"></textarea>
            <?php } else { ?>
                <input type="<?php echo get_text($input_meta['input_type']); ?>" name="row[<?php echo get_text($f); ?>]" value="" class="frm_input" style="width:100%;" placeholder="<?php echo get_text($input_meta['placeholder']); ?>">
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <input type="submit" value="추가" class="btn_submit btn">
</div>
</form>

<?php } else if ($mode === 'data_edit' && $selected_table_ok) { ?>

<?php if ($is_admin !== 'super') { alert('최고관리자만 사용할 수 있습니다.'); } ?>

<div class="btn_fixed_top">
    <a href="<?php echo $_SERVER['SCRIPT_NAME'].'?'.rb_build_qs(array('mode'=>'data','table'=>$table,'page'=>$page,'sfl'=>$sfl,'stx'=>$stx), true); ?>" class="btn btn_02">목록</a>
</div>

<form name="fdataedit" method="post" action="./rb_dbtool_update.php" autocomplete="off" data-rb-auth="1">
<input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
<input type="hidden" name="action" value="data_update">
<input type="hidden" name="table" value="<?php echo get_text($table); ?>">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">

<?php
// PK hidden
foreach ($pk_cols as $p) {
    $pv = isset($edit_row[$p]) ? (string)$edit_row[$p] : '';
    echo '<input type="hidden" name="pk['.get_text($p).']" value="'.get_text($pv).'">';
}
?>

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>데이터 수정</caption>
    <tbody>
    <?php
    foreach ($columns as $c) {
        $f = (string)($c['Field'] ?? '');
        if ($f === '') continue;

        $type = strtolower((string)($c['Type'] ?? ''));
        $extra = strtolower((string)($c['Extra'] ?? ''));
        $is_ai = (strpos($extra, 'auto_increment') !== false);

        $is_pk = in_array($f, $pk_cols, true);
        $val = isset($edit_row[$f]) ? (string)$edit_row[$f] : '';

        $input_meta = rb_dbtool_input_meta($type);
        $readonly = ($is_pk || $is_ai) ? 'readonly' : '';
    ?>
    <tr>
        <th scope="row"><?php echo get_text($f); ?><?php echo $is_pk ? ' (PK)' : ''; ?></th>
        <td>
            <span style="display:inline-block; min-width:90px; color:#888; white-space:nowrap;"><?php echo get_text($c['Type'] ?? ''); ?></span>
        </td>
        <td>
            <?php if ($input_meta['kind'] === 'textarea') { ?>
                <textarea name="row[<?php echo get_text($f); ?>]" class="frm_input" style="width:100%;height:120px;" placeholder="<?php echo get_text($input_meta['placeholder']); ?>" <?php echo $readonly; ?>><?php echo get_text($val); ?></textarea>
            <?php } else { ?>
                <input type="<?php echo get_text($input_meta['input_type']); ?>" name="row[<?php echo get_text($f); ?>]" value="<?php echo get_text($val); ?>" class="frm_input" style="width:100%;" placeholder="<?php echo get_text($input_meta['placeholder']); ?>" <?php echo $readonly; ?>>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
    </tbody>
    </table>
</div>

<div class="">
    <input type="submit" value="수정" class="btn_submit btn">
</div>
</form>

<?php } else { ?>

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>안내</caption>
    <tbody>
    <tr>
        <th scope="row">선택</th>
        <td>테이블 목록에서 <strong>컬럼</strong> 또는 <strong>데이터</strong>를 선택하세요.</td>
    </tr>
    </tbody>
    </table>
</div>

<?php } ?>

<form id="rb_action_form" method="post" action="./rb_dbtool_update.php" style="display:none;" data-rb-auth="1">
    <input type="hidden" name="token" value="<?php echo $admin_token_js; ?>">
    <input type="hidden" name="action" id="rb_action" value="">
    <input type="hidden" name="table" id="rb_table" value="">
    <input type="hidden" name="col" id="rb_col" value="">
<input type="hidden" name="auth_dbname" value="">
<input type="hidden" name="auth_dbpass" value="">
<input type="hidden" name="auth_token" value="">
</form>

<style>
#rbAuthOverlay{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,.45);z-index:99990;display:none;}
#rbAuthModal{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:400px;max-width:90vw;background:var(--rb-adm-bg-w);border-radius:10px;z-index:99999;display:none;box-shadow:0 10px 30px rgba(0,0,0,.25); border:1px solid rgba(255,255,255,0.1);}

#rbAuthModal .hd{padding:20px 30px;color:var(--rb-strong); font-family: 'font-B'; border-bottom: 1px solid var(--rb-card-bd);}
#rbAuthModal .bd{padding:20px 30px;}
#rbAuthModal .ft{padding:20px 30px;border-top:1px solid var(--rb-card-bd);text-align:center;}
#rbAuthModal .row{margin-bottom:10px;}
#rbAuthModal .row label{display:block;margin-bottom:4px; color:var(--rb-strong)}
#rbAuthModal .row input{width:100%;height:36px;padding:0 10px;}
#rbAuthModal .row select{width:100%;height:36px;padding:0 10px;}
#rbAuthModal .tokenline{display:flex;gap:8px;align-items:center;}
#rbAuthModal .tokenline button{white-space:nowrap;}
.rb_dbtool_type_custom{margin-top:0;}
.rb_dbtool_quick_btns{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;}
.rb_dbtool_quick_btns button{padding:4px 8px;line-height:1.2;}
</style>

<div id="rbAuthOverlay"></div>
<div id="rbAuthModal" role="dialog" aria-modal="true">
    <div class="hd">보안 확인</div>
    <div class="bd">
        <div class="row">
            <label>DB명</label>
            <input type="text" id="rbAuthDbName" value="" class="frm_input">
        </div>
        <div class="row">
            <label>DB비밀번호</label>
            <input type="password" id="rbAuthDbPass" value="" class="frm_input">
        </div>
        <div class="row">
            <label for="rbAuthTokenExpire">유효기간</label>
            <select id="rbAuthTokenExpire" class="frm_input">
                <option value="1d">24시간 유효</option>
                <option value="3d">3일 유효</option>
                <option value="7d">7일 유효</option>
                <option value="1m">1개월 유효</option>
                <option value="forever">영구 유효</option>
            </select>
        </div>
        <div class="row">
            <label>토큰</label>
            <div class="tokenline">
                <input type="text" id="rbAuthToken" value="" class="frm_input">
                <button type="button" class="btn btn_02" id="rbAuthGenTokenBtn">생성</button>
            </div>
            <div class="frm_info" style="margin-top:15px;">
                토큰 사용방법<br>
                1. 유효기간을 선택한 뒤 <strong>생성</strong> 버튼을 누르세요.<br>
                2. <strong>/data/rb_dbtool/</strong> 폴더에 생성된 최신 파일을 열어주세요.<br>
                3. 파일의 token 문자열을 복사해서 위 입력칸에 붙여넣으세요.<br>
                4. 파일을 삭제했거나 분실한 경우 새로 생성해주세요.<br>
                5. 토큰은 생성 시 선택한 유효기간 동안 사용할 수 있습니다.
            </div>
        </div>
    </div>
    <div class="ft">
        <button type="button" class="btn btn_02" id="rbAuthCancelBtn">취소</button>
        <button type="button" class="btn_submit btn" id="rbAuthOkBtn">확인</button>
    </div>
</div>

<script>
var RB_ADMIN_TOKEN = "<?php echo addslashes($admin_token_js); ?>";

var RB_DBTOOL_AUTHED = <?php echo $rb_auth_ok ? 'true' : 'false'; ?>;
var RB_AUTH_TARGET_FORM = null;

function rbConfirm2(message1, message2) {
    if (typeof window.rb_confirm === "function") {
        return window.rb_confirm(message1).then(function(ok1){
            if (!ok1) return false;
            return window.rb_confirm(message2).then(function(ok2){
                return !!ok2;
            });
        });
    }
    var ok = confirm(message1);
    if (!ok) return false;
    return confirm(message2);
}

function rbOpenAuthModal(form) {
    RB_AUTH_TARGET_FORM = form;
    document.getElementById('rbAuthDbName').value = '';
    document.getElementById('rbAuthDbPass').value = '';
    document.getElementById('rbAuthToken').value  = '';

    document.getElementById('rbAuthOverlay').style.display = 'block';
    document.getElementById('rbAuthModal').style.display = 'block';
}

function rbCloseAuthModal() {
    document.getElementById('rbAuthOverlay').style.display = 'none';
    document.getElementById('rbAuthModal').style.display = 'none';
    RB_AUTH_TARGET_FORM = null;
}

function rbFillAuthHidden(form) {
    var dbn = document.getElementById('rbAuthDbName').value || '';
    var dbp = document.getElementById('rbAuthDbPass').value || '';
    var tkn = document.getElementById('rbAuthToken').value || '';

    var inputs = form.querySelectorAll('input[name="auth_dbname"], input[name="auth_dbpass"], input[name="auth_token"]');
    for (var i=0;i<inputs.length;i++){
        if (inputs[i].name === 'auth_dbname') inputs[i].value = dbn;
        if (inputs[i].name === 'auth_dbpass') inputs[i].value = dbp;
        if (inputs[i].name === 'auth_token')  inputs[i].value = tkn;
    }
}

document.getElementById('rbAuthCancelBtn').addEventListener('click', function(){
    rbCloseAuthModal();
});



document.getElementById('rbAuthGenTokenBtn').addEventListener('click', function(){
    var expire = document.getElementById('rbAuthTokenExpire').value || '1d';
    var fd = new FormData();
    fd.append('token', RB_ADMIN_TOKEN);
    fd.append('action', 'generate_token');
    fd.append('expire_type', expire);
    fd.append('ajax', '1');

    fetch('./rb_dbtool_update.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(res){
        return res.text().then(function(txt){
            var json = null;
            try { json = JSON.parse(txt); } catch(e) {}

            if (json && json.ok) {
                var expireText = json.expire_text ? ('\n유효기간: ' + json.expire_text) : '';
                alert('토큰이 생성되었습니다.\n/data/rb_dbtool/ 폴더에 생성된 파일을 확인해주세요.' + expireText);
                return;
            }

            if (!json) {
                var preview = (txt || '').replace(/\s+/g,' ').slice(0, 300);
                alert('토큰 생성 응답이 올바르지 않습니다.\nHTTP: '+res.status+'\n\n'+preview);
                return;
            }

            alert(json.msg ? json.msg : '토큰 생성에 실패했습니다.');
        });
    }).catch(function(err){
        alert('토큰 생성 요청에 실패했습니다.\n' + (err && err.message ? err.message : ''));
    });
});

document.getElementById('rbAuthOkBtn').addEventListener('click', function(){
    if (!RB_AUTH_TARGET_FORM) return;

    var dbn = (document.getElementById('rbAuthDbName').value || '').trim();
    var dbp = (document.getElementById('rbAuthDbPass').value || '').trim();
    var tkn = (document.getElementById('rbAuthToken').value  || '').trim();

    // 빈값 체크
    if (!dbn) { alert('DB명을 입력해주세요.'); document.getElementById('rbAuthDbName').focus(); return; }
    if (!dbp) { alert('DB비밀번호를 입력해주세요.'); document.getElementById('rbAuthDbPass').focus(); return; }
    if (!tkn) { alert('토큰을 입력해주세요.'); document.getElementById('rbAuthToken').focus(); return; }

    var targetForm = RB_AUTH_TARGET_FORM; // ★ 닫기 전에 잡아둠

    var fd = new FormData();
    fd.append('token', RB_ADMIN_TOKEN);
    fd.append('action', 'verify_auth');
    fd.append('ajax', '1');
    fd.append('auth_dbname', dbn);
    fd.append('auth_dbpass', dbp);
    fd.append('auth_token', tkn);

    fetch('./rb_dbtool_update.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(res){
        return res.text().then(function(txt){
            var json = null;
            try { json = JSON.parse(txt); } catch(e) {}

            if (json && json.ok) {
                rbFillAuthHidden(targetForm);

                RB_DBTOOL_AUTHED = true;
rbCloseAuthModal();
                if (targetForm.requestSubmit) targetForm.requestSubmit();
                else targetForm.submit();
                return;
            }

            if (!json) {
                var preview = (txt || '').replace(/\s+/g,' ').slice(0, 300);
                alert('보안 확인 응답이 올바르지 않습니다.\nHTTP: '+res.status+'\n\n'+preview);
                return;
            }

            alert(json.msg ? json.msg : '보안 확인에 실패했습니다.');
        });
    }).catch(function(err){
        alert('보안 확인 요청에 실패했습니다.\n' + (err && err.message ? err.message : ''));
    });
});

// 폼 submit 가로채기(보안 팝업)
document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || !form.getAttribute) return;

    if (form.getAttribute('data-rb-auth') === '1') {
        if (RB_DBTOOL_AUTHED) return;
        // 이미 auth 값 채워져서 submit 되는 경우(모달확인 버튼 후) 방지
        var adb = form.querySelector('input[name="auth_dbname"]');
        var adp = form.querySelector('input[name="auth_dbpass"]');
        var atk = form.querySelector('input[name="auth_token"]');
        if (adb && adp && atk && adb.value && adp.value && atk.value) return;

        e.preventDefault();
        rbOpenAuthModal(form);
    }
}, true);

// 테이블/컬럼 삭제는 2중 확인 + 보안팝업
function rbSubmitAction(action, tableName, colName, extraPk) {
    var f = document.getElementById('rb_action_form');
    if (!f) {
        alert('동작 폼(rb_action_form)을 찾을 수 없습니다.');
        return;
    }

    document.getElementById('rb_action').value = action;
    document.getElementById('rb_table').value  = tableName || '';
    document.getElementById('rb_col').value    = colName || '';

    // pk[] 기존 제거 후 재생성
    var olds = f.querySelectorAll('input[name^="pk["]');
    for (var i=0;i<olds.length;i++) olds[i].parentNode.removeChild(olds[i]);

    if (extraPk && typeof extraPk === 'object') {
        for (var k in extraPk) {
            if (!extraPk.hasOwnProperty(k)) continue;
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = k;
            inp.value = extraPk[k];
            f.appendChild(inp);
        }
    }

    // 이미 보안 인증(세션)이 되어있으면 재입력 없이 바로 전송
    if (RB_DBTOOL_AUTHED) {
        if (f.requestSubmit) f.requestSubmit();
        else f.submit();
        return;
    }

    // 인증이 없으면 auth 값 비우고 모달 오픈
    var adb = f.querySelector('input[name="auth_dbname"]');
    var adp = f.querySelector('input[name="auth_dbpass"]');
    var atk = f.querySelector('input[name="auth_token"]');
    if (adb) adb.value = '';
    if (adp) adp.value = '';
    if (atk) atk.value = '';

    rbOpenAuthModal(f);
}

function rbDoDropTable(tableName) {
    if (!tableName) return false;

    var msg1 = tableName +" 테이블을 삭제합니다.\n정말 진행할까요? (1/2)";
    var msg2 = tableName +" 테이블을 삭제합니다.\n삭제하면 복구가 어렵습니다.\n\n정말 진행할까요? (2/2)";

    var run = rbConfirm2(msg1, msg2);
    if (run && typeof run.then === "function") {
        run.then(function(ok){
            if (!ok) return;
            rbSubmitAction('drop_table', tableName, '', null);
        });
        return false;
    }

    if (!run) return false;
    rbSubmitAction('drop_table', tableName, '', null);
    return false;
}

function rbDoDropColumn(tableName, colName) {
    if (!tableName || !colName) return false;

    var msg1 = colName +" 컬럼을 삭제합니다.\n정말 진행할까요? (1/2)";
    var msg2 = colName +" 컬럼을 삭제합니다.\n삭제하면 복구가 어렵습니다.\n\n정말 진행할까요? (2/2)";

    var run = rbConfirm2(msg1, msg2);
    if (run && typeof run.then === "function") {
        run.then(function(ok){
            if (!ok) return;
            rbSubmitAction('drop_column', tableName, colName, null);
        });
        return false;
    }

    if (!run) return false;
    rbSubmitAction('drop_column', tableName, colName, null);
    return false;
}

function rbDoDeleteRow(tableName, pkObj) {
    if (!tableName || !pkObj) return false;

    var msg1 = "데이터를 삭제합니다.\n정말 진행할까요? (1/2)";
    var msg2 = "데이터를 삭제합니다.\n삭제하면 복구가 어렵습니다.\n\n정말 진행할까요? (2/2)";

    var run = rbConfirm2(msg1, msg2);
    if (run && typeof run.then === "function") {
        run.then(function(ok){
            if (!ok) return;
            rbSubmitAction('data_delete', tableName, '', pkObj);
        });
        return false;
    }

    if (!run) return false;
    rbSubmitAction('data_delete', tableName, '', pkObj);
    return false;
}

function rbToggleTypeInput(selectEl) {
    if (!selectEl) return;
    var targetId = selectEl.getAttribute('data-target');
    if (!targetId) return;
    var target = document.getElementById(targetId);
    if (!target) return;

    if (selectEl.value === 'custom') {
        target.style.display = '';
    } else {
        target.style.display = 'none';
    }
}

document.addEventListener('change', function(e){
    var el = e.target;
    if (!el || !el.classList || !el.classList.contains('rb_dbtool_type_preset')) return;
    rbToggleTypeInput(el);
});

document.addEventListener('DOMContentLoaded', function(){
    var presets = document.querySelectorAll('.rb_dbtool_type_preset');
    for (var i = 0; i < presets.length; i++) {
        rbToggleTypeInput(presets[i]);
    }

    var defaultBtns = document.querySelectorAll('[data-default-target]');
    for (var j = 0; j < defaultBtns.length; j++) {
        defaultBtns[j].addEventListener('click', function(){
            var target = document.getElementById(this.getAttribute('data-default-target'));
            if (!target) return;
            target.value = this.getAttribute('data-default-value') || '';
            target.focus();
        });
    }

    var typePreset = document.getElementById('new_type_preset');
    var pkChk = document.querySelector('input[name="new_pk"]');
    var aiChk = document.querySelector('input[name="new_auto_increment"]');
    var nullChk = document.querySelector('input[name="new_null"]');

    function rbSyncColumnOptions() {
        if (!typePreset) return;
        var val = typePreset.value;
        var isNumberType = ['int_11','bigint_20','decimal_10_2','tinyint_1','custom'].indexOf(val) !== -1;

        if (aiChk) {
            aiChk.disabled = !isNumberType;
            if (!isNumberType) aiChk.checked = false;
        }

        if (pkChk && pkChk.checked && nullChk) {
            nullChk.checked = false;
        }

        if (aiChk && aiChk.checked) {
            if (nullChk) nullChk.checked = false;
            if (pkChk) pkChk.checked = true;
        }
    }

    if (typePreset) typePreset.addEventListener('change', rbSyncColumnOptions);
    if (pkChk) pkChk.addEventListener('change', rbSyncColumnOptions);
    if (aiChk) aiChk.addEventListener('change', rbSyncColumnOptions);
    if (nullChk) nullChk.addEventListener('change', rbSyncColumnOptions);
    rbSyncColumnOptions();
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
