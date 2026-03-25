<?php
// ✅ CORS HEADERS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// ✅ HANDLE PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/db.php");

// GET DATA
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password)) {
    echo json_encode(["message" => "Missing fields"]);
    exit;
}

$username = trim($data->username);
$password = trim($data->password);

try {
    // FIND ADMIN
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // VERIFY PASSWORD
    if ($admin && password_verify($password, $admin['password'])) {
        echo json_encode([
            "message" => "Login successful",
            "admin" => [
                "id" => $admin['id'],
                "username" => $admin['username']
            ]
        ]);
    } else {
        echo json_encode(["message" => "Invalid credentials"]);
    }

} catch (PDOException $e) {
    echo json_encode(["message" => "Server error"]);
}