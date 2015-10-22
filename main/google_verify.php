<?php
require_once('../lib/phpjwt/JWT.php');
require_once('../header.php');

$token =  $_POST['token'];
$uid = $_POST['uid'];

$data = NULL;
try {
    $data = Fireebase\JWT\JWT::decode($token, $GOOGLE_JWT_KEYS);
}
catch (Exception $e) {
    die(json_encode(array(
        'code' => -4,
        'message' => "解码 google token 出错：" . var_export($e, TRUE),
    ), TRUE));
}

$gdata = $data;

/// 检查 Google 数据是否合法 
if (time() > $gdata['exp']) {
    apiout(-1, "token 已经过期");
    die();
}

if (strcmp($gdata['aud'], $GOOGLE_CLIENT_ID) != 0) {
    apiout(-2, "Google CLIENT ID 不匹配");
    die();
}


/// 在数据库中创建对应的记录
$sql = sprintf("INSERT INTO b_user (uid, google_uid, name, email, google_face, ctime) VALUES ('%s', '%s', '%s', '%s', %ld)",
            $uid, $my->real_escape_string($gdata['sub']), $my->real_escape_string($gdata['name']), $my->real_escape_string($gdata['email']), $my->real_escape_string($gdata['picture']), time());
$my->query($sql) or die($my->error);

apiout(0, '操作成功');
?>
