<?php
// 이 파일은 새로운 파일 생성시 반드시 포함되어야 함
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

if (!isset($g5['title'])) {
    $g5['title'] = $config['cf_title'];
    $g5_head_title = $g5['title'];
}
else {
    // 상태바에 표시될 제목
    $g5_head_title = implode(' | ', array_filter(array($g5['title'], $config['cf_title'])));
}

$g5['title'] = strip_tags($g5['title']);
$g5_head_title = strip_tags($g5_head_title);

// 현재 접속자
// 게시판 제목에 ' 포함되면 오류 발생
$g5['lo_location'] = addslashes($g5['title']);
if (!$g5['lo_location'])
    $g5['lo_location'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
$g5['lo_url'] = addslashes(clean_xss_tags($_SERVER['REQUEST_URI']));
if (strstr($g5['lo_url'], '/'.G5_ADMIN_DIR.'/') || $is_admin == 'super') $g5['lo_url'] = '';

/*
// 만료된 페이지로 사용하시는 경우
header("Cache-Control: no-cache"); // HTTP/1.1
header("Expires: 0"); // rfc2616 - Section 14.21
header("Pragma: no-cache"); // HTTP/1.0
*/
?>
<!doctype html>
<html lang="ko">
<head>

<meta charset="utf-8">

<?php if (!empty($rb_builder['bu_module_spinner_use'])) { ?>
<style>
html.rb-module-spinner-use .rb_layout_box .rb_swiper:not(.rb-swiper-ready):not(.rb-swiper-spinner-timeout) {
    position: relative;
    min-height: 70px;
}
html.rb-module-spinner-use .rb_layout_box .rb_swiper:not(.rb-swiper-ready):not(.rb-swiper-spinner-timeout) .rb_swiper_inner {
    opacity: 0;
}
html.rb-module-spinner-use .rb_layout_box .rb_swiper .rb_swiper_inner {
    transition: opacity 0.13s ease-out;
}
html.rb-module-spinner-use .rb_layout_box .rb_swiper:not(.rb-swiper-ready):not(.rb-swiper-spinner-timeout)::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 22px;
    height: 22px;
    margin-top: -11px;
    margin-left: -11px;
    border: 2px solid rgba(160, 160, 160, 0.22);
    border-top-color: rgba(160, 160, 160, 0.72);
    border-radius: 50%;
    animation: rbModuleSwiperSpin 0.35s ease-in-out infinite;
    pointer-events: none;
    z-index: 20;
}
@keyframes rbModuleSwiperSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
<script>
document.documentElement.classList.add('rb-module-spinner-use');
</script>
<?php } ?>

<script>
(function () {
    function rbSwiperNumber(slider, name, fallback) {
        var value = parseInt(slider.getAttribute('data-' + name), 10);
        return Number.isFinite(value) ? value : fallback;
    }

    function rbPrepareSwiperGrid(slider) {
        if (!slider || slider.nodeType !== 1 || slider.classList.contains('rb-swiper-ready')) return;
        if (!slider.closest('.rb_layout_box')) return;

        var wrapper = slider.querySelector('.rb-swiper-wrapper');
        if (!wrapper) return;

        var mobile = window.innerWidth <= 1024;
        var cols = Math.max(1, rbSwiperNumber(slider, mobile ? 'mo-w' : 'pc-w', 1));
        var rows = Math.max(1, rbSwiperNumber(slider, mobile ? 'mo-h' : 'pc-h', 1));
        var gap = Math.max(0, rbSwiperNumber(slider, mobile ? 'mo-gap' : 'pc-gap', 0));
        var pageSize = cols * rows;
        var slides = Array.prototype.filter.call(wrapper.children, function (child) {
            return child.classList && child.classList.contains('rb_swiper_list');
        });

        if (!slides.length) return;

        slider.classList.add('rb-swiper-pregrid');
        if (document.documentElement.classList.contains('rb-module-spinner-use') && !slider.hasAttribute('data-rb-spinner-timer')) {
            slider.setAttribute('data-rb-spinner-timer', '1');
            window.setTimeout(function () {
                if (!slider.classList.contains('rb-swiper-ready')) {
                    slider.classList.add('rb-swiper-spinner-timeout');
                }
            }, 3000);
        }
        wrapper.style.display = 'grid';
        wrapper.style.gridTemplateColumns = 'repeat(' + cols + ', minmax(0, 1fr))';
        wrapper.style.gridAutoFlow = 'row';
        wrapper.style.columnGap = gap + 'px';
        wrapper.style.rowGap = gap + 'px';
        wrapper.style.transform = 'none';
        wrapper.style.transition = 'none';

        slides.forEach(function (slide, index) {
            slide.classList.add('swiper-slide');
            slide.style.display = index < pageSize ? '' : 'none';
        });
    }

    function rbPrepareSwiperScope(node) {
        if (!node || node.nodeType !== 1) return;

        if (node.matches && node.matches('.rb_swiper')) rbPrepareSwiperGrid(node);
        if (node.querySelectorAll) {
            node.querySelectorAll('.rb_swiper').forEach(rbPrepareSwiperGrid);
        }

        var parentSlider = node.closest ? node.closest('.rb_swiper') : null;
        if (parentSlider) rbPrepareSwiperGrid(parentSlider);
    }

    var observer = new MutationObserver(function (records) {
        records.forEach(function (record) {
            Array.prototype.forEach.call(record.addedNodes, rbPrepareSwiperScope);
        });
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
    document.addEventListener('DOMContentLoaded', function () {
        rbPrepareSwiperScope(document.documentElement);
    });

    var resizeFrame = 0;
    window.addEventListener('resize', function () {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(function () {
            document.querySelectorAll('.rb_swiper:not(.rb-swiper-ready)').forEach(rbPrepareSwiperGrid);
        });
    });
})();
</script>

<!-- viewport { -->
<?php if(isset($rb_builder['bu_viewport']) && $rb_builder['bu_viewport']) { ?>
<meta name="viewport" content="width=device-width,initial-scale=<?php echo $rb_builder['bu_viewport'] ?>,minimum-scale=<?php echo $rb_builder['bu_viewport'] ?>,maximum-scale=<?php echo $rb_builder['bu_viewport'] ?>,user-scalable=no" />
<?php } else { ?>
<meta name="viewport" content="width=device-width,initial-scale=0.9,minimum-scale=0.9,maximum-scale=0.9,user-scalable=no" />
<?php } ?>
<meta name="HandheldFriendly" content="true" />
<meta http-equiv="imagetoolbar" content="no" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<!-- } -->

<?php
// // HTML escape
if (!function_exists('rb_h')) {
    function rb_h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// // 모드 판단
$rb_meta_is_post    = (isset($bo_table) && $bo_table && isset($wr_id) && $wr_id);
$rb_meta_is_item    = (isset($it_id) && $it_id);
$rb_meta_is_content = (isset($co_id) && $co_id);

// // 타이틀: 기본은 무조건 g5_head_title
$rb_meta_head_title = isset($g5_head_title) ? (string)$g5_head_title : '';
$rb_meta_title      = $rb_meta_head_title;
$rb_meta_og_title   = $rb_meta_head_title;

// // index에서만 se_title 우선 (없으면 g5_head_title)
if (defined('_INDEX_') && _INDEX_) {
    $rb_meta_seo_title = isset($seo['se_title']) ? trim((string)$seo['se_title']) : '';
    if ($rb_meta_seo_title !== '') {
        $rb_meta_title    = $rb_meta_seo_title;
        $rb_meta_og_title = $rb_meta_seo_title;
    }
}

// // 기본값(SEO 고정)
$rb_meta_keywords    = isset($seo['se_keywords']) ? (string)$seo['se_keywords'] : '';
$rb_meta_description = isset($seo['se_description']) ? (string)$seo['se_description'] : '';

$rb_meta_og_description = isset($seo['se_og_description']) ? (string)$seo['se_og_description'] : '';
$rb_meta_og_image       = '';

// // 기본 OG 이미지(설정이 있을 때만)
if (!empty($seo['se_og_image'])) {
    // // 기존 구조 호환: /data/seo/og_image 라는 "고정 파일"을 쓰는 구조
    $rb_meta_og_image = G5_URL . '/data/seo/og_image';
}

// // 1) 게시물 상세
if ($rb_meta_is_post) {

    $rb_meta_views = get_view($write, $board, $board_skin_path);
    if (!is_array($rb_meta_views)) $rb_meta_views = array();

    $rb_meta_wr_content_str = (string)($rb_meta_views['wr_content'] ?? '');

    // // description
    $rb_meta_desc = strip_tags($rb_meta_wr_content_str);
    $rb_meta_desc = preg_replace("/<(.*?)\>/", "", $rb_meta_desc);
    $rb_meta_desc = preg_replace("/&nbsp;/", " ", $rb_meta_desc);
    $rb_meta_desc = preg_replace("/\s+/", " ", $rb_meta_desc);
    $rb_meta_desc = trim($rb_meta_desc);

    if (function_exists('cut_str')) $rb_meta_desc = cut_str($rb_meta_desc, 100);
    else $rb_meta_desc = mb_substr($rb_meta_desc, 0, 100, 'UTF-8');

    // // image: 첨부 > 본문 첫 이미지
    $rb_meta_img = '';
    if (!empty($rb_meta_views['file'][0]['file'])) {
        $rb_meta_img = G5_DATA_URL . '/file/' . $bo_table . '/' . rawurlencode((string)$rb_meta_views['file'][0]['file']);
    } else {
        $rb_meta_matches = get_editor_image($rb_meta_wr_content_str);
        if (is_array($rb_meta_matches) && isset($rb_meta_matches[1]) && is_array($rb_meta_matches[1])) {
            for ($rb_meta_i = 0; $rb_meta_i < count($rb_meta_matches[1]); $rb_meta_i++) {
                $rb_meta_m = array();
                if (preg_match("/src=[\'\"]?([^>\'\"]+[^>\'\"]+)/i", (string)$rb_meta_matches[1][$rb_meta_i], $rb_meta_m)) {
                    if (!empty($rb_meta_m[1])) { $rb_meta_img = (string)$rb_meta_m[1]; break; }
                }
            }
        }
    }

    $rb_meta_description    = $rb_meta_desc;
    $rb_meta_og_description = $rb_meta_desc;
    if ($rb_meta_img !== '') $rb_meta_og_image = $rb_meta_img;

// // 2) 상품
} else if ($rb_meta_is_item) {

    $rb_meta_it_id_safe = preg_replace("/[^0-9a-zA-Z_\-]/", "", (string)$it_id);

    $rb_meta_item_row = sql_fetch(" SELECT it_id, it_basic, it_img1 FROM {$g5['g5_shop_item_table']} WHERE it_id = '{$rb_meta_it_id_safe}' LIMIT 1 ");

    if (is_array($rb_meta_item_row) && !empty($rb_meta_item_row['it_id'])) {

        $rb_meta_it_basic = (string)($rb_meta_item_row['it_basic'] ?? '');

        // // description
        $rb_meta_desc = strip_tags($rb_meta_it_basic);
        $rb_meta_desc = preg_replace("/\s+/", " ", $rb_meta_desc);
        $rb_meta_desc = trim($rb_meta_desc);

        if (function_exists('cut_str')) $rb_meta_desc = cut_str($rb_meta_desc, 100);
        else $rb_meta_desc = mb_substr($rb_meta_desc, 0, 100, 'UTF-8');

        // // image 1: it_img1 우선
        $rb_meta_img = '';
        $rb_meta_img1 = trim((string)($rb_meta_item_row['it_img1'] ?? ''));

        if ($rb_meta_img1 !== '') {
            if (preg_match("~^https?://~i", $rb_meta_img1)) {
                $rb_meta_img = $rb_meta_img1;
            } else {
                // // 슬래시 유지 + 각 조각만 인코딩
                $rb_meta_parts = explode('/', $rb_meta_img1);
                $rb_meta_parts = array_map('rawurlencode', $rb_meta_parts);
                $rb_meta_img = G5_DATA_URL . '/item/' . implode('/', $rb_meta_parts);
            }
        }

        // // image 2: it_img1이 비었을 때 get_it_image()로 첫 이미지 추출(가능하면)
        if ($rb_meta_img === '' && function_exists('get_it_image')) {
            $rb_meta_img_html = get_it_image($rb_meta_it_id_safe, 0, 0);
            $rb_meta_m = array();
            if (preg_match("/src=[\'\"]?([^>\'\"]+[^>\'\"]+)/i", (string)$rb_meta_img_html, $rb_meta_m)) {
                if (!empty($rb_meta_m[1])) $rb_meta_img = (string)$rb_meta_m[1];
            }
        }

        $rb_meta_description    = $rb_meta_desc;
        $rb_meta_og_description = $rb_meta_desc;
        if ($rb_meta_img !== '') $rb_meta_og_image = $rb_meta_img;
    }

// // 3) 내용관리
} else if ($rb_meta_is_content) {

    $rb_meta_co_id_safe = preg_replace("/[^0-9a-zA-Z_\-]/", "", (string)$co_id);

    $rb_meta_content_row = sql_fetch(" SELECT co_id, co_content FROM {$g5['content_table']} WHERE co_id = '{$rb_meta_co_id_safe}' LIMIT 1 ");

    if (is_array($rb_meta_content_row) && !empty($rb_meta_content_row['co_id'])) {

        $rb_meta_co_content = (string)($rb_meta_content_row['co_content'] ?? '');

        // // description: co_content (HTML 제거 + 공백 정리 + 100자 자르기)
        $rb_meta_desc = strip_tags($rb_meta_co_content);
        $rb_meta_desc = preg_replace("/&nbsp;/", " ", $rb_meta_desc);
        $rb_meta_desc = preg_replace("/\s+/", " ", $rb_meta_desc);
        $rb_meta_desc = trim($rb_meta_desc);

        if (function_exists('cut_str')) $rb_meta_desc = cut_str($rb_meta_desc, 100);
        else $rb_meta_desc = mb_substr($rb_meta_desc, 0, 100, 'UTF-8');

        $rb_meta_description    = $rb_meta_desc;
        $rb_meta_og_description = $rb_meta_desc;

        // // OG Image: 하드코딩(rb.page/co_id.php) > DB내용(co_content) > 기본 OG 이미지 유지
        $rb_meta_img = '';

        // // 1) 하드코딩 파일에서 첫 이미지(src/data-src/srcset) 추출 (정적 케이스만)
        $rb_meta_page_file = G5_THEME_PATH . '/rb.page/' . $rb_meta_co_id_safe . '.php';

        if (is_file($rb_meta_page_file) && is_readable($rb_meta_page_file)) {

            $rb_meta_file_html = @file_get_contents($rb_meta_page_file);

            if ($rb_meta_file_html !== false && $rb_meta_file_html !== '') {

                // // src 우선
                $rb_meta_m = array();
                if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $rb_meta_file_html, $rb_meta_m)) {
                    if (!empty($rb_meta_m[1])) $rb_meta_img = trim((string)$rb_meta_m[1]);
                }

                // // src가 없으면 data-src
                if ($rb_meta_img === '') {
                    $rb_meta_m = array();
                    if (preg_match('/<img[^>]+data-src=[\'"]([^\'"]+)[\'"]/i', $rb_meta_file_html, $rb_meta_m)) {
                        if (!empty($rb_meta_m[1])) $rb_meta_img = trim((string)$rb_meta_m[1]);
                    }
                }

                // // srcset만 있는 케이스: 첫 URL만 추출
                if ($rb_meta_img === '') {
                    $rb_meta_m = array();
                    if (preg_match('/<img[^>]+srcset=[\'"]([^\'"]+)[\'"]/i', $rb_meta_file_html, $rb_meta_m)) {
                        $rb_meta_srcset = trim((string)($rb_meta_m[1] ?? ''));
                        if ($rb_meta_srcset !== '') {
                            $rb_meta_first = trim(strtok($rb_meta_srcset, ',')); // // 첫 후보
                            $rb_meta_first = preg_split('/\s+/', $rb_meta_first);
                            if (is_array($rb_meta_first) && !empty($rb_meta_first[0])) $rb_meta_img = trim((string)$rb_meta_first[0]);
                        }
                    }
                }

                // // 상대경로 보정: /로 시작하면 도메인, 아니면 테마URL 기준
                if ($rb_meta_img !== '') {
                    if (!preg_match('~^https?://~i', $rb_meta_img) && strpos($rb_meta_img, '//') !== 0) {
                        if (strpos($rb_meta_img, '/') === 0) {
                            $rb_meta_img = G5_URL . $rb_meta_img;
                        } else {
                            $rb_meta_img = G5_THEME_URL . '/' . ltrim($rb_meta_img, '/');
                        }
                    }
                }
            }
        }

        // // 2) 하드코딩에서 못찾으면 DB 내용(co_content)에서 첫 이미지 추출
        if ($rb_meta_img === '' && function_exists('get_editor_image')) {

            $rb_meta_matches = get_editor_image($rb_meta_co_content);

            if (is_array($rb_meta_matches) && isset($rb_meta_matches[1]) && is_array($rb_meta_matches[1])) {
                for ($rb_meta_i = 0; $rb_meta_i < count($rb_meta_matches[1]); $rb_meta_i++) {
                    $rb_meta_m = array();
                    if (preg_match("/src=[\'\"]?([^>\'\"]+[^>\'\"]+)/i", (string)$rb_meta_matches[1][$rb_meta_i], $rb_meta_m)) {
                        if (!empty($rb_meta_m[1])) { $rb_meta_img = (string)$rb_meta_m[1]; break; }
                    }
                }
            }
        }

        // // 3) 최종 반영(없으면 기본 OG 이미지 유지)
        if ($rb_meta_img !== '') {
            $rb_meta_og_image = $rb_meta_img;
        }
    }
}

?>


<!-- META { -->
<?php if ($rb_meta_title !== '' || $rb_meta_keywords !== '' || $rb_meta_description !== '') { ?>
<meta name="title" content="<?php echo rb_h($rb_meta_title); ?>" />
<meta name="keywords" content="<?php echo rb_h($rb_meta_keywords); ?>" />
<meta name="description" content="<?php echo rb_h($rb_meta_description); ?>" />
<meta name="robots" content="index,follow" />
<?php } ?>
<!-- } -->

<!-- OG { -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo rb_h(getCurrentUrl()); ?>" />

<?php if ($rb_meta_og_title !== '') { ?>
<meta property="og:title" content="<?php echo rb_h($rb_meta_og_title); ?>" />
<?php } ?>

<?php
// // og:site_name = se_title 우선, 없으면 cf_title
$rb_meta_og_site_name = '';
if (isset($seo['se_title'])) {
    $rb_meta_og_site_name = trim((string)$seo['se_title']);
}
if ($rb_meta_og_site_name === '' && isset($config['cf_title'])) {
    $rb_meta_og_site_name = trim((string)$config['cf_title']);
}
?>
<?php if ($rb_meta_og_site_name !== '') { ?>
<meta property="og:site_name" content="<?php echo rb_h($rb_meta_og_site_name); ?>" />
<?php } ?>

<?php if ($rb_meta_og_description !== '') { ?>
<meta property="og:description" content="<?php echo rb_h($rb_meta_og_description); ?>" />
<?php } ?>

<?php if ($rb_meta_og_image !== '') { ?>
<meta property="og:image" content="<?php echo rb_h($rb_meta_og_image); ?>?ver=<?php echo (int)G5_SERVER_TIME; ?>" />
<?php } ?>
<!-- } -->

<!-- ICO { -->
<?php if(isset($seo['se_favicon']) && $seo['se_favicon']) { ?>
<link rel="shortcut icon" href="<?php echo G5_URL ?>/data/seo/favicon?ver=<?php echo G5_SERVER_TIME ?>" type="image/x-icon">
<link rel="icon" href="<?php echo G5_URL ?>/data/seo/favicon?ver=<?php echo G5_SERVER_TIME ?>" type="image/x-icon">
<?php } ?>
<!-- } -->

<?php
//소유권 확인 메타
if(isset($seo['se_naver_meta']) && $seo['se_naver_meta']) {
    echo $seo['se_naver_meta'];
}

if(isset($seo['se_google_meta']) && $seo['se_google_meta']) {
    echo $seo['se_google_meta'];
}
?>


<?php
if(isset($config['cf_add_meta']) && $config['cf_add_meta'])
    echo $config['cf_add_meta'].PHP_EOL;
?>

<title><?php echo $g5_head_title; ?></title>

<?php
$shop_css = '';
if (defined('_SHOP_')) $shop_css = '_shop';
echo '<link rel="stylesheet" href="'.run_replace('head_css_url', G5_THEME_CSS_URL.'/'.(G5_IS_MOBILE?'mobile':'default').$shop_css.'.css?ver='.G5_CSS_VER, G5_THEME_URL).'">'.PHP_EOL;
?>
<!--[if lte IE 8]>
<script src="<?php echo G5_JS_URL ?>/html5.js"></script>
<![endif]-->

<script>
// 자바스크립트에서 사용하는 전역변수 선언
const g5_url       = "<?php echo G5_URL ?>";
const g5_bbs_url   = "<?php echo G5_BBS_URL ?>";
const g5_is_member = "<?php echo isset($is_member)?$is_member:''; ?>";
const g5_is_admin  = "<?php echo isset($is_admin)?$is_admin:''; ?>";
const g5_is_mobile = "<?php echo G5_IS_MOBILE ?>";
const g5_bo_table  = "<?php echo isset($bo_table)?$bo_table:''; ?>";
const g5_sca       = "<?php echo isset($sca)?$sca:''; ?>";
const g5_editor    = "<?php echo ($config['cf_editor'] && $board['bo_use_dhtml_editor'])?$config['cf_editor']:''; ?>";
const g5_cookie_domain = "<?php echo G5_COOKIE_DOMAIN ?>";
<?php if (defined('G5_USE_SHOP') && G5_USE_SHOP) { ?>
const g5_theme_shop_url = "<?php echo G5_THEME_SHOP_URL; ?>";
const g5_shop_url = "<?php echo G5_SHOP_URL; ?>";
<?php } ?>
<?php if(defined('G5_IS_ADMIN')) { ?>
const g5_admin_url = "<?php echo G5_ADMIN_URL; ?>";
<?php } ?>

// 레이아웃 ajax 에 전달되는 인덱스 플래그
const is_index = <?php echo defined('_INDEX_') ? 'true' : 'false'; ?>;
const is_shop = <?php echo defined('_SHOP_') ? 'true' : 'false'; ?>;
</script>

<?php
$font = (isset($rb_core) && is_array($rb_core) && isset($rb_core['font'])) ? trim((string)$rb_core['font']) : '';
if ($font === '') {
    $font = 'Pretendard';
}

$rb_header_use_white_logo = !empty($rb_core['header_logo_white']);
$rb_header_logo_mo_url = function_exists('rb_header_logo_url')
    ? rb_header_logo_url('mo', $rb_header_use_white_logo)
    : G5_THEME_URL . '/rb.img/logos/mo' . ($rb_header_use_white_logo ? '_w' : '') . '.png';
$rb_header_logo_pc_url = function_exists('rb_header_logo_url')
    ? rb_header_logo_url('pc', $rb_header_use_white_logo)
    : G5_THEME_URL . '/rb.img/logos/pc' . ($rb_header_use_white_logo ? '_w' : '') . '.png';

add_javascript('<script src="'.G5_JS_URL.'/jquery-1.12.4.min.js"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery-migrate-1.4.1.min.js"></script>', 0);

if(defined('_SHOP_')) {
    if (isset($rb_core['layout_shop'])) {
        add_javascript('<script src="' . G5_THEME_URL . '/rb.js/rb.layout.shop.js?ver=2.2.7.1"></script>', 0);
    }
} else {
    if (isset($rb_core['layout'])) {
        add_javascript('<script src="' . G5_THEME_URL . '/rb.js/rb.layout.js?ver=2.2.7.1"></script>', 0);
    }
}


if (defined('_SHOP_')) {
    if(!G5_IS_MOBILE) {
        add_javascript('<script src="'.G5_JS_URL.'/jquery.shop.menu.js?ver='.G5_JS_VER.'"></script>', 0);
    }
} else {
    add_javascript('<script src="'.G5_JS_URL.'/jquery.menu.js?ver='.G5_JS_VER.'"></script>', 0);
}
add_javascript('<script src="'.G5_JS_URL.'/common.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/wrest.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_JS_URL.'/placeholders.min.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/font-awesome/css/font-awesome.min.css">', 0);
if(G5_IS_MOBILE) {
    add_javascript('<script src="'.G5_JS_URL.'/modernizr.custom.70111.js"></script>', 1); // overflow scroll 감지
}

if(defined('_SHOP_')) {
    add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/rb.css/style.shop.css?ver='.filemtime(G5_THEME_PATH.'/rb.css/style.shop.css').'" />', 0);
}

/** 테마구성 **/
$rb_css_files = [
    'reset.css',
    'style.css',
    'mobile.css',
    'form.css',
    'swiper.css',
    'custom.css',
];

foreach ($rb_css_files as $rb_css_file) {
    $rb_css_path = G5_THEME_PATH . "/rb.css/$rb_css_file";
    $rb_css_url = G5_THEME_URL . "/rb.css/$rb_css_file";
    $rb_css_ver = file_exists($rb_css_path) ? filemtime($rb_css_path) : time(); // filemtime 호출 최소화
    add_stylesheet("<link rel='stylesheet' href='{$rb_css_url}?ver={$rb_css_ver}' />", 0);
}

add_javascript('<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>', 0);
add_javascript('<script src="'.G5_THEME_URL.'/rb.js/swiper.js"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/rb.fonts/'.$font.'/'.$font.'.css?ver='.filemtime(G5_THEME_PATH.'/rb.fonts/'.$font.'/'.$font.'.css').'" />', 0);
add_stylesheet('<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css" />', 0);
add_javascript('<script src="'.G5_THEME_URL.'/rb.js/rb.common.js"></script>', 0);

if(!defined('G5_IS_ADMIN'))
    echo $config['cf_add_script'];
?>

<script>
    (function() {
        function setViewport() {
            var w = document.documentElement.clientWidth;
            document.documentElement.style.setProperty('--vw', w + 'px');
            document.documentElement.style.setProperty('--scrollbar-width', (window.innerWidth - w) + 'px');
        }
        setViewport();
        window.addEventListener('resize', setViewport);
    })();
</script>

</head>
<body<?php echo isset($g5['body_script']) ? $g5['body_script'] : ''; ?>>
<?php
if ($is_member) { // 회원이라면 로그인 중이라는 메세지를 출력해준다.
    $sr_admin_msg = '';
    if ($is_admin == 'super') $sr_admin_msg = "최고관리자 ";
    else if ($is_admin == 'group') $sr_admin_msg = "그룹관리자 ";
    else if ($is_admin == 'board') $sr_admin_msg = "게시판관리자 ";

    echo '<div id="hd_login_msg">'.$sr_admin_msg.get_text($member['mb_nick']).'님 로그인 중 ';
    echo '<a href="'.G5_BBS_URL.'/logout.php">로그아웃</a></div>';
}

?>

<main class="<?php echo isset($rb_core['color']) ? $rb_core['color'] : ''; ?> <?php echo isset($rb_core['header']) ? $rb_core['header'] : ''; ?> gap_pc_<?php echo isset($rb_core['gap_pc']) ? $rb_core['gap_pc'] : ''; ?>" id="main">


<?php if (!empty($rb_builder['bu_load'])) { ?>

    <?php if(isset($rb_builder['bu_load']) && $rb_builder['bu_load'] == 2) { ?>

    <?php if (defined("_INDEX_")) { ?>
        <!-- 로더 시작 { -->
        <div id="loadings">
            <div id="loadings_spin"></div>
        </div>

        <script>

            // DOM을 포함한 페이지가 준비가 되면 사라집니다.
            $(window).on("load", function() {
                $('#loadings').delay(500).fadeOut(500);
            });

        </script>
        <!-- } -->
    <?php } ?>

    <?php } else { ?>
        <!-- 로더 시작 { -->
        <div id="loadings">
            <div id="loadings_spin"></div>
        </div>

        <script>

            // DOM을 포함한 페이지가 준비가 되면 사라집니다.
            $(window).on("load", function() {
                $('#loadings').delay(500).fadeOut(500);
            });

        </script>
        <!-- } -->
    <?php } ?>


<?php } ?>
