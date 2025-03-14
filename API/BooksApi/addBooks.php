<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["BookTitle"], $data["Department"], $data["Course"], $data["Quantity"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Prepare and execute the SQL statement
$bookTitle = $conn->real_escape_string($data["BookTitle"]);
$departmentID = $conn->real_escape_string($data["Department"]);
$courseID = $conn->real_escape_string($data["Course"]);
$quantity = intval($data["Quantity"]);

// Check if book already exists
$checkSql = "SELECT ID FROM books WHERE BookTitle = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("s", $bookTitle);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["error" => "Book with this title already exists"]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Insert new book
$sql = "INSERT INTO books (BookTitle, DepartmentID, CourseID, Quantity) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssi", $bookTitle, $departmentID, $courseID, $quantity);
    if ($stmt->execute()) {
        http_response_code(201); // Created
        echo json_encode(["success" => "Book added successfully"]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["error" => "Failed to add book: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
}

$conn->close();
?>