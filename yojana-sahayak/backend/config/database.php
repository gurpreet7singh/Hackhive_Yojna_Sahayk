<?php

// Database configuration

$host = "localhost";
$dbname = "yojana_sahayak";
$username = "root";
$password = "";

try {

    // Create PDO connection

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Configure PDO error handling

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Fetch results as associative arrays

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    // Log the actual error; don't expose database details to users.

    error_log("Database connection failed: " . $e->getMessage());

    http_response_code(500);

    die("Database connection failed. Please try again later.");

}

?>