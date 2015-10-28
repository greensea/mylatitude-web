<?php
require_once('../lib/phpjwt/JWT.php');
require_once('../header.php');

$token =  $_POST['token'];
$uid = $_POST['uid'];

$data = NULL;
try {
    $data = Firebase\JWT\JWT::decode($token, $GOOGLE_JWT_KEYS);
}
catch (Exception $e) {
    /// 可能是 Google JWT keys 过期了，刷新并重试
    LOGI("检验 google token 失败，可能是 JWT key 过期了，刷新 keys 后重试");
    google_jwt_keys_refresh();
    $GOOGLE_JWT_KEYS = google_jwt_keys();
    
    try {
        $data = Firebase\JWT\JWT::decode($token, $GOOGLE_JWT_KEYS);
    }
    catch ($e2) {
        apiout(-4, "解码 google token 出错：" . $e2->getMessage())
    }
}



$gdata = json_decode(json_encode($data), TRUE);

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
$sql = sprintf("INSERT INTO b_user (uid, google_uid, name, email, google_face, ctime) VALUES ('%s', '%s', '%s', '%s', '%s', %ld)",
            $uid, $my->real_escape_string($gdata['sub']), $my->real_escape_string($gdata['name']), $my->real_escape_string($gdata['email']), $my->real_escape_string($gdata['picture']), time());
$my->query($sql) or die($my->error . " ($sql)");

apiout(0, '操作成功');
?>
