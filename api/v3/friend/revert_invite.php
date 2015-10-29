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

/// 检查这个请求是不是自己的
$invite = $db->get('b_invite', '*', ['invite_id' => $invite_id]);
if ($invite) {
    if ($invite['sender_google_uid'] != $user['google_uid']) {
        LOGD("好友请求（invite_id={$invite_id}） 不是用户(google_uid={$user['google_uid']})的");
        apiout(-3, '请求参数 invite_id 非法');
    }
    
    if ($invite['rtime'] != 0 || $invite['dtime'] != 0 || $invite['atime'] != 0) {
        LOGD("请求的状态不正确: " . var_export($invite));
        apiout(-4, '请求可能已经被撤销或者被对方处理过了');
    }
}
else {
    apiout(-4, '好友请求不存在');
}


/// 撤销请求
$where = ['AND' => [
    'invite_id' => $invite_id,
    'dtime' => 0,
    'atime' => 0,
    'rtime' => 0
]];
$data = ['rtime' => time()];
$db->update('b_invite', $data, $where);

apiout(0, '成功撤销了好友请求');
?>

