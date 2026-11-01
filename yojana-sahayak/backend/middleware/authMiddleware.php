<?php

require_once __DIR__ . "/../utils/response.php";


function requireAuth()
{
    startSecureSession();

    if (!isset($_SESSION["user_id"])) {
        jsonResponse(false, "Authentication required.", null, 401);
    }

    return (int) $_SESSION["user_id"];
}


function requireAdmin()
{
    $userId = requireAuth();

    if (($_SESSION["user_role"] ?? "") !== "admin") {
        jsonResponse(false, "Admin access required.", null, 403);
    }

    return $userId;
}

?>