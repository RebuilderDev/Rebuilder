<?php
if (!defined('_GNUBOARD_')) exit;

// 2.2.7.7: 메뉴 N 아이콘. 기존 get_new_ico()와 별도로 동작한다.
function rb_hn_path($key) {
    global $g5;
    $scope = (defined('G5_MYSQL_DB') ? G5_MYSQL_DB : '').'|'.G5_URL;
    foreach (array('board_new_table','qa_content_table','g5_shop_item_table') as $table) $scope .= '|'.(isset($g5[$table]) ? $g5[$table] : '');
    return G5_DATA_PATH.'/cache/rb_header_new_'.sha1($scope.'|'.$key).'.php';
}
function rb_hn_generation($tag) {
    $value = @file_get_contents(rb_hn_path('generation|'.$tag));
    return $value === false ? '' : $value;
}
function rb_hn_invalidate($tag) {
    @file_put_contents(rb_hn_path('generation|'.$tag), '<?php exit; ?>'.uniqid('', true), LOCK_EX);
}
function rb_hn_cached($key, $tag, $loader, $ttl = 60) {
    static $memory = array();
    $generation = rb_hn_generation($tag).'|'.rb_hn_generation('indexes');
    $memory_key = $key.'|'.$generation;
    if (array_key_exists($memory_key, $memory)) return $memory[$memory_key];
    $path = rb_hn_path($key);
    $raw = @file_get_contents($path);
    $data = $raw === false ? null : json_decode(substr($raw, 14), true);
    $now = time();
    if (is_array($data) && isset($data['until'], $data['generation']) && array_key_exists('value', $data) && $data['generation'] === $generation && $data['until'] > $now) {
        return $memory[$memory_key] = $data['value'];
    }
    // 갱신 요청 하나만 DB를 조회한다. 다른 요청은 기존 값 또는 빈 값을 사용한다.
    $lock = @fopen($path.'.lock', 'c');
    if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return $memory[$memory_key] = (is_array($data) && isset($data['generation']) && array_key_exists('value', $data) && $data['generation'] === $generation ? $data['value'] : null);
    }
    if ($lock) {
        $fresh = @file_get_contents($path);
        $fresh = $fresh === false ? null : json_decode(substr($fresh, 14), true);
        if (is_array($fresh) && isset($fresh['generation'], $fresh['until']) && array_key_exists('value', $fresh) && $fresh['generation'] === $generation && $fresh['until'] > $now) {
            flock($lock, LOCK_UN); fclose($lock);
            return $memory[$memory_key] = $fresh['value'];
        }
    }
    try {
        $value = call_user_func($loader);
        $payload = json_encode(array('until'=>$now+$ttl, 'generation'=>$generation, 'value'=>$value));
        $tmp = @tempnam(dirname($path), 'rb_hn_');
        if ($tmp !== false) {
            if (@file_put_contents($tmp, "<?php exit; ?>\n".$payload) !== false) @rename($tmp, $path);
            if (is_file($tmp)) @unlink($tmp);
        }
    } catch (Exception $e) {
        $value = null;
    }
    if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
    return $memory[$memory_key] = $value;
}
function rb_hn_table($table) {
    return is_string($table) && preg_match('/^[a-zA-Z0-9_]+$/D', $table) ? '`'.$table.'`' : '';
}
function rb_hn_indexed($table, $index) {
    return rb_hn_cached('index|'.$table.'|'.$index, 'indexes', function() use ($table, $index) {
        $safe = rb_hn_table($table);
        if (!$safe) return false;
        $row = sql_fetch("SHOW INDEX FROM {$safe} WHERE Key_name = '".sql_real_escape_string($index)."'", false);
        return !empty($row['Key_name']);
    }, 300);
}
function rb_hn_ready($shop = null) {
    global $g5;
    if ($shop === null) $shop = defined('_SHOP_');
    $key = $shop ? 'g5_shop_item_table' : 'board_new_table';
    return !empty($g5[$key]) && rb_hn_indexed($g5[$key], $shop ? 'rb_header_new_item' : 'rb_header_new_board');
}
function rb_hn_boards() {
    global $g5;
    return (array)rb_hn_cached('boards', 'boards', function() use ($g5) {
        $table = rb_hn_table($g5['board_table']);
        $groups = rb_hn_table($g5['group_table']);
        if (!$table || !$groups) return array();
        $result = sql_query("SELECT b.bo_table, b.bo_list_level, b.bo_read_level, b.bo_admin, b.gr_id, g.gr_use_access, g.gr_admin FROM {$table} b LEFT JOIN {$groups} g ON g.gr_id=b.gr_id", false);
        $rows = array();
        if ($result) while ($row = sql_fetch_array($result)) $rows[$row['bo_table']] = $row;
        return $rows;
    });
}
function rb_hn_settings($shop = null) {
    global $rb_config;
    if ($shop === null) $shop = defined('_SHOP_');
    $suffix = $shop ? '_shop' : '';
    $get = function($key, $default) use ($rb_config, $suffix) {
        return isset($rb_config['co_header_new_'.$key.$suffix]) ? $rb_config['co_header_new_'.$key.$suffix] : $default;
    };
    $color = $get('color', '');
    if ($color === '') $color = isset($rb_config['co_color']) ? $rb_config['co_color'] : '#AA20FF';
    if (!preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/iD', $color)) $color = '#AA20FF';
    return array('use'=>(int)$get('use', 0) === 1, 'days'=>max(1, min(365, (int)$get('days', 1))),
        'tpl'=>max(1, min(3, (int)$get('tpl', 1))), 'color'=>$color,
        'size'=>max(8, min(24, (int)$get('size', 10))), 'font'=>$get('font', 'font-B') === 'font-R' ? 'font-R' : 'font-B');
}
function rb_hn_badge($options) {
    return '<span class="rb-header-new rb-header-new-'.$options['tpl'].' '.$options['font'].'" style="background-color:'.htmlspecialchars($options['color'], ENT_QUOTES, 'UTF-8').';font-size:'.$options['size'].'px" aria-label="새글">N</span>';
}
function rb_hn_resolve($link) {
    if (!is_string($link) || strlen($link) > 8192) return null;
    $link = trim(html_entity_decode($link, ENT_QUOTES, 'UTF-8'));
    $url = @parse_url($link); $base = @parse_url(G5_URL);
    if (!$url || isset($url['user']) || isset($url['pass'])) return null;
    if (isset($url['scheme']) && !in_array(strtolower($url['scheme']), array('http','https'), true)) return null;
    if (isset($url['scheme']) && !isset($url['host'])) return null;
    if (isset($url['host']) && (!isset($base['host']) || strcasecmp($url['host'], $base['host']) !== 0)) return null;
    if (isset($url['port']) && !in_array((int)$url['port'], array(80,443,isset($base['port']) ? (int)$base['port'] : 80), true)) return null;
    $root = isset($base['path']) ? rtrim(rawurldecode($base['path']), '/') : '';
    $path = isset($url['path']) ? rawurldecode($url['path']) : '';
    if (strpos($path, '..') !== false || preg_match('/[\x00-\x1f\x7f\\\\]/', $path)) return null;
    // ?bo_table=... 단독 주소와 설치 경로 기준 상대 주소도 처리한다.
    if ($path === '') {
        if (!isset($url['query'])) return null;
        $path = isset($url['host']) ? '/' : $root.'/';
    } elseif ($path[0] !== '/') $path = $root.'/'.$path;
    if ($root !== '' && strpos($path, $root.'/') !== 0) return null;
    $relative = substr($path, strlen($root));
    $query = array(); if (isset($url['query'])) parse_str($url['query'], $query);
    foreach (array('bo_table','onetable','sca','ca_id','wr_id','wr_seo_title','stx','sfl','it_id','it_seo_title','type','q','qname','qexplan','qid','qbasic','qcaid','qfrom','qto','qa_id') as $key) {
        if (isset($query[$key]) && !is_scalar($query[$key])) return null;
    }
    $bbs = rawurldecode(parse_url(G5_BBS_URL, PHP_URL_PATH));
    $category = isset($query['sca']) ? (string)$query['sca'] : '';
    // 파일 경로 전체를 확인한다. /qalist 게시판과 /bbs/qalist.php 문의는 다른 대상이다.
    if (in_array($path, array($bbs.'/qalist.php',$bbs.'/qaview.php',$bbs.'/qawrite.php'), true)) return array('kind'=>'qa','id'=>'qa','category'=>$category);
    $shop_target = rb_hn_shop_resolve($path, $query, $root);
    if ($shop_target) return $shop_target;
    // 명시적인 bo_table은 커스텀 페이지에서도 실제 게시판 목록과 대조한다.
    if (isset($query['bo_table']) && $query['bo_table'] !== '') $id = (string)$query['bo_table'];
    elseif ($path === $bbs.'/search.php' && !empty($query['onetable'])) $id = (string)$query['onetable'];
    elseif (preg_match('~^/rss/([a-zA-Z0-9_]+)$~D', $relative, $match)) $id = $match[1];
    elseif (preg_match('~^/([a-zA-Z0-9_]+)(?:/?|/write/?|/[0-9]+/?|/[^/]+/)$~uD', $relative, $match)
        && !preg_match('~^/(?:content|rss|shop)/~D', $relative)) $id = $match[1];
    else $id = '';
    if ($id !== '') {
        $boards = rb_hn_boards();
        if (isset($boards[$id])) return array('kind'=>'board','id'=>$id,'category'=>$category,'board'=>$boards[$id]);
    }
    return null;
}
function rb_hn_shop_resolve($path, $query, $root) {
    if (!defined('G5_SHOP_URL')) return null;
    $shop = rtrim(rawurldecode(parse_url(G5_SHOP_URL, PHP_URL_PATH)), '/');
    $pretty = $root.'/shop';
    $mobile = (defined('G5_MOBILE_URL') ? rtrim(rawurldecode(parse_url(G5_MOBILE_URL, PHP_URL_PATH)), '/') : $root.'/mobile').'/'.basename($shop);
    $in_shop = false; $route = '';
    foreach (array_unique(array($shop, $pretty, $mobile)) as $prefix) {
        if ($path === $prefix || strpos($path, $prefix.'/') === 0) {
            $in_shop = true; $route = substr($path, strlen($prefix)); break;
        }
    }
    $kind = ''; $id = ''; $filters = array();
    if ($in_shop && $route === '/search.php') {
        $kind = 'shop_search'; $id = 'search';
        $keyword = isset($query['q']) ? trim(strip_tags($query['q'])) : '';
        if (function_exists('get_search_string')) $keyword = get_search_string($keyword);
        if (function_exists('utf8_strcut')) $keyword = utf8_strcut($keyword, 30, '');
        elseif (function_exists('mb_substr')) $keyword = mb_substr($keyword, 0, 30, 'UTF-8');
        if (strlen($keyword) > 120 || !preg_match('//u', $keyword)) return null;
        $filters['q'] = $keyword;
        foreach (array('qname','qexplan','qid','qbasic') as $field) $filters[$field] = !empty($query[$field]);
        $filters['qcaid'] = isset($query['qcaid']) ? preg_replace('/[^a-z0-9]/i', '', $query['qcaid']) : '';
        if (strlen($filters['qcaid']) > 10) return null;
        foreach (array('qfrom','qto') as $field) {
            $value = isset($query[$field]) ? preg_replace('/[^0-9]/', '', $query[$field]) : '';
            if (strlen($value) > 12) return null;
            $filters[$field] = $value;
        }
    } elseif ($in_shop && ($route === '/item.php' || preg_match('~^/(?!list-|type-)([a-zA-Z0-9_-]+)$~D', $route, $match))) {
        if (!empty($query['it_seo_title'])) { $kind = 'item_seo'; $id = $query['it_seo_title']; }
        else { $kind = 'item'; $id = isset($query['it_id']) ? $query['it_id'] : (isset($match[1]) ? $match[1] : ''); }
    } elseif ($in_shop && preg_match('~^/([^/]+)/$~uD', $route, $match)) {
        $kind = 'item_seo'; $id = !empty($query['it_seo_title']) ? $query['it_seo_title'] : $match[1];
    } elseif ($in_shop && ($route === '/list.php' || preg_match('~^/list-([a-z0-9]+)$~iD', $route, $match))) {
        $kind = 'shop'; $id = isset($query['ca_id']) ? $query['ca_id'] : (isset($match[1]) ? $match[1] : '');
    } elseif ($in_shop && ($route === '/listtype.php' || preg_match('~^/type-([1-5])$~D', $route, $match))) {
        $kind = 'shop_type'; $id = isset($query['type']) ? $query['type'] : (isset($match[1]) ? $match[1] : '');
    } elseif (!isset($query['bo_table']) && (!empty($query['it_id']) || !empty($query['it_seo_title']))) {
        $kind = !empty($query['it_seo_title']) ? 'item_seo' : 'item'; $id = $query[$kind === 'item' ? 'it_id' : 'it_seo_title'];
    } elseif (!isset($query['bo_table']) && isset($query['ca_id'])) {
        $kind = 'shop'; $id = $query['ca_id'];
    } elseif ($in_shop && ($route === '' || $route === '/' || $route === '/index.php')) {
        $kind = 'shop_all'; $id = 'all';
    } elseif (!isset($query['bo_table']) && isset($query['type']) && ($in_shop || $path === $root.'/')) {
        $kind = 'shop_type'; $id = $query['type'];
    }
    if ($kind === '') return null;
    if ($kind === 'shop' && !preg_match('/^[a-z0-9]{1,10}$/iD', $id)) return null;
    if ($kind === 'shop_type' && !preg_match('/^[1-5]$/D', $id)) return null;
    if ($kind === 'item' && !preg_match('/^[a-zA-Z0-9_-]{1,20}$/D', $id)) return null;
    if ($kind === 'item_seo') {
        if (function_exists('generate_seo_title')) $id = generate_seo_title($id);
        if ($id === '' || strlen($id) > 800 || !preg_match('//u', $id)) return null;
    }
    return array('kind'=>$kind,'id'=>(string)$id,'category'=>'','filters'=>$filters);
}
function rb_hn_shop_latest($target, $cutoff, $end) {
    global $g5;
    if (empty($g5['g5_shop_item_table']) || empty($g5['g5_shop_category_table'])) return null;
    $name = $g5['g5_shop_item_table']; $table = rb_hn_table($name); $categories = rb_hn_table($g5['g5_shop_category_table']);
    if (!$table || !$categories) return null;
    $id = sql_real_escape_string($target['id']);
    if ($target['kind'] === 'item' || $target['kind'] === 'item_seo') {
        $seo = $target['kind'] === 'item_seo'; $index = $seo ? 'it_seo_title' : 'PRIMARY';
        if (!rb_hn_indexed($name, $index)) return null;
        $column = $seo ? 'it_seo_title' : 'it_id';
        // SEO 주소의 실제 상품과 동일하게 ID 역순 1건을 선택한다.
        $row = sql_fetch("SELECT i.it_time AS dt, i.it_use, c.ca_use FROM {$table} i FORCE INDEX ({$index}) JOIN {$categories} c ON c.ca_id=i.ca_id WHERE i.{$column}='{$id}' ORDER BY i.it_id DESC LIMIT 1", false);
        return !empty($row['it_use']) && !empty($row['ca_use']) && isset($row['dt']) ? $row['dt'] : null;
    }
    if (!rb_hn_indexed($name, 'rb_header_new_item')) return null;
    $where = '';
    if ($target['kind'] === 'shop') {
        $where = " AND EXISTS (SELECT 1 FROM {$categories} selected WHERE selected.ca_id='{$id}' AND selected.ca_use=1) AND (i.ca_id LIKE '{$id}%' OR i.ca_id2 LIKE '{$id}%' OR i.ca_id3 LIKE '{$id}%')";
    } elseif ($target['kind'] === 'shop_type') $where = ' AND i.it_type'.(int)$target['id'].'=1';
    elseif ($target['kind'] === 'shop_search') {
        $filters = $target['filters']; $fields = array();
        foreach (array('qname'=>'it_name','qexplan'=>'it_explan2','qid'=>'it_id','qbasic'=>'it_basic') as $flag=>$field) if ($filters[$flag]) $fields[] = 'i.'.$field;
        if (!$fields) $fields = array('i.it_name','i.it_explan2','i.it_id','i.it_basic');
        foreach (explode(' ', $filters['q']) as $word) if ($word !== '') $where .= " AND CONCAT(".implode(",' ',", $fields).") LIKE '%".sql_real_escape_string($word)."%'";
        if ($filters['qcaid'] !== '') $where .= " AND i.ca_id LIKE '".sql_real_escape_string($filters['qcaid'])."%'";
        if ($filters['qfrom'] !== '' && $filters['qto'] !== '') $where .= ' AND i.it_price BETWEEN '.(float)$filters['qfrom'].' AND '.(float)$filters['qto'];
    }
    $row = sql_fetch("SELECT i.it_time AS dt FROM {$table} i FORCE INDEX (rb_header_new_item) JOIN {$categories} c ON c.ca_id=i.ca_id AND c.ca_use=1 WHERE i.it_use=1 AND i.it_time>='{$cutoff}' AND i.it_time<='{$end}'{$where} ORDER BY i.it_time DESC LIMIT 1", false);
    return isset($row['dt']) ? $row['dt'] : null;
}
function rb_hn_board_allowed($board) {
    global $member, $is_admin, $g5;
    $id = isset($member['mb_id']) ? $member['mb_id'] : '';
    if ($is_admin === 'super' || ($id !== '' && ($id === $board['bo_admin'] || $id === $board['gr_admin']))) return true;
    $level = isset($member['mb_level']) ? (int)$member['mb_level'] : 1;
    if ($level < (int)$board['bo_list_level'] || $level < (int)$board['bo_read_level']) return false;
    if (empty($board['gr_use_access'])) return true;
    if ($id === '') return false;
    static $groups = null;
    if ($groups === null) {
        $groups = array(); $table = rb_hn_table($g5['group_member_table']);
        $result = $table ? sql_query("SELECT gr_id FROM {$table} WHERE mb_id='".sql_real_escape_string($id)."'", false) : false;
        if ($result) while ($row = sql_fetch_array($result)) $groups[$row['gr_id']] = true;
    }
    return !empty($groups[$board['gr_id']]);
}
function rb_header_new_icon($link) {
    global $g5, $member, $is_admin;
    $options = rb_hn_settings();
    if (!$options['use']) return '';
    $target = rb_hn_resolve($link);
    if (!$target) return '';
    if ($target['kind'] === 'board' && !rb_hn_board_allowed($target['board'])) return '';
    if ($target['kind'] === 'qa' && empty($is_admin)) return '';
    $now = defined('G5_SERVER_TIME') ? G5_SERVER_TIME : time();
    $cutoff = date('Y-m-d H:i:s', $now - $options['days'] * 86400);
    $key = 'latest-v2|'.json_encode(array($target['kind'],$target['id'],$target['category'],$options['days'],$target['kind']==='qa' ? 'admin' : '',isset($target['filters']) ? $target['filters'] : array()));
    $tag = $target['kind'] === 'board' ? 'board|'.$target['id'] : ($target['kind'] === 'qa' ? 'qa' : 'shop');
    $latest = rb_hn_cached($key, $tag, function() use ($target, $g5, $cutoff, $now) {
        $category = sql_real_escape_string($target['category']); $end = date('Y-m-d H:i:s', $now);
        if ($target['kind'] === 'board') {
            $name = $g5['board_new_table'];
            if (!rb_hn_indexed($name, 'rb_header_new_board')) return null;
            $table = rb_hn_table($name); $id = sql_real_escape_string($target['id']);
            $join = ''; $where = '';
            if ($category !== '') {
                $write = rb_hn_table($g5['write_prefix'].$target['id']);
                if (!$write) return null;
                $join = " JOIN {$write} w ON w.wr_id=n.wr_id";
                $where = " AND w.wr_is_comment=0 AND w.ca_name='{$category}'";
            }
            $row = sql_fetch("SELECT n.bn_datetime AS dt FROM {$table} n FORCE INDEX (rb_header_new_board){$join} WHERE n.bo_table='{$id}' AND n.wr_id=n.wr_parent AND n.bn_datetime>='{$cutoff}' AND n.bn_datetime<='{$end}'{$where} ORDER BY n.bn_datetime DESC LIMIT 1", false);
        } elseif ($target['kind'] !== 'qa') {
            return rb_hn_shop_latest($target, $cutoff, $end);
        } else {
            $name = $g5['qa_content_table'];
            if (!rb_hn_indexed($name, 'rb_header_new_qa_type')) return null;
            $table = rb_hn_table($name);
            $filter = $category === '' ? '' : " AND q.qa_category='{$category}'";
            // $is_admin이 참인 관리자에게만 신규 문의를 표시한다.
            $row = sql_fetch("SELECT q.qa_datetime AS dt FROM {$table} q FORCE INDEX (rb_header_new_qa_type) WHERE q.qa_type=0 AND q.qa_datetime>='{$cutoff}' AND q.qa_datetime<='{$end}'{$filter} ORDER BY q.qa_datetime DESC LIMIT 1", false);
        }
        return isset($row['dt']) ? $row['dt'] : null;
    });
    return $latest && $latest >= $cutoff && $latest <= date('Y-m-d H:i:s', $now) ? rb_hn_badge($options) : '';
}
function rb_header_menu_name($row) {
    $link = isset($row['ori_me_link']) ? $row['ori_me_link'] : (isset($row['me_link']) ? $row['me_link'] : '');
    return rb_hn_menu_label($row['me_name'], $link);
}
function rb_hn_menu_label($name, $link) {
    $icon = rb_header_new_icon($link);
    if ($icon === '') return $name;
    return '<span class="rb-header-menu-label"><span class="rb-header-menu-text">'.$name.'</span>'.$icon.'</span>';
}
function rb_header_category_name($row) {
    return rb_hn_menu_label(get_text($row['ca_name']), shop_category_url($row['ca_id']));
}
function rb_hn_board_written($board) { if (!empty($board['bo_table'])) rb_hn_invalidate('board|'.$board['bo_table']); }
function rb_hn_board_deleted($write, $board) { rb_hn_board_written($board); }
function rb_hn_qa_written() { rb_hn_invalidate('qa'); }
function rb_hn_item_written() { rb_hn_invalidate('shop'); }
if (function_exists('add_event')) {
    add_event('write_update_after','rb_hn_board_written',20,1);
    add_event('bbs_delete','rb_hn_board_deleted',20,2);
    add_event('bbs_delete_all','rb_hn_board_deleted',20,2);
    add_event('qawrite_update','rb_hn_qa_written',20,0);
    add_event('qa_delete','rb_hn_qa_written',20,0);
    add_event('shop_admin_itemformupdate','rb_hn_item_written',20,0);
}
