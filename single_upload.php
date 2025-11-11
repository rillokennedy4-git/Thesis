<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$uploadedBy = $_SESSION['user_id']; // Logged-in user ID

// Ensure only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Validate required fields
if (!isset($_POST['studentId'], $_POST['documentTypeId'], $_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit();
}

$studentId = $_POST['studentId'];
$documentTypeId = $_POST['documentTypeId'];
$file = $_FILES['file'];

// Fetch student status
$studentQuery = "SELECT Status FROM tbl_student WHERE StudentNumber = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit();
}

$student = $result->fetch_assoc();
$status = strtoupper($student['Status']); // Normalize case

// Determine upload paths based on student status
$basePath = $_SERVER['DOCUMENT_ROOT'] . "/NEW/upload/";
if ($status === 'TRANSFEREE') {
    $relativePath = "transfereesfiles/";
} elseif ($status === 'NEW STUDENT') {
    $relativePath = "201files/";
} else {
    $relativePath = "active_students/"; // Default for other categories
}

$uploadPath = $basePath . $relativePath; // Absolute path for saving files

// Ensure the directory exists
if (!is_dir($uploadPath) && !mkdir($uploadPath, 0777, true) && !is_dir($uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to create directory for file uploads.']);
    exit();
}

// Validate file type
$allowedTypes = ['application/pdf'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
    exit();
}

// Fetch document type name
$documentTypeQuery = "SELECT Type_Name FROM tbl_documenttype WHERE DocumentType_ID = ?";
$stmt = $conn->prepare($documentTypeQuery);
$stmt->bind_param("i", $documentTypeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document type.']);
    exit();
}

$documentType = $result->fetch_assoc();
$documentTypeName = str_replace(' ', '_', $documentType['Type_Name']);

// Generate unique filename to prevent overwriting
$newFileName = $studentId . "_" . $documentTypeName . "_" . time() . ".pdf";
$targetFilePath = $uploadPath . $newFileName; // Absolute file path for saving
$databaseFilePath = "upload/" . $relativePath . $newFileName; // Relative path for database

// Move file to the correct directory
if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload the file to the server.']);
    exit();
}

// Insert or update the database record
$insertQuery = "
    INSERT INTO tbl_documents (Student_Id, DocumentType_Id, File_Path, Category_Id, Date_Uploaded, FileName, Uploaded_By)
    VALUES (?, ?, ?, 1, NOW(), ?, ?)
    ON DUPLICATE KEY UPDATE 
        File_Path = VALUES(File_Path), 
        Date_Uploaded = NOW(), 
        FileName = VALUES(FileName),
        Uploaded_By = VALUES(Uploaded_By)
";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("iissi", $studentId, $documentTypeId, $databaseFilePath, $newFileName, $uploadedBy);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully!', 'file_path' => $databaseFilePath]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update the database.']);
}
?>
