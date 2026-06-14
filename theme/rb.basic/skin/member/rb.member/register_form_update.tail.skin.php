<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

//추가정보 저장
$rb_post_mb_partner = isset($_POST['mb_partner']) ? (int)$_POST['mb_partner'] : 0;
$rb_post_mb_bank = isset($_POST['mb_bank']) ? sql_escape_string($_POST['mb_bank']) : '';
$rb_post_re = isset($_POST['re']) ? $_POST['re'] : '';

if($w == "") {
    if(isset($pa['pa_is']) && $pa['pa_is'] == 1 && isset($pa['pa_use']) && $pa['pa_use'] == 1) {

        if($rb_post_mb_partner == 2 || $rb_post_mb_partner == 1) {

            if(isset($pa['pa_add_use']) && $pa['pa_add_use'] == 1) {

                if(isset($pa['pa_level']) && $pa['pa_level']) {
                    $re_level = $pa['pa_level'];
                } else {
                    $re_level = $config['cf_register_level'];
                }

                memo_auto_send('입점 신청이 승인 되었습니다.', '', $mb_id, "system-msg");
                memo_auto_send('입점사 가입 승인건이 있습니다.', '', $config['cf_admin'], "system-msg");

            } else {
                $re_level = $config['cf_register_level'];
                memo_auto_send('입점 신청이 접수 되었습니다.', '', $mb_id, "system-msg");
                memo_auto_send('입점사 가입 신청건이 있습니다.', '', $config['cf_admin'], "system-msg");
            }


            $sqls = "UPDATE {$g5['member_table']}
                        SET mb_partner = '{$rb_post_mb_partner}',
                            mb_partner_add_time = '" . G5_TIME_YMDHIS . "',
                            mb_bank = '{$rb_post_mb_bank}',
                            mb_level = '{$re_level}'
                            where mb_id = '{$mb_id}' ";
            sql_query($sqls);
        }
    }
} else if ($w == "u") {

    if(isset($pa['pa_is']) && $pa['pa_is'] == 1 && isset($pa['pa_use']) && $pa['pa_use'] == 1) {

        if($rb_post_mb_partner == 2 || $rb_post_mb_partner == 1) {

            $sqls = "UPDATE {$g5['member_table']}
                        SET mb_bank = '{$rb_post_mb_bank}'
                            where mb_id = '{$mb_id}' ";
            sql_query($sqls);

            if($rb_post_re == "re") {

                if(isset($pa['pa_add_use']) && $pa['pa_add_use'] == 1) {

                    if(isset($pa['pa_level']) && $pa['pa_level']) {
                        $re_level = $pa['pa_level'];
                    } else {
                        $re_level = $config['cf_register_level'];
                    }

                    memo_auto_send('입점 전환 신청이 승인 되었습니다.', '', $mb_id, "system-msg");
                    memo_auto_send('입점사 전환가입 승인건이 있습니다.', '', $config['cf_admin'], "system-msg");

                } else {

                    $re_level = $config['cf_register_level'];

                    memo_auto_send('입점 전환 신청이 접수 되었습니다.', '', $mb_id, "system-msg");
                    memo_auto_send('입점사 전환가입 신청건이 있습니다.', '', $config['cf_admin'], "system-msg");

                }

                $sqls = "UPDATE {$g5['member_table']}
                        SET mb_partner = '{$rb_post_mb_partner}',
                            mb_partner_add_time = '" . G5_TIME_YMDHIS . "',
                            mb_bank = '{$rb_post_mb_bank}',
                            mb_level = '{$re_level}'
                            where mb_id = '{$mb_id}' ";
                sql_query($sqls);

                goto_url(G5_HTTP_BBS_URL.'/register_result.php?partner='.$rb_post_mb_partner);
            }

        }

    }

}

if ($w == "" && function_exists('rb_sms_cert_is_enabled') && rb_sms_cert_is_enabled() && get_session('ss_rb_sms_cert_verified') === '1') {
    $rb_sms_cert_hp = function_exists('rb_sms_cert_normalize_hp') ? rb_sms_cert_normalize_hp(get_session('ss_rb_sms_cert_hp')) : preg_replace('/[^0-9]/', '', (string)get_session('ss_rb_sms_cert_hp'));
    if ($rb_sms_cert_hp !== '') {
        sql_query("UPDATE {$g5['member_table']}
            SET mb_hp = '" . sql_escape_string(hyphen_hp_number($rb_sms_cert_hp)) . "',
                mb_certify = 'hp'
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
