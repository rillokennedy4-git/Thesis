<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentNumbers'])) {
    $studentNumbers = $_POST['studentNumbers'];
    $dateArchived = date('Y-m-d H:i:s');
    
    $success = true;
    $message = '';

    foreach ($studentNumbers as $studentNumber) {
        // Fetch student details
        $sql = "
            SELECT StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id
            FROM tbl_student
            WHERE StudentNumber = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $studentData = $result->fetch_assoc();

        if ($studentData) {
            // Insert student data into tbl_archive
            $insertArchiveSql = "
                INSERT INTO tbl_archive 
                (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id, Category_Id, Date_Archived)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2, ?)
            ";
            $archiveStmt = $conn->prepare($insertArchiveSql);
            $archiveStmt->bind_param(
                "sssssiisis",
                $studentData['StudentNumber'],
                $studentData['LastName'],
                $studentData['FirstName'],
                $studentData['MiddleName'],
                $studentData['Gender'],
                $studentData['Course_Id'],
                $studentData['Status'],
                $studentData['AcademicYr_Id'],
                $studentData['Semester_Id'],
                $dateArchived
            );

            if (!$archiveStmt->execute()) {
                $success = false;
                $message = 'Error inserting into tbl_archive: ' . $conn->error;
                break;
            }

            // Update the student Category_Id in tbl_student to 2 (Archived)
            $updateStudentSql = "
                UPDATE tbl_student 
                SET Category_Id = 2 
                WHERE StudentNumber = ?
            ";
            $updateStmt = $conn->prepare($updateStudentSql);
            $updateStmt->bind_param("s", $studentNumber);

            if (!$updateStmt->execute()) {
                $success = false;
                $message = 'Error updating tbl_student: ' . $conn->error;
                break;
            }

            // Update Category_Id in tbl_documents to 2 (Archived) for the student's documents
            $updateDocumentsSql = "
                UPDATE tbl_documents 
                SET Category_Id = 2 
                WHERE Student_Id = ?
            ";
            $updateDocumentsStmt = $conn->prepare($updateDocumentsSql);
            $updateDocumentsStmt->bind_param("s", $studentNumber);

            if (!$updateDocumentsStmt->execute()) {
                $success = false;
                $message = 'Error updating tbl_documents: ' . $conn->error;
                break;
            }
        } else {
            $success = false;
            $message = "Student with StudentNumber $studentNumber not found.";
            break;
        }
    }

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Selected students archived successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => $message]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
