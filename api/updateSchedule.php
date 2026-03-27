<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    !$data ||
    !isset($data['schedule_id'], $data['start_date'], $data['end_date'])
) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$id = $data['schedule_id'];
$start = $data['start_date'];
$end = $data['end_date'];
$note = isset($data['note']) ? $data['note'] : "";

try {
    $sql = "UPDATE schedules 
            SET start_date = :start, end_date = :end, note = :note
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':start' => $start,
        ':end' => $end,
        ':note' => $note,
        ':id' => $id
    ]);

    echo json_encode(["status" => "success"]);
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}