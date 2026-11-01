
<?php

require_once __DIR__ . "/../controllers/ChatController.php";
require_once __DIR__ . "/../middleware/authMiddleware.php";
require_once __DIR__ . "/../utils/response.php";

header("Content-Type: application/json");

$controller = new ChatController();

$method = $_SERVER["REQUEST_METHOD"];


/* =========================================
   GET REQUEST
   Retrieve sessions or messages
========================================= */

if ($method === "GET") {

    /*
        ?action=sessions
        ?action=messages&session_id=1
    */

    $action = $_GET["action"] ?? "";

    $userId = requireAuth();


    if ($action === "sessions") {

        $result = $controller->getSessions($userId);

        jsonResponse(
            $result["success"],
            $result["message"] ?? "Sessions retrieved.",
            $result["sessions"] ?? null
        );

    }


    elseif ($action === "messages") {

        $sessionId = filter_var(
            $_GET["session_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$sessionId) {

            jsonResponse(false, "Valid session ID is required.", null, 400);

        }

        $result = $controller->getMessages($sessionId, $userId);

        jsonResponse(
            $result["success"],
            $result["message"] ?? "Messages retrieved.",
            $result["messages"] ?? null,
            $result["success"] ? 200 : 404
        );

    }


    else {

        jsonResponse(false, "Invalid GET action.", null, 400);

    }

}


/* =========================================
   POST REQUEST
   Create session or save messages
========================================= */

elseif ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {

        jsonResponse(false, "Invalid JSON request.", null, 400);

    }


    $action = $data["action"] ?? "";


    /*
        Guests can create temporary chat sessions.
        Registered users create sessions linked to user_id.
    */

    if ($action === "create_session") {

        startSecureSession();

        $userId = $_SESSION["user_id"] ?? null;

        $language = $data["language"] ?? "en";

        if (!in_array($language, ["en", "hi"], true)) {

            jsonResponse(false, "Invalid language.", null, 400);

        }

        $result = $controller->createSession(
            $userId ? (int) $userId : null,
            $language
        );

        jsonResponse(
            $result["success"],
            $result["message"] ?? "Session created.",
            $result["session_id"] ?? null,
            $result["success"] ? 201 : 500
        );

    }


    elseif ($action === "save_message") {

        $userId = requireAuth();

        $sessionId = filter_var(
            $data["session_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $sender = $data["sender"] ?? "";

        $message = trim($data["message"] ?? "");

        $status = $data["status"] ?? "complete";


        if (!$sessionId || $message === "") {

            jsonResponse(false, "Session ID and message are required.", null, 400);

        }


        if (!in_array($sender, ["user", "assistant", "system"], true)) {

            jsonResponse(false, "Invalid message sender.", null, 400);

        }


        if (!in_array($status, ["pending", "complete", "error"], true)) {

            jsonResponse(false, "Invalid response status.", null, 400);

        }


        /*
            Verify session belongs to this user.
        */

        $result = $controller->getMessages($sessionId, $userId);

        /*
            getMessages verifies ownership.
            An empty session is valid and returns success.
        */

        if (!$result["success"]) {

            jsonResponse(false, "Chat session not found.", null, 404);

        }


        $saved = $controller->saveMessage(
            $sessionId,
            $sender,
            $message,
            $status
        );


        jsonResponse(
            $saved["success"],
            $saved["message"] ?? "Message saved.",
            $saved["message_id"] ?? null,
            $saved["success"] ? 201 : 500
        );

    }


    else {

        jsonResponse(false, "Invalid POST action.", null, 400);

    }

}


/* =========================================
   PUT REQUEST
   Update conversation title
========================================= */

elseif ($method === "PUT") {

    $userId = requireAuth();

    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {

        jsonResponse(false, "Invalid JSON request.", null, 400);

    }


    $sessionId = filter_var(
        $data["session_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $title = trim($data["title"] ?? "");


    if (!$sessionId || $title === "") {

        jsonResponse(false, "Session ID and title are required.", null, 400);

    }


    $result = $controller->updateSessionTitle(
        $sessionId,
        $userId,
        $title
    );


    jsonResponse(
        $result["success"],
        $result["success"]
            ? "Session title updated."
            : "Chat session not found.",
        null,
        $result["success"] ? 200 : 404
    );

}


/* =========================================
   DELETE REQUEST
   Delete conversation
========================================= */

elseif ($method === "DELETE") {

    $userId = requireAuth();

    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {

        jsonResponse(false, "Invalid JSON request.", null, 400);

    }


    $sessionId = filter_var(
        $data["session_id"] ?? null,
        FILTER_VALIDATE_INT
    );


    if (!$sessionId) {

        jsonResponse(false, "Valid session ID is required.", null, 400);

    }


    $result = $controller->deleteSession(
        $sessionId,
        $userId
    );


    jsonResponse(
        $result["success"],
        $result["message"] ?? "Session deleted.",
        null,
        $result["success"] ? 200 : 404
    );

}


/* =========================================
   UNSUPPORTED METHODS
========================================= */

else {

    jsonResponse(false, "Method not allowed.", null, 405);

}

?>
