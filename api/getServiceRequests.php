<?php
// File: kemaricorp-backend/api/getServiceRequests.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include '../config/db.php';

try {
    $sql = "SELECT id, name, email, phone, service, message AS details, status, created_at
            FROM service_requests
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$req) {
        $req['status'] = isset($req['status']) ? ucfirst(strtolower($req['status'])) : "New";
    }

    echo json_encode(["status" => "success", "data" => $requests]);

} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>