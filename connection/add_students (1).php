<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
require $_SERVER['DOCUMENT_ROOT'] . "/NEW/vendor/autoload.php"; // Ensure the autoloader path is correct

use PhpOffice\PhpSpreadsheet\IOFactory; // Import the class properly

$con = getDbConnection(); // Use function for dynamic connection

$error = ""; // Initialize error variable
$success = false; // To check if record added successfully

// Function to get courses
function getCourses() {
    global $con;
    $sql = "SELECT course_id, course_name FROM tbl_course";
    $result = $con->query($sql);
    $courses = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    return $courses;
}

// Function to get academic years
function getAcademicYears() {
    global $con;
    $sql = "SELECT academicYr_id, Academic_Year FROM tbl_academicyr";
    $result = $con->query($sql);
    $academicYears = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $academicYears[] = $row;
        }
    }
    return $academicYears;
}

// Function to get semesters
function getSemesters() {
    global $con;
    $sql = "SELECT semester_id, semester_name FROM tbl_semester";
    $result = $con->query($sql);
    $semesters = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row;
        }
    }
    return $semesters;
}

// Handle Excel Upload
if (isset($_POST['upload_excel'])) {
    if (isset($_FILES['excel_file']['tmp_name'])) {
        $filePath = $_FILES['excel_file']['tmp_name'];

        try {
            // Load the Excel file using PhpSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip the first row (headers)
            unset($rows[0]);

            foreach ($rows as $row) {
                $studentNumber = trim($row[0]);
                $lastName = trim($row[1]);
                $firstName = trim($row[2]);
                $middleName = trim($row[3]);
                $gender = trim($row[4]);
                $courseName = trim($row[5]);
                $status = trim($row[6]);
                $academicYear = trim($row[7]);
                $semester = trim($row[8]);

                if (empty($studentNumber) || empty($lastName) || empty($firstName)) {
                    continue;
                }

                // Retrieve course ID
                $courseStmt = $con->prepare("SELECT course_id FROM tbl_course WHERE course_name = ?");
                $courseStmt->bind_param("s", $courseName);
                $courseStmt->execute();
                $courseStmt->bind_result($courseId);
                $courseStmt->fetch();
                $courseStmt->close();

                if (!$courseId) continue;

                // Retrieve academic year ID
                $yearStmt = $con->prepare("SELECT academicYr_id FROM tbl_academicyr WHERE Academic_Year = ?");
                $yearStmt->bind_param("s", $academicYear);
                $yearStmt->execute();
                $yearStmt->bind_result($academicYrId);
                $yearStmt->fetch();
                $yearStmt->close();

                if (!$academicYrId) continue;

                // Retrieve semester ID
                $semStmt = $con->prepare("SELECT semester_id FROM tbl_semester WHERE semester_name = ?");
                $semStmt->bind_param("s", $semester);
                $semStmt->execute();
                $semStmt->bind_result($semesterId);
                $semStmt->fetch();
                $semStmt->close();

                if (!$semesterId) continue;

                // Check if student number already exists
                $checkStmt = $con->prepare("SELECT StudentNumber FROM tbl_student WHERE StudentNumber = ?");
                $checkStmt->bind_param("s", $studentNumber);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $checkStmt->close();
                    continue;
                }
                $checkStmt->close();

                // Insert student record
                $stmt = $con->prepare("
                    INSERT INTO tbl_student 
                    (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_id, Status, AcademicYr_id, Semester_id, Category_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->bind_param(
                    "sssssisii",
                    $studentNumber, $lastName, $firstName, $middleName, 
                    $gender, $courseId, $status, $academicYrId, $semesterId
                );

                if (!$stmt->execute()) {
                    $error = "Error inserting student: " . $stmt->error;
                }

                $stmt->close();
            }
            $success = true;
        } catch (Exception $e) {
            $error = "Error loading file: " . $e->getMessage();
        }
    } else {
        $error = "No file selected.";
    }
}
?>
