<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
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

// Accept both POST and DELETE methods for flexibility
if ($_SERVER["REQUEST_METHOD"] !== "POST" && $_SERVER["REQUEST_METHOD"] !== "DELETE") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST or DELETE methods are allowed"]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

// Check if book ID is provided
if (!isset($data["bookID"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Book ID is required"]);
    exit;
}

// Prepare and execute the SQL statement
$bookID = intval($data["bookID"]);

// Check if book exists before deleting
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

// Delete the book
$sql = "DELETE FROM Books WHERE ID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $bookID);
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            http_response_code(200); // OK
            echo json_encode(["success" => "Book deleted successfully"]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(["error" => "Failed to delete book"]);
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to delete book: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
}

$conn->close();
?>