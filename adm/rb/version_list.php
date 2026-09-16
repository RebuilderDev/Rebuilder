<?php
$sub_menu = '000230';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

// common.php에서 로드한 extend의 기능별 전역상수를 읽습니다.
$rb_version_groups = array(1 => array(), 2 => array());
$rb_version_constants = get_defined_constants();
foreach ($rb_version_constants as $rb_version_constant => $rb_version_name) {
    if (!preg_match('/^RB_SERVICE_NAME_([A-Za-z0-9_]+)$/D', $rb_version_constant, $rb_version_matches)) continue;
    $rb_version_key = $rb_version_matches[1];
    $rb_version_category_constant = 'RB_SERVICE_CAT_'.$rb_version_key;
    $rb_version_ver_constant = 'RB_SERVICE_VER_'.$rb_version_key;
    if (!isset($rb_version_constants[$rb_version_category_constant], $rb_version_constants[$rb_version_ver_constant])) continue;
    $rb_version_category = $rb_version_constants[$rb_version_category_constant];
    $rb_version_ver = $rb_version_constants[$rb_version_ver_constant];
    if (!in_array($rb_version_category, array(1, 2, '1', '2'), true)
        || !is_scalar($rb_version_name) || !is_scalar($rb_version_ver)
        || trim((string)$rb_version_name) === '') continue;
    $rb_version_groups[(int)$rb_version_category][] = array('name' => (string)$rb_version_name, 'version' => (string)$rb_version_ver);
}
foreach ($rb_version_groups as &$rb_version_items) {
    usort($rb_version_items, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}
unset($rb_version_items);

$g5['title'] = '버전관리';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>현재 빌더에 설치된 기능 및 서비스 목록 입니다.</p>
</div>

<?php foreach (array(1 => '부가기능', 2 => '부가서비스') as $rb_version_type => $rb_version_title) {
    $rb_version_items = $rb_version_groups[$rb_version_type];
?>
<section>
    <h2 class="h2_frm"><?php echo $rb_version_title; ?></h2>
    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption><?php echo $rb_version_title; ?> 버전 목록</caption>
            <colgroup>
                <col width="40%">
                <col width="10%">
                <col width="40%">
                <col width="10%">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col" width="40%">기능명</th>
                    <th scope="col" width="10%">버전</th>
                    <th scope="col" width="40%">기능명</th>
                    <th scope="col" width="10%">버전</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rb_version_items) { ?>
                <tr><td colspan="4" class="empty_table">등록된 항목이 없습니다.</td></tr>
                <?php } else {
                    for ($rb_version_i = 0; $rb_version_i < count($rb_version_items); $rb_version_i += 2) { ?>
                <tr>
                    <?php for ($rb_version_j = 0; $rb_version_j < 2; $rb_version_j++) {
                        $rb_version_item = isset($rb_version_items[$rb_version_i + $rb_version_j]) ? $rb_version_items[$rb_version_i + $rb_version_j] : null;
                    ?>
                    <td><p><?php echo $rb_version_item ? htmlspecialchars($rb_version_item['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '&nbsp;'; ?></p></td>
                    <td><p><?php echo $rb_version_item ? htmlspecialchars($rb_version_item['version'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '&nbsp;'; ?></p></td>
                    <?php } ?>
                </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>
</section>
<?php } ?>

<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
