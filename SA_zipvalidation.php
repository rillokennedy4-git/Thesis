<?php
session_start();

// Ensure the user is logged in and is a System Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include the database connection
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Get the password and type from the request
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'];
$type = $data['type']; // 'archive' or 'database'

// Fetch the hashed password for the logged-in system admin
$userId = $_SESSION['user_id'];
$sql = "SELECT Password FROM tbl_user WHERE User_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hashedPassword = $row['Password'];

    // Validate the password
    if (password_verify($password, $hashedPassword)) {
        // Password is valid, return success and the type
        echo json_encode(['success' => true, 'type' => $type]);
    } else {
        // Password is invalid
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    // User not found
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
?>