<?php
// Database connection
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require "../../dbcon.php";

 $conn = mysqli_connect($servername, $username, $password, $dbname);
    

// First, let's check the actual column name in your courses table
$check_columns_query = "SHOW COLUMNS FROM course";
$columns_result = $conn->query($check_columns_query);

$course_column_name = "Course"; // Default assumption
if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        // Look for the column that might contain course name (Course, Name, Title, etc.)
        if (in_array(strtolower($column['Field']), ['course', 'name', 'title', 'coursename'])) {
            $course_column_name = $column['Field'];
            break;
        }
    }
}

// Now use the correct column name in our main query, including Preview blob columns
$sql = "
    SELECT 
        'module' AS type,
        m.ModuleID AS id, 
        m.Title AS name, 
        d.Name AS department, 
        c.{$course_column_name} AS Course, 
        m.Quantity,
        m.Preview AS preview
    FROM modules m
    LEFT JOIN department d ON m.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON m.CourseID = c.CourseID
    
    UNION ALL
    
    SELECT 
        'uniform' AS type,
        u.UniformID AS id, 
        u.Name AS name, 
        d.Name AS department, 
        c.{$course_column_name} AS Course, 
        u.Quantity,
        u.img AS preview
    FROM uniforms u
    LEFT JOIN department d ON u.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON u.CourseID = c.CourseID
    
    UNION ALL
    
    SELECT 
        'book' AS type,
        b.ID AS id, 
        b.BookTitle AS name, 
        d.Name AS department, 
        c.{$course_column_name} AS Course, 
        b.Quantity,
        b.Preview AS preview
    FROM books b
    LEFT JOIN department d ON b.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON b.CourseID = c.CourseID
";

$result = $conn->query($sql);

// Count totals and low stock items
$totalItems = 0;
$lowStockItems = 0;

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $totalItems++;
        if ($row['Quantity'] < 100) {
            $lowStockItems++;
        }
        
        // Convert the blob data to base64 for display in HTML
        if (isset($row['preview']) && $row['preview'] !== null) {
            $row['preview'] = base64_encode($row['preview']);
        } else {
            $row['preview'] = null;
        }
        
        $items[] = $row;
    }
} else {
    // Log the error if the query fails
    $response = [
        "error" => "Query failed: " . $conn->error,
        "query" => $sql,
        "items" => [],
        "stats" => [
            "totalItems" => 0,
            "lowStockItems" => 0
        ]
    ];
    echo json_encode($response);
    $conn->close();
    exit;
}

$conn->close();

// Return data including stats
$response = [
    "items" => $items,
    "stats" => [
        "totalItems" => $totalItems,
        "lowStockItems" => $lowStockItems
    ]
];

echo json_encode($response);
?>