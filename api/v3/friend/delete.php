<?php
/**
 * 删除一个好友
 */
require_once('../../../header.php');


$email = postv('invite');
$uid = postv('uid');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}




/// 删除好友关联
$db->action(function ($db) {
    global $user;
    global $friend;
    
    $where = [
        'OR #cond1' => [
            'AND #cond1.1' => [
                'friend1_google_uid' => $user['google_uid'],
                'friend2_google_uid' => $friend['google_uid'],
                'dtime' => 0,
            ],
            'AND #cond1.2' => [
                'friend1_google_uid' => $user['google_uid'],
                'friend2_google_uid' => $friend['google_uid'],
                'dtime' => 0
            ],
        ]
    ];
    $data = ['dtime' => time()];
    
    $db->update('b_friend', $data, $where);
    
    return true;
});


apiout($code, $message);
?>

