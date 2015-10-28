<?php
require_once('lib/func.php');
require_once('lib/medoo.php');

$my = new mysqli('latitude.greensea.org', 'latitude', 'WZCy8uR63pvdAdRN', 'latitude');
$db = new medoo([
    'database_type' => 'mysql',
    'database_name' => 'latitude',
    'server' => 'latitude.greensea.org',
    'username' => 'latitude',
    'password' => 'WZCy8uR63pvdAdRN',
    'charset' => 'utf8'
]);
    

$GOOGLE_CLIENT_ID = '1026836454706-vmm6iis0p8rsic5p7786ov7nrh04f1ee.apps.googleusercontent.com';

$GOOGLE_JWT_KEYS = google_jwt_keys();

?>
