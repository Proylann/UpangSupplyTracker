<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require "../../dbcon.php";

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST method is allowed"]);
    exit;
}

// Get the input data
$input = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Uniform ID is required"]);
    exit;
}

$uniformId = intval($input['id']);

// Check if the uniform exists
$checkSql = "SELECT UniformID FROM uniforms WHERE UniformID = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $uniformId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Uniform not found"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Start transaction
$conn->begin_transaction();

try {
    // Delete the uniform
    $deleteSql = "DELETE FROM uniforms WHERE UniformID = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $uniformId);

    if (!$deleteStmt->execute()) {
        throw new Exception("Database error: " . $deleteStmt->error);
    }

    // Check if any rows were affected
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("No uniform was deleted. It may have already been removed.");
    }

    $deleteStmt->close();
    $conn->commit();

    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Uniform deleted successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
