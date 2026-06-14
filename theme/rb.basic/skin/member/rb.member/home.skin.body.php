<?php if (!defined('_GNUBOARD_')) exit; ?>


<?php // ========== 홈 탭 ========== ?>
<?php if ($ca == '') { ?>

<div>
    <ul class="cont_info_wrap">
        <li class="cont_info_wrap_l">
            <dd>닉네임</dd>
            <dd><?php echo $mb['mb_nick']; ?></dd>
        </li>
        <li class="cont_info_wrap_r">
            <dd>회원레벨</dd>
            <dd><?php echo $mb['mb_level']; ?>레벨</dd>
        </li>
        <div class="cb"></div>
    </ul>
    <ul class="cont_info_wrap">
        <li class="cont_info_wrap_l">
            <dd>포인트</dd>
            <dd>
               <?php if($member['mb_id'] == $mb['mb_id']) { ?><a href="<?php echo G5_BBS_URL ?>/point.php" target="_blank" class="win_point"><?php } ?>
                    <?php echo number_format($mb['mb_point']) ?>P
                <?php if($member['mb_id'] == $mb['mb_id']) { ?></a><?php } ?>
            </dd>
        </li>
        <li class="cont_info_wrap_r">
            <dd>가입일</dd>
            <dd><?php echo ($member['mb_level'] >= $mb['mb_level']) ? substr($mb['mb_datetime'], 0, 10) . ' (+' . number_format($mb_reg_after) . '일)' : '알 수 없음'; ?></dd>
        </li>
        <div class="cb"></div>
    </ul>
    <ul class="cont_info_wrap">
        <li class="cont_info_wrap_l">
            <dd>운영채널</dd>
            <dd><?php if ($mb_homepage) { ?><a href="<?php echo $mb_homepage; ?>" target="_blank"><?php echo $mb_homepage; ?></a><?php } else { ?>-<?php } ?></dd>
        </li>
        <li class="cont_info_wrap_r">
            <dd>최종접속</dd>
            <dd><?php echo ($member['mb_level'] >= $mb['mb_level']) ? $mb['mb_today_login'] : '알 수 없음'; ?></dd>
        </li>
        <div class="cb"></div>
    </ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
function numberWithCommas(x) {
    try {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    } catch (e) {
        return x;
    }
}

</script>

<?php if(isset($rb_builder['bu_mini_use1']) && $rb_builder['bu_mini_use1'] == 1) { ?>
<?php
// 최근 7일 게시물/댓글 집계
$dates = [];
for ($i = 6; $i >= 0; $i--) { $dates[] = date('Y-m-d', strtotime("-{$i} days")); }
$post_counts    = array_fill(0, count($dates), 0);
$comment_counts = array_fill(0, count($dates), 0);
$bn_table = isset($g5['board_new_table']) ? $g5['board_new_table'] : G5_TABLE_PREFIX . 'board_new';
$from_dt  = $dates[0] . ' 00:00:00';
$sql = "SELECT DATE(bn_datetime) AS ymd,
        SUM(CASE WHEN wr_id = wr_parent THEN 1 ELSE 0 END) AS posts,
        SUM(CASE WHEN wr_id <> wr_parent THEN 1 ELSE 0 END) AS comments
        FROM {$bn_table}
        WHERE bn_datetime >= '{$from_dt}' AND mb_id = '{$mb['mb_id']}'
        GROUP BY DATE(bn_datetime) ORDER BY ymd";
$res = sql_query($sql);
$idx_map = array_flip($dates);
while ($row = sql_fetch_array($res)) {
    if (isset($idx_map[$row['ymd']])) {
        $post_counts[$idx_map[$row['ymd']]]    = (int)$row['posts'];
        $comment_counts[$idx_map[$row['ymd']]] = (int)$row['comments'];
    }
}
?>


<div class="rb_chart">
    <li class="bbs_main_wrap_tit_l">
        <h2 class="font-B font-18">게시물 현황 (7일)</h2>
    </li>
    <li class="bbs_main_wrap_tit_r mt-20">
        <button type="button" class="more_btn" onclick="location.href='<?php echo G5_BBS_URL; ?>/search.php?stx=<?php echo $mb['mb_id']; ?>&sfl=mb_id&sop=and';">더보기</button>
    </li>
    <div class="cb"></div>
    <div id="rb-chart1" class="font-R"></div>
</div>
<div class="cb"></div>
<script>
    var categories = <?php echo json_encode($dates, JSON_UNESCAPED_UNICODE); ?>;
    var postSeries = <?php echo json_encode(array_map('intval', $post_counts)); ?>;
    var cmtSeries = <?php echo json_encode(array_map('intval', $comment_counts)); ?>;
    var overallMax = Math.max(1, postSeries.length ? Math.max.apply(null, postSeries) : 0, cmtSeries.length ? Math.max.apply(null, cmtSeries) : 0);
    var mainColor = '<?php echo isset($rb_config['co_color']) ? $rb_config['co_color'] : '#7b5cff'; ?>';
    new ApexCharts(document.querySelector('#rb-chart1'), {
        chart: {
            type: 'line',
            height: 260,
            width: '100%',
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            selection: {
                enabled: false
            }
        },
        series: [{
            name: '글',
            data: postSeries
        }, {
            name: '댓글',
            data: cmtSeries
        }],
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    fontSize: '11px',
                    colors: '#000'
                },
                formatter: function(val) {
                    return String(val).replace(/^\d{4}-/, '');
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            min: 0,
            max: overallMax,
            tickAmount: 6,
            labels: {
                show: false
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        stroke: {
            width: [2, 2],
            curve: 'smooth'
        },
        markers: {
            size: 0,
            hover: {
                size: 5
            }
        },
        dataLabels: {
            enabled: true,
            background: {
                enabled: false
            },
            offsetY: -6,
            style: {
                fontSize: '11px',
                colors: ['#000']
            },
            formatter: function(val) {
                return val ? numberWithCommas(val) + '건' : '';
            }
        },
        colors: [mainColor, '#77d500'],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val) {
                    return numberWithCommas(val) + '건';
                }
            },
            style: {
                fontSize: '11px'
            }
        },
        grid: {
            show: true,
            borderColor: '#e5e5ef',
            strokeDashArray: 3,
            yaxis: {
                lines: {
                    show: true
                }
            },
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        legend: {
            show: true,
            fontSize: '11px'
        }
    }).render();
</script>
<?php } ?>

<?php if(isset($rb_builder['bu_mini_use2']) && $rb_builder['bu_mini_use2'] == 1) { ?>
<?php
// 최근 7일 포인트 획득 집계
$pt_dates = [];
$pt_values = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $pt_dates[] = $d;
    $row_pt = sql_fetch("SELECT SUM(CASE WHEN po_point >= 0 THEN IFNULL(po_point,0) ELSE 0 END) AS total_points
        FROM {$g5['point_table']}
        WHERE mb_id = '" . sql_real_escape_string($mb['mb_id']) . "'
          AND DATE(po_datetime) = '{$d}'");
    $pt_values[] = isset($row_pt['total_points']) ? (int)$row_pt['total_points'] : 0;
}
?>
<div class="rb_chart">
    <li class="bbs_main_wrap_tit_l">
        <h2 class="font-B font-18">포인트 획득 (7일)</h2>
    </li>
    <div class="cb"></div>
    <div id="rb-chart2" class="font-R"></div>
</div>
<div class="cb"></div>
<script>
    // 수정
    var weekCats = <?php echo json_encode(array_map(function($d){ return substr($d,5); }, $pt_dates), JSON_UNESCAPED_UNICODE); ?>;
    var weekVals = <?php echo json_encode(array_map('intval', $pt_values)); ?>;
    var mainColor2 = '<?php echo isset($rb_config['co_color']) ? $rb_config['co_color'] : '#7b5cff'; ?>';
    var STEP = 1000;
    var rawMax = weekVals.length ? Math.max.apply(null, weekVals) : 0;
    var yMax = Math.max(2000, Math.ceil(rawMax / STEP) * STEP);
    new ApexCharts(document.querySelector('#rb-chart2'), {
        chart: {
            type: 'bar',
            height: 260,
            width: '100%',
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            },
            selection: {
                enabled: false
            }
        },
        series: [{
            name: '포인트',
            data: weekVals
        }],
        xaxis: {
            categories: weekCats,
            labels: {
                style: {
                    fontSize: '11px',
                    colors: '#000'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            min: 0,
            max: yMax,
            tickAmount: Math.max(1, Math.ceil(yMax / STEP)),
            decimalsInFloat: 0,
            labels: {
                style: {
                    fontSize: '11px',
                    colors: '#000'
                },
                formatter: function(val) {
                    return numberWithCommas(val);
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 6,
                borderRadiusApplication: 'end',
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '11px',
                colors: ['#000']
            },
            formatter: function(val) {
                return val ? numberWithCommas(val) + 'P' : '';
            }
        },
        colors: [mainColor2],
        tooltip: {
            y: {
                formatter: function(val) {
                    return numberWithCommas(val) + 'P';
                }
            },
            style: {
                fontSize: '12px'
            }
        },
        grid: {
            show: true,
            borderColor: '#e5e5ef',
            strokeDashArray: 3,
            yaxis: {
                lines: {
                    show: true
                }
            },
            xaxis: {
                lines: {
                    show: false
                }
            }
        },
        legend: {
            show: false
        }
    }).render();
</script>
<?php } ?>

<?php if(isset($rb_builder['bu_mini_use3']) && $rb_builder['bu_mini_use3'] == 1) { ?>
<div>
    <ul class="cont_info_wrap_mmt">
        <?php
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $sql_commons = " from {$g5['board_new_table']} a, {$g5['board_table']} b
                         where a.bo_table = b.bo_table and a.wr_id = a.wr_parent
                           and a.mb_id = '{$mb['mb_id']}'
                           and a.bn_datetime >= '{$seven_days_ago}' order by a.bn_id desc ";
        $sqls = " select a.*, b.bo_subject, b.bo_mobile_subject {$sql_commons} limit 5 ";
        $results = sql_query($sqls);
        ?>
        <div class="bbs_main">
            <ul class="bbs_main_wrap_thumb_con">
                <div class="">
                    <ul class="">
                        <?php for ($i = 0; $rows = sql_fetch_array($results); $i++) {
                            $tmp_write_table = $g5['write_prefix'] . $rows['bo_table'];
                            $row2    = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$rows['wr_id']}' ");
                            $hrefs   = get_pretty_url($rows['bo_table'], $row2['wr_id']);
                            $thumb   = get_list_thumbnail($rows['bo_table'], $row2['wr_id'], $thumb_width, $thumb_height, false, true);
                            if ($thumb['src']) {
                                $img = $thumb['src'];
                            } else {
                                $img = G5_THEME_URL . '/rb.img/no_image.png';
                                $thumb['alt'] = '이미지가 없습니다.';
                            }
                            $img_content = '<img src="' . $img . '" alt="' . $thumb['alt'] . '" class="skin_list_image">';
                            $sec_txt     = '<span style="opacity:0.6">작성자 및 관리자 외 열람할 수 없습니다.</span>';
                            $wr_content  = get_text(preg_replace('/&nbsp;/', '', preg_replace('/<(.*?)>/', '', $row2['wr_content'])));
                        ?>
                        <dd class="mb-20" onclick="location.href='<?php echo $hrefs; ?>';">
                            <div>
                                <?php if ($thumb['src']) { ?>
                                <ul class="bbs_main_wrap_con_ul1"><a href="<?php echo $hrefs; ?>"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a></ul>
                                <?php } ?>
                                <ul class="bbs_main_wrap_con_ul2" <?php if (!$thumb['src']) echo 'style="padding-right:0px;"'; ?>>
                                    <li class="bbs_main_wrap_con_subj cut"><a href="<?php echo $hrefs; ?>"><?php echo $row2['wr_subject']; ?></a></li>
                                    <?php if (strstr($row2['wr_option'], 'secret')) { ?>
                                    <li class="bbs_main_wrap_con_cont"><?php echo $sec_txt; ?></li>
                                    <?php } else { ?>
                                    <li class="bbs_main_wrap_con_cont cut2"><a href="<?php echo $hrefs; ?>"><?php echo $wr_content; ?></a></li>
                                    <?php } ?>
                                    <li class="bbs_main_wrap_con_info">
                                        <span class="prof_tiny_name font-B"><?php echo $row2['wr_name']; ?></span>
                                        <?php echo passing_time($row2['wr_datetime']); ?><br>
                                        <?php if ($row2['ca_name']) { echo $rows['bo_subject'] . ' [' . $row2['ca_name'] . ']'; } else { echo $rows['bo_subject']; } ?>
                                        댓글 <?php echo number_format($row2['wr_comment']); ?>
                                        조회 <?php echo number_format($row2['wr_hit']); ?>
                                    </li>
                                </ul>
                                <div class="cb"></div>
                            </div>
                        </dd>
                        <?php } ?>
                        <?php if ($i == 0) { ?><dd class="no_data" style="width:100% !important;">등록한 게시물이 없습니다.</dd><?php } ?>
                    </ul>
                </div>
            </ul>
        </div>
    </ul>
</div>
<?php } ?>

<?php } // end ca == '' ?>


<?php // ========== 새글 / 홈 게시물 목록 ========== ?>
<?php if ($ca == 'bbs') { ?>
<div>
    <ul class="cont_info_wrap_mmt">
        <?php
        $sql_commons = " from {$g5['board_new_table']} a, {$g5['board_table']} b
                         where a.bo_table = b.bo_table and a.wr_id = a.wr_parent
                           and a.bo_table NOT IN ('update','test','request')
                           and a.mb_id = '{$mb['mb_id']}' order by a.bn_id desc ";


            $rpg_row         = sql_fetch(" select count(*) as cnt {$sql_commons} ");
            $rpg_total_count = $rpg_row['cnt'];
            $rpg_rows        = 10;
            $rpg_total_page  = ceil($rpg_total_count / $rpg_rows);
            if ($page < 1) $page = 1;
            $from_record     = ($page - 1) * $rpg_rows;
            $rpg_write_pages = get_paging(
                G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
                $page, $rpg_total_page,
                "?mb_id={$mb_id}&amp;ca={$ca}&amp;page="
            );
            $sqls = " select a.*, b.bo_subject, b.bo_mobile_subject {$sql_commons} limit {$from_record}, {$rpg_rows} ";

        $results = sql_query($sqls);
        ?>
        <div class="bbs_main">
            <ul class="bbs_main_wrap_thumb_con">
                <div class="">
                    <ul class="">
                        <?php for ($i = 0; $rows = sql_fetch_array($results); $i++) {
                            $tmp_write_table = $g5['write_prefix'] . $rows['bo_table'];
                            $row2    = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$rows['wr_id']}' ");
                            $hrefs   = get_pretty_url($rows['bo_table'], $row2['wr_id']);
                            $thumb   = get_list_thumbnail($rows['bo_table'], $row2['wr_id'], $thumb_width, $thumb_height, false, true);
                            if ($thumb['src']) {
                                $img = $thumb['src'];
                            } else {
                                $img = G5_THEME_URL . '/rb.img/no_image.png';
                                $thumb['alt'] = '이미지가 없습니다.';
                            }
                            $img_content = '<img src="' . $img . '" alt="' . $thumb['alt'] . '" class="skin_list_image">';
                            $sec_txt     = '<span style="opacity:0.6">작성자 및 관리자 외 열람할 수 없습니다.</span>';
                            $wr_content  = get_text(preg_replace('/&nbsp;/', '', preg_replace('/<(.*?)>/', '', $row2['wr_content'])));
                        ?>
                        <dd class="mb-20" onclick="location.href='<?php echo $hrefs; ?>';">
                            <div>
                                <?php if ($thumb['src']) { ?>
                                <ul class="bbs_main_wrap_con_ul1"><a href="<?php echo $hrefs; ?>"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a></ul>
                                <?php } ?>
                                <ul class="bbs_main_wrap_con_ul2" <?php if (!$thumb['src']) echo 'style="padding-right:0px;"'; ?>>
                                    <li class="bbs_main_wrap_con_subj cut"><a href="<?php echo $hrefs; ?>"><?php echo $row2['wr_subject']; ?></a></li>
                                    <?php if (strstr($row2['wr_option'], 'secret')) { ?>
                                    <li class="bbs_main_wrap_con_cont"><?php echo $sec_txt; ?></li>
                                    <?php } else { ?>
                                    <li class="bbs_main_wrap_con_cont cut2"><a href="<?php echo $hrefs; ?>"><?php echo $wr_content; ?></a></li>
                                    <?php } ?>
                                    <li class="bbs_main_wrap_con_info">
                                        <span class="prof_tiny_name font-B"><?php echo $row2['wr_name']; ?></span>
                                        <?php echo passing_time($row2['wr_datetime']); ?><br>
                                        <?php if ($row2['ca_name']) { echo $rows['bo_subject'] . ' [' . $row2['ca_name'] . ']'; } else { echo $rows['bo_subject']; } ?>
                                        댓글 <?php echo number_format($row2['wr_comment']); ?>
                                        조회 <?php echo number_format($row2['wr_hit']); ?>
                                    </li>
                                </ul>
                                <div class="cb"></div>
                            </div>
                        </dd>
                        <?php } ?>
                        <?php if ($i == 0) { ?><dd class="no_data" style="width:100% !important;">등록한 게시물이 없습니다.</dd><?php } ?>
                    </ul>
                </div>

            </ul>
        </div>
    </ul>
    <?php echo $rpg_write_pages; ?>
</div>
<?php } // end ca == bbs || '' ?>


<?php // ========== 새댓글 목록 ========== ?>
<?php if ($ca == 'comment') { ?>
<div>
    <ul class="cont_info_wrap_mmt">
        <?php
        $sql_commons = " from {$g5['board_new_table']} a, {$g5['board_table']} b
                         where a.bo_table = b.bo_table and a.wr_id != a.wr_parent
                           and a.bo_table NOT IN ('update','test','request')
                           and a.mb_id = '{$mb['mb_id']}' order by a.bn_id desc ";

        $rpg_row         = sql_fetch(" select count(*) as cnt {$sql_commons} ");
        $rpg_total_count = $rpg_row['cnt'];
        $rpg_rows        = 10;
        $rpg_total_page  = ceil($rpg_total_count / $rpg_rows);
        if ($page < 1) $page = 1;
        $from_record     = ($page - 1) * $rpg_rows;
        $rpg_write_pages = get_paging(
            G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
            $page, $rpg_total_page,
            "?mb_id={$mb_id}&amp;ca={$ca}&amp;page="
        );
        $sqls = " select a.*, b.bo_subject, b.bo_mobile_subject {$sql_commons} limit {$from_record}, {$rpg_rows} ";
        $results = sql_query($sqls);
        ?>
        <div class="bbs_main">
            <ul class="bbs_main_wrap_thumb_con">
                <?php for ($i = 0; $rows = sql_fetch_array($results); $i++) {
                    $tmp_write_table = $g5['write_prefix'] . $rows['bo_table'];
                    // 댓글 본문
                    $row_cmt  = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$rows['wr_id']}' ");
                    // 원글
                    $row_orig = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$rows['wr_parent']}' ");
                    $hrefs    = G5_BBS_URL . '/board.php?bo_table=' . $rows['bo_table'] . '&wr_id=' . $rows['wr_parent'] . '#c_' . $rows['wr_id'];
                    $cmt_text  = get_text(preg_replace('/&nbsp;/', '', preg_replace('/<(.*?)>/', '', $row_cmt['wr_content'])));
                    $is_secret = strstr($row_cmt['wr_option'], 'secret') || strstr($row_orig['wr_option'], 'secret');
                ?>
                <dd class="rb_comment_item" onclick="location.href='<?php echo $hrefs; ?>';">
                    <div class="rb_comment_orig cut">
                        <span class="rb_comment_board"><?php echo $rows['bo_subject']; ?></span>
                        <a href="<?php echo $hrefs; ?>"><?php echo $row_orig['wr_subject']; ?></a>
                    </div>
                    <div class="rb_comment_body cut2">
                        <?php if ($is_secret) { ?>
                        <span style="opacity:0.5">작성자 및 관리자 외 열람할 수 없습니다.</span>
                        <?php } else { ?>
                        <?php echo $cmt_text; ?>
                        <?php } ?>
                    </div>
                    <div class="rb_comment_info">
                        <?php echo passing_time($row_cmt['wr_datetime']); ?>
                    </div>
                </dd>
                <?php } ?>
                <?php if ($i == 0) { ?><dd class="no_data" style="width:100%;">등록한 댓글이 없습니다.</dd><?php } ?>
            </ul>
        </div>
    </ul>
    <?php echo $rpg_write_pages; ?>
</div>


<?php } // end ca == comment ?>

<?php // ========== 구독 탭 ========== ?>
<?php
if (isset($sb['sb_use']) && $sb['sb_use'] == 1) {
    if ($mb['mb_id'] == $member['mb_id'] || $is_admin) {
        include_once(G5_PATH . '/rb/rb.mod/subscribe/subscribe_table.skin.php');
    } elseif ($ca == 'fn' || $ca == 'fw') {
        alert('올바른 방법으로 이용해주세요.');
    }
}
?>
