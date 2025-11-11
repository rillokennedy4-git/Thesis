<?php
header("Content-Type: application/json");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$conn = new mysqli("localhost", "root", "", "db_archive");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Fetch user data
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Invalid user ID provided."]);
        exit;
    }

    // Debugging: Log the user ID received
    error_log("Fetching data for User_Id: " . $userId);

    $stmt = $conn->prepare("SELECT User_Id, First_Name, Last_Name, Username, Status, Picture FROM tbl_user WHERE User_Id = ?");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepared statement failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user["Picture"] = !empty($user["Picture"]) ? "upload/profile_pictures/" . $user["Picture"] : "https://via.placeholder.com/120";
        echo json_encode(["status" => "success", "user" => $user]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found for the given ID."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// If no valid GET request, respond with error
echo json_encode(["status" => "error", "message" => "Invalid request: Missing or incorrect parameters."]);
