<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
        exit;
    }

    // Retrieve form data
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role = $_POST['role'] ?? 'Staff';

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
        exit;
    }

    // Check for role constraints
    if ($role === 'System Admin') {
        $checkAdminQuery = "SELECT COUNT(*) as count FROM tbl_user WHERE Role = 'System Admin'";
        $adminResult = $conn->query($checkAdminQuery);
        $adminCount = $adminResult->fetch_assoc()['count'];
        if ($adminCount >= 1) {
            echo json_encode(["status" => "error", "message" => "Only one System Admin is allowed."]);
            exit;
        }
    }



    // Check if username exists
    $checkUsernameQuery = "SELECT * FROM tbl_user WHERE Username = ?";
    $stmt = $conn->prepare($checkUsernameQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Username already exists"]);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Set the default status to Active
    $status = "Active";

    // Handle profile picture upload
    $picture = "";
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
        $picture = uniqid() . "_" . basename($_FILES['picture']['name']);
        $uploadDir = "../upload/profile_pictures/";
        $uploadFile = $uploadDir . $picture;

        // Create the directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
            echo json_encode(["status" => "error", "message" => "Failed to upload profile picture"]);
            exit;
        }
    }

    // Insert data into the database
    $sql = "INSERT INTO tbl_user (First_Name, Last_Name, Username, Password, Role, Status, Picture, Create_At) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $firstName, $lastName, $username, $hashedPassword, $role, $status, $picture);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User added successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    // Respond with an error if the request method is not POST
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
