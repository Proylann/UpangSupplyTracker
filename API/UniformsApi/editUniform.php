<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/database.php";

// Ensure request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

// Validate required fields
if (!isset($_POST["uniform_id"], $_POST["uniformName"], $_POST["department"], $_POST["quantity"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$uniform_id = $_POST["uniform_id"];
$name = $_POST["uniformName"];
$department = $_POST["department"];
$course = $_POST["course"] ?? null;
$stock = $_POST["quantity"];
$imagePath = null;

// Check if a file was uploaded
if (isset($_FILES["uniformImage"]) && $_FILES["uniformImage"]["error"] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/";
    $fileName = basename($_FILES["uniformImage"]["name"]);
    $targetFilePath = $uploadDir . time() . "_" . $fileName; // Prevent duplicate names
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Validate file type (only allow images)
    $allowedTypes = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(["success" => false, "message" => "Invalid file type"]);
        exit;
    }

    // Move uploaded file
    if (move_uploaded_file($_FILES["uniformImage"]["tmp_name"], $targetFilePath)) {
        $imagePath = $targetFilePath;
    } else {
        echo json_encode(["success" => false, "message" => "Failed to upload image"]);
        exit;
    }
}

// Update database
if ($imagePath) {
    $sql = "UPDATE uniforms SET Name=?, DepartmentID=?, CourseID=?, Stock=?, Image=? WHERE ID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $name, $department, $course, $stock, $imagePath, $uniform_id);
} else {
    $sql = "UPDATE uniforms SET Name=?, DepartmentID=?, CourseID=?, Stock=? WHERE ID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $department, $course, $stock, $uniform_id);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Uniform updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update uniform: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
