<?php
if (!defined('_GNUBOARD_')) exit;

// 설치된 코어를 우선 사용하고 없는 보안 검사만 동일 규칙으로 보완한다.

if (!function_exists('get_super_admin_type')) {
    function get_super_admin_type($admin_type)
    {
        return $admin_type === 'super' ? 'super' : '';
    }
}

if (!function_exists('is_content_include_allowed')) {
    function is_content_include_allowed($path){
        if( !is_string($path) || $path === '' ){
            return false;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if( !in_array($ext, array('php', 'htm', 'html')) ){
            return false;
        }

        $real = @realpath($path);
        if( $real !== false && defined('G5_DATA_PATH') ){
            $data_real = @realpath(G5_DATA_PATH);
            if( $data_real !== false ){
                $real_norm = str_replace('\\', '/', $real);
                $data_norm = rtrim(str_replace('\\', '/', $data_real), '/').'/';
                if( strpos($real_norm, $data_norm) === 0 ){
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists('is_disallowed_active_filename')) {
    function is_disallowed_active_filename($filename)
    {
        if (!is_string($filename)) return true;
        if (preg_match('/[\x00-\x1f\x7f]/', $filename)) return true;
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = rtrim($filename, ' .');
        // 다중 확장자 및 Windows 대체 데이터 스트림 표기도 차단한다.
        return (bool) preg_match('/\.(?:svgz?|xhtml|xht|xml|xsl|xslt|mht|mhtml|htc)(?:[. :]|$)/i', $filename);
    }
}

if (!function_exists('get_sql_affected_rows')) {
    function get_sql_affected_rows($link=null)
    {
        global $g5;

        if (!$link) {
            $link = $g5['connect_db'];
        }

        if (function_exists('mysqli_affected_rows') && G5_MYSQLI_USE) {
            return @mysqli_affected_rows($link);
        } else {
            return @mysql_affected_rows($link);
        }
    }
}

if (!function_exists('get_email_certify_token')) {
    function get_email_certify_token()
    {
        return get_random_token_string(16) . '.' . G5_SERVER_TIME;
    }
}

if (!function_exists('check_request_origin')) {
    function check_request_origin($redirect_url = '')
    {
        // 환경에 따라 opt-out 가능
        if (defined('G5_DISABLE_ORIGIN_CHECK') && G5_DISABLE_ORIGIN_CHECK) {
            return true;
        }

        // GET 요청은 검증하지 않음 (CSRF는 상태 변경 요청에만 의미 있음)
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        if ($method === 'GET' || $method === 'HEAD') {
            return true;
        }

        if (!$redirect_url) {
            $redirect_url = defined('G5_URL') ? G5_URL : '/';
        }

        // Origin 우선, 없으면 Referer 사용
        $origin  = isset($_SERVER['HTTP_ORIGIN'])  ? trim($_SERVER['HTTP_ORIGIN'])  : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : '';
        $source  = $origin !== '' ? $origin : $referer;

        if ($source === '') {
            alert('올바른 경로로 접근해 주십시오.', $redirect_url);
        }

        $source_host = @parse_url($source, PHP_URL_HOST);
        if (!$source_host) {
            alert('올바른 경로로 접근해 주십시오.', $redirect_url);
        }

        // config.php의 G5_URL에서 호스트 추출 (HTTP_HOST보다 신뢰할 수 있음)
        $server_host = defined('G5_URL') ? @parse_url(G5_URL, PHP_URL_HOST) : '';
        if (!$server_host) {
            $server_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $colon = strpos($server_host, ':');
            if ($colon !== false) {
                $server_host = substr($server_host, 0, $colon);
            }
        }

        // www. 접두사 제거하여 비교 (www.example.com == example.com)
        $source_host = preg_replace('/^www\./i', '', $source_host);
        $server_host = preg_replace('/^www\./i', '', $server_host);

        if (!$server_host || strcasecmp($source_host, $server_host) !== 0) {
            alert('올바른 경로로 접근해 주십시오.', $redirect_url);
        }

        return true;
    }
}

function rb_legacy_security_mail_base_url($domain = null)
{
    if ($domain === null) {
        $domain = defined('G5_DOMAIN') ? G5_DOMAIN : '';
    }
    if (!is_string($domain) || $domain === '' || preg_match('/[\x00-\x20\x7f\\\\<>"\']/', $domain)) {
        return false;
    }
    $parts = @parse_url($domain);
    if (!$parts || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
        || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])) {
        return false;
    }
    // 호스트에는 포트 구분용 IPv6 대괄호 외의 URL 구문을 허용하지 않는다.
    $host = $parts['host'];
    if ($host[0] === '[') {
        if (substr($host, -1) !== ']' || !filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return false;
    } elseif (!preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9.])?$/i', $host)) {
        return false;
    }
    if ($host[0] !== '[') {
        if (strlen($host) > 254) return false;
        $hostname = substr($host, -1) === '.' ? substr($host, 0, -1) : $host;
        foreach (explode('.', $hostname) as $label) {
            if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $label)) return false;
        }
    }
    if (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535)) return false;
    if (isset($parts['path']) && (preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|7f|2f|5c)/i', $parts['path'])
        || preg_match('~(?:^|/)(?:\.|%2e){1,2}(?:/|$)~i', $parts['path']))) return false;

    return rtrim($domain, '/');
}

// 신 코어의 고정 도메인 정책을 유지한다. 구 코어만 기존 사이트 URL 규약을 따른다.
function rb_security_mail_base_url()
{
    if (function_exists('g5_security_mail_base_url')) return g5_security_mail_base_url();
    return rb_legacy_security_mail_base_url(defined('G5_DOMAIN') && G5_DOMAIN ? G5_DOMAIN : G5_URL);
}
function rb_require_security_mail_url()
{
    if (function_exists('g5_require_security_mail_url')) return g5_require_security_mail_url();
    $url = rb_security_mail_base_url();
    if ($url === false) { alert('메일 인증을 위한 사이트 주소를 확인해 주세요.'); exit; }
    return $url;
}
function rb_email_cert_key($member)
{
    return function_exists('get_email_cert_key') ? get_email_cert_key($member['mb_id'], $member['mb_datetime'])
        : md5($member['mb_ip'].$member['mb_datetime']);
}

function rb_core_table_has_column($table, $column)
{
    static $columns = array();
    if (!preg_match('/\A[A-Za-z0-9_]+\z/', (string)$table)) return false;
    if (!isset($columns[$table])) {
        $columns[$table] = array();
        $result = sql_query('SHOW COLUMNS FROM `'.$table.'`', false);
        if ($result) while ($row = sql_fetch_array($result)) $columns[$table][$row['Field']] = true;
    }
    return isset($columns[$table][$column]);
}

// 구 회원 테이블에서도 기본 가입·수정은 유지하고 동의 이력은 회원 메모에 보존한다.
function rb_member_consent_sql($marketing, $thirdparty, $log)
{
    global $g5;
    $sql = '';
    foreach (array('mb_marketing_agree'=>$marketing, 'mb_thirdparty_agree'=>$thirdparty) as $column=>$value) {
        if (rb_core_table_has_column($g5['member_table'], $column)) $sql .= ', '.$column.'='.(int)(bool)$value;
    }
    if ($log !== '') {
        $column = rb_core_table_has_column($g5['member_table'], 'mb_agree_log') ? 'mb_agree_log' : 'mb_memo';
        $sql .= ', '.$column."=CONCAT('".sql_real_escape_string($log)."', IFNULL(".$column.", ''))";
    }
    return $sql;
}
