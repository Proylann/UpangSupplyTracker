<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../dbcon.php";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Check if search query is provided
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL query
$sql = "SELECT 
            u.UniformID as uniformId,  
            u.Name as name,
            u.description,
            u.Quantity as quantity,
            u.DepartmentID as departmentId,
            d.Name as departmentName,
            u.CourseID as courseId,
            c.CourseName as courseName,
            u.img
        FROM uniforms u
        LEFT JOIN department d ON u.DepartmentID = d.DepartmentID
        LEFT JOIN course c ON u.CourseID = c.CourseID";

// Add search condition if query is provided
if (!empty($searchQuery)) {
    $searchQuery = $conn->real_escape_string($searchQuery);
    $sql .= " WHERE u.Name LIKE '%$searchQuery%' 
             OR u.description LIKE '%$searchQuery%' 
             OR d.Name LIKE '%$searchQuery%'
             OR c.CourseName LIKE '%$searchQuery%'";
}

$result = $conn->query($sql);

$uniforms = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert BLOB image to Base64 if it exists
        if (!empty($row['img'])) {
            $row['img'] = base64_encode($row['img']);
        } else {
            $row['img'] = null; // If no image, return null
        }

        $uniforms[] = $row;
    }
    
    // Send successful JSON response
    http_response_code(200);
    echo json_encode(["uniforms" => $uniforms]);
} else {
    // No uniforms found or query error
    http_response_code(200); // Still return 200 but with empty array
    echo json_encode(["uniforms" => []]);
}

$conn->close();
?>