<?php
$MYSQL_HOST = 'YOUR_MYSQL_HOST';
$MYSQL_USER = 'YOUR_MYSQL_USERNAME';
$MYSQL_PASSWORD = 'YOUR_MYSQL_PASSWORD';
$MYSQL_DATABASE = 'YOUR_MYSQL_DATABASE_NAME';

$LOG_PATH = '/var/log/latitude.log';
$LOG_LEVEL = LOG_DEBUG;

/**
 * 计算用户移动距离时，只使用精度高于（即定位精度值比此处设定的值更小）此处设定值的位置信息进行计算
 */
$MIN_DISTANCE_ACCURATENESS = 1000;

$GOOGLE_CLIENT_ID = 'YOUR_GOOGLE_APP_CLIENT_ID';



?>
