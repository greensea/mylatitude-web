<?php
/**
 * 获取好友列表
 */
require_once('../../../header.php');


$uid = postv('uid');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

/// 查询好友数据
$where = ['AND' => [
    'friend1_google_id' => $user['google_uid'],
    'dtime' => 0,
]];

$friends = array();
$res = $db->select('b_friend', '*', $where);
if ($res) {
    $friends = $res;
}

/// 查询好友的位置信息
foreach ($friends as $k => $v) {
    $friends[$k]['location'] = getLastLocationByGoogleUID($v['google_uid']);
}
$friends = apiDeleteKeys($friends, ['google_uid', 'user_id']);



/// 查询已发邀请的好友数据
$sents = array();
$where = ['AND' => [
    'sender_google_uid' => $user['google_uid'],
    'dtime' => 0,
    'atime' => 0
]];
$res = $db->select('b_invite', '*', $where);
if ($res) {
    $sent = $res;
}
$sents = apiDeleteKeys($sents, ['google_uid', 'user_id']);



/// 查询等待批准的好友数据
$validates = array();
$where = ['AND' => [
    'invited_google_uid' => $user['google_uid'],
    'dtime' => 0,
    'atime' => 0
]];
$res = $db->select('b_invite', '*', $where);
if ($res) {
    $validates = $res;
}
$validates = apiDeleteKeys($sents, ['google_uid', 'user_id']);



$data = [
    'friends' => $friends,
    'sents' => $sents,
    'validates' => $validates,
];

apiout(0, '操作成功', $data);
?>

