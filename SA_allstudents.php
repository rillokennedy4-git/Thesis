<?php
session_start();

// Check if the user is logged in by verifying the presence of `user_id` in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;


// Function to automatically archive students with Record_Status = COMPLETE
function autoArchiveStudents() {
    global $conn;
    $dateArchived = date('Y-m-d H:i:s');
    $baseArchivePath = $_SERVER['DOCUMENT_ROOT'] . "/NEW/archive/";

    // Fetch students with Record_Status = COMPLETE and not yet archived
    $fetchStudentsSql = "
        SELECT 
            s.StudentNumber, s.LastName, s.FirstName, s.MiddleName, s.Gender, s.Course_Id, 
            s.Status, s.AcademicYr_Id, s.Semester_Id, ay.Academic_Year
        FROM tbl_student s
        JOIN tbl_record_status rs ON s.StudentNumber = rs.StudentNumber
        JOIN tbl_academicyr ay ON s.AcademicYr_Id = ay.AcademicYr_Id
        WHERE rs.Record_Status = 'COMPLETE' AND s.Category_Id != 2
    ";
    $studentsResult = $conn->query($fetchStudentsSql);

    if ($studentsResult->num_rows > 0) {
        while ($studentData = $studentsResult->fetch_assoc()) {
            $studentNumber = $studentData['StudentNumber'];
            $lastName = $studentData['LastName'];
            $firstName = $studentData['FirstName'];
            $academicYear = str_replace(" ", "_", $studentData['Academic_Year']); // Fix spaces in folder name

            // Define archive folder path
            $studentFolder = "{$studentNumber}_{$lastName}_{$firstName}";
            $archivePath = "{$baseArchivePath}A.Y._{$academicYear}/{$studentFolder}/";

            // Create archive directories if they don’t exist
            if (!is_dir($archivePath)) {
                mkdir($archivePath, 0777, true);
            }

            // Move student's documents to archive folder
            $fetchDocumentsSql = "SELECT Document_ID, File_Path FROM tbl_documents WHERE Student_Id = ?";
            $docStmt = $conn->prepare($fetchDocumentsSql);
            $docStmt->bind_param("s", $studentNumber);
            $docStmt->execute();
            $docResult = $docStmt->get_result();

            while ($docRow = $docResult->fetch_assoc()) {
                $documentId = $docRow['Document_ID'];
                $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . "/NEW/" . $docRow['File_Path'];
                $fileName = basename($oldFilePath);
                $newFilePath = $archivePath . $fileName;
                $databaseFilePath = "archive/A.Y._{$academicYear}/{$studentFolder}/{$fileName}";

                // Move file if it exists
                if (file_exists($oldFilePath)) {
                    rename($oldFilePath, $newFilePath);
                }

                // Update database path
                $updateDocumentSql = "UPDATE tbl_documents SET File_Path = ?, Category_Id = 2 WHERE Document_ID = ?";
                $updateDocStmt = $conn->prepare($updateDocumentSql);
                $updateDocStmt->bind_param("si", $databaseFilePath, $documentId);
                $updateDocStmt->execute();
            }

            // Insert student into archive table
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
            $archiveStmt->execute();

            // Update Student's Category to 2 (Archived)
            $updateStudentSql = "UPDATE tbl_student SET Category_Id = 2 WHERE StudentNumber = ?";
            $updateStmt = $conn->prepare($updateStudentSql);
            $updateStmt->bind_param("s", $studentNumber);
            $updateStmt->execute();
        }
    }
}



// Call the auto archive function
autoArchiveStudents();

// Handle AJAX request for filtering
if (isset($_POST['yearValue']) || isset($_POST['semesterValue']) || isset($_POST['programValue']) || isset($_POST['statusValue']) ) {
    $yearValue = $_POST['yearValue'];
    $semesterValue = $_POST['semesterValue'];
    $programValue = $_POST['programValue'];
    $statusValue = $_POST['statusValue'];
    

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
   

    $sql .= " ORDER BY tbl_student.LastName ASC";
    $students = $conn->query($sql) or die($conn->error);

    $result = '';

    // Loop through each row of the query results
    while ($row = $students->fetch_assoc()) {
        $result .= "<tr>
                        <td>{$row['StudentNumber']}</td>
                        <td>{$row['LastName']}, {$row['FirstName']} {$row['MiddleName']}</td>
                        <td>{$row['course_name']}</td>
                        <td>{$row['Academic_Year']}</td>
                        <td>{$row['Semester_Name']}</td>
                        <td>{$row['Status']}</td>
                        <td>{$row['Record_Status']}</td>
                        <td><a href='SA_studentview.php?studentNumber={$row['StudentNumber']}' title='View'><i class='fas fa-eye'></i></a></td>
                    </tr>";
    }

    echo $result;
    exit;
}

// Query to get distinct academic years, semesters, and programs for the dropdown
$yearQuery = "SELECT DISTINCT Academic_Year FROM tbl_academicyr ORDER BY Academic_Year ASC";
$yearResults = $conn->query($yearQuery) or die($conn->error);

$semesterQuery = "SELECT DISTINCT Semester_Name FROM tbl_semester ORDER BY Semester_Name ASC";
$semesterResults = $conn->query($semesterQuery) or die($conn->error);

$programQuery = "SELECT DISTINCT course_name FROM tbl_course ORDER BY course_name ASC";
$programResults = $conn->query($programQuery) or die($conn->error);

// New Queries for Status, Gender, and Record Status
$statusQuery = "SELECT DISTINCT Status FROM tbl_student ORDER BY Status ASC";
$statusResults = $conn->query($statusQuery) or die($conn->error);





// Fetch all students
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
LEFT JOIN
    tbl_record_status ON tbl_student.StudentNumber = tbl_record_status.StudentNumber
WHERE 
    tbl_student.Category_id = 1
ORDER BY 
    tbl_student.LastName ASC
";

$students = $conn->query($sql);
if (!$students) {
    die("Initial Query Error: " . $conn->error);
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
<title>Archiving System</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
</head>



<body class="sb-nav-fixed">
            <div >
            <?php include 'topnav.php'; ?>
            </div>
            <div id="layoutSidenav">
                <br>
                <br>
                <br>

            <div id="layoutSidenav_nav">
            <?php include 'sidebar1.php'; ?>
            </div>
            
            <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">LIST OF ALL STUDENTS</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">CONTAINING THEIR PROFILE</li>
                    </ol>

                    <!-- Filter Panel -->
                    <div class="card mb-4">

                        <div class="card-body">
                        <!-- Archive icon button
                        <div class="d-flex justify-content-end mb-3">
                            <button id="archiveSelected" class="btn btn-success btn-sm" title="Archive Selected">
                                <i class="fas fa-archive"></i> Archive
                            </button>
                            
                        </div> -->
                            <div class="row ">
                                <div class="col-md-2 mb-3">
                                    <label for="yearFilter" class="form-label">Year Enrolled:</label>
                                    <select id="yearFilter" class="form-select">
                                        <option value="">All</option>
                                        <?php while ($row = $yearResults->fetch_assoc()) { ?>
                                            <option value="<?php echo $row['Academic_Year']; ?>"><?php echo $row['Academic_Year']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="semesterFilter" class="form-label">Semester:</label>
                                    <select id="semesterFilter" class="form-select">
                                        <option value="">All</option>
                                        <?php while ($row = $semesterResults->fetch_assoc()) { ?>
                                            <option value="<?php echo $row['Semester_Name']; ?>"><?php echo $row['Semester_Name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="programFilter" class="form-label">Program:</label>
                                    <select id="programFilter" class="form-select">
                                        <option value="">All</option>
                                        <?php while ($row = $programResults->fetch_assoc()) { ?>
                                            <option value="<?php echo $row['course_name']; ?>"><?php echo $row['course_name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="statusFilter" class="form-label">Status:</label>
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All</option>
                                        <?php while ($row = $statusResults->fetch_assoc()) { ?>
                                            <option value="<?php echo $row['Status']; ?>"><?php echo $row['Status']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                               
                                
                                <!-- Search and Export Buttons -->
                                <div class="col-md-2 mb-3">
                                    <button id="searchButton" class="btn btn-primary">Search</button>
                                    
                                </div>
                            </div>
                            
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Name</th>
                                        
                                        <th>Program</th>
                                        <th>Year Enrolled</th>
                                        <th>Sem Enrolled</th>
                                        <th>Status</th>
                                        <th>Record Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody">
                                    <?php while ($row = $students->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?php echo $row['StudentNumber']; ?></td>
                                            <td><?php echo $row['LastName'] . ', ' . $row['FirstName'] . ' ' . $row['MiddleName']; ?></td>

                                            <td><?php echo $row['course_name']; ?></td>
                                            <td><?php echo $row['Academic_Year']; ?></td>
                                            <td><?php echo $row['Semester_Name']; ?></td>
                                            <td><?php echo $row['Status']; ?></td>
                                            <td><?php echo $row['Record_Status']; ?></td>
                                            <td><a href="SA_studentview.php?studentNumber=<?php echo $row['StudentNumber']; ?>" title="View"><i class="fas fa-eye"></i></a></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>





    <style>
        #searchButton {
        white-space: nowrap; /* Prevents the button text from wrapping */
    }
    .row  {
        display: flex;
        flex-wrap: nowrap;
        gap: 1rem; /* Space between each filter item */
        align-items: flex-end; /* Aligns labels and dropdowns with the button */
    }
    .col-md-2 {
        flex: 1; /* Makes each item take equal width */
        min-width: 150px; /* Ensures dropdowns have a minimum width */
    }

    </style>

<script src="js/jsforfuctioninallstudents.js"></script>
    
<script src="js/jsforfilteronallstudents.js"></script>
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
<script src="js/preload.js" defer></script>
</body>
</html>
