
<?php

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../utils/response.php";

class AuthController
{
    private $userModel;

    public function __construct($pdo)
    {
        $this->userModel = new User($pdo);
    }

    // =====================================
    // REGISTER
    // =====================================

    public function register($data)
    {
        $name = trim($data["full_name"] ?? "");
        $email = strtolower(trim($data["email"] ?? ""));
        $password = $data["password"] ?? "";
        $confirmPassword = $data["confirm_password"] ?? "";

        // Validate name
        if (
            $name === "" ||
            strlen($name) > 120 ||
            !preg_match("/\p{L}/u", $name)
        ) {
            jsonResponse(false, "Please enter a valid name.", null, 422);
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, "Please enter a valid email address.", null, 422);
        }

        // Validate password
        if (strlen($password) < 8) {
            jsonResponse(
                false,
                "Password must contain at least 8 characters.",
                null,
                422
            );
        }

        if (strlen($password) > 72) {
            jsonResponse(
                false,
                "Password must not exceed 72 characters.",
                null,
                422
            );
        }

        if ($password !== $confirmPassword) {
            jsonResponse(false, "Passwords do not match.", null, 422);
        }

        // Check whether email already exists
        if ($this->userModel->findByEmail($email)) {
            jsonResponse(
                false,
                "An account with this email already exists.",
                null,
                409
            );
        }

        // Hash password securely
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!$passwordHash) {
            jsonResponse(false, "Unable to secure your password.", null, 500);
        }

        // Create user
        try {

            $user = $this->userModel->create(
                $name,
                $email,
                $passwordHash
            );

            jsonResponse(
                true,
                "Registration successful. Please log in.",
                ["user" => $user],
                201
            );

        } catch (PDOException $e) {

            // Handle duplicate email race condition
            if ($e->getCode() === "23000") {
                jsonResponse(
                    false,
                    "This email is already registered.",
                    null,
                    409
                );
            }

            error_log("Registration error: " . $e->getMessage());

            jsonResponse(
                false,
                "Registration failed. Please try again.",
                null,
                500
            );
        }
    }

    // =====================================
    // LOGIN
    // =====================================

    public function login($data)
    {
        $email = strtolower(trim($data["email"] ?? ""));
        $password = $data["password"] ?? "";

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $password === ""
        ) {
            jsonResponse(
                false,
                "Please enter valid login credentials.",
                null,
                422
            );
        }

        // Find user
        $user = $this->userModel->findByEmail($email);

        // Verify credentials
        if (
            !$user ||
            !password_verify($password, $user["password_hash"])
        ) {
            jsonResponse(
                false,
                "Invalid email or password.",
                null,
                401
            );
        }

        // Check account status
        if ($user["status"] !== "active") {
            jsonResponse(
                false,
                "Your account is inactive.",
                null,
                403
            );
        }

        // Prevent session fixation
        session_regenerate_id(true);

        // Store authenticated user information
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_role"] = $user["role"];
        $_SESSION["user_name"] = $user["full_name"];
        $_SESSION["user_email"] = $user["email"];

        // Never return password hash to frontend
        unset($user["password_hash"]);

        jsonResponse(
            true,
            "Login successful.",
            ["user" => $user]
        );
    }

    // =====================================
    // LOGOUT
    // =====================================

    public function logout()
    {
        // Clear session data
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                [
                    "expires" => time() - 42000,
                    "path" => $params["path"],
                    "domain" => $params["domain"],
                    "secure" => $params["secure"],
                    "httponly" => $params["httponly"],
                    "samesite" => $params["samesite"]
                ]
            );
        }

        // Destroy session
        session_destroy();

        jsonResponse(true, "Logged out successfully.");
    }

    // =====================================
    // CURRENT USER
    // =====================================

    public function me()
    {
        // Check session
        if (!isset($_SESSION["user_id"])) {
            jsonResponse(
                false,
                "You are not logged in.",
                null,
                401
            );
        }

        // Retrieve user from database
        $user = $this->userModel->findById($_SESSION["user_id"]);

        // Validate account
        if (!$user || $user["status"] !== "active") {

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    "",
                    [
                        "expires" => time() - 42000,
                        "path" => $params["path"],
                        "domain" => $params["domain"],
                        "secure" => $params["secure"],
                        "httponly" => $params["httponly"],
                        "samesite" => $params["samesite"]
                    ]
                );
            }

            session_destroy();

            jsonResponse(
                false,
                "Your session is no longer valid.",
                null,
                401
            );
        }

        // Refresh session information
        $_SESSION["user_name"] = $user["full_name"];
        $_SESSION["user_email"] = $user["email"];
        $_SESSION["user_role"] = $user["role"];

        // Remove sensitive information
        unset($user["password_hash"]);

        jsonResponse(
            true,
            "User retrieved successfully.",
            ["user" => $user]
        );
    }
}

?>
