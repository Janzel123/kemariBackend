<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/db.php");

$method = $_SERVER['REQUEST_METHOD'];


// ✅ GET
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, username, created_at FROM admins ORDER BY id DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}


// ✅ CREATE
if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"));

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


// ✅ UPDATE
if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"));

    $id = $data->id;
    $username = trim($data->username);
    $password = trim($data->password);

    if ($username === "") {
        echo json_encode(["message" => "Username required"]);
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


// ❌ DELETE
if ($method === "DELETE") {
    $data = json_decode(file_get_contents("php://input"));

    $id = $data->id;

    try {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
        $stmt->execute([$id]);

        echo json_encode(["message" => "Admin deleted"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Delete failed"]);
    }
}