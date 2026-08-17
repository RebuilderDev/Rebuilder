<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

define('RB_LICENSE_API_BASE', 'https://rebuilder.co.kr/api/rb-license/v1');

/**
 * 이 테이블에는 공식 홈페이지가 발급한 설치 인증정보만 저장합니다.
 * 빌더의 실제 설치·업데이트 DB 구조는 공식 홈페이지 API에서만 내려옵니다.
 */
function rb_license_client_table_install()
{
    return sql_query("CREATE TABLE IF NOT EXISTS `rb_license_client` (
        `client_id` tinyint unsigned NOT NULL DEFAULT 1,
        `installation_uuid` char(36) NOT NULL DEFAULT '',
        `installation_secret` varchar(160) NOT NULL DEFAULT '',
        `registration_status` varchar(24) NOT NULL DEFAULT 'pending',
        `usage_type` varchar(32) NOT NULL DEFAULT '',
        `environment_type` varchar(20) NOT NULL DEFAULT 'unknown',
        `license_state` varchar(24) NOT NULL DEFAULT 'pending',
        `status_notice` varchar(255) NOT NULL DEFAULT '',
        `registered_at` datetime DEFAULT NULL,
        `last_checked_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`client_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8", false) ? true : false;
}

function rb_license_client_get()
{
    if (!rb_license_client_table_install()) {
        return array();
    }
    $row = sql_fetch("SELECT * FROM rb_license_client WHERE client_id=1 LIMIT 1", false);
    return is_array($row) ? $row : array();
}

function rb_license_random_bytes($length)
{
    $bytes = false;
    if (function_exists('random_bytes')) {
        try {
            $bytes = random_bytes($length);
        } catch (Exception $e) {
            $bytes = false;
        }
    }
    if ($bytes === false && function_exists('openssl_random_pseudo_bytes')) {
        $strong = false;
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if (!$strong) {
            $bytes = false;
        }
    }
    return is_string($bytes) && strlen($bytes) === $length ? $bytes : false;
}

function rb_license_uuid_v4()
{
    $data = rb_license_random_bytes(16);
    if ($data === false) {
        return '';
    }
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function rb_license_prepare_identity()
{
    $client = rb_license_client_get();
    if (!empty($client['installation_uuid']) && !empty($client['installation_secret'])) {
        return $client;
    }

    $uuid = rb_license_uuid_v4();
    $secret_bytes = rb_license_random_bytes(32);
    if ($uuid === '' || $secret_bytes === false) {
        return array('error' => '안전한 설치 인증정보를 생성할 수 없는 서버 환경입니다.');
    }
    $secret = 'RBC'.strtoupper(bin2hex($secret_bytes));
    $now = defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s');
    $saved = sql_query("INSERT INTO rb_license_client
            (client_id, installation_uuid, installation_secret, registration_status,
             usage_type, environment_type, license_state, status_notice, created_at, updated_at)
        VALUES
            (1, '".sql_real_escape_string($uuid)."', '".sql_real_escape_string($secret)."', 'pending',
             '', 'unknown', 'pending', '', '".sql_real_escape_string($now)."', '".sql_real_escape_string($now)."')
        ON DUPLICATE KEY UPDATE
            installation_uuid=IF(installation_uuid='', VALUES(installation_uuid), installation_uuid),
            installation_secret=IF(installation_secret='', VALUES(installation_secret), installation_secret),
            updated_at=VALUES(updated_at)", false);
    if (!$saved) {
        return array('error' => '설치 인증정보를 저장하지 못했습니다. DB 권한을 확인해 주세요.');
    }
    return rb_license_client_get();
}

function rb_license_current_domain()
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
    if ($host === '' && isset($_SERVER['SERVER_NAME'])) {
        $host = trim((string) $_SERVER['SERVER_NAME']);
    }
    return substr($host, 0, 255);
}

function rb_license_server_fingerprint()
{
    $parts = array(
        defined('G5_PATH') ? G5_PATH : '',
        defined('G5_DATA_PATH') ? G5_DATA_PATH : '',
        defined('G5_MYSQL_HOST') ? G5_MYSQL_HOST : '',
        defined('G5_MYSQL_DB') ? G5_MYSQL_DB : '',
        isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '',
        isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
        function_exists('php_uname') ? php_uname('n') : '',
    );
    return hash('sha256', implode('|', $parts));
}

function rb_license_api_payload($client)
{
    return array(
        'installation_uuid' => isset($client['installation_uuid']) ? $client['installation_uuid'] : '',
        'installation_secret' => isset($client['installation_secret']) ? $client['installation_secret'] : '',
        'domain' => rb_license_current_domain(),
        'builder_version' => defined('RB_VER') ? RB_VER : '2.2.7',
        'gnuboard_version' => defined('G5_GNUBOARD_VER') ? G5_GNUBOARD_VER : '',
        'php_version' => PHP_VERSION,
        'server_fingerprint_hash' => rb_license_server_fingerprint(),
    );
}

function rb_license_http_post($path, $payload)
{
    $url = RB_LICENSE_API_BASE.'/'.ltrim($path, '/');
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        return array('success' => false, 'message' => '전송할 인증정보를 만들지 못했습니다.');
    }

    $response = false;
    $connection_error = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $curl_options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
            CURLOPT_USERAGENT => 'Rebuilder/'.(defined('RB_VER') ? RB_VER : '2.2.7'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
        );
        $ca_candidates = array(
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            dirname(PHP_BINARY).'/curl-ca-bundle.crt',
            dirname(PHP_BINARY).'/extras/ssl/cacert.pem',
        );
        foreach ($ca_candidates as $ca_file) {
            if ($ca_file && is_readable($ca_file)) {
                $curl_options[CURLOPT_CAINFO] = $ca_file;
                break;
            }
        }
        curl_setopt_array($ch, $curl_options);
        $response = curl_exec($ch);
        if ($response === false) {
            $connection_error = curl_error($ch);
        }
        curl_close($ch);
    }

    // 일부 로컬 PHP는 cURL과 OpenSSL의 CA 설정이 서로 다르므로 검증을 유지한 대체 통신을 시도합니다.
    if ($response === false && function_exists('file_get_contents')) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ),
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ),
        ));
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $last_error = error_get_last();
            $stream_error = isset($last_error['message']) ? $last_error['message'] : '';
            if ($stream_error !== '') {
                $connection_error .= ($connection_error !== '' ? ' / ' : '').$stream_error;
            }
        }
    }

    if ($response === false || $response === '') {
        $detail = $connection_error !== '' ? ' ('.$connection_error.')' : '';
        return array(
            'success' => false,
            'message' => '인증 서버에 연결할 수 없습니다. 외부 HTTPS 통신과 서버의 CA 인증서 설정을 확인해 주세요.'.$detail,
        );
    }
    if (strlen($response) > 5 * 1024 * 1024) {
        return array('success' => false, 'message' => '인증 서버의 응답 크기가 올바르지 않습니다.');
    }

    $decoded = json_decode(trim($response), true);
    if (!is_array($decoded) || !array_key_exists('success', $decoded)) {
        return array('success' => false, 'message' => '인증 서버의 응답 형식을 확인할 수 없습니다.');
    }
    if (empty($decoded['success'])) {
        $message = isset($decoded['error']['message']) ? trim((string) $decoded['error']['message']) : '요청을 처리하지 못했습니다.';
        return array(
            'success' => false,
            'code' => isset($decoded['error']['code']) ? (string) $decoded['error']['code'] : '',
            'message' => $message,
        );
    }
    return array('success' => true, 'data' => isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : array());
}

function rb_license_save_status($data, $registered = false)
{
    $registration_status = isset($data['installation_status'])
        ? substr((string) $data['installation_status'], 0, 24)
        : (isset($data['state']) ? substr((string) $data['state'], 0, 24) : 'registered');
    $usage_type = isset($data['usage_type']) ? substr((string) $data['usage_type'], 0, 32) : '';
    $environment = isset($data['environment_type']) ? substr((string) $data['environment_type'], 0, 20) : 'unknown';
    $license_state = isset($data['license_state']) ? substr((string) $data['license_state'], 0, 24) : 'pending';
    $notice = isset($data['notice']) ? substr((string) $data['notice'], 0, 255) : '';
    return sql_query("UPDATE rb_license_client
                         SET registration_status='".sql_real_escape_string($registration_status)."',
                             usage_type=IF('".sql_real_escape_string($usage_type)."'='', usage_type, '".sql_real_escape_string($usage_type)."'),
                             environment_type='".sql_real_escape_string($environment)."',
                             license_state='".sql_real_escape_string($license_state)."',
                             status_notice='".sql_real_escape_string($notice)."',
                             registered_at=".($registered ? 'IFNULL(registered_at, NOW())' : 'registered_at').",
                             last_checked_at=NOW(), updated_at=NOW()
                       WHERE client_id=1", false) ? true : false;
}

function rb_license_register_token($install_token)
{
    $install_token = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $install_token));
    if (!preg_match('/^RBI[A-F0-9]{32}$/', $install_token)) {
        return array('success' => false, 'message' => '설치 토큰 형식을 확인해 주세요.');
    }
    $client = rb_license_prepare_identity();
    if (isset($client['error'])) {
        return array('success' => false, 'message' => $client['error']);
    }
    if (!empty($client['registered_at']) && $client['registration_status'] !== 'pending') {
        return array('success' => false, 'message' => '이미 설치 토큰이 등록된 빌더입니다.');
    }
    $payload = rb_license_api_payload($client);
    $payload['install_token'] = $install_token;
    $response = rb_license_http_post('register.php', $payload);
    if (empty($response['success'])) {
        return $response;
    }
    rb_license_save_status($response['data'], true);
    return array('success' => true, 'data' => $response['data']);
}

function rb_license_check_remote()
{
    $client = rb_license_client_get();
    if (empty($client['installation_uuid']) || empty($client['installation_secret']) || empty($client['registered_at'])) {
        return array('success' => false, 'code' => 'token_required', 'message' => '설치 토큰을 먼저 등록해 주세요.');
    }
    $response = rb_license_http_post('check.php', rb_license_api_payload($client));
    if (!empty($response['success'])) {
        rb_license_save_status($response['data'], false);
    }
    return $response;
}

function rb_license_fetch_schema()
{
    $client = rb_license_client_get();
    if (empty($client['installation_uuid']) || empty($client['installation_secret']) || empty($client['registered_at'])) {
        return array('success' => false, 'code' => 'token_required', 'message' => '설치 토큰을 먼저 등록해 주세요.');
    }
    $payload = rb_license_api_payload($client);
    $payload['g5_table_prefix'] = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
    $payload['g5_shop_table_prefix'] = defined('G5_SHOP_TABLE_PREFIX') ? G5_SHOP_TABLE_PREFIX : 'g5_shop_';
    return rb_license_http_post('schema.php', $payload);
}

function rb_license_valid_identifier($name)
{
    return is_string($name) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $name);
}

function rb_license_apply_bootstrap_sql($statements, &$changed)
{
    if (!is_array($statements)) {
        return '최초 설치 구조가 올바르지 않습니다.';
    }
    foreach ($statements as $sql) {
        $sql = trim((string) $sql);
        if (!preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`([A-Za-z0-9_]+)`\s*\(/i', $sql, $match)
            || strpos($sql, ';') !== false || strlen($sql) > 100000) {
            return '최초 설치 구조의 안전성을 확인할 수 없습니다.';
        }
        $table = $match[1];
        $before = sql_fetch("SELECT COUNT(*) AS cnt
                               FROM information_schema.TABLES
                              WHERE TABLE_SCHEMA='".sql_real_escape_string(G5_MYSQL_DB)."'
                                AND TABLE_NAME='".sql_real_escape_string($table)."'", false);
        if (!sql_query($sql, false)) {
            return '빌더 기본 테이블을 생성하지 못했습니다: '.mysqli_error($GLOBALS['g5']['connect_db']);
        }
        if (empty($before['cnt'])) {
            $changed = true;
        }
    }
    return '';
}

function rb_license_apply_schema_tables($schema, &$changed)
{
    if (empty($schema['tables']) || !is_array($schema['tables'])) {
        return 'DB 업데이트 구조가 올바르지 않습니다.';
    }
    foreach ($schema['tables'] as $table => $info) {
        if (!rb_license_valid_identifier($table) || empty($info['columns']) || !is_array($info['columns'])) {
            return 'DB 업데이트 테이블 정보를 확인할 수 없습니다.';
        }
        $exists = sql_fetch("SELECT COUNT(*) AS cnt
                               FROM information_schema.TABLES
                              WHERE TABLE_SCHEMA='".sql_real_escape_string(G5_MYSQL_DB)."'
                                AND TABLE_NAME='".sql_real_escape_string($table)."'", false);
        if (empty($exists['cnt'])) {
            $definitions = array();
            foreach ($info['columns'] as $column => $definition) {
                $definition = trim((string) $definition);
                if (!rb_license_valid_identifier($column) || $definition === '' || strlen($definition) > 1000
                    || strpos($definition, ';') !== false || strpos($definition, '--') !== false
                    || strpos($definition, '/*') !== false) {
                    return '['.$table.'] 생성 구조를 확인할 수 없습니다.';
                }
                $definitions[] = '`'.$column.'` '.$definition;
            }
            if (!empty($info['primary_key'])) {
                if (!rb_license_valid_identifier($info['primary_key'])) {
                    return '['.$table.'] 기본키 정보를 확인할 수 없습니다.';
                }
                $definitions[] = 'PRIMARY KEY (`'.$info['primary_key'].'`)';
            }
            if (!sql_query('CREATE TABLE `'.$table.'` ('.implode(', ', $definitions).')', false)) {
                return '['.$table.'] 테이블 생성 실패: '.mysqli_error($GLOBALS['g5']['connect_db']);
            }
            $changed = true;
            continue;
        }

        $add_columns = array();
        foreach ($info['columns'] as $column => $definition) {
            $definition = trim((string) $definition);
            if (!rb_license_valid_identifier($column) || $definition === '' || strlen($definition) > 1000
                || strpos($definition, ';') !== false || strpos($definition, '--') !== false
                || strpos($definition, '/*') !== false) {
                return '['.$table.'] 컬럼 구조를 확인할 수 없습니다.';
            }
            $column_result = sql_query("SHOW COLUMNS FROM `{$table}` LIKE '".sql_real_escape_string($column)."'", false);
            if (!$column_result) {
                return '['.$table.'] ['.$column.'] 컬럼을 확인하지 못했습니다.';
            }
            if (mysqli_num_rows($column_result) === 0) {
                $add_columns[] = 'ADD `'.$column.'` '.$definition;
            }
        }
        if ($add_columns && !sql_query('ALTER TABLE `'.$table.'` '.implode(', ', $add_columns), false)) {
            return '['.$table.'] 컬럼 업데이트 실패: '.mysqli_error($GLOBALS['g5']['connect_db']);
        }
        if ($add_columns) {
            $changed = true;
        }
    }
    return '';
}

function rb_license_apply_seed($seeds, &$changed)
{
    if (!is_array($seeds)) {
        return '';
    }
    foreach ($seeds as $seed) {
        if (empty($seed['table']) || !rb_license_valid_identifier($seed['table'])
            || empty($seed['values']) || !is_array($seed['values'])) {
            return '기본 설정값 구조가 올바르지 않습니다.';
        }
        $table = $seed['table'];
        if (!empty($seed['when_empty'])) {
            $count = sql_fetch("SELECT COUNT(*) AS cnt FROM `{$table}`", false);
            if (!empty($count['cnt'])) {
                continue;
            }
        }
        $columns = array();
        $values = array();
        foreach ($seed['values'] as $column => $value) {
            if (!rb_license_valid_identifier($column) || is_array($value)) {
                return '기본 설정값 항목이 올바르지 않습니다.';
            }
            $columns[] = '`'.$column.'`';
            $value = $value === '__NOW__' ? (defined('G5_TIME_YMDHIS') ? G5_TIME_YMDHIS : date('Y-m-d H:i:s')) : (string) $value;
            $values[] = "'".sql_real_escape_string($value)."'";
        }
        if (!sql_query('INSERT INTO `'.$table.'` ('.implode(',', $columns).') VALUES ('.implode(',', $values).')', false)) {
            return '['.$table.'] 기본 설정값을 저장하지 못했습니다.';
        }
        $changed = true;
    }
    return '';
}

function rb_license_apply_remote_schema($data)
{
    if (!is_array($data) || empty($data['schema_version'])) {
        return array('success' => false, 'message' => 'DB 업데이트 응답이 올바르지 않습니다.');
    }
    $changed = false;
    $error = rb_license_apply_bootstrap_sql(isset($data['bootstrap_sql']) ? $data['bootstrap_sql'] : array(), $changed);
    if ($error === '') {
        $error = rb_license_apply_schema_tables(isset($data['schema']) ? $data['schema'] : array(), $changed);
    }
    if ($error === '') {
        $error = rb_license_apply_seed(isset($data['bootstrap_seed']) ? $data['bootstrap_seed'] : array(), $changed);
    }
    if ($error !== '') {
        return array('success' => false, 'message' => $error);
    }
    return array(
        'success' => true,
        'changed' => $changed,
        'message' => $changed
            ? 'DB 설치 및 업데이트가 완료되었습니다.'
            : '현재 DB가 최신 상태입니다.',
    );
}
