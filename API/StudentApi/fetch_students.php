<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require '../../dbcon.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$sql = "SELECT 
            s.StudentNumber,  
            s.FullName,  
            s.Password,  
            d.Name 
        FROM Students s
        JOIN department d ON s.DepartmentID = d.DepartmentID
        ORDER BY s.FullName ASC";

$result = $conn->query($sql);

$students = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Send JSON response
http_response_code(200);
echo json_encode(["students" => $students]);

$conn->close();
?>
