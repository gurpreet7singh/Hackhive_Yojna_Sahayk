
<?php

// =====================================
// AUTH API
// =====================================

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";
require_once __DIR__ . "/../controllers/AuthController.php";

// =====================================
// INITIALIZE SESSION
// =====================================

startSecureSession();

// =====================================
// INITIALIZE CONTROLLER
// =====================================

$authController = new AuthController($pdo);

// =====================================
// REQUEST METHOD & ACTION
// =====================================

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$action = $_GET["action"] ?? "";

// =====================================
// READ JSON INPUT
// =====================================

$input = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($input)) {
    $input = [];
}

// =====================================
// ROUTING
// =====================================

switch ($action) {

    case "register":

        if ($method !== "POST") {
            jsonResponse(false, "Method not allowed.", null, 405);
        }

        $authController->register($input);
        break;


    case "login":

        if ($method !== "POST") {
            jsonResponse(false, "Method not allowed.", null, 405);
        }

        $authController->login($input);
        break;


    case "logout":

        if ($method !== "POST") {
            jsonResponse(false, "Method not allowed.", null, 405);
        }

        $authController->logout();
        break;


    case "me":

        if ($method !== "GET") {
            jsonResponse(false, "Method not allowed.", null, 405);
        }

        $authController->me();
        break;


    default:

        jsonResponse(false, "Invalid authentication action.", null, 404);
        break;
}

?>
