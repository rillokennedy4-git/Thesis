<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Fetch the latest academic year with archived students
$currentYearQuery = "
    SELECT ay.Academic_Year, ay.AcademicYr_Id 
    FROM tbl_archive AS a
    JOIN tbl_academicyr AS ay ON a.AcademicYr_Id = ay.AcademicYr_Id
    ORDER BY ay.AcademicYr_Id DESC
    LIMIT 1
";
$currentYearResult = $conn->query($currentYearQuery);

// Check if there's a valid result for the current year
if ($currentYearResult && $currentYearResult->num_rows > 0) {
    $currentYearData = $currentYearResult->fetch_assoc();
    $currentAcademicYear = $currentYearData['Academic_Year'];
    $currentAcademicYearId = $currentYearData['AcademicYr_Id'];
} else {
    $currentAcademicYear = "N/A";
    $currentAcademicYearId = 0;
}

// Fetch archived students for the current academic year
$studentsQuery = "
    SELECT 
        a.StudentNumber, 
        a.LastName, 
        a.FirstName, 
        a.MiddleName, 
        c.Course_Name, 
        s.Semester_Name, 
        a.Date_Archived
    FROM tbl_archive AS a
    JOIN tbl_course AS c ON a.Course_Id = c.Course_Id
    JOIN tbl_semester AS s ON a.Semester_Id = s.Semester_Id
    WHERE a.AcademicYr_Id = ?
    ORDER BY a.LastName ASC
";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("i", $currentAcademicYearId);
$stmt->execute();
$studentsResult = $stmt->get_result();
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
>
    <main>

    <div class="container mt-5">
        <!-- Header -->
        <div class="text-center">
            <br>
            <h1 class="header-title">Current Year Archived Students</h1>
            <p class="header-subtitle">Academic Year: <?php echo htmlspecialchars($currentAcademicYear); ?></p>
        </div>

        <!-- Table -->
        <div class="card mt-4">
            <div class="card-body">
                <?php if ($currentAcademicYearId > 0): ?>
                    <table id="datatablesSimple" class="table table-striped">
                    <thead >
                            <tr>
                                <th>Student Number</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Semester</th>
                                <th>Date Archived</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($studentsResult->num_rows > 0): ?>
                                <?php while ($row = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['StudentNumber']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['LastName'] . ", " . $row['FirstName'] . " " . $row['MiddleName']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['Course_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Semester_Name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Date_Archived']); ?></td>
                                        <td class="text-center">
                                            <a href="SA_studentview.php?studentNumber=<?php echo htmlspecialchars($row['StudentNumber']); ?>" 
                                               class="btn btn-view" title="View Profile">
                                               <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No archived students found for the current academic year.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center">
                        <p class="text-danger">No archived students found for any academic year.</p>
                    </div>
                <?php endif; ?>
            </div>
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
