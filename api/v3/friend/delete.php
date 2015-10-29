<?php
/**
 * 删除一个好友
 */
require_once('../../../header.php');


$email = postv('email');
$uid = postv('uid');
$user = getByUID($uid);
if (!$user) {
    LOGD("(uid={$uid}）找不到对应的用户");
    apiout(-2, '你还没有登录');
}

$where = [
    'AND' => ['email' => $email],
    'ORDER' => 'user_id DESC'
];
$friend = $db->get('b_user', '*', $where);
if (!$friend) {
    apiout(-10, 'email参数错误');
}



/// 删除好友关联
$db->action(function ($db) {
    global $user;
    global $friend;
    global $code;
    global $message;
    
    $where = [
        'OR #cond1' => [
            'AND #cond1.1' => [
                'friend1_google_uid' => $user['google_uid'],
                'friend2_google_uid' => $friend['google_uid'],
                'dtime' => 0,
            ],
            'AND #cond1.2' => [
                'friend1_google_uid' => $friend['google_uid'],
                'friend2_google_uid' => $user['google_uid'],
                'dtime' => 0
            ],
        ]
    ];
    $data = ['dtime' => time()];
    
    $ret = $db->update('b_friend', $data, $where);
    if ($ret === FALSE) {
        $msg = '数据库操作失败: ' . var_export($db->error(), TRUE) . ' (' . $db->last_query() . ')';
        $code = -11;
        $message = '数据库操作失败: ' . $msg;
        return FALSE;
    }
    
    return true;
});


apiout($code, $message);
?>

