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


apiout(0, '操作成功', $friends);
?>

