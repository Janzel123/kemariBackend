<?php
// File: kemaribackend/api/getSchedules.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

try {
    $sql = "SELECT 
                s.id AS schedule_id,
                s.request_id,
                s.start_date,
                s.end_date,
                s.note,
                r.name,
                r.email,
                r.phone,
                r.service,
                r.message AS details,
                r.status
            FROM schedules s
            JOIN service_requests r ON r.id = s.request_id
            ORDER BY s.start_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $schedules]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}