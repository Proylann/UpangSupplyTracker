<?php
// Include database connection
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once "../../dbcon.php";

// Check request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

// Get input data
$bookTitle = isset($_POST["BookTitle"]) ? $conn->real_escape_string($_POST["BookTitle"]) : '';
$departmentID = isset($_POST["Department"]) ? $conn->real_escape_string($_POST["Department"]) : '';
$courseID = isset($_POST["Course"]) ? $conn->real_escape_string($_POST["Course"]) : '';
$quantity = isset($_POST["Quantity"]) ? intval($_POST["Quantity"]) : 0;
$preview = null;

// Validate required fields
if (empty($bookTitle) || empty($departmentID) || empty($courseID) || $quantity <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Handle file upload if present
if (isset($_FILES["BookImage"]) && $_FILES["BookImage"]["error"] == 0) {
    $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
    
    if (in_array($_FILES["BookImage"]["type"], $allowedTypes)) {
        // Read the image file
        $imageData = file_get_contents($_FILES["BookImage"]["tmp_name"]);
        // Base64 encode the image data for storage
        $preview = base64_encode($imageData);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid file type. Only JPG, PNG, and GIF are allowed."]);
        exit;
    }
}

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

// Insert new book with image
if ($preview) {
    $sql = "INSERT INTO books (BookTitle, DepartmentID, CourseID, Quantity, Preview) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $bookTitle, $departmentID, $courseID, $quantity, $preview);
} else {
    $sql = "INSERT INTO books (BookTitle, DepartmentID, CourseID, Quantity) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $bookTitle, $departmentID, $courseID, $quantity);
}

if ($stmt) {
    if ($stmt->execute()) {
        http_response_code(201); // Created
        echo json_encode(["success" => true, "message" => "Book added successfully"]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(["success" => false, "error" => "Failed to add book: " . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
}

$conn->close();
?>