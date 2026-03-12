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

// Read input JSON
$input = file_get_contents("php://input");
$data = json_decode($input);

// Validate JSON
if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON or no data received",
        "raw_input" => $input
    ]);
    exit;
}

// Honeypot check
if (!empty($data->website)) {
    // Likely spam
    echo json_encode(["status" => "error", "message" => "Spam detected"]);
    exit;
}

// Required fields
$required = ['name', 'email', 'phone', 'service'];
foreach ($required as $field) {
    if (empty($data->$field)) {
        echo json_encode([
            "status" => "error",
            "message" => "Field '$field' is required"
        ]);
        exit;
    }
}

// Optional fields
$message = isset($data->message) ? $data->message : "";

// Prepare SQL
$sql = "INSERT INTO service_requests (name, email, phone, service, message) 
        VALUES (:name, :email, :phone, :service, :message)";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute([
        ':name' => $data->name,
        ':email' => $data->email,
        ':phone' => $data->phone,
        ':service' => $data->service,
        ':message' => $message
    ]);

    echo json_encode(["status" => "success"]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>