<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../../dbcon.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Accept both POST and PUT methods for flexibility
if ($_SERVER["REQUEST_METHOD"] !== "POST" && $_SERVER["REQUEST_METHOD"] !== "PUT") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST or PUT methods are allowed"]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

// Check if required fields are provided
if (!isset($data["originalStudentNumber"], $data["FullName"], $data["DepartmentID"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Prepare and escape input data
$originalStudentNumber = $conn->real_escape_string($data["originalStudentNumber"]);
$fullName = $conn->real_escape_string($data["FullName"]);
$departmentID = intval($data["DepartmentID"]);

// Handle optional student number change
$newStudentNumber = isset($data["StudentNumber"]) ? 
    $conn->real_escape_string($data["StudentNumber"]) : 
    $originalStudentNumber;

// Check if student exists before updating
$checkSql = "SELECT StudentNumber FROM Students WHERE StudentNumber = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("s", $originalStudentNumber);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(["error" => "Student not found"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// If student number is being changed, check if the new one already exists
if ($originalStudentNumber !== $newStudentNumber) {
    $checkSql = "SELECT StudentNumber FROM Students WHERE StudentNumber = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $newStudentNumber);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(["error" => "Another student with this ID already exists"]);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();
}

// Build the SQL query based on whether password is being updated
if (isset($data["Password"]) && !empty($data["Password"])) {
    $password = password_hash($data["Password"], PASSWORD_DEFAULT);
    $sql = "UPDATE Students SET StudentNumber = ?, FullName = ?, Password = ?, DepartmentID = ? WHERE StudentNumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $newStudentNumber, $fullName, $password, $departmentID, $originalStudentNumber);
} else {
    $sql = "UPDATE Students SET StudentNumber = ?, FullName = ?, DepartmentID = ? WHERE StudentNumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $newStudentNumber, $fullName, $departmentID, $originalStudentNumber);
}

if ($stmt) {
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(["success" => "Student updated successfully"]);
        } else {
            http_response_code(200); // Still OK, might be just no changes made
            echo json_encode(["success" => "No changes were made to the student record"]);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to update student: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
}

$conn->close();
?>