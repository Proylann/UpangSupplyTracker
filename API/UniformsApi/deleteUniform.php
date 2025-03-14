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

// Debug: Log the received data
error_log("POST data: " . json_encode($_POST));

// Check if the ID is provided
if (empty($_POST['id'])) {
    error_log("Uniform ID missing for delete operation.");
    echo json_encode(["success" => false, "message" => "Uniform ID is required for deletion"]);
    exit;
}

$uniformId = intval($_POST['id']);

// Check if the uniform exists in the database
$checkSql = "SELECT UniformID FROM uniforms WHERE UniformID = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $uniformId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    error_log("Uniform with ID $uniformId not found.");
    echo json_encode(["success" => false, "message" => "Uniform not found"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Start transaction
$conn->begin_transaction();

try {
    // Check if the Uniform is Currently being used in reserveation
    // $usageSql = "SELECT COUNT(*) as count FROM reservations WHERE UniformID = ? AND Status = 'active'";
    // $usageStmt = $conn->prepare($usageSql);
    // $usageStmt->bind_param("i", $uniformId);
    // $usageStmt->execute();
    // $usageResult = $usageStmt->get_result()->fetch_assoc();
    // 
    // if ($usageResult['count'] > 0) {
    //     throw new Exception("Cannot delete uniform as it is currently in use");
    // }
    // $usageStmt->close();

    // Delete the uniform
    $deleteSql = "DELETE FROM uniforms WHERE UniformID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $uniformId);
    
    if (!$deleteStmt->execute()) {
        error_log("Database error: " . $deleteStmt->error);
        throw new Exception("Database error: " . $deleteStmt->error);
    }

    // Check if any rows were affected
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("No uniform was deleted. It may have already been removed.");
    }

    $deleteStmt->close();
    
    $conn->commit();
    
    echo json_encode([
        "success" => true, 
        "message" => "Uniform deleted successfully"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>