<?php
/**
 * 获取好友列表
 */
require_once('../../../header.php');


$uid = getv('uid');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

/// 获取好友位置信息
$friends = getFriendsWithLocationByGoogleUID($user['google_uid']);


/// 获取我的轨迹
$where = [
    'AND' => [
        'google_uid' => $user['google_uid'],
        'rtime[>]' => time() - 86400,
    ],
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
$mytracks = array();
if (count($tracks) > 30) {
    $step = round(count($tracks) / 30) + 1;
    for ($i = 0; $i < count($tracks); $i += $step) {
        $mytracks[] = $tracks[$i];
    }
}
else {
    $mytracks = $tracks;
}

array_reverse($mytracks);

$data = [
    'tracks' => $mytracks,
    'friends' => $friends
];

apiout(0, '操作成功', $data);
?>

