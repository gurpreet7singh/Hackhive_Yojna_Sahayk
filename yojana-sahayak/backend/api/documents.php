<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../utils/response.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";
require_once __DIR__ . "/../controllers/DocumentController.php";

$controller = new DocumentController($pdo);

$method = $_SERVER["REQUEST_METHOD"];

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($method === "GET") {

    if ($id) {
        $controller->getOne($id);
    } else {
        $controller->getAll();
    }

} elseif ($method === "POST") {

    requireAdmin();
    $controller->upload();

} elseif ($method === "DELETE") {

    requireAdmin();

    if (!$id) {
        jsonResponse(false, "Document ID is required.", null, 400);
    }

    $controller->delete($id);

} else {

    jsonResponse(false, "Method not allowed.", null, 405);

}