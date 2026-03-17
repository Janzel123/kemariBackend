<?php
// --- CORS headers for React frontend ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (!isset($data->id) || !isset($data->status)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

try {
    $sql = "UPDATE messages SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':status' => $data->status,
        ':id' => $data->id
    ]);

    echo json_encode(["status" => "success"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>