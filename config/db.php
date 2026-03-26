<?php
header("Content-Type: application/json");

$host = "localhost";
$dbname = "kemaricorp";
$username = "root";
$password = "";
$charset = "utf8mb4"; // modern charset for full Unicode support

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES => false, // use real prepared statements
];

try {
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Return JSON error (frontend can handle it)
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}
?>