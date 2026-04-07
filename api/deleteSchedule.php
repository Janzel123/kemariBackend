<?php
// File: kemaribackend/api/deleteSchedule.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['schedule_id']) || empty($data['schedule_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing schedule_id"]);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = :schedule_id");
    $stmt->bindParam(':schedule_id', $data['schedule_id'], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Schedule deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Schedule not found"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}