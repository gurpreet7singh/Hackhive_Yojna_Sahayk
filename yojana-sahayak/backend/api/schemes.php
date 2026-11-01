<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";
require_once __DIR__ . "/../controllers/SchemeController.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";

$controller = new SchemeController($pdo);

$method = $_SERVER["REQUEST_METHOD"];

$id = isset($_GET["id"]) ? filter_var(
    $_GET["id"],
    FILTER_VALIDATE_INT
) : null;


// GET requests are public

if ($method === "GET") {

    if ($id !== null && $id !== false) {
        $controller->getOne($id);
    }

    if (isset($_GET["id"])) {
        jsonResponse(false, "Invalid scheme ID.", null, 422);
    }

    $controller->getAll();
}


// All remaining operations require admin access

if (!in_array($method, ["POST", "PUT", "DELETE"], true)) {
    jsonResponse(false, "Method not allowed.", null, 405);
}

requireAdmin();


// CREATE

if ($method === "POST") {

    $data = getJsonInput();

    $controller->create($data);
}


// UPDATE

if ($method === "PUT") {

    if (!$id || $id === false) {
        jsonResponse(false, "Valid scheme ID is required.", null, 422);
    }

    $data = getJsonInput();

    $controller->update($id, $data);
}


// DELETE

if ($method === "DELETE") {

    if (!$id || $id === false) {
        jsonResponse(false, "Valid scheme ID is required.", null, 422);
    }

    $controller->delete($id);
}

?>