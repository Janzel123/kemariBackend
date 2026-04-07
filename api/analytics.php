<?php
// File: kemaribackend/api/getAnalytics.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

try {
    // 1. Total Requests
    $totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM service_requests");
    $totalStmt->execute();
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Daily Requests (last 30 days)
    $dailyStmt = $conn->prepare("
        SELECT DATE(created_at) AS date, COUNT(*) AS count
        FROM service_requests
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $dailyStmt->execute();
    $daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. By Status
    $statusStmt = $conn->prepare("
        SELECT COALESCE(NULLIF(TRIM(status), ''), 'New') AS status, COUNT(*) AS count
        FROM service_requests
        GROUP BY status
    ");
    $statusStmt->execute();
    $byStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. By Service
    $serviceStmt = $conn->prepare("
        SELECT service, COUNT(*) AS count
        FROM service_requests
        GROUP BY service
        ORDER BY count DESC
    ");
    $serviceStmt->execute();
    $byService = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_requests" => (int)$total,
            "daily_requests" => $daily,
            "by_status"      => $byStatus,
            "by_service"     => $byService
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>