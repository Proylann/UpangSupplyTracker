<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require '../dbcon.php';

$query = "SELECT DepartmentID, Name FROM department";
$result = mysqli_query($conn, $query);

$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row;
}

echo json_encode($departments);
?>


