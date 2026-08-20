<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (!defined('G5_IS_ADMIN') || !G5_IS_ADMIN || $is_admin !== 'super') {
    return;
}

include_once(G5_ADMIN_PATH.'/rb/rb_license.lib.php');

function rb_license_admin_notice_tail()
{
    global $is_admin;
    if ($is_admin !== 'super') {
        return;
    }

    $current_script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    if (preg_match('~/adm/rb/rb_form\.php$~', $current_script)) {
        return;
    }

    $client = rb_license_client_get();
    $message = '';
    if (empty($client['registered_at'])) {
        $builder_table = sql_fetch("SELECT COUNT(*) AS cnt
                                      FROM information_schema.TABLES
                                     WHERE TABLE_SCHEMA='".sql_real_escape_string(G5_MYSQL_DB)."'
                                       AND TABLE_NAME='rb_builder'", false);
        if (!empty($builder_table['cnt'])) {
            $message = "빌더 토큰 등록이 필요합니다.\n빌더설정 메뉴에서 토큰을 등록해주세요.";
        } else {
            $message = "빌더 설치가 필요합니다.\n빌더설정 메뉴에서 빌더를 설치해주세요.";
        }
    } else {
        $checked_once = (int) get_session('ss_rb_license_remote_checked_once');
        if (!$checked_once) {
            set_session('ss_rb_license_remote_checked_once', 1);
            $check = rb_license_check_remote();
            $client = rb_license_client_get();
            if (empty($client['registered_at'])) {
                $message = "빌더 토큰 등록이 필요합니다.\n빌더설정 메뉴에서 토큰을 등록해주세요.";
            } elseif (!empty($check['success']) && isset($check['data'])) {
                if (isset($check['data']['state']) && $check['data']['state'] === 'clone_pending') {
                    $message = isset($check['data']['notice']) ? $check['data']['notice'] : "복제된 설치환경입니다.\n빌더설정에서 새 인증 토큰을 등록해 주세요.";
                }
            }
        }
        if ($message === '' && isset($client['registration_status'])
            && $client['registration_status'] === 'disabled') {
            $message = !empty($client['status_notice'])
                ? $client['status_notice']
                : '사용이 중지된 설치입니다.';
        }
        if ($message === '' && isset($client['environment_type'], $client['license_state'])
            && $client['environment_type'] === 'production' && $client['license_state'] !== 'active') {
            $message = !empty($client['status_notice'])
                ? $client['status_notice']
                : '운영 도메인 라이선스가 확인되지 않았습니다.';
        }
    }

    if ($message === '') {
        return;
    }
    ?>
    <style>
    .rb-license-notice-layer{position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,.48);display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
    .rb-license-notice-text{width:min(520px,100%);padding:28px;background:#fff;border:1px solid #ddd;color:#222;font-size:15px;line-height:1.7;text-align:center;white-space:pre-line;box-shadow:0 18px 50px rgba(0,0,0,.22);box-sizing:border-box}
    </style>
    <div class="rb-license-notice-layer" id="rb-license-notice-layer" role="dialog" aria-modal="true" aria-label="빌더 설치 안내">
        <div class="rb-license-notice-text"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <script>
    (function(){
        var layer=document.getElementById('rb-license-notice-layer');
        if(!layer)return;
        layer.addEventListener('click',function(){layer.parentNode.removeChild(layer);});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'&&layer.parentNode)layer.parentNode.removeChild(layer);});
    })();
    </script>
    <?php
}

add_event('tail_sub', 'rb_license_admin_notice_tail', G5_HOOK_DEFAULT_PRIORITY);
