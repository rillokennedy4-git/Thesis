<?php
// Include the Composer autoloader to load PhpSpreadsheet and other dependencies
require __DIR__ . '/vendor/autoload.php';

// Include database connection
include_once("connections/connection.php");
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to get the database connection
$con = getDbConnection();

// Handle Excel template download
if (isset($_POST['download_template'])) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add headers to the Excel sheet
    $sheet->setCellValue('A1', 'StudentNumber');
    $sheet->setCellValue('B1', 'LastName');
    $sheet->setCellValue('C1', 'FirstName');
    $sheet->setCellValue('D1', 'MiddleName');
    $sheet->setCellValue('E1', 'Gender');
    $sheet->setCellValue('F1', 'Course Name');
    $sheet->setCellValue('G1', 'Status');
    $sheet->setCellValue('H1', 'Academic Year');
    $sheet->setCellValue('I1', 'Semester');

    // Set the file name and export the Excel template
    $writer = new Xlsx($spreadsheet);
    $fileName = 'Student_Registration_Template.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    $writer->save("php://output");
    exit;
}

// Handle Excel file upload and data processing
if (isset($_POST['upload_excel'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Verify if file uploaded successfully
    if (!is_uploaded_file($file)) {
        echo "<script>alert('Failed to upload the file.');</script>";
        exit;
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($file);
    } catch (Exception $e) {
        echo "<script>alert('Error loading file: " . $e->getMessage() . "');</script>";
        exit;
    }

    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow(); // Get the total number of rows

    $con = getDbConnection();

    $errors = [];
    $successCount = 0;

    for ($row = 2; $row <= $highestRow; $row++) { // Start from row 2, assuming row 1 is headers
        $studentNumber = strtoupper($sheet->getCell('A' . $row)->getValue());
        $lastName = strtoupper($sheet->getCell('B' . $row)->getValue());
        $firstName = strtoupper($sheet->getCell('C' . $row)->getValue());
        $middleName = strtoupper($sheet->getCell('D' . $row)->getValue());
        $gender = strtoupper($sheet->getCell('E' . $row)->getValue());
        $courseName = strtoupper($sheet->getCell('F' . $row)->getValue());
        $status = strtoupper($sheet->getCell('G' . $row)->getValue());
        $academicYear = strtoupper($sheet->getCell('H' . $row)->getValue());
        $semesterName = strtoupper($sheet->getCell('I' . $row)->getValue());
        $categoryId = 1; // Default category

        // Validate Course_Id
        $stmt = $con->prepare("SELECT course_id FROM tbl_course WHERE UPPER(course_name) = ?");
        $stmt->bind_param("s", $courseName);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($courseId);
            $stmt->fetch();
        } else {
            $errors[] = "Row $row: Invalid Course - $courseName does not exist.";
            continue;
        }

        // Validate AcademicYr_Id
        $stmt = $con->prepare("SELECT AcademicYr_Id FROM tbl_academicyr WHERE UPPER(Academic_Year) = ?");
        $stmt->bind_param("s", $academicYear);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($academicYrId);
            $stmt->fetch();
        } else {
            $errors[] = "Row $row: Invalid Academic Year - $academicYear does not exist.";
            continue;
        }

        // Validate Semester_Id
        $stmt = $con->prepare("SELECT Semester_Id FROM tbl_semester WHERE UPPER(Semester_Name) = ?");
        $stmt->bind_param("s", $semesterName);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($semesterId);
            $stmt->fetch();
        } else {
            $errors[] = "Row $row: Invalid Semester - $semesterName does not exist.";
            continue;
        }

        // Check if student number already exists
        $stmt = $con->prepare("SELECT StudentNumber FROM tbl_student WHERE StudentNumber = ?");
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Row $row: Student number $studentNumber already exists.";
        } else {
            // Insert student data
            $stmt = $con->prepare("INSERT INTO tbl_student (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id, Category_Id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisiii", $studentNumber, $lastName, $firstName, $middleName, $gender, $courseId, $status, $academicYrId, $semesterId, $categoryId);

            if ($stmt->execute()) {
                // Insert default "PENDING" status in tbl_record_status
                $recordStmt = $con->prepare("INSERT INTO tbl_record_status (StudentNumber, record_status) VALUES (?, 'PENDING')");
                $recordStmt->bind_param("s", $studentNumber);
                $recordStmt->execute();
                $recordStmt->close();

                $successCount++;
            } else {
                $errors[] = "Row $row: Error inserting data - " . $stmt->error;
            }
        }
    }

    $con->close();

    if (empty($errors)) {
        echo "<script>alert('All data successfully inserted.');</script>";
    } else {
        $errorMessages = implode("\\n", $errors);
        echo "<script>alert('The following errors occurred:\\n$errorMessages');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Data Upload</title>
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Student Data Upload</h2>
        <form method="POST">
            <button type="submit" name="download_template" class="btn btn-primary">Download Excel Template</button>
        </form>

        <form method="POST" enctype="multipart/form-data" class="mt-3">
            <div class="form-group">
                <label for="excel_file">Upload Excel File:</label>
                <input type="file" name="excel_file" class="form-control" required>
            </div>
            <button type="submit" name="upload_excel" class="btn btn-success mt-3">Upload Excel</button>
        </form>
    </div>
</body>
</html>
