<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require '../dbcon.php';

function error422($message) {
    $data = ['status' => 422, 'message' => $message];
    header('HTTP/1.0 422 Unprocessable Entity');
    echo json_encode($data);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $inputData = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($inputData['FullName']) || !isset($inputData['StudentNumber']) || 
        !isset($inputData['Password']) || !isset($inputData['DepartmentID']) || !isset($inputData['CourseID'])) {
        error422('Missing required fields');
    }

    global $conn;
    $fullname = mysqli_real_escape_string($conn, trim($inputData['FullName']));
    $studentNumber = mysqli_real_escape_string($conn, trim($inputData['StudentNumber']));
    $password = password_hash(trim($inputData['Password']), PASSWORD_DEFAULT); // Hash password
    $departmentID = mysqli_real_escape_string($conn, trim($inputData['DepartmentID']));
    $courseID = mysqli_real_escape_string($conn, trim($inputData['CourseID']));

    if(empty($fullname)) error422('Enter your full name');
    if(empty($studentNumber)) error422('Enter Student Number');
    if(empty($inputData['Password'])) error422('Enter your password'); // Check before hashing
    if(empty($departmentID)) error422('Enter your department');
    if(empty($courseID)) error422('Enter your course');

    // Check if CourseID exists
    $checkCourseQuery = "SELECT CourseID FROM course WHERE CourseID = ?";
    $checkCourseStmt = $conn->prepare($checkCourseQuery);
    $checkCourseStmt->bind_param("s", $courseID);
    $checkCourseStmt->execute();
    $courseResult = $checkCourseStmt->get_result();
    
    if ($courseResult->num_rows == 0) {
        error422('Invalid Course ID. Please select an existing course.');
    }
    $checkCourseStmt->close();

    // Check if StudentNumber already exists
    $checkQuery = "SELECT * FROM students WHERE StudentNumber = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $studentNumber);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        error422('Student Number already exists');
    }
    $checkStmt->close();

    // Insert Data
    $query = "INSERT INTO students (FullName, StudentNumber, Password, DepartmentID, CourseID) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $fullname, $studentNumber, $password, $departmentID, $courseID);

    if ($stmt->execute()) {
        $data = ['status' => 201, 'message' => 'Student registered successfully'];
        header('HTTP/1.0 201 Created');
        echo json_encode($data);
    } else {
        error422('Database Error: ' . mysqli_error($conn));
    }
    $stmt->close();
} else {
    $data = ['status' => 405, 'message' => "Method Not Allowed"];
    header('HTTP/1.0 405 Method Not Allowed');
    echo json_encode($data);
}
?>
