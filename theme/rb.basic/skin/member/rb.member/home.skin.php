<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css?ver='.G5_TIME_YMDHIS.'">', 0);
$thumb_width  = 120;
$thumb_height = 120;
$ca = isset($_GET['ca']) ? $_GET['ca'] : '';
?>

<div class="rb_profile_layout">
    <?php include 'home.skin.header.php'; ?>
</div>
<div class="cb"></div>
