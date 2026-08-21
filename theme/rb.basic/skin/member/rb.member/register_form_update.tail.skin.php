<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 새 알림 설정 UI가 포함된 회원가입/정보수정 요청만 별도 설정을 저장합니다.
// 테이블 구조는 빌더가 만들지 않고 공식 홈페이지 API의 DB 업데이트로만 설치합니다.
if (($w === '' || $w === 'u')
    && isset($_POST['rb_notification_preference_present'])
    && (string) $_POST['rb_notification_preference_present'] === '1'
    && function_exists('rb_notification_save_preference')) {
    $rb_notification_agree = isset($_POST['rb_notification_agree']) && (string) $_POST['rb_notification_agree'] === '1';
    $rb_notify_push = $rb_notification_agree
        && isset($_POST['rb_notify_push'])
        && (string) $_POST['rb_notify_push'] === '1';
    $rb_notify_site = $rb_notification_agree
        && isset($_POST['rb_notify_site'])
        && (string) $_POST['rb_notify_site'] === '1';
    $rb_category_preferences = array();
    foreach (array('comment', 'reply', 'shop', 'subscribe', 'other') as $rb_notification_type) {
        $rb_post_key = 'rb_notify_'.$rb_notification_type;
        $rb_category_preferences['notify_'.$rb_notification_type] = $rb_notify_site
            && isset($_POST[$rb_post_key])
            && (string) $_POST[$rb_post_key] === '1';
    }
    rb_notification_save_preference($mb_id, $rb_notify_push, $rb_notify_site, $rb_category_preferences);
}

if (($w == "" || $w == "u") && function_exists('rb_sms_cert_is_enabled') && rb_sms_cert_is_enabled() && get_session('ss_rb_sms_cert_verified') === '1') {
    $rb_sms_cert_hp = function_exists('rb_sms_cert_normalize_hp') ? rb_sms_cert_normalize_hp(get_session('ss_rb_sms_cert_hp')) : preg_replace('/[^0-9]/', '', (string)get_session('ss_rb_sms_cert_hp'));
    if ($rb_sms_cert_hp !== '') {
        sql_query("UPDATE {$g5['member_table']}
            SET mb_hp = '" . sql_escape_string(hyphen_hp_number($rb_sms_cert_hp)) . "',
                mb_certify = 'hp'
            WHERE mb_id = '" . sql_escape_string($mb_id) . "'");
    }
}

if ($w == "u") {
    $rb_session_hp = get_session('ss_reg_mb_hp');
    $rb_old_hp = $rb_session_hp !== '' ? preg_replace('/[^0-9]/', '', (string)$rb_session_hp) : (isset($member['mb_hp']) ? preg_replace('/[^0-9]/', '', (string)$member['mb_hp']) : '');
    $rb_new_hp = isset($mb_hp) ? preg_replace('/[^0-9]/', '', (string)$mb_hp) : '';

    if ($rb_old_hp !== $rb_new_hp && get_session('ss_rb_sms_cert_verified') !== '1') {
        sql_query("UPDATE {$g5['member_table']}
            SET mb_certify = ''
            WHERE mb_id = '" . sql_escape_string($mb_id) . "'");
    }
}

//----------------------------------------------------------
// SMS 문자전송 시작
//----------------------------------------------------------


$sms_contents = isset($default['de_sms_cont1']) ? $default['de_sms_cont1'] : '';
$sms_contents = str_replace("{이름}", $mb_name, $sms_contents);
$sms_contents = str_replace("{회원아이디}", $mb_id, $sms_contents);
$sms_contents = str_replace("{회사명}", isset($default['de_admin_company_name']) ? $default['de_admin_company_name'] : '', $sms_contents);

// 핸드폰번호에서 숫자만 취한다
$receive_number = preg_replace("/[^0-9]/", "", $mb_hp);  // 수신자번호 (회원님의 핸드폰번호)
$send_number = preg_replace("/[^0-9]/", "", isset($default['de_admin_company_tel']) ? $default['de_admin_company_tel'] : ''); // 발신자번호

$rb_de_sms_use1 = !empty($default['de_sms_use1']);
$rb_de_admin_company_name = isset($default['de_admin_company_name']) ? $default['de_admin_company_name'] : '';

if ($w == "" && $rb_de_sms_use1 && $receive_number)
{
	if ($config['cf_sms_use'] == 'icode')
	{
		if($config['cf_sms_type'] == 'LMS') {
            include_once(G5_LIB_PATH.'/icode.lms.lib.php');

            $port_setting = get_icode_port_type($config['cf_icode_id'], $config['cf_icode_pw']);

            // SMS 모듈 클래스 생성
            if($port_setting !== false) {
                $SMS = new LMS;
                $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $port_setting);

                $strDest     = array();
                $strDest[]   = $receive_number;
                $strCallBack = $send_number;
                $strCaller   = iconv_euckr(trim($rb_de_admin_company_name));
                $strSubject  = '';
                $strURL      = '';
                $strData     = iconv_euckr($sms_contents);
                $strDate     = '';
                $nCount      = count($strDest);

                $res = $SMS->Add($strDest, $strCallBack, $strCaller, $strSubject, $strURL, $strData, $strDate, $nCount);

                $SMS->Send();
                $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
            }
        } else {
            include_once(G5_LIB_PATH.'/icode.sms.lib.php');

            $SMS = new SMS; // SMS 연결
            $SMS->SMS_con($config['cf_icode_server_ip'], $config['cf_icode_id'], $config['cf_icode_pw'], $config['cf_icode_server_port']);
            $SMS->Add($receive_number, $send_number, $config['cf_icode_id'], iconv_euckr(stripslashes($sms_contents)), "");
            $SMS->Send();
            $SMS->Init(); // 보관하고 있던 결과값을 지웁니다.
        }
	}
}
//----------------------------------------------------------
// SMS 문자전송 끝
//----------------------------------------------------------;
