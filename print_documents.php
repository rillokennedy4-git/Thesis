<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}

include_once("database/db_connection.php");
$con = getDbConnection();

// Get filter values from the URL
$academicYear = $_GET['academic_year'] ?? '';
$course = $_GET['course'] ?? '';
$status = $_GET['status'] ?? '';

// Fetch all document types
$docTypeQuery = "SELECT DocumentType_Id, Type_Name FROM tbl_documenttype";
$docTypeResult = mysqli_query($con, $docTypeQuery);

if (!$docTypeResult) {
    die("Error fetching document types: " . mysqli_error($con));
}

$docTypes = [];
while ($row = mysqli_fetch_assoc($docTypeResult)) {
    $docTypes[$row['DocumentType_Id']] = $row['Type_Name'];
}

// Build the query dynamically
$query = "
    SELECT 
        s.StudentNumber, 
        CONCAT(s.LastName, ', ', s.FirstName, ' ', IFNULL(s.MiddleName, '')) AS StudentName, 
        c.Course_name AS Course, 
        ay.academic_year AS AcademicYear, 
        s.Status AS Category, 
        d.DocumentType_Id
    FROM tbl_student s
    LEFT JOIN tbl_documents d ON s.StudentNumber = d.Student_Id
    LEFT JOIN tbl_course c ON s.Course_Id = c.Course_Id
    LEFT JOIN tbl_academicyr ay ON s.AcademicYr_Id = ay.AcademicYr_Id
    WHERE 1=1
";

if ($academicYear) {
    $query .= " AND s.AcademicYr_Id = '" . mysqli_real_escape_string($con, $academicYear) . "'";
}
if ($course) {
    $query .= " AND s.Course_Id = '" . mysqli_real_escape_string($con, $course) . "'";
}
if ($status) {
    $query .= " AND s.Status = '" . mysqli_real_escape_string($con, $status) . "'";
}

$result = mysqli_query($con, $query);

if (!$result) {
    die("Error fetching data: " . mysqli_error($con));
}

// Prepare data for rendering
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $studentId = $row['StudentNumber'];
    $category = strtolower($row['Category']) === 'transferee' ? 'TRANSFEREE' : 'NEW STUDENT';

    if (!isset($students[$studentId])) {
        $students[$studentId] = [
            'StudentName' => $row['StudentName'],
            'Course' => $row['Course'],
            'Category' => $category,
            'AcademicYear' => $row['AcademicYear'],
            'Documents' => array_fill_keys(array_keys($docTypes), 'NOT REQUIRED'),
        ];
    }

    // Define relevant documents for each category
    $transferDocs = ['COA', 'TOR', 'TRANSFER CREDENTIALS', 'GOOD MORAL', '2x2 PICTURE', 'APPLICATION ADMISSION', 'BIRTH CERTIFICATE', 'BARANGAY CLEARANCE'];
    $newDocs = ['FORM137', 'COA', 'FORM138', 'GOOD MORAL', '2x2 PICTURE', 'BIRTH CERTIFICATE', 'BARANGAY CLEARANCE'];
    $relevantDocs = $category === 'TRANSFEREE' ? $transferDocs : ($category === 'NEW STUDENT' ? $newDocs : []);

    // Update document status
    foreach ($docTypes as $typeId => $typeName) {
        if (in_array($typeName, $relevantDocs)) {
            $students[$studentId]['Documents'][$typeId] = false; // Default to "✘"
        }
    }

    // Mark uploaded documents as "✔"
    if ($row['DocumentType_Id'] && isset($students[$studentId]['Documents'][$row['DocumentType_Id']])) {
        $students[$studentId]['Documents'][$row['DocumentType_Id']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archiving System</title>
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
    /* General styling */
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .header img {
        width: 100px;
        height: auto;
    }
    .header .title {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
    }
    .buttons-container {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .btn-secondary {
        background-color: #6c757d; /* Gray for "Back" button */
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
    }
    .btn-secondary:hover {
        background-color: #5a6268; /* Slightly darker gray on hover */
    }
    .btn-primary {
        background-color: #007bff; /* Blue for "Print" button */
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
    }
    .btn-primary:hover {
        background-color: #0056b3; /* Slightly darker blue on hover */
    }

    /* Adjust table styles */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px; /* Reduce font size */
    }
    th, td {
        border: 1px solid black;
        padding: 5px; /* Reduce padding */
        text-align: center;
    }
    th {
        background-color: #f2f2f2;
    }
    td:nth-child(2), td:nth-child(3), td:nth-child(4) {
        white-space: nowrap; /* Prevent wrapping for specific columns */
    }
    .check {
        color: green;
        font-weight: bold;
    }
    .cross {
        color: red;
        font-weight: bold;
    }
    .unrequired {
        color: orange;
        font-weight: bold;
    }

    /* Enforce landscape orientation for printing */
    @page {
        size: A4 landscape; /* Ensure landscape orientation */
        margin: 10mm; /* Adjust margins */
    }
    @media print {
        .buttons-container {
            display: none;
        }
        body {
            margin: 0;
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

    <!-- Buttons -->
    <div class="buttons-container">
    <button class="btn btn-secondary" onclick="window.history.back()">Back</button>
    <button class="btn btn-primary" onclick="window.print()">Print</button>
</div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Academic Year</th>
                <th>Status</th>
                <?php foreach ($docTypes as $typeName): ?>
                    <th><?= htmlspecialchars($typeName) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $studentNumber => $studentData): ?>
                <tr>
                    <td><?= htmlspecialchars($studentNumber) ?></td>
                    <td><?= htmlspecialchars($studentData['StudentName']) ?></td>
                    <td><?= htmlspecialchars($studentData['Course']) ?></td>
                    <td><?= htmlspecialchars($studentData['AcademicYear']) ?></td>
                    <td><?= htmlspecialchars($studentData['Category']) ?></td>
                    <?php foreach ($studentData['Documents'] as $status): ?>
                        <td>
                            <?php if ($status === true): ?>
                                <span class="check">&#x2714;</span>
                            <?php elseif ($status === false): ?>
                                <span class="cross">&#x2718;</span>
                            <?php else: ?>
                                <span class="unrequired">NOT REQUIRED</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
