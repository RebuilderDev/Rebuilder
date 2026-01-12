<?php
$sub_menu = '000300';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, "w");

// ===== RB AJAX (디자인 불러오기/프리셋) =====
if (isset($_GET['rb_ajax']) && $_GET['rb_ajax']) {
    $act = preg_replace('/[^a-z_]/', '', (string)$_GET['rb_ajax']);

    $design_dir = G5_DATA_PATH . "/rb_display_design";
    $preset_dir = G5_DATA_PATH . "/rb_banner_presets";

    @mkdir($preset_dir, G5_DIR_PERMISSION);
    @chmod($preset_dir, G5_DIR_PERMISSION);

    function rb_ajax_out($arr){
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit;
    }

    function rb_rrmdir($dir){
        if (!is_dir($dir)) return;
        $items = @scandir($dir);
        if (!is_array($items)) return;
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') continue;
            $p = $dir . '/' . $it;
            if (is_dir($p)) rb_rrmdir($p);
            else @unlink($p);
        }
        @rmdir($dir);
    }

    // // JSON 문자열이 \\" 형태로 들어오는 경우까지 최대한 배열로 변환
    function rb_json_maybe_array($raw){
        if (!is_string($raw)) return $raw;
        $s = trim($raw);
        if ($s === '') return $raw;

        $tmp = json_decode($s, true);
        if (is_array($tmp)) return $tmp;

        // // 슬래시/이스케이프가 섞여 들어오는 경우 대비
        $s2 = function_exists('stripcslashes') ? stripcslashes($s) : $s;
        if ($s2 !== $s) {
            $tmp2 = json_decode($s2, true);
            if (is_array($tmp2)) return $tmp2;
        }

        // // "{...}" 처럼 JSON 문자열로 한 번 감싸진 형태면 한 번 더 풀기
        $tmp3 = json_decode($s, true);
        if (is_string($tmp3)) {
            $tmp4 = json_decode($tmp3, true);
            if (is_array($tmp4)) return $tmp4;
        }
        $tmp5 = json_decode($s2, true);
        if (is_string($tmp5)) {
            $tmp6 = json_decode($tmp5, true);
            if (is_array($tmp6)) return $tmp6;
        }

        // // 앞뒤 따옴표가 붙어 있으면 제거 후 재시도
        if (strlen($s) >= 2) {
            $f = substr($s, 0, 1);
            $l = substr($s, -1);
            if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                $s3 = substr($s, 1, -1);
                $tmp7 = json_decode($s3, true);
                if (is_array($tmp7)) return $tmp7;
                $s4 = function_exists('stripcslashes') ? stripcslashes($s3) : $s3;
                $tmp8 = json_decode($s4, true);
                if (is_array($tmp8)) return $tmp8;
            }
        }

        return $raw;
    }


    // // 기존 배너(디자인형) 설정값 가져오기
    if ($act === 'design_get') {
        $id = isset($_GET['bn_id']) ? (int)$_GET['bn_id'] : 0;
        if ($id <= 0) rb_ajax_out(['ok'=>0, 'msg'=>'invalid id']);

        $row = sql_fetch("SELECT bn_id, bn_alt, bn_type, bn_design_json, bn_stage_json FROM rb_banner WHERE bn_id='".(int)$id."' LIMIT 1");
        if (!$row || !isset($row['bn_id'])) rb_ajax_out(['ok'=>0, 'msg'=>'not found']);

        $bg_url = '';
        $bg_has = 0;
        $p = $design_dir . '/' . (int)$id;
        if (is_file($p)) {
            $bg_has = 1;
            $bg_url = G5_URL . '/rb/rb.mod/display/displaydesignimg.php?did='.(int)$id;
        }

        rb_ajax_out([
            'ok' => 1,
            'bn_id' => (int)$row['bn_id'],
            'title' => (string)$row['bn_alt'],
            'bn_type' => (string)$row['bn_type'],
            'design_json' => (string)$row['bn_design_json'],
            'stage_json' => (string)$row['bn_stage_json'],
            'bg_has' => $bg_has,
            'bg_url' => $bg_url
        ]);
    }

    // // 프리셋 목록
    if ($act === 'preset_list') {
        $items = [];

        if (is_dir($preset_dir)) {
            $dirs = @scandir($preset_dir);
            if (is_array($dirs)) {
                foreach ($dirs as $d) {
                    if ($d === '.' || $d === '..') continue;
                    if (!preg_match('/^[0-9A-Za-z_\-]+$/', $d)) continue;

                    $meta_path = $preset_dir . '/' . $d . '/meta.json';
                    $preview_path = $preset_dir . '/' . $d . '/preview.png';

                    if (!is_file($meta_path) || !is_file($preview_path)) continue;

                    $meta_raw = @file_get_contents($meta_path);
                    if ($meta_raw === false) continue;

                    $meta = json_decode($meta_raw, true);
                    if (!is_array($meta)) continue;

                    $title = isset($meta['title']) ? (string)$meta['title'] : '';
                    $ts = isset($meta['created_ts']) ? (int)$meta['created_ts'] : 0;

                    $bg_file = isset($meta['bg_file']) ? (string)$meta['bg_file'] : '';
                    $bg_has = ($bg_file && is_file($preset_dir . '/' . $d . '/' . $bg_file)) ? 1 : 0;

                    $items[] = [
                        'id' => $d,
                        'title' => $title,
                        'created' => isset($meta['created']) ? (string)$meta['created'] : '',
                        'created_ts' => $ts,
                        'preview_url' => G5_DATA_URL . '/rb_banner_presets/' . $d . '/preview.png?v=' . (int)@filemtime($preview_path),
                        'bg_has' => $bg_has
                    ];
                }
            }
        }

        // // 최신순 정렬
        usort($items, function($a, $b){
            $ta = isset($a['created_ts']) ? (int)$a['created_ts'] : 0;
            $tb = isset($b['created_ts']) ? (int)$b['created_ts'] : 0;
            if ($ta === $tb) return 0;
            return ($ta > $tb) ? -1 : 1;
        });

        rb_ajax_out(['ok'=>1, 'items'=>$items]);
    }

    // // 프리셋 단건 가져오기
    if ($act === 'preset_get') {
        $pid = isset($_GET['pid']) ? preg_replace('/[^0-9A-Za-z_\-]/', '', (string)$_GET['pid']) : '';
        if ($pid === '') rb_ajax_out(['ok'=>0, 'msg'=>'invalid pid']);

        $meta_path = $preset_dir . '/' . $pid . '/meta.json';
        if (!is_file($meta_path)) rb_ajax_out(['ok'=>0, 'msg'=>'not found']);

        $meta_raw = @file_get_contents($meta_path);
        $meta = json_decode((string)$meta_raw, true);
        if (!is_array($meta)) rb_ajax_out(['ok'=>0, 'msg'=>'broken meta']);

        $bg_file = isset($meta['bg_file']) ? (string)$meta['bg_file'] : '';
        $bg_has = ($bg_file && is_file($preset_dir . '/' . $pid . '/' . $bg_file)) ? 1 : 0;
        $bg_url = $bg_has ? (G5_DATA_URL . '/rb_banner_presets/' . $pid . '/' . $bg_file . '?v='.(int)@filemtime($preset_dir . '/' . $pid . '/' . $bg_file)) : '';

        rb_ajax_out([
            'ok' => 1,
            'id' => $pid,
            'title' => isset($meta['title']) ? (string)$meta['title'] : '',
            'design_json' => isset($meta['design_json']) ? $meta['design_json'] : '',
            'stage_json' => isset($meta['stage_json']) ? $meta['stage_json'] : '',
            'bg_has' => $bg_has,
            'bg_url' => $bg_url
        ]);
    }


    // // 프리셋 삭제
    if ($act === 'preset_delete') {
        $pid = isset($_POST['pid']) ? preg_replace('/[^0-9A-Za-z_\-]/', '', (string)$_POST['pid']) : '';
        if ($pid === '') rb_ajax_out(['ok'=>0, 'msg'=>'invalid pid']);

        $pdir = $preset_dir . '/' . $pid;
        if (!is_dir($pdir)) rb_ajax_out(['ok'=>0, 'msg'=>'not found']);

        rb_rrmdir($pdir);
        rb_ajax_out(['ok'=>1]);
    }

    // // 프리셋 ZIP 다운로드
    if ($act === 'preset_download') {
        $pid = isset($_GET['pid']) ? preg_replace('/[^0-9A-Za-z_\-]/', '', (string)$_GET['pid']) : '';
        if ($pid === '') die('invalid');

        $pdir = $preset_dir . '/' . $pid;
        if (!is_dir($pdir)) die('not found');

        $meta_path = $pdir . '/meta.json';
        $preview_path = $pdir . '/preview.png';
        if (!is_file($meta_path) || !is_file($preview_path)) die('broken');

        $zip_tmp = tempnam(sys_get_temp_dir(), 'rbp_');
        if ($zip_tmp === false) die('tmp fail');

        $zip_path = $zip_tmp . '.zip';
        @unlink($zip_tmp);

        if (!class_exists('ZipArchive')) die('ZipArchive not available');

        $za = new ZipArchive();
        if ($za->open($zip_path, ZipArchive::CREATE) !== true) die('zip fail');

        $za->addFile($meta_path, 'meta.json');
        $za->addFile($preview_path, 'preview.png');

        $meta_raw = @file_get_contents($meta_path);
        $meta = json_decode((string)$meta_raw, true);
        if (is_array($meta) && !empty($meta['bg_file'])) {
            $bg_file = preg_replace('/[^0-9A-Za-z_\.\-]/', '', (string)$meta['bg_file']);
            if ($bg_file && is_file($pdir . '/' . $bg_file)) {
                $za->addFile($pdir . '/' . $bg_file, $bg_file);
            }
        }

        $za->close();

        if (!is_file($zip_path)) die('zip fail');

        if (!headers_sent()) {
            $zip_name = 'preset_' . $pid;
            $t = '';
            if (isset($meta['title'])) $t = trim((string)$meta['title']);
            if ($t !== '') {
                // 파일명에 들어가면 위험한 문자는 제거 (한글/숫자/영문/공백/대시/언더바만 허용)
                $t = preg_replace('/[^\p{L}\p{N}\s_\-]+/u', '', $t);
                $t = trim(preg_replace('/\s+/u', ' ', $t));
                if ($t !== '') $zip_name .= '_' . $t;
            }
            $zip_name_full = $zip_name . '.zip';
            $zip_name_ascii = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $zip_name_full);

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name_ascii . '"; filename*=UTF-8\'\'' . rawurlencode($zip_name_full));
            header('Content-Length: ' . (int)filesize($zip_path));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        @readfile($zip_path);
        @unlink($zip_path);
        exit;
    }

    // // 프리셋 ZIP 업로드(가져오기)
    if ($act === 'preset_import') {
        if (!isset($_FILES['preset_zip']) || !is_array($_FILES['preset_zip'])) rb_ajax_out(['ok'=>0, 'msg'=>'file required']);
        if (!isset($_FILES['preset_zip']['tmp_name']) || $_FILES['preset_zip']['tmp_name'] === '') rb_ajax_out(['ok'=>0, 'msg'=>'file required']);
        if (!is_uploaded_file($_FILES['preset_zip']['tmp_name'])) rb_ajax_out(['ok'=>0, 'msg'=>'upload failed']);

        $name = isset($_FILES['preset_zip']['name']) ? (string)$_FILES['preset_zip']['name'] : '';
        if (!preg_match('/\.zip$/i', $name)) rb_ajax_out(['ok'=>0, 'msg'=>'zip only']);

        $tmp = $_FILES['preset_zip']['tmp_name'];

        if (!class_exists('ZipArchive')) rb_ajax_out(['ok'=>0, 'msg'=>'ZipArchive not available']);

        $za = new ZipArchive();
        if ($za->open($tmp) !== true) rb_ajax_out(['ok'=>0, 'msg'=>'zip open failed']);

        $buf = [];
        for ($i=0; $i<$za->numFiles; $i++){
            $stat = $za->statIndex($i);
            if (!is_array($stat) || empty($stat['name'])) continue;
            $fn = (string)$stat['name'];
            $fn = str_replace('\\', '/', $fn);
            if (substr($fn, -1) === '/') continue;

            // // 루트 폴더가 있는 zip도 허용
            $base = basename($fn);
            if ($base === '' || $base === '.' || $base === '..') continue;

            if (!preg_match('/^(meta\.json|preview\.png|bg\.(png|jpg|jpeg))$/i', $base)) continue;

            $data = $za->getFromIndex($i);
            if ($data === false) continue;

            $buf[$base] = $data;
        }
        $za->close();

        if (empty($buf['meta.json']) || empty($buf['preview.png'])) rb_ajax_out(['ok'=>0, 'msg'=>'meta.json / preview.png 필요']);

        $pid = date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 6);
        $pdir = $preset_dir . '/' . $pid;

        @mkdir($pdir, G5_DIR_PERMISSION, true);
        @chmod($pdir, G5_DIR_PERMISSION);

        foreach ($buf as $k => $v) {
            $k2 = strtolower($k);
            if ($k2 === 'meta.json') {
                @file_put_contents($pdir . '/meta.json', $v);
            } else if ($k2 === 'preview.png') {
                @file_put_contents($pdir . '/preview.png', $v);
            } else if (preg_match('/^bg\.(png|jpg|jpeg)$/i', $k)) {
                $ext = strtolower(pathinfo($k, PATHINFO_EXTENSION));
                $save = 'bg.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                @file_put_contents($pdir . '/' . $save, $v);

                // // meta.json에 bg_file이 없으면 자동 설정
                $meta_raw = @file_get_contents($pdir . '/meta.json');
                $meta = json_decode((string)$meta_raw, true);
                if (!is_array($meta)) $meta = [];
                if (empty($meta['bg_file'])) {
                    $meta['bg_file'] = $save;
                    $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($meta_json === false) $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE);
        @file_put_contents($pdir . '/meta.json', $meta_json);
                }
            }
        }

        // // 메타 기본값 보정
        $meta_raw = @file_get_contents($pdir . '/meta.json');
        $meta = json_decode((string)$meta_raw, true);
        if (!is_array($meta)) $meta = [];
        if (empty($meta['created_ts'])) $meta['created_ts'] = time();
        if (empty($meta['created'])) $meta['created'] = date('Y-m-d H:i:s');
        if (empty($meta['title'])) $meta['title'] = $pid;

        // // 보기 좋게 저장되도록 design_json/stage_json 문자열이면 배열로 변환 시도
        if (isset($meta['design_json'])) $meta['design_json'] = rb_json_maybe_array($meta['design_json']);
        if (isset($meta['stage_json'])) $meta['stage_json'] = rb_json_maybe_array($meta['stage_json']);
        $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($meta_json === false) $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE);
        @file_put_contents($pdir . '/meta.json', $meta_json);

        rb_ajax_out(['ok'=>1, 'id'=>$pid]);
    }

// // 프리셋 저장
    if ($act === 'preset_save') {
        $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
        if ($title === '') rb_ajax_out(['ok'=>0, 'msg'=>'title required']);

        $design_json = isset($_POST['design_json']) ? (string)$_POST['design_json'] : '';
        $stage_json  = isset($_POST['stage_json']) ? (string)$_POST['stage_json'] : '';

        $preview_data = isset($_POST['preview_data']) ? (string)$_POST['preview_data'] : '';
        $bg_data      = isset($_POST['bg_data']) ? (string)$_POST['bg_data'] : '';

        if ($preview_data === '' || !preg_match('~^data:image/(png|jpe?g);base64,~i', $preview_data, $m1)) {
            rb_ajax_out(['ok'=>0, 'msg'=>'preview required']);
        }

        // // 너무 큰 요청 방지(대략 15MB)
        if (strlen($preview_data) > 15000000) rb_ajax_out(['ok'=>0, 'msg'=>'preview too large']);

        $pid = date('YmdHis', G5_SERVER_TIME) . '_' . substr(md5(uniqid('', true)), 0, 6);
        $dir = $preset_dir . '/' . $pid;

        @mkdir($dir, G5_DIR_PERMISSION);
        @chmod($dir, G5_DIR_PERMISSION);

        // // preview 저장
        $preview_bin = base64_decode(substr($preview_data, strpos($preview_data, ',')+1));
        if ($preview_bin === false || strlen($preview_bin) < 10) rb_ajax_out(['ok'=>0, 'msg'=>'preview decode fail']);

        $preview_path = $dir . '/preview.png';
        if (@file_put_contents($preview_path, $preview_bin) === false) rb_ajax_out(['ok'=>0, 'msg'=>'preview write fail']);
        @chmod($preview_path, G5_FILE_PERMISSION);

        // // bg 저장(옵션)
        $bg_file = '';
        if ($bg_data !== '' && preg_match('~^data:image/(png|jpe?g);base64,~i', $bg_data, $m2)) {
            if (strlen($bg_data) <= 20000000) { // // 대략 20MB
                $ext = strtolower($m2[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $bg_bin = base64_decode(substr($bg_data, strpos($bg_data, ',')+1));
                if ($bg_bin !== false && strlen($bg_bin) > 10) {
                    $bg_file = 'bg.' . $ext;
                    $bg_path = $dir . '/' . $bg_file;
                    if (@file_put_contents($bg_path, $bg_bin) !== false) {
                        @chmod($bg_path, G5_FILE_PERMISSION);
                    } else {
                        $bg_file = '';
                    }
                }
            }
        }

        // // meta.json은 보기 좋게(줄바꿈/정렬) 저장
        $design_val = rb_json_maybe_array($design_json);
        $stage_val  = rb_json_maybe_array($stage_json);
$meta = [
            'id' => $pid,
            'title' => $title,
            'created' => G5_TIME_YMDHIS,
            'created_ts' => (int)G5_SERVER_TIME,
            'bg_file' => $bg_file,
            'design_json' => $design_val,
            'stage_json' => $stage_val
        ];
        $meta_path = $dir . '/meta.json';

        $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($meta_json === false) $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE);

        @file_put_contents($meta_path, $meta_json);
        @chmod($meta_path, G5_FILE_PERMISSION);

        rb_ajax_out(['ok'=>1, 'id'=>$pid]);
    }

    rb_ajax_out(['ok'=>0, 'msg'=>'invalid act']);
}

$bn_id = isset($bn_id) ? preg_replace('/[^0-9]/', '', $bn_id) : '';

$bn_design_bg_src = '';
if (!empty($bn_id)) {
    $p = G5_DATA_PATH . '/rb_display_design/' . (int)$bn_id;
    if (is_file($p)) {
        $bn_design_bg_src = G5_URL . '/rb/rb.mod/display/displaydesignimg.php?did='.(int)$bn_id.'&v='.G5_SERVER_TIME;
    }
}

$html_title = '배너';
$g5['title'] = $html_title . ' 관리';

if (isset($w) && $w == "u") {
    $html_title .= ' 수정';
    $sql = "SELECT * FROM rb_banner WHERE bn_id = '$bn_id'";
    $bn = sql_fetch($sql);
} else {
    $html_title .= ' 입력';
    $bn = [
        'bn_url' => "http://",
        'bn_begin_time' => date("Y-m-d 00:00:00", time()),
        'bn_end_time' => date("Y-m-d 00:00:00", time() + (60 * 60 * 24 * 31)),
        'bn_id' => ''
    ];
}

// 접속기기 필드 추가
if (!sql_query("SELECT bn_device FROM rb_banner LIMIT 0, 1")) {
    sql_query("ALTER TABLE `rb_banner` ADD `bn_device` varchar(10) NOT NULL DEFAULT '' AFTER `bn_url`", true);
    sql_query("UPDATE rb_banner SET bn_device = 'pc'", true);
}

// // 디자인형 컬럼 추가(없으면 생성)
if(!sql_query("SELECT bn_type FROM rb_banner LIMIT 0,1")) {
    sql_query("ALTER TABLE rb_banner ADD bn_type varchar(10) NOT NULL DEFAULT 'image' AFTER bn_device");
}
if(!sql_query("SELECT bn_design_json FROM rb_banner LIMIT 0,1")) {
    sql_query("ALTER TABLE rb_banner ADD bn_design_json LONGTEXT NOT NULL AFTER bn_type");
}
if(!sql_query("SELECT bn_stage_json FROM rb_banner LIMIT 0,1")) {
    sql_query("ALTER TABLE rb_banner ADD bn_stage_json TEXT NOT NULL AFTER bn_design_json");
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<form name="fbanner" action="./banner_form_update.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo isset($w) ? $w : ''; ?>">
<input type="hidden" name="bn_id" value="<?php echo $bn_id; ?>">


<?php
// // JSON hidden value 안전 출력
function rb_json_attr($s){
    $s = (string)$s;
    if ($s === '') return '';
    // // 혹시 \" 형태로 저장된 경우 대비
    if (strpos($s, '\\"') !== false) $s = str_replace('\\"', '"', $s);
    if (strpos($s, "\\'") !== false) $s = str_replace("\\'", "'", $s);
    // // 슬래시 중복 방지
    if (function_exists('stripslashes')) $s = stripslashes($s);
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<input type="hidden" name="bn_type" id="bn_type" value="<?php echo (isset($bn['bn_type']) && $bn['bn_type'] === 'design') ? 'design' : 'image'; ?>">
<input type="hidden" name="bn_design_json" id="bn_design_json" value="<?php echo isset($bn['bn_design_json']) ? rb_json_attr($bn['bn_design_json']) : ''; ?>">
<input type="hidden" name="bn_stage_json" id="bn_stage_json" value="<?php echo isset($bn['bn_stage_json']) ? rb_json_attr($bn['bn_stage_json']) : ''; ?>">


<input type="hidden" name="rb_bg_copy_kind" id="rb_bg_copy_kind" value="">
<input type="hidden" name="rb_bg_copy_id" id="rb_bg_copy_id" value="">

<link rel='stylesheet' href='<?php echo G5_URL ?>/adm/rb/css/swiper.css' />
<link rel='stylesheet' href='<?php echo G5_URL ?>/adm/rb/css/banner_form.css' />
<script src="<?php echo G5_URL ?>/adm/rb/js/swiper.js"></script>


<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>

    <tr>
        <th scope="row">배너 타입</th>
        <td>
            <label style="margin-right:12px;">
                <input type="radio" name="bn_type_pick" value="image" checked> 이미지형
            </label>
            <label>
                <input type="radio" name="bn_type_pick" value="design"> 디자인형
            </label>
        </td>
    </tr>


    <tr id="rb_row_banner_image">
        <th scope="row">이미지</th>
        <td>
            <input type="file" name="bn_bimg">
            <?php
            $bimg_str = '';

            $cur_id = isset($bn['bn_id']) ? (int)$bn['bn_id'] : 0;

            // // rb_display 우선, 없으면 banners fallback
            $bimg = '';
            if ($cur_id > 0) {
                $p1 = G5_DATA_PATH . "/rb_display/" . $cur_id;
                $p2 = G5_DATA_PATH . "/banners/" . $cur_id;

                if (is_file($p1)) $bimg = $p1;
                else if (is_file($p2)) $bimg = $p2;
            }

            if ($cur_id > 0 && $bimg && is_file($bimg)) {
                $size = @getimagesize($bimg);
                $w0 = (is_array($size) && isset($size[0])) ? (int)$size[0] : 0;
                $width = ($w0 > 550) ? 550 : $w0;

                echo '<input type="checkbox" name="bn_bimg_del" value="1" id="bn_bimg_del"> <label for="bn_bimg_del">삭제</label>';

                // // 미리보기는 서빙 엔드포인트로 통일(폴더 변경/차단 대응)
                $bimg_str = '<img src="' . G5_URL . '/rb/rb.mod/display/displayimg.php?did=' . $cur_id . '&v=' . G5_SERVER_TIME . '" width="' . $width . '">';
            }

            if ($cur_id > 0) {
                echo '<div class="banner_or_img">';
                echo $bimg_str;
                echo '</div>';
            }
            ?>
        </td>
    </tr>



    <tr id="rb_row_stage" style="display:none;">
        <th scope="row">스테이지 설정</th>
        <td>
            <?php echo help("<strong>반응형 자동 비율</strong> 설정시 배너 내부 오브젝트의 크기가 박스크기에 비례하여 축소 되거나 확대 됩니다.<br>가로폭은 실제 출력되는 배너영역을 채우며, 설정된 비율로 보여집니다.<br>같은 배너그룹의 경우 가급적 같은사이즈로 맞추는것을 권합 합니다."); ?>
            <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
                <div>
                    <label>
                        <span class="mr-5"><strong>사이즈</strong></span>
                        <select id="rb_bd_ratio" class="sel_bf">
                            <option value="" disabled>ㅡ 히어로</option>
                            <option value="16 / 9" selected>16:9</option>
                            <option value="21 / 9">21:9</option>
                            <option value="" disabled>ㅡ 띠배너</option>
                            <option value="3 / 1">3:1</option>
                            <option value="4 / 1">4:1</option>
                            <option value="5 / 1">5:1</option>
                            <option value="" disabled>ㅡ 리스트</option>
                            <option value="1 / 1">1:1</option>
                            <option value="3 / 2">3:2</option>
                            <option value="4 / 2">4:2</option>
                            <option value="4 / 3">4:3</option>
                            <option value="" disabled>ㅡ 세로형</option>
                            <option value="9 / 16">9:16</option>
                            <option value="4 / 5">4:5</option>
                            <option value="2 / 3">2:3</option>
                        </select>
                    </label>
                </div>
                <div>
                  <label>
                    <span class="mr-5"><strong>세로정렬</strong></span>
                    <select id="rb_stage_valign" class="sel_bf" style="width:90px;">
                      <option value="top">상단</option>
                      <option value="center" selected>중앙</option>
                      <option value="bottom">하단</option>
                    </select>
                  </label>
                </div>
                <div>
                    <label>
                      <input type="checkbox" id="rb_stage_ratio" value="1">
                      반응형 자동 비율
                    </label>
                </div>
                <div>
                    <label>
                        <span class="mr-5">반응형 미리보기</span>
                        <input type="range" id="rb_bd_canvas_w_n" min="280" max="1024" step="20" value="550">
                        <span style="color:#666;font-size:12px;" id="rb_bd_canvas_w_n_num">0px</span>
                    </label>


                </div>
            </div>
            <div style="margin-top:10px;">
                    <label><strong>스테이지 내부 여백　</strong><span class="mr-5">상</span>
                        <input type="number" id="rb_bd_pad_t" class="inp_bf mr-10" min="0" max="300" value="20" style="width:50px;">
                    </label>
                    <label> <span class="mr-5">하</span>
                        <input type="number" id="rb_bd_pad_b" class="inp_bf mr-10" min="0" max="300" value="20" style="width:50px;">
                    </label>
                    <label> <span class="mr-5">좌</span>
                        <input type="number" id="rb_bd_pad_l" class="inp_bf mr-10" min="0" max="300" value="20" style="width:50px;">
                    </label>
                    <label> <span class="mr-5">우</span>
                        <input type="number" id="rb_bd_pad_r" class="inp_bf mr-10" min="0" max="300" value="20" style="width:50px;">
                    </label>
                    <span style="color:#666;font-size:12px;">px</span>
                </div>

        </td>
    </tr>

    <tr id="rb_row_banner_design" style="display:none;">
        <th scope="row">디자인 편집</th>
        <td class="rb_bd_lay_top_bg">

            <div class="edit_wraps">

            <div class="rb_bd_lay_top">
                <div style="display:flex;gap:5px 20px;align-items:center;flex-wrap:wrap;">

                    <div>
                        <label><input type="radio" name="rb_bd_bgmode" value="solid" checked> 단색</label>
                        <label style="margin-left:10px;"><input type="radio" name="rb_bd_bgmode" value="gradient"> 그라데이션</label>
                        <label style="margin-left:10px;"><input type="radio" name="rb_bd_bgmode" value="image"> 이미지</label>
                    </div>

                    <div id="rb_bd_solid_row">
                        <span class="rb_bd_lbl mr-5">컬러</span>
                        <input type="color" id="rb_bd_solid" value="#ffffff">
                    </div>

                    <div id="rb_bd_grad_row" style="display:none;">
                        <span class="rb_bd_lbl mr-5">컬러1</span><input type="color" id="rb_bd_g1" value="#111111">
                        <span class="rb_bd_lbl mr-5" style="margin-left:8px;">컬러2</span><input type="color" id="rb_bd_g2" value="#444444">
                        <span class="rb_bd_lbl mr-5" style="margin-left:8px;">방향</span>
                        <input type="range" id="rb_bd_angle" min="0" max="360" value="45" step="5">
                    </div>

                    <div>
                        <span class="rb_bd_lbl mr-5">백그라운드 밝기</span>
                        <input type="range" id="rb_bd_brightness" min="30" max="100" value="100" step="5">
                        <span class="rb_bd_val" id="rb_bd_brightness_val">100%</span>
                    </div>
                </div>

            </div>



            <div class="rb_bd_lay_center">
                <div class="rb_bd_editor">
                    <div class="rb_bd_canvas_wrap">
                        <div class="rb_bd_canvas_inner">

                            <div class="rb_bd_top">
                                <div class="rb_bd_actions">
                                    <input type="file" name="bn_bgimg" id="rb_bd_bgfile" accept="image/*" hidden>
                                    <label for="rb_bd_bgfile" class="btn btn_02" id="rb_bd_bg_pick">이미지 찾기</label>
                                    <button type="button" class="btn btn_02" id="rb_bd_bg_clear">이미지 제거</button>
                                </div>
                            </div>

                            <div class="rb_bd_canvas" id="rb_bd_canvas">
                                <div class="rb_bd_bg_wrap" id="rb_bd_bg_wrap">
                                    <div class="rb_bd_bg_color" id="rb_bd_bg_color"></div>
                                    <img class="rb_bd_bg_img" id="rb_bd_bg_img" data-saved-src="<?php echo $bn_design_bg_src; ?>" alt="">
                                </div>
                                <div class="rb_bd_stage" id="rb_bd_stage"></div>
                                <!--
                                <div class="rb_bd_drop" id="rb_bd_drop">이미지 드랍</div>
                                -->
                            </div>


                            <div class="rb_bd_loadbar_inner">
                                <div class="rb_bd_canvas_actions">

                                    <div class="rb_bd_loaditem">
                                        <span class="rb_bd_lbl">불러오기</span>
                                        <select id="rb_design_load" class="sel_bf" style="width:100px;">
                                            <option value="">선택</option>
                                            <?php
                                        $sql = "SELECT bn_id, bn_alt FROM rb_banner WHERE bn_type='design' ";
                                        if (!empty($bn_id)) $sql .= " AND bn_id <> '".(int)$bn_id."' ";
                                        $sql .= " ORDER BY bn_id DESC LIMIT 200";
                                        $rs = sql_query($sql);
                                        for ($i=0; $row=sql_fetch_array($rs); $i++){
                                            $t = isset($row['bn_alt']) ? (string)$row['bn_alt'] : '';
                                            if ($t === '') $t = 'ID ' . (int)$row['bn_id'];
                                            echo '<option value="'.(int)$row['bn_id'].'">'.htmlspecialchars($t, ENT_QUOTES, 'UTF-8').' ('.(int)$row['bn_id'].')</option>';
                                        }
                                        ?>
                                        </select>
                                    </div>

                                    <button type="button" class="btn btn_02" id="rb_stage_download_btn">PNG 다운로드</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>



            <div class="rb_bd_lay_right">

                <div class="rb_bd_panel">


                    <div class="rb_bd_group">
                        <div class="rb_bd_group_tit">텍스트</div>
                        <?php echo help("텍스트 블록을 클릭하신 후 텍스트 입력 및 스타일 적용을 하실 수 있어요."); ?>

                        <div class="rb_txt_toolbar" id="rb_txt_toolbar">
                            <div>
                                <button type="button" class="btn_bf btn_submit" id="rb_txt_add">추가</button>
                                <button type="button" class="btn_bf" id="rb_txt_del">선택삭제</button>
                            </div>
                            <!--
                            <span class="rb_txt_sep"></span>
                            -->
                            <div>
                                <select id="rb_txt_fontw" style="width:95px;" class="sel_bf">
                                    <option value="font-R" selected>font-R</option>
                                    <option value="font-B">font-B</option>
                                    <option value="font-H">font-H</option>
                                </select>
                            </div>

                            <div>
                                <input type="color" id="rb_txt_color" value="#000000">
                            </div>

                            <div>
                                <button type="button" class="toolbar_btns" data-align="left"><img src="./img/banner_form/align_left_line.svg"></button>
                                <button type="button" class="toolbar_btns" data-align="center"><img src="./img/banner_form/align_center_line.svg"></button>
                                <button type="button" class="toolbar_btns" data-align="right"><img src="./img/banner_form/align_right_line.svg"></button>
                            </div>

                            <div class="mt-10">
                                <span class="rb_bd_lbl mr-10">크기</span>
                                <input type="range" id="rb_txt_size_n" min="8" max="80" value="18" class="mr-5">
                                <span style="color:#666;font-size:12px;" id="rb_txt_size_n_num">0px</span>
                            </div>

                            <div>
                            <span class="rb_bd_lbl mr-10">상단여백</span>
                                <input type="range" id="rb_txt_top" min="0" max="200" value="40" class="mr-5">
                                <span style="color:#666;font-size:12px;" id="rb_txt_top_num">0px</span>
                            </div>

                        </div>


                    </div>



                    <div class="rb_bd_group" id="rb_btn_group">
                        <div class="rb_bd_group_tit">버튼</div>

                        <div class="rb_bd_row">
                            <label style="display:flex;align-items:center;gap:6px;">
                                <input type="checkbox" id="rb_btn_use" value="1"> 사용
                            </label>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">정렬</span>
                            <select id="rb_btn_align" class="sel_bf" style="width:70px;">
                                <option value="left">좌측</option>
                                <option value="center" selected>중앙</option>
                                <option value="right">우측</option>
                            </select>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">텍스트</span>
                            <input type="text" id="rb_btn_text" class="inp_bf" value="자세히 보기" style="width:100px;">
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">링크</span>
                            <input type="text" id="rb_btn_link" class="inp_bf" value="" style="width:130px;">
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">새창여부</span>
                            <label>
                                <input type="checkbox" id="rb_btn_new_win" value="1">
                                새창
                            </label>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">여백</span>
                            <input type="number" id="rb_btn_px" class="inp_bf" min="0" max="60" value="10" style="width:60px;">
                            <input type="number" id="rb_btn_py" class="inp_bf" min="0" max="60" value="16" style="width:60px;">
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">모서리</span>
                            <input type="range" id="rb_btn_radius" min="0" max="200" step="1" value="0">
                            <span style="color:#666;font-size:12px;" id="rb_btn_radius_num">px</span>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">상단여백</span>
                            <input type="range" id="rb_btn_mt" min="0" max="300" step="1" value="0">
                            <span style="color:#666;font-size:12px;" id="rb_btn_mt_num">px</span>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">폰트크기</span>
                            <input type="range" id="rb_btn_font" min="8" max="80" step="1" value="14">
                            <span style="color:#666;font-size:12px;" id="rb_btn_font_num">px</span>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">굵기</span>
                            <select id="rb_btn_fontw" class="sel_bf" style="width:80px;">
                                <option value="font-R" selected>font-R</option>
                                <option value="font-B">font-B</option>
                                <option value="font-H">font-H</option>
                            </select>
                        </div>



                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">컬러</span>
                            <input type="color" id="rb_btn_bg" value="#ffffff">
                            <input type="color" id="rb_btn_color" value="#000000">
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">테두리</span>
                            <label style="display:flex;align-items:center;gap:6px;">
                                <input type="checkbox" id="rb_btn_border" class="inp_bf" value="1"> 사용
                            </label>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">테두리종류</span>
                            <select id="rb_btn_border_style" class="sel_bf" style="width:80px;">
                                <option value="solid" selected>solid</option>
                                <option value="dashed">dashed</option>
                                <option value="dotted">dotted</option>
                            </select>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">테두리두께</span>
                            <input type="range" id="rb_btn_border_w" min="0" max="10" step="1" value="14">
                            <span style="color:#666;font-size:12px;" id="rb_btn_border_w_num">px</span>
                        </div>

                        <div class="rb_bd_row">
                            <span class="rb_bd_lbl" style="width:80px;">테두리컬러</span>
                            <input type="color" id="rb_btn_border_color" value="#cccccc">
                        </div>
                    </div>


                </div>

            </div>

        </div>
        <div class="rb_bd_loadbar">


                            <div class="rb_bd_loaditem">
                                <input type="text" id="rb_preset_title" class="inp_bf" placeholder="프리셋 제목" style="width:180px;">
                                <button type="button" class="btn btn_02" id="rb_preset_save_btn">프리셋 저장</button>

                                <div class="rb_bd_loaditem rb_bd_loaditem2">
                                    <input type="file" id="rb_preset_upload_file" accept=".zip" style="display:none;">
                                    <button type="button" class="btn btn_03" id="rb_preset_upload_btn">프리셋 업로드(.zip)</button>
                                </div>
                            </div>



                        <div class="rb_preset_list_wrap">
                            <div class="rb_preset_list" id="rb_preset_list"></div>
                        </div>


                        <br>
                        <?php echo help("프리셋 저장시 /data/rb_banner_presets/ 폴더에 저장됩니다.<br>프리셋을 다운로드하여 공유할 수 있으며, 다운로드한 프리셋 zip 파일을 업로드하여 프리셋을 추가할 수 있습니다."); ?>

                    </div>
        </td>

    </tr>


<script>
(function () {
    function setRangeNum(rangeEl) {
        if (!rangeEl || !rangeEl.id) return;

        var numEl = document.getElementById(rangeEl.id + '_num');
        if (!numEl) return;

        var v = rangeEl.value;
        numEl.textContent = String(v) + 'px';
    }

    function initRangeNumSync() {
        var ranges = document.querySelectorAll('input[type="range"][id]');
        for (var i = 0; i < ranges.length; i++) {
            (function (el) {
                // 초기 표시
                setRangeNum(el);

                // 드래그/변경 시 표시
                el.addEventListener('input', function () {
                    setRangeNum(el);
                });
                el.addEventListener('change', function () {
                    setRangeNum(el);
                });
            })(ranges[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRangeNumSync);
    } else {
        initRangeNumSync();
    }
})();
</script>

<script>
(function () {
    function applyPaddingTop() {
        var topEl = document.querySelector('.rb_bd_lay_top');
        if (!topEl) return;

        var h = topEl.getBoundingClientRect().height;
        var pt = (Math.round(h) + 15) + 'px';

        var centerEl = document.querySelector('.rb_bd_lay_center');
        if (centerEl) centerEl.style.paddingTop = pt;

        var rightEl = document.querySelector('.rb_bd_lay_right');
        if (rightEl) rightEl.style.paddingTop = pt;
    }

    function init() {
        applyPaddingTop();

        window.addEventListener('resize', applyPaddingTop);

        if ('ResizeObserver' in window) {
            var ro = new ResizeObserver(function () {
                applyPaddingTop();
            });
            var topEl = document.querySelector('.rb_bd_lay_top');
            if (topEl) ro.observe(topEl);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<script>
(function(){
    var rowImage = document.getElementById('rb_row_banner_image');
    var rowStage = document.getElementById('rb_row_stage');
    var rowDesign = document.getElementById('rb_row_banner_design');

    var hidType = document.getElementById('bn_type');
    var hidJson = document.getElementById('bn_design_json');
    var hidStage = document.getElementById('bn_stage_json');

    var radios = document.querySelectorAll('input[name="bn_type_pick"]');

    function clamp(n,a,b){
        n = parseInt(n,10);
        if (isNaN(n)) n = a;
        if (n < a) n = a;
        if (n > b) n = b;
        return n;
    }
    function px(n){ return String(parseInt(n,10) || 0) + 'px'; }

    function spx(n){
        n = parseInt(n,10);
        if (isNaN(n)) n = 0;
        return 'calc(' + n + 'px * var(--rb-scale,1))';
    }

    function setType(t){
        t = (t === 'design') ? 'design' : 'image';
        if (hidType) hidType.value = t;

        if (rowImage) rowImage.style.display = (t === 'image') ? '' : 'none';
        if (rowStage) rowStage.style.display = (t === 'design') ? '' : 'none';
        if (rowDesign) rowDesign.style.display = (t === 'design') ? '' : 'none';

        if (t === 'design') {
            requestAnimationFrame(function(){
                applyStage();
                renderBg();
                renderStage();
                loadPresets();
            });
        }
    }

    setType((hidType && hidType.value) ? hidType.value : 'image');
    for (var i=0;i<radios.length;i++){
        if (hidType && radios[i].value === hidType.value) radios[i].checked = true;
        radios[i].addEventListener('change', function(){ setType(this.value); });
    }

    function getWrapMaxW(){
        var wrap = document.querySelector('.rb_bd_canvas_wrap');
        if (!wrap) return 0;

        var w = wrap.clientWidth;
        if (!w || w < 1) w = wrap.getBoundingClientRect().width;
        w = Math.floor(w);

        return (w && w > 0) ? w : 0;
    }

    // ====== 스테이지 ======
    var canvas = document.getElementById('rb_bd_canvas');
    if (!canvas) return;

    var ratioSel = document.getElementById('rb_bd_ratio');
    var wRange = document.getElementById('rb_bd_canvas_w');
    var wNum = document.getElementById('rb_bd_canvas_w_n');
    var scaleChk = document.getElementById('rb_stage_ratio');
    var valignSel = document.getElementById('rb_stage_valign');

    var padT = document.getElementById('rb_bd_pad_t');
    var padR = document.getElementById('rb_bd_pad_r');
    var padB = document.getElementById('rb_bd_pad_b');
    var padL = document.getElementById('rb_bd_pad_l');

    var stage = document.getElementById('rb_bd_stage');

    var stageState = { ratio:'16 / 9', width:550, base_w:550, scale:0, pad_t:20, pad_r:20, pad_b:20, pad_l:20, valign:'center' };


    try{
        if (hidStage && hidStage.value) {
            var p = JSON.parse(hidStage.value);
            if (p && typeof p === 'object') stageState = p;
        }
    }catch(e){}

    if (!stageState.base_w) stageState.base_w = stageState.width;
    stageState.scale = stageState.scale ? 1 : 0;
    if (scaleChk) scaleChk.checked = stageState.scale ? true : false;

    function applyStage(){
        var r = stageState.ratio || '16 / 9';

        var wrapMax = getWrapMaxW(); // wrap 가로폭
        var dynMax = 1024;

        if (wrapMax > 0) dynMax = Math.min(dynMax, wrapMax);

        // wrap이 280보다 작아지는 극단 상황도 대비
        var w;
        if (dynMax < 1) {
            w = clamp(stageState.width, 280, 1024);
        } else if (dynMax < 280) {
            w = dynMax;
        } else {
            w = clamp(stageState.width, 280, dynMax);
        }

        var bw = clamp(stageState.base_w ? stageState.base_w : w, 280, 1024);

        var pt = clamp(stageState.pad_t, 0, 300);
        var pr = clamp(stageState.pad_r, 0, 300);
        var pb = clamp(stageState.pad_b, 0, 300);
        var pl = clamp(stageState.pad_l, 0, 300);

        stageState.width = w;
        stageState.base_w = bw;
        stageState.pad_t = pt; stageState.pad_r = pr; stageState.pad_b = pb; stageState.pad_l = pl;
        stageState.scale = stageState.scale ? 1 : 0;

        var sc = stageState.scale ? (w / bw) : 1;
        if (sc < 0.2) sc = 0.2;
        if (sc > 3) sc = 3;

        canvas.style.setProperty('--rb-canvas-r', r);
        canvas.style.setProperty('--rb-canvas-w', w + 'px');

        if (stage) {
            stage.style.setProperty('--rb-scale', String(sc));
            stage.style.padding =
                'calc(' + pt + 'px * var(--rb-scale,1)) ' +
                'calc(' + pr + 'px * var(--rb-scale,1)) ' +
                'calc(' + pb + 'px * var(--rb-scale,1)) ' +
                'calc(' + pl + 'px * var(--rb-scale,1))';

            // // 세로정렬 적용
            stage.style.display = 'flex';
            stage.style.flexDirection = 'column';
            stage.style.alignItems = 'stretch';

            var va = stageState.valign ? String(stageState.valign) : 'center';
            if (va === 'top') stage.style.justifyContent = 'flex-start';
            else if (va === 'bottom') stage.style.justifyContent = 'flex-end';
            else stage.style.justifyContent = 'center';
        }


        if (ratioSel) ratioSel.value = r;
        if (wRange) wRange.value = String(w);
        if (wRange && dynMax >= 280) wRange.max = String(dynMax);
        if (wNum && dynMax >= 280) wNum.max = String(dynMax);

        if (wNum) wNum.value = String(w);

        if (padT) padT.value = String(pt);
        if (padR) padR.value = String(pr);
        if (padB) padB.value = String(pb);
        if (padL) padL.value = String(pl);

        if (scaleChk) scaleChk.checked = stageState.scale ? true : false;

        try{ if (hidStage) hidStage.value = JSON.stringify(stageState); }catch(e){}
    }

    function setW(v){ stageState.width = clamp(v, 280, 1024); applyStage(); }
    function setPad(){
        stageState.pad_t = clamp(padT ? padT.value : stageState.pad_t, 0, 300);
        stageState.pad_r = clamp(padR ? padR.value : stageState.pad_r, 0, 300);
        stageState.pad_b = clamp(padB ? padB.value : stageState.pad_b, 0, 300);
        stageState.pad_l = clamp(padL ? padL.value : stageState.pad_l, 0, 300);
        applyStage();
    }

    if (ratioSel){
        ratioSel.addEventListener('change', function(){ stageState.ratio = this.value; applyStage(); });
    }

    if (valignSel) {
        valignSel.value = stageState.valign ? stageState.valign : 'center';
        valignSel.addEventListener('change', function(){
            stageState.valign = this.value || 'center';
            applyStage();
        });
    }

    if (wRange) wRange.addEventListener('input', function(){ setW(this.value); });
    if (wNum) wNum.addEventListener('input', function(){ setW(this.value); });

    if (padT) padT.addEventListener('input', setPad);
    if (padR) padR.addEventListener('input', setPad);
    if (padB) padB.addEventListener('input', setPad);
    if (padL) padL.addEventListener('input', setPad);

    if (scaleChk){
        scaleChk.addEventListener('change', function(){
            stageState.scale = this.checked ? 1 : 0;
            applyStage();
            try{ renderTexts(); }catch(e){}
        });
    }

    applyStage();

    // ====== 배경 ======
    var bgWrap = document.getElementById('rb_bd_bg_wrap');
    var bgColor = document.getElementById('rb_bd_bg_color');
    var bgImg = document.getElementById('rb_bd_bg_img');
    var bgFile = document.getElementById('rb_bd_bgfile');

    var bgModeRadios = document.querySelectorAll('input[name="rb_bd_bgmode"]');
    var solidRow = document.getElementById('rb_bd_solid_row');
    var gradRow = document.getElementById('rb_bd_grad_row');

    var solidInp = document.getElementById('rb_bd_solid');
    var g1Inp = document.getElementById('rb_bd_g1');
    var g2Inp = document.getElementById('rb_bd_g2');
    var angleInp = document.getElementById('rb_bd_angle');

    var brightInp = document.getElementById('rb_bd_brightness');
    var brightVal = document.getElementById('rb_bd_brightness_val');

    var btnBgClear = document.getElementById('rb_bd_bg_clear');

    // ====== 텍스트 툴 ======
    var btnAdd = document.getElementById('rb_txt_add');
    var txtFontW = document.getElementById('rb_txt_fontw');
    var colorInp = document.getElementById('rb_txt_color');
    var sizeInp = document.getElementById('rb_txt_size');
    var sizeNum = document.getElementById('rb_txt_size_n');
    var topNum = document.getElementById('rb_txt_top');
    var btnDel = document.getElementById('rb_txt_del');
    var alignBtns = document.querySelectorAll('#rb_txt_toolbar [data-align]');

    // ====== 버튼 UI ======
    var btnUse = document.getElementById('rb_btn_use');
    var btnAlign = document.getElementById('rb_btn_align');
    var btnText = document.getElementById('rb_btn_text');
    var btnLink = document.getElementById('rb_btn_link');
    var btnNewWin = document.getElementById('rb_btn_new_win');
    var btnPX = document.getElementById('rb_btn_px');
    var btnPY = document.getElementById('rb_btn_py');
    var btnRadius = document.getElementById('rb_btn_radius');
    var btnFont = document.getElementById('rb_btn_font');
    var btnBg = document.getElementById('rb_btn_bg');
    var btnColor = document.getElementById('rb_btn_color');
    var btnBorder = document.getElementById('rb_btn_border');
    var btnBorderStyle = document.getElementById('rb_btn_border_style');
    var btnBorderW = document.getElementById('rb_btn_border_w');
    var btnBorderColor = document.getElementById('rb_btn_border_color');
    var btnMt = document.getElementById('rb_btn_mt');
    var btnFontW = document.getElementById('rb_btn_fontw');

var selDesignLoad = document.getElementById('rb_design_load');
    var presetTitle = document.getElementById('rb_preset_title');
    var presetSaveBtn = document.getElementById('rb_preset_save_btn');
    var presetList = document.getElementById('rb_preset_list');

    var presetUploadFile = document.getElementById('rb_preset_upload_file');
    var presetUploadBtn = document.getElementById('rb_preset_upload_btn');
    var stageDownloadBtn = document.getElementById('rb_stage_download_btn');



    var bgCopyKind = document.getElementById('rb_bg_copy_kind');
    var bgCopyId = document.getElementById('rb_bg_copy_id');

    function setBgCopy(kind, id){
        if (bgCopyKind) bgCopyKind.value = kind ? kind : '';
        if (bgCopyId) bgCopyId.value = id ? id : '';
    }

    var state = {
        v: 1,
        bg: { mode:'solid', solid:'#ffffff', g1:'#92c9f7', g2:'#cc9afe', angle:45, brightness:100, hasImage:0 },
        texts: [],
        btn: {
            use: 0,
            align: 'left',
            text: '자세히 보기',
            link: '',
            new_win: 0,
            px: 25,
            py: 15,
            radius: 10,
            font: 16,
            bg: '#f0f5f9',
            color: '#000000',
            border: 0,
            border_style: 'solid',
            border_w: 1,
            border_color: '#cccccc',
            mt: 0,
            fontw: 'font-B'
        }
    };

    try{
        if (hidJson && hidJson.value) {
            var parsed = JSON.parse(hidJson.value);
            if (parsed && typeof parsed === 'object') state = parsed;
        }
    }catch(e){}

    // 누락 보정
    if (!state.bg) state.bg = { mode:'solid', solid:'#ffffff', g1:'#92c9f7', g2:'#cc9afe', angle:45, brightness:100, hasImage:0 };
    if (!state.texts) state.texts = [];
    if (!state.btn) {
        state.btn = { use:0, align:'center', text:'자세히 보기', link:'', new_win:0, px:10, py:16, radius:12, bg:'#ffffff', color:'#000000', border:0, border_style:'solid', border_w:1, border_color:'#cccccc' };
    }

    if (typeof state.btn.mt === 'undefined') state.btn.mt = 0;
    if (typeof state.btn.new_win === 'undefined') state.btn.new_win = 0;
    if (!state.btn.fontw) state.btn.fontw = 'font-R';

    if (!state.order || !Array.isArray(state.order)) state.order = [];

    var selectedId = '';
    var bgPreviewUrl = '';

    // // 저장된 배경이미지 자동 로드(수정 시)
    var savedBgSrc = bgImg ? bgImg.getAttribute('data-saved-src') : '';
    if (savedBgSrc && state && state.bg && String(state.bg.hasImage) === '1') {
        state.bg.mode = 'image';
        bgImg.src = savedBgSrc;
        bgImg.style.setProperty('display', 'block', 'important');
    }

    function saveState(){
        try{ if (hidJson) hidJson.value = JSON.stringify(state); }catch(e){}
    }

    function uid(){ return 't' + Date.now() + '_' + Math.floor(Math.random()*100000); }

function rbEsc(s){
        s = (s === null || typeof s === 'undefined') ? '' : String(s);
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function rbFetchJson(url, opt){
        opt = opt || {};
        opt.credentials = 'same-origin';
        return fetch(url, opt).then(function(r){ return r.json(); });
    }

    function rbParseJson(raw){
        if (!raw) return null;

        // // object/array 그대로 허용 (프리셋 meta.json을 보기 좋게 저장했을 때 대응)
        if (typeof raw === 'object') return raw;

        raw = String(raw);
        try{ return JSON.parse(raw); }catch(e){}
        try{ return JSON.parse(raw.replace(/\\"/g,'"')); }catch(e2){}
        return null;
    }

    function rbFixStage(s){
        if (!s || typeof s !== 'object') s = {};
        if (!s.pad || typeof s.pad !== 'object') s.pad = {};
        if (typeof s.ratio === 'undefined') s.ratio = '16 / 9';
        if (typeof s.width === 'undefined') s.width = 550;
        if (typeof s.ratioFit === 'undefined') s.ratioFit = 0;
        if (typeof s.pad.t === 'undefined') s.pad.t = 20;
        if (typeof s.pad.r === 'undefined') s.pad.r = 20;
        if (typeof s.pad.b === 'undefined') s.pad.b = 20;
        if (typeof s.pad.l === 'undefined') s.pad.l = 20;
        return s;
    }

    function rbFixState(s){
        if (!s || typeof s !== 'object') s = {};
        if (!s.bg || typeof s.bg !== 'object') s.bg = {};
        if (!s.texts || !Array.isArray(s.texts)) s.texts = [];
        if (!s.order || !Array.isArray(s.order)) s.order = [];
        if (!s.btn || typeof s.btn !== 'object') s.btn = {};

        if (typeof s.bg.mode === 'undefined') s.bg.mode = 'solid';
        if (typeof s.bg.solid === 'undefined') s.bg.solid = '#ffffff';
        if (typeof s.bg.g1 === 'undefined') s.bg.g1 = '#92c9f7';
        if (typeof s.bg.g2 === 'undefined') s.bg.g2 = '#cc9afe';
        if (typeof s.bg.angle === 'undefined') s.bg.angle = 45;
        if (typeof s.bg.brightness === 'undefined') s.bg.brightness = 100;
        if (typeof s.bg.hasImage === 'undefined') s.bg.hasImage = 0;

        if (typeof s.btn.mt === 'undefined') s.btn.mt = 0;
        if (typeof s.btn.fontw === 'undefined') s.btn.fontw = 'font-R';

        return s;
    }

    function rbApplyLoaded(designRaw, stageRaw, bgUrl, copyKind, copyId){
        var ns = rbParseJson(designRaw);
        var stg = rbParseJson(stageRaw);

        if (stg) {
            stageState = rbFixStage(stg);
            applyStage();
        }

        if (ns) {
            state = rbFixState(ns);

            // // UI 동기화
            if (solidInp) solidInp.value = state.bg.solid;
            if (g1Inp) g1Inp.value = state.bg.g1;
            if (g2Inp) g2Inp.value = state.bg.g2;
            if (angleInp) angleInp.value = state.bg.angle;
            if (brightInp) brightInp.value = state.bg.brightness;
            if (brightVal) brightVal.textContent = String(state.bg.brightness) + '%';

            // // bg mode 라디오
            if (bgModeRadios && bgModeRadios.length){
                for (var i=0;i<bgModeRadios.length;i++){
                    bgModeRadios[i].checked = (bgModeRadios[i].value === state.bg.mode);
                }
            }
            setBgUIByMode();

            if (bgUrl) {
                state.bg.hasImage = 1;
                state.bg.mode = 'image';

                if (bgModeRadios && bgModeRadios.length){
                    for (var j=0;j<bgModeRadios.length;j++){
                        bgModeRadios[j].checked = (bgModeRadios[j].value === 'image');
                    }
                }

                if (bgPreviewUrl) {
                    try{ URL.revokeObjectURL(bgPreviewUrl); }catch(e){}
                    bgPreviewUrl = null;
                }

                if (bgImg) {
                    bgImg.src = bgUrl + (bgUrl.indexOf('?') > -1 ? '&' : '?') + 'v=' + Date.now();
                    bgImg.style.setProperty('display', 'block', 'important');
                }

                setBgCopy(copyKind, copyId);
            } else {
                setBgCopy('', '');
            }

            syncBtnUI();
            renderBg();
            renderStage();
            saveState();
        }
    }

    var presetSwiper = null;

    function initPresetSwiper(){
        var el = document.querySelector('#rb_preset_list .swiper, #rb_preset_list .swiper-container');
        if (!el) return;

        // 기존 인스턴스 있으면 제거
        if (presetSwiper && typeof presetSwiper.destroy === 'function') {
            try { presetSwiper.destroy(true, true); } catch(e){}
            presetSwiper = null;
        }

        // pagination은 "해당 컨테이너 내부" 것으로 잡아야 꼬임이 없음
        var pag = el.querySelector('.swiper-pagination');

        presetSwiper = new Swiper(el, {
            slidesPerView: 'auto',
            spaceBetween: 10
        });
    }

    function loadPresets(){
        if (!presetList) return;

        rbFetchJson('./banner_form.php?rb_ajax=preset_list').then(function(res){
            if (!res || !res.ok) {
                presetList.innerHTML = '';
                return;
            }

            var items = res.items || [];
            if (!items.length) {
                presetList.innerHTML = '<div style="color:#777;font-size:12px;">저장된 프리셋이 없습니다.</div>';
                return;
            }

            var html = '';
            html += '<div class="swiper-container" id="rb_swiper_1">';
            html += '  <div class="swiper-wrapper">';

            for (var i=0;i<items.length;i++){
                var it = items[i] || {};

                html += '    <div class="swiper-slide">'
                     +        '<div class="rb_preset_item" data-pid="'+rbEsc(it.id)+'">'
                     +            '<button type="button" class="rb_preset_btn rb_preset_down" data-act="down" data-pid="'+rbEsc(it.id)+'" title="다운로드">'
                     +                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0l4-4m-4 4l-4-4M5 17h14v4H5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                     +            '</button>'
                     +            '<button type="button" class="rb_preset_btn rb_preset_del" data-act="del" data-pid="'+rbEsc(it.id)+'" title="삭제">'
                     +                "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g fill='none'><path d='M24 0v24H0V0zM12.593 23.258l-.011.002-.071.035-.02.004-.014-.004-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01-.017.428.005.02.01.013.104.074.015.004.012-.004.104-.074.012-.016.004-.017-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113-.013.002-.185.093-.01.01-.003.011.018.43.005.012.008.007.201.093c.012.004.023 0 .029-.008l.004-.014-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014-.034.614c0 .012.007.02.017.024l.015-.002.201-.093.01-.008.004-.011.017-.43-.003-.012-.01-.01z'/><path fill='#09244BFF' d='M14.28 2a2 2 0 0 1 1.897 1.368L16.72 5H20a1 1 0 1 1 0 2l-.003.071-.867 12.143A3 3 0 0 1 16.138 22H7.862a3 3 0 0 1-2.992-2.786L4.003 7.07A1.01 1.01 0 0 1 4 7a1 1 0 0 1 0-2h3.28l.543-1.632A2 2 0 0 1 9.721 2zm3.717 5H6.003l.862 12.071a1 1 0 0 0 .997.929h8.276a1 1 0 0 0 .997-.929zM10 10a1 1 0 0 1 .993.883L11 11v5a1 1 0 0 1-1.993.117L9 16v-5a1 1 0 0 1 1-1m4 0a1 1 0 0 1 1 1v5a1 1 0 1 1-2 0v-5a1 1 0 0 1 1-1m.28-6H9.72l-.333 1h5.226z'/></g></svg>"
                     +            '</button>'
                     +            '<img src="'+rbEsc(it.preview_url)+'" alt="">'
                     +            '<div class="tit">'+rbEsc(it.title || it.id)+'</div>'
                     +        '</div>'
                     +    '</div>';
            }

            html += '  </div>';
            html += '</div>';

            presetList.innerHTML = html;

            requestAnimationFrame(function(){
                initPresetSwiper();
            });

        }).catch(function(){
            if (presetList) presetList.innerHTML = '';
        });
    }


    function ensureHtml2Canvas(cb){
        if (window.html2canvas) { cb(true); return; }

        var src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
        var s = document.createElement('script');
        s.src = src;
        s.onload = function(){ cb(!!window.html2canvas); };
        s.onerror = function(){ cb(false); };
        document.head.appendChild(s);
    }


    function rbCaptureFixBg(el){
        // html2canvas가 object-fit:cover 를 정확히 캡쳐 못하는 케이스 보정
        // 배경 img가 있을 때만 임시 div(background-image)로 대체해서 캡쳐하고, 끝나면 원복
        if (!el) return function(){};

        var img = el.querySelector('.rb_bd_bg_img');
        if (!img || !img.src) return function(){};

        var cs = window.getComputedStyle ? window.getComputedStyle(img) : null;
        if (cs && cs.display === 'none') return function(){};

        var parent = img.parentNode;
        if (!parent) return function(){};

        var ph = document.createElement('div');
        ph.className = 'rb_bd_bg_img_capture';
        ph.style.position = 'absolute';
        ph.style.inset = '0';
        ph.style.zIndex = (cs && cs.zIndex) ? cs.zIndex : '2';
        ph.style.backgroundImage = 'url("' + img.src.replace(/"/g,'') + '")';
        ph.style.backgroundSize = 'cover';
        ph.style.backgroundPosition = 'center center';
        ph.style.backgroundRepeat = 'no-repeat';

        parent.insertBefore(ph, img);

        var prevVis = img.style.visibility;
        img.style.visibility = 'hidden';

        return function(){
            try{ img.style.visibility = prevVis; }catch(e){}
            try{ if (ph && ph.parentNode) ph.parentNode.removeChild(ph); }catch(e){}
        };
    }

    function rbCaptureNoBorder(el){
        if (!el) return Promise.reject('no element');

        if (el.classList) el.classList.add('rb_capture_noborder');

        var _cleanupBg = function(){};

        return new Promise(function(resolve, reject){
            ensureHtml2Canvas(function(ok){
                if (!ok || !window.html2canvas){
                    try{ _cleanupBg && _cleanupBg(); }catch(e){}
                    if (el.classList) el.classList.remove('rb_capture_noborder');
                    reject('html2canvas not available');
                    return;
                }

                // object-fit 배경이미지 캡쳐 보정
                _cleanupBg = rbCaptureFixBg(el);

                var done = function(){
                    try{ _cleanupBg && _cleanupBg(); }catch(e){}
                    if (el.classList) el.classList.remove('rb_capture_noborder');
                };

                window.html2canvas(el, {
                    backgroundColor: null,
                    useCORS: true,
                    allowTaint: true,
                    scale: 1
                }).then(function(cnv){
                    done();
                    resolve(cnv);
                }).catch(function(err){
                    done();
                    reject(err);
                });
            });
        });
    }

    function setBgUIByMode(){
        if (solidRow) solidRow.style.display = (state.bg.mode === 'solid') ? '' : 'none';
        if (gradRow) gradRow.style.display = (state.bg.mode === 'gradient') ? '' : 'none';
    }

    function renderBg(){
        var b = clamp(state.bg.brightness, 30, 200);
        state.bg.brightness = b;

        if (brightInp) brightInp.value = String(b);
        if (brightVal) brightVal.textContent = String(b) + '%';
        if (bgWrap) bgWrap.style.filter = 'brightness(' + (b/100) + ')';

        setBgUIByMode();

        if (state.bg.mode === 'solid'){
            bgColor.style.background = state.bg.solid || '#ffffff';
        } else if (state.bg.mode === 'gradient'){
            var ang = clamp(state.bg.angle, 0, 360);
            state.bg.angle = ang;
            bgColor.style.background = 'linear-gradient(' + ang + 'deg, ' + (state.bg.g1||'#92c9f7') + ', ' + (state.bg.g2||'#cc9afe') + ')';
        } else {
            bgColor.style.background = '#ffffff';
        }

        var hasSrc = (bgImg && bgImg.getAttribute('src')) ? 1 : 0;
        if (state.bg.mode === 'image' && state.bg.hasImage && hasSrc) {
            bgImg.style.setProperty('display', 'block', 'important');
        } else {
            bgImg.style.setProperty('display', 'none', 'important');
        }
    }

    function setBgPreviewFromFile(file){
        if (!file) return;
        if (!file.type || file.type.indexOf('image/') !== 0) return;

        if (bgPreviewUrl) {
            try{ URL.revokeObjectURL(bgPreviewUrl); }catch(e){}
            bgPreviewUrl = '';
        }

        bgPreviewUrl = URL.createObjectURL(file);

        state.bg.mode = 'image';
        state.bg.hasImage = 1;

        for (var i=0;i<bgModeRadios.length;i++){
            if (bgModeRadios[i].value === 'image') bgModeRadios[i].checked = true;
        }

        bgImg.src = bgPreviewUrl;
        bgImg.style.setProperty('display', 'block', 'important');

        renderBg();
        saveState();
    }

    canvas.addEventListener('dragover', function(e){
        e.preventDefault();
        canvas.classList.add('rb_dragover');
    });
    canvas.addEventListener('dragleave', function(){
        canvas.classList.remove('rb_dragover');
    });
    canvas.addEventListener('drop', function(e){
        e.preventDefault();
        canvas.classList.remove('rb_dragover');
        var dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files[0]) return;

        var f = dt.files[0];
        setBgPreviewFromFile(f);

        // // 드랍 파일도 실제 업로드되게 file input에 주입(Chrome OK)
        if (bgFile) {
            try{
                var dtx = new DataTransfer();
                dtx.items.add(f);
                bgFile.files = dtx.files;
            }catch(err){}
        }
    });

    if (bgFile){
        bgFile.addEventListener('change', function(){
            if (this.files && this.files[0]) {
                setBgCopy('', '');
                setBgPreviewFromFile(this.files[0]);
            }
        });
    }

    if (btnBgClear){
        btnBgClear.addEventListener('click', function(){
            state.bg.hasImage = 0;
            setBgCopy('', '');
            if (bgPreviewUrl) {
                try{ URL.revokeObjectURL(bgPreviewUrl); }catch(e){}
                bgPreviewUrl = '';
            }
            bgImg.removeAttribute('src');
            bgImg.style.setProperty('display', 'none', 'important');

            if (state.bg.mode === 'image') state.bg.mode = 'solid';
            for (var i=0;i<bgModeRadios.length;i++){
                if (bgModeRadios[i].value === state.bg.mode) bgModeRadios[i].checked = true;
            }
            renderBg();
            saveState();
        });
    }

    // ====== 불러오기/프리셋 ======
    if (selDesignLoad){
        selDesignLoad.addEventListener('change', function(){
            var id = this.value;
            if (!id) return;

            rbFetchJson('./banner_form.php?rb_ajax=design_get&bn_id=' + encodeURIComponent(id)).then(function(res){
                if (!res || !res.ok) {
                    alert((res && res.msg) ? res.msg : '불러오기 실패');
                    return;
                }
                var bgUrl = (res.bg_has && res.bg_url) ? res.bg_url : '';
                rbApplyLoaded(res.design_json, res.stage_json, bgUrl, 'banner', String(res.bn_id));
            }).catch(function(){
                alert('불러오기 실패');
            });
        });
    }

    if (presetList){
        presetList.addEventListener('click', function(ev){
            var btn = ev.target;
            while (btn && btn !== presetList && (!btn.classList || !btn.classList.contains('rb_preset_btn'))) {
                btn = btn.parentNode;
            }
            if (btn && btn !== presetList) {
                var act = btn.getAttribute('data-act');
                var bpid = btn.getAttribute('data-pid');

                if (act === 'del' && bpid) {
                    ev.preventDefault();
                    ev.stopPropagation();

                    function doDeletePreset() {
                        var fd = new FormData();
                        fd.append('pid', bpid);

                        fetch('./banner_form.php?rb_ajax=preset_delete', {method:'POST', body:fd, credentials:'same-origin'})
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                if (!res || !res.ok) {
                                    alert((res && res.msg) ? res.msg : '삭제 실패');
                                    return;
                                }
                                loadPresets();
                            }).catch(function(){
                                alert('삭제 실패');
                            });
                    }

                    // rb_confirm가 있으면 사용, 없으면 confirm() 폴백
                    if (typeof window.rb_confirm === 'function') {
                        window.rb_confirm('이 프리셋을 삭제할까요?\n삭제시 복구할 수 없습니다.').then(function(ok){
                            if (!ok) return;
                            doDeletePreset();
                        });
                    } else {
                        if (!confirm('이 프리셋을 삭제할까요?\n삭제시 복구할 수 없습니다.')) return;
                        doDeletePreset();
                    }
                    return;
                }

                if (act === 'down' && bpid) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    window.location.href = './banner_form.php?rb_ajax=preset_download&pid=' + encodeURIComponent(bpid);
                    return;
                }
            }

            var el = ev.target;
            while (el && el !== presetList && (!el.classList || !el.classList.contains('rb_preset_item'))) {
                el = el.parentNode;
            }
            if (!el || el === presetList) return;

            var pid = el.getAttribute('data-pid');
            if (!pid) return;

            rbFetchJson('./banner_form.php?rb_ajax=preset_get&pid=' + encodeURIComponent(pid)).then(function(res){
                if (!res || !res.ok) {
                    alert((res && res.msg) ? res.msg : '적용 실패');
                    return;
                }
                var bgUrl = (res.bg_has && res.bg_url) ? res.bg_url : '';
                rbApplyLoaded(res.design_json, res.stage_json, bgUrl, (bgUrl ? 'preset' : ''), pid);
            }).catch(function(){
                alert('적용 실패');
            });
        });
    }

    if (presetSaveBtn){
        presetSaveBtn.addEventListener('click', function(){
            var t = presetTitle ? presetTitle.value.trim() : '';
            if (!t) {
                alert('프리셋 제목을 입력해 주세요.');
                if (presetTitle) presetTitle.focus();
                return;
            }

            ensureHtml2Canvas(function(ok){
                if (!ok) {
                    alert('프리셋 저장용 스크립트를 불러오지 못했습니다.');
                    return;
                }

                var el = document.getElementById('rb_bd_canvas');
                if (!el) {
                    alert('캔버스가 없습니다.');
                    return;
                }

                rbCaptureNoBorder(el).then(function(cnv){
                    var previewData = '';
                    try{ previewData = cnv.toDataURL('image/png'); }catch(e){ previewData = ''; }

                    var bgPromise = Promise.resolve('');
                    if (state && state.bg && state.bg.mode === 'image' && state.bg.hasImage && bgImg && bgImg.src) {
                        bgPromise = fetch(bgImg.src, {credentials:'same-origin'}).then(function(r){ return r.blob(); }).then(function(blob){
                            return new Promise(function(resolve){
                                var fr = new FileReader();
                                fr.onload = function(){ resolve(fr.result); };
                                fr.onerror = function(){ resolve(''); };
                                fr.readAsDataURL(blob);
                            });
                        }).catch(function(){ return ''; });
                    }

                    return bgPromise.then(function(bgData){
                        var fd = new FormData();
                        fd.append('title', t);
                        try{ fd.append('design_json', JSON.stringify(state)); }catch(e){ fd.append('design_json', ''); }
                        try{ fd.append('stage_json', JSON.stringify(stageState)); }catch(e){ fd.append('stage_json', ''); }
                        fd.append('preview_data', previewData);
                        if (bgData) fd.append('bg_data', bgData);

                        return fetch('./banner_form.php?rb_ajax=preset_save', {method:'POST', body:fd, credentials:'same-origin'})
                            .then(function(r){ return r.json(); })
                            .then(function(res){
                                if (res && res.ok) {
                                    alert('프리셋이 저장되었습니다.');
                                    if (presetTitle) presetTitle.value = '';
                                    loadPresets();
                                } else {
                                    alert((res && res.msg) ? res.msg : '저장 실패');
                                }
                            });
                    });
                }).catch(function(){
                    alert('프리셋 이미지 생성에 실패했습니다.');
                });
            });
        });
    }

    if (presetUploadBtn && presetUploadFile){
        presetUploadBtn.addEventListener('click', function(){
            presetUploadFile.click();
        });

        presetUploadFile.addEventListener('change', function(){
            if (!presetUploadFile.files || !presetUploadFile.files.length) return;

            var f = presetUploadFile.files[0];
            var fd = new FormData();
            fd.append('preset_zip', f);

            fetch('./banner_form.php?rb_ajax=preset_import', {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(res){
                    presetUploadFile.value = '';
                    if (!res || !res.ok) {
                        alert((res && res.msg) ? res.msg : '업로드 실패');
                        return;
                    }
                    loadPresets();
                }).catch(function(){
                    presetUploadFile.value = '';
                    alert('업로드 실패');
                });
        });
    }

    if (stageDownloadBtn){
        stageDownloadBtn.addEventListener('click', function(){
            ensureHtml2Canvas(function(ok){
                if (!ok) { alert('캡처 라이브러리 로드 실패'); return; }

                var el = document.getElementById('rb_bd_canvas');
                if (!el) { alert('캔버스가 없습니다.'); return; }

                rbCaptureNoBorder(el).then(function(cnv){
                    var url = '';
                    try{ url = cnv.toDataURL('image/png'); }catch(e){ url = ''; }
                    if (!url) { alert('다운로드 실패'); return; }

                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'stage_' + Date.now() + '.png';
                    document.body.appendChild(a);
                    a.click();
                    a.parentNode.removeChild(a);
                }).catch(function(){
                    alert('다운로드 실패');
                });
            });
        });
    }


    for (var i=0;i<bgModeRadios.length;i++){
        bgModeRadios[i].addEventListener('change', function(){
            state.bg.mode = this.value;
            renderBg();
            saveState();
        });
    }

    if (solidInp) solidInp.addEventListener('input', function(){ state.bg.solid = this.value || '#ffffff'; renderBg(); saveState(); });
    if (g1Inp) g1Inp.addEventListener('input', function(){ state.bg.g1 = this.value || '#92c9f7'; renderBg(); saveState(); });
    if (g2Inp) g2Inp.addEventListener('input', function(){ state.bg.g2 = this.value || '#cc9afe'; renderBg(); saveState(); });
    if (angleInp) angleInp.addEventListener('input', function(){ state.bg.angle = clamp(this.value,0,360); renderBg(); saveState(); });
    if (brightInp) brightInp.addEventListener('input', function(){ state.bg.brightness = clamp(this.value,30,200); renderBg(); saveState(); });

    // ===== 텍스트 =====
    function findText(id){
        for (var i=0;i<state.texts.length;i++){
            if (state.texts[i].id === id) return state.texts[i];
        }
        return null;
    }

    function getSelectedEl(){
        return selectedId ? stage.querySelector('[data-id="'+selectedId+'"]') : null;
    }

    function deselectAll(){
        var nodes = stage.querySelectorAll('.rb_bd_txt');
        for (var i=0;i<nodes.length;i++){
            nodes[i].classList.remove('rb_sel');
            nodes[i].setAttribute('contenteditable','false');
            nodes[i].setAttribute('draggable','true');
        }
        selectedId = '';
    }

    function selectById(id){
        deselectAll();
        selectedId = id;

        var el = getSelectedEl();
        if (!el) return;

        el.classList.add('rb_sel');

        var t = findText(id);
        if (t){
            if (sizeInp) sizeInp.value = String(clamp(t.fontSize, 8, 80));
            if (sizeNum) sizeNum.value = String(clamp(t.fontSize, 8, 80));
            if (topNum) topNum.value = String(clamp(t.mt, 0, 999));
            if (t.color && colorInp) colorInp.value = t.color;
            if (txtFontW) txtFontW.value = t.fontw || 'font-R';

            // // [추가] 클릭 선택 시 숫자표시(span)도 즉시 갱신
            var s1 = document.getElementById('rb_txt_size_n_num');
            if (s1) s1.textContent = String(clamp(t.fontSize, 8, 80)) + 'px';

            var s2 = document.getElementById('rb_txt_top_num');
            if (s2) s2.textContent = String(clamp(t.mt, 0, 999)) + 'px';

        }
    }

    // ===== 스테이지 블록 순서(드래그 정렬) =====
    function syncOrder(){
        if (!state.order || !Array.isArray(state.order)) state.order = [];

        var ids = [];
        for (var i=0;i<state.texts.length;i++){
            if (state.texts[i] && state.texts[i].id) ids.push(String(state.texts[i].id));
        }

        var out = [];
        var seen = {};

        for (var j=0;j<state.order.length;j++){
            var tok = String(state.order[j]);

            if (tok === 'btn'){
                if (state.btn && state.btn.use){
                    if (!seen['btn']) { out.push('btn'); seen['btn'] = 1; }
                }
                continue;
            }

            if (tok && ids.indexOf(tok) !== -1 && !seen[tok]){
                out.push(tok);
                seen[tok] = 1;
            }
        }

        for (var k=0;k<ids.length;k++){
            var id = ids[k];
            if (!seen[id]){
                out.push(id);
                seen[id] = 1;
            }
        }

        if (state.btn && state.btn.use && !seen['btn']){
            out.push('btn');
        }

        state.order = out;
    }

    function resortTextsByOrder(){
        if (!state.texts || !Array.isArray(state.texts)) return;
        if (!state.order || !Array.isArray(state.order)) return;

        var map = {};
        for (var i=0;i<state.texts.length;i++){
            var t = state.texts[i];
            if (t && t.id) map[String(t.id)] = t;
        }

        var arr = [];
        for (var j=0;j<state.order.length;j++){
            var tok = String(state.order[j]);
            if (tok === 'btn') continue;
            if (map[tok]) arr.push(map[tok]);
        }

        // // 혹시 누락된 텍스트가 있으면 뒤에 붙임
        for (var k=0;k<state.texts.length;k++){
            var t2 = state.texts[k];
            if (!t2 || !t2.id) continue;
            if (arr.indexOf(t2) === -1) arr.push(t2);
        }

        state.texts = arr;
    }

    function moveOrderToken(dragId, targetId, pos){
        syncOrder();

        dragId = String(dragId || '');
        targetId = String(targetId || '');

        if (!dragId) return;

        var order = state.order.slice();
        var from = order.indexOf(dragId);

        if (from === -1) return;

        order.splice(from, 1);

        if (!targetId){
            order.push(dragId);
        } else {
            var to = order.indexOf(targetId);
            if (to === -1){
                order.push(dragId);
            } else {
                if (pos === 'after') to++;
                order.splice(to, 0, dragId);
            }
        }

        state.order = order;
        resortTextsByOrder();
        saveState();
    }



        function createButtonWrap(){
        if (!state.btn || !state.btn.use) return null;

        var wrap = document.createElement('div');
        wrap.className = 'rb_bd_btn_wrap rb_bd_item';
        wrap.setAttribute('data-dragid', 'btn');
        wrap.setAttribute('draggable', 'true');
        wrap.style.textAlign = state.btn.align || 'center';
        wrap.style.marginTop = spx(clamp(state.btn.mt, 0, 999));

        var a = document.createElement('a');
        a.className = 'rb_bd_btn';
        a.textContent = state.btn.text || '자세히 보기';
        a.href = state.btn.link ? state.btn.link : 'javascript:void(0)';

        var pxv = clamp(state.btn.px, 0, 60);
        var pyv = clamp(state.btn.py, 0, 60);
        var rv = clamp(state.btn.radius, 0, 60);
        var bw = clamp(state.btn.border_w, 0, 10);

        a.style.padding = spx(pyv) + ' ' + spx(pxv);
        a.style.borderRadius = spx(rv);
        a.style.fontSize = spx(clamp(state.btn.font, 8, 80));
        a.style.background = state.btn.bg || '#ffffff';
        a.style.color = state.btn.color || '#000000';

        // 폰트 클래스 적용(기존 폰트 클래스 제거 후 1개만)
        a.classList.remove('font-R','font-B','font-H');
        a.classList.add(state.btn.fontw || 'font-R');
        a.style.fontFamily = (state.btn.fontw || 'font-R');

        if (state.btn.border) {
            a.style.borderStyle = (state.btn.border_style || 'solid');
            a.style.borderColor = (state.btn.border_color || '#cccccc');
            a.style.borderWidth = spx(bw);
        } else {
            a.style.border = 'none';
            a.style.borderWidth = '';
            a.style.borderStyle = '';
            a.style.borderColor = '';
        }

        wrap.appendChild(a);
        return wrap;
    }

    function renderButton(inner){
        if (!inner) inner = stage.querySelector('.rb_bd_stage_inner');
        if (!inner) return;

        var old = inner.querySelector('.rb_bd_btn_wrap');
        if (old && old.parentNode) old.parentNode.removeChild(old);

        var wrap = createButtonWrap();
        if (!wrap) return;

        inner.appendChild(wrap);
    }

    function ensureInner(){
        var inner = stage.querySelector('.rb_bd_stage_inner');
        if (!inner){
            inner = document.createElement('div');
            inner.className = 'rb_bd_stage_inner';
            stage.innerHTML = '';
            stage.appendChild(inner);
        }
        return inner;
    }

        function renderTexts(){
        syncOrder();

        var inner = ensureInner();
        inner.innerHTML = '';

        for (var i=0;i<state.order.length;i++){
            var tok = String(state.order[i]);

            if (tok === 'btn'){
                var bw = createButtonWrap();
                if (bw) inner.appendChild(bw);
                continue;
            }

            var t = findText(tok);
            if (!t) continue;

            var el = document.createElement('div');
            el.className = 'rb_bd_txt rb_bd_item';
            el.setAttribute('data-id', t.id);
            el.setAttribute('data-dragid', t.id);
            el.setAttribute('draggable','true');
            el.setAttribute('contenteditable','false');

            el.style.marginTop = spx(t.mt);
            el.style.fontSize = spx(t.fontSize);
            el.style.textAlign = t.align || 'left';
            el.style.color = t.color || '#000000';
            el.classList.remove('font-R','font-B','font-H');
            el.classList.add(t.fontw || 'font-R');
            el.style.fontFamily = (t.fontw || 'font-R');

            el.innerHTML = (typeof t.html === 'string' && t.html) ? t.html : '텍스트';
            inner.appendChild(el);
        }

        if (selectedId) selectById(selectedId);
        saveState();
    }

    function renderStage(){
        renderTexts();
    }

    function addText(){
        var id = uid();

        state.texts.push({
            id: id,
            html: '텍스트',
            mt: 0,
            fontSize: 18,
            align: 'left',
            color: '#000000',
            fontw: 'font-R'
        });

        selectedId = id;
        renderStage(); // 또는 renderTexts()

        if (topNum) topNum.value = '0'; // ← 중요: UI도 같이 0으로

        var el = getSelectedEl();
        if (el){
            selectById(id);
            el.setAttribute('contenteditable','true');
            el.setAttribute('draggable','false');
            el.focus();
        }
    }

    if (btnAdd) btnAdd.addEventListener('click', addText);

    stage.addEventListener('click', function(e){
        if (rbDnD && rbDnD.lastDropTs && (Date.now() - rbDnD.lastDropTs) < 250) return;
        var el = e.target;
        while (el && el !== stage && !el.classList.contains('rb_bd_txt')) el = el.parentNode;

        if (!el || el === stage) { deselectAll(); return; }

        var id = el.getAttribute('data-id') || '';
        if (!id) return;

        if (selectedId !== id) selectById(id);

        el.setAttribute('contenteditable','true');
        el.setAttribute('draggable','false');
        el.focus();
    });

    stage.addEventListener('input', function(e){
        var el = e.target;
        while (el && el !== stage && !el.classList.contains('rb_bd_txt')) el = el.parentNode;
        if (!el || el === stage) return;

        var id = el.getAttribute('data-id') || '';
        if (!id) return;

        var t = findText(id);
        if (!t) return;

        t.html = el.innerHTML;
        saveState();
    });

    // ===== 드래그&드랍(텍스트/버튼 순서 변경) =====
    var rbDnD = {dragId:'', lastDropTs:0};

    function getDndItem(target){
        var el = target;
        while (el && el !== stage && !(el.classList && el.classList.contains('rb_bd_item'))) el = el.parentNode;
        return (el && el !== stage) ? el : null;
    }

    function clearDnDOver(){
        var inner = stage.querySelector('.rb_bd_stage_inner');
        if (!inner) return;
        var nodes = inner.querySelectorAll('.rb_drag_over_top, .rb_drag_over_bottom');
        for (var i=0;i<nodes.length;i++){
            nodes[i].classList.remove('rb_drag_over_top','rb_drag_over_bottom');
        }
    }

    function clearDnDAll(){
        var inner = stage.querySelector('.rb_bd_stage_inner');
        if (!inner) return;
        var nodes = inner.querySelectorAll('.rb_dragging, .rb_drag_over_top, .rb_drag_over_bottom');
        for (var i=0;i<nodes.length;i++){
            nodes[i].classList.remove('rb_dragging','rb_drag_over_top','rb_drag_over_bottom');
        }
    }

    stage.addEventListener('dragstart', function(e){
        var item = getDndItem(e.target);
        if (!item) return;

        var dragid = item.getAttribute('data-dragid') || item.getAttribute('data-id') || '';
        if (!dragid) return;

        rbDnD.dragId = dragid;

        try{
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragid);
        }catch(err){}

        clearDnDAll();
        item.classList.add('rb_dragging');
    });

    stage.addEventListener('dragover', function(e){
        if (!rbDnD.dragId) return;
        e.preventDefault();

        var item = getDndItem(e.target);
        clearDnDOver();

        if (!item) return;

        var targetid = item.getAttribute('data-dragid') || item.getAttribute('data-id') || '';
        if (!targetid || targetid === rbDnD.dragId) return;

        var rect = item.getBoundingClientRect();
        var before = (e.clientY < (rect.top + rect.height/2));

        item.classList.add(before ? 'rb_drag_over_top' : 'rb_drag_over_bottom');
    });

    stage.addEventListener('drop', function(e){
        if (!rbDnD.dragId) return;
        e.preventDefault();

        var dragid = rbDnD.dragId;

        try{
            var dt = e.dataTransfer.getData('text/plain');
            if (dt) dragid = dt;
        }catch(err){}

        var item = getDndItem(e.target);
        var targetid = item ? (item.getAttribute('data-dragid') || item.getAttribute('data-id') || '') : '';
        var pos = 'after';

        if (item){
            var rect = item.getBoundingClientRect();
            pos = (e.clientY < (rect.top + rect.height/2)) ? 'before' : 'after';
        }

        rbDnD.dragId = '';
        rbDnD.lastDropTs = Date.now();
        clearDnDAll();

        if (!targetid){
            moveOrderToken(dragid, '', 'after');
        } else {
            moveOrderToken(dragid, targetid, pos);
        }

        renderStage();
    });

    stage.addEventListener('dragend', function(){
        rbDnD.dragId = '';
        clearDnDAll();
    });

    function applyToSelected(fn){
        if (!selectedId) return;
        var t = findText(selectedId);
        var el = getSelectedEl();
        if (!t || !el) return;
        fn(t, el);
        saveState();
    }



    if (colorInp){
        colorInp.addEventListener('input', function(){
            var c = this.value || '#000000';
            applyToSelected(function(t, el){
                t.color = c;
                el.style.color = c;
            });
        });
    }

    function applyFontSize(n){
        n = clamp(n, 8, 80);
        if (sizeInp) sizeInp.value = String(n);
        if (sizeNum) sizeNum.value = String(n);

        applyToSelected(function(t, el){
            t.fontSize = n;
            el.style.fontSize = spx(n);
        });
    }
    if (sizeInp) sizeInp.addEventListener('input', function(){ applyFontSize(this.value); });
    if (sizeNum) sizeNum.addEventListener('input', function(){ applyFontSize(this.value); });

    if (topNum){
        topNum.addEventListener('input', function(){
            var v = clamp(this.value, 0, 999);
            this.value = String(v);
            applyToSelected(function(t, el){
                t.mt = v;
                el.style.marginTop = spx(v);
            });
        });
    }

    for (var k=0;k<alignBtns.length;k++){
        alignBtns[k].addEventListener('click', function(){
            var a = this.getAttribute('data-align') || 'left';
            applyToSelected(function(t, el){
                t.align = a;
                el.style.textAlign = a;
            });
        });
    }

    if (btnDel){
        btnDel.addEventListener('click', function(){
            if (!selectedId) return;
            var next = [];
            for (var i=0;i<state.texts.length;i++){
                if (state.texts[i].id !== selectedId) next.push(state.texts[i]);
            }
            state.texts = next;
            selectedId = '';
            renderStage();
            deselectAll();
        });
    }

    if (txtFontW){
        txtFontW.addEventListener('change', function(){
            if (!selectedId) return;

            var v = this.value || 'font-R';

            var t = findText(selectedId);
            if (!t) return;

            t.fontw = v;

            var el = stage.querySelector('[data-id="'+selectedId+'"]');
            if (el){
                el.classList.remove('font-R','font-B','font-H');
                el.classList.add(v);

                // font-family 인라인 강제
                el.style.setProperty('font-family', v, 'important');
            }

            saveState();
        });
    }

    // ===== 버튼 설정 반영 =====
    function syncBtnUI(){
        if (!state.btn) return;

        if (btnUse) btnUse.checked = state.btn.use ? true : false;
        if (btnAlign) btnAlign.value = state.btn.align || 'center';
        if (btnText) btnText.value = state.btn.text || '자세히 보기';
        if (btnLink) btnLink.value = state.btn.link || '';
        if (btnNewWin) btnNewWin.checked = state.btn.new_win ? true : false;

        if (btnPX) btnPX.value = String(clamp(state.btn.px, 0, 60));
        if (btnPY) btnPY.value = String(clamp(state.btn.py, 0, 60));
        if (btnRadius) btnRadius.value = String(clamp(state.btn.radius, 0, 60));
        if (btnFont) btnFont.value = String(clamp(state.btn.font, 8, 80));

        if (btnBg) btnBg.value = state.btn.bg || '#ffffff';
        if (btnColor) btnColor.value = state.btn.color || '#000000';

        if (btnBorder) btnBorder.checked = state.btn.border ? true : false;
        if (btnBorderStyle) btnBorderStyle.value = state.btn.border_style || 'solid';
        if (btnBorderW) btnBorderW.value = String(clamp(state.btn.border_w, 0, 10));
        if (btnBorderColor) btnBorderColor.value = state.btn.border_color || '#cccccc';

        if (btnMt) btnMt.value = String(clamp(state.btn.mt, 0, 999));
        if (btnFontW) btnFontW.value = state.btn.fontw || 'font-R';
    }

    function applyBtnFromUI(){
        if (!state.btn) return;

        state.btn.use = (btnUse && btnUse.checked) ? 1 : 0;
        state.btn.align = btnAlign ? btnAlign.value : 'center';
        state.btn.text = btnText ? (btnText.value || '자세히 보기') : '자세히 보기';
        state.btn.link = btnLink ? (btnLink.value || '') : '';
        state.btn.new_win = (btnNewWin && btnNewWin.checked) ? 1 : 0;

        state.btn.px = clamp(btnPX ? btnPX.value : 10, 0, 60);
        state.btn.py = clamp(btnPY ? btnPY.value : 16, 0, 60);
        state.btn.radius = clamp(btnRadius ? btnRadius.value : 12, 0, 60);
        state.btn.font = clamp(btnFont ? btnFont.value : 14, 8, 80);

        state.btn.bg = btnBg ? (btnBg.value || '#ffffff') : '#ffffff';
        state.btn.color = btnColor ? (btnColor.value || '#000000') : '#000000';

        state.btn.border = (btnBorder && btnBorder.checked) ? 1 : 0;
        state.btn.border_style = btnBorderStyle ? (btnBorderStyle.value || 'solid') : 'solid';
        state.btn.border_w = clamp(btnBorderW ? btnBorderW.value : 1, 0, 10);
        state.btn.border_color = btnBorderColor ? (btnBorderColor.value || '#cccccc') : '#cccccc';

        state.btn.mt = clamp(btnMt ? btnMt.value : 0, 0, 999);
        state.btn.fontw = btnFontW ? (btnFontW.value || 'font-R') : 'font-R';

        renderStage();
        saveState();
    }

    if (btnUse) btnUse.addEventListener('change', applyBtnFromUI);
    if (btnAlign) btnAlign.addEventListener('change', applyBtnFromUI);
    if (btnText) btnText.addEventListener('input', applyBtnFromUI);
    if (btnLink) btnLink.addEventListener('input', applyBtnFromUI);
    if (btnNewWin) btnNewWin.addEventListener('change', applyBtnFromUI);
    if (btnPX) btnPX.addEventListener('input', applyBtnFromUI);
    if (btnPY) btnPY.addEventListener('input', applyBtnFromUI);
    if (btnRadius) btnRadius.addEventListener('input', applyBtnFromUI);
    if (btnFont) btnFont.addEventListener('input', applyBtnFromUI);
    if (btnBg) btnBg.addEventListener('input', applyBtnFromUI);
    if (btnColor) btnColor.addEventListener('input', applyBtnFromUI);
    if (btnBorder) btnBorder.addEventListener('change', applyBtnFromUI);
    if (btnBorderStyle) btnBorderStyle.addEventListener('change', applyBtnFromUI);
    if (btnBorderW) btnBorderW.addEventListener('input', applyBtnFromUI);
    if (btnBorderColor) btnBorderColor.addEventListener('input', applyBtnFromUI);
    if (btnMt) btnMt.addEventListener('input', applyBtnFromUI);
    if (btnFontW) btnFontW.addEventListener('change', applyBtnFromUI);

    // 초기 UI/렌더
    if (solidInp) solidInp.value = state.bg.solid || '#ffffff';
    if (g1Inp) g1Inp.value = state.bg.g1 || '#92c9f7';
    if (g2Inp) g2Inp.value = state.bg.g2 || '#cc9afe';
    if (angleInp) angleInp.value = String(clamp(state.bg.angle,0,360));
    if (brightInp) brightInp.value = String(clamp(state.bg.brightness,30,200));
    if (colorInp) colorInp.value = '#000000';

    for (var i=0;i<bgModeRadios.length;i++){
        if (bgModeRadios[i].value === state.bg.mode) bgModeRadios[i].checked = true;
    }

    syncBtnUI();
    renderBg();
    renderStage();
})();

</script>





    <tr>
        <th scope="row"><label for="bn_alt">이미지 설명</label></th>
        <td>
            <?php echo help("이미지 태그의 alt, title 에 해당되는 내용입니다."); ?>
            <input type="text" name="bn_alt" value="<?php echo isset($bn['bn_alt']) ? get_text($bn['bn_alt']) : ''; ?>" id="bn_alt" class="frm_input" size="80">
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_url">링크</label></th>
        <td>
            <?php echo help("배너이미지 클릭시 이동하는 Url입니다.<br>디자인형 배너의 경우 전체영역에 링크가 추가 됩니다."); ?>
            <input type="text" name="bn_url" size="80" value="<?php echo isset($bn['bn_url']) ? $bn['bn_url'] : 'http://'; ?>" id="bn_url" class="frm_input">
        </td>
    </tr>

    <tr>
        <th scope="row"><label for="bn_position">출력그룹</label></th>
        <td>
            <select name="bn_position" id="bn_position">
                <?php echo rb_banner_group_list($bn['bn_position']) ?>
                <option value="개별출력" <?php echo get_selected(isset($bn['bn_position']) ? $bn['bn_position'] : '', '개별출력'); ?>>개별출력</option>
				<option value="" <?php echo get_selected(isset($bn['bn_position']) ? $bn['bn_position'] : '', ''); ?>>미출력</option>
			</select> <input type="text" name="bn_position_use" id="bn_position_use" class="frm_input" placeholder="그룹생성">
			<br><br>
			<?php echo help("개별출력의 경우 그룹화 되지 않습니다. 그룹이 없는 경우 그룹생성 항목에 직접 입력하시면 생성 됩니다.<br>생성되는 배너는 모두 모듈설정 패널에서 사용하실 수 있습니다."); ?>

			<?php echo htmlspecialchars("그룹별 출력 : <?php echo rb_banners('그룹명'); ?>"); ?><br>
			<?php echo htmlspecialchars("개별출력 : <?php echo rb_banners('개별출력', '배너ID'); ?>"); ?><br>
			<?php echo htmlspecialchars("미출력 : 배너를 출력하지 않습니다."); ?>

        </td>
    </tr>


    <tr>
        <th scope="row"><label for="bn_device">출력기기</label></th>
        <td>
            <?php echo help('배너이미지를 출력할 기기를 선택할 수 있습니다.'); ?>
            <?php $bn_device = isset($bn['bn_device']) ? $bn['bn_device'] : null; ?>
            <select name="bn_device" id="bn_device">
                <option value="both"<?php echo get_selected($bn_device, 'both', true); ?>>PC와 모바일</option>
                <option value="pc"<?php echo get_selected($bn_device, 'pc', true); ?>>PC</option>
                <option value="mobile"<?php echo get_selected($bn_device, 'mobile', true); ?>>모바일</option>
        </select>
        </td>
    </tr>

    <tr>
        <th scope="row"><label for="bn_border">테두리</label></th>
        <td>
             <?php echo help("배너이미지에 테두리를 넣을지를 설정합니다.", 50); ?>
            <select name="bn_border" id="bn_border">
                <option value="0" <?php echo get_selected(isset($bn['bn_border']) ? $bn['bn_border'] : 0, 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected(isset($bn['bn_border']) ? $bn['bn_border'] : 1, 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_radius">모서리</label></th>
        <td>
             <?php echo help("배너이미지에 모서리를 둥글게 처리할지를 설정 합니다.", 50); ?>
            <select name="bn_radius" id="bn_radius">
                <option value="0" <?php echo get_selected(isset($bn['bn_radius']) ? $bn['bn_radius'] : 0, 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected(isset($bn['bn_radius']) ? $bn['bn_radius'] : 1, 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_ad_ico">PR아이콘</label></th>
        <td>
             <?php echo help("배너이미지에 PR 아이콘을 보여줄지 설정 합니다.", 50); ?>
            <select name="bn_ad_ico" id="bn_ad_ico">
                <option value="0" <?php echo get_selected(isset($bn['bn_ad_ico']) ? $bn['bn_ad_ico'] : 0, 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected(isset($bn['bn_ad_ico']) ? $bn['bn_ad_ico'] : 1, 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_new_win">새창</label></th>
        <td>
            <?php echo help("배너이미지 클릭시 새창연결 여부를 선택할 수 있습니다.", 50); ?>
            <select name="bn_new_win" id="bn_new_win">
                <option value="0" <?php echo get_selected(isset($bn['bn_new_win']) ? $bn['bn_new_win'] : 0, 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected(isset($bn['bn_new_win']) ? $bn['bn_new_win'] : 1, 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_begin_time">게시 시작일시</label></th>
        <td>
            <?php echo help("배너 게시 시작일시를 설정합니다."); ?>
            <input type="text" name="bn_begin_time" value="<?php echo isset($bn['bn_begin_time']) ? $bn['bn_begin_time'] : date("Y-m-d 00:00:00", time()); ?>" id="bn_begin_time" class="frm_input" size="21" maxlength="19">
            <input type="checkbox" name="bn_begin_chk" value="<?php echo date("Y-m-d 00:00:00", time()); ?>" id="bn_begin_chk" onclick="if (this.checked == true) this.form.bn_begin_time.value=this.form.bn_begin_chk.value; else this.form.bn_begin_time.value = this.form.bn_begin_time.defaultValue;">
            <label for="bn_begin_chk">오늘</label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_end_time">게시 종료일시</label></th>
        <td>
            <?php echo help("배너 게시 종료일시를 설정합니다."); ?>
            <input type="text" name="bn_end_time" value="<?php echo isset($bn['bn_end_time']) ? $bn['bn_end_time'] : date("Y-m-d 00:00:00", time() + 60 * 60 * 24 * 31); ?>" id="bn_end_time" class="frm_input" size=21 maxlength=19>
            <input type="checkbox" name="bn_end_chk" value="<?php echo date("Y-m-d 23:59:59", time() + 60 * 60 * 24 * 31); ?>" id="bn_end_chk" onclick="if (this.checked == true) this.form.bn_end_time.value=this.form.bn_end_chk.value; else this.form.bn_end_time.value = this.form.bn_end_time.defaultValue;">
            <label for="bn_end_chk">오늘+31일</label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_order">출력 순서</label></th>
        <td>
           <?php echo help("배너를 출력할 때 순서를 정합니다. 숫자가 작을수록 먼저 출력됩니다."); ?>
           <?php echo order_select("bn_order", isset($bn['bn_order']) ? $bn['bn_order'] : 0); ?>
        </td>
    </tr>

    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./banner_list.php" class="btn_02 btn">목록</a>
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>

</form>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
?>
