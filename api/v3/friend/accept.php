<?php
/**
 * 同意一个好友请求
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
    
    /// 检查对应的请求是否有效
    if ($invite['dtime'] != 0 || $invite['atime'] != 0 || $invite['rtime'] != 0) {
        LOGD("请求（invite_id={$invite_id}）已失效：" . var_export($invite, TRUE));
        $code = -6;
        $message = '请求已失效';
        return FALSE;
    }
    
    
    /// 根据 invite 给出的信息，创建好友数据
    $data = [
        [
            'friend1_google_uid' => $invite['sender_google_uid'],
            'friend2_google_uid' => $invite['invited_google_uid'],
            'ctime' => time(),
            'invite_id' => $invite['invite_id'],
        ],
        [
            'friend1_google_uid' => $invite['invite_google_uid'],
            'friend2_google_uid' => $invite['sender_google_uid'],
            'ctime' => time(),
            'invite_id' => $invite['invite_id'],
        ],
    ];
    $db->insert('b_friend', $data);
    
    
    /// 更新 invite 数据
    $where = ['invite_id' => $invite['invite_id']];
    $data = ['atime' => time()];
    $db->update('b_invite', $data, $where);
    
    
    $code = 0;
    $message = '好友添加成功';
    
    return TRUE;
});


apiout($code, $message);
?>

