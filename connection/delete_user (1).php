<?php
header('Content-Type: application/json');

// Decode the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate inputs
$userId = $data['userId'] ?? null;
$password = $data['password'] ?? null;

// Database connection
$conn = new mysqli("localhost", "root", "", "db_archive");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

if ($password) {
    // Fetch the latest password from the database
    $stmt = $conn->prepare("SELECT Password FROM tbl_user WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['Password'];

        // Verify the provided password against the stored hash
        if (password_verify($password, $hashedPassword)) {
            echo json_encode(['status' => 'success', 'message' => 'Password verified.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }

    $stmt->close();
} elseif ($userId) {
    // Delete account
    $stmt = $conn->prepare("DELETE FROM tbl_user WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing userId or password']);
}

$conn->close();
