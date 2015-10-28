<?php
/**
 * 保存位置信息第三版，供 com.greensea.pgs 的 Java 服务使用
 * 
 * POST 参数：
 *  locations: <JSON>
 * 其中 <JSON> 为: [<LOCATION>, ...]
 * 其中　location 为: {latitude: .0, longitude: .0, altitude: .0, accuracy: .0, time: 0L, src: ""}
 * 
 * GET 参数:
 *  uid: APP 本地保存的 unique id，对应我们这边的 uid
 * 
 * 如果操作成功应返回　"ok"，即　die("ok");
 * 如果操作失败应返回错误信息和描述
 */
require_once('../header.php');

if ($my->connect_error) {
    apiout(-1, $my->connect_error);
    die();
}


$name = isset($_GET['name']) ? $_GET['name'] : '';
$ctime = $_SERVER['REQUEST_TIME'];

$raw = $_POST['locations'];
$j = json_decode($raw, TRUE);
if (!is_array($j)) {
    apiout(-2, "输入数据格式不正确");
    die();
}

$user = getByUID($_GET['uid']);
if (!$user) {
    apiout(-3, "uid`{$_GET['uid']}'不存在");
    die();
}
$google_uid = $my->real_escape_string($user['google_uid']);
$uid = $my->real_escape_string($_GET['uid']);

foreach ($j as $loc) {
    $rtime = (int)$loc['time'];
    $rtime /= 1000;
    $latitude = (double)$loc['latitude'];
    $longitude = (double)$loc['longitude'];
    $accurateness = (int)$loc['accuracy'];
    $altitude = (double)$loc['altitude'];
    $src = $my->real_escape_string($loc['src']);
    
    $sql = "REPLACE INTO b_location
    (name, ctime, rtime, latitude, longitude, accurateness, altitude, google_uid, uid, src) VALUES 
    ('', ${ctime}, ${rtime}, ${latitude}, ${longitude}, ${accurateness}, ${altitude}, '{$google_uid}', '{$uid}', '{$src}')";
    $ret = $result = $my->query($sql);
file_put_contents("/tmp/abc", $sql . "\n");
    
    if ($ret === FALSE) {
        apiout(-2, $my->error . "({$sql})");
        die();
    }
    else {
        $cnt++;
    }
}


die('ok');
?>

