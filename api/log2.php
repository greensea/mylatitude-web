<?php
require_once('../header.php');

if ($my->connect_error) {
    apiout(-1, $my->connect_error);
}


$name = $_GET['name'];
$ctime = $_SERVER['REQUEST_TIME'];

$raw = file_get_contents('php://input');
$j = json_decode($raw, TRUE);
if (!is_array($j)) {
    apiout(-2, "输入数据格式不正确");
}

$user = getByUid($_GET['uid']);
if (!$user) {
    apiout(-1, $_GET['uid'] . '不存在');
    die();
}
$google_uid = $my->real_escape_string($user['google_uid']);
$uid = $my->real_escape_string($_GET['uid']);

foreach ($j['locations'] as $loc) {
    $rtime = (int)$loc['time'];
    $latitude = (double)$loc['latitude'];
    $longitude = (double)$loc['longitude'];
    $accurateness = (int)$loc['accuracy'];
    $altitude = (double)$loc['altitude'];
    
    $sql = "REPLACE INTO b_location
    (name, ctime, rtime, latitude, longitude, accurateness, altitude, google_uid) VALUES 
    ('', ${ctime}, ${rtime}, ${latitude}, ${longitude}, ${accurateness}, ${altitude}, '{$google_uid}', '{$uid}')";
    $ret = $result = $my->query($sql);
    
    if ($ret === FALSE) {
        apiout(-2, $my->error);
        die();
    }
    else {
        $cnt++;
    }
}


apiout(0, '操作成功');
?>

