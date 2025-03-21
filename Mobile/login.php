<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require'../dbcon.php';

$response = ["success" => false, "message" => "Invalid request"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data
    $rawData = file_get_contents("php://input");
    file_put_contents("debug_log.txt", $rawData); // Log raw data for debugging

    $input = json_decode($rawData, true);

    if ($input === null) {
        $response["message"] = "Invalid JSON format";
    } elseif (!empty($input['StudentNumber']) && !empty($input['Password'])) {
        $studentNumber = trim($input['StudentNumber']);
        $password = trim($input['Password']);

        $stmt = $conn->prepare("SELECT password FROM students WHERE StudentNumber = ?");
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_password);
            $stmt->fetch();

            if (password_verify($password, $db_password)) {
                $response = [
                    "success" => true,
                    "message" => "Login successful!",
                    "StudentNumber" => $studentNumber
                ];
            } else {
                $response["message"] = "Invalid credentials";
            }
        } else {
            $response["message"] = "User not found";
        }

        $stmt->close();
    } else {
        $response["message"] = "StudentNumber and Password are required";
    }
}

// Return JSON response
echo json_encode($response);
?>