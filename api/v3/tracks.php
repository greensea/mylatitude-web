<?php
/**
 * 获取好友列表
 */
require_once('../../header.php');


$uid = getv('uid');
$date = getv('date', '');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

$cond = [
    'rtime[>]' => time() - 86400
];
if ($date != '') {
    $stime = strtotime("$date 00:00:00");
    $etime = strtotime("$date 23:59:59");
    if ($stime !== FALSE && $etime !== FALSE) {
        $cond = [
            'rtime[>=]' => $stime,
            'rtime[<=]' => $etime
        ];
    }
}
$cond['google_uid'] = $user['google_uid'];

/// 获取我的轨迹
$where = [
    'AND' => $cond,
    'ORDER' => 'rtime DESC'
];
$tracks = $db->select('b_location', ['latitude', 'longitude', 'rtime'], $where);
if (!$tracks) {
    $tracks = array();
}

foreach ($tracks as $k => $v) {
    $tracks[$k]['latitude'] =(float)$v['latitude'];
    $tracks[$k]['longitude'] =(float)$v['longitude'];
}

/// 精简轨迹，最多返回 30 个轨迹信息
$mytracks = compressTracks($tracks);


array_reverse($mytracks);

$data = [
    'tracks' => $mytracks,
];

apiout(0, '操作成功', $data);
?>

