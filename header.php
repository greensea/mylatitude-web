<?php
require_once('lib/func.php');

$my = new mysqli('latitude.greensea.org', 'latitude', 'WZCy8uR63pvdAdRN', 'latitude');

$GOOGLE_CLIENT_ID = '1026836454706-vmm6iis0p8rsic5p7786ov7nrh04f1ee.apps.googleusercontent.com';

$GOOGLE_JWT_KEYS = google_jwt_keys();

?>
