<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "db_archive");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$documentId = $_POST['documentId'];
$fileTmpPath = $_FILES['file']['tmp_name'] ?? null;

if (!$documentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// If a new file is uploaded
if ($fileTmpPath) {
    $allowedFileType = "application/pdf";
    $fileType = $_FILES['file']['type'];

    if ($fileType != $allowedFileType) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF files are allowed.']);
        exit;
    }

    // Get current file information from the database
    $sqlGetInfo = "
        SELECT d.File_Path, s.StudentNumber, dt.Type_Name AS DocumentType
        FROM tbl_documents d
        JOIN tbl_student s ON d.Student_Id = s.StudentNumber
        JOIN tbl_documenttype dt ON d.DocumentType_Id = dt.DocumentType_ID
        WHERE d.Document_Id = ?
    ";
    $stmtGetInfo = $conn->prepare($sqlGetInfo);
    $stmtGetInfo->bind_param('i', $documentId);
    $stmtGetInfo->execute();
    $result = $stmtGetInfo->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentFilePath = $row['File_Path'];
        $studentNumber = $row['StudentNumber'];
        $documentType = str_replace(" ", "_", $row['DocumentType']);

        // Delete the old file if it exists
        $currentAbsolutePath = __DIR__ . "/../" . $currentFilePath;
        if (file_exists($currentAbsolutePath)) {
            unlink($currentAbsolutePath);
        }

        // Define the new file name and path
        $newFileName = $studentNumber . "_" . $documentType . ".pdf";
        $uploadDir = "uploads/";
        $absoluteUploadDir = __DIR__ . "/uploads/";

        // Create the upload directory if it does not exist
        if (!is_dir($absoluteUploadDir)) {
            mkdir($absoluteUploadDir, 0777, true);
        }

        $newFilePath = $absoluteUploadDir . $newFileName; // Absolute path to save the file
        $relativeFilePath = "connection/uploads/" . $newFileName; // Path to be stored in the database

        // Move the uploaded file to the correct directory with the new name
        if (!move_uploaded_file($fileTmpPath, $newFilePath)) {
            echo json_encode(['success' => false, 'message' => 'Error moving the uploaded file.']);
            exit;
        }

        // Update the document with the new file path and file name
        $sqlUpdate = "UPDATE tbl_documents SET File_Path = ?, FileName = ? WHERE Document_Id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
            exit;
        }
        $stmtUpdate->bind_param('ssi', $relativeFilePath, $newFileName, $documentId);

        if ($stmtUpdate->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update the document.']);
        }

        $stmtUpdate->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
    }

    $stmtGetInfo->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No file was uploaded']);
    exit;
}

$conn->close();
?>
