<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($userId && $status) {
        $conn = new mysqli("localhost", "root", "", "db_archive");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("UPDATE tbl_user SET Status = ? WHERE User_Id = ?");
        $stmt->bind_param("si", $status, $userId);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }

        $stmt->close();
        $conn->close();
    } else {
        echo 'error';
    }
}
?>
