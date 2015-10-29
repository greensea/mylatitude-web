<?php
/**
 * 撤销一个好友请求
 */
require_once('../../../header.php');


$uid = postv('uid');
$invite_id = postv('invite_id');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}


/// 撤销请求
$where = ['AND' => [
    'sender_google_uid' => $user['google_uid'],
    'invited_google_uid' => $friend['google_uid'],
    'dtime' => 0,
    'atime' => 0,
    'rtime' => 0
]];
$data = ['rtime' => time()];
$db->update('b_invite', $data, $where);

apiout(0, '成功撤销了好友请求');
?>

