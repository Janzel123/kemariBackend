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

// Read JSON input
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

// Required fields
$required = ['name', 'email', 'message'];
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
$company = $data->company ?? "";
$phone = $data->phone ?? "";
$subject = $data->subject ?? "No Subject";

// Prepare SQL
$sql = "INSERT INTO messages (name, company, email, phone, subject, message)
        VALUES (:name, :company, :email, :phone, :subject, :message)";

$stmt = $conn->prepare($sql);

try {
    $stmt->execute([
        ':name' => $data->name,
        ':company' => $company,
        ':email' => $data->email,
        ':phone' => $phone,
        ':subject' => $subject,
        ':message' => $data->message
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Message saved successfully"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>