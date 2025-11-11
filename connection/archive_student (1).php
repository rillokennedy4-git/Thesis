<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = $_POST['studentNumber'];

    if (!$studentNumber) {
        echo json_encode(['success' => false, 'message' => 'Invalid student number.']);
        exit;
    }

    // Archive the student by setting Category_Id to 2
    $archiveStudentSql = "UPDATE tbl_student SET Category_Id = 2 WHERE StudentNumber = ?";
    $stmt = $conn->prepare($archiveStudentSql);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'SQL preparation failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('i', $studentNumber);

    if ($stmt->execute()) {
        // Archive all documents of the student by setting Category_Id to 2
        $archiveDocsSql = "UPDATE tbl_documents SET Category_Id = 2 WHERE Student_Id = ?";
        $stmtDocs = $conn->prepare($archiveDocsSql);

        if ($stmtDocs === false) {
            echo json_encode(['success' => false, 'message' => 'SQL preparation for documents failed: ' . $conn->error]);
            exit;
        }

        $stmtDocs->bind_param('i', $studentNumber);
        
        if ($stmtDocs->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to archive student documents: ' . $stmtDocs->error]);
        }
        $stmtDocs->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive the student: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
