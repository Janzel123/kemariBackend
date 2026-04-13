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

// 📥 GET DATA
// ✅ POST uses $_POST + $_FILES (multipart/form-data), others use JSON body
if ($method !== "POST") {
    $data = json_decode(file_get_contents("php://input"));
}


// =========================
// ✅ GET ADMINS
// =========================
if ($method === "GET") {
    $stmt = $conn->prepare("SELECT id, username, role, profile_image, created_at FROM admins ORDER BY id DESC");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}


// =========================
// 🔐 CHECK AUTH FOR OTHER METHODS
// =========================
if ($method === "POST") {
    $admin_id = $_POST['admin_id'] ?? null;
} else {
    $admin_id = $data->admin_id ?? null;
}

if (!$admin_id) {
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

$stmt = $conn->prepare("SELECT role FROM admins WHERE id=?");
$stmt->execute([$admin_id]);
$currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentAdmin) {
    echo json_encode(["message" => "Invalid admin"]);
    exit;
}

// ✅ Renamed to $currentRole to avoid conflict with $_POST['role']
$currentRole = $currentAdmin['role'];


// =========================
// ✅ SHARED: HANDLE PROFILE IMAGE UPLOAD
// Returns new image path or null if no file was uploaded
// =========================
function handleImageUpload($uploadDir = "../uploads/admins/") {
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        return null; // no file uploaded — that's fine
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileInfo    = $_FILES['profile_image'];
    $ext         = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowedExts)) {
        echo json_encode(["message" => "Invalid image type. Allowed: jpg, jpeg, png, gif, webp"]);
        exit;
    }

    if ($fileInfo['size'] > 2 * 1024 * 1024) {
        echo json_encode(["message" => "Image too large. Max 2MB."]);
        exit;
    }

    $newFilename = "admin_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $destination = $uploadDir . $newFilename;

    if (!move_uploaded_file($fileInfo['tmp_name'], $destination)) {
        echo json_encode(["message" => "Failed to save image"]);
        exit;
    }

    return "uploads/admins/" . $newFilename;
}


// =========================
// ✅ CREATE ADMIN (SUPERADMIN ONLY)
// =========================
if ($method === "POST" && empty($_POST['id'])) {

    if ($currentRole !== "superadmin") {
        echo json_encode(["message" => "Access denied"]);
        exit;
    }

    $username    = trim($_POST['username'] ?? "");
    $password    = trim($_POST['password'] ?? "");
    // ✅ Read role from POST, default to "admin" if not provided or invalid
    $targetRole  = trim($_POST['role'] ?? "admin");
    if (!in_array($targetRole, ["admin", "superadmin"])) {
        $targetRole = "admin";
    }

    if ($username === "" || $password === "") {
        echo json_encode(["message" => "Fields required"]);
        exit;
    }

    $hashed    = password_hash($password, PASSWORD_DEFAULT);
    $imagePath = handleImageUpload();

    try {
        // ✅ Now includes role in the INSERT
        $stmt = $conn->prepare("INSERT INTO admins (username, password, role, profile_image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashed, $targetRole, $imagePath]);

        echo json_encode(["message" => "Admin created"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Username already exists"]);
    }

    exit;
}


// =========================
// ✏️ UPDATE ADMIN (SUPERADMIN OR OWNER) — supports profile image change
// =========================
if ($method === "POST" && !empty($_POST['id'])) {

    $id       = (int) $_POST['id'];
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($username === "") {
        echo json_encode(["message" => "Username required"]);
        exit;
    }

    // 🔐 ONLY SUPERADMIN OR OWNER CAN EDIT
    if ($currentRole !== "superadmin" && $admin_id != $id) {
        echo json_encode(["message" => "You can only edit your own account"]);
        exit;
    }

    // ✅ Only superadmin can change role; owners editing themselves keep their current role
    $targetRole = null;
    if ($currentRole === "superadmin") {
        $targetRole = trim($_POST['role'] ?? "admin");
        if (!in_array($targetRole, ["admin", "superadmin"])) {
            $targetRole = "admin";
        }
    }

    // ✅ Handle new image upload
    $newImagePath = handleImageUpload();

    // If a new image was uploaded, delete the old one
    if ($newImagePath) {
        $stmt = $conn->prepare("SELECT profile_image FROM admins WHERE id=?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['profile_image']) {
            $oldFile = "../" . $existing['profile_image'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    }

    try {
        // ✅ Build query dynamically based on what changed (now includes role if superadmin)
        if ($currentRole === "superadmin") {
            if ($password !== "" && $newImagePath) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("UPDATE admins SET username=?, password=?, role=?, profile_image=? WHERE id=?");
                $stmt->execute([$username, $hashed, $targetRole, $newImagePath, $id]);

            } elseif ($password !== "") {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("UPDATE admins SET username=?, password=?, role=? WHERE id=?");
                $stmt->execute([$username, $hashed, $targetRole, $id]);

            } elseif ($newImagePath) {
                $stmt = $conn->prepare("UPDATE admins SET username=?, role=?, profile_image=? WHERE id=?");
                $stmt->execute([$username, $targetRole, $newImagePath, $id]);

            } else {
                $stmt = $conn->prepare("UPDATE admins SET username=?, role=? WHERE id=?");
                $stmt->execute([$username, $targetRole, $id]);
            }
        } else {
            // Non-superadmin (owner editing themselves) — no role change allowed
            if ($password !== "" && $newImagePath) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("UPDATE admins SET username=?, password=?, profile_image=? WHERE id=?");
                $stmt->execute([$username, $hashed, $newImagePath, $id]);

            } elseif ($password !== "") {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $conn->prepare("UPDATE admins SET username=?, password=? WHERE id=?");
                $stmt->execute([$username, $hashed, $id]);

            } elseif ($newImagePath) {
                $stmt = $conn->prepare("UPDATE admins SET username=?, profile_image=? WHERE id=?");
                $stmt->execute([$username, $newImagePath, $id]);

            } else {
                $stmt = $conn->prepare("UPDATE admins SET username=? WHERE id=?");
                $stmt->execute([$username, $id]);
            }
        }

        echo json_encode(["message" => "Admin updated"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Update failed"]);
    }

    exit;
}


// =========================
// ❌ DELETE ADMIN (SUPERADMIN ONLY)
// =========================
if ($method === "DELETE") {

    if ($currentRole !== "superadmin") {
        echo json_encode(["message" => "Access denied"]);
        exit;
    }

    if ($data->id == $data->admin_id) {
        echo json_encode(["message" => "You cannot delete yourself"]);
        exit;
    }

    try {
        // ✅ Also delete the profile image file if it exists
        $stmt = $conn->prepare("SELECT profile_image FROM admins WHERE id=?");
        $stmt->execute([$data->id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['profile_image']) {
            $filePath = "../" . $row['profile_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
        $stmt->execute([$data->id]);

        echo json_encode(["message" => "Admin deleted"]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "Delete failed"]);
    }

    exit;
}