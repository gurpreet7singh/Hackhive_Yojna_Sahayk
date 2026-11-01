<?php

require_once __DIR__ . "/../utils/response.php";
require_once __DIR__ . "/../controllers/AuthController.php";
require_once __DIR__ . "/../config/database.php";

requirePost();

startSecureSession();

$auth = new AuthController($pdo);

$auth->logout();

?>