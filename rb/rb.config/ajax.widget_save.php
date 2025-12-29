<?php
// rb/rb.lib/ajax.widget_save.php
include_once('../../common.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

@ini_set('display_errors', '0');
@error_reporting(E_ALL);

// // JSON 응답 유틸
function rb_json_exit($arr)
{
    if (!is_array($arr)) $arr = array('ok' => false, 'msg' => 'Invalid response');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// // 파일/폴더 진단(권한 문제 확인용)
function rb_fs_diag($path)
{
    $out = array();
    $out['path'] = (string)$path;
    $out['exists'] = file_exists($path) ? 1 : 0;
    $out['is_dir'] = is_dir($path) ? 1 : 0;
    $out['is_file'] = is_file($path) ? 1 : 0;
    $out['writable'] = is_writable($path) ? 1 : 0;

    $perms = @fileperms($path);
    if ($perms !== false) $out['perms'] = substr(sprintf('%o', $perms), -4);

    $owner = @fileowner($path);
    if ($owner !== false) $out['owner_uid'] = $owner;

    $group = @filegroup($path);
    if ($group !== false) $out['group_gid'] = $group;

    if (function_exists('posix_geteuid')) $out['php_euid'] = @posix_geteuid();
    if (function_exists('posix_getegid')) $out['php_egid'] = @posix_getegid();

    return $out;
}

// 1) 슈퍼관리자만
if (!isset($is_admin) || $is_admin !== 'super') {
    rb_json_exit(array('ok' => false, 'msg' => '권한이 없습니다.'));
}

// 2) CSRF
$csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
if (!isset($_SESSION['rb_widget_csrf']) || !hash_equals((string)$_SESSION['rb_widget_csrf'], $csrf)) {
    rb_json_exit(array('ok' => false, 'msg' => 'CSRF 검증 실패'));
}

// 3) Origin/Referer (동일 호스트만 허용)
$host    = parse_url(G5_URL, PHP_URL_HOST);
$origin  = isset($_SERVER['HTTP_ORIGIN']) ? (string)$_SERVER['HTTP_ORIGIN'] : '';
$referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';

$origin_host  = $origin ? parse_url($origin, PHP_URL_HOST) : '';
$referer_host = $referer ? parse_url($referer, PHP_URL_HOST) : '';

$bad_origin  = ($origin_host && $host && $origin_host !== $host);
$bad_referer = ($referer_host && $host && $referer_host !== $host);

if ($bad_origin || $bad_referer) {
    rb_json_exit(array('ok' => false, 'msg' => '잘못된 요청 출처'));
}

// ---- 입력 ----
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    rb_json_exit(array('ok' => false, 'msg' => 'Invalid method'));
}

$folder    = isset($_POST['folder']) ? trim((string)$_POST['folder']) : '';
$overwrite = isset($_POST['overwrite']) ? trim((string)$_POST['overwrite']) : '0';
$code      = isset($_POST['code']) ? (string)$_POST['code'] : '';

$pattern = ($overwrite === '1')
    ? '/^(?!\.)(?!.*\.\.)[A-Za-z0-9_.-]+$/'
    : '/^(?!\.)(?!.*\.\.)[A-Za-z0-9_-]+$/';

if ($folder === '' ||
    !preg_match($pattern, $folder) ||
    strpos($folder, '/') !== false || strpos($folder, '\\') !== false) {
    rb_json_exit(array('ok' => false, 'msg' => '폴더명 형식 오류'));
}

if ($code === '') {
    rb_json_exit(array('ok' => false, 'msg' => '코드가 비어있습니다.'));
}

// // 위험 함수 필터(기본 방어)
$deny = '/\b(eval|exec|system|shell_exec|popen|proc_open|passthru|pcntl_\w+)\s*\(/i';
if (preg_match($deny, $code)) {
    rb_json_exit(array('ok' => false, 'msg' => '위험 함수 사용이 감지되어 차단되었습니다.'));
}

// // 이스케이프 원복/개행 정규화
$code = preg_replace('/\\\\([\'"])/', '$1', $code);
$code = str_replace("\r\n", "\n", $code);

// ---- 경로 준비 ----
$BASE = G5_PATH . '/rb/rb.widget';

if (!is_dir($BASE)) {
    rb_json_exit(array('ok' => false, 'msg' => 'BASE 경로가 없습니다: ' . $BASE));
}

$target_dir  = $BASE . '/' . $folder;
$target_file = $target_dir . '/widget.php';

// // 폴더가 없으면 BASE에 mkdir 해야 하므로 BASE writable 체크
if (!is_dir($target_dir)) {
    if (!is_writable($BASE)) {
        rb_json_exit(array(
            'ok' => false,
            'msg' => '위젯 폴더 생성 권한이 없습니다(BASE)',
            'raw' => array(
                'base' => rb_fs_diag($BASE),
                'target_dir' => $target_dir
            )
        ));
    }

    $mk = @mkdir($target_dir, 0755, true);
    clearstatcache();

    if (!$mk && !is_dir($target_dir)) {
        $e = error_get_last();
        rb_json_exit(array(
            'ok' => false,
            'msg' => '디렉터리 생성 실패',
            'raw' => ($e['message'] ?? '') . ' path=' . $target_dir
        ));
    }
}

// // 폴더가 이미 있으면 target_dir writable이면 저장 가능 (BASE는 굳이 안 봄)
if (!is_writable($target_dir)) {
    rb_json_exit(array(
        'ok' => false,
        'msg' => '대상 폴더에 쓰기 권한이 없습니다',
        'raw' => array(
            'dir' => rb_fs_diag($target_dir),
            'base' => rb_fs_diag($BASE)
        )
    ));
}

// // 경로 검증 (realpath가 실패하는 호스팅 대비)
$base_real = realpath($BASE);
$dir_real  = realpath($target_dir);

if ($base_real === false) {
    rb_json_exit(array(
        'ok' => false,
        'msg' => 'realpath(BASE) 실패: 권한/설정 확인',
        'raw' => $BASE
    ));
}

$check_dir = $dir_real ? $dir_real : $target_dir;

$prefixOk = (strpos(
    rtrim($check_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
    rtrim($base_real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
) === 0);

if (!$prefixOk) {
    rb_json_exit(array(
        'ok' => false,
        'msg' => '경로 검증 실패',
        'raw' => 'base_real=' . $base_real . '; dir_real=' . ($dir_real ? $dir_real : '(false)') . '; target=' . $target_dir
    ));
}

// // 덮어쓰기 정책
if (file_exists($target_file) && $overwrite !== '1') {
    rb_json_exit(array('ok' => false, 'msg' => '이미 파일이 존재합니다.'));
}

// // 파일이 이미 있고 덮어쓰기면 파일 자체도 writable 체크(환경에 따라 파일만 막히는 경우)
if (file_exists($target_file) && $overwrite === '1' && !is_writable($target_file)) {
    rb_json_exit(array(
        'ok' => false,
        'msg' => '대상 파일에 쓰기 권한이 없습니다',
        'raw' => rb_fs_diag($target_file)
    ));
}

// ==== (신규) DATA 동일폴더 백업 준비 ====
$data_base_dir = G5_DATA_PATH . '/rb_log/custom_widget/' . $folder;
if (!is_dir($data_base_dir)) { @mkdir($data_base_dir, 0755, true); }

$backup_dir = $data_base_dir . '/backup';
if (!is_dir($backup_dir)) { @mkdir($backup_dir, 0755, true); }

// 이전 해시 (감사 로그용)
$prev_hash = file_exists($target_file) ? @hash_file('sha256', $target_file) : null;

// ==== (변경) 백업: DATA 동일폴더에 저장 ====
if (file_exists($target_file)) {
    $backup_name = 'widget.php.bak_' . date('Ymd_His');
    $backup_path = $backup_dir . '/' . $backup_name;

    if (!@copy($target_file, $backup_path)) {
        $e = error_get_last();
        rb_json_exit(array(
            'ok' => false,
            'msg' => '백업 파일 생성 실패',
            'raw' => ($e['message'] ?? '') . ' backup_path=' . $backup_path
        ));
    }
    @chmod($backup_path, 0644);
}

// UTF-8 보정(가능한 환경에서만)
if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
    if (!mb_detect_encoding($code, 'UTF-8', true)) {
        $code = mb_convert_encoding($code, 'UTF-8');
    }
}

/* =========================
   원자 저장(+직접 저장 Fallback)
   ========================= */
$tmp = $target_file . '.tmp_' . uniqid('', true);
$bytes = @file_put_contents($tmp, $code, LOCK_EX);

if ($bytes === false) {
    // ---- Fallback: 임시파일이 막히는 환경에서는 바로 대상 파일에 저장 ----
    $fp = @fopen($target_file, 'wb');
    if ($fp) {
        $w = @fwrite($fp, $code);
        @fflush($fp);
        @fclose($fp);

        if ($w !== false) {
            @chmod($target_file, 0644);
            @unlink($tmp);
            goto __SAVE_DONE;
        }
    }

    $e = error_get_last();
    rb_json_exit(array(
        'ok' => false,
        'msg' => '파일 쓰기 실패(tmp/direct)',
        'raw' => ($e['message'] ?? ''),
        'diag' => array(
            'base' => rb_fs_diag($BASE),
            'dir' => rb_fs_diag($target_dir),
            'file' => rb_fs_diag($target_file)
        )
    ));
}

// 임시파일 저장 성공 시 rename으로 원자 교체 (rename 실패 시 copy 대체)
if (!@rename($tmp, $target_file)) {
    $e = error_get_last();

    if (@copy($tmp, $target_file)) {
        @chmod($target_file, 0644);
        @unlink($tmp);
    } else {
        @unlink($tmp);
        rb_json_exit(array(
            'ok' => false,
            'msg' => '파일 교체 실패(rename/copy)',
            'raw' => ($e['message'] ?? '') . ' target=' . $target_file,
            'diag' => array(
                'dir' => rb_fs_diag($target_dir),
                'file' => rb_fs_diag($target_file)
            )
        ));
    }
}

@chmod($target_file, 0644);

__SAVE_DONE:

// ---- 저장 로그 (data/rb_log/custom_widget/{folder}) ----
$log_dir = $data_base_dir;
if (!is_dir($log_dir)) { @mkdir($log_dir, 0755, true); }

$log_file = $log_dir . '/save.log.txt';
$now  = date('Y-m-d H:i:s');
$ip   = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '-';
$mb   = isset($member['mb_id']) ? (string)$member['mb_id'] : '-';

$line = sprintf(
    "[%s] %s %s SAVE folder=%s size=%d prev=%s backup_dir=%s\n",
    $now,
    $ip,
    $mb,
    $folder,
    strlen($code),
    ($prev_hash ? $prev_hash : '-'),
    $backup_dir
);

@file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
@chmod($log_file, 0644);

// ---- 응답 ----
$public_hint = str_replace(G5_PATH, '', $target_file);
rb_json_exit(array('ok' => true, 'path' => $public_hint));
