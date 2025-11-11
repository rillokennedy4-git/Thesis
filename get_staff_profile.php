<?php
session_start();
include 'database/db_connection.php'; // Replace with your database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id']; // Get logged-in user's ID from session

// Fetch user data from the database
$query = "SELECT Picture, First_Name, Last_Name, Username FROM tbl_user WHERE User_id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if ($userData) {
        // Return user data as JSON
        echo json_encode($userData);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Database query failed']);
}

$conn->close();
?>
