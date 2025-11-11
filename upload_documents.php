<?php
session_start(); // Ensure session is started to access user_id

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $con = getDbConnection();

    $studentNumber = $_POST['StudentNumber'];
    $documentTypes = $_POST['DocumentType'];
    $uploadedBy = $_SESSION['user_id']; // Use session variable for Uploaded_By

    // Fetch student's status
    $stmt = $con->prepare("SELECT Status FROM tbl_student WHERE StudentNumber = ?");
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $stmt->bind_result($studentStatus);
    $stmt->fetch();
    $stmt->close();

    if (!$studentStatus) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Student status not found."]);
        exit;
    }

    $uploadPath = ($studentStatus === "NEW STUDENT") ? "upload/201files/" : "upload/Transfereesfiles/";
    if (!file_exists($uploadPath) && !mkdir($uploadPath, 0777, true)) {
        echo json_encode(["success" => false, "message" => "Failed to create directory."]);
        exit;
    }

    $uploadedFiles = $_FILES['documents'];
    $response = [];
    $successCount = 0;

    for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
        $fileName = $uploadedFiles['name'][$i];
        $fileTmpName = $uploadedFiles['tmp_name'][$i];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($uploadedFiles['error'][$i] !== UPLOAD_ERR_OK || $fileExtension !== "pdf") {
            $response[] = ["file" => $fileName, "status" => "error", "message" => "Invalid file or error during upload."];
            continue;
        }

        $documentType = $documentTypes[$i];
        $uniqueFileName = $studentNumber . "_" . str_replace(' ', '_', $documentType) . ".pdf";
        $destinationPath = $uploadPath . $uniqueFileName;

        if (move_uploaded_file($fileTmpName, $destinationPath)) {
            $stmt = $con->prepare("SELECT DocumentType_Id FROM tbl_documenttype WHERE Type_Name = ?");
            $stmt->bind_param("s", $documentType);
            $stmt->execute();
            $stmt->bind_result($documentTypeId);
            $stmt->fetch();
            $stmt->close();

            if ($documentTypeId) {
                $stmt = $con->prepare("
                    INSERT INTO tbl_documents (Student_Id, DocumentType_Id, File_Path, FileName, Category_Id, Date_Uploaded, Uploaded_By)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ");
                $categoryId = 1; // Active category
                $stmt->bind_param("iissis", $studentNumber, $documentTypeId, $destinationPath, $uniqueFileName, $categoryId, $uploadedBy);

                if ($stmt->execute()) {
                    $successCount++;
                    $response[] = ["file" => $fileName, "status" => "success", "message" => "File uploaded successfully."];
                } else {
                    $response[] = ["file" => $fileName, "status" => "error", "message" => "Database error: " . $stmt->error];
                }
                $stmt->close();
            } else {
                $response[] = ["file" => $fileName, "status" => "error", "message" => "Invalid document type."];
            }
        } else {
            $response[] = ["file" => $fileName, "status" => "error", "message" => "Failed to move uploaded file."];
        }
    }

    // Check if the student's record is complete and archive if necessary
    $shouldArchive = false;
    if ($successCount > 0) {
        $shouldArchive = autoArchiveStudent($con, $studentNumber, $studentStatus);
    }

    $con->close();

    header('Content-Type: application/json');
    echo json_encode(["success" => true, "successCount" => $successCount, "response" => $response, "shouldArchive" => $shouldArchive]);
}

/**
 * Auto-archive a student if their record is complete.
 */
function autoArchiveStudent($con, $studentNumber, $studentStatus) {
    // Define required documents based on student status
    $requiredDocuments = ($studentStatus === 'TRANSFEREE') ? [3, 8, 9, 4, 5, 10, 7, 6] : [1, 2, 3, 4, 5, 6, 7];

    // Fetch submitted documents
    $submittedDocumentsSql = "SELECT DocumentType_Id FROM tbl_documents WHERE Student_Id = ?";
    $stmt = $con->prepare($submittedDocumentsSql);
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
        // Update record status to "COMPLETE"
        $updateStatusSql = "
            INSERT INTO tbl_record_status (StudentNumber, Record_Status) 
            VALUES (?, 'COMPLETE') 
            ON DUPLICATE KEY UPDATE Record_Status = 'COMPLETE'
        ";
        $stmtUpdate = $con->prepare($updateStatusSql);
        $stmtUpdate->bind_param("s", $studentNumber);
        $stmtUpdate->execute();

        // Archive the student
        archiveStudent($con, $studentNumber);
        return true;
    }
    return false;
}

/**
 * Archive a student by moving their files and updating the database.
 */
function archiveStudent($con, $studentNumber) {
    // Fetch student data
    $fetchStudentSql = "
        SELECT 
            s.StudentNumber, s.LastName, s.FirstName, s.MiddleName, s.Gender, s.Course_Id, 
            s.Status, s.AcademicYr_Id, s.Semester_Id, a.Academic_Year
        FROM tbl_student s
        JOIN tbl_academicyr a ON s.AcademicYr_Id = a.AcademicYr_Id
        WHERE s.StudentNumber = ?
    ";
    $stmt = $con->prepare($fetchStudentSql);
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $studentData = $stmt->get_result()->fetch_assoc();

    if (!$studentData) {
        throw new Exception("Student not found.");
    }

    $dateArchived = date('Y-m-d H:i:s');

    // Define the archive folder structure
    $archiveBasePath = $_SERVER['DOCUMENT_ROOT'] . "/NEW/archive/";
    $academicYearFolder = "A.Y. " . $studentData['Academic_Year'];
    $studentFolder = $studentData['StudentNumber'] . "_" . $studentData['LastName'] . "_" . $studentData['FirstName'];
    $destinationPath = $archiveBasePath . $academicYearFolder . "/" . $studentFolder . "/";

    // Create the academic year and student folders if they don't exist
    if (!is_dir($archiveBasePath . $academicYearFolder)) {
        mkdir($archiveBasePath . $academicYearFolder, 0777, true);
    }
    if (!is_dir($destinationPath)) {
        mkdir($destinationPath, 0777, true);
    }

    // Move files from upload to archive folder
    $uploadPath = $_SERVER['DOCUMENT_ROOT'] . "/NEW/upload/";
    $sourceFolder = ($studentData['Status'] == 'NEW STUDENT') ? '201files/' : 'Transfereesfiles/';
    $sourcePath = $uploadPath . $sourceFolder;

    if (is_dir($sourcePath)) {
        $files = glob($sourcePath . $studentData['StudentNumber'] . "_*");
        foreach ($files as $file) {
            $fileName = basename($file);
            $destinationFile = $destinationPath . $fileName;

            if (rename($file, $destinationFile)) {
                // Update File_Path in tbl_documents
                $newFilePath = "archive/" . $academicYearFolder . "/" . $studentFolder . "/" . $fileName;
                $updateFilePathSql = "
                    UPDATE tbl_documents 
                    SET File_Path = ?, Category_Id = 2
                    WHERE Student_Id = ? AND File_Path LIKE ?
                ";
                $stmtUpdate = $con->prepare($updateFilePathSql);
                $oldFilePathPattern = "%" . $fileName;
                $stmtUpdate->bind_param("sss", $newFilePath, $studentData['StudentNumber'], $oldFilePathPattern);
                $stmtUpdate->execute();
            }
        }
    }

    // Update Category_Id to 2 (Archived) in tbl_student
    $updateStudentSql = "
        UPDATE tbl_student 
        SET Category_Id = 2 
        WHERE StudentNumber = ?
    ";
    $stmtUpdate = $con->prepare($updateStudentSql);
    $stmtUpdate->bind_param("s", $studentData['StudentNumber']);
    $stmtUpdate->execute();

    // Insert student data into tbl_archive
    $insertArchiveSql = "
        INSERT INTO tbl_archive 
        (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id, Category_Id, Date_Archived)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2, ?)
    ";
    $stmtInsert = $con->prepare($insertArchiveSql);
    $stmtInsert->bind_param(
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
    $stmtInsert->execute();
}
?>