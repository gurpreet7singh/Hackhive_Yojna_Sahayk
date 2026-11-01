<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";
require_once __DIR__ . "/../controllers/AuthController.php";

requirePost();

startSecureSession();

$auth = new AuthController($pdo);

$data = getJsonInput();

$auth->register($data);

?>