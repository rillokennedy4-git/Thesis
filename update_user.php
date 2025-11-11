<?php
header("Content-Type: application/json");
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Handle GET Request - Fetch User Data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $userId = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT User_Id, First_Name, Last_Name, Username, Status, Picture FROM tbl_user WHERE User_Id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user["Picture"] = !empty($user["Picture"]) ? "upload/profile_pictures/" . $user["Picture"] : "https://via.placeholder.com/120";
        echo json_encode(["status" => "success", "user" => $user]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Handle POST Request - Update User Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : null;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : null;
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : null;
    $adminPassword = isset($_POST['adminPassword']) ? $_POST['adminPassword'] : null;

    // Validate required parameters
    if (!$userId || !$firstName || !$lastName || !$username || !$status) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit;
    }

    // Handle archiving validation
    if ($status === 'Inactive') {
        if (!$adminPassword) {
            echo json_encode(["status" => "error", "message" => "System Admin's password is required to Deactivate an account."]);
            exit;
        }

        // Validate admin password
        $stmt = $conn->prepare("SELECT Password FROM tbl_user WHERE User_Id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0 || !password_verify($adminPassword, $result->fetch_assoc()['Password'])) {
            echo json_encode(["status" => "error", "message" => "Invalid admin password."]);
            exit;
        }
    }

    // Update user details and status
    $stmt = $conn->prepare("UPDATE tbl_user SET First_Name = ?, Last_Name = ?, Username = ?, Status = ? WHERE User_Id = ?");
    $stmt->bind_param("ssssi", $firstName, $lastName, $username, $status, $userId);

    if ($stmt->execute()) {
        // Handle profile picture upload if provided
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = "upload/profile_pictures/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
            }

            // Generate a unique name for the uploaded file
            $fileExtension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $pictureName = uniqid("profile_") . "." . $fileExtension;
            $uploadFile = $uploadDir . $pictureName;

            if (move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
                // Update the database with the new file name
                $stmt = $conn->prepare("UPDATE tbl_user SET Picture = ? WHERE User_Id = ?");
                $stmt->bind_param("si", $pictureName, $userId);
                if (!$stmt->execute()) {
                    echo json_encode(["status" => "error", "message" => "Failed to update profile picture in the database."]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to upload profile picture."]);
                exit;
            }
        }

        // Update password if a new password is provided
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tbl_user SET Password = ? WHERE User_Id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            if (!$stmt->execute()) {
                echo json_encode(["status" => "error", "message" => "Failed to update password."]);
                exit;
            }
        }

        echo json_encode(["status" => "success", "message" => "User updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update user."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Default response for invalid requests
echo json_encode(["status" => "error", "message" => "Invalid request. Check parameters."]);
exit;
