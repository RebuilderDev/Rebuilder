<?php
if (!defined('_GNUBOARD_')) exit; //개별 페이지 접근 불가

// 알림 관리에서 설정한 초 단위 폴링주기를 밀리초로 변환합니다.
$polling_seconds = function_exists('rb_notification_polling_seconds')
    ? rb_notification_polling_seconds()
    : 60;
$wset['delay'] = $polling_seconds * 1000;
$alarm_url = G5_URL . "/rb/rb.mod/alarm";
?>

<?php
// 특정 페이지에서 alarm 표시 안함
$except_alarm_page = array(
    'memo.php',
    'point.php',
    'scrap.php',
    'profile.php',
    'coupon.php',
    'memo_form.php'
);

if (!in_array(basename($_SERVER['PHP_SELF']), $except_alarm_page)) {
    if (isset($member['mb_id']) && $member['mb_id']) { // $member 배열과 'mb_id' 키가 정의되어 있는지 확인 ?>
        <link rel="stylesheet" href="<?php echo $alarm_url ?>/alarm.css?ver=<?php echo time(); ?>">
        <script>
            var memo_alarm_url = "<?php echo $alarm_url; ?>";
            var memo_alarm_bbs_url = "<?php echo G5_BBS_URL; ?>";
            //var audio = new Audio("<?php echo $alarm_url;?>/memo_on.mp3");  // 임의 폴더 아래에 사운드 파일을 넣고 자바스크립트 동일경로
        </script>
        <?php
        $dirs = dirname($_SERVER['PHP_SELF']); // $PHP_SELF 대신 $_SERVER['PHP_SELF'] 사용
        $dirs_chk = str_replace('/', '', $dirs);
        ?>

        <?php
        $alarm_request_header = trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $alarm_app_title = trim((string) ($app['ap_title'] ?? ''));
        $is_alarm_app = $alarm_request_header !== ''
            && $alarm_app_title !== ''
            && $alarm_request_header === $alarm_app_title;
        ?>
        <?php if ($is_alarm_app) { ?>
        <script src="<?php echo $alarm_url ?>/alarm.app.js?ver=<?php echo time(); ?>"></script>
        <?php } else { ?>
        <script src="<?php echo $alarm_url ?>/alarm.js?ver=<?php echo time(); ?>"></script>
        <?php } ?>
        <script type="text/javascript">
            $(function() {
                setInterval(function() {
                    check_alarm();
                }, <?php echo $wset['delay'] ?>);
                check_alarm();
            });
        </script>
    <?php } ?>
<?php } ?>
