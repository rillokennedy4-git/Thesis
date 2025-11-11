<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Search Functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch Academic Years with Search (No Pagination)
$searchQuery = "";
if (!empty($search)) {
    $searchQuery = "AND ay.Academic_Year LIKE ?";
}
$yearQuery = "
    SELECT DISTINCT ay.Academic_Year, ay.AcademicYr_Id
    FROM tbl_archive AS a
    JOIN tbl_academicyr AS ay ON a.AcademicYr_Id = ay.AcademicYr_Id
    WHERE 1=1 $searchQuery
    ORDER BY ay.AcademicYr_Id ASC
";

$stmt = $conn->prepare($yearQuery);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("s", $searchParam);
}
$stmt->execute();
$yearResults = $stmt->get_result();

// Count Total Results (Optional, if needed for other purposes)
$countQuery = "
    SELECT COUNT(DISTINCT ay.AcademicYr_Id) AS total
    FROM tbl_archive AS a
    JOIN tbl_academicyr AS ay ON a.AcademicYr_Id = ay.AcademicYr_Id
    WHERE 1=1 $searchQuery
";

$stmtCount = $conn->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bind_param("s", $searchParam);
}
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$total_records = $countResult->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Archiving System</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        .table-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px; /* Add some spacing below the header */
        }
        .table-header-container .btn {
            display: flex;
            align-items: center;
        }
        .table-header-container .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body class="sb-nav-fixed">
<div >
            <?php include 'topnav2.php'; ?>
        </div>

        <div >
            <main>

    <div class="container mt-5">
        <!-- Header -->
        <div class="text-center">
            <br><br><br>
            <h1 class="header-title">Archived Academic Years</h1>
            <p class="header-subtitle">View and manage archived students for each academic year.</p>
        </div>

                <!-- Search Bar and Table -->
                <div class="card">
                    <div class="card-body">
                        <!-- Table Header with Search and Download Button -->
                        <div class="table-header-container">
                            <!-- Search Bar -->
                            <form class="d-flex" method="GET" action="">
                                
                            </form>

                        </div>

                        <!-- Table -->
                        <table id="datatablesSimple" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($yearResults->num_rows > 0): ?>
                                    <?php while ($row = $yearResults->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['Academic_Year']; ?></td>
                                            <td class="text-center">
                                                <a href="connection_folder.php?academicYearId=<?php echo $row['AcademicYr_Id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-folder-open"></i> View Students
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No archived students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
 
        </main>
        
    </div>
    <script>document.addEventListener('keydown', function(event) {
    // Check if the Alt key is pressed along with the Left or Right Arrow key
    if (event.altKey && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
        // Prevent the default behavior (navigation)
        event.preventDefault();
        console.log('Alt + Arrow key combination disabled.');
    }
});
        // Disable Alt + Arrow key combination (Go Back and Go Forward)
        document.addEventListener('keydown', function(event) {
            // Check if the Alt key is pressed along with the Left or Right Arrow key
            if (event.altKey && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
                // Prevent the default behavior (navigation)
                event.preventDefault();
                console.log('Alt + Arrow key combination disabled.');
            }
        });

        // Disable history navigation using popstate event
        window.addEventListener('popstate', function(event) {
            // Prevent the default behavior (navigation)
            event.preventDefault();
            console.log('History navigation disabled.');
        });</script>
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
