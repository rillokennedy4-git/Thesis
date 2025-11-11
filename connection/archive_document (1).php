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

// Update the Category_Id to "2" (Archived)
$sql = "UPDATE tbl_documents SET Category_Id = 2 WHERE Document_Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $documentId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to archive the document.']);
}

$stmt->close();
$conn->close();
?>
