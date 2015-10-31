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
    'ORDER' => 'rtime ASC'
];
$tracks = $db->select('b_location', ['latitude', 'longitude', 'accuracy', 'rtime'], $where);
if (!$tracks) {
    $tracks = array();
}

/// 精简轨迹，最多返回 30 个轨迹信息
if (count($tracks) > 30) {
    $step = round(count($tracks) / 30) + 1;
    for ($i = 0; $i < count($tracks) - 1; $i += $step) {
        unset($tracks[$i]);
    }
}
    

$data = [
    'tracks' => $tracks,
    'friends' => $friends
];

apiout(0, '操作成功', $friends);
?>

