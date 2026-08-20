<?php
include_once('../common.php');
@set_time_limit(0);

$rb_sitemap_is_post = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
$rb_sitemap_cache_ttl = 86400;

if (!defined('G5_PATH') || !defined('G5_URL')) {
    http_response_code(500);
    header('Content-Type: ' . ($rb_sitemap_is_post ? 'application/json' : 'text/plain') . '; charset=utf-8');
    echo $rb_sitemap_is_post
        ? json_encode(['success' => false, 'msg' => 'G5_PATH/G5_URL undefined'])
        : 'G5_PATH/G5_URL undefined';
    exit;
}

$sitemap_file = G5_PATH . '/sitemap.xml';
$base_url     = G5_URL;
$bbs_url      = defined('G5_BBS_URL') ? G5_BBS_URL : (G5_URL . '/bbs');
$sitemap_url  = G5_URL . '/rb/sitemap.php';
$download_url = $sitemap_url . '?download=1';
$rb_sitemap_download = isset($_GET['download']) && $_GET['download'] === '1';

if ($rb_sitemap_is_post && empty($is_admin)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'msg' => '관리자만 사이트맵을 생성할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function rb_sitemap_output_xml($path, $xml, $cache_ttl, $download = false) {
    $mtime = @filemtime($path);
    if ($mtime === false) $mtime = time();
    $etag = '"' . sha1($xml) . '"';

    if (!$download) {
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
        if ($if_none_match === $etag || ($if_modified_since !== false && $if_modified_since >= $mtime)) {
            http_response_code(304);
            header('ETag: ' . $etag);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
            exit;
        }
    }

    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=' . (int)$cache_ttl);
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    if ($download) {
        header('Content-Disposition: attachment; filename="sitemap.xml"');
    }
    echo $xml;
    exit;
}

if (!$rb_sitemap_is_post && is_file($sitemap_file) && @filesize($sitemap_file) > 0) {
    $mtime = @filemtime($sitemap_file);
    if ($mtime !== false && $mtime >= time() - $rb_sitemap_cache_ttl) {
        $cached_xml = @file_get_contents($sitemap_file);
        if ($cached_xml !== false && $cached_xml !== '') {
            rb_sitemap_output_xml($sitemap_file, $cached_xml, $rb_sitemap_cache_ttl, $rb_sitemap_download);
        }
    }
}

function rb_write_atomic($path, $content) {
    // 1. 원자적 쓰기 시도
    $tmp = $path . '.tmp';
    $wrote = @file_put_contents($tmp, $content, LOCK_EX);
    if ($wrote !== false) {
        if (@rename($tmp, $path)) {
            return ['ok' => true];
        }
        @unlink($tmp);
    }

    // 2. 직접 쓰기 시도
    if (@file_put_contents($path, $content, LOCK_EX) !== false) {
        return ['ok' => true];
    }

    // 3. LOCK_EX 없이 시도
    if (@file_put_contents($path, $content) !== false) {
        return ['ok' => true];
    }

    // 4. fopen 방식 시도
    $fh = @fopen($path, 'w');
    if ($fh) {
        $result = @fwrite($fh, $content);
        @fclose($fh);
        if ($result !== false) return ['ok' => true];
    }

    return ['ok' => false, 'msg' => '파일 쓰기 실패: ' . $path . ' (권한을 확인해주세요. chmod 644 또는 쓰기권한 필요)'];
}

function esc_loc($url) {
    return htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$rb_sitemap_data_path = defined('G5_DATA_PATH') ? G5_DATA_PATH : (G5_PATH . '/data');
$rb_sitemap_lock_dir = $rb_sitemap_data_path . '/cache';
if (!is_dir($rb_sitemap_lock_dir)) {
    @mkdir($rb_sitemap_lock_dir, 0755, true);
}
$rb_sitemap_lock = @fopen($rb_sitemap_lock_dir . '/rb_sitemap.lock', 'c');
if (!$rb_sitemap_lock || !@flock($rb_sitemap_lock, LOCK_EX)) {
    if ($rb_sitemap_lock) @fclose($rb_sitemap_lock);
    $cached_xml = @file_get_contents($sitemap_file);
    if (!$rb_sitemap_is_post && $cached_xml !== false && $cached_xml !== '') {
        rb_sitemap_output_xml($sitemap_file, $cached_xml, 60, $rb_sitemap_download);
    }
    http_response_code(500);
    header('Content-Type: ' . ($rb_sitemap_is_post ? 'application/json' : 'text/plain') . '; charset=utf-8');
    $msg = '사이트맵 캐시 잠금 파일을 생성할 수 없습니다. data/cache 디렉터리의 쓰기 권한을 확인해 주세요.';
    echo $rb_sitemap_is_post ? json_encode(['success' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE) : $msg;
    exit;
}

clearstatcache(true, $sitemap_file);
if (!$rb_sitemap_is_post && is_file($sitemap_file) && @filesize($sitemap_file) > 0) {
    $mtime = @filemtime($sitemap_file);
    if ($mtime !== false && $mtime >= time() - $rb_sitemap_cache_ttl) {
        $cached_xml = @file_get_contents($sitemap_file);
        if ($cached_xml !== false && $cached_xml !== '') {
            @flock($rb_sitemap_lock, LOCK_UN);
            @fclose($rb_sitemap_lock);
            rb_sitemap_output_xml($sitemap_file, $cached_xml, $rb_sitemap_cache_ttl, $rb_sitemap_download);
        }
    }
}

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$xml .= "  <url>";
$xml .= '<loc>' . esc_loc($base_url) . '</loc>';
$xml .= '<changefreq>daily</changefreq><priority>1.0</priority>';
$xml .= "</url>\n";

$boards = [];
$res = sql_query("
    SELECT bo_table
      FROM {$g5['board_table']}
     WHERE bo_use_search='1'
       AND bo_list_level='1'
       AND bo_read_level='1'
");
while ($r = sql_fetch_array($res)) {
    if (!empty($r['bo_table'])) $boards[] = $r['bo_table'];
}

foreach ($boards as $bo_table) {
    $loc = function_exists('get_pretty_url') ? get_pretty_url($bo_table) : $bbs_url . '/board.php?bo_table=' . urlencode($bo_table);
    $xml .= "  <url>";
    $xml .= '<loc>' . esc_loc($loc) . '</loc>';
    $xml .= '<changefreq>daily</changefreq><priority>0.7</priority>';
    $xml .= "</url>\n";
}

foreach ($boards as $bo_table) {
    $wres = sql_query("
        SELECT wr_id, wr_last, wr_option
          FROM {$g5['write_prefix']}{$bo_table}
         WHERE wr_is_comment = 0
    ");
    while ($w = sql_fetch_array($wres)) {
        $opt = (string)($w['wr_option'] ?? '');
        if (strpos($opt, 'secret') !== false) continue;

        $loc = function_exists('get_pretty_url') ? get_pretty_url($bo_table, $w['wr_id']) : $bbs_url . '/board.php?bo_table=' . urlencode($bo_table) . '&wr_id=' . (int)$w['wr_id'];

        $xml .= "  <url>";
        $xml .= '<loc>' . esc_loc($loc) . '</loc>';
        if (!empty($w['wr_last'])) {
            $ts = strtotime($w['wr_last']);
            if ($ts) $xml .= '<lastmod>' . date('c', $ts) . '</lastmod>';
        }
        $xml .= '<changefreq>daily</changefreq><priority>1.0</priority>';
        $xml .= "</url>\n";
    }
}

$cres = sql_query("SELECT co_id FROM {$g5['content_table']}");
while ($c = sql_fetch_array($cres)) {
    $co_id = $c['co_id'] ?? '';
    if ($co_id === '') continue;

    $loc = function_exists('get_pretty_url') ? get_pretty_url('content', $co_id) : $bbs_url . '/content.php?co_id=' . urlencode($co_id);

    $xml .= "  <url>";
    $xml .= '<loc>' . esc_loc($loc) . '</loc>';
    $xml .= '<changefreq>monthly</changefreq><priority>0.5</priority>';
    $xml .= "</url>\n";
}

if (defined('G5_USE_SHOP') && G5_USE_SHOP) {
    $catres = sql_query("SELECT ca_id FROM {$g5['g5_shop_category_table']} WHERE ca_use='1'");
    while ($cr = sql_fetch_array($catres)) {
        $ca_id = $cr['ca_id'] ?? '';
        if ($ca_id === '') continue;
        $loc = G5_URL . '/shop/list.php?ca_id=' . urlencode($ca_id);
        $xml .= "  <url>";
        $xml .= '<loc>' . esc_loc($loc) . '</loc>';
        $xml .= '<changefreq>weekly</changefreq><priority>0.7</priority>';
        $xml .= "</url>\n";
    }

    $special_types = ['it_type1', 'it_type2', 'it_type3', 'it_type4', 'it_type5'];
    $already = [];

    foreach ($special_types as $col) {
        $ires = sql_query("SELECT it_id, it_time FROM {$g5['g5_shop_item_table']} WHERE it_use='1' AND {$col}='1'");
        while ($it = sql_fetch_array($ires)) {
            $it_id = $it['it_id'] ?? '';
            if ($it_id === '' || isset($already[$it_id])) continue;
            $already[$it_id] = 1;
            $loc = G5_URL . '/shop/item.php?it_id=' . urlencode($it_id);
            $xml .= "  <url>";
            $xml .= '<loc>' . esc_loc($loc) . '</loc>';
            if (!empty($it['it_time'])) {
                $ts = strtotime($it['it_time']);
                if ($ts) $xml .= '<lastmod>' . date('c', $ts) . '</lastmod>';
            }
            $xml .= '<changefreq>daily</changefreq><priority>1.0</priority>';
            $xml .= "</url>\n";
        }
    }

    $aires = sql_query("SELECT it_id, it_time FROM {$g5['g5_shop_item_table']} WHERE it_use='1'");
    while ($it = sql_fetch_array($aires)) {
        $it_id = $it['it_id'] ?? '';
        if ($it_id === '' || isset($already[$it_id])) continue;
        $already[$it_id] = 1;
        $loc = G5_URL . '/shop/item.php?it_id=' . urlencode($it_id);
        $xml .= "  <url>";
        $xml .= '<loc>' . esc_loc($loc) . '</loc>';
        if (!empty($it['it_time'])) {
            $ts = strtotime($it['it_time']);
            if ($ts) $xml .= '<lastmod>' . date('c', $ts) . '</lastmod>';
        }
        $xml .= '<changefreq>daily</changefreq><priority>0.9</priority>';
        $xml .= "</url>\n";
    }
}

$xml .= '</urlset>';

$result = rb_write_atomic($sitemap_file, $xml);
if (!$result['ok']) {
    @flock($rb_sitemap_lock, LOCK_UN);
    @fclose($rb_sitemap_lock);
    http_response_code(500);
    header('Content-Type: ' . ($rb_sitemap_is_post ? 'application/json' : 'text/plain') . '; charset=utf-8');
    echo $rb_sitemap_is_post
        ? json_encode(['success' => false, 'msg' => $result['msg']], JSON_UNESCAPED_UNICODE)
        : $result['msg'];
    exit;
}

@flock($rb_sitemap_lock, LOCK_UN);
@fclose($rb_sitemap_lock);

// robots.txt 처리
$robots = '';
$rb_seo_ok = false;

$rb_res = @sql_query("SELECT se_robots FROM rb_seo LIMIT 1");
if ($rb_res) {
    $rb_row = @sql_fetch_array($rb_res);
    if ($rb_row && isset($rb_row['se_robots'])) {
        $robots = (string)$rb_row['se_robots'];
        $rb_seo_ok = true;
    }
}
$robots = trim($robots);
$robots = preg_replace('#^Sitemap:.*$#mi', '', $robots);
$robots = trim($robots);

if ($robots !== '' && substr($robots, -1) !== "\n") $robots .= "\n";
$robots .= "Sitemap: {$sitemap_url}\n";

if ($rb_seo_ok) {
    $esc = function_exists('sql_real_escape_string') ? sql_real_escape_string($robots) : addslashes($robots);
    @sql_query("UPDATE rb_seo SET se_robots = '{$esc}'");
}

$robots_result = rb_write_atomic(G5_PATH . '/robots.txt', $robots);
if (!$robots_result['ok']) {
    if (!$rb_sitemap_is_post) {
        rb_sitemap_output_xml($sitemap_file, $xml, $rb_sitemap_cache_ttl, $rb_sitemap_download);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'url' => $download_url, 'sitemap_url' => $sitemap_url, 'warning' => $robots_result['msg']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$rb_sitemap_is_post) {
    rb_sitemap_output_xml($sitemap_file, $xml, $rb_sitemap_cache_ttl, $rb_sitemap_download);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'url' => $download_url, 'sitemap_url' => $sitemap_url], JSON_UNESCAPED_UNICODE);
exit;
