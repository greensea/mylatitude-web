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

$friends = getFriendsWithLocationByGoogleUID($user['google_uid']);


/// 查询已发邀请的好友数据
$sents = array();
$where = ['AND' => [
    'sender_google_uid' => $user['google_uid'],
    'dtime' => 0,
    'atime' => 0,
    'rtime' => 0,
]];
$res = $db->select('b_invite', '*', $where);
if ($res) {
    $sents = $res;
}

/// 查询用户数据
foreach ($sents as $k => $v) {
    $u = $db->get('b_user', ['name', 'email', 'google_face'], ['google_uid' => $v['invited_google_uid']]);
    $sents[$k]['invited_user'] = $u;
}



/// 查询等待批准的好友数据
$validates = array();
$where = ['AND' => [
    'invited_google_uid' => $user['google_uid'],
    'dtime' => 0,
    'atime' => 0,
    'rtime' => 0,
]];
$res = $db->select('b_invite', '*', $where);
if ($res) {
    $validates = $res;
}

/// 查询用户数据
foreach ($validates as $k => $v) {
    $u = $db->get('b_user', ['name', 'email', 'google_face'], ['google_uid' => $v['sender_google_uid']]);
    $validates[$k]['sender_user'] = $u;
}



$data = [
    'friends' => $friends,
    'sents' => $sents,
    'validates' => $validates,
];

apiout(0, '操作成功', $data);
?>

