<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css?ver='.G5_TIME_YMDHIS.'">', 0);
$thumb_width = 120;
$thumb_height = 120;

$ca = isset($_GET['ca']) ? $_GET['ca'] : '';
?>

<style>
    #container_title {display: none;}
</style>


<div class="rb_prof rb_prof_new">

    <ul class="rb_prof_info">
        <div class="rb_prof_info_img">
        <span id="prof_image_ch"><?php echo get_member_profile_img($mb['mb_id']); ?></span>
        <?php if($mb['mb_id'] == $member['mb_id']) { ?>
        <button type="button" id="prof_ch_btn">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.58597 1.1C9.93996 0.746476 10.4136 0.538456 10.9134 0.516981C11.4132 0.495507 11.903 0.662139 12.286 0.984002L12.414 1.101L14.314 3H17C17.5044 3.00009 17.9901 3.19077 18.3599 3.53384C18.7297 3.8769 18.9561 4.34702 18.994 4.85L19 5V7.686L20.9 9.586C21.2538 9.94004 21.462 10.4139 21.4834 10.9139C21.5049 11.414 21.3381 11.9039 21.016 12.287L20.899 12.414L18.999 14.314V17C18.9991 17.5046 18.8086 17.9906 18.4655 18.3605C18.1224 18.7305 17.6521 18.9572 17.149 18.995L17 19H14.315L12.415 20.9C12.0609 21.2538 11.5871 21.462 11.087 21.4835C10.587 21.505 10.097 21.3382 9.71397 21.016L9.58697 20.9L7.68697 19H4.99997C4.49539 19.0002 4.0094 18.8096 3.63942 18.4665C3.26944 18.1234 3.04281 17.6532 3.00497 17.15L2.99997 17V14.314L1.09997 12.414C0.746165 12.06 0.537968 11.5861 0.516492 11.0861C0.495016 10.586 0.661821 10.0961 0.98397 9.713L1.09997 9.586L2.99997 7.686V5C3.00006 4.4956 3.19074 4.00986 3.53381 3.64009C3.87687 3.27032 4.34699 3.04383 4.84997 3.006L4.99997 3H7.68597L9.58597 1.1ZM11 8C10.2043 8 9.44126 8.31607 8.87865 8.87868C8.31604 9.44129 7.99997 10.2044 7.99997 11C7.99997 11.7957 8.31604 12.5587 8.87865 13.1213C9.44126 13.6839 10.2043 14 11 14C11.7956 14 12.5587 13.6839 13.1213 13.1213C13.6839 12.5587 14 11.7957 14 11C14 10.2044 13.6839 9.44129 13.1213 8.87868C12.5587 8.31607 11.7956 8 11 8Z" fill="#09244B"/>
            </svg>
        </button>
        <?php } ?>
        <input type="file" id="prof_image_ch_input" style="display:none" accept="image/*" style="display:none;" readonly>

        <script>
            $(document).ready(function(){
                $('#prof_ch_btn').on('click', function() {
                    $('#prof_image_ch_input').click();
                });

                $('#prof_image_ch_input').on('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const img = new Image();
                        img.onload = function() {
                                const formData = new FormData();
                                formData.append('profile_image', file);

                                $.ajax({
                                    url: '<?php echo G5_URL ?>/rb/rb.lib/ajax.upload_prof_image.php',
                                    type: 'POST',
                                    data: formData,
                                    contentType: false,
                                    processData: false,
                                    success: function(response) {
                                        const data = JSON.parse(response);
                                        if (data.success) {
                                            $('#prof_image_ch').html('<img src="' + data.image_url + '" alt="profile_image">');
                                            //location.reload();
                                        } else {
                                            alert(data.message);
                                        }
                                    }
                                });

                        }
                        img.src = URL.createObjectURL(file);
                    }
                });
            });
        </script>
        </div>

        <div class="rb_prof_info_info">
            <li class="rb_prof_info_nick font-B"><?php echo $mb['mb_nick'] ?><span><?php echo $mb['mb_level'] ?> Lv</span></li>

            <li class="rb_prof_info_txt">
            <span>게시물 <?php echo number_format(wr_cnt($mb['mb_id'], "w")); ?>개</span>
            <span>댓글 <?php echo number_format(wr_cnt($mb['mb_id'], "c")); ?>개</span>
            <?php if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { // 구독 사용시 ?><?php echo sb_cnt($mb['mb_id']) ?><?php } ?>
            </li>

        </div>



        <div class="cb"></div>


        <?php if(isset($mb['mb_profile']) && $mb['mb_profile']) { ?>
        <li class="rb_prof_info_txt"><?php echo $mb['mb_profile'] ?></li>
        <?php } ?>


    </ul>


    <ul class="rb_prof_btn">
        <div id="bo_v_share">
        	<ul class="copy_urls">
                <li>


                    <a class="fl_btns" id="data-copy" title="공유링크 복사" alt="공유링크 복사" href="javascript:void(0);">
                        <img src="<?php echo G5_THEME_URL ?>/rb.img/icon/ico_link.svg">
                    </a>

                    <?php if($mb['mb_id'] == $member['mb_id']) { ?>
                        <a class="fl_btns fl_btns_txt fl_btns_txt_mgl" title="정보수정" alt="정보수정" href="<?php echo G5_BBS_URL ?>/member_confirm.php?url=<?php echo G5_BBS_URL ?>/register_form.php">정보수정</a>
                    <?php } else { ?>
                        <a class="fl_btns" title="쪽지보내기" alt="쪽지보내기" href="<?php echo G5_BBS_URL ?>/memo_form.php?me_recv_mb_id=<?php echo $mb['mb_id'] ?>" onclick="win_memo(this.href); return false;">
                            <img src="<?php echo G5_THEME_URL ?>/rb.img/icon/ico_msg.svg">
                        </a>

                        <?php if (isset($chat_set['ch_use']) && $chat_set['ch_use'] == 1) { ?>
                        <a class="fl_btns" title="채팅하기" alt="채팅하기" href="<?php echo G5_URL ?>/rb/chat_form.php?me_recv_mb_id=<?php echo $mb['mb_id'] ?>" onclick="win_chat(this.href); return false;">
                            <img src="<?php echo G5_THEME_URL ?>/rb.img/icon/ico_chat.svg">
                        </a>
                        <?php } ?>


                    <?php
                        if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { // 구독 사용시
                            $sb_mb_id = $mb['mb_id'];
                            include_once(G5_PATH.'/rb/rb.mod/subscribe/subscribe_my.skin.php');
                        }
                    ?>

                    <?php } ?>

        	    </li>
        	    <?php
                $currents_url = G5_URL."/rb/home.php?mb_id=".$mb['mb_id'];
                ?>
        	    <input type="hidden" id="data-area" class="data-area" value="<?php echo $currents_url ?>">
        	    <script>
        	        $(document).ready(function() {

        	            $('#data-copy').click(function() {
        	                $('#data-area').attr('type', 'text'); // 화면에서 hidden 처리한 input box type을 text로 일시 변환
        	                $('#data-area').select(); // input에 담긴 데이터를 선택
        	                var copy = document.execCommand('copy'); // clipboard에 데이터 복사
        	                $('#data-area').attr('type', 'hidden'); // input box를 다시 hidden 처리
        	                if (copy) {
        	                    alert("미니홈 링크가 복사 되었습니다."); // 사용자 알림
        	                }
        	            });

        	        });
        	    </script>
        	</ul>
	    </div>
    </ul>


</div>

<div class="rb_prof_tab">

    <div>

        <nav id="bo_cate" class="swiper-container swiper-container-category">
            <ul id="bo_cate_ul" class="swiper-wrapper swiper-wrapper-category">
                <li class="swiper-slide swiper-slide-category"><a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $mb['mb_id'] ?>" <?php if($ca == "") { ?>id="bo_cate_on"<?php } ?>>홈</a></li>
                <li class="swiper-slide swiper-slide-category"><a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $mb['mb_id'] ?>&ca=bbs" <?php if($ca == "bbs") { ?>id="bo_cate_on"<?php } ?>>새글</a></li>
                <?php if(isset($sb['sb_use']) && $sb['sb_use'] == 1) { ?>
                    <?php if($mb['mb_id'] == $member['mb_id']) { ?>
                    <li class="swiper-slide swiper-slide-category"><a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $mb['mb_id'] ?>&ca=fw" <?php if($ca == "fw") { ?>id="bo_cate_on"<?php } ?>>구독자</a></li>
                    <li class="swiper-slide swiper-slide-category"><a href="<?php echo G5_URL ?>/rb/home.php?mb_id=<?php echo $mb['mb_id'] ?>&ca=fn" <?php if($ca == "fn") { ?>id="bo_cate_on"<?php } ?>>내 구독</a></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </nav>

        <script>
            $(document).ready(function(){
                $("#bo_cate_ul li").addClass("swiper-slide swiper-slide-category");
            });

            var activeElement = document.querySelector('.swiper-slide-category a#bo_cate_on');

            // 초기 슬라이드 인덱스를 담을 변수
            var initialSlideIndex = 0;

            if (activeElement) {
                // 부모 li 태그를 가져옴
                var parentLi = activeElement.closest('.swiper-slide-category');

                // 모든 슬라이드 요소들을 가져옴
                var allSlides = document.querySelectorAll('.swiper-slide-category');

                // 부모 li 태그의 인덱스를 계산
                initialSlideIndex = Array.prototype.indexOf.call(allSlides, parentLi);
            }

            var swiper = new Swiper('.swiper-container-category', {
                slidesPerView: 'auto', //가로갯수
                spaceBetween: 0, // 간격
                //slidesOffsetBefore: 40, //좌측여백
                //slidesOffsetAfter: 40, // 우측여백
                observer: true, //리셋
                observeParents: true, //리셋
                touchRatio: 1, // 드래그 가능여부
                initialSlide: initialSlideIndex, // 초기 슬라이드 인덱스 설정

            });

        </script>


    </div>


        <?php if($ca == "") { ?>
        <div>
            <ul class="cont_info_wrap">
                <li class="cont_info_wrap_l">
                    <dd>닉네임</dd>
                    <dd><?php echo $mb['mb_nick'] ?> <!--<span>@<?php echo $mb['mb_id'] ?></span>--></dd>
                </li>
                <li class="cont_info_wrap_r">
                    <dd>회원레벨</dd>
                    <dd><?php echo $mb['mb_level'] ?>레벨</dd>
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
                    <dd><?php echo ($member['mb_level'] >= $mb['mb_level']) ?  substr($mb['mb_datetime'],0,10) ." (+".number_format($mb_reg_after)."일)" : "알 수 없음";  ?></dd>
                </li>
                <div class="cb"></div>
            </ul>
            <ul class="cont_info_wrap">
                <li class="cont_info_wrap_l">
                    <dd>운영채널</dd>
                    <dd>
                    <?php if($mb_homepage) { ?>
                    <a href="<?php echo $mb_homepage ?>" target="_blank"><?php echo $mb_homepage ?></a>
                    <?php } else { ?>
                    -
                    <?php } ?>
                    </dd>
                </li>
                <li class="cont_info_wrap_r">
                    <dd>최종접속</dd>
                    <dd><?php echo ($member['mb_level'] >= $mb['mb_level']) ? $mb['mb_today_login'] : "알 수 없음"; ?></dd>
                </li>
                <div class="cb"></div>
            </ul>
        </div>

        <?php if(isset($rb_builder['bu_mini_use1']) && $rb_builder['bu_mini_use1'] == 1) { ?>
        <?php
        // // 최근 7일 배열 준비 (오늘 포함)
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-{$i} days"));
        }

        // // 초기 카운터 0으로 세팅
        $post_counts    = array_fill(0, count($dates), 0); // 글
        $comment_counts = array_fill(0, count($dates), 0); // 댓글

        // // g5_board_new 테이블 기준으로 글/댓글 구분
        // // - wr_id = wr_parent : 원글
        // // - wr_id != wr_parent : 댓글
        $bn_table = isset($g5['board_new_table']) ? $g5['board_new_table'] : G5_TABLE_PREFIX.'board_new';

        // // 조회 시작일 계산
        $from_dt = $dates[0] . ' 00:00:00';

        $sql = "
            SELECT DATE(bn_datetime) AS ymd,
                   SUM(CASE WHEN wr_id = wr_parent THEN 1 ELSE 0 END) AS posts,
                   SUM(CASE WHEN wr_id <> wr_parent THEN 1 ELSE 0 END) AS comments
            FROM {$bn_table}
            WHERE bn_datetime >= '{$from_dt}' and mb_id = '{$mb['mb_id']}'
            GROUP BY DATE(bn_datetime)
            ORDER BY ymd
        ";
        $res = sql_query($sql);

        // // 결과를 날짜 인덱스에 매핑
        $idx_map = array_flip($dates);
        while ($row = sql_fetch_array($res)) {
            $ymd = $row['ymd'];
            if (isset($idx_map[$ymd])) {
                $i = $idx_map[$ymd];
                $post_counts[$i]    = (int)$row['posts'];
                $comment_counts[$i] = (int)$row['comments'];
            }
        }
        ?>

        <div class="minihome_charts">
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

            <div class="rb_chart rb_chart_div1">
                <li class="bbs_main_wrap_tit_l">
                    <h2 class="font-B font-18">최근 게시물 현황</h2>
                </li>
                <li class="bbs_main_wrap_tit_r mt-20">
                    <button type="button" class="more_btn" onclick="location.href='<?php echo G5_BBS_URL ?>/search.php?stx=<?php echo $mb['mb_id'] ?>&sfl=mb_id&sop=and';">더보기</button>
                </li>
                <div class="cb"></div>
                <div id="rb-chart1" class="font-R"></div>
            </div>

            <div class="cb"></div>

            <script>
                // // 숫자 포맷
                function numberWithCommas(x) {
                    try {
                        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    } catch (e) {
                        return x;
                    }
                }

                // // PHP → JS 데이터 주입
                const categories = <?php echo json_encode($dates, JSON_UNESCAPED_UNICODE); ?>;
                const postSeries = <?php echo json_encode(array_map('intval', $post_counts)); ?>;
                const cmtSeries = <?php echo json_encode(array_map('intval', $comment_counts)); ?>;


                // 글/댓글 최대값 계산
                const postMax = (postSeries.length ? Math.max.apply(null, postSeries) : 0);
                const cmtMax  = (cmtSeries.length  ? Math.max.apply(null, cmtSeries)  : 0);
                const overallMax = Math.max(1, postMax, cmtMax); // 모두 0이면 1로 최소 높이 확보


                const mainColor = '<?php echo isset($rb_config['co_color']) ? $rb_config['co_color'] : '#7b5cff'; ?>';
                const subColor = '#b989ff';

                const options1 = {
                    chart: {
                        type: 'line',
                        height: 260,
                        toolbar: {
                            show: false
                        }
                    },
                    series: [{
                            name: '글',
                            data: postSeries
                        },
                        {
                            name: '댓글',
                            data: cmtSeries
                        }
                    ],
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '11px',
                                colors: '#000'
                            },
                            formatter: (val) => String(val).replace(/^\d{4}-/, '') // YYYY- 제거 → MM-DD
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
                      max: overallMax,              // ← 게시물·댓글 중 최대치로 상한 고정
                      tickAmount: 6,                // // 선택: 눈금 수
                      labels: {
                        show: false,
                        style: { fontSize:'11px', colors:'#000' },
                        formatter: (val) => numberWithCommas(val)
                      },
                      axisBorder: { show:false },
                      axisTicks:  { show:false }
                    },

                    // 라인 스타일
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

                    // 값 라벨
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
                        formatter: (val) => val ? numberWithCommas(val) + '건' : ''
                    },

                    colors: [mainColor, subColor],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: (val) => numberWithCommas(val) + '건'
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
                };

                const chart1 = new ApexCharts(document.querySelector("#rb-chart1"), options1);
                chart1.render();
            </script>
        </div>
        <?php } ?>

        <?php if(isset($rb_builder['bu_mini_use2']) && $rb_builder['bu_mini_use2'] == 1) { ?>
        <?php if (($config['cf_admin'] ?? '') !== ($mb['mb_id'] ?? '')) { ?>
        <?php
            // // 기준 회원
            $target_mb_id = $mb['mb_id'];

            // // 최근 7일(오늘 포함) 레이블/데이터
            $day_labels  = [];
            $day_points  = [];

            // // 6일 전 ~ 오늘(0일 전)까지
            for ($i = 6; $i >= 0; $i--) {
                $ts = strtotime("-{$i} day");                  // 0: 오늘, 6: 6일 전
                $ds = date('Y-m-d 00:00:00', $ts);            // 시작 시각
                $de = date('Y-m-d 23:59:59', $ts);            // 종료 시각

                // // 레이블: MM-DD
                $day_labels[] = date('m-d', $ts);

                // // 해당 일자 획득 포인트 합계 (사용/차감 포인트는 제외: po_point > 0 만 합산)
                $sql_day = "
                    SELECT
                        SUM(IF(po_point > 0, po_point, 0)) AS total_points
                    FROM {$g5['point_table']}
                    WHERE mb_id = '".sql_real_escape_string($target_mb_id)."'
                      AND po_datetime >= '{$ds}'
                      AND po_datetime <= '{$de}'
                ";
                $row_day = sql_fetch($sql_day);
                $day_points[] = (int)($row_day['total_points'] ?? 0);
            }
            // // ===== /최근 7일 집계 끝 =====
        ?>
        <div class="minihome_charts">
            <div class="rb_chart rb_chart_div1">
                <li class="bbs_main_wrap_tit_l">
                    <h2 class="font-B font-18">최근 포인트 획득</h2>
                </li>
                <li class="bbs_main_wrap_tit_r mt-20">

                </li>
                <div class="cb"></div>
                <div id="rb-chart2" class="font-R"></div>
            </div>
            <div class="cb"></div>

            <script>
                // // PHP → JS 데이터 주입 (최근 7일)
                const weekCats = <?php echo json_encode($day_labels, JSON_UNESCAPED_UNICODE); ?>;
                const weekVals = <?php echo json_encode(array_map('intval', $day_points)); ?>;

                // // 색상: 차트1과 톤 맞춤
                const mainColor2 = '<?php echo $rb_config['co_color'] ?? "#7b5cff"; ?>';

                // // y축 스케일 계산
                // // STEP: 기본 간격, MAX_TICKS: 최대 눈금 개수
                const BASE_STEP  = 1000;
                const MAX_TICKS  = 8;

                let rawMax = weekVals.length ? Math.max.apply(null, weekVals) : 0;
                if (rawMax <= 0) {
                    rawMax = BASE_STEP * 2;   // // 데이터 없을 때 기본값
                }

                let step  = BASE_STEP;
                // // 눈금 개수가 MAX_TICKS를 넘지 않도록 STEP을 2배씩 키움
                while (Math.ceil(rawMax / step) > MAX_TICKS) {
                    step *= 2;
                }

                const ticks = Math.ceil(rawMax / step);   // // 실제 눈금 개수 (MAX_TICKS 이내)
                const yMax  = ticks * step;              // // y축 최댓값


                const options2 = {
                    chart: {
                        type: 'bar',
                        height: 260,
                        toolbar: {
                            show: false
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
                      max: yMax,              // // 동적 상한
                      tickAmount: ticks,      // // 과도하게 많지 않은 눈금 개수
                      decimalsInFloat: 0,
                      labels: {
                        style: { fontSize: '11px', colors: '#000' },
                        formatter: (val) => numberWithCommas(val)
                      },
                      axisBorder: { show: false },
                      axisTicks:  { show: false }
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '35%',
                            borderRadius: 6,
                            borderRadiusApplication: 'end',
                            borderRadiusWhenStacked: 'last',
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
                        formatter: (val) => val ? numberWithCommas(val) + '점' : ''
                    },
                    colors: [mainColor2],
                    tooltip: {
                        y: {
                            formatter: (val) => numberWithCommas(val) + '점'
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
                };

                const chart2 = new ApexCharts(document.querySelector("#rb-chart2"), options2);
                chart2.render();
            </script>

        </div>
        <?php } ?>
        <?php } ?>

        <?php if(empty($rb_builder['bu_mini_use3']) || $rb_builder['bu_mini_use3'] == 0) { ?>
        <style>
            .main_latest_inc {display: none;}
        </style>
        <?php } ?>

        <?php } ?>

        <?php if($ca == "bbs" || $ca == "") { ?>
        <div <?php if($ca == "") { ?>class="main_latest_inc"<?php } ?>>

            <ul class="cont_info_wrap cont_info_wrap_mmt">
                <?php
                    $sql_commons = " from {$g5['board_new_table']} a, {$g5['board_table']} b where a.bo_table = b.bo_table and a.wr_id = a.wr_parent and a.mb_id = '{$mb['mb_id']}' and b.bo_use_search = '1' order by a.bn_id desc ";

                    if($ca == "bbs") {

                        /* 페이징 추가 { */
                        $rpg_sql = " select count(*) as cnt {$sql_commons} ";
                        $rpg_row = sql_fetch($rpg_sql);
                        $rpg_total_count = $rpg_row['cnt'];

                        //$rpg_rows = G5_IS_MOBILE ? $config['cf_mobile_page_rows'] : $config['cf_new_rows'];
                        $rpg_rows = G5_IS_MOBILE ? 10 : 10;
                        $rpg_total_page  = ceil($rpg_total_count / $rpg_rows);  // 전체 페이지 계산
                        if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
                        $from_record = ($page - 1) * $rpg_rows; // 시작 열을 구함

                        $rpg_write_pages = get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $rpg_total_page, "?mb_id=$mb_id&amp;ca=$ca&amp;page=");
                        /* } */


                        $sqls = " select a.*, b.bo_subject, b.bo_mobile_subject {$sql_commons} limit {$from_record}, {$rpg_rows} ";
                    } else {
                        $sqls = " select a.*, b.bo_subject, b.bo_mobile_subject {$sql_commons} limit 10 ";
                    }

                    $results = sql_query($sqls);

                ?>

                <div class="bbs_main">


                <!-- { -->
                <ul class="bbs_main_wrap_thumb_con">
                    <div class="swiper-container swiper-container-home">
                        <ul class="swiper-wrapper swiper-wrapper-home">

                <?php
                for ($i=0; $rows=sql_fetch_array($results); $i++) {
                    $tmp_write_table = $g5['write_prefix'].$rows['bo_table'];
                    $row2 = sql_fetch(" select * from {$tmp_write_table} where wr_id = '{$rows['wr_id']}' ");
                    $hrefs = get_pretty_url($rows['bo_table'], $row2['wr_id']);

                    //썸네일
                    $thumb = get_list_thumbnail($rows['bo_table'], $row2['wr_id'], $thumb_width, $thumb_height, false, true);


                    if($thumb['src']) {
                        $img = $thumb['src'];
                    } else {
                        $img = G5_THEME_URL.'/rb.img/no_image.png';
                        $thumb['alt'] = '이미지가 없습니다.';
                    }

                    //썸네일 출력 class="skin_list_image" 필수 (높이값 설정용)
                    $img_content = '<img src="'.$img.'" alt="'.$thumb['alt'].'" class="skin_list_image">';

                    //게시물 링크
                    $wr_href = get_pretty_url($rows['bo_table'], $row2['wr_id']);

                    $sec_txt = '<span style="opacity:0.6">작성자 및 관리자 외 열람할 수 없습니다.<br>비밀글 기능으로 보호된 글입니다.</span>';

                    //본문출력 (class="cut" : 한줄자르기 / class="cut2" : 두줄자르기)
                    $wr_content = preg_replace("/<(.*?)\>/","",$row2['wr_content']);
                    $wr_content = preg_replace("/&nbsp;/","",$wr_content);
                    $wr_content = get_text($wr_content);
                ?>


                            <dd class="swiper-slide swiper-slide-home" onclick="location.href='<?php echo $hrefs ?>';">

                                <div>

                                    <?php if($thumb['src']) { ?>
                                    <ul class="bbs_main_wrap_con_ul1">
                                        <a href="<?php echo $hrefs ?>"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a>
                                    </ul>
                                    <?php } ?>


                                    <ul class="bbs_main_wrap_con_ul2" <?php if(!$thumb['src']) { ?>style="padding-right:0px;"<?php } ?>>
                                        <li class="bbs_main_wrap_con_subj cut"><a href="<?php echo $hrefs ?>"><?php echo $row2['wr_subject'] ?></a></li>

                                        <?php if (strstr($row2['wr_option'], 'secret')) { ?>
                                            <li class="bbs_main_wrap_con_cont">
                                                <?php echo $sec_txt; ?>
                                            </li>
                                        <?php } else { ?>
                                        <li class="bbs_main_wrap_con_cont cut2">
                                            <a href="<?php echo $hrefs ?>"><?php echo $wr_content; ?></a>
                                            </li>
                                        <?php } ?>


                                            <li class="bbs_main_wrap_con_info">
                                                <span class="prof_tiny_name font-B"><?php echo $row2['wr_name'] ?></span>
                                                <?php echo passing_time($row2['wr_datetime']) ?><br>

                                                <?php if($row2['ca_name']) { ?>
                                                <?php echo $rows['bo_subject'] ?> [<?php echo $row2['ca_name'] ?>]
                                                <?php } else { ?>
                                                <?php echo $rows['bo_subject'] ?>
                                                <?php } ?>


                                                댓글 <?php echo number_format($row2['wr_comment']); ?>
                                                조회 <?php echo number_format($row2['wr_hit']); ?>

                                            </li>

                                    </ul>
                                    <div class="cb"></div>
                                </div>
                            </dd>
                            <!-- } -->

                            <?php }  ?>
                            <?php if ($i == 0) { //게시물이 없을 때  ?>
                            <dd class="no_data" style="width:100% !important;">등록한 게시물이 없습니다.</dd>
                            <?php }  ?>



                        </ul>
                    </div>

                    <!-- 모듈세팅 { -->
                    <script>

                        var swiper = new Swiper('.swiper-container-home', {
                            slidesPerColumnFill: 'row', //세로형
                            slidesPerView: 2, //가로갯수
                            slidesPerColumn: 999, // 세로갯수
                            spaceBetween: 20, // 간격
                            observer: true, //리셋
                            observeParents: true, //리셋
                            touchRatio: 0, // 드래그 가능여부

                            breakpoints: { //반응형세팅
                                1024: {
                                    slidesPerView: 2, //가로갯수
                                    slidesPerColumn: 999, //세로갯수
                                    spaceBetween: 20, //간격
                                },
                                10: {
                                    slidesPerView: 1, //가로갯수
                                    slidesPerColumn: 999, //세로갯수
                                    spaceBetween: 20, //간격
                                }
                            }
                        });


                    </script>
                    <!-- } -->

                </ul>
                <!-- } -->

            </div>

            </ul>
            <!--
            <ul class="cont_info_wrap cont_info_wrap_mmt">
                <h2>상품</h2>

            </ul>
            -->

            <?php
            if($ca == "bbs") {
                echo $rpg_write_pages;
            }
            ?>

        </div>
        <?php } ?>


        <?php
        if(isset($sb['sb_use']) && $sb['sb_use'] == 1) {
            if($mb['mb_id'] == $member['mb_id']) {
                include_once(G5_PATH.'/rb/rb.mod/subscribe/subscribe_table.skin.php');
            } else {
                if($ca == "fn" || $ca == "fw") {
                    alert('올바른 방법으로 이용해주세요.');
                }
            }
        }
        ?>


</div>


<div class="cb"></div>

