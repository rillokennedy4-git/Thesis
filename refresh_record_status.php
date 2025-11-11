<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

header('Content-Type: application/json'); // Set content type to JSON

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

function checkAndUpdateRecordStatus($studentNumber, $status) {
    global $conn;

    // Define required documents based on student status
    $requiredDocuments = ($status === 'TRANSFEREE') ? [3, 8, 9, 4, 5, 10, 7, 6] : [1, 2, 3, 4, 5, 6, 7];

    // Fetch submitted documents
    $submittedDocumentsSql = "SELECT DocumentType_Id FROM tbl_documents WHERE Student_Id = ?";
    $stmt = $conn->prepare($submittedDocumentsSql);

    if (!$stmt) {
        error_log("Error preparing statement for submitted documents: " . $conn->error);
        throw new Exception("Database error.");
    }

    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    // Collect submitted document types
    $submittedDocuments = [];
    while ($row = $result->fetch_assoc()) {
        $submittedDocuments[] = $row['DocumentType_Id'];
    }

    // Check if all required documents have been submitted
    $isComplete = !array_diff($requiredDocuments, $submittedDocuments);

    if ($isComplete) {
        // Update record status in tbl_record_status
        $updateStatusSql = "
            INSERT INTO tbl_record_status (StudentNumber, Record_Status) 
            VALUES (?, 'COMPLETE') 
            ON DUPLICATE KEY UPDATE Record_Status = 'COMPLETE'
        ";
        $stmtUpdate = $conn->prepare($updateStatusSql);

        if (!$stmtUpdate) {
            error_log("Error preparing update statement for student $studentNumber: " . $conn->error);
            throw new Exception("Database error.");
        }

        $stmtUpdate->bind_param("s", $studentNumber);
        if (!$stmtUpdate->execute()) {
            error_log("Error executing update for student $studentNumber: " . $stmtUpdate->error);
            throw new Exception("Database error.");
        }
    }
}

try {
    // Get all students
    $sql = "SELECT StudentNumber, Status FROM tbl_student WHERE Category_id = 1";
    $students = $conn->query($sql);

    if (!$students) {
        error_log("Error fetching students: " . $conn->error);
        throw new Exception("Database error.");
    }

    // Update each student's status
    while ($student = $students->fetch_assoc()) {
        checkAndUpdateRecordStatus($student['StudentNumber'], $student['Status']);
    }

    // Return success message
    echo json_encode(['success' => true, 'message' => 'Record status refreshed successfully.']);
} catch (Exception $e) {
    // Return error message
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
