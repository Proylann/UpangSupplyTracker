<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require "../../dbcon.php";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Handle different request types
$request_method = $_SERVER['REQUEST_METHOD'];

// For preflight requests
if ($request_method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fetch uniforms
if ($request_method === 'GET') {
    $sql = "SELECT 
            u.UniformID AS ID,   
            u.Name, 
            d.Name AS Department, 
            c.CourseName AS Course,  
            u.Quantity AS Stock,
            u.DepartmentID,
            u.CourseID,
            u.img AS Preview 
        FROM uniforms u
        LEFT JOIN department d ON u.DepartmentID = d.DepartmentID
        LEFT JOIN course c ON u.CourseID = c.CourseID AND c.DepartmentID = d.DepartmentID";

    $result = $conn->query($sql);

    $uniforms = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Convert BLOB image to Base64
            if (!empty($row['Preview'])) {
                $row['Preview'] = base64_encode($row['Preview']);
            } else {
                $row['Preview'] = null; // If no image, return null
            }

            $uniforms[] = $row;
        }
    }

    // Send JSON response
    echo json_encode(["success" => true, "uniforms" => $uniforms]);
    exit;
}

// Add or update uniform
if ($request_method === 'POST') {
    // Get mode safely
    $mode = isset($_POST['mode']) ? $_POST['mode'] : null;

    if (!$mode) {
        echo json_encode(["success" => false, "message" => "Mode is required"]);
        exit;
    }

    // Validate required fields
    if (empty($_POST['uniformName']) || empty($_POST['department']) || empty($_POST['quantity'])) {
        echo json_encode(["success" => false, "message" => "Required fields are missing"]);
        exit;
    }

    $name = trim($_POST['uniformName']);
    $departmentId = intval($_POST['department']);
    $quantity = intval($_POST['quantity']);
    $courseId = !empty($_POST['course']) ? intval($_POST['course']) : null;

    // Start transaction
    $conn->begin_transaction();

    try {
        if ($mode === 'add') {
            // Check for image upload
            if (!isset($_FILES['uniformImage']) || $_FILES['uniformImage']['error'] != 0) {
                throw new Exception("Image upload is required for new uniforms");
            }

            // Process image
            $imageData = file_get_contents($_FILES['uniformImage']['tmp_name']);

            // Insert new uniform
            $sql = "INSERT INTO uniforms (Name, Quantity, DepartmentID, CourseID, img) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiib", $name, $quantity, $departmentId, $courseId, $imageData);
            $stmt->send_long_data(4, $imageData);

        } elseif ($mode === 'edit') {
            if (empty($_POST['id'])) {
                throw new Exception("Uniform ID is required for editing");
            }

            $uniformId = intval($_POST['id']);

            // Check if image was uploaded for update
            if (isset($_FILES['uniformImage']) && $_FILES['uniformImage']['error'] == 0) {
                $imageData = file_get_contents($_FILES['uniformImage']['tmp_name']);
                $sql = "UPDATE uniforms SET 
                        Name = ?,  
                        Quantity = ?, 
                        DepartmentID = ?, 
                        CourseID = ?, 
                        img = ? 
                        WHERE UniformID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siibi", $name, $quantity, $departmentId, $courseId, $imageData, $uniformId);
                $stmt->send_long_data(4, $imageData);
            } else {
                $sql = "UPDATE uniforms SET 
                        Name = ?, 
                        Quantity = ?, 
                        DepartmentID = ?, 
                        CourseID = ? 
                        WHERE UniformID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siiii", $name, $quantity, $departmentId, $courseId, $uniformId);
            }
        } else {
            throw new Exception("Invalid mode specified");
        }

        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        $conn->commit();

        echo json_encode([
            "success" => true, 
            "message" => ($mode === 'add') ? "Uniform added successfully" : "Uniform updated successfully"
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

$conn->close();
?>