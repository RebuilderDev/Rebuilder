<?php
if (!defined('_GNUBOARD_')) exit;

    global $g5;
    function rb_human_bytes($bytes, $decimals = 2)
    {
        if (!is_numeric($bytes) || $bytes < 0) return '';
        $units = array('B','KB','MB','GB','TB','PB');
        $i = 0;
        while ($bytes >= 1024 && $i < (count($units) - 1)) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $decimals) . ' ' . $units[$i];
    }

    function rb_ini_to_bytes($val)
    {
        $val = trim((string)$val);
        if ($val === '' || $val === '-1') return null;

        $num  = (float)preg_replace('/[^0-9.]/', '', $val);
        $unit = strtoupper(preg_replace('/[^A-Z]/i', '', $val));

        if ($unit === 'K') return (int)($num * 1024);
        if ($unit === 'M') return (int)($num * 1024 * 1024);
        if ($unit === 'G') return (int)($num * 1024 * 1024 * 1024);
        if ($unit === 'T') return (int)($num * 1024 * 1024 * 1024 * 1024);

        return (int)$num;
    }

    function rb_dir_size($dir, $max_files = 300000)
    {
        if (!is_dir($dir) || !is_readable($dir)) return null;

        $size = 0;
        $cnt  = 0;

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($it as $file) {
                if ($file->isFile()) {
                    $size += (int)$file->getSize();
                    $cnt++;
                    if ($max_files > 0 && $cnt >= $max_files) break; // 안전장치
                }
            }
        } catch (Exception $e) {
            return null;
        }

        return $size;
    }

    // data 폴더 사용용량
    $data_dir  = defined('G5_DATA_PATH') ? G5_DATA_PATH : (rtrim(G5_PATH, '/\\') . '/data');
    $data_size = rb_dir_size($data_dir);

    // php 업로드/입력/메모리 제한
    $upload_bytes = rb_ini_to_bytes(ini_get('upload_max_filesize'));
    $post_bytes   = rb_ini_to_bytes(ini_get('post_max_size'));
    $mem_bytes    = rb_ini_to_bytes(ini_get('memory_limit'));

    $real_upload_max = null;
    if ($upload_bytes !== null && $post_bytes !== null) $real_upload_max = min($upload_bytes, $post_bytes);
    else if ($upload_bytes !== null) $real_upload_max = $upload_bytes;
    else if ($post_bytes !== null) $real_upload_max = $post_bytes;

    // DB 정보
    $db_ver = '';
    $row = sql_fetch(" SELECT VERSION() AS v ");
    if (isset($row['v']) && $row['v']) $db_ver = (string)$row['v'];

    function rb_db_var($name)
    {
        $name = preg_replace('/[^a-z0-9_]/i', '', (string)$name);
        if ($name === '') return '';
        $r = sql_fetch(" SHOW VARIABLES LIKE '{$name}' ");
        return isset($r['Value']) ? (string)$r['Value'] : '';
    }

    $max_packet = rb_db_var('max_allowed_packet');
    $sql_mode   = rb_db_var('sql_mode');
    $cs_server  = rb_db_var('character_set_server');
    $co_server  = rb_db_var('collation_server');

    function rb_cnt($sql)
    {
        $row = sql_fetch($sql);
        if (!$row) return 0;
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }

    // 탈퇴/차단 날짜가 "비어있음"으로 취급할 값들(마이그레이션 대비)
    function rb_empty_date_in()
    {
        return "('', '0', '00000000', '0000-00-00')";
    }

    $empty_in = rb_empty_date_in();

    // 1) 총회원수(탈퇴/접근차단 제외)
    $member_cnt = rb_cnt("
        SELECT COUNT(*) AS cnt
          FROM {$g5['member_table']}
         WHERE IFNULL(mb_leave_date,'') IN {$empty_in}
           AND IFNULL(mb_intercept_date,'') IN {$empty_in}
    ");

    // 2) 오늘 로그인한 회원수(탈퇴/차단 제외)
    $today_start    = date('Y-m-d 00:00:00');
    $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));

    $today_login_cnt = rb_cnt("
        SELECT COUNT(*) AS cnt
          FROM {$g5['member_table']}
         WHERE mb_today_login >= '{$today_start}'
           AND mb_today_login < '{$tomorrow_start}'
           AND IFNULL(mb_leave_date,'') IN {$empty_in}
           AND IFNULL(mb_intercept_date,'') IN {$empty_in}
    ");

    // 3) 총게시물수, 총댓글수 (board 캐시 합산)
    $w_total = 0;
    $c_total = 0;

    $row = sql_fetch("
        SELECT
            IFNULL(SUM(bo_count_write), 0)   AS w_cnt,
            IFNULL(SUM(bo_count_comment), 0) AS c_cnt
          FROM {$g5['board_table']}
    ");

    if ($row) {
        $w_total = (int)$row['w_cnt'];
        $c_total = (int)$row['c_cnt'];
    }

    // 4) 총상품수 (영카트)
    $item_cnt = 0;
    if (isset($g5['g5_shop_item_table']) && $g5['g5_shop_item_table']) {
        $item_cnt = rb_cnt("SELECT COUNT(*) AS cnt FROM {$g5['g5_shop_item_table']}");
    }




// 라이선스 키 조회
if (!function_exists('rb_get_license_key')) {
    function rb_get_license_key() {
        $row = sql_fetch("SELECT key_no FROM rb_key LIMIT 1");
        return isset($row['key_no']) ? trim($row['key_no']) : '';
    }
}

if (!function_exists('rb_license_key_html')) {
    function rb_license_key_html($opts = array()) {
        $defaults = array(
            'copy_button'   => true,
            'register_url'  => 'https://rebuilder.co.kr',
            'generate_url'  => G5_ADMIN_URL.'/rb/rb_db_update.php',
            'generate_text' => 'DB 업데이트',
            'wrap_class'    => 'rb-license-box',
            'label_text'    => '라이선스',
        );
        foreach ($defaults as $k => $v) if (!isset($opts[$k])) $opts[$k] = $v;

        $key = rb_get_license_key();

        ob_start(); ?>
<div class="<?php echo $opts['wrap_class']; ?>">
    <?php if ($key !== '') { ?>
    <div class="rb-lic-row">
        <span class="rb-lic-label"><?php echo $opts['label_text']; ?></span>
        <?php if (!empty($opts['copy_button'])) { ?>
        <button type="button" class="rb-lic-btn" id="rb-lic-copy">복사</button>
        <?php } ?>
        <a href="<?php echo G5_ADMIN_URL; ?>/rb/rb_form.php" class="rb-lic-btn rb-lic-out">보기</a>
    </div>
    <input type="hidden" id="rb-lic-hidden" value="<?php echo get_text($key); ?>">
    <?php } ?>

</div>

<script>
    (function() {
        var btn = document.getElementById('rb-lic-copy');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var input = document.getElementById('rb-lic-hidden');
            if (!input) return;
            try {
                input.type = 'text';
                input.select();
                document.execCommand('copy');
                input.type = 'hidden';
                alert('라이선스키가 클립보드에 복사되었습니다.');
            } catch (e) {}
        });
    })();
</script>

<?php
        return ob_get_clean();
    }
}

?>


<div class="rb-welcome">
    <div class="rb-welcome-card">
        <div class="rb-welcome-head">
            <div class="rb-avatar" aria-hidden="true">
                <?php echo get_member_profile_img($member['mb_id']); ?>
            </div>
            <div class="rb-head-text">
                <div class="rb-name"><?php echo $member['mb_nick'] ?> 님</div>
                <button type="button" class="rb-btn" onclick="location.href='<?php echo G5_ADMIN_URL ?>/member_form.php?w=u&amp;mb_id=<?php echo $member['mb_id'] ?>';">정보수정</button>
            </div>
        </div>

        <div class="rb-info-grid">
            <div class="rb-kv"><span class="k">그누보드(영카트)</span><span class="v"><?php echo G5_GNUBOARD_VER ?></span></div>
            <div class="rb-kv"><span class="k">빌더</span><span class="v"><?php echo RB_VER ?></span></div>

            <?php echo rb_license_key_html(); ?>

            <div class="rb-kv-top">
                <div class="rb-kv"><span class="k">유효회원</span><span class="v"><?php echo number_format($member_cnt) ?> 명</span></div>
                <div class="rb-kv"><span class="k">총 게시물</span><span class="v"><?php echo number_format($w_total) ?> 개</span></div>
                <div class="rb-kv"><span class="k">총 댓글</span><span class="v"><?php echo number_format($c_total) ?> 개</span></div>
                <div class="rb-kv"><span class="k">오늘 로그인</span><span class="v"><?php echo number_format($today_login_cnt) ?> 명</span></div>
            </div>

            <div class="rb-kv-top">
                <?php if ($data_size !== null) { ?>
                <div class="rb-kv"><span class="k">Data 폴더 사용량</span><span class="v"><?php echo rb_human_bytes($data_size) ?></span></div>
                <?php } ?>
                <?php if ($upload_bytes !== null) { ?>
                <div class="rb-kv"><span class="k">Upload Max</span><span class="v"><?php echo rb_human_bytes($upload_bytes) ?></span></div>
                <?php } ?>
                <?php if ($post_bytes !== null) { ?>
                <div class="rb-kv"><span class="k">Post Max</span><span class="v"><?php echo rb_human_bytes($post_bytes) ?></span></div>
                <?php } ?>

                <div class="rb-kv"><span class="k">Max File Uploads</span><span class="v"><?php echo ini_get('max_file_uploads') ?></span></div>
                <div class="rb-kv"><span class="k">PHP</span><span class="v"><?php echo PHP_VERSION ?></span></div>
                <?php if ($db_ver !== '') { ?>
                <div class="rb-kv"><span class="k">DB</span><span class="v"><?php echo $db_ver ?></span></div>
                <?php } ?>
                <div class="rb-kv"><span class="k">Curl</span><span class="v"><?php echo (function_exists('curl_version') ? 'ON' : 'OFF') ?></span></div>
            </div>

            <?php if($is_admin == 'super') { ?>
            <a href="<?php echo G5_ADMIN_URL; ?>/rb/rb_dbtool.php" class="rb_dbtool_btn">DB 관리툴</a>
            <?php } ?>
        </div>

        <div class="rb-memo">
            <div class="rb-memo-title"><?php echo $member['mb_nick'] ?>님의 한줄 메모</div>
            <div class="rb-memo-input">
                <input
                    type="search"
                    id="memo-input"
                    name="rb_memo_text"
                    placeholder="메모입력 후 엔터"
                    value=""
                    autocomplete="new-password"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    readonly
                />
            </div>
            <ul class="rb-memo-list" id="memo-list"></ul>
        </div>

        <script>
        jQuery(function($) {
            var $input = $('#memo-input');
            var $list = $('#memo-list');

            // readonly는 포커스 때만 해제 (자동완성 제안/자동입력 줄이기)
            $input.on('focus', function() {
                this.removeAttribute('readonly');
            });

            // 뒤로가기/복원(bfcache) 시 값 자동복원 방지
            $(window).on('pageshow', function() {
                $input.val('');
            });

            function renderList(list) {
                $list.empty();

                if (!list || list.length === 0) {
                    $list.append('<div class="rb-memo-empty">등록된 메모가 없습니다.</div>');
                    return;
                }

                $.each(list, function(i, line) {
                    var parts = line.split('|');
                    var date = parts[0] || '';
                    var txt = parts[1] || '';
                    var li = $('<li>');
                    li.append('<div class="date">' + date + '</div>');
                    li.append('<div class="txt">' + txt + '</div>');
                    li.append('<button type="button" class="rb-x" data-idx="' + i + '">×</button>');
                    $list.append(li);
                });
            }

            function loadMemo() {
                $.post('<?php echo G5_ADMIN_URL ?>/rb/rb_memo.php', { mode: 'list' }, function(res) {
                    if (res.result == 'ok') renderList(res.list);
                }, 'json');
            }

            // 엔터 입력 → 메모 추가 (keypress 대신 keydown 권장)
            $input.on('keydown', function(e) {
                if (e.which == 13) {
                    var v = $input.val().trim();
                    if (v != '') {
                        $.post('<?php echo G5_ADMIN_URL ?>/rb/rb_memo.php', {
                            mode: 'add',
                            content: v
                        }, function(res) {
                            if (res.result == 'ok') {
                                renderList(res.list);
                                $input.val('');
                            }
                        }, 'json');
                    }
                    e.preventDefault();
                    return false;
                }
            });

            $list.on('click', '.rb-x', function() {
                var idx = $(this).data('idx');
                $.post('<?php echo G5_ADMIN_URL ?>/rb/rb_memo.php', {
                    mode: 'delete',
                    index: idx
                }, function(res) {
                    if (res.result == 'ok') renderList(res.list);
                }, 'json');
            });

            loadMemo();
        });
        </script>


    </div>
</div>
