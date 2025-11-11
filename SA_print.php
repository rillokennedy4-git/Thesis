<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
$con = getDbConnection();

// Get filter values from URL parameters
$yearValue = $_GET['yearValue'] ?? '';
$semesterValue = $_GET['semesterValue'] ?? '';
$programValue = $_GET['programValue'] ?? '';
$statusValue = $_GET['statusValue'] ?? '';
$genderValue = $_GET['genderValue'] ?? '';
$recordStatusValue = $_GET['recordStatusValue'] ?? '';

// Build query with filters
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

";

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

$sql .= " ORDER BY tbl_student.LastName ASC";
$students = $con->query($sql) or die($con->error);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archiving System</title>
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
    /* General screen styles (Print Preview) */
    /* General screen styles (Print Preview) */
/* General screen styles (Print Preview) */
@media screen {
    /* Buttons - Larger Size */
.buttons-container button {
    padding: 15px 15px; /* Larger padding for bigger buttons */
    font-size: 15px; /* Bigger font size */
    border: none;
    border-radius: 8px; /* Slightly rounded corners */
    cursor: pointer;
    transition: transform 0.2s ease-in-out; /* Add a hover effect */
}

.buttons-container button:hover {
    transform: scale(1.05); /* Slight zoom on hover */
}

.print-btn {
    background-color: #007bff; /* Blue */
    color: white;
}

.print-btn:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

.back-btn {
    background-color: #6c757d; /* Gray */
    color: white;
}

.back-btn:hover {
    background-color: #5a6268; /* Darker gray on hover */
}

    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        padding: 0;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        margin-bottom: 10px;
    }

    .header img {
        width: 150px;
        height: auto;
    }

    .header .title {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .buttons-container {
        display: flex;
        justify-content: space-between;
        margin: 10px;
    }

    .table-container {
        margin: 20px auto;
        overflow-x: auto;
    }

    table {
        width: 100%;
        table-layout: auto; /* Columns auto-adjust based on content */
        border-collapse: collapse;
    }

    th, td {
        border: 1px solid #ddd;
        text-align: center;
        padding: 8px;
        font-size: 14px;
        word-wrap: break-word; /* Allow text to wrap */
        white-space: normal; /* Default: allow text wrapping */
    }

    /* Keep Name column in a single line */
    td:nth-child(2), th:nth-child(2) {
        white-space: nowrap; /* Prevent wrapping */
        overflow: hidden; /* Hide any text overflow */
        text-overflow: ellipsis; /* Add ellipsis if text is too long */
    }

    th {
        background-color: #f4f4f4;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
}
    
/* Print-specific styles */
@media print {
    @page {
        margin: 10mm;
    }

    body {
        margin: 0;
        padding: 0;
    }
    
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .header img {
        width: 80px;
        height: auto;
    }
    
    .header .title {
        text-align: center;
        font-size: 12px;
        font-weight: bold;
    }

    .buttons-container {
        display: none; /* Hide buttons during print */
    }

    table {
        width: 100%;
        table-layout: auto;
        border-collapse: collapse;
        margin: 10px auto;
    }

    th, td {
        border: 1px solid #000;
        text-align: center;
        padding: 6px;
        font-size: 12px;
        word-wrap: break-word;
        white-space: normal;
    }

    /* Keep Name column in a single line for print */
    td:nth-child(2), th:nth-child(2) {
        white-space: nowrap; /* Prevent wrapping */
        overflow: hidden;
        text-overflow: ellipsis;
    }

    th {
        background-color: #eaeaea;
        font-size: 13px;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: transparent;
    }
}



</style>


</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="images/bagong.jpg" alt="Bagong Pilipinas">
        <div class="title">
            <p>Republic of the Philippines</p>
            <p><strong>CITY COLLEGE OF TAGAYTAY</strong></p>
            <p>Akle St. Kaybagal South Tagaytay City</p>
            <p>Tel. Nos.: 046-483-0472 / 046-483-0470</p>
        </div>
        <img src="images/cct.jpg" alt="City College of Tagaytay">
    </div>

    <!-- Buttons Container -->
    <div class="buttons-container">
        <button class="back-btn" onclick="window.location.href='SA_report.php'">Back</button>
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <!-- Table Container -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Program</th>
                    <th>Year Enrolled</th>
                    <th>Semester Enrolled</th>
                    <th>Status</th>
                    <th>Record Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $students->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['StudentNumber']; ?></td>
                        <td><?php echo "{$row['LastName']}, {$row['FirstName']} {$row['MiddleName']}"; ?></td>
                        <td><?php echo $row['Gender']; ?></td>
                        <td><?php echo $row['course_name']; ?></td>
                        <td><?php echo $row['Academic_Year']; ?></td>
                        <td><?php echo $row['Semester_Name']; ?></td>
                        <td><?php echo $row['Status']; ?></td>
                        <td><?php echo $row['Record_Status']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <br><br><br><br>
    <?php
    $userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Unknown User'; // Retrieve full name
?>
<!-- Printed by Section -->
<div class="prepared-by">
    <br><br><br><br><br><br><br>
    Printed by: <?php echo strtoupper(htmlspecialchars($userName)); ?>
</div>

</body>
</html>
