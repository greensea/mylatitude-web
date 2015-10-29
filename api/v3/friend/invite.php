<?php
/**
 * 发送一个好友请求
 */
require_once('../../../header.php');


$uid = postv('uid');
$email = postv('email');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

if (!$email) {
    apiout(-3, "好友账户邮箱参数非法");
}

/// 检查好友邮箱是否存在于我们的系统中
$friend = NULL;
$friends = $db->select('b_user', '*', [
    'email' => $email
]);
if (!$friends  || count($friends ) <= 0) {
    apiout(-4, '好友数据不存在');
}
else {
    $friend = $friends[0];
}

/// 创建请求
apiout(0, '成功发送请求 ');

?>

