<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require "../../dbcon.php";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method used: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

// Debugging logs
error_log("POST data: " . json_encode($_POST));
error_log("FILES data: " . json_encode($_FILES));

// Validate required fields
if (empty($_POST['uniformName']) || empty($_POST['department']) || empty($_POST['quantity']) || empty($_POST['uniformDescription']) || !isset($_FILES['uniformImage'])) {
    error_log("Missing required fields: " . json_encode($_POST));
    echo json_encode(["success" => false, "message" => "All fields (Name, Department, Quantity, Description, Image) are required"]);
    exit;
}

$name = trim($_POST['uniformName']);
$departmentId = intval($_POST['department']);
$quantity = intval($_POST['quantity']);
$courseId = !empty($_POST['course']) ? intval($_POST['course']) : null;
$description = trim($_POST['uniformDescription']);

// Check for image upload
if (!isset($_FILES['uniformImage']) || $_FILES['uniformImage']['error'] != 0) {
    error_log("Image upload error: " . json_encode($_FILES));
    echo json_encode(["success" => false, "message" => "Image upload is required"]);
    exit;
}

// Process image
$imageData = file_get_contents($_FILES['uniformImage']['tmp_name']);

// Start transaction
$conn->begin_transaction();

try {
    // Insert new uniform
    $sql = "INSERT INTO uniforms (Name, Quantity, Description, DepartmentID, CourseID, img) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissib", $name, $quantity, $description, $departmentId, $courseId, $imageData);
    $stmt->send_long_data(5, $imageData);

    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }

    $conn->commit();
    echo json_encode(["success" => true, "message" => "Uniform added successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
