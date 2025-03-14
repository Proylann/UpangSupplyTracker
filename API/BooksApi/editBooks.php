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
if (!isset($data["ID"], $data["BookTitle"], $data["Department"], $data["Course"], $data["Quantity"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Prepare and escape input data
$bookID = intval($data["ID"]);
$bookTitle = $conn->real_escape_string($data["BookTitle"]);
$departmentID = intval($data["Department"]);
$courseID = intval($data["Course"]);
$quantity = intval($data["Quantity"]);

// Check if book exists before updating
$checkSql = "SELECT ID FROM Books WHERE ID = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $bookID);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(["error" => "Book not found"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Prepare the update query
$sql = "UPDATE Books SET BookTitle = ?, DepartmentID = ?, CourseID = ?, Quantity = ? WHERE ID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("siiii", $bookTitle, $departmentID, $courseID, $quantity, $bookID);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(["success" => "Book updated successfully"]);
        } else {
            http_response_code(200); // Still OK, might be just no changes made
            echo json_encode(["success" => "No changes were made to the book record"]);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to update book: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
}

$conn->close();
?>