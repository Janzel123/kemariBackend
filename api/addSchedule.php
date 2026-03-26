<?php
// File: kemaribackend/api/addSchedule.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['request_id'], $data['start_date'], $data['end_date'])) {
    echo json_encode(["status" => "error", "message" => "Missing required data"]);
    exit;
}

$request_id = $data['request_id'];
$start = $data['start_date'];
$end = $data['end_date'];

try {
    $sql = "INSERT INTO schedules (request_id, start_date, end_date) 
            VALUES (:request_id, :start, :end)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':request_id' => $request_id,
        ':start' => $start,
        ':end' => $end
    ]);

    echo json_encode(["status" => "success", "message" => "Schedule added"]);
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}