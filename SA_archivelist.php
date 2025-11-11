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

// Pagination Variables
$results_per_page = 5; // Number of rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$start_from = ($page - 1) * $results_per_page;

// Search Functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch Academic Years with Pagination and Search
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
    LIMIT ?, ?
";

$stmt = $conn->prepare($yearQuery);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("sii", $searchParam, $start_from, $results_per_page);
} else {
    $stmt->bind_param("ii", $start_from, $results_per_page);
}
$stmt->execute();
$yearResults = $stmt->get_result();

// Count Total Results for Pagination
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
$total_pages = ceil($total_records / $results_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiving System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="shortcut icon" href="images/ccticon.png">
    <link href="css/styles1.css" rel="stylesheet" />
    

</head>
<body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="staff.php">Welcome, Staff!</a>
<!-- Sidebar Toggle-->
<button class="btn btn-sm btn-link text-white" onclick="history.back()">
  <i class="fas fa-arrow-left"></i> Back
</button>
           
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
            
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#" id="logoutBtn">Log Out</a></li>
                        

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                fetch('/csrf-token')
                                    .then(response => response.json())
                                    .then(data => {
                                        document.querySelector('input[name="_token"]').value = data.csrfToken;
                                    });
                            });
                        </script>

                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 script -->

                    </ul>
                </li>
            </ul>
        </nav>

        

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 script -->

        <script>
document.getElementById('logoutBtn').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent immediate navigation
    Swal.fire({
        title: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, logout'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php'; // Redirect to logout.php to destroy the session
        }
    });
});


</script>



<div id="layoutSidenav_content">
        <main>

    <div class="container mt-5">
        <!-- Header -->
        <div class="text-center">
            <br>
            <h1 class="header-title">Archived Academic Years</h1>
            <p class="header-subtitle">View and manage archived students for each academic year.</p>
        </div>

        <!-- Search Bar and Table -->
        <div class="card">
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped">
                <thead >
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
                                        <a href="SA_connectionfolder.php?academicYearId=<?php echo $row['AcademicYr_Id']; ?>" class="btn btn-primary btn-sm">
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
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
