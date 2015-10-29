<?php
/**
 * 拒绝一个好友请求
 */
require_once('../../../header.php');


$invite_id = postv('invite_id');
$uid = postv('uid');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

if (!$invite_id) {
    apiout(-3, "invite_id 参数错误");
}


/// 在发送好友请求的时候已经检查过账户合法性了，在此可以不用检查
$code = 0;
$message = '操作成功';
$db->action(function($db) {
    global $invite_id;
    global $code;
    global $message;
    global $user;
    
    $res = $db->select('b_invite', '*', ['invite_id' => $invite_id]);
    if (!$res || count($res) <= 0) {
        $code = -4;
        $message = "invite_id={$invite_id} 不存在";
        return FALSE;
    }
    
    $invite = $res[0];
    
    
    /// 检查对应的请求是不是属于自己的
    if ($invite['invited_google_id'] != $user['google_id']) {
        LOGD("请求编号（{$invite_id}）对应的接收请求的用户不是当前用户（当前用户 google_uid={$user['google_uid']})");
        $code = -5;
        $message = 'invite_id 参数错误';
        return false;
    }
    
    
    /// 修改 invite 数据

    
    /// 更新 invite 数据
    $where = ['invite_id' => $invite['invite_id']];
    $data = ['dtime' => time()];
    $db->update('b_invite', $data, $where);
    
    
    $code = 0;
    $message = '拒绝了好友的请求';
    
    return TRUE;
});


apiout($code, $message);
?>

