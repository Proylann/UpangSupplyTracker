<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require "../../dbcon.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Check if uniformID is provided
if (!isset($_GET["uniformID"])) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing uniform ID"]);
    exit;
}

$uniformID = intval($_GET["uniformID"]);

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Prepare the query to fetch uniform details
$sql = "SELECT u.ID, u.Name, u.Quantity, u.Description, u.DepartmentID, u.CourseID
        FROM uniforms u
        WHERE u.ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uniformID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $uniform = $result->fetch_assoc();
    
    // Convert binary data to base64 if