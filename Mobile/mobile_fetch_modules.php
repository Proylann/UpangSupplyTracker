<?php
// mobile_fetch_modules.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require "../dbcon.php";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Handle optional search parameter
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$departmentFilter = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$courseFilter = isset($_GET['course_id']) ? $_GET['course_id'] : '';

// Build the SQL query with optional filters
$sql = "SELECT m.ModuleID, m.Title, m.Semester, m.Quantity, 
                 d.DepartmentID, 
               m.CourseID
        FROM modules m
        LEFT JOIN department d ON m.DepartmentID = d.DepartmentID
        LEFT JOIN course c ON m.CourseID = c.CourseID
        WHERE 1=1";

// Add filters if provided
if (!empty($searchQuery)) {
    $sql .= " AND (m.Title LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
}

if (!empty($departmentFilter)) {
    $sql .= " AND m.DepartmentID = " . $conn->real_escape_string($departmentFilter);
}

if (!empty($courseFilter)) {
    $sql .= " AND m.CourseID = " . $conn->real_escape_string($courseFilter);
}

$result = $conn->query($sql);

if ($result) {
    $modules = [];
    while ($row = $result->fetch_assoc()) {
        $modules[] = [
            "moduleId" => (int)$row['ModuleID'],
            "title" => $row['Title'],
            "semester" => $row['Semester'],
            "quantity" => (int)$row['Quantity'],
            "departmentId" => (int)$row['DepartmentID'],
            "courseId" => $row['CourseID'] ? (int)$row['CourseID'] : null,
        ];
    }
    echo json_encode(["modules" => $modules]);
} else {
    echo json_encode(["error" => "Error: " . $conn->error]);
}

$conn->close();
?>