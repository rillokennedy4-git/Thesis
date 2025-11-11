<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "db_archive");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$documentId = $input['documentId'];

if (!$documentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit;
}

// Fetch document data
$sql = "SELECT Document_Id, Student_Id, File_Path FROM tbl_documents WHERE Document_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $documentId);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    echo json_encode(['success' => true, 'document' => $document]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch document data.']);
}

$stmt->close();
$conn->close();
?>
