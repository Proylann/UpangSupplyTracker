<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");


$host = "localhost";  // Ensure this line exists
$username = "root";   // Your database username
$password = "";       // Your database password (empty if none)
$dbname = "student_db"; // Your actual database name

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to connect to database: " . $conn->connect_error]);
    exit;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}



error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Fetch all departments
$sql = "SELECT DepartmentID, Name FROM department ORDER BY Name";
$result = $conn->query($sql);

if ($result) {
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    echo json_encode(["departments" => $departments]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch departments"]);
}

$conn->close();
?>