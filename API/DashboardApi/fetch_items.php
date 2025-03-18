<?php
// Database connection
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require "../../dbcon.php";
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Get category filter from request
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Base query joining the category table
$sql = "
    SELECT 
        'module' AS type,
        m.ModuleID AS id, 
        m.Title AS name, 
        d.Name AS department, 
        c.CourseID AS Course, 
        m.Quantity,
        m.Preview AS preview
    FROM modules m
    LEFT JOIN department d ON m.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON m.CourseID = c.CourseID
    LEFT JOIN category cat ON cat.module_id = m.ModuleID
    
    UNION ALL
    
    SELECT 
        'uniform' AS type,
        u.UniformID AS id, 
        u.Name AS name, 
        d.Name AS department, 
        c.CourseID AS Course, 
        u.Quantity,
        u.img AS preview
    FROM uniforms u
    LEFT JOIN department d ON u.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON u.CourseID = c.CourseID
    LEFT JOIN category cat ON cat.uniform_id = u.UniformID
    
    UNION ALL
    
    SELECT 
        'book' AS type,
        b.ID AS id, 
        b.BookTitle AS name, 
        d.Name AS department, 
        c.CourseID AS Course, 
        b.Quantity,
        b.Preview AS preview
    FROM books b
    LEFT JOIN department d ON b.DepartmentID = d.DepartmentID
    LEFT JOIN course c ON b.CourseID = c.CourseID
    LEFT JOIN category cat ON cat.book_id = b.ID
";

// Apply category filter
if ($categoryFilter !== 'all') {
    $sql .= " WHERE cat.category_name = ?";
}

$stmt = $conn->prepare($sql);
if ($categoryFilter !== 'all') {
    $stmt->bind_param("s", $categoryFilter);
}
$stmt->execute();
$result = $stmt->get_result();

// Prepare response
$items = [];
$totalItems = 0;
$lowStockItems = 0;

while ($row = $result->fetch_assoc()) {
    $totalItems++;
    if ($row['Quantity'] < 100) {
        $lowStockItems++;
    }

    // Convert preview BLOB to base64 if available
    if (isset($row['preview']) && $row['preview'] !== null) {
        $row['preview'] = base64_encode($row['preview']);
    }

    $items[] = $row;
}

echo json_encode([
    "items" => $items,
    "stats" => [
        "totalItems" => $totalItems,
        "lowStockItems" => $lowStockItems
    ]
]);

$stmt->close();
$conn->close();
?>
