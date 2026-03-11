<?php
// --- CORS headers for React dev server ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

// --- Rest of your code remains the same ---
$input = file_get_contents("php://input");
$data = json_decode($input);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON or no data received",
        "raw_input" => $input
    ]);
    exit;
}

// Validate required fields
$required = ['name', 'email', 'subject', 'message'];
foreach ($required as $field) {
    if (empty($data->$field)) {
        echo json_encode([
            "status" => "error",
            "message" => "Field '$field' is required",
            "received" => $data
        ]);
        exit;
    }
}

// Prepare SQL
$sql = "INSERT INTO messages (name,email,subject,message) VALUES (:name,:email,:subject,:message)";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute([
        ':name' => $data->name,
        ':email' => $data->email,
        ':subject' => $data->subject,
        ':message' => $data->message
    ]);
    echo json_encode(["status" => "success"]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>