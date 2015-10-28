<?php
/**
 * 发送一个好友请求
 */
require_once('../../../header.php');


$uid = postv('uid');
$email = postv('invite');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

if (!$email) {
    apiout(-3, "请输入邀请人的邮箱");
}

/// 检查对方用户名是否存在于我们的系统中
$friend = NULL;
$friends = $db->select('b_user', '*', [
    'email' => $email
]);
if (!$friends  || count($friends ) <= 0) {
    apiout(-4, '对方还没有开始使用我的纵横，无法邀请');
}
else {
    $friend = $friends[0];
}

/// 检查是否存在未批准或拒绝的邀请
$res = $db->select('b_invite', '*', [
    'sender_google_uid' => $user['google_uid'],
    'invited_google_uid' => $friend['google_uid'],
    'dtime' => 0,
    'atime' => 0,
]);
if ($res && count($res) > 0) {
    apiout(-5, '您已经给对方发送过邀请了，正在等待对方回应');
}

/// 检查双方是否已经是好友了
$res = $db->select('b_friend', '*', [
    'friend1_google_uid' => $user['google_uid'],
    'friend2_google_uid' => $friend['google_uid'],
    'dtime' => 0,
]);
if ($res && count($res) > 0) {
    apiout(-6, '您和对方已经是好友了');
}

/// 检查是否企图将自己添加为好友
if ($friend['google_uid'] == $user['google_uid']) {
    apiout(-7, '不能添加自己为好友');
}


/// 添加邀请
$data = [
    'ctime' => time(),
    'sender_google_uid' => $user['google_uid'],
    'invited_google_uid' => $friend['google_uid'],
];
$db->insert('b_invite', $data);

apiout(0, '已经向对方发送了邀请');

?>

