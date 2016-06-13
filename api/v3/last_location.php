<?php
/**
 * 获取用户最新的位置信息
 */
require_once('../../header.php');

if ($my->connect_error) {
    apiout(-1, $my->connect_error);
    die();
}


$uid = $_GET['uid'];

$uid_qs = $my->real_escape_string($uid);

$sql = "SELECT * FROM b_user WHERE uid='{$uid_qs}'";
$res = $my->query($sql);
if (!$res) {
    apiout(-2, $my->error . "(${sql}");
    die();
}
$user = $res->fetch_array();
if (!$user) {
    apiout(-3, "uid=`{$uid}'的用户不存在");
    die();
}

$google_uid_qs = $my->real_escape_string($user['google_uid']);


$sql = "SELECT * FROM b_location WHERE google_uid='{$google_uid_qs}' ORDER BY rtime DESC LIMIT 1";
$res = $my->query($sql);
if (!$res) {
    apiout(-4, $my->error . "(${sql})");
    die();
}

$loc = $res->fetch_array(MYSQLI_ASSOC);
if (!$loc) {
    apiout(-4, "uid=`{$uid}' 的用户未上报任何位置(google_uid=`{$user['google_uid']}')");
    die();
}
else {
    unset($loc['uid']);
    
    $stat = getUserStatData($uid, ['distance', 'distance_per_day']);
    $loc['stat'] = $stat;
    
    
    apiout(0, "操作成功", $loc);
    die();
}
?>

