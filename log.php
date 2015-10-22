<?php
require_once('header.php');

if ($my->connect_error) {
    die(json_encode(array(
        'code' => -1,
        'message' => $my->connect_error
    )));
}

if (!isset($_GET['name'])) {
    die(json_encode(array(
        'code' => -3,
        'message' => '必须提供用户名'
    ), JSON_UNESCAPED_UNICODE));
}

$name = $_GET['name'];
$ctime = $_SERVER['REQUEST_TIME'];

$raw = file_get_contents('php://input');
$cnt = 0;
str_replace('\r', '\n', $raw);
$lines = explode('\n', $raw);
foreach ($lines as $line) {
    if (strlen($line) == 0) {
        continue;
    }
    
    $fields = explode(',', $line);

    $name = $my->real_escape_string($name);
    $rtime = (int)$fields[0];
    $latitude = (double)$fields[1];
    $longitude = (double)$fields[2];
    $accurateness = (int)$fields[3];
    $altitude = (double)$fields[4];
    
    $sql = "REPLACE INTO b_location
    (name, ctime, rtime, latitude, longitude, accurateness, altitude) VALUES 
    ('{$name}', ${ctime}, ${rtime}, ${latitude}, ${longitude}, ${accurateness}, ${altitude})";
    $ret = $result = $my->query($sql);
    if ($ret === FALSE) {
        die(json_encode(array(
            'code' => '-2',
            'message' => $my->error
        )));
    }
    else {
        $cnt++;
    }
}


die(json_encode(array(
    'code' => 0,
    'data' => array(
        'cnt' => $cnt
    )
)));
?>

