<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Default credentials for reset
$default_username = "system.admin";
$default_password = "123systemadmin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if the reset credentials are entered
    if ($username === 'systemadminreset' && $password === 'systemadminreset') {
        // Hash the default password
        $hashed_default_password = password_hash($default_password, PASSWORD_DEFAULT);

        // Prepare the query to reset the default credentials
        $reset_query = "UPDATE tbl_user SET Username = ?, Password = ? WHERE Role = 'System Admin'";
        $stmt = $conn->prepare($reset_query);
        if (!$stmt) {
            $error = "Database error. Please try again later.";
            header("Location: ../index.php?error=" . urlencode($error));
            exit();
        }

        // Bind and execute the reset query
        $stmt->bind_param('ss', $default_username, $hashed_default_password);
        if (!$stmt->execute()) {
            $error = "Database error. Please try again later.";
            header("Location: ../index.php?error=" . urlencode($error));
            exit();
        }

        // Redirect with a success message
        $successMessage = "Default credentials have been reset.";
        header("Location: ../index.php?success=" . urlencode($successMessage));
        exit();
    }

    // Normal login logic
    $query = "SELECT * FROM tbl_user WHERE Username = ?";
    if (isset($conn)) {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $error = "Database error. Please try again later.";
            header("Location: ../index.php?error=" . urlencode($error));
            exit();
        }

        // Bind and execute the query
        $stmt->bind_param('s', $username);
        if (!$stmt->execute()) {
            $error = "Database error. Please try again later.";
            header("Location: ../index.php?error=" . urlencode($error));
            exit();
        }

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $db_password = $user['Password'];

            // Check if the account is inactive
            if ($user['Status'] === 'Inactive') {
                $error = "The account is inactive.";
                header("Location: ../index.php?error=" . urlencode($error));
                exit();
            }

            // Verify the password
            if (password_verify($password, $db_password)) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set session variables on successful login
                $_SESSION['user_id'] = $user['User_id'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['user_name'] = $user['First_Name'] . ' ' . $user['Last_Name']; // Store full name
                $_SESSION['last_activity'] = time();

                // Redirect with success and role parameters
                // Redirect with success and role parameters
$successMessage = "Login successfully";
$role = $user['Role'];
header("Location: ../index.php?success=" . urlencode($successMessage) . "&role=" . urlencode($role));
exit();
            } else {
                // Wrong password
                $error = "Wrong username or password.";
                header("Location: ../index.php?error=" . urlencode($error));
                exit();
            }
        } else {
            // User not found
            $error = "Wrong username or password.";
            header("Location: ../index.php?error=" . urlencode($error));
            exit();
        }
    } else {
        // Database connection error
        $error = "Database connection error. Please try again later.";
        header("Location: ../index.php?error=" . urlencode($error));
        exit();
    }
}
?>