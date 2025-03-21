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

$host = "localhost";  // Ensure this line exists
$username = "root";   // Your database username
$password = "";       // Your database password (empty if none)
$dbname = "mainDb"; // Your actual database name

// Debugging: Check if database variables exist
if (!isset($host, $username, $password, $dbname)) {
    die(json_encode(["success" => false, "error" => "Database connection details are missing"]));
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if departmentID is provided and valid
if (!isset($_GET['departmentID']) || !is_numeric($_GET['departmentID'])) {
    die(json_encode(["success" => false, "error" => "Invalid department ID"]));
}

$departmentID = intval($_GET['departmentID']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT CourseID, CourseName FROM course WHERE DepartmentID = ?");
    $stmt->execute([$departmentID]);
    
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "courses" => $courses]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
