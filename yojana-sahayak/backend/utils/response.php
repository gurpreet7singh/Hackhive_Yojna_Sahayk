<?php

function jsonResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}


function startSecureSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set("session.use_strict_mode", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/yojana-sahayak/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


function getJsonInput()
{
    $input = json_decode(file_get_contents("php://input"), true);

    return is_array($input) ? $input : [];
}


function requirePost()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        jsonResponse(false, "Method not allowed.", null, 405);
    }
}

?>