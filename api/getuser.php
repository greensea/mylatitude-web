<?php
require_once('../header.php');

$code = 0;
$message = '';
$data = NULL;

$res = $my->query(sprintf("SELECT * FROM b_user WHERE uid='%s'", $my->real_escape_string($_GET['uid']))) or die($my->error);
$row = $res->fetch_array();
if ($row) {
    $data = $row;
    $code = 0;
    $data = array(
        'user' => $row
    );
}
else {
    $code = -1;
    $message = '用户不存在';
}
apiout($code, $message, $data);
?>
