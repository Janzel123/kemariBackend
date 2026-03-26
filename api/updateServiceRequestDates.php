<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

include "../config/db.php";

// Handle OPTIONS (CORS fix)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    echo json_encode(["status" => "ok"]);
    exit;
}

// Read JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Debug
if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "No input received",
        "raw" => $raw
    ]);
    exit;
}

$id = $data['id'] ?? null;
$start = $data['start_date'] ?? null;
$end = $data['end_date'] ?? null;

if (!$id || !$start || !$end) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing parameters"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE service_requests 
        SET start_date = :start, end_date = :end 
        WHERE id = :id
    ");

    $stmt->execute([
        ":start" => $start,
        ":end" => $end,
        ":id" => $id
    ]);

    echo json_encode([
        "status" => "success"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>