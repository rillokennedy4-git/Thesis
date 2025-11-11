<?php
session_start();

// Check if the user is logged in by verifying the presence of `user_id` in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
if ($_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}

include_once("database/db_connection.php");
$con = getDbConnection();

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

// Fetch filtering data for Academic Year, Course, and Status
$academicYearsQuery = "SELECT AcademicYr_Id, academic_year FROM tbl_academicyr";
$academicYearsResult = mysqli_query($con, $academicYearsQuery);

if (!$academicYearsResult) {
    die("Error fetching academic years: " . mysqli_error($con));
}

$academicYears = [];
while ($row = mysqli_fetch_assoc($academicYearsResult)) {
    $academicYears[$row['AcademicYr_Id']] = $row['academic_year'];
}

$coursesQuery = "SELECT Course_Id, Course_name FROM tbl_course";
$coursesResult = mysqli_query($con, $coursesQuery);

if (!$coursesResult) {
    die("Error fetching courses: " . mysqli_error($con));
}

$courses = [];
while ($row = mysqli_fetch_assoc($coursesResult)) {
    $courses[$row['Course_Id']] = $row['Course_name'];
}

$statusOptions = ['NEW STUDENT', 'TRANSFEREE'];

// Handle filtering inputs
$selectedYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$selectedCourse = isset($_GET['course']) ? $_GET['course'] : '';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query dynamically based on filters
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

if ($selectedYear) {
    $query .= " AND s.AcademicYr_Id = '" . mysqli_real_escape_string($con, $selectedYear) . "'";
}
if ($selectedCourse) {
    $query .= " AND s.Course_Id = '" . mysqli_real_escape_string($con, $selectedCourse) . "'";
}
if ($selectedStatus) {
    $query .= " AND s.Status = '" . mysqli_real_escape_string($con, $selectedStatus) . "'";
}

$result = mysqli_query($con, $query);

if (!$result) {
    die("Error fetching student data: " . mysqli_error($con));
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
            'Documents' => array_fill_keys(array_keys($docTypes), false), // Default to unchecked
        ];
    }

    // Check if document type is relevant for the student's category
    $transferDocs = ['COA', 'TOR', 'TRANSFER CREDENTIALS', 'GOOD MORAL', '2x2 PICTURE', 'APPLICATION ADMISSION', 'BIRTH CERTIFICATE', 'BARANGAY CLEARANCE'];
    $newDocs = ['FORM137', 'COA', 'FORM138', 'GOOD MORAL', '2x2 PICTURE', 'BIRTH CERTIFICATE', 'BARANGAY CLEARANCE'];
    $relevantDocs = $category === 'TRANSFEREE' ? $transferDocs : $newDocs;

    // Ensure that only relevant documents are marked
    foreach ($docTypes as $typeId => $typeName) {
        if (in_array($typeName, $relevantDocs)) {
            if (!isset($students[$studentId]['Documents'][$typeId])) {
                $students[$studentId]['Documents'][$typeId] = false; // Default unchecked
            }
        } else {
            $students[$studentId]['Documents'][$typeId] = 'NOT REQUIRED'; // Mark as 'NOT REQUIRED'
        }
    }

    // Mark uploaded documents as checked (✔)
    if ($row['DocumentType_Id']) {
        $students[$studentId]['Documents'][$row['DocumentType_Id']] = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiving System</title>
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        #searchButton {
            white-space: nowrap; /* Prevents the button text from wrapping */
        }
        .row {
            display: flex;
            flex-wrap: nowrap;
            gap: 1rem; /* Space between each filter item */
            align-items: flex-end; /* Aligns labels and dropdowns with the button */
        }
        .col-md-2 {
            flex: 1; /* Makes each item take equal width */
            min-width: 150px; /* Ensures dropdowns have a minimum width */
        }
        /* Add this CSS to prevent text wrapping in table cells */
        #datatablesSimple th,
        #datatablesSimple td {
            white-space: nowrap;
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

    </style>
</head>
<body>
    <div>
        <?php include 'topnav.php'; ?>
    </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
        <main>
            <div class="container mt-5">
                <h1 class="mb-3">CHECKLIST OF DOCUMENTS</h1>
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select id="academic_year" name="academic_year" class="form-select">
                                <option value="">-- All Academic Years --</option>
                                <?php foreach ($academicYears as $yearId => $yearName): ?>
                                    <option value="<?= htmlspecialchars($yearId) ?>" <?= $selectedYear == $yearId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($yearName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="course" class="form-label">Course</label>
                            <select id="course" name="course" class="form-select">
                                <option value="">-- All Courses --</option>
                                <?php foreach ($courses as $courseId => $courseName): ?>
                                    <option value="<?= htmlspecialchars($courseId) ?>" <?= $selectedCourse == $courseId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($courseName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">-- All Statuses --</option>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= $selectedStatus == $status ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" id="searchButton" class="btn btn-primary">Filter</button>
                            <a id="printButton" href="#" class="btn btn-secondary">Print</a>
                        </div>
                    </div>

                </form>
                <div class="card">
                    <div class="card-body">
                        <table id="datatablesSimple" class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student Number</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Academic Year</th> <!-- New column -->
                                    <th>Status</th>
                                    <?php foreach ($docTypes as $typeName): ?>
                                        <th><?= htmlspecialchars($typeName) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                <?php foreach ($students as $studentNumber => $studentData): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($studentNumber) ?></td>
                                        <td><?= htmlspecialchars($studentData['StudentName']) ?></td>
                                        <td><?= htmlspecialchars($studentData['Course']) ?></td>
                                        <td><?= htmlspecialchars($studentData['AcademicYear']) ?></td> <!-- Display academic year -->
                                        <td><?= htmlspecialchars($studentData['Category']) ?></td>
                                        <?php foreach ($studentData['Documents'] as $status): ?>
                                            <td>
                                                <?php if ($status === true): ?>
                                                    <span style="color: green; font-weight: bold;">&#x2714;</span>
                                                <?php elseif ($status === false): ?>
                                                    <span style="color: red; font-weight: bold;">&#x2718;</span>
                                                <?php else: ?>
                                                    <span style="color: orange; font-weight: bold;">NOT REQUIRED</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    </main>
            <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>
            <script>
document.getElementById('printButton').addEventListener('click', function (event) {
    event.preventDefault(); // Prevent default action

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to print the checklist?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Print',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // If user confirms, construct the URL and redirect
            const academicYear = document.getElementById('academic_year').value;
            const course = document.getElementById('course').value;
            const status = document.getElementById('status').value;

            // Construct the URL for the print preview page
            const printUrl = `print_documents.php?academic_year=${academicYear}&course=${course}&status=${status}`;
            
            // Redirect to the print preview page
            window.location.href = printUrl;
        }
    });
});
</script>
    <!-- button -->
<script src="js/jquery-3.6.0.min.js" ></script>

<script src="js/datatables-simple-demo.js"></script>

    <!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>

<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>

<!-- Simple Datatables -->
<script src="js/tables.js" defer></script>

<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>
</body>
</html>