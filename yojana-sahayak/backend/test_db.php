<?php

require_once "config/database.php";

try {

    // Test database connection

    $query = $pdo->query("SELECT DATABASE() AS database_name");

    $result = $query->fetch();

    echo "<h2>Database Connection Successful!</h2>";

    echo "Connected Database: " . $result['database_name'];

    echo "<br><br>";

    // Count database tables

    $query = $pdo->query("SHOW TABLES");

    $tables = $query->fetchAll();

    echo "<h3>Available Tables:</h3>";

    foreach ($tables as $table) {

        echo htmlspecialchars(array_values($table)[0]) . "<br>";

    }

    echo "<br>Total Tables: " . count($tables);

} catch (PDOException $e) {

    echo "Error: " . htmlspecialchars($e->getMessage());

}

?>