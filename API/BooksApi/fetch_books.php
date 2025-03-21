<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../../dbcon.php";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$sql = "SELECT 
            b.ID,  
            b.Preview,  
            b.BookTitle, 
            d.Name AS Department, 
            c.CourseName AS Course,  
            b.Quantity
        FROM books b
        LEFT JOIN department d ON b.DepartmentID = d.DepartmentID
        LEFT JOIN course c ON b.CourseID = c.CourseID AND c.DepartmentID = d.DepartmentID";

$result = $conn->query($sql);

$books = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert BLOB image to Base64 if it exists
        if (!empty($row['Preview'])) {
            $row['Preview'] = base64_encode($row['Preview']);
        } else {
            $row['Preview'] = null; // If no image, return null
        }

        $books[] = $row;
    }
}

// Send JSON response
http_response_code(200);
echo json_encode(["books" => $books]);

$conn->close();

?>
