<?php
session_start();
header("Content-Type: application/json");

// Check if the user is logged in and is a System Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $userId = $data['userId'] ?? null;
    $adminPassword = $data['adminPassword'] ?? null;

    if (!$userId || !$adminPassword) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit();
    }

    // Verify System Administrator password
    $stmt = $conn->prepare("SELECT Password FROM tbl_user WHERE User_Id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "System Administrator not found."]);
        exit();
    }

    $admin = $result->fetch_assoc();
    if (!password_verify($adminPassword, $admin['Password'])) {
        echo json_encode(["status" => "error", "message" => "Invalid System Administrator password."]);
        exit();
    }

    // Restore the account
    $stmt = $conn->prepare("UPDATE tbl_user SET Status = 'Active' WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Account restored successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to restore account."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// Default response for invalid requests
echo json_encode(["status" => "error", "message" => "Invalid request."]);
exit();
