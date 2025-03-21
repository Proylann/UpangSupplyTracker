<?php
// Include database connection
require '../dbcon.php';

// Set response headers
header('Content-Type: application/json');

// Get department ID from request
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : null;

// Validate department ID
if (!$department_id) {
    echo json_encode([]);
    exit;
}

try {
    // Prepare SQL statement to fetch courses by department ID
    $stmt = $conn->prepare("SELECT CourseID, CourseName FROM course WHERE DepartmentID = ?");
    $stmt->bind_param("s", $department_id);
    $stmt->execute();
    
    // Get result
    $result = $stmt->get_result();
    $courses = [];
    
    // Fetch all courses
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'CourseID' => $row['CourseID'],
            'CourseName' => $row['CourseName']
        ];
    }
    
    // Return courses as JSON
    echo json_encode($courses);
    
} catch (Exception $e) {
    // Return empty array in case of error
    echo json_encode([]);
    
    // Log error (optional)
    error_log('Error fetching courses: ' . $e->getMessage());
}


$stmt->close();
$conn->close();
?>