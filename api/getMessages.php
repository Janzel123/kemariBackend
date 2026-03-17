<?php
// --- CORS headers for React frontend ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

try {
    // Include status in SELECT
    $sql = "SELECT id, name, email, phone, message, status, created_at
            FROM messages
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize status: capitalize first letter
    foreach ($messages as &$msg) {
        $msg['status'] = isset($msg['status']) 
            ? ucfirst(strtolower($msg['status'])) 
            : "New";
    }

    echo json_encode([
        "status" => "success",
        "data" => $messages
    ]);

} catch(PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>