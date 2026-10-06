<?php
$sub_menu = '000300';
include_once('./_common.php');
include_once(G5_PATH.'/rb/rb.lib/rb_banner_theme.lib.php');

auth_check_menu($auth, $sub_menu, "r");

//배너 테이블이 있는지 검사한다.
if(!sql_query(" DESCRIBE rb_banner ", false)) {
       $query_cp = sql_query(" CREATE TABLE IF NOT EXISTS `rb_banner` (
        `bn_id` int(11) NOT NULL AUTO_INCREMENT,
        `bn_alt` varchar(255) NOT NULL DEFAULT '',
        `bn_url` varchar(255) NOT NULL DEFAULT '',
        `bn_device` varchar(10) NOT NULL DEFAULT '',
        `bn_position` varchar(255) NOT NULL DEFAULT '',
        `bn_border` tinyint(4) NOT NULL DEFAULT '0',
        `bn_radius` tinyint(4) NOT NULL DEFAULT '0',
        `bn_ad_ico` tinyint(4) NOT NULL DEFAULT '0',
        `bn_new_win` tinyint(4) NOT NULL DEFAULT '0',
        `bn_begin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_hit` int(11) NOT NULL DEFAULT '0',
        `bn_order` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`bn_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", true);
      sql_query(" ALTER TABLE `rb_banner` ADD PRIMARY KEY (`bn_id`) ", false);
      sql_query(" ALTER TABLE `rb_banner` MODIFY `bn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;COMMIT ", false);
}

$g5['title'] = '배너관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

// 새 배너는 소속 기록을, 이전 공용 배너는 모듈의 사용 테마를 확인한다.
$rb_banner_themes = rb_banner_theme_usage(true);
$rb_banner_registry = rb_banner_theme_registry();
$rb_banner_theme_names = array();
foreach($rb_banner_themes as $rb_banner_usage_themes)
    foreach($rb_banner_usage_themes as $rb_banner_usage_theme=>$unused) $rb_banner_theme_names[$rb_banner_usage_theme]=$rb_banner_usage_theme;
$rb_banner_theme_dirs = function_exists('get_theme_dir') ? get_theme_dir() : array();
foreach ($rb_banner_theme_names as $rb_banner_theme_key => $rb_banner_theme_label) {
    if (function_exists('get_theme_info') && in_array((string)$rb_banner_theme_key, $rb_banner_theme_dirs, true)) {
        $rb_banner_theme_info = get_theme_info($rb_banner_theme_key);
        if (!empty($rb_banner_theme_info['theme_name'])) {
            $rb_banner_theme_name = trim(strip_tags($rb_banner_theme_info['theme_name']));
            if ($rb_banner_theme_name !== '' && $rb_banner_theme_name !== (string)$rb_banner_theme_key)
                $rb_banner_theme_names[$rb_banner_theme_key] = $rb_banner_theme_name.' · '.$rb_banner_theme_key;
        }
    }
}
natcasesort($rb_banner_theme_names);
$rb_banner_theme_filter = isset($_GET['theme_filter']) && is_string($_GET['theme_filter']) ? $_GET['theme_filter'] : '';
if ($rb_banner_theme_filter !== '__rb_unlinked__' && !isset($rb_banner_theme_names[$rb_banner_theme_filter])) $rb_banner_theme_filter = '';
$sql_common = " from rb_banner ";
if ($rb_banner_theme_filter !== '') {
    $rb_banner_filter_ids = array();
    foreach ($rb_banner_themes as $rb_banner_usage_id => $rb_banner_usage_themes) {
        if ($rb_banner_theme_filter === '__rb_unlinked__' || isset($rb_banner_usage_themes[$rb_banner_theme_filter]))
            $rb_banner_filter_ids[] = (int)$rb_banner_usage_id;
    }
    if ($rb_banner_filter_ids) {
        $sql_common .= ' where bn_id '.($rb_banner_theme_filter === '__rb_unlinked__' ? 'NOT IN' : 'IN').' ('.implode(',', $rb_banner_filter_ids).') ';
    } elseif ($rb_banner_theme_filter !== '__rb_unlinked__') {
        $sql_common .= ' where 1 = 0 ';
    }
}

// 테이블의 전체 레코드수만 얻음
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt"> 등록된 배너 </span><span class="ov_num"> <?php echo $total_count; ?>개</span></span>
</div>

<div class="btn_fixed_top">
    <a href="./banner_form.php" class="btn_01 btn">배너추가</a>
</div>

<form method="get" class="local_sch01 local_sch">
    <label for="rb_banner_theme_filter" class="sound_only">사용 테마</label>
    <select name="theme_filter" id="rb_banner_theme_filter" onchange="this.form.submit();">
        <option value="">전체 테마</option>
        <?php foreach ($rb_banner_theme_names as $rb_banner_theme_key => $rb_banner_theme_label) { ?>
        <option value="<?php echo htmlspecialchars((string)$rb_banner_theme_key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $rb_banner_theme_filter === (string)$rb_banner_theme_key ? ' selected' : ''; ?>><?php echo htmlspecialchars($rb_banner_theme_label, ENT_QUOTES, 'UTF-8'); ?></option>
        <?php } ?>
        <option value="__rb_unlinked__"<?php echo $rb_banner_theme_filter === '__rb_unlinked__' ? ' selected' : ''; ?>>공용 / 미연결</option>
    </select>
    <noscript><input type="submit" value="조회" class="btn_submit"></noscript>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col" id="th_id">ID</th>
        <th scope="col" id="th_dvc">배너 Url</th>
		<th scope="col" id="th_inf">배너설명</th>
        <th scope="col" id="th_loc">출력그룹</th>
        <th scope="col" id="th_dev">출력기기</th>
        <th scope="col" id="th_st">시작일시</th>
        <th scope="col" id="th_end">종료일시</th>
        <th scope="col" id="th_odr">출력순서</th>
        <th scope="col" id="th_hit">클릭</th>
        <th scope="col" id="th_mng">관리</th>
    </tr>

    </thead>
    <tbody>
    <?php
    $sql = " select * {$sql_common}
          order by bn_id desc
          limit $from_record, $rows  ";
    $result = sql_query($sql);
    for ($i=0; $row=sql_fetch_array($result); $i++) {

        $bn_border  = $row['bn_border'];
        $bn_radius  = $row['bn_radius'];
        $bn_ad_ico  = $row['bn_ad_ico'];
        // 새창 띄우기인지
        $bn_new_win = ($row['bn_new_win']) ? 'target="_blank"' : '';

        // 실제 배너 출력과 같은 순서로 확인한다. 행마다 초기화해 이전 URL이 남지 않게 한다.
        $bn_img = '';
        $bn_image_id = (int)$row['bn_id'];
        foreach (array('rb_display', 'banners') as $bn_image_folder) {
            if ($bn_image_id > 0 && is_file(G5_DATA_PATH.'/'.$bn_image_folder.'/'.$bn_image_id)) {
                $bn_img = G5_URL.'/rb/rb.mod/display/displayimg.php?did='.$bn_image_id;
                break;
            }
        }

        switch($row['bn_device']) {
            case 'pc':
                $bn_device = 'PC';
                break;
            case 'mobile':
                $bn_device = '모바일';
                break;
            default:
                $bn_device = 'PC와 모바일';
                break;
        }

        $bn_begin_time = substr($row['bn_begin_time'], 0, 19);
        $bn_end_time   = substr($row['bn_end_time'], 0, 19);

        $bg = 'bg'.($i%2);
    ?>

    <tr class="<?php echo $bg; ?>">
        <td headers="th_id" class="td_num"><?php echo $row['bn_id']; ?></td>
        <td headers="th_dvc"><a href="<?php echo !empty($bn_img) ? $bn_img : '#'; ?>" target="_blank"><?php echo !empty($bn_img) ? $bn_img : '이미지 없음'; ?></a></td>
        <td headers="th_inf">
            <?php echo !empty($row['bn_alt']) ? $row['bn_alt'] : '-'; ?><br>
            사용 테마:
            <?php
            $rb_banner_row_themes = isset($rb_banner_themes[(int)$row['bn_id']]) ? $rb_banner_themes[(int)$row['bn_id']] : array();
            if (!$rb_banner_row_themes) echo isset($rb_banner_registry['owners'][(int)$row['bn_id']]) && $rb_banner_registry['owners'][(int)$row['bn_id']]==='*'?'공용 (모든 테마)':'미연결';
            else {
                $rb_banner_row_labels = array();
                foreach ($rb_banner_theme_names as $rb_banner_theme_key => $rb_banner_theme_label)
                    if (isset($rb_banner_row_themes[$rb_banner_theme_key])) $rb_banner_row_labels[] = htmlspecialchars($rb_banner_theme_label, ENT_QUOTES, 'UTF-8');
                echo implode('<br>', $rb_banner_row_labels);
            }
            ?>
        </td>
        <td headers="th_loc">
		<?php if($row['bn_position'] == "") {
			echo "-";
		} else {
			echo htmlspecialchars($row['bn_position'],ENT_QUOTES,'UTF-8');
		}
		?>
		</td>
        <td headers="th_loc">
		<?php echo $bn_device; ?>
		</td>
        <td headers="th_st" class="td_datetime"><?php echo $bn_begin_time; ?></td>
        <td headers="th_end" class="td_datetime"><?php echo $bn_end_time; ?></td>
        <td headers="th_odr" class="td_num"><?php echo $row['bn_order']; ?></td>
        <td headers="th_hit" class="td_num"><?php echo number_format($row['bn_hit']); ?></td>
        <td headers="th_mng" class="td_mng td_mns_m">
            <a href="./banner_form.php?w=u&amp;bn_id=<?php echo $row['bn_id']; ?>" class="btn btn_03">수정</a>
            <a href="./banner_form_update.php?w=d&amp;bn_id=<?php echo $row['bn_id']; ?>" onclick="return delete_confirm(this);" class="btn btn_02">삭제</a>
        </td>
    </tr>

    <?php
    }
    if ($i == 0) {
    echo '<tr><td colspan="10" class="empty_table">자료가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>

</div>

<?php
$rb_banner_page_query = $rb_banner_theme_filter !== '' ? 'theme_filter='.rawurlencode($rb_banner_theme_filter).'&amp;' : '';
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$rb_banner_page_query.'page=');
?>


<div class="local_desc01 local_desc">
    <p>
        배너를 등록하시면 모듈설정에서 추가하실 수 있습니다.

    </p>
</div>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
