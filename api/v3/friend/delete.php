<?php
/**
 * 删除一个好友
 */
require_once('../../../header.php');


$invite_id = postv('invite_id');
$email = postv('invite');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

if (!$invite_id) {
    apiout(-3, "invite_id 参数错误");
}



/// 删除好友关联
$db->action(function ($db) {
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

