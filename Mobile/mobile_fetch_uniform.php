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

$sql = "SELECT 
            u.UniformID,  
            u.Name,  
            u.description, 
            u.Quantity,
            u.img,
            d.Name AS Department, 
            c.CourseName AS Course,  
            u.DepartmentID,
            u.CourseID
        FROM uniforms u
        LEFT JOIN department d ON u.DepartmentID = d.DepartmentID
        LEFT JOIN course c ON u.CourseID = c.CourseID";

$result = $conn->query($sql);

$uniforms = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert BLOB image to Base64 if it exists
        if (!empty($row['img'])) {
            $row['img'] = base64_encode($row['img']);
        } else {
            $row['img'] = null; // If no image, return null
        }

        $uniforms[] = [
            "uniformId" => $row["UniformID"],
            "name" => $row["Name"],
            "description" => $row["description"],
            "quantity" => $row["Quantity"],
            "departmentId" => $row["DepartmentID"],
            "departmentName" => $row["Department"],
            "courseId" => $row["CourseID"],
            "courseName" => $row["Course"],
            "img" => $row["img"]
        ];
    }
}

// Send JSON response
http_response_code(200);
echo json_encode(["uniforms" => $uniforms]);

$conn->close();

?>