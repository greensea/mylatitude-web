<?php
require_once('lib/func.php');
require_once('lib/medoo.php');

if (!file_exists(__DIR__ . '/config.php')) {    
    die('请先创建配置文件 config.php，配置文件模版可以使用　config.sample.php');
}
else {
    require_once('config.php');
}


$my = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DATABASE);
$db = new medoo([
    'database_type' => 'mysql',
    'database_name' => $MYSQL_DATABASE,
    'server' => $MYSQL_HOST,
    'username' => $MYSQL_USER,
    'password' => $MYSQL_PASSWORD,
    'charset' => 'utf8'
]);
    

$GOOGLE_JWT_KEYS = google_jwt_keys();

?>
