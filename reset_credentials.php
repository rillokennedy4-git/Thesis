<?php
session_start();

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");


// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Default credentials
    $defaultUsername = "system.admin";
    $defaultPassword = password_hash("123systemadmin123", PASSWORD_BCRYPT); // Hash the password

    // Update the database
    $query = "UPDATE tbl_user SET Username = ?, Password = ? WHERE Role = 'System Admin'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $defaultUsername, $defaultPassword);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Credentials reset successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to reset credentials."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>