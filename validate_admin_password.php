<?php
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'];

$stmt = $con->prepare("SELECT Password FROM tbl_user WHERE Username = 'system.admin'");
$stmt->execute();
$stmt->bind_result($storedPasswordHash);
$stmt->fetch();
$stmt->close();

if (password_verify($password, $storedPasswordHash)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$con->close();
?>