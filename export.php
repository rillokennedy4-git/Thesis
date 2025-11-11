<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet autoload file
include_once("database/db_connection.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$con = getDbConnection();

// Get filter values from URL
$yearValue = isset($_GET['yearValue']) ? $_GET['yearValue'] : "";
$semesterValue = isset($_GET['semesterValue']) ? $_GET['semesterValue'] : "";
$programValue = isset($_GET['programValue']) ? $_GET['programValue'] : "";
$statusValue = isset($_GET['statusValue']) ? $_GET['statusValue'] : "";
$genderValue = isset($_GET['genderValue']) ? $_GET['genderValue'] : "";
$recordStatusValue = isset($_GET['recordStatusValue']) ? $_GET['recordStatusValue'] : "";

// Construct the query based on filter values
$sql = "
SELECT 
    tbl_student.StudentNumber,
    tbl_student.FirstName,
    tbl_student.MiddleName,
    tbl_student.LastName,
    tbl_student.Gender,
    tbl_course.course_name,
    tbl_academicyr.Academic_Year,
    tbl_semester.Semester_Name,
    tbl_student.Status,
    tbl_record_status.Record_Status
FROM 
    tbl_student
JOIN 
    tbl_course ON tbl_student.Course_id = tbl_course.Course_id
JOIN 
    tbl_academicyr ON tbl_student.AcademicYr_id = tbl_academicyr.AcademicYr_id
JOIN 
    tbl_semester ON tbl_student.Semester_id = tbl_semester.Semester_id
JOIN
    tbl_record_status ON tbl_student.StudentNumber = tbl_record_status.StudentNumber
WHERE 
    tbl_student.Category_id = 1
";

// Apply filters if set
if ($yearValue !== "") {
    $sql .= " AND tbl_academicyr.Academic_Year = '$yearValue'";
}
if ($semesterValue !== "") {
    $sql .= " AND tbl_semester.Semester_Name = '$semesterValue'";
}
if ($programValue !== "") {
    $sql .= " AND tbl_course.course_name = '$programValue'";
}
if ($statusValue !== "") {
    $sql .= " AND tbl_student.Status = '$statusValue'";
}
if ($genderValue !== "") {
    $sql .= " AND tbl_student.Gender = '$genderValue'";
}
if ($recordStatusValue !== "") {
    $sql .= " AND tbl_record_status.Record_Status = '$recordStatusValue'";
}

// Finalize query with ordering
$sql .= " ORDER BY tbl_student.LastName ASC";

$students = $con->query($sql) or die($con->error);

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the headers for the Excel file
$sheet->setCellValue('A1', 'Student Number');
$sheet->setCellValue('B1', 'First Name');
$sheet->setCellValue('C1', 'Middle Name');
$sheet->setCellValue('D1', 'Last Name');
$sheet->setCellValue('E1', 'Gender');
$sheet->setCellValue('F1', 'Program');
$sheet->setCellValue('G1', 'Academic Year');
$sheet->setCellValue('H1', 'Semester');
$sheet->setCellValue('I1', 'Status');
$sheet->setCellValue('J1', 'Record Status'); // New header for Record Status

// Add data to the Excel file
$rowCount = 2;

while ($row = $students->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowCount, $row['StudentNumber']);
    $sheet->setCellValue('B' . $rowCount, $row['FirstName']);
    $sheet->setCellValue('C' . $rowCount, $row['MiddleName']);
    $sheet->setCellValue('D' . $rowCount, $row['LastName']);
    $sheet->setCellValue('E' . $rowCount, $row['Gender']);
    $sheet->setCellValue('F' . $rowCount, $row['course_name']);
    $sheet->setCellValue('G' . $rowCount, $row['Academic_Year']);
    $sheet->setCellValue('H' . $rowCount, $row['Semester_Name']);
    $sheet->setCellValue('I' . $rowCount, $row['Status']);
    $sheet->setCellValue('J' . $rowCount, $row['Record_Status']); // Add Record Status to each row
    $rowCount++;
}

// Write the Excel file
$writer = new Xlsx($spreadsheet);

// Set the headers to force download the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="students.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>
