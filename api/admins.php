<?php
// ✅ CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// ✅ PREFLIGHT
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/db.php");

$method = $_SERVER['REQUEST_METHOD'];

// 📥 GET DATA (for POST, PUT, DELETE)
$data = json_decode(file_get_contents("php://input"));


// =========================
// ✅ GET ADMINS (no restriction)
// =========================
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM admins ORDER BY id DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}


// =========================
// 🔐 CHECK AUTH FOR OTHER METHODS
// =========================
if ($method !== "GET") {

    if (!isset($data->admin_id)) {
        echo json_encode(["message" => "Unauthorized"]);
        exit;
    }

    // GET CURRENT ADMIN ROLE
    $stmt = $conn->prepare("SELECT role FROM admins WHERE id=?");
    $stmt->execute([$data->admin_id]);
    $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentAdmin) {
        echo json_encode(["message" => "Invalid admin"]);
        exit;
    }

    $role = $currentAdmin['role'];
}


// =========================
// ✅ CREATE ADMIN (SUPERADMIN ONLY)
// =========================
if ($method === "POST") {

    if ($role !== "superadmin") {
        echo json_encode(["message" => "Access denied"]);
        exit;
    }

    $username = trim($data->username);
    $password = trim($data->password);

    if ($username === "" || $password === "") {
        echo json_encode(["message" => "Fields required"]);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashed]);

        echo json_encode(["message" => "Admin created"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Username exists"]);
    }
}


// =========================
// ✏️ UPDATE ADMIN (ALL ADMINS)
// =========================
if ($method === "PUT") {

    $id = $data->id;
    $username = trim($data->username);
    $password = trim($data->password);

    if ($username === "") {
        echo json_encode(["message" => "Username required"]);
        exit;
    }

    // 🔐 RULE: ONLY SUPERADMIN OR OWNER CAN EDIT
    if ($role !== "superadmin" && $data->admin_id != $id) {
        echo json_encode(["message" => "You can only edit your own account"]);
        exit;
    }

    try {
        if ($password !== "") {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET username=?, password=? WHERE id=?");
            $stmt->execute([$username, $hashed, $id]);
        } else {
            $stmt = $conn->prepare("UPDATE admins SET username=? WHERE id=?");
            $stmt->execute([$username, $id]);
        }

        echo json_encode(["message" => "Admin updated"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Update failed"]);
    }
}


// =========================
// ❌ DELETE ADMIN (SUPERADMIN ONLY)
// =========================
if ($method === "DELETE") {

    if ($role !== "superadmin") {
        echo json_encode(["message" => "Access denied"]);
        exit;
    }

    // ❗ PREVENT SELF DELETE
    if ($data->id == $data->admin_id) {
        echo json_encode(["message" => "You cannot delete yourself"]);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
        $stmt->execute([$data->id]);

        echo json_encode(["message" => "Admin deleted"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Delete failed"]);
    }
}