<?php
// Turn off all error reporting to prevent errors from breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Create a function to return error responses
function returnError($message) {
    echo json_encode(["error" => $message]);
    exit;
}

// First check if we can connect to the database
include '../../dbcon.php';

if (!isset($conn) || !$conn) {
    returnError("Database connection failed");
}

// Function to safely fetch data from database
function fetchData($conn, $query) {
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        returnError("Query failed: " . mysqli_error($conn));
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

try {
    // Simple queries to get basic data from each table with correct column names
    $booksQuery = "SELECT ID, Preview, BookTitle as name, Quantity, DepartmentID, CourseID, 'book' as type FROM books";
    $modulesQuery = "SELECT ModuleID as ID,  Title as name, Quantity, DepartmentID, CourseID, 'module' as type FROM modules";
    $uniformsQuery = "SELECT UniformID as ID, img as Preview, Name as name, Quantity, DepartmentID, NULL as CourseID, 'uniform' as type FROM uniforms";

    // Execute queries
    $books = fetchData($conn, $booksQuery);
    $modules = fetchData($conn, $modulesQuery);
    $uniforms = fetchData($conn, $uniformsQuery);

    // Combine all items
    $allItems = array_merge($books, $modules, $uniforms);
    
    // Process items to format them correctly
    $items = [];
    $lowStockCount = 0;
    
    foreach ($allItems as $item) {
        // Convert binary data to base64
        $preview = null;
        if (isset($item['Preview']) && $item['Preview']) {
            $preview = base64_encode($item['Preview']);
        }
        
        // Get department name
        $departmentName = 'N/A';
        if (!empty($item['DepartmentID'])) {
            $deptQuery = "SELECT DepartmentName FROM department WHERE DepartmentID = " . intval($item['DepartmentID']);
            $deptResult = mysqli_query($conn, $deptQuery);
            if ($deptResult && $deptRow = mysqli_fetch_assoc($deptResult)) {
                $departmentName = $deptRow['DepartmentName'];
            }
        }
        
        // Get course name
        $courseName = 'N/A';
        if (!empty($item['CourseID'])) {
            $courseQuery = "SELECT CourseName FROM course WHERE CourseID = " . intval($item['CourseID']);
            $courseResult = mysqli_query($conn, $courseQuery);
            if ($courseResult && $courseRow = mysqli_fetch_assoc($courseResult)) {
                $courseName = $courseRow['CourseName'];
            }
        }
        
        // Count low stock items
        if ($item['Quantity'] < 100) {
            $lowStockCount++;
        }
        
        // Format item for output - make sure all expected fields are present
        $items[] = [
            'id' => $item['ID'],
            'name' => $item['name'],
            'preview' => $preview,
            'department' => $departmentName,
            'Course' => $courseName,
            'Quantity' => (int)$item['Quantity'],  // Ensure it's an integer
            'type' => $item['type']
        ];
    }
    
    // Create stats object
    $stats = [
        'totalItems' => count($items),
        'lowStockItems' => $lowStockCount
    ];
    
    // Output the JSON response
    echo json_encode([
        'items' => $items,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    returnError("Unexpected error: " . $e->getMessage());
}
?>