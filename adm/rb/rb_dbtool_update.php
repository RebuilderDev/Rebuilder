<?php
$sub_menu = '';
include_once('./_common.php');

if ($is_admin !== 'super') {
    alert('최고관리자만 사용할 수 있습니다.');
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$table  = isset($_POST['table']) ? trim((string)$_POST['table']) : '';
$ajax   = isset($_POST['ajax']) ? trim((string)$_POST['ajax']) : '';

if (!function_exists('rb_is_safe_ident')) {
    function rb_is_safe_ident($s) {
        $s = (string)$s;
        if ($s === '') return false;
        return (bool)preg_match('/^[a-zA-Z0-9_]+$/', $s);
    }
}

if (!function_exists('rb_guard_table')) {
    function rb_guard_table($table) {
        if (!rb_is_safe_ident($table)) alert('테이블명이 올바르지 않습니다.');
        return true;
    }
}

if (!function_exists('rb_guard_col_type')) {
    function rb_guard_col_type($type) {
        $type = trim((string)$type);
        if ($type === '') alert('타입이 비었습니다.');
        if (preg_match('/[`;]/', $type)) alert('허용되지 않은 문자가 포함되어 있습니다.');
        if (stripos($type, '--') !== false) alert('허용되지 않은 문자가 포함되어 있습니다.');
        if (stripos($type, '/*') !== false || stripos($type, '*/') !== false) alert('허용되지 않은 문자가 포함되어 있습니다.');
        if (preg_match('/[^a-zA-Z0-9_\s\(\),\.\'+-]/', $type)) alert('타입 형식이 올바르지 않습니다.');
        return $type;
    }
}

if (!function_exists('rb_sql_default')) {
    function rb_sql_default($v) {
        $v = trim((string)$v);
        if ($v === '') return '';
        $u = strtoupper($v);
        if ($u === 'NULL') return 'DEFAULT NULL';
        if ($u === 'CURRENT_TIMESTAMP') return 'DEFAULT CURRENT_TIMESTAMP';
        if (preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $v)) return 'DEFAULT '.$v;
        $v = sql_escape_string($v);
        return "DEFAULT '{$v}'";
    }
}

if (!function_exists('rb_sql_comment')) {
    function rb_sql_comment($v) {
        $v = (string)$v;
        $v = sql_escape_string($v);
        return "COMMENT '{$v}'";
    }
}

if (!function_exists('rb_json')) {
    function rb_json($ok, $msg = '', $extra = array()) {
        if (function_exists('ob_get_length') && ob_get_length()) {
            @ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        $out = array('ok' => (bool)$ok, 'msg' => (string)$msg);
        if (is_array($extra) && count($extra) > 0) $out = array_merge($out, $extra);
        echo json_encode($out);
        exit;
    }
}

if (!function_exists('rb_dbtool_expire_policies')) {
    function rb_dbtool_expire_policies() {
        return array(
            '1d' => array('seconds' => 86400, 'label' => '24시간 유효'),
            '3d' => array('seconds' => 86400 * 3, 'label' => '3일 유효'),
            '7d' => array('seconds' => 86400 * 7, 'label' => '7일 유효'),
            '1m' => array('seconds' => 86400 * 30, 'label' => '1개월 유효'),
            'forever' => array('seconds' => 0, 'label' => '영구 유효'),
        );
    }
}

if (!function_exists('rb_dbtool_expire_policy')) {
    function rb_dbtool_expire_policy($key) {
        $policies = rb_dbtool_expire_policies();
        $key = trim((string)$key);
        if ($key === '' || !isset($policies[$key])) $key = '1d';
        return array($key, $policies[$key]);
    }
}

if (!function_exists('rb_dbtool_format_expire_message')) {
    function rb_dbtool_format_expire_message($key) {
        list($policy_key, $policy) = rb_dbtool_expire_policy($key);
        return (string)$policy['label'];
    }
}

if (!function_exists('rb_dbtool_type_presets')) {
    function rb_dbtool_type_presets() {
        return array(
            'varchar_255' => 'VARCHAR(255)',
            'char_20' => 'CHAR(20)',
            'int_11' => 'INT(11)',
            'bigint_20' => 'BIGINT(20)',
            'decimal_10_2' => 'DECIMAL(10,2)',
            'tinyint_1' => 'TINYINT(1)',
            'text' => 'TEXT',
            'mediumtext' => 'MEDIUMTEXT',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
        );
    }
}

if (!function_exists('rb_dbtool_resolve_col_type')) {
    function rb_dbtool_resolve_col_type($preset_key, $custom_type) {
        $preset_key = trim((string)$preset_key);
        $custom_type = trim((string)$custom_type);
        $presets = rb_dbtool_type_presets();

        if ($preset_key !== '' && $preset_key !== 'custom' && isset($presets[$preset_key])) {
            return (string)$presets[$preset_key];
        }

        return $custom_type;
    }
}

// admin token 검증 (AJAX는 JSON으로, 일반은 alert로)
if (!function_exists('rb_check_admin_token')) {
    function rb_check_admin_token($is_ajax = false) {
        $posted = isset($_POST['token']) ? (string)$_POST['token'] : '';
        $posted = trim($posted);

        $sess = '';
        if (function_exists('get_session')) {
            $sess = (string)get_session('rb_dbtool_admin_token');
            $sess = trim($sess);
        }

        if ($posted === '' || $sess === '' || !hash_equals($sess, $posted)) {
            if ($is_ajax) rb_json(false, '올바른 방법으로 이용해 주십시오. (토큰 오류)');
            alert('올바른 방법으로 이용해 주십시오.');
        }
    }
}

// 토큰 파일 생성
if (!function_exists('rb_gen_token_file')) {
    function rb_gen_token_file($expire_type = '1d') {
        $base = defined('G5_DATA_PATH') ? G5_DATA_PATH : (G5_PATH.'/data');
        $dir = rtrim($base, '/').'/rb_dbtool';
        list($expire_key, $expire_policy) = rb_dbtool_expire_policy($expire_type);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                $e = error_get_last();
                $msg = isset($e['message']) ? $e['message'] : 'mkdir 실패';
                return array(false, '토큰 폴더 생성 실패: '.$msg);
            }
        }

        if (!is_writable($dir)) {
            return array(false, '토큰 폴더에 쓰기 권한이 없습니다: '.$dir);
        }

        $ts = date('YmdHis');

        try {
            $rand  = substr(bin2hex(random_bytes(8)), 0, 8);
            $token = bin2hex(random_bytes(16));
        } catch (Exception $ex) {
            $rand  = substr(md5(uniqid('', true)), 0, 8);
            $token = md5(uniqid((string)mt_rand(), true)).md5(uniqid((string)mt_rand(), true));
            $token = substr($token, 0, 32);
        }

        $fname = $ts.'_'.$rand.'.txt';
        $path  = rtrim($dir, '/').'/'.$fname;
        $issued_at = date('Y-m-d H:i:s');
        $expires_at = '';
        if ((int)$expire_policy['seconds'] > 0) {
            $expires_at = date('Y-m-d H:i:s', time() + (int)$expire_policy['seconds']);
        }

        $payload = array(
            'token' => $token,
            'expire_type' => $expire_key,
            'expire_label' => (string)$expire_policy['label'],
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
        );
        $content = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ok = @file_put_contents($path, $content, LOCK_EX);
        if ($ok === false) $ok = @file_put_contents($path, $content);

        if ($ok === false) {
            $e = error_get_last();
            $msg = isset($e['message']) ? $e['message'] : 'file_put_contents 실패';
            return array(false, '토큰 파일 생성 실패: '.$msg.' ('.$path.')');
        }

        @chmod($path, 0644);

        return array(true, $fname, $path, $expire_key, (string)$expire_policy['label']);
    }
}

if (!function_exists('rb_dbtool_parse_token_file')) {
    function rb_dbtool_parse_token_file($path) {
        if (!is_file($path)) return array();

        $raw = @file_get_contents($path);
        if ($raw === false) return array();

        $raw = trim($raw);
        if ($raw === '') return array();

        $json = json_decode($raw, true);
        if (is_array($json) && isset($json['token'])) {
            $expire_type = isset($json['expire_type']) ? (string)$json['expire_type'] : '1d';
            list($expire_key, $expire_policy) = rb_dbtool_expire_policy($expire_type);

            $issued_ts = 0;
            if (!empty($json['issued_at'])) {
                $issued_ts = strtotime((string)$json['issued_at']);
            }
            if ($issued_ts <= 0) {
                $mtime = @filemtime($path);
                $issued_ts = $mtime ? (int)$mtime : time();
            }

            $expires_ts = 0;
            if (!empty($json['expires_at'])) {
                $expires_ts = strtotime((string)$json['expires_at']);
            } else if ((int)$expire_policy['seconds'] > 0) {
                $expires_ts = $issued_ts + (int)$expire_policy['seconds'];
            }

            return array(
                'token' => trim((string)$json['token']),
                'expire_type' => $expire_key,
                'expire_label' => isset($json['expire_label']) && $json['expire_label'] !== '' ? (string)$json['expire_label'] : (string)$expire_policy['label'],
                'issued_ts' => $issued_ts,
                'expires_ts' => $expires_ts,
                'legacy' => false,
            );
        }

        $mtime = @filemtime($path);
        $issued_ts = $mtime ? (int)$mtime : time();
        return array(
            'token' => $raw,
            'expire_type' => '1d',
            'expire_label' => '24시간 유효',
            'issued_ts' => $issued_ts,
            'expires_ts' => $issued_ts + 86400,
            'legacy' => true,
        );
    }
}

if (!function_exists('rb_dbtool_is_token_meta_expired')) {
    function rb_dbtool_is_token_meta_expired($meta) {
        $expires_ts = isset($meta['expires_ts']) ? (int)$meta['expires_ts'] : 0;
        if ($expires_ts > 0 && time() > $expires_ts) return true;
        return false;
    }
}

// 토큰 파일 찾기(선택 만료 / 구버전 24시간 호환)
if (!function_exists('rb_find_token_file')) {
    function rb_find_token_file($token) {
        $token = trim((string)$token);
        if ($token === '') return array('', array());

        $base = defined('G5_DATA_PATH') ? G5_DATA_PATH : (G5_PATH.'/data');
        $dir  = rtrim($base, '/').'/rb_dbtool';
        if (!is_dir($dir)) return array('', array());

        $dh = @opendir($dir);
        if (!$dh) return array('', array());

        $found = '';
        $found_meta = array();

        while (($file = readdir($dh)) !== false) {
            if (!preg_match('/^[0-9]{14}_[a-zA-Z0-9]{8}\.txt$/', $file)) continue;

            $path = rtrim($dir, '/').'/'.$file;
            if (!is_file($path)) continue;

            $meta = rb_dbtool_parse_token_file($path);
            if (empty($meta) || empty($meta['token'])) continue;

            if (rb_dbtool_is_token_meta_expired($meta)) {
                @unlink($path);
                continue;
            }

            if ((string)$meta['token'] === $token) {
                $found = $path;
                $found_meta = $meta;
                break;
            }
        }

        closedir($dh);
        return array($found, $found_meta);
    }
}

// 토큰 검증(선택 만료 / 파일 삭제 없이 유지)
if (!function_exists('rb_verify_token_valid')) {
    function rb_verify_token_valid($token) {
        list($path, $meta) = rb_find_token_file($token);
        if ($path === '' || empty($meta)) return array(false, array(), '');
        return array(true, $meta, $path);
    }
}

if (!function_exists('rb_dbtool_session_authed')) {
    function rb_dbtool_session_authed() {
        // 관리자 세션 기반 보안
        $ok = function_exists('get_session') ? (string)get_session('rb_dbtool_auth_ok') : '';
        if ($ok !== '1') return false;

        $token = function_exists('get_session') ? (string)get_session('rb_dbtool_auth_token') : '';
        $token = trim($token);
        if ($token === '') return false;

        // 토큰 파일이 살아있고 만료 전이면 유효
        list($path, $meta) = rb_find_token_file($token);
        if ($path === '' || empty($meta)) {
            // 토큰 파일이 없으면 세션도 무효화
            if (function_exists('set_session')) {
                set_session('rb_dbtool_auth_ok', '');
                set_session('rb_dbtool_auth_token', '');
            }
            return false;
        }

        return true;
    }
}

// DB 비밀번호 검증(입력 비번으로 새 연결 시도)
if (!function_exists('rb_verify_db_password')) {
    function rb_verify_db_password($dbname, $dbpass) {
        $dbname = (string)$dbname;
        $dbpass = (string)$dbpass;

        $host    = defined('G5_MYSQL_HOST') ? G5_MYSQL_HOST : '';
        $user    = defined('G5_MYSQL_USER') ? G5_MYSQL_USER : '';
        $real_db = defined('G5_MYSQL_DB') ? G5_MYSQL_DB : '';

        if ($host === '' || $user === '' || $real_db === '') return false;
        if ($dbname !== $real_db) return false;

        $conn = @mysqli_connect($host, $user, $dbpass, $dbname);
        if (!$conn) return false;
        @mysqli_close($conn);
        return true;
    }
}

// auth 검증 (AJAX) - submit 전에 틀린 정보면 alert용 (토큰 소비 X)
if ($action === 'verify_auth') {
    rb_check_admin_token(true);

    $dbname = isset($_POST['auth_dbname']) ? (string)$_POST['auth_dbname'] : '';
    $dbpass = isset($_POST['auth_dbpass']) ? (string)$_POST['auth_dbpass'] : '';
    $tokenv = isset($_POST['auth_token']) ? (string)$_POST['auth_token'] : '';

    $dbname = trim($dbname);
    $dbpass = (string)$dbpass;
    $tokenv = trim($tokenv);

    if ($dbname === '' || $dbpass === '' || $tokenv === '') {
        rb_json(false, 'DB명/DB비밀번호/토큰을 모두 입력해주세요.');
    }

    if (!rb_verify_db_password($dbname, $dbpass)) {
        rb_json(false, 'DB명 또는 DB비밀번호가 올바르지 않습니다.');
    }

    list($token_ok, $token_meta) = rb_verify_token_valid($tokenv);
    if (!$token_ok) {
        if (function_exists('set_session')) {
            set_session('rb_dbtool_auth_ok', '');
            set_session('rb_dbtool_auth_token', '');
        }
        rb_json(false, '토큰 파일이 없거나 만료되었습니다. 새로 생성해주세요.');
    }

    if (function_exists('set_session')) {
        set_session('rb_dbtool_auth_ok', '1');
        set_session('rb_dbtool_auth_token', $tokenv);
    }

    rb_json(true, 'OK', array('expire_text' => (string)($token_meta['expire_label'] ?? '')));
}

// 변경 작업 전 보안 팝업 값 검증 (여기서 토큰 1회용 소비)
if (!function_exists('rb_require_popup_auth')) {
    function rb_require_popup_auth() {

        if (rb_dbtool_session_authed()) {
            return;
        }

        $dbname = isset($_POST['auth_dbname']) ? (string)$_POST['auth_dbname'] : '';
        $dbpass = isset($_POST['auth_dbpass']) ? (string)$_POST['auth_dbpass'] : '';
        $token  = isset($_POST['auth_token']) ? (string)$_POST['auth_token'] : '';

        $dbname = trim($dbname);
        $token  = trim($token);

        if ($dbname === '' || $dbpass === '' || $token === '') {
            alert('보안 확인 값(DB명/DB비밀번호/토큰)이 필요합니다.');
        }

        if (!rb_verify_db_password($dbname, $dbpass)) {
            alert('DB명 또는 DB비밀번호가 올바르지 않습니다.');
        }

        list($token_ok, $token_meta) = rb_verify_token_valid($token);
        if (!$token_ok) {
            if (function_exists('set_session')) {
                set_session('rb_dbtool_auth_ok', '');
                set_session('rb_dbtool_auth_token', '');
            }
            alert('토큰 파일이 없거나 만료되었습니다. 새로 생성해주세요.');
        }
    }
}


// 토큰 생성 (AJAX)
if ($action === 'generate_token') {
    rb_check_admin_token(true);
    $expire_type = isset($_POST['expire_type']) ? (string)$_POST['expire_type'] : '1d';

    if (function_exists('set_session')) {
        set_session('rb_dbtool_auth_ok', '');
        set_session('rb_dbtool_auth_token', '');
    }

    $r = rb_gen_token_file($expire_type);
    if ($r[0]) rb_json(true, '', array('file' => $r[1], 'path' => $r[2], 'expire_type' => $r[3], 'expire_text' => $r[4]));
    rb_json(false, $r[1]);
}

// 이하부터는 "변경" 작업: 보안 팝업 인증으로 처리
if ($ajax === '1') {
    rb_check_admin_token(true);
}
rb_require_popup_auth();

// 엔진/문자셋 검증
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
    $supported_charsets[$cs] = true;
}

$back_tables = './rb_dbtool.php?mode=tables';
$back_cols   = './rb_dbtool.php?mode=columns&table='.urlencode($table);
$back_data   = './rb_dbtool.php?mode=data&table='.urlencode($table).'&page=1';

if ($action === 'drop_table') {

    rb_guard_table($table);
    sql_query("drop table `{$table}`", false);
    goto_url($back_tables);

} else if ($action === 'create_table') {

    $new_table = isset($_POST['new_table']) ? trim((string)$_POST['new_table']) : '';
    $engine    = isset($_POST['engine']) ? trim((string)$_POST['engine']) : '';
    $collation = isset($_POST['charset']) ? trim((string)$_POST['charset']) : '';
    $comment   = isset($_POST['comment']) ? trim((string)$_POST['comment']) : '';

    if (!rb_is_safe_ident($new_table)) alert('테이블명이 올바르지 않습니다.');
    if ($engine === '' || !isset($supported_engines[$engine])) alert('지원하지 않는 엔진입니다.');

    $supported_collations = array();
    $res_coll = sql_query("show collation");
    while ($cl = sql_fetch_array($res_coll)) {
        $co = trim((string)($cl['Collation'] ?? ''));
        $cs = trim((string)($cl['Charset'] ?? ''));
        if ($co !== '') $supported_collations[$co] = $cs;
    }

    if ($collation === '' || !isset($supported_collations[$collation])) alert('지원하지 않는 콜레이션입니다.');
    $charset = $supported_collations[$collation];

    $with_id = isset($_POST['with_id']) ? 1 : 0;

    $cols = array();
    $pk = '';

    if ($with_id) {
        $cols[] = "`id` int(11) not null auto_increment";
        $pk = "primary key (`id`)";
    }

    if (count($cols) === 0) {
        $cols[] = "`id` int(11) not null auto_increment";
        $pk = "primary key (`id`)";
    }

    $defs = implode(', ', $cols);
    if ($pk !== '') $defs .= ', '.$pk;

    $comment_sql = '';
    if ($comment !== '') $comment_sql = " comment '".sql_escape_string($comment)."'";

    $sql = "create table `{$new_table}` ({$defs}) engine={$engine} default charset={$charset} collate={$collation}{$comment_sql}";
    sql_query($sql, false);

    goto_url($back_tables);

} else if ($action === 'add_column') {

    rb_guard_table($table);

    $new_col     = isset($_POST['new_col']) ? trim((string)$_POST['new_col']) : '';
    $new_type    = isset($_POST['new_type']) ? trim((string)$_POST['new_type']) : '';
    $new_type_preset = isset($_POST['new_type_preset']) ? trim((string)$_POST['new_type_preset']) : 'varchar_255';
    $new_null    = isset($_POST['new_null']) ? 1 : 0;
    $new_pk      = isset($_POST['new_pk']) ? 1 : 0;
    $new_auto_increment = isset($_POST['new_auto_increment']) ? 1 : 0;
    $new_unique  = isset($_POST['new_unique']) ? 1 : 0;
    $new_index   = isset($_POST['new_index']) ? 1 : 0;
    $new_default = isset($_POST['new_default']) ? (string)$_POST['new_default'] : '';
    $new_comment = isset($_POST['new_comment']) ? (string)$_POST['new_comment'] : '';
    $new_after   = isset($_POST['new_after']) ? trim((string)$_POST['new_after']) : '';

    if (!rb_is_safe_ident($new_col)) alert('컬럼명이 올바르지 않습니다.');
    $exists_col = sql_fetch("show columns from `{$table}` like '".sql_escape_string($new_col)."'");
    if (!empty($exists_col)) alert('이미 존재하는 컬럼명입니다.');
    $new_type = rb_dbtool_resolve_col_type($new_type_preset, $new_type);
    $new_type = rb_guard_col_type($new_type);

    if ($new_pk) {
        $pk_check = sql_fetch("show keys from `{$table}` where Key_name = 'PRIMARY'");
        if (!empty($pk_check)) {
            alert('이 테이블에는 이미 기본키(PK)가 있습니다. 기본키는 테이블당 1개만 가능합니다.');
        }
    }

    $null_sql = $new_null ? 'NULL' : 'NOT NULL';
    $def_sql  = rb_sql_default($new_default);
    $com_sql  = rb_sql_comment($new_comment);
    $extra_sql = $new_auto_increment ? ' AUTO_INCREMENT' : '';

    if ($new_auto_increment) {
        $null_sql = 'NOT NULL';
    }

    $pos_sql = '';
    if ($new_after !== '') {
        if ($new_after === 'FIRST') {
            $pos_sql = ' FIRST';
        } else {
            if (!rb_is_safe_ident($new_after)) alert('컬럼 위치 값이 올바르지 않습니다.');
            $pos_sql = " AFTER `{$new_after}`";
        }
    }

    $sql = "alter table `{$table}` add column `{$new_col}` {$new_type} {$null_sql}";
    if ($def_sql !== '') $sql .= " {$def_sql}";
    $sql .= " {$com_sql}{$extra_sql}{$pos_sql}";

    sql_query($sql, false);

    if ($new_pk) {
        sql_query("alter table `{$table}` add primary key (`{$new_col}`)", false);
    }

    if ($new_unique) {
        $unique_name = 'uniq_'.$new_col;
        $exists_unique = sql_fetch("show index from `{$table}` where Key_name = '".sql_escape_string($unique_name)."'");
        if (!empty($exists_unique)) alert('이미 같은 UNIQUE 인덱스명이 존재합니다.');
        sql_query("alter table `{$table}` add unique `uniq_{$new_col}` (`{$new_col}`)", false);
    }

    if ($new_index) {
        $index_name = 'idx_'.$new_col;
        $exists_index = sql_fetch("show index from `{$table}` where Key_name = '".sql_escape_string($index_name)."'");
        if (!empty($exists_index)) alert('이미 같은 INDEX명이 존재합니다.');
        sql_query("alter table `{$table}` add index `idx_{$new_col}` (`{$new_col}`)", false);
    }

    goto_url($back_cols);

} else if ($action === 'drop_column') {

    rb_guard_table($table);

    $col = isset($_POST['col']) ? trim((string)$_POST['col']) : '';
    if (!rb_is_safe_ident($col)) alert('컬럼명이 올바르지 않습니다.');

    $sql = "alter table `{$table}` drop column `{$col}`";
    sql_query($sql, false);

    goto_url($back_cols);

} else if ($action === 'update_columns') {

    rb_guard_table($table);

    $col_name = isset($_POST['col_name']) ? (array)$_POST['col_name'] : array();
    $col_type_preset = isset($_POST['col_type_preset']) ? (array)$_POST['col_type_preset'] : array();
    $col_type = isset($_POST['col_type']) ? (array)$_POST['col_type'] : array();
    $col_null = isset($_POST['col_null']) ? (array)$_POST['col_null'] : array();
    $col_def  = isset($_POST['col_default']) ? (array)$_POST['col_default'] : array();
    $col_com  = isset($_POST['col_comment']) ? (array)$_POST['col_comment'] : array();

    $current = array();
    $rs = sql_query("show full columns from `{$table}`");
    while ($r = sql_fetch_array($rs)) {
        $fname = (string)($r['Field'] ?? '');
        if ($fname === '') continue;
        $current[$fname] = $r;
    }

    $cnt = count($col_name);

    for ($i=0; $i<$cnt; $i++) {
        $name = isset($col_name[$i]) ? trim((string)$col_name[$i]) : '';
        if (!rb_is_safe_ident($name)) continue;

        $preset_key = isset($col_type_preset[$i]) ? trim((string)$col_type_preset[$i]) : 'custom';
        $type = isset($col_type[$i]) ? trim((string)$col_type[$i]) : '';
        $type = rb_dbtool_resolve_col_type($preset_key, $type);
        $type = rb_guard_col_type($type);

        $nullv = isset($col_null[$i]) ? strtoupper(trim((string)$col_null[$i])) : 'NO';
        $null_sql = ($nullv === 'YES') ? 'NULL' : 'NOT NULL';

        $defv = isset($col_def[$i]) ? (string)$col_def[$i] : '';
        $comv = isset($col_com[$i]) ? (string)$col_com[$i] : '';

        $def_sql = rb_sql_default($defv);
        $com_sql = rb_sql_comment($comv);

        $extra_sql = '';
        if (isset($current[$name])) {
            $extra = (string)($current[$name]['Extra'] ?? '');
            if (stripos($extra, 'auto_increment') !== false) {
                $extra_sql = ' AUTO_INCREMENT';
                $null_sql = 'NOT NULL';
            }
        }

        $sql = "alter table `{$table}` modify column `{$name}` {$type} {$null_sql}";
        if ($def_sql !== '') $sql .= " {$def_sql}";
        $sql .= " {$com_sql}{$extra_sql}";

        sql_query($sql, false);
    }

    goto_url($back_cols);

} else if ($action === 'data_insert') {

    rb_guard_table($table);

    $row = isset($_POST['row']) ? (array)$_POST['row'] : array();

    $cols = array();
    $auto_cols = array();
    $rs = sql_query("show full columns from `{$table}`");
    while ($c = sql_fetch_array($rs)) {
        $f = (string)($c['Field'] ?? '');
        if ($f === '') continue;
        $cols[$f] = $c;
        $extra = strtolower((string)($c['Extra'] ?? ''));
        if (strpos($extra, 'auto_increment') !== false) $auto_cols[$f] = true;
    }

    $fields = array();
    $values = array();

    foreach ($cols as $f => $c) {
        if (isset($auto_cols[$f])) continue;
        if (!array_key_exists($f, $row)) continue;

        $v = (string)$row[$f];
        $v_trim = trim($v);

        $fields[] = "`{$f}`";

        if (strtoupper($v_trim) === 'NULL') {
            $values[] = "NULL";
        } else {
            $values[] = "'".sql_escape_string($v)."'";
        }
    }

    if (count($fields) === 0) alert('추가할 데이터가 없습니다.');

    $sql = "insert into `{$table}` (".implode(',', $fields).") values (".implode(',', $values).")";
    sql_query($sql, false);

    goto_url($back_data);

} else if ($action === 'data_update') {

    rb_guard_table($table);

    $row = isset($_POST['row']) ? (array)$_POST['row'] : array();
    $pk  = isset($_POST['pk']) ? (array)$_POST['pk'] : array();

    $pk_cols = array();
    $rs = sql_query("show full columns from `{$table}`");
    $cols = array();
    while ($c = sql_fetch_array($rs)) {
        $f = (string)($c['Field'] ?? '');
        if ($f === '') continue;
        $cols[$f] = $c;
        if ((string)($c['Key'] ?? '') === 'PRI') $pk_cols[] = $f;
    }
    if (count($pk_cols) === 0) alert('PK가 없어 수정할 수 없습니다.');

    $where = array();
    foreach ($pk_cols as $p) {
        $pv = isset($pk[$p]) ? (string)$pk[$p] : '';
        $where[] = "`{$p}`='".sql_escape_string($pv)."'";
    }

    $sets = array();
    foreach ($row as $f => $v) {
        if (!rb_is_safe_ident($f)) continue;
        if (in_array($f, $pk_cols, true)) continue;
        if (!isset($cols[$f])) continue;

        $v = (string)$v;
        $v_trim = trim($v);

        if (strtoupper($v_trim) === 'NULL') {
            $sets[] = "`{$f}`=NULL";
        } else {
            $sets[] = "`{$f}`='".sql_escape_string($v)."'";
        }
    }

    if (count($sets) === 0) alert('수정할 내용이 없습니다.');

    $sql = "update `{$table}` set ".implode(',', $sets)." where ".implode(' and ', $where)." limit 1";
    sql_query($sql, false);

    goto_url($back_data);

} else if ($action === 'data_delete') {

    rb_guard_table($table);

    $pk = isset($_POST['pk']) ? (array)$_POST['pk'] : array();

    $pk_cols = array();
    $rs = sql_query("show full columns from `{$table}`");
    while ($c = sql_fetch_array($rs)) {
        $f = (string)($c['Field'] ?? '');
        if ($f === '') continue;
        if ((string)($c['Key'] ?? '') === 'PRI') $pk_cols[] = $f;
    }
    if (count($pk_cols) === 0) alert('PK가 없어 삭제할 수 없습니다.');

    $where = array();
    foreach ($pk_cols as $p) {
        $pv = isset($pk[$p]) ? (string)$pk[$p] : '';
        $where[] = "`{$p}`='".sql_escape_string($pv)."'";
    }

    $where_sql = implode(' and ', $where);

    $sql = "delete from `{$table}` where {$where_sql} limit 1";
    sql_query($sql, false);

    goto_url($back_data);

} else {

    alert('잘못된 요청입니다.');

}
