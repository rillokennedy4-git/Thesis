<?php
// Database connection
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNumber = $_POST['student_number'] ?? null;
    $documentTypeId = 12; // DocumentType_ID for "Shifting Form"
    $categoryId = 1; // Adjust if needed (e.g., 1 for Active)
    $uploadDir = "section_staff/upload/Shiftingform/"; // Full upload directory path
    $dbPath = "upload/Shiftingform/"; // Relative path for storing in the database

    // Ensure the directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Verify if a file is provided in the request
    if (isset($_FILES['shifting_form_file']) && $_FILES['shifting_form_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['shifting_form_file']['tmp_name'];
        $fileExtension = pathinfo($_FILES['shifting_form_file']['name'], PATHINFO_EXTENSION);

        // Only allow PDF files
        if (strtolower($fileExtension) !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
            exit;
        }

        // Define the new file name and paths
        $newFileName = $studentNumber . "_ShiftingForm." . $fileExtension;
        $destPath = $uploadDir . $newFileName; // Full path for moving the file
        $dbFilePath = $dbPath . $newFileName; // Path to store in the database

        // Attempt to move the file to the target directory
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $dateUploaded = date("Y-m-d H:i:s");

            // Insert the file record into tbl_documents
            $sql = "INSERT INTO tbl_documents (Student_Id, DocumentType_Id, File_Path, Category_Id, Date_Uploaded, FileName) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sissss", $studentNumber, $documentTypeId, $dbFilePath, $categoryId, $dateUploaded, $newFileName);

                if ($stmt->execute()) {
                    // Update the student's status to "IRREGULAR" in tbl_student
                    $updateStatusSql = "UPDATE tbl_student SET Status = 'IRREGULAR' WHERE StudentNumber = ?";
                    $updateStmt = $conn->prepare($updateStatusSql);
                    if ($updateStmt) {
                        $updateStmt->bind_param("s", $studentNumber);
                        if ($updateStmt->execute()) {
                            echo json_encode(['success' => true, 'message' => 'Shifting Form uploaded and status updated to IRREGULAR.']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update student status.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to prepare status update statement.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save document in the database.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare document insertion statement.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move the uploaded file.']);
        }
    } else {
        // Log upload error details for debugging
        $uploadError = $_FILES['shifting_form_file']['error'] ?? 'No file found';
        error_log("File upload error (Code: $uploadError): " . json_encode($_FILES));

        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    }
}
?>
