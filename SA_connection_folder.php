<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the user is logged in by verifying the presence of `user_id` in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
// Ensure that only users with the 'Staff' role can access this page
if ($_SESSION['role'] !== 'System Admin') {
    // Redirect to the login page or an error page if the role is not 'Staff'
    header("Location: index.php");
    exit();
}


// Include the database connection file
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Get the AcademicYr_Id from the URL
if (!isset($_GET['academicYearId'])) {
    die("Academic Year ID not specified.");
}
$academicYearId = intval($_GET['academicYearId']);

// Query to fetch the actual Academic Year name
$yearQuery = "SELECT Academic_Year FROM tbl_academicyr WHERE AcademicYr_Id = ?";
$stmtYear = $conn->prepare($yearQuery);
$stmtYear->bind_param("i", $academicYearId);
$stmtYear->execute();
$yearResult = $stmtYear->get_result();
$academicYearName = $yearResult->fetch_assoc()['Academic_Year'] ?? 'Unknown Year';

// Query to fetch archived students for the specified academic year
$sql = "
SELECT 
    tbl_archive.StudentNumber,
    tbl_archive.LastName,
    tbl_archive.FirstName,
    tbl_archive.MiddleName,
    tbl_archive.Gender,
    tbl_course.course_name AS Course,
    tbl_archive.Status,
    tbl_academicyr.Academic_Year,
    tbl_semester.Semester_Name,
    tbl_archive.Date_Archived
FROM 
    tbl_archive
JOIN 
    tbl_course ON tbl_archive.Course_Id = tbl_course.Course_Id
JOIN 
    tbl_academicyr ON tbl_archive.AcademicYr_Id = tbl_academicyr.AcademicYr_Id
JOIN 
    tbl_semester ON tbl_archive.Semester_Id = tbl_semester.Semester_Id
WHERE 
    tbl_archive.AcademicYr_Id = ?
ORDER BY 
    tbl_archive.LastName ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $academicYearId);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Archiving System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">

</head>
<body class="sb-nav-fixed">
    <div>
        <?php include 'topnav2.php'; ?>
    </div>
    <div>
        <div>
            
        </div>
            <div >
                <main><br><br><br>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Archived Students for Academic Year <?php echo $academicYearName; ?></h1>
                        <div class="card mt-4">
                            <div class="card-body">
                                <table id="datatablesSimple" class="table table-striped">
                                <thead >
                                        <tr>
                                            <th>Student Number</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Course</th>
                                            <th>Academic Year</th>
                                            <th>Semester</th>
                                            <th>Date Archived</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) { ?>
                                                <tr>
                                                    <td><?php echo $row['StudentNumber']; ?></td>
                                                    <td><?php echo $row['LastName'] . ', ' . $row['FirstName'] . ' ' . $row['MiddleName']; ?></td>
                                                    <td><?php echo $row['Gender']; ?></td>
                                                    <td><?php echo $row['Course']; ?></td>
                                                    <td><?php echo $row['Academic_Year']; ?></td>
                                                    <td><?php echo $row['Semester_Name']; ?></td>
                                                    <td><?php echo $row['Date_Archived']; ?></td>
                                                    <td><a href="SA_studentview.php?studentNumber=<?php echo $row['StudentNumber']; ?>" title="View"><i class="fas fa-eye"></i></a></td>
                                                </tr>
                                            <?php }
                                        } else {
                                            echo "<tr><td colspan='8'>No archived students found for this academic year.</td></tr>";
                                        } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                </div>
            </main>
        </div>
    </div>
<!-- button -->
<script src="js/jquery-3.6.0.min.js" defer></script>

<script src="js/datatables-simple-demo.js" defer></script>

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
